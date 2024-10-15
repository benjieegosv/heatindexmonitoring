<?php
// Include PHPMailer library
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Make sure this path is correct based on where you installed PHPMailer
require 'C:/xampp/htdocs/heatindexmonitoring-main/PHPMailer/src/PHPMailer.php';
require 'C:/xampp/htdocs/heatindexmonitoring-main/PHPMailer/src/SMTP.php';
require 'C:/xampp/htdocs/heatindexmonitoring-main/PHPMailer/src/Exception.php';

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form inputs
    $name = htmlspecialchars(trim($_POST['name']));
    $email = htmlspecialchars(trim($_POST['email']));
    $message = htmlspecialchars(trim($_POST['message']));

    // Validate the inputs
    if (!empty($name) && !empty($email) && !empty($message)) {
        // Create an instance of PHPMailer
        $mail = new PHPMailer(true);

        try {
            // SMTP server configuration
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';              // Set the SMTP server to send through
            $mail->SMTPAuth = true;                      // Enable SMTP authentication
            $mail->Username = 'kazeynaval0329@gmail.com';    // Your Gmail address (admin email)
            $mail->Password = 'htszjykecyxlclhg';     // Your Gmail password or App Password if 2FA is enabled
            $mail->SMTPSecure = 'tls';                   // Enable TLS encryption
            $mail->Port = 587;                           // TCP port to connect to

            // Set the sender email (coming from the form)
            $mail->setFrom($email, $name);               // Sender's email and name from form input
            $mail->addAddress('kazeynaval0329@gmail.com');      // Add recipient email (replace with your admin email)

            // Content
            $mail->isHTML(false);                        // Set email format to plain text
            $mail->Subject = "New Contact Us Message from $name";
            $mail->Body = "Name: $name\nEmail: $email\n\nMessage:\n$message";

            // Send the email
            $mail->send();
            echo "<div class='message success'><h1>Thank you! Your message has been sent successfully.</h1><a href='home.php' class='back-link'>Back to Home</a></div>";
        } catch (Exception $e) {
            // Handle errors with styled error message
            echo "<div class='message error'><h1>Message could not be sent.</h1><p>Mailer Error: {$mail->ErrorInfo}</p><a href='contact.php' class='back-link'>Try again</a></div>";
        }
    } else {
        // Display an error message if any field is empty with styling
        echo "<div class='message error'><h1>Error: All fields are required!</h1><a href='contact.php' class='back-link'>Go back to Contact Form</a></div>";
    }
} else {
    // Redirect to contact page if the script is accessed directly
    header("Location: contact.php");
    exit();
}
?>

<!-- Include some basic styling for the error/success messages -->
<style>
    body {
        font-family: Arial, sans-serif;
        background-color: #f4f4f4;
        padding: 20px;
    }
    .message {
        max-width: 600px;
        margin: 50px auto;
        padding: 20px;
        border-radius: 8px;
        text-align: center;
    }
    .message h1 {
        margin-bottom: 10px;
    }
    .message.success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    .message.error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    .back-link {
        display: inline-block;
        margin-top: 15px;
        padding: 10px 15px;
        background-color: #007bff;
        color: white;
        text-decoration: none;
        border-radius: 5px;
    }
    .back-link:hover {
        background-color: #0056b3;
    }
</style>

