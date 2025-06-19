<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

include '../includes/db.php';
include '../includes/notification_functions.php';

try {
    // Get pending notifications for the current user
    $notifications = getPendingNotifications($_SESSION['user_id']);
    
    if (!$notifications) {
        throw new Exception("Failed to fetch notifications");
    }
    
    $notificationData = [];
    
    while ($row = $notifications->fetch_assoc()) {
        // Create notification data
        $notificationData[] = [
            'id' => $row['id'],
            'title' => '🎂 Birthday Reminder',
            'icon' => '/attendance-management/images/cake.png',
            'body' => $row['message'],
            'url' => '/attendance-management/birthday.php'
        ];
        
        // Mark notification as sent
        markNotificationAsSent($row['id']);
    }
    
    // Log for debugging
    error_log("Sending notifications: " . json_encode($notificationData));
    
    echo json_encode([
        'status' => 'success',
        'notifications' => $notificationData,
        'user_id' => $_SESSION['user_id']
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
