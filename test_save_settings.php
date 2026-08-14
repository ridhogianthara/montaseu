<?php
require 'config/database.php';

$settings = getSettings();
$settings['office_lat'] = "'-6.230588";
$settings['office_lng'] = "'106.808018";

$res = saveToGoogle('save_settings', $settings);
echo "Save result: " . ($res ? 'SUCCESS' : 'FAIL') . "\n";
echo "Fetching back...\n";
print_r(getSettings());
