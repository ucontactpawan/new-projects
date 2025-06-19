<?php
require_once 'includes/db.php';

date_default_timezone_set('Asia/Kolkata');

$today = date('Y-m-d');
$sevenDaysLater = date('Y-m-d', strtotime('+7 days'));


$sql = "
SELECT e.id AS user_id, e.name, ed.dob 
FROM employees e 
JOIN employee_details ed ON e.id = ed.employee_id
WHERE DATE_FORMAT(ed.dob, '%m-%d') 
BETWEEN DATE_FORMAT(CURDATE(), '%m-%d') AND DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 7 DAY), '%m-%d')
";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $user_id = $row['user_id'];
        $name = $row['name'];
        $dob = $row['dob'];
        $birthdayDate = date('Y') . '-' . date('m-d', strtotime($dob)); // birthday this year

        // Check if already inserted today
        $checkSql = "SELECT * FROM notifications 
                     WHERE user_id = $user_id 
                     AND DATE(last_sent) = CURDATE() 
                     AND birthday_date = '$birthdayDate' 
                     AND status = 1";

        $check = $conn->query($checkSql);

        if ($check->num_rows == 0) {
            $title = "🎂 Upcoming Birthday";
            $message = "$name's birthday is on " . date('d M', strtotime($dob)) . "!";
            
            $insertSql = "INSERT INTO notifications (user_id, title, message, birthday_date, status, last_sent) 
                          VALUES ($user_id, '$title', '$message', '$birthdayDate', 1, NOW())";

            $conn->query($insertSql);
        }
    }

    echo json_encode(["success" => true, "message" => "Notifications inserted."]);
} else {
    echo json_encode(["success" => false, "message" => "No upcoming birthdays found."]);
}
?>
