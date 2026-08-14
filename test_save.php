<?php
require 'config/database.php';

$attendances = getAttendances();
$attendances[] = [
    'id' => 1,
    'user_id' => 2,
    'date' => date('Y-m-d'),
    'clock_in_time' => date('Y-m-d H:i:s'),
    'clock_in_photo' => 'https://placehold.co/400x300',
    'clock_in_lat' => -6.230588,
    'clock_in_lng' => 106.808018,
    'clock_in_address' => 'Test Address',
    'clock_in_status' => 'On Time',
    'clock_in_notes' => 'Test',
    'clock_out_time' => null,
    'clock_out_photo' => null,
    'clock_out_lat' => null,
    'clock_out_lng' => null,
    'clock_out_address' => null,
    'clock_out_notes' => null,
    'work_duration' => null,
    'location_type' => 'Office',
    'clock_in_location_type' => 'Office',
    'clock_out_location_type' => null,
    'created_at' => date('Y-m-d H:i:s')
];

$res = saveToGoogle('save_attendances', $attendances);
echo "Save result: " . ($res ? 'SUCCESS' : 'FAIL') . "\n";
echo "Fetching back...\n";
print_r(getAttendances());
