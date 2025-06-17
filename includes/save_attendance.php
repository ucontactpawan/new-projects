<?php
session_start();


function convertTimeZone($time, $fromZone = 'Asia/Kolkata', $toZone = 'UTC', $format = 'Y-m-d H:i:s') {
    if (empty($time)) return null;
    
    try {
        $datetime = new DateTime($time, new DateTimeZone($fromZone));
        $datetime->setTimezone(new DateTimeZone($toZone));
        return $datetime->format($format);
    } catch (Exception $e) {
        error_log("Time conversion error: " . $e->getMessage());
        return null;
    }
}

// Set default timezone if not set
if (!ini_get('date.timezone')) {
    date_default_timezone_set('Asia/Kolkata'); // Change this to your local timezone
}

// Debug logging for timezone
error_log("Current timezone: " . date_default_timezone_get());
error_log("Current local time: " . date('Y-m-d H:i:s'));

// Clear any previous output
if (ob_get_level()) ob_end_clean();

// Set JSON header
header('Content-Type: application/json');

// Include required files
include '../includes/db.php';
include '../includes/auth.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    error_log("Session user_id not set!");
    echo json_encode([
        'status' => 'error',
        'message' => 'User not logged in'
    ]);
    exit;
}

$creator_id = $_SESSION['user_id'];
error_log("Creator ID from session: " . $creator_id);

// Get JSON input
$raw_input = file_get_contents('php://input');
$input = json_decode($raw_input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid JSON format: ' . json_last_error_msg()
    ]);
    exit;
}

// Validate input
if (!isset($input['date'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Date is required'
    ]);
    exit;
}

if (!isset($input['attendance']) || !is_array($input['attendance'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Attendance data is required'
    ]);
    exit;
}

try {    // Start transaction
    mysqli_begin_transaction($conn);
    $success_count = 0;
    
    foreach ($input['attendance'] as $entry) {
        if (!isset($entry['employee_id'])) {
            throw new Exception("Employee ID is required");
        }

        $employee_id = (int)$entry['employee_id'];
        
        // Convert local times to UTC using the convertTimeZone function
        $in_time = isset($entry['in_time']) ? convertTimeZone($entry['in_time'], 'Asia/Kolkata', 'UTC') : null;
        $out_time = isset($entry['out_time']) ? convertTimeZone($entry['out_time'], 'Asia/Kolkata', 'UTC') : null;
        
        // Escape the UTC times for database
        $in_time = $in_time ? mysqli_real_escape_string($conn, $in_time) : null;
        $out_time = $out_time ? mysqli_real_escape_string($conn, $out_time) : null;
        $comments = isset($entry['comments']) ? mysqli_real_escape_string($conn, $entry['comments']) : '';

        // Debug logging for time conversion
        error_log("Employee ID: $employee_id, Local IN time: " . $entry['in_time'] . ", Converted IN time: $in_time");
        error_log("Employee ID: $employee_id, Local OUT time: " . $entry['out_time'] . ", Converted OUT time: $out_time");

        // Check if record exists
        $check_sql = "SELECT id FROM attendance WHERE employee_id = ? AND DATE(in_time) = DATE(?)";
        $stmt = mysqli_prepare($conn, $check_sql);
        if (!$stmt) {
            throw new Exception("Failed to prepare check statement: " . mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($stmt, "is", $employee_id, $in_time);
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Failed to execute check: " . mysqli_stmt_error($stmt));
        }
        $result = mysqli_stmt_get_result($stmt);
        mysqli_stmt_close($stmt);

        if ($result && mysqli_num_rows($result) > 0) {
            // Update existing record
            $row = mysqli_fetch_assoc($result);
            $attendance_id = $row['id'];
            
            $update_sql = "UPDATE attendance SET 
                          in_time = ?,
                          out_time = ?,
                          comments = ?,
                          status = '1'
                          WHERE id = ?";
            
            $update_stmt = mysqli_prepare($conn, $update_sql);
            if (!$update_stmt) {
                throw new Exception("Failed to prepare update statement: " . mysqli_error($conn));
            }
            mysqli_stmt_bind_param($update_stmt, "sssi", $in_time, $out_time, $comments, $attendance_id);
            if (!mysqli_stmt_execute($update_stmt)) {
                throw new Exception("Failed to update attendance: " . mysqli_stmt_error($update_stmt));
            }
            mysqli_stmt_close($update_stmt);
        } else {
            // Insert new attendance record
            $insert_sql = "INSERT INTO attendance (employee_id, in_time, out_time, comments, status) VALUES (?, ?, ?, ?, '1')";
            $insert_stmt = mysqli_prepare($conn, $insert_sql);
            if (!$insert_stmt) {
                throw new Exception("Failed to prepare insert statement: " . mysqli_error($conn));
            }
            mysqli_stmt_bind_param($insert_stmt, "isss", $employee_id, $in_time, $out_time, $comments);
            if (!mysqli_stmt_execute($insert_stmt)) {
                throw new Exception("Failed to insert attendance: " . mysqli_stmt_error($insert_stmt));
            }
            $attendance_id = mysqli_insert_id($conn);
            mysqli_stmt_close($insert_stmt);
        }

        // Delete existing history records for this attendance
        $delete_history_sql = "DELETE FROM attendance_history WHERE attendance_id = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_history_sql);
        if (!$delete_stmt) {
            throw new Exception("Failed to prepare delete statement: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($delete_stmt, "i", $attendance_id);
        mysqli_stmt_execute($delete_stmt);
        mysqli_stmt_close($delete_stmt);     
        // Insert IN record into attendance_history with creator_id
        $history_in_sql = "INSERT INTO attendance_history 
                          (attendance_id, employee_id, action, date_time, comments, status, creator_id) 
                          VALUES (?, ?, 'IN', ?, ?, '1', ?)";
        $history_in_stmt = mysqli_prepare($conn, $history_in_sql);
        if (!$history_in_stmt) {
            throw new Exception("Failed to prepare history IN statement: " . mysqli_error($conn));
        }
        // Use UTC time for history records
        mysqli_stmt_bind_param($history_in_stmt, "iissi", $attendance_id, $employee_id, $in_time, $comments, $creator_id);
        if (!mysqli_stmt_execute($history_in_stmt)) {
            throw new Exception("Failed to insert IN history: " . mysqli_stmt_error($history_in_stmt));
        }
        mysqli_stmt_close($history_in_stmt);

        // Insert OUT record into attendance_history with creator_id if out_time exists
        if ($out_time) {
            $history_out_sql = "INSERT INTO attendance_history 
                               (attendance_id, employee_id, action, date_time, comments, status, creator_id) 
                               VALUES (?, ?, 'OUT', ?, ?, '1', ?)";
            $history_out_stmt = mysqli_prepare($conn, $history_out_sql);
            if (!$history_out_stmt) {
                throw new Exception("Failed to prepare history OUT statement: " . mysqli_error($conn));
            }
            // Use UTC time for history records
            mysqli_stmt_bind_param($history_out_stmt, "iissi", $attendance_id, $employee_id, $out_time, $comments, $creator_id);
            if (!mysqli_stmt_execute($history_out_stmt)) {
                throw new Exception("Failed to insert OUT history: " . mysqli_stmt_error($history_out_stmt));
            }
            mysqli_stmt_close($history_out_stmt);
        }

        $success_count++;
    }

    // Commit transaction
    mysqli_commit($conn);
    echo json_encode([
        'status' => 'success',
        'message' => "Successfully saved attendance for $success_count employee(s)"
    ]);

} catch (Exception $e) {
    mysqli_rollback($conn);
    error_log("Error in save_attendance.php: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to save attendance: ' . $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        mysqli_close($conn);
    }
}
?>


