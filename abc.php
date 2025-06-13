<?php

date_default_timezone_set('UTC'); // Set timezone to Asia/Kolkata

// echo "Current timezone: " .date_default_timezone_get();
// echo "<br>";

// echo "Current date and time : " . date('Y-m-d H:i:s');

function convertDateOtherZone($date, $fromZone = 'Asia/Kolkata', $toZone = 'UTC', $formate="Y-m-d H:i:s")
{
    $kolkataTime = new DateTime($date, new DateTimeZone($fromZone));

    // Convert to UTC
    $kolkataTime->setTimezone(new DateTimeZone($toZone));

    return $kolkataTime->format($formate);
}

//$date = convertDateOtherZone("2025-06-13 09:30:00", 'Asia/Kolkata', 'UTC', 'Y-m-d H:i:s');
$date = convertDateOtherZone("2025-06-13 04:00:00", 'Asia/Kolkata', 'UTC', 'Y-m-d H:i:s');
 

// Output the UTC time
echo  $date;

