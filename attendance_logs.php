<?php
session_start();
include 'includes/db.php';
include 'includes/auth.php';

// Function to convert UTC to local time
function getFormattedTime($utcTime, $format = 'h:i A')
{
    if (!$utcTime) return '';
    $dt = new DateTime($utcTime, new DateTimeZone('UTC'));
    $dt->setTimezone(new DateTimeZone('Asia/Kolkata'));
    return $dt->format($format);
}

// Function to format duration if (in_array('Early Out', $attendance_info['statuses'])) {n hours and minutes
function formatDuration($min)
{
    if ($min <= 0) return '';
    $h = floor($min / 60);
    $m = $min % 60;
    return "{$h}Hr {$m}Min";
}

// Function to determine attendance status and calculate times
function calculateAttendanceStatus($in_time, $out_time, $total_late_minutes, $early_outs_count)
{
    if (!$in_time) return ['late_mins' => 0, 'early_mins' => 0, 'extra_hour' => 0, 'statuses' => ['Absent'], 'remaining_allowance' => 20 - $total_late_minutes];

    $in_dt = new DateTime($in_time, new DateTimeZone('UTC'));
    $in_dt->setTimezone(new DateTimeZone('Asia/Kolkata'));

    $out_dt = $out_time ? new DateTime($out_time, new DateTimeZone('UTC')) : null;
    if ($out_dt) $out_dt->setTimezone(new DateTimeZone('Asia/Kolkata'));

    $standard_in = new DateTime($in_dt->format('Y-m-d') . ' 09:30:00', new DateTimeZone('Asia/Kolkata'));
    $late_limit = new DateTime($in_dt->format('Y-m-d') . ' 09:45:00', new DateTimeZone('Asia/Kolkata'));
    $early_out_limit = new DateTime($in_dt->format('Y-m-d') . ' 17:00:00', new DateTimeZone('Asia/Kolkata'));
    $standard_out = new DateTime($in_dt->format('Y-m-d') . ' 18:30:00', new DateTimeZone('Asia/Kolkata'));
    $extra_time = new DateTime($in_dt->format('Y-m-d') . ' 19:30:00', new DateTimeZone('Asia/Kolkata'));    // Calculate late minutes - only for arrivals between 9:31-9:45
    $late_mins = 0;
    if ($in_dt > $standard_in && $in_dt <= $late_limit) {
        $late_mins = $in_dt->diff($standard_in)->i + ($in_dt->diff($standard_in)->h * 60);
    }

    // Calculate early out minutes
    $early_mins = 0;
    if ($out_dt && $out_dt < $standard_out) {
        $early_mins = $out_dt->diff($standard_out)->i + ($out_dt->diff($standard_out)->h * 60);
    }

    // Calculate extra time (hours)
    $extra_hour = 0;
    if ($out_dt && $out_dt >= $extra_time && $out_dt > $standard_out) {
        $work_duration = $out_dt->diff($standard_out);
        $minutes_after_standard = ($work_duration->h * 60) + $work_duration->i;
        if ($minutes_after_standard >= 60) {
            $extra_hour = 1;
        }
    }

    // Initialize status array and calculate remaining late allowance
    $statuses = [];
    $remaining_allowance = 20 - $total_late_minutes;

    // Determine base status based on arrival time
    if ($in_dt <= $standard_in) {
        // Came at or before 9:30
        if ($out_dt && $out_dt >= $standard_out) {
            $statuses[] = 'Full Time';
        } else {
            $statuses[] = 'On Time';
        }
    } elseif ($in_dt <= $late_limit) {
        // Came between 9:31-9:45
        if ($remaining_allowance >= $late_mins) {
            $statuses[] = 'Late In';
            $remaining_allowance -= $late_mins;
        } else {
            $statuses[] = 'Half Day';
            $late_mins = 0; // Don't count late minutes for Half Day
        }
    } else {
        // Came after 9:45
        $statuses[] = 'Half Day';
        $late_mins = 0; // Don't count late minutes for Half Day
    }

    // Add out time status if needed
    if ($out_dt) {
        if ($out_dt >= $standard_out) {
            // Left at or after 6:30 PM
            if (!in_array('Full Time', $statuses)) {
                if (count($statuses) === 1 && $statuses[0] === 'Late In') {
                    $statuses[] = 'On Time';
                } elseif ($statuses[0] === 'Half Day') {
                    $statuses[] = 'On Time';
                }
            }
        } else if ($out_dt >= $early_out_limit) {
            // Left between 5:00 PM and 6:30 PM
            if ($early_outs_count < 2) {
                $statuses[] = 'Early Out';
            } else {
                if (!in_array('Half Day', $statuses)) {
                    $statuses[] = 'Half Day';
                }
            }
        } else {
            // Left before 5:00 PM
            if (!in_array('Half Day', $statuses)) {
                $statuses[] = 'Half Day';
            }
        }
    }

    return [
        'late_mins' => $late_mins,
        'early_mins' => $early_mins,
        'extra_hour' => $extra_hour,
        'statuses' => $statuses,
        'remaining_allowance' => max(0, $remaining_allowance)
    ];
}

// Redirect if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Function to generate all days in a month
function generateDateRange($date_from, $date_to)
{
    $dates = [];
    $start = new DateTime($date_from);
    $start->setTime(0, 0, 0); // Reset time part
    $end = new DateTime($date_to);
    $end->setTime(23, 59, 59); // Set to end of day

    $current = clone $start;
    while ($current <= $end) {
        $dates[] = $current->format('Y-m-d');
        $current->modify('+1 day');
    }

    return $dates;
}

// Get filter values
$employee_id = isset($_GET['employee_id']) && !empty($_GET['employee_id']) ? $_GET['employee_id'] : $_SESSION['user_id'];

// Set the date range to include full month
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');
$month = isset($_GET['month']) ? $_GET['month'] : date('m');

// Calculate first and last day of the month
$date_from = date('Y-m-01', strtotime("$year-$month-01")); // First day of month
$date_to = date('Y-m-t', strtotime("$year-$month-01"));   // Last day of month

// Generate all dates for the selected period
$all_dates = generateDateRange($date_from, $date_to);

// Create a temporary table for dates
$create_dates_table = "CREATE TEMPORARY TABLE date_range (date_day DATE)";
mysqli_query($conn, $create_dates_table);

$insert_dates = mysqli_prepare($conn, "INSERT INTO date_range (date_day) VALUES (?)");
foreach ($all_dates as $date) {
    mysqli_stmt_bind_param($insert_dates, "s", $date);
    mysqli_stmt_execute($insert_dates);
}

// Query to get attendance logs with employee names
if (!canViewOthersAttendance()) {
    // If user can't view others' attendance, force their own user_id
    $employee_id = $_SESSION['user_id'];
}

$query = "SELECT 
            dr.date_day as attendance_date,
            e.id as employee_id,
            a.id,
            a.in_time,
            a.out_time,
            a.comments,
            COALESCE(TIMESTAMPDIFF(MINUTE, a.in_time, a.out_time), 0) as working_minutes
          FROM date_range dr
          CROSS JOIN (
              SELECT id 
              FROM employees 
              WHERE id = ?
          ) e
          LEFT JOIN attendance a ON DATE(a.in_time) = dr.date_day AND a.employee_id = e.id
          WHERE 1=1";

$params = array($employee_id);
$types = "i";

