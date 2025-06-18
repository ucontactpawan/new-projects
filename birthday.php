<?php
session_start();
include '../includes/db.php';
include '../includes/auth.php';
include '../includes/birthday_functions.php';

// Get birthdays for current month only
$birthdays = getBirthdays();

if (!$birthdays) {
    echo "Error fetching birthdays.";
    exit;
}

$currentMonth = date('F'); // Get current month name
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Birthday Celebrations - Portal</title>
    <link rel="stylesheet" href="../css/birthday.css">
</head>
<body>
    <div class="birthday-container">
        <h1>Birthday Celebrations</h1>
        
        <div class="birthday-sections">
            <div class="recent-birthdays">
                <h2>Recent Birthdays in <?php echo $currentMonth; ?></h2>
                <div class="birthday-cards">
                    <?php 
                    $foundRecent = false;
                    while($birthday = $birthdays->fetch_assoc()): 
                        if($birthday['birthday_status'] == 'recent'):
                            $foundRecent = true;
                    ?>
                        <div class="birthday-card past" data-days="<?php echo $birthday['days_until_birthday']; ?>">
                            <div class="cake-icon">🎂</div>
                            <h3><?php echo htmlspecialchars($birthday['employee_name']); ?></h3>
                            <p class="birth-date"><?php echo formatFullDOB($birthday['dob']); ?></p>
                            <p class="days-until">
                                <?php echo formatBirthdayDate($birthday['days_until_birthday']); ?>
                            </p>
                        </div>
                    <?php 
                        endif;
                    endwhile; 
                    if (!$foundRecent): 
                    ?>
                        <p class="no-birthdays">No birthdays from 1st to <?php echo date('jS', strtotime('-1 day')); ?> <?php echo $currentMonth; ?>.</p>
                    <?php 
                    endif;
                    $birthdays->data_seek(0); // Reset result pointer
                    ?>
                </div>
            </div>

            <div class="upcoming-birthdays">
                <h2>Upcoming Birthdays in <?php echo $currentMonth; ?></h2>
                <div class="birthday-cards">
                    <?php 
                    $foundUpcoming = false;
                    while($birthday = $birthdays->fetch_assoc()): 
                        if($birthday['birthday_status'] == 'upcoming'):
                            $foundUpcoming = true;
                    ?>
                        <div class="birthday-card future" data-days="<?php echo $birthday['days_until_birthday']; ?>">
                            <div class="cake-icon">🎂</div>
                            <h3><?php echo htmlspecialchars($birthday['employee_name']); ?></h3>
                            <p class="birth-date"><?php echo formatFullDOB($birthday['dob']); ?></p>
                            <p class="days-until">
                                <?php echo formatBirthdayDate($birthday['days_until_birthday']); ?>
                            </p>
                        </div>
                    <?php 
                        endif;
                    endwhile;
                    if (!$foundUpcoming):
                    ?>
                        <p class="no-birthdays">No upcoming birthdays from <?php echo date('jS'); ?> to <?php echo date('t'); ?>th <?php echo $currentMonth; ?>.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/birthday.js"></script>
</body>
</html>