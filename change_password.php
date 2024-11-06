<?php
session_start();
include 'db_conn.php';

// Check if user is logged in and determine user type
if (!isset($_SESSION['accNum']) && !isset($_SESSION['adminAccNum']) && !isset($_SESSION['guestAccNum'])) {
    header("Location: login.php");
    exit();
}

// UserType and user_id variables
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

if ($userType === 'admin') {
    include 'header.php';
} elseif ($userType === 'staff') {
    include 'header_staff.php';
} elseif ($userType === 'guest') {
    include 'header_guest.php';
}

function getTableByUserType($userType) {
    switch ($userType) {
        case 'admin':
            return 'admin_account';
        case 'staff':
            return 'staff_account';
        case 'guest':
            return 'guest_account';
        default:
            return '';
    }
}

$table = getTableByUserType($userType);
$feedback = "";
$showNewPasswordFields = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['oldPasswordSubmit'])) {
    $oldPassword = $_POST['oldPassword'];

    // Fetch the user's current password from the correct table
    $sql = "SELECT password FROM $table WHERE accNum = ?";
    $stmt = $link->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $storedPassword = $user['password'];

    // Check if the old password matches either the hashed or plain text stored password
    if (password_verify($oldPassword, $storedPassword) || $storedPassword === $oldPassword) {
        $showNewPasswordFields = true; // Allow new password fields to be shown
    } else {
        $feedback = "<span style='color: red;'>Old password is incorrect.</span>";
    }
}

// Handle the password change after validating the old password
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['newPasswordSubmit'])) {
    $newPassword = $_POST['newPassword'];
    $confirmNewPassword = $_POST['confirmNewPassword'];

    if (!empty($newPassword) && !empty($confirmNewPassword)) {
        // Check if new password matches the confirmation
        if ($newPassword === $confirmNewPassword) {
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            $update_sql = "UPDATE $table SET password = ? WHERE accNum = ?";
            $update_stmt = $link->prepare($update_sql);
            $update_stmt->bind_param("si", $hashedPassword, $user_id);
            
            if ($update_stmt->execute()) {
                $feedback = "<span style='color: green;'>Password successfully updated!</span>";
            } else {
                $feedback = "<span style='color: red;'>Error updating password.</span>";
            }
        } else {
            $feedback = "<span style='color: red;'>New passwords do not match.</span>";
        }
    } else {
        $feedback = "<span style='color: red;'>All fields are required.</span>";
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <style>
        #change-password-section {
            max-width: 500px;
            margin: 50px auto;
            padding: 40px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .form-control:focus {
            border-color: #600000;
            box-shadow: 0 0 10px rgba(128, 0, 0, 0.5);
            outline: none;
        }
    </style>
</head>
<body>
    <div id="change-password-section" class="container">
        <h2>Change Password</h2>

        <?php if (!$showNewPasswordFields): ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="oldPassword">Old Password</label>
                <input type="password" class="form-control" id="oldPassword" name="oldPassword" required>
            </div>
            <button type="submit" name="oldPasswordSubmit" class="btn" style="background-color: maroon; color: white;">Validate Old Password</button>
        </form>
        <?php else: ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="newPassword">New Password</label>
                <input type="password" class="form-control" id="newPassword" name="newPassword" required>
            </div>
            <div class="form-group">
                <label for="confirmNewPassword">Confirm New Password</label>
                <input type="password" class="form-control" id="confirmNewPassword" name="confirmNewPassword" required>
            </div>
            <button type="submit" name="newPasswordSubmit" class="btn" style="background-color: maroon; color: white;">Change Password</button>
        </form>
        <?php endif; ?>
        
        <br>
        <?php echo $feedback; ?>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    <?php include 'footer.php'; ?>
</body>
</html>
