<?php
// Include database connection and start session
include 'db_conn.php'; 
session_start();

// Check if user is not logged in and redirect to login page
if (!isset($_SESSION['accNum']) && !isset($_SESSION['adminAccNum']) && !isset($_SESSION['guestAccNum'])) {
    header("Location: login.php");
    exit();
}

// Initialize userType and user_id variables
if (isset($_SESSION['adminAccNum'])) {
    $userType = 'admin'; 
    $user_id = $_SESSION['adminAccNum'];
} elseif (isset($_SESSION['accNum'])) {
    $userType = 'staff';
    $user_id = $_SESSION['accNum']; 
} elseif (isset($_SESSION['guestAccNum'])) {
    $userType = 'guest';
    $user_id = $_SESSION['guestAccNum']; 
} else {
    echo "Error: User type not recognized.";
    exit();
}

// Include the appropriate header file based on user type
if ($userType === 'admin') {
    include 'header.php';
} elseif ($userType === 'staff') {
    include 'header_staff.php';
} elseif ($userType === 'guest') {
    include 'header_guest.php';
}

// Retrieve user information based on user type
$sql = '';
if ($userType == 'admin') {
    $sql = "SELECT accNum, username, email FROM admin_account WHERE accNum = ?";
} elseif ($userType == 'staff') {
    $sql = "SELECT accNum, username, email FROM staff_account WHERE accNum = ?";
} elseif ($userType == 'guest') {
    $sql = "SELECT accNum, username, email FROM guest_account WHERE accNum = ?";
}

$stmt = $link->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Process user information
    $user_info = $result->fetch_assoc();
    $stmt->close();
} else {
    die("Prepare failed: " . $link->error);
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
    <link rel="icon" type="image/x-icon" href="Images/PUP.png">
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
    <?php include 'footer.php'; ?>
</body>
</html>
