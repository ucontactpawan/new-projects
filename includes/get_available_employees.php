<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
session_start();

try {
    require_once __DIR__ . '/db.php';
    require_once __DIR__ . '/auth.php';

    // Ensure clean output
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');

    $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');   
    // Only get employees who don't have attendance marked for the date  
    $query = "SELECT e.id, e.name 
              FROM employees e
              LEFT JOIN attendance a ON e.id = a.employee_id 
              AND DATE(a.in_time) = DATE(?)
              WHERE e.status = '1' AND a.id IS NULL
              ORDER BY e.name";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Failed to prepare query: " . $conn->error);
    }

    $stmt->bind_param("s", $date);

    if (!$stmt->execute()) {
        throw new Exception("Failed to execute query: " . $stmt->error);
    }

    $result = $stmt->get_result();
    if (!$result) {
        throw new Exception("Failed to get result: " . $stmt->error);
    }

    $employees = [];
    while ($row = $result->fetch_assoc()) {
        $employees[] = [
            'id' => $row['id'],
            'name' => $row['name']
        ];
    }

    echo json_encode([
        'success' => true,
        'employees' => $employees
    ]);

} catch (Exception $e) {
    error_log("Error in get_available_employees.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
