<?php 

function getBirthdays() {
    global $conn;
    
    $query = "SELECT 
        e.name as employee_name,
        ed.dob,
        DAY(ed.dob) as birth_day,
        MONTH(ed.dob) as birth_month,
        YEAR(ed.dob) as birth_year,
        CASE 
            WHEN (MONTH(ed.dob) = MONTH(CURDATE()) AND DAY(ed.dob) >= DAY(CURDATE()))
            THEN DATEDIFF(
                STR_TO_DATE(
                    CONCAT(YEAR(CURDATE()), '-', MONTH(ed.dob), '-', DAY(ed.dob)),
                    '%Y-%m-%d'
                ),
                CURDATE()
            )
            ELSE DATEDIFF(
                STR_TO_DATE(
                    CONCAT(YEAR(CURDATE()), '-', MONTH(ed.dob), '-', DAY(ed.dob)),
                    '%Y-%m-%d'
                ),
                CURDATE()
            )
        END as days_until_birthday,
        CASE 
            WHEN MONTH(ed.dob) = MONTH(CURDATE()) AND DAY(ed.dob) < DAY(CURDATE()) THEN 'recent'
            WHEN MONTH(ed.dob) = MONTH(CURDATE()) AND DAY(ed.dob) >= DAY(CURDATE()) THEN 'upcoming'
            ELSE 'other'
        END as birthday_status
    FROM employee_details ed
    JOIN employees e ON e.id = ed.employee_id
    WHERE MONTH(ed.dob) = MONTH(CURDATE())
    ORDER BY DAY(ed.dob)";
    
    $result = $conn->query($query);
    
    if (!$result) {
        error_log("SQL Error in getBirthdays: " . $conn->error);
        return false;
    }
    
    return $result;
}

function formatBirthdayDate($days_until) {
    if ($days_until == 0) {
        return "Today";
    } else if ($days_until == 1) {
        return "Tomorrow";
    } else if ($days_until == -1) {
        return "Yesterday";
    } else if ($days_until < 0) {
        return abs($days_until) . " days ago";
    } else {
        return "In " . $days_until . " days";
    }
}

function formatFullDOB($dob) {
    return date('d M Y', strtotime($dob));
}
