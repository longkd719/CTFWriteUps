<?php
session_start();

$FLAG = "KMACTF{fake-flag}";
$DB_HOST = "mysql"; // change me
$DB_USER = "root"; // change me
$DB_PASS = "REDACTED"; // change me
$DB_NAME = "ninjashop";

$connection = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($connection->connect_error) {
    die("<strong>MySQL connection error</strong>");
}

function waf($input) {
    // Prevent sqli -.-
    $blacklist = join("|", ["sleep", "benchmark", "order", "limit", "exp", "extract", "xml", "floor", "rand", "count", "or" ,"and", ">", "<", "\|", "&","\(", "\)", "\\\\" ,"1337", "0x539"]);
    if (preg_match("/${blacklist}/si", $input)) die("<strong>Stop! No cheat =))) </strong>");
    return TRUE;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.cdnfonts.com/css/public-pixel" rel="stylesheet">
</head>
<style>
    * {
        font-family: 'Public Pixel', sans-serif;
    }
</style>