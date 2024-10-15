<?php

$host = 'localhost'; 
$username = 'root'; 
$password = '12345678'; 
$database = 'db_heatindex'; 

// Create connection
$link = mysqli_connect($host, $username, $password, $database);

// Check connection
if (!$link) {
    die("Connection failed: " . mysqli_connect_error());
}
