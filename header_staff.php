<?php
// Start the session if not already started and establish a database connection
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('db_conn.php');

// Initialize variables
$firstName = "No user logged in";
$accNum = null;

// Check if the session username and accNum are set
if (isset($_SESSION['accNum']) && !empty($_SESSION['accNum'])) {
    $accNum = $_SESSION['accNum']; // Ensure this variable is set

    // Prepare query to fetch the user's first name
    $query = "SELECT firstName FROM staff_account WHERE accNum = ?";
    
    // Check if the database connection and query preparation are successful
    if ($stmt = $link->prepare($query)) { 
        $stmt->bind_param("i", $accNum);
        $stmt->execute();
        $result = $stmt->get_result();

        // Fetch user information if available
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $firstName = htmlspecialchars($user['firstName']); // Ensure proper escaping
        } else {
            $firstName = "Unknown User";
        }

        // Close statement
        $stmt->close();
    } else {
        $firstName = "Error preparing the statement";
    }
} else {
    // Redirect to login page if no user is logged in
    header("Location: login.php");
    exit;
}
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weather Dashboard</title>
    <link rel="stylesheet" type="text/css" href="homestyle.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link rel="icon" type="image/x-icon" href="Images/PUPIcon.png">
    <link href="https://fonts.googleapis.com/css?family=Quicksand&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<body>
<header>
<nav class="navbar sticky-top navbar-expand-sm navbar-dark bg-maroon">
    <a class="navbar-brand" href="home.php">
        <img src="Images/PUPIcon.png" width="30" height="30" class="d-inline-block align-top" alt="PUP Logo"> PUP Heat Index Monitoring
    </a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ml-auto">
            <li class="nav-item <?php if($current_page == 'home.php') { echo 'active'; } ?>">
                <a class="nav-link" href="home.php" id="home-nav">Home</a>
            </li>
            <li class="nav-item <?php if($current_page == 'monitoring.php') { echo 'active'; } ?>">
                <a class="nav-link" href="monitoring.php" id="monitoring-nav">Monitoring</a>
            </li>
            <li class="nav-item <?php if($current_page == 'reports.php') { echo 'active'; } ?>">
                <a class="nav-link" href="reports.php" id="reports-nav">Reports</a>
            </li>
            <li class="nav-item <?php if($current_page == 'alert_notifications.php') { echo 'active'; } ?>">
                <a class="nav-link" href="alerts_notifications.php" id="alerts-notifications-nav">Alerts & Notifications</a>
            </li>
        </ul>
        <ul class="navbar-nav">
            <li class="nav-item dropdown profile">
                <a href="#" class="nav-link dropdown-toggle" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <img src="Images/Staff.png" alt="user-image" class="img-circle img-inline" width="30" height="30">
                    <span class="ml-2"><?php echo $firstName; ?></span>
                </a>
                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                    <a class="dropdown-item" href="profile.php?id=<?php echo (int)$accNum; ?>">
                        <i class="glyphicon glyphicon-user"></i> Profile
                    </a>                    
                    <a class="dropdown-item" href="change_password.php">
                        <i class="glyphicon glyphicon-cog"></i> Change Password
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
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
