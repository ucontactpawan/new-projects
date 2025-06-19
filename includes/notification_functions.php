<?php
require_once 'db.php';

function createBirthdayNotification($user_id, $employee_name, $birth_date) {
    global $conn;
    
    $title = "Birthday Reminder";
    $message = "{$employee_name}'s birthday is on {$birth_date}";
    
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, birthday_date, status) VALUES (?, ?, ?, ?, 1)");
    $stmt->bind_param("isss", $user_id, $title, $message, $birth_date);
    
    return $stmt->execute();
}

function getPendingNotifications($user_id) {
    global $conn;
    
    // First, check for any upcoming birthdays and create notifications if needed
    $upcoming = getUpcomingBirthdays();
    while ($employee = $upcoming->fetch_assoc()) {
        // Create a notification if one doesn't exist for this birthday
        $check = $conn->prepare("
            SELECT id FROM notifications 
            WHERE user_id = ? AND birthday_date = ? AND YEAR(created_at) = YEAR(CURRENT_DATE)
        ");
        $birthday_date = date('Y-m-d', strtotime($employee['dob']));
        $check->bind_param("is", $user_id, $birthday_date);
        $check->execute();
        $result = $check->get_result();
        
        if ($result->num_rows === 0) {
            createBirthdayNotification($user_id, $employee['name'], $birthday_date);
        }
    }
    
    // Now get all pending notifications
    $stmt = $conn->prepare("
        SELECT n.* 
        FROM notifications n
        WHERE n.status = 1 
        AND (n.last_sent IS NULL OR DATE(n.last_sent) != CURDATE())
        AND n.user_id = ?
    ");
    
    if (!$stmt) {
        error_log("Error preparing statement: " . $conn->error);
        return null;
    }
    
    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        error_log("Error executing statement: " . $stmt->error);
        return null;
    }
    
    return $stmt->get_result();
}

function markNotificationAsSent($notification_id) {
    global $conn;
    
    $stmt = $conn->prepare("UPDATE notifications SET last_sent = NOW() WHERE id = ?");
    $stmt->bind_param("i", $notification_id);
    
    return $stmt->execute();
}

function getUpcomingBirthdays() {
    global $conn;
    
    $query = "
        SELECT e.id, e.name, ed.dob
        FROM employees e
        JOIN employee_details ed ON e.id = ed.employee_id
        WHERE 
            DATE_FORMAT(ed.dob, '%m-%d') 
            BETWEEN DATE_FORMAT(NOW(), '%m-%d')
            AND DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 7 DAY), '%m-%d')
        ORDER BY DATE_FORMAT(ed.dob, '%m-%d')
    ";
    
    $result = $conn->query($query);
    return $result;
}
