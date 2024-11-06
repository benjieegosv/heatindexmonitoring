<?php
require 'vendor/autoload.php';
include 'db_conn.php';  // Ensure this file contains your database connection setup

require 'C:/xampp/htdocs/heatindexmonitoring-main/PHPMailer/src/PHPMailer.php';
require 'C:/xampp/htdocs/heatindexmonitoring-main/PHPMailer/src/SMTP.php';
require 'C:/xampp/htdocs/heatindexmonitoring-main/PHPMailer/src/Exception.php';

// Collect data from the POST request
$heatIndex = $_POST['heatIndex'] ?? '';
$temperature = $_POST['temperature'] ?? '';
$humidity = $_POST['humidity'] ?? '';
$classification = $_POST['classification'] ?? '';
$description = $_POST['description'] ?? '';
$precautions = $_POST['precautions'] ?? '';
$deviceId = $_POST['deviceId'] ?? ''; // Get the selected device ID

// Fetch device location based on deviceId
$deviceLocation = '';
if (!empty($deviceId)) {
    $sql = "SELECT location FROM device_info WHERE deviceId = ?";
    $stmt = $link->prepare($sql);
    $stmt->bind_param("i", $deviceId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $deviceLocation = $result->fetch_assoc()['location'];
    } else {
        echo json_encode(['message' => 'Device not found.']);
        exit();
    }
}

// Fetch all emails from guest_account table
$sql = "SELECT email FROM guest_account";
$result = $link->query($sql);

if ($result->num_rows > 0) {
    // Setup PHPMailer
    $mail = new PHPMailer\PHPMailer\PHPMailer();
    $mail->isSMTP(); 
    $mail->Host = 'smtp.gmail.com';  // Specify your mail server
    $mail->SMTPAuth = true;
    $mail->Username = 'kazeynaval0329@gmail.com'; // Your email address
    $mail->Password = 'htszjykecyxlclhg'; // Your email password
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    // Set the email sender
    $mail->setFrom('kazeynaval0329@gmail.com', 'PUP Heat Index Monitoring System');
    $mail->isHTML(true); // Set email format to HTML
    $mail->Subject = 'Urgent: Cancel Class Request';

    // Loop through all emails and send the email to each
    while ($row = $result->fetch_assoc()) {
        $email = $row['email'];
        
        // Add the recipient's email
        $mail->addAddress($email);

        // Set the email content
        $mail->Body = "
            <h2>Heat Index Monitoring Alert</h2>
            <p><strong>Heat Index:</strong> $heatIndex</p>
            <p><strong>Temperature:</strong> $temperature</p>
            <p><strong>Humidity:</strong> $humidity</p>
            <p><strong>Classification:</strong> $classification</p>
            <p><strong>Description:</strong> $description</p>
            <p><strong>Precautions:</strong> $precautions</p>
            <p><strong>Device Location:</strong> $deviceLocation</p> <!-- Added location -->
        ";

        // Attempt to send the email
        if (!$mail->send()) {
            echo "Message could not be sent to $email. Mailer Error: " . $mail->ErrorInfo . "<br>";
        } else {
            echo "Cancel Class Request has been sent successfully.";
        }

        // Clear all recipients for the next iteration
        $mail->clearAddresses();
    }
} else {
    echo "No users found in the guest_account table.";
}
?>
