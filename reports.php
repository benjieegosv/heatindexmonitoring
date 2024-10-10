<?php
// Include database connection and start session
include 'db_conn.php'; 
session_start();

// Check if user is logged in
if (!isset($_SESSION['accNum']) && !isset($_SESSION['adminAccNum'])) {
    header("Location: login.php");
    exit();
}

// Initialize userType variable
if (isset($_SESSION['adminAccNum'])) {
    $userType = 'admin'; 
    $user_id = $_SESSION['adminAccNum'];
} elseif (isset($_SESSION['accNum'])) {
    $userType = 'staff';
    $user_id = $_SESSION['accNum']; 
} else {
    echo "Error: User type not recognized.";
    exit();
}

// Include the appropriate header file based on user type
if ($userType === 'admin') {
    include 'header.php';
} elseif ($userType === 'staff') {
    include 'header_staff.php';
}

// Retrieve additional user information if needed
$sql = "
    SELECT accNum, username, email FROM staff_account WHERE accNum = ? 
    UNION ALL 
    SELECT accNum, username, email FROM admin_account WHERE accNum = ?
";
$stmt = $link->prepare($sql);

if ($stmt) {
    $stmt->bind_param("ii", $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Process additional user information
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
    <title>Manage Accounts</title>
    <style>
        body::before {
            content: "";
            position: fixed; /* Make sure the image stays in place while scrolling */
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url("Images/Thermo.jpg");
            background-repeat: no-repeat;
            background-size: cover;
            background-position: center;
            filter: blur(8px); /* Apply blur effect to the background image */
            z-index: -1; /* Ensure the blurred background stays behind the content */
        }

        #manage-reports-section {
            max-width: 800px;
            margin: 50px auto;
            padding: 40px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .option-container {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .option-container button {
            padding: 20px 30px;
            font-size: 18px;
            color: #fff;
            width: 80%;
            background-color: #800000; /* Maroon */
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin: 18px 0; /* Space between buttons */
        }

        .option-container button:hover {
            background-color: #4d0000; /* Darker maroon for hover */
        }
    </style>
</head>
<body>
    <div id="manage-reports-section">
        <h2>Reports</h2>
        <div class="option-container">
            <button id="view-reports-btn">View Reports</button>
            <button id="generate-reports-btn">Generate Reports</button>
        </div>
    </div>
    
    <script>
        document.getElementById('view-reports-btn').addEventListener('click', function() {
            window.location.href = 'view_reports.php'; // Redirect to the user approval page
        });

        document.getElementById('generate-reports-btn').addEventListener('click', function() {
            window.location.href = 'generate_reports.php'; //
        });
    </script>
</body>
</html>
