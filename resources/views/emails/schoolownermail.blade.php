<?php

use App\Http\Controllers\api;

$root = $_SERVER['DOCUMENT_ROOT'];
$file = file_get_contents($root . '/driving_school/mailers/schoolownermail.html', 'r');

$file = str_replace('#customer_name', $data['customer_name'], $file);
$file = str_replace('#purchase_date', $data['purchase_date'], $file);
$file = str_replace('#package_name', $data['package_name'], $file);
$file = str_replace('#package_amount', $data['package_amount'], $file);
$file = str_replace('#School_name', $data['School_name'], $file);
$file = str_replace('#status', $data['status'], $file);

echo $file;
// exit();
?>
