<?php
session_start();
include 'db_conn.php'; // Include database connection

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Check if the token is valid and not expired
    $query = "SELECT accNum, expires_at FROM password_resets WHERE token = ? AND expires_at >= NOW()";
    $stmt = $link->prepare($query);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $resetData = $result->fetch_assoc();

    if ($resetData) {
        // Debug expiration time
        $currentTime = date("Y-m-d H:i:s");
        echo "Token expiration time: " . $resetData['expires_at'] . "<br>";

        // Proceed with password reset
        if (isset($_POST['new_password'])) {
            $newPassword = password_hash($_POST['new_password'], PASSWORD_BCRYPT);
            $query = "UPDATE staff_account SET password = ? WHERE accNum = ?";
            $stmt = $link->prepare($query);
            $stmt->bind_param("si", $newPassword, $resetData['accNum']);
            $stmt->execute();

            // Delete the token after successful password reset
            $query = "DELETE FROM password_resets WHERE token = ?";
            $stmt = $link->prepare($query);
            $stmt->bind_param("s", $token);
            $stmt->execute();

            echo "Password has been reset successfully.";
        }
    } else {
        echo "Invalid or expired token.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <style>
    body {
        font-family: sans-serif;
        background-color: #f4f4f4;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
        margin: 0;
        }

        h2 {
        color: #333;
        }

        form {
        background-color: #fff;
        padding: 60px;
        border-radius: 5px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        input[type="password"] {
        width: 100%;
        padding: 15px;
        margin-bottom: 15px;
        border: 1px solid #ddd;
        border-radius: 4px;
        box-sizing: border-box;
        }

        input[type="submit"] {
        background-color: #6c1f1f;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        }

        input[type="submit"]:hover {
        background-color: black;
        color: white;
        }
               
        .back-button {
        display: inline-block;
        padding: 7px 10px;
        background-color: #007bff; /* Bootstrap primary color */
        color: white;
        text-align: center;
        text-decoration: none;
        border-radius: 5px;
        transition: background-color 0.3s, transform 0.2s;
        margin-top: 10px;
        }

        .back-button:hover {
        background-color: #0056b3; /* Darker shade on hover */
        transform: scale(1.05);
        }
        </style>
</head>
<body>
    <h2>Reset Your Password</h2>
    <form action="" method="POST">
        <input type="password" name="new_password" placeholder="Enter your new password" required><br>
        <input type="submit" value="Reset Password">
        <a href="login.php" class="back-button">Back to Login</a>
    </form>
</body>
</html>
