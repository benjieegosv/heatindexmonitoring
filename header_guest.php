<?php
// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('db_conn.php'); // Ensure your DB connection file is included

// Initialize variables
$firstName = "No user logged in";
$user_id = null; // Initialize user_id

// Check if the session account number for guest is set
if (!empty($_SESSION['guestAccNum'])) {
    $accNum = $_SESSION['guestAccNum']; 

    // Fetch user information
    $query = "SELECT firstName FROM guest_account WHERE accNum = ?";
    if ($stmt = $link->prepare($query)) {
        $stmt->bind_param("i", $accNum);
        
        // Execute the statement and get the result
        if ($stmt->execute()) {
            $result = $stmt->get_result();

            // Fetch user information if available
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                $firstName = htmlspecialchars($user['firstName']);
                $user_id = $accNum; // Set user_id for profile link
            } else {
                $firstName = "Unknown User"; // Handle case where user isn't found
            }
        } else {
            $firstName = "Error executing query: " . htmlspecialchars($stmt->error);
        }

        // Close the statement
        $stmt->close();
    } else {
        $firstName = "Error preparing the statement: " . htmlspecialchars($link->error);
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
    <link rel="icon" type="image/x-icon" href="Images/PUPIcon.png">
    <link href="https://fonts.googleapis.com/css?family=Quicksand&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
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
            <li class="nav-item active">
                <a class="nav-link" href="home.php" id="home-nav">Home</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="monitoring.php" id="monitoring-nav">Monitoring</a>
            </li>
        </ul>
        <ul class="navbar-nav">
            <li class="nav-item dropdown profile">
                <a href="#" class="nav-link dropdown-toggle" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <img src="Images/Guest.png" alt="user-image" class="img-circle img-inline" width="30" height="30">
                    <span class="ml-2"><?php echo $firstName; ?></span>
                </a>
                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                    <?php if ($user_id): ?>
                        <a class="dropdown-item" href="profile.php?id=<?php echo (int)$user_id; ?>">
                            <i class="glyphicon glyphicon-user"></i> Profile
                        </a>
                    <?php endif; ?>
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
