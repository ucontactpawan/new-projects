<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'includes/db.php';
include 'includes/auth.php';
include 'includes/birthday_functions.php';

// Get birthdays for current month
$birthdays = getBirthdays();
if (!$birthdays) {
    echo "Error fetching birthdays.";
    exit;
}

$currentMonth = date('F');
$todayDay = date('d');
$todayMonth = date('m');
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Birthday Celebrations - Portal</title>

  <!-- CSS Links -->
  <link rel="stylesheet" href="css/birthday.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" />
  <link rel="stylesheet" href="css/style.css" />
  <link rel="stylesheet" href="css/navbar.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
</head>
<body>

<?php include('includes/navbar.php'); ?>
<?php include('includes/sidebar.php'); ?>

<div class="birthday-container">
  <h1>EMPLOYEE BIRTHDAYS</h1>

  <div class="birthday-sections">

    <!-- Today's Birthdays -->
    <div class="section-container">
      <h2>Today's Birthdays</h2>
      <div class="birthday-cards">
        <?php 
        $foundToday = false;
        $birthdays->data_seek(0);
        while ($birthday = $birthdays->fetch_assoc()):
          $dobDay = date('d', strtotime($birthday['dob']));
          $dobMonth = date('m', strtotime($birthday['dob']));
          if ($dobDay == $todayDay && $dobMonth == $todayMonth):
            $foundToday = true;
        ?>
          <div class="birthday-card highlight-today">
            <div class="cake-icon">🎂</div>
            <h3 class="employee-name"><?php echo htmlspecialchars($birthday['employee_name']); ?></h3>
            <p class="birth-date"><?php echo date('d M Y', strtotime($birthday['dob'])); ?></p>
          </div>
        <?php 
          endif;
        endwhile;
        if (!$foundToday): 
        ?>
          <div class="no-events">No birthdays today.</div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Upcoming Birthdays -->
    <div class="section-container">
      <h2>Upcoming Birthdays</h2>
      <div class="birthday-cards">
        <?php 
        $foundUpcoming = false;
        $birthdays->data_seek(0);
        while ($birthday = $birthdays->fetch_assoc()):
          $dobDay = date('d', strtotime($birthday['dob']));
          $dobMonth = date('m', strtotime($birthday['dob']));
          if ($dobMonth == $todayMonth && $dobDay > $todayDay):
            $foundUpcoming = true;
        ?>
          <div class="birthday-card future">
            <div class="cake-icon">🎂</div>
            <h3 class="employee-name"><?php echo htmlspecialchars($birthday['employee_name']); ?></h3>
            <p class="birth-date"><?php echo date('d M Y', strtotime($birthday['dob'])); ?></p>
          </div>
        <?php 
          endif;
        endwhile;
        if (!$foundUpcoming): 
        ?>
          <div class="no-events">No upcoming birthdays this month.</div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Recent Birthdays -->
    <div class="section-container">
      <h2>Recent Birthdays</h2>
      <div class="birthday-cards">
        <?php 
        $foundRecent = false;
        $birthdays->data_seek(0);
        while ($birthday = $birthdays->fetch_assoc()):
          $dobDay = date('d', strtotime($birthday['dob']));
          $dobMonth = date('m', strtotime($birthday['dob']));
          if ($dobMonth == $todayMonth && $dobDay < $todayDay):
            $foundRecent = true;
        ?>
          <div class="birthday-card past">
            <div class="cake-icon">🎂</div>
            <h3 class="employee-name"><?php echo htmlspecialchars($birthday['employee_name']); ?></h3>
            <p class="birth-date"><?php echo date('d M Y', strtotime($birthday['dob'])); ?></p>
          </div>
        <?php 
          endif;
        endwhile;
        if (!$foundRecent): 
        ?>
          <div class="no-events">No recent birthdays this month.</div>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<?php include('includes/footer.php'); ?>

<!-- Scripts -->
<script src="js/birthday.js"></script>
<script src="js/notifications.js?v=1.1"></script>
<script>
document.addEventListener('DOMContentLoaded', async () => {
    try {
        if (!("Notification" in window)) {
            console.warn("This browser doesn't support notifications");
            return;
        }

        // Request permission if needed
        if (Notification.permission !== "granted") {
            const permission = await Notification.requestPermission();
            if (permission !== "granted") {
                console.log("Notification permission denied");
                return;
            }
        }

        // Initialize notifications
        await checkAndSendNotifications();
        
        // Add visibility change listener
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                checkAndSendNotifications();
            }
        });

    } catch (err) {
        console.error('Notification init error:', err);
    }
});
</script>

</body>
</html>
