<?php 

function getAnniversaries($conn) {
    $employees = [];
    $sql = "SELECT id, name, joining_date FROM employees";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $employees[] = $row;
        }
    }
    return $employees;
}

function categorizeAnniversaries($employees) {
    $today = date('m-d');
    $tomorrow = date('m-d', strtotime('+1 day'));
    $thisMonth = date('m');
    $todayDay = (int)date('d');

    $todays = [];
    $upcoming = [];
    $recent = [];

    foreach ($employees as $emp) {
        $joiningMonthDay = date('m-d', strtotime($emp['joining_date']));
        $joiningMonth = date('m', strtotime($emp['joining_date']));
        $joiningDay = (int)date('d', strtotime($emp['joining_date']));

        if ($joiningMonth == $thisMonth) {
            if ($joiningMonthDay == $today) {
                $todays[] = $emp;
            } elseif ($joiningMonthDay == $tomorrow) {
                $emp['when'] = 'Tomorrow';
                $upcoming[] = $emp;
            } elseif ($joiningDay > $todayDay) {
                $emp['when'] = 'In ' . ($joiningDay - $todayDay) . ' days';
                $upcoming[] = $emp;
            } elseif ($joiningDay < $todayDay) {
                $emp['when'] = ($todayDay - $joiningDay) . ' days ago';
                $recent[] = $emp;
            }
        }
    }
    return [$todays, $upcoming, $recent];
}

?>