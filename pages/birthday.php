<?php 

session_start();
include '../includes/db.php';
include '../includes/auth.php';
include '../includes/birthday_functions.php';

$birthdays = getUpcomingBirthdays(5);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Birthdays</title>
    <link rel="stylesheet" href="../css/birthday.css">
</head>
<body>
    <div class="birthday-container">
        <h1>Upcoming Birthdays</h1>

        <div class="birthday-cards">
            <?php while($birthday = $birthdays->fetch_assoc()): ?>
                <div class="birthday-card" data-days="<?php echo $birthday['days_until_birthday']; ?>">
                    <div class="cake-icon">🎂</div>
                    <h3><?php echo htmlspecialchars($birthday['employee_name']); ?></h3>
                    <p class="birth-date"><?php echo date('d M', strtotime($birthday['dob'])); ?></p>
                    <p class="days-until">
                        <?php echo formatBirthday($birthday['dob'], $birthday['days_until_birthday']); ?>
                    </p>
                </div>
                <?php endwhile; ?>
        </div>
    </div>

    <script src="../js/birthday.js"></script>
</body>
</html>