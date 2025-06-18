<?php
session_start();
include 'includes/db.php';
include 'includes/auth.php';
include 'includes/birthday_functions.php';

// Get birthdays for current month only
$birthdays = getBirthdays();

if (!$birthdays) {
    echo "Error fetching birthdays.";
    exit;
}

$currentMonth = date('F'); // Get current month name
$todayDay = date('d');
$todayMonth = date('m');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Birthday Celebrations - Portal</title>
    <link rel="stylesheet" href="css/birthday.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Base styles -->
    <link rel="stylesheet" href="css/style.css">
    <!-- Component styles -->
    <link rel="stylesheet" href="css/navbar.css">
</head>
<body>


<!-- "Today's Birthdays": Only employees whose birthday is today.
"Upcoming Birthdays": Only employees whose birthday is after today (in this month).
"Recent Birthdays": Only employees whose birthday is before today (in this month). -->

     
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
                    while($birthday = $birthdays->fetch_assoc()): 
                        $dobDay = date('d', strtotime($birthday['dob']));
                        $dobMonth = date('m', strtotime($birthday['dob']));
                        if($dobDay == $todayDay && $dobMonth == $todayMonth):
                            $foundToday = true;
                    ?>
                        <div class="birthday-card highlight-today">
                            <div class="cake-icon">🎂</div>
                            <div class="employee-name"><?php echo htmlspecialchars($birthday['employee_name']); ?></div>
                            <div class="birth-date"><?php echo date('d M Y', strtotime($birthday['dob'])); ?></div>
                            <div class="days-until">Today</div>
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

            <!-- Upcoming Birthdays -->            <div class="section-container">
                <h2>Upcoming Birthdays</h2>
                <div class="birthday-cards">
                    <?php 
                    $foundUpcoming = false;
                    $birthdays->data_seek(0);
                    while($birthday = $birthdays->fetch_assoc()): 
                        $dobDay = date('d', strtotime($birthday['dob']));
                        $dobMonth = date('m', strtotime($birthday['dob']));
                        if($dobMonth == $todayMonth && $dobDay > $todayDay):
                            $foundUpcoming = true;
                            $daysUntil = $birthday['days_until_birthday'];
                            $whenText = ($daysUntil == 1) ? "Tomorrow" : "in {$daysUntil} days";
                    ?>
                        <div class="birthday-card">
                            <div class="cake-icon">🎂</div>
                            <div class="employee-name"><?php echo htmlspecialchars($birthday['employee_name']); ?></div>
                            <div class="birth-date"><?php echo date('d M Y', strtotime($birthday['dob'])); ?></div>
                            <div class="days-until"><?php echo $whenText; ?></div>
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
                    while($birthday = $birthdays->fetch_assoc()): 
                        $dobDay = date('d', strtotime($birthday['dob']));
                        $dobMonth = date('m', strtotime($birthday['dob']));
                        if($dobMonth == $todayMonth && $dobDay < $todayDay):
                            $foundRecent = true;
                            $daysAgo = $todayDay - $dobDay;
                            $whenText = ($daysAgo == 1) ? "1 day ago" : "{$daysAgo} days ago";
                    ?>
                        <div class="birthday-card">
                            <div class="cake-icon">🎂</div>
                            <div class="employee-name"><?php echo htmlspecialchars($birthday['employee_name']); ?></div>
                            <div class="birth-date"><?php echo date('d M Y', strtotime($birthday['dob'])); ?></div>
                            <div class="days-until"><?php echo $whenText; ?></div>
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

    <script src="js/birthday.js"></script>
</body>
</html>