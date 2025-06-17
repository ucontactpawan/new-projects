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


//time cal culation logic 

// 9:30 in, 5:30 out → "On Time" + "Early Out"
// 9:46 in, 6:30 out → "Half Day" + "On Time" (no late minutes counted)
// 9:30 in, 6:32 out → "Full Time" only
// 9:32 in, 6:40 out → "Late In" + "On Time" (counts towards 20-min allowance)
// 9:53 in, 7:36 out → "Half Day" + "On Time" (no late minutes counted)

// The updated logic now works as follows:

// When employee comes between 9:31-9:45:

// Calculate current late minutes
// If current late minutes ≥ 11 AND total would exceed 20 → "Half Day" (like June 10)
// Otherwise → "Late In"
// Status assignment:

// June 6 (9:32 AM): 2 min late → "Late In" + "On Time"
// June 9 (9:32 AM): 2 min late → "Late In" + "On Time"
// June 10 (9:41 AM): 11 min late → "Half Day" + "On Time" (maximum late minutes)
// June 16 (9:32 AM): 2 min late → "Late In" + "On Time"
// June 17 (9:35 AM): 5 min late → "Late In" + "On Time"
// Late minutes tracking:

// June 6: +2 minutes
// June 9: +2 minutes
// June 10: +0 minutes (Half Day)
// June 16: +2 minutes
// June 17: +5 minutes Total: 11 minutes (under the 20-minute allowance)

