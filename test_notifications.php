<?php
session_start();
include 'includes/db.php';
include 'includes/notification_functions.php';

// Only allow access to admins/HR
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'hr','employee'])) {
    header("Location: login.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = createBirthdayNotification(
        $_SESSION['user_id'],
        "Test Employee",
        date('Y-m-d')
    );
    $message = $result ? "Test notification created!" : "Error creating notification";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Notifications</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div style="padding: 20px;">
        <h1>Test Notifications</h1>
        
        <?php if (isset($message)): ?>
            <p><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <form method="POST">
            <button type="submit">Create Test Notification</button>
        </form>

        <div id="notificationStatus"></div>
    </div>

    <script src="js/notifications.js?v=1.1"></script>
    <script>
    document.addEventListener('DOMContentLoaded', async () => {
        const status = document.getElementById('notificationStatus');
        
        try {
            if (!("Notification" in window)) {
                status.textContent = "Browser doesn't support notifications";
                return;
            }

            if (Notification.permission === "granted") {
                status.textContent = "Notifications are enabled";
                await checkAndSendNotifications();
            } else if (Notification.permission !== "denied") {
                const permission = await Notification.requestPermission();
                status.textContent = `Notification permission: ${permission}`;
                if (permission === "granted") {
                    await checkAndSendNotifications();
                }
            } else {
                status.textContent = "Notifications are denied";
            }
        } catch (err) {
            status.textContent = `Error: ${err.message}`;
            console.error(err);
        }
    });
    </script>
</body>
</html>
