<?php

$root = $_SERVER['DOCUMENT_ROOT'];
$file = file_get_contents($root . '/driving_school/mailers/forgotPassword.html', 'r');

$file = str_replace('#otp', $data['otp'], $file);
$file = str_replace('#name', $data['name'], $file);

echo $file;
?>
