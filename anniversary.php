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
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Base styles -->
    <link rel="stylesheet" href="css/style.css">
    <!-- Component styles -->
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/anniversary.css">
</head>
<body>
    <?php include('includes/navbar.php'); ?>
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="anniversary-container" style="padding: 40px 0 60px 0; max-width: 800px; margin: 0 auto;">
            <h1>EMPLOYEE CELEBRATIONS</h1>
            <div class="anniversary-section">
                <h2>Today's Anniversaries</h2>
                <div class="anniversary-cards">
                    <?php if (count($todaysAnniversaries)): ?>
                        <?php foreach ($todaysAnniversaries as $emp): ?>
                            <div class="anniversary-card today">
                                <div class="cake-icon">🎉</div>
                                <div style="font-size:1.3em;font-weight:600;"><?php echo htmlspecialchars($emp['name']); ?></div>
                                <div><?php echo date('d M Y', strtotime($emp['joining_date'])); ?></div>
                                <div class="when today">Today</div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="color:#aaa;">No anniversaries today.</div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="anniversary-section">
                <h2>Upcoming Anniversaries</h2>
                <div class="anniversary-cards">
                    <?php if (count($upcomingAnniversaries)): ?>
                        <?php foreach ($upcomingAnniversaries as $emp): ?>
                            <div class="anniversary-card">
                                <div class="cake-icon">🎉</div>
                                <div style="font-size:1.1em;font-weight:600;"><?php echo htmlspecialchars($emp['name']); ?></div>
                                <div><?php echo date('d M Y', strtotime($emp['joining_date'])); ?></div>
                                <div class="when"><?php echo isset($emp['when']) ? $emp['when'] : ''; ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="color:#aaa;">No upcoming anniversaries this month.</div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="anniversary-section">
                <h2>Recent Anniversaries</h2>
                <div class="anniversary-cards">
                    <?php if (count($recentAnniversaries)): ?>
                        <?php foreach ($recentAnniversaries as $emp): ?>
                            <div class="anniversary-card recent">
                                <div class="cake-icon">🎉</div>
                                <div style="font-size:1.1em;font-weight:600;"><?php echo htmlspecialchars($emp['name']); ?></div>
                                <div><?php echo date('d M Y', strtotime($emp['joining_date'])); ?></div>
                                <div class="when"><?php echo isset($emp['when']) ? $emp['when'] : ''; ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="color:#aaa;">No recent anniversaries this month.</div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>


    <?php include 'includes/footer.php' ?>
    <script src="js/anniversary.js"></script>
</body>
</html>