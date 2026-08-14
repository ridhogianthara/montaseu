<?php
$apiKey = '9a4e29e3dacea965c75e35b4dd2b6d3c';
$base64Data = 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.imgbb.com/1/upload?key=' . $apiKey);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
$data = array('image' => $base64Data);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$result = curl_exec($ch);
echo $result;
?>
