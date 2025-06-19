<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/notification_functions.php';

// For testing purposes, set a user ID if not logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Change this to an existing user ID
}

// Debug function to check upcoming birthdays
function debugUpcomingBirthdays() {
    global $conn;
    $result = getUpcomingBirthdays();
    echo "<h3>Upcoming Birthdays (Next 7 Days)</h3>";
    if ($result && $result->num_rows > 0) {
        echo "<ul>";
        while ($row = $result->fetch_assoc()) {
            echo "<li>{$row['name']} - DOB: {$row['dob']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p class='error'>No upcoming birthdays found.</p>";
    }
}

// Debug function to create a test notification
function createTestNotification() {
    global $conn;
    $user_id = $_SESSION['user_id'];
    $test_name = "Test Employee";
    $test_date = date('Y-m-d', strtotime('+2 days'));
    
    if (createBirthdayNotification($user_id, $test_name, $test_date)) {
        echo "<p class='success'>Test notification created successfully!</p>";
    } else {
        echo "<p class='error'>Error creating test notification.</p>";
    }
}

// Handle form submissions
if (isset($_POST['action'])) {
    if ($_POST['action'] === 'create_test') {
        createTestNotification();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Debug Notifications</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        pre { background: #f5f5f5; padding: 10px; }
        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>
    <h1>Notification Debug Page</h1>
    
    <h2>Current Session</h2>
    <pre><?php print_r($_SESSION); ?></pre>    <h2>Database Status</h2>
    
    <?php debugUpcomingBirthdays(); ?>

    <h2>Notifications Table Content</h2>
    <?php
    $query = "SELECT * FROM notifications";
    $result = $conn->query($query);
    if ($result && $result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>
        <tr><th>ID</th><th>User ID</th><th>Title</th><th>Message</th><th>Birthday Date</th><th>Status</th><th>Last Sent</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['user_id']}</td>";
            echo "<td>{$row['title']}</td>";
            echo "<td>{$row['message']}</td>";
            echo "<td>{$row['birthday_date']}</td>";
            echo "<td>{$row['status']}</td>";
            echo "<td>{$row['last_sent']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error'>No notifications in database.</p>";
    }
    ?>

    <h2>Create Test Notification</h2>
    <form method="post">
        <input type="hidden" name="action" value="create_test">
        <button type="submit">Create Test Notification</button>
    </form>

    <h2>Pending Notifications</h2>
    <?php
    try {
        $notifications = getPendingNotifications($_SESSION['user_id']);
        if ($notifications) {
            echo "<pre>";
            while ($row = $notifications->fetch_assoc()) {
                print_r($row);
            }
            echo "</pre>";
        } else {
            echo "<p class='error'>No pending notifications found.</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
    }
    ?>

    <h2>Test Browser Notifications</h2>
    <button onclick="testNotification()">Send Test Notification</button>    <script>
        async function testNotification() {
            if (!("Notification" in window)) {
                alert("This browser does not support desktop notifications");
                return;
            }

            try {
                const permission = await Notification.requestPermission();
                if (permission === "granted") {
                    const notification = new Notification("Test Notification", {
                        body: "This is a test notification from the debug page",
                        icon: "/attendance-management/images/cake.png"
                    });
                    console.log("Test notification sent successfully");
                } else {
                    alert("Permission denied for notifications");
                }
            } catch (error) {
                console.error("Error sending test notification:", error);
                alert("Error sending notification: " + error.message);
            }
        }

        // Debug function to check API response
        async function debugCheckNotifications() {
            try {
                console.log('Fetching notifications...');
                const response = await fetch('api/get_pending_notifications.php');
                console.log('Response status:', response.status);
                const data = await response.json();
                console.log('API Response:', data);

                if (data.status === 'success' && data.notifications && data.notifications.length > 0) {
                    console.log('Found notifications:', data.notifications);
                    for (const notif of data.notifications) {
                        console.log('Sending notification:', notif);
                        await sendNotification(notif);
                    }
                } else {
                    console.log('No notifications found or error in response');
                }
            } catch (error) {
                console.error('Error in debugCheckNotifications:', error);
            }
        }

        // Load notifications.js first
        const script = document.createElement('script');
        script.src = '/attendance-management/js/notifications.js';
        script.onload = () => {
            console.log('notifications.js loaded successfully');
            // Check notifications after script is loaded
            debugCheckNotifications();
        };
        script.onerror = (error) => {
            console.error('Error loading notifications.js:', error);
        };
        document.head.appendChild(script);
    </script>

    <div style="margin-top: 20px;">
        <button onclick="debugCheckNotifications()">Check Pending Notifications</button>
        <div id="debugOutput" style="margin-top: 10px; padding: 10px; background: #f5f5f5;"></div>
    </div>
</body>
</html>