if ($date_from) {
    $query .= " AND dr.date_day >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if ($date_to) {
    $query .= " AND dr.date_day <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$query .= " ORDER BY dr.date_day ASC";

// Add error checking after prepare statement
$stmt = mysqli_prepare($conn, $query);
if ($stmt === false) {
    die('Prepare failed: ' . mysqli_error($conn));
}

if (!empty($params)) {
    if (!mysqli_stmt_bind_param($stmt, $types, ...$params)) {
        die('Binding parameters failed: ' . mysqli_stmt_error($stmt));
    }
}

if (!mysqli_stmt_execute($stmt)) {
    die('Execute failed: ' . mysqli_stmt_error($stmt));
}

$result = mysqli_stmt_get_result($stmt);
if ($result === false) {
    die('Getting result set failed: ' . mysqli_stmt_error($stmt));
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Logs</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/attendance_logs.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment-timezone/0.5.33/moment-timezone-with-data.min.js"></script>
</head>

<body>
    <?php include 'includes/navbar.php'; ?>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="logs-container">
            <div class="filter-header">
                <div class="filter-group">
                    <label>Select Month</label>
                    <select class="form-select" id="monthFilter">
                        <?php
                        $months = [
                            '01' => 'January',
                            '02' => 'February',
                            '03' => 'March',
                            '04' => 'April',
                            '05' => 'May',
                            '06' => 'June',
                            '07' => 'July',
                            '08' => 'August',
                            '09' => 'September',
                            '10' => 'October',
                            '11' => 'November',
                            '12' => 'December'
                        ];
                        $selected_month = isset($_GET['month']) ? $_GET['month'] : date('m');
                        foreach ($months as $value => $label) {
                            $selected = $value == $selected_month ? 'selected' : '';
                            echo "<option value='$value' $selected>$label</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Select Year</label>
                    <select class="form-select" id="yearFilter">
                        <?php
                        $currentYear = date('Y');
                        for ($i = $currentYear - 2; $i <= $currentYear + 2; $i++) {
                            $selected = $i == $currentYear ? 'selected' : '';
                            echo "<option value='$i' $selected>$i</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Select Employee</label>
                    <select class="form-select" id="employeeFilter">
                        <?php
                        if (canViewOthersAttendance()) {
                            // Show all employees for users with permission
                            $emp_query = "SELECT id, name FROM employees ORDER BY name ASC";
                        } else {
                            // Show only the current user for users without permission
                            $emp_query = "SELECT id, name FROM employees WHERE id = {$_SESSION['user_id']} ORDER BY name ASC";
                        }
                        $emp_result = mysqli_query($conn, $emp_query);
                        while ($emp = mysqli_fetch_assoc($emp_result)) {
                            $selected = $emp['id'] == $employee_id ? 'selected' : '';
                            echo "<option value='{$emp['id']}' $selected>" .
                                htmlspecialchars($emp['name']) . "</option>";
                        }
                        ?>
                    </select>

                </div>
            </div>
            <div class="total-hours">
                Total Working Hours <span id="totalWorkingHours"><?php echo formatDuration($total_working_minutes); ?></span>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>In Time</th>
                            <th>Out Time</th>
                            <th>In | Out | Extra Time</th>
                            <th>Total Hours</th>
                            <th>Status</th>
                            <th>Comments</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $total_working_minutes = 0;
                        $monthly_late_minutes = array(); // Track late minutes per employee
                        $monthly_early_outs = array(); // Track early outs per employee
                        $attendance_data = array();

                        // First pass: collect data by employee
                        while ($row = mysqli_fetch_assoc($result)) {
                            $emp_id = $row['employee_id'];
                            if (!isset($monthly_late_minutes[$emp_id])) {
                                $monthly_late_minutes[$emp_id] = 0;
                                $monthly_early_outs[$emp_id] = 0;
                            }
                            $attendance_data[] = $row;
                        }                        // Initialize tracking variables
                        $monthly_late_minutes = [];
                        $monthly_early_outs = [];
                        $total_working_minutes = 0;

                        // Second pass: display with proper status based on accumulated data
                        foreach ($attendance_data as $row) {
                            $emp_id = $row['employee_id'];
                            $in_time = $row['in_time'] ?? null;
                            $out_time = $row['out_time'] ?? null;
                            $is_weekend = in_array(date('N', strtotime($row['attendance_date'])), [6, 7]); // 6 = Saturday, 7 = Sunday

                            // Initialize tracking for employee if not exists
                            if (!isset($monthly_late_minutes[$emp_id])) {
                                $monthly_late_minutes[$emp_id] = 0;
                            }
                            if (!isset($monthly_early_outs[$emp_id])) {
                                $monthly_early_outs[$emp_id] = 0;
                            }

                            if ($emp_id === null) {
                                // Empty day
                                $time_status = "0 | 0 | 0";
                                $working_minutes = 0;
                                $today = new DateTime();
                                $rowDate = new DateTime($row['attendance_date']);

                                if ($rowDate > $today) {
                                    // Future date
                                    $status_class = '';
                                    $statuses = [''];
                                } elseif ($is_weekend) {
                                    // Weekend - no status
                                    $status_class = '';
                                    $statuses = [''];
                                } else {
                                    // Past weekday with no attendance
                                    $status_class = 'bg-danger';
                                    $statuses = ['Absent'];
                                }
                            } else {
                                // Handle attendance status
                                $attendance_info = [];

                                if (!$in_time) {
                                    if ($is_weekend) {
                                        $attendance_info = [
                                            'late_mins' => 0,
                                            'early_mins' => 0,
                                            'extra_hour' => 0,
                                            'statuses' => [''] // Empty status for weekend
                                        ];
                                    } else {
                                        $attendance_info = [
                                            'late_mins' => 0,
                                            'early_mins' => 0,
                                            'extra_hour' => 0,
                                            'statuses' => ['Absent']
                                        ];
                                    }
                                } else {
                                    $attendance_info = calculateAttendanceStatus(
                                        $in_time,
                                        $out_time,
                                        $monthly_late_minutes[$emp_id] ?? 0,
                                        $monthly_early_outs[$emp_id] ?? 0
                                    );
                                }
                                // Update monthly totals
                                $monthly_late_minutes[$emp_id] += $attendance_info['late_mins'];
                                if (in_array('Early Out', $attendance_info['statuses'])) {
                                    $monthly_early_outs[$emp_id]++;
                                }

                                // Format time status string
                                $time_status =
                                    ($attendance_info['late_mins'] > 0 ? "-{$attendance_info['late_mins']}m" : "0") .
                                    " | " .
                                    ($attendance_info['early_mins'] > 0 ? "{$attendance_info['early_mins']}m" : "0") .
                                    " | " .
                                    ($attendance_info['extra_hour'] > 0 ? "{$attendance_info['extra_hour']}h" : "0");

                                $working_minutes = intval($row['working_minutes']);
                                $total_working_minutes += $working_minutes;
                                $statuses = $attendance_info['statuses'];
                                $status_class = match ($statuses[0]) {
                                    'Present' => 'bg-full-day',
                                    'Late In' => 'bg-late-in',
                                    'Half Day' => 'bg-half-day',
                                    'Full Time' => 'bg-full-day',
                                    'Early Out' => 'bg-early-out',
                                    'On Time' => 'bg-on-time',
                                    'Holiday' => 'bg-holiday',
                                    'Extra Working' => 'bg-extra-working',
                                    'Leave' => 'bg-leave',
                                    'Absent' => 'bg-leave',
                                    default => 'bg-ignore'
                                };
                            }
                        ?> <tr>
                                <td><?php echo date('D, d M Y', strtotime($row['attendance_date'])); ?></td>
                                <?php
                                $today = new DateTime('today');
                                $row_date = new DateTime($row['attendance_date']);
                                $is_weekend = in_array(date('N', strtotime($row['attendance_date'])), [6, 7]);
                                $is_future = $row_date > $today;

                                // For weekends, absent days, and future dates - show blank fields
                                if ($is_weekend || empty($row['in_time']) || $is_future) {
                                    echo "<td></td><td></td><td></td><td></td>";
                                } else {
                                    // For days with attendance
                                ?>
                                    <td><?php echo getFormattedTime($row['in_time']); ?></td>
                                    <td><?php echo getFormattedTime($row['out_time']); ?></td>
                                    <td><?php echo "-{$attendance_info['late_mins']}m | {$attendance_info['early_mins']}m | {$attendance_info['extra_hour']}h"; ?></td>
                                    <td><?php echo formatDuration($working_minutes); ?></td>
                                <?php
                                }
                                ?>
                                <td>
                                    <?php
                                    if ($is_future || $is_weekend) {
                                        // Future date or Weekend - show nothing
                                        echo "";
                                    } elseif (empty($row['in_time'])) {
                                        // Absent
                                        echo "<span class='badge bg-leave me-1'>Leave</span>";
                                    } else {
                                        // Normal attendance status
                                        foreach ($statuses as $status) {
                                            if (empty($status)) continue; // Skip empty status
                                            $badge_class = match ($status) {
                                                'Present' => 'bg-full-day',
                                                'Late In' => 'bg-late-in',
                                                'Half Day' => 'bg-half-day',
                                                'Full Time' => 'bg-full-day',
                                                'Early Out' => 'bg-early-out',
                                                'On Time' => 'bg-on-time',
                                                'Holiday' => 'bg-holiday',
                                                'Leave' => 'bg-leave',
                                                'Extra Working' => 'bg-extra-working',
                                                default => 'bg-ignore'
                                            };
                                            echo "<span class='badge {$badge_class} me-1'>{$status}</span>";
                                        }
                                    }
                                    ?>
                                </td>
                                <td><?php echo empty($row['comments']) ? '' : htmlspecialchars($row['comments']); ?></td>
                                <td>
                                    <?php if (!empty($row['id'])) { ?>
                                        <button class="btn btn-sm btn-primary edit-btn"
                                            title="Edit"
                                            data-id="<?php echo $row['id']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    <?php } ?>
                                </td>
                            </tr>
                        <?php }

                        // Calculate total working hours
                        $total_hours = floor($total_working_minutes / 60);
                        $total_minutes = $total_working_minutes % 60;
                        ?>
                    </tbody>
                </table>
            </div>

            <script>
                // Update total working hours display
                document.addEventListener('DOMContentLoaded', function() {
                    var totalWorkingHours = document.getElementById('totalWorkingHours');
                    if (totalWorkingHours) {
                        totalWorkingHours.textContent = '<?php echo $total_working_minutes > 0 ? $total_hours . "Hr " . $total_minutes . "Min" : "0Hr 0Min"; ?>';
                    }
                });

                document.addEventListener("DOMContentLoaded", function() {
                    // Get URL parameters
                    const urlParams = new URLSearchParams(window.location.search);
                    const selectedMonth = urlParams.get('month');
                    const selectedYear = urlParams.get('year');

                    // Add click handler for filter button
                    $(document).on('click', '.filter-btn', function(e) {
                        e.preventDefault();
                        const month = $("#monthFilter").val();
                        const year = $("#yearFilter").val();
                        const employee = $("#employeeFilter").val();

                        // Build the URL with parameters
                        const params = new URLSearchParams();
                        params.append('month', month);
                        params.append('year', year);
                        if (employee) {
                            params.append('employee_id', employee);
                        }

                        // Redirect with filters
                        window.location.href = `attendance_logs.php?${params.toString()}`;
                    });

                    // Initialize filters with URL values if present
                    if (selectedMonth) {
                        $("#monthFilter").val(selectedMonth);
                    }
                    if (selectedYear) {
                        $("#yearFilter").val(selectedYear);
                    }
                });
            </script>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/attendance_logs.js"></script>
</body>

</html>