<?php
// Start the session if not already started and establish a database connection
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('db_conn.php');

// Initialize variables
$firstName = "No user logged in";

// Check if the session username is set
if (isset($_SESSION['accNum']) && !empty($_SESSION['accNum'])) {
    $username = $_SESSION['username'];
    $accNum = $_SESSION['accNum']; // Ensure this variable is set

    // fetch user information
    $query = "SELECT firstName FROM staff_account WHERE accNum = ?";
    if ($stmt = $link->prepare($query)) { // Use $link instead of $conn
        $stmt->bind_param("i", $accNum);
        $stmt->execute();
        $result = $stmt->get_result();

        // Fetch user information if available
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $firstName = htmlspecialchars($user['firstName']);
        } else {
            $firstName = "Unknown User";
        }

        // Close 
        $stmt->close();
    } else {
        $firstName = "Error preparing the statement";
    }
} else {
    $firstName = "No user logged in";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weather Dashboard</title>
    <link rel="stylesheet" type="text/css" href="homestyle.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link rel="icon" type="x-icon" href="jagran_logo1.jpg">
    <link href="https://fonts.googleapis.com/css?family=Quicksand&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body>
<header>
<nav class="navbar sticky-top navbar-expand-sm navbar-dark bg-maroon">
    <a class="navbar-brand" href="home.php">
        <img src="Images/PUP.png" width="30" height="30" class="d-inline-block align-top" alt="PUP Logo"> PUP Heat Index Monitoring
    </a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ml-auto">
            <li class="nav-item active">
                <a class="nav-link" href="home.php" id="home-nav">Home</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="monitoring.php" id="monitoring-nav">Monitoring</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="reports.php" id="reports-nav">Reports</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="alerts_notifications.php" id="alerts-notifications-nav">Alerts & Notifications</a>
            </li>
        </ul>
        <ul class="navbar-nav">
            <li class="nav-item dropdown profile">
                <a href="#" class="nav-link dropdown-toggle" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <img src="Images/FaMO.png" alt="user-image" class="img-circle img-inline" width="30" height="30">
                    <span class="ml-2"><?php echo $firstName; ?></span>
                </a>
                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                    <a class="dropdown-item" href="profile.php?id=<?php echo (int)$staff_account['id']; ?>">
                        <i class="glyphicon glyphicon-user"></i> Profile
                    </a>
                    <a class="dropdown-item" href="edit_account.php">
                        <i class="glyphicon glyphicon-cog"></i> Settings
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="login.php">
                        <i class="glyphicon glyphicon-off"></i> Logout
                    </a>
                </div>
            </li>
        </ul>
    </div>
</nav>
</header>
</body>
</html>
