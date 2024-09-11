<?php
// Include database connection and start session
include 'db_conn.php'; 
session_start();

// Check if user is logged in
if (!isset($_SESSION['accNum'])) {
    header("Location: login.php");
    exit();
}

// Retrieve user information from session
$user_id = $_SESSION['accNum'];

// Determine user type
$sql = "
    SELECT 'admin' AS user_type FROM admin_account WHERE accNum = ?
    UNION
    SELECT 'staff' AS user_type FROM staff_account WHERE accNum = ?
";
$stmt = $link->prepare($sql);

if ($stmt) {
    $stmt->bind_param("ii", $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_type = $result->num_rows > 0 ? $result->fetch_assoc()['user_type'] : null;
    $stmt->close();
} else {
    die("Prepare failed: " . $link->error);
}

// Include the appropriate header file based on user type
if ($user_type === 'admin') {
    include 'header.php';
} elseif ($user_type === 'staff') {
    include 'header_staff.php';
} else {
    echo "Error: User type not recognized.";
    exit();
}

$link->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
    <link rel="stylesheet" type="text/css" href="homestyle.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link rel="icon" type="image/x-icon" href="jagran_logo1.jpg">
    <link href="https://fonts.googleapis.com/css?family=Quicksand&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body>
    <div id="homepage-section" class="homepage-container">
        <div class="left">
            <img src="Images/cnn.png" alt="Heat Index Chart"> 
        </div>
        <div class="right">
            <h2>Welcome to Our Heat Index Monitoring System</h2>
            <p>Stay informed and stay safe with our cutting-edge Heat Index Monitoring System. Our platform provides real-time data on temperature, humidity, and heat index levels, helping you make informed decisions to protect students' health and well-being.</p>
        </div>
    </div>
    <script>
    document.getElementById('home-nav').addEventListener('click', function() {
        document.getElementById('homepage-section').style.display = 'block';
        document.getElementById('monitoring-section').style.display = 'none';
        document.getElementById('compare-section').style.display = 'none';
    });
    </script>
</body>
</html>
