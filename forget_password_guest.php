<?php
session_start();
include 'db_conn.php'; // Include database connection
require 'vendor/autoload.php'; // Include PHPMailer autoload

use PHPMailer\PHPMailer\Exception;

// Include PHPMailer
require 'C:/xampp/htdocs/heatindexmonitoring-main/PHPMailer/src/PHPMailer.php';
require 'C:/xampp/htdocs/heatindexmonitoring-main/PHPMailer/src/SMTP.php';
require 'C:/xampp/htdocs/heatindexmonitoring-main/PHPMailer/src/Exception.php';

function test_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// Handle Forgot Password Form Submission
if (isset($_POST['reset_password'])) {
    $email = test_input($_POST['email']);
    
    // Check if email exists in the database
    $query = "SELECT accNum, email FROM guest_account WHERE email = ?";
    $stmt = $link->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if ($user) {
        // Log current time for debugging
        $currentTime = date("Y-m-d H:i:s");
        echo "Current time: " . $currentTime . "<br>";
    
        // Generate a unique token and expiration time (1 hour validity)
        $token = bin2hex(random_bytes(50)); 
        $expTime = date("Y-m-d H:i:s", strtotime('+1 day'));
        echo "Expiration time: " . $expTime . "<br>";

        // Store token in a 'password_resets' table
        $query = "INSERT INTO password_resets_guest (accNum, token, expires_at) VALUES (?, ?, ?)";
        $stmt = $link->prepare($query);

        if ($stmt === false) {
            die('Prepare failed: ' . htmlspecialchars($link->error));
        }

        $stmt->bind_param("iss", $user['accNum'], $token, $expTime);

        if (!$stmt->execute()) {
            die('Execute failed: ' . htmlspecialchars($stmt->error));
        }

        // Send the reset email using PHPMailer
        $resetLink = "http://localhost/heatindexmonitoring-main/reset_password_guest.php?token=" . $token;

        $mail = new PHPMailer\PHPMailer\PHPMailer();
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; // Use your SMTP host
            $mail->SMTPAuth = true;
            $mail->Username = 'kazeynaval0329@gmail.com'; // Your email
            $mail->Password = 'htszjykecyxlclhg'; // Your email password
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('kazeynaval0329@gmail.com', 'PUP Heat Index Monitoring System');
            $mail->addAddress($email); // Recipient email

            // Email content
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request';
            $mail->Body = "<p>We received a request to reset your password. Click <a href='$resetLink'>here</a> to reset it. This link is valid for 1 day.</p>";

            if ($mail->send()) {
                echo "A password reset link has been sent to your email address.";
            } else {
                echo "Error in sending the reset email.";
            }
        } catch (Exception $e) {
            echo "Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
        echo "No account found with this email.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="your-stylesheet.css"> 
    <style>
        body {
            font-family: sans-serif;
            background-color: #f4f4f4;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0; 
        }

        h2 {
            color: #333;
        }

        /* Form Container Styling */
        form {
            background-color: #fff;
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            text-align: center; /* Center content within the form */
        }

        /* Input Fields Styling */
        input[type="email"] {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        /* Submit Button Styling */
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

        /* Link Styling */
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
    <h2>Forgot Your Password?</h2>
    <p>Enter your email address, and we will send you instructions to reset your password.</p>
    
    <!-- Forgot Password Form -->
    <form action="forget_password_guest.php" method="POST">
        <input type="email" name="email" placeholder="Enter your email" required><br>
        <input type="submit" name="reset_password" value="Reset Password"><br>
    </form>

    <a href="login.php" class="back-button">Back to Login</a>
</body>
</html>
