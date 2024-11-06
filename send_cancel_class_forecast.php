<?php
require 'vendor/autoload.php'; // Assuming you are using Composer for PHPMailer
include 'db_conn.php';  // Ensure this file contains your database connection setup

require 'C:/xampp/htdocs/heatindexmonitoring-main/PHPMailer/src/PHPMailer.php';
require 'C:/xampp/htdocs/heatindexmonitoring-main/PHPMailer/src/SMTP.php';
require 'C:/xampp/htdocs/heatindexmonitoring-main/PHPMailer/src/Exception.php';

// Collect data from the POST request
$date = $_POST['date'] ?? '';
$suggestion = $_POST['suggestion'] ?? '';

// Fetch forecast details (temperature, humidity, heat index) based on the date
$sqlForecast = "SELECT temperature_forecast, humidity_forecast, heat_index_forecast FROM forecast_data WHERE date = ?";
$stmtForecast = $link->prepare($sqlForecast);
$stmtForecast->bind_param("s", $date);
$stmtForecast->execute();
$resultForecast = $stmtForecast->get_result();

if ($resultForecast->num_rows > 0) {
    $forecast = $resultForecast->fetch_assoc();
    $temperature = $forecast['temperature_forecast'];
    $humidity = $forecast['humidity_forecast'];
    $heatIndex = $forecast['heat_index_forecast'];

    // Logic to cancel the forecast class (example: delete the record)
    $sqlDelete = "DELETE FROM forecast_data WHERE date = ?";
    $stmtDelete = $link->prepare($sqlDelete);
    $stmtDelete->bind_param("s", $date);

    if ($stmtDelete->execute()) {
        echo "Forecast for the date $date has been canceled successfully.";
        
        // Send email notifications
        // Fetch all emails from guest_account table
        $sqlEmail = "SELECT email FROM guest_account";
        $resultEmail = $link->query($sqlEmail);

        if ($resultEmail->num_rows > 0) {
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
            $mail->Subject = 'Urgent: Cancel Class Request from Forecast';

            // Loop through all emails and send the email to each
            while ($row = $resultEmail->fetch_assoc()) {
                $email = $row['email'];
                
                // Add the recipient's email
                $mail->addAddress($email);

                // Set the email content
                $mail->Body = "
                    <h2>Heat Index Monitoring Alert</h2>
                    <p><strong>Date:</strong> $date</p>
                    <p><strong>Temperature:</strong> $temperature</p>
                    <p><strong>Humidity:</strong> $humidity</p>
                    <p><strong>Heat Index:</strong> $heatIndex</p>
                    <p><strong>Additional Information:</strong> $suggestion</p>
                ";

                // Attempt to send the email
                if (!$mail->send()) {
                    echo "Message could not be sent to $email. Mailer Error: " . $mail->ErrorInfo . "<br>";
                } else {
                    echo " Cancel Class Request has been sent successfully.";
                }

                // Clear all recipients for the next iteration
                $mail->clearAddresses();
            }
        } else {
            echo "No users found in the guest_account table.";
        }
    } else {
        echo "Error canceling forecast: " . $stmtDelete->error;
    }

    $stmtDelete->close();
} else {
    echo "No forecast data found for the selected date.";
}

$stmtForecast->close();