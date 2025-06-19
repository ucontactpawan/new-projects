<?php
session_start();
include 'includes/db.php';
include 'includes/auth.php';
include 'includes/anniversary_functions.php';

$employees = getAnniversaries($conn);
list($todaysAnniversaries, $upcomingAnniversaries, $recentAnniversaries) = categorizeAnniversaries($employees);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Employee Anniversaries</title>
    <link rel="stylesheet" href="css/anniversary.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Base styles -->
    <link rel="stylesheet" href="css/style.css">
    <!-- Component styles -->
    <link rel="stylesheet" href="css/navbar.css">
    
</head>

<body>
    <?php include('includes/navbar.php'); ?>
    <?php include ('includes/sidebar.php'); ?> 
    <div class="anniversary-container">
        <h1>EMPLOYEE ANNIVERSARIES</h1>

        <div class="anniversary-sections">
            <!-- Today's Anniversaries -->
            <div class="todays-anniversaries">
                <h2 class="anniversary-heading">Today's Anniversaries</h2>
                <div class="anniversary-cards">
                    <?php if (count($todaysAnniversaries)): ?>
                        <?php foreach ($todaysAnniversaries as $emp): ?>
                            <div class="anniversary-card future" data-days="0">
                                <div class="cake-icon">🎉</div>
                                <h3><?php echo htmlspecialchars($emp['name']); ?></h3>
                                <p class="join-date"><?php echo date('d M Y', strtotime($emp['joining_date'])); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-anniversaries">No anniversaries today.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Upcoming Anniversaries -->
            <div class="upcoming-anniversaries">
                <h2 class="anniversary-heading">Upcoming Anniversaries</h2>
                <div class="anniversary-cards">
                    <?php if (count($upcomingAnniversaries)): ?>
                        <?php foreach ($upcomingAnniversaries as $emp): ?>
                            <div class="anniversary-card future">
                                <div class="cake-icon">🎉</div>
                                <h3><?php echo htmlspecialchars($emp['name']); ?></h3>
                                <p class="join-date"><?php echo date('d M Y', strtotime($emp['joining_date'])); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-anniversaries">No upcoming anniversaries this month.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Anniversaries -->
            <div class="recent-anniversaries">
                <h2 class="anniversary-heading">Recent Anniversaries</h2>
                <div class="anniversary-cards">
                    <?php if (count($recentAnniversaries)): ?>
                        <?php foreach ($recentAnniversaries as $emp): ?>
                            <div class="anniversary-card past">
                                <div class="cake-icon">🎉</div>
                                <h3><?php echo htmlspecialchars($emp['name']); ?></h3>
                                <p class="join-date"><?php echo date('d M Y', strtotime($emp['joining_date'])); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-anniversaries">No recent anniversaries this month.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>


    <?php include 'includes/footer.php' ?>
    <script src="js/anniversary.js"></script>
</body>

</html>