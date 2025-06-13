<?php 

include '../includes/db.php';

if($_SERVER['REQUEST_METHOD']=== 'GET'){
    $employeeId = $_GET['employee_id'] ?? null;
    $date = $_GET['date'] ?? null;

    try{          $query = "SELECT 
            a.id,
            a.employee_id,
            e.name as employee_name,
            DATE_FORMAT(a.in_time, '%a, %d %b %Y') as date,
            DATE(a.in_time) as date_raw,
            TIME_FORMAT(a.in_time, '%h:%i %p') as in_time,
            TIME_FORMAT(a.out_time, '%h:%i %p') as out_time,
            TIMEDIFF(a.out_time, a.in_time) as total_hours,
            CASE 
                WHEN TIME(a.in_time) > '09:30:00' THEN TIME_TO_SEC(TIMEDIFF(TIME(a.in_time), '09:30:00'))/60
                ELSE 0
            END as late_time,
            CASE 
                WHEN a.in_time IS NOT NULL AND a.out_time IS NOT NULL THEN 'Present'
                WHEN a.in_time IS NOT NULL THEN 'Check In'
                ELSE 'Not Set'
            END as status,
            a.comments,
            a.status as record_status
        FROM attendance a
        JOIN employees e ON a.employee_id = e.id 
        WHERE a.status = '1'";

        $params = [];
        $types = "";
        if($employeeId){          
              $query .= " AND a.employee_id = ?";
            $params[] = $employeeId;
            $types .= "i";
        }        if($date){
            $date = date('Y-m-d', strtotime($date)); // Convert any date format to YYYY-MM-DD
            $query .= " AND DATE(a.in_time) = ?";
            $params[] = $date;
            $types .= "s";
        }

        $query .= " ORDER BY DATE(a.in_time) DESC, TIME(a.in_time) ASC, e.name ASC";

        $stmt = $conn->prepare($query);
        if(!empty($params)){
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $history = [];
        while($row = $result->fetch_assoc()){
            $history[] = $row;
        }

        echo json_encode([
            'success' => true,
            'data' => $history
        ]);
    }catch(Exception $e){
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}