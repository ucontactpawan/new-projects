<?php 

function getUpcomingBirthdays($daysAhead = 5){
    global $conn;

    $query = "SELECT 
            e.name as employee_name,
            ed.dob,
            DATE_FORMAT(ed.dob, '%d-%m') as birth_date,
            DATEDIFF(
            DATE(CONCAT(YEAR(CURDATE()), DATE_FORMAT (ed.dob, '-%m-%d'))),
            CURDATE())
            as  days_until_birthday
            FROM employee_details ed
            JOIN employees e ON e.id = ed.employee_id
            HAVING days_until_birthday BETWEEN 0 AND ? 
            ORDER BY days_until_birthday";

            $stmt = $conn->prepare($query);
            if(!$stmt){
                die("Query preparation failed: " . $conn->error);
            }
            $stmt->bind_param('i', $daysAhead);
            $stmt->execute();
            return $stmt->get_result();
}

function formatBirthday($dob, $days_until){
    if($days_until == 0){
         return "Today";
  } else if ($days_until == 1){
     return "Tomorrow";
  } else {
     return "In " .  $days_until . " days";
  }
}
