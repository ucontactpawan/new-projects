async function sendNotification(notificationData) {
    if (!("Notification" in window)) {
        console.log('Notifications not supported');
        return;
    }

    try {
        const permission = await Notification.requestPermission();
        if (permission !== "granted") {
            console.log('Notification permission not granted');
            return;
        }

        const notification = new Notification(notificationData.title, {
            body: notificationData.body,
            icon: notificationData.icon,
            tag: `birthday-${notificationData.id}`,
            renotify: true
        });

        notification.onclick = () => {
            window.focus();
            window.location.href = notificationData.url;
        };

    } catch (error) {
        console.error('Error sending notification:', error);
    }
}

async function checkAndSendNotifications() {
    try {
        const response = await fetch('api/get_pending_notifications.php');
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const result = await response.json();

        if (result.status === 'success' && result.notifications && Array.isArray(result.notifications)) {
            for (const notification of result.notifications) {
                await sendNotification(notification);
            }
            
            // Update sidebar bell icon if there are notifications
            const bellIcon = document.querySelector('.notification-bell');
            if (bellIcon) {
                bellIcon.classList.add('has-notifications');
            }
        }
    } catch (error) {
        console.error('Error checking notifications:', error);
    }
}

// Initialize notifications on page load
document.addEventListener('DOMContentLoaded', async () => {
    // Check for notifications immediately
    await checkAndSendNotifications();
    
    // Then check every hour
    setInterval(checkAndSendNotifications, 60 * 60 * 1000);
});
