<?php
require 'vendor/autoload.php'; // Assuming you are using Composer for PHPMailer
require 'C:/xampp/htdocs/heatindexmonitoring-main/PHPMailer/src/PHPMailer.php';
require 'C:/xampp/htdocs/heatindexmonitoring-main/PHPMailer/src/SMTP.php';
require 'C:/xampp/htdocs/heatindexmonitoring-main/PHPMailer/src/Exception.php';

include 'db_conn.php'; 
session_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['accNum'])) {
    header("Location: login.php");
    exit();
}

// Retrieve user information from session
$user_id = $_SESSION['accNum'];

// Determine user type
$sql = "SELECT 'staff' AS user_type FROM staff_account WHERE accNum = ?";
$stmt = $link->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_type = $result->num_rows > 0 ? $result->fetch_assoc()['user_type'] : null;
    $stmt->close();
} else {
    die("Prepare failed: " . $link->error);
}

// Include the appropriate header file based on user type
if ($user_type === 'staff') {
    include 'header_staff.php';
} else {
    echo "Error: User type not recognized.";
    exit();
}

// Fetch devices for dropdown
$devicesQuery = "SELECT deviceId, deviceName FROM device_info";
$devicesResult = $link->query($devicesQuery);
$devices = [];
while ($row = $devicesResult->fetch_assoc()) {
    $devices[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PUP Heat Index Monitoring</title>
    <link rel="stylesheet" type="text/css" href="homestyle.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <link rel="icon" type="x-icon" href="jagran_logo1.jpg">
    <link href="https://fonts.googleapis.com/css?family=Quicksand&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        .header-section {
            text-align: center;
            margin-top: 50px;
            padding-bottom: 20px;
            color: #333;
        }

        .header-section h1 {
            font-size: 3em;
            font-weight: 700;
            color: white;
            text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.2);
        }

        .device-select-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 20px;
        }

        .device-label {
            font-size: 20px;
            margin-right: 15px;
            color: white;
        }

        .device-dropdown {
            width: 50%;
            padding: 10px;
            font-size: 18px;
            border-radius: 10px;
            border: 1px solid #ccc;
            box-shadow: inset 0px 2px 5px rgba(0, 0, 0, 0.05);
            background-color: #f8f8f8;
        }

        #monitoringData {
            background-color: rgba(255, 255, 255, 0.85); /* Semi-transparent background */
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.1);
            margin-top: 30px;
        }

        #monitoringData h2 {
            font-size: 28px;
            margin-bottom: 20px;
            color: #444;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 10px;
        }

        #monitoringData p {
            font-size: 20px;
            margin: 10px 0;
            line-height: 1.6;
        }

        #monitoringData span {
            font-weight: bold;
            color: #333;
        }

        .data-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }

        .data-section .data-box {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            width: 30%;
            box-shadow: 0px 3px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
            border: 1px solid #eee;
        }

        .data-box p {
            font-size: 22px;
            font-weight: 600;
            color: #666;
        }

        .data-box span {
            font-size: 26px;
            color: #ff4c4c; /* A color for heat index or alerting */
        }

        /* Styling classification with color coding */
        .classification-danger {
            color: #ff4c4c;
            font-weight: bold;
        }

        .classification-warning {
            color: #ffcc00;
            font-weight: bold;
        }

        .classification-safe {
            color: #4caf50;
            font-weight: bold;
        }

        /* Adjust for smaller screens */
        @media (max-width: 768px) {
            .data-section {
                flex-direction: column;
                align-items: center;
                height: auto; 
            }

            .data-box {
                width: 80%;
                margin-bottom: 20px;
            }

            .device-dropdown {
                width: 80%;
            }
        }

        @media (max-width: 576px) {
            .data-box {
                width: 100%;
            }

            .header-section h1 {
                font-size: 2.5em;
            }

            #monitoringData {
                padding: 20px;
            }

            #monitoringData p {
                font-size: 18px;
            }

            .device-dropdown {
                width: 100%;
            }
        }

        .suggestion-box {
            margin-top: 20px;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 10px;
            box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1);
        }

        .suggestion-box textarea {
            width: 100%;
            height: 100px;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
            resize: none;
        }

        .button-group {
            margin-top: 20px;
            text-align: center;
        }

        .button-group button {
            padding: 10px 20px;
            margin: 10px;
            font-size: 18px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .cancel-class-button {
            background-color: #ff4c4c;
            color: white;
        }

        .send-suggestion-button {
            background-color: #4caf50;
            color: white;
        }

        /* Forecast Section */
        #forecast-section {
            margin-top: 30px;
            background-color: rgba(255, 255, 255, 0.85);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .date-selector {
            margin-bottom: 20px;
            text-align: center;
        }

        .forecast-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .forecast-table th, .forecast-table td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: center;
        }

        .forecast-table th {
            background-color: #f0f0f0;
        }
    </style>
    <script>
        let ws;
        let selectedDeviceId;

        // Function to change the device
        function changeDevice(deviceId) {
            console.log('Device selected: ' + deviceId); // Log device selection
            selectedDeviceId = deviceId;

            // Close any existing WebSocket connection
            if (ws) {
                ws.close();
            }

            if (deviceId) {
                // Establish a new WebSocket connection
                ws = new WebSocket('ws://192.168.247.185:8080');

                ws.onopen = function() {
                    console.log('WebSocket connection opened');
                    ws.send(JSON.stringify({ action: 'changeDevice', deviceId: deviceId }));
                };

                ws.onmessage = function(event) {
                    if (event.data instanceof Blob) {
                        // If the message is a Blob, convert it to text before parsing
                        const reader = new FileReader();
                        reader.onload = function() {
                            try {
                                const data = JSON.parse(reader.result); // Parse the JSON from the Blob
                                console.log('Received data from WebSocket:', data);

                                // Check if data contains the necessary keys
                                if (data.heat_index && data.temperature && data.humidity) {
                                    // Update the UI with the received data
                                    document.getElementById('currentHeatIndex').textContent = data.heat_index + "°C";
                                    document.getElementById('currentTemperature').textContent = data.temperature + "°C";
                                    document.getElementById('currentHumidity').textContent = data.humidity + "%";
                                    document.getElementById('currentUpdateTime').textContent = `Last updated: ${new Date().toLocaleString()}`;

                                    // Fetch safety precautions based on the heat index
                                    fetchSafetyPrecautions(data.heat_index);
                                } else {
                                    console.error('Data missing required fields:', data);
                                }
                            } catch (error) {
                                console.error('Error parsing WebSocket message as JSON:', error);
                            }
                        };
                        reader.readAsText(event.data); // Read the Blob as text
                    } else {
                        // Handle text data directly (if the data is not a Blob)
                        try {
                            const data = JSON.parse(event.data); // Directly parse JSON from string
                            console.log('Received data from WebSocket:', data);

                            // Update the UI with the received data
                            document.getElementById('currentHeatIndex').textContent = data.heat_index + "°C";
                            document.getElementById('currentTemperature').textContent = data.temperature + "°C";
                            document.getElementById('currentHumidity').textContent = data.humidity + "%";
                            document.getElementById('currentUpdateTime').textContent = `Last updated: ${new Date().toLocaleString()}`;

                            // Fetch safety precautions based on the heat index
                            fetchSafetyPrecautions(data.heat_index);
                        } catch (error) {
                            console.error('Error parsing WebSocket message as JSON:', error);
                        }
                    }
                };

                ws.onerror = function(error) {
                    console.error('WebSocket error:', error);
                    resetDisplay();
                };

                ws.onclose = function() {
                    console.log('WebSocket connection closed');
                };
            } else {
                resetDisplay();  // Reset the display if no device is selected
            }
        }

        function fetchSafetyPrecautions(heatIndex, temperature, humidity) {
            let xhr = new XMLHttpRequest();
            xhr.open('GET', 'get_safety_precautions.php?heatIndex=' + heatIndex + '&temperature=' + temperature + '&humidity=' + humidity, true);
            xhr.onload = function () {
                if (xhr.status === 200) {
                    let precautions = JSON.parse(xhr.responseText);
                    console.log('Received safety precautions:', precautions);  // Log the response

                    document.getElementById('classification').textContent = precautions.classification;
                    document.getElementById('description').textContent = precautions.description;
                    document.getElementById('safetyPrecautions').textContent = precautions.safety_precautions;
                } else {
                    console.error('Error fetching safety precautions.');
                }
            };
            xhr.onerror = function() {
                console.error('Request failed');
            };
            xhr.send();
        }

        // Send cancel class request
        function sendCancelClass() {
            const heatIndex = document.getElementById('currentHeatIndex').textContent;
            const temperature = document.getElementById('currentTemperature').textContent;
            const humidity = document.getElementById('currentHumidity').textContent;
            const classification = document.getElementById('classification').textContent;
            const description = document.getElementById('description').textContent;
            const precautions = document.getElementById('safetyPrecautions').textContent;
            const timestamp = document.getElementById('currentUpdateTime').textContent; // Getting the timestamp
            const deviceId = selectedDeviceId; // Get the selected device ID

            const formData = new FormData();
            formData.append('heatIndex', heatIndex);
            formData.append('temperature', temperature);
            formData.append('humidity', humidity);
            formData.append('classification', classification);
            formData.append('description', description);
            formData.append('precautions', precautions);
            formData.append('timestamp', timestamp); // Include timestamp
            formData.append('deviceId', deviceId); // Include the selected device ID

            fetch('send_cancel_class.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(result => {
                alert(result);
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }

        // Send suggestions
        function sendSuggestions() {
            const heatIndex = document.getElementById('currentHeatIndex').textContent;
            const temperature = document.getElementById('currentTemperature').textContent;
            const humidity = document.getElementById('currentHumidity').textContent;
            const classification = document.getElementById('classification').textContent;
            const description = document.getElementById('description').textContent;
            const precautions = document.getElementById('safetyPrecautions').textContent;
            const timestamp = document.getElementById('currentUpdateTime').textContent; // Getting the timestamp
            const deviceId = selectedDeviceId; // Get the selected device ID

            const formData = new FormData();
            formData.append('heatIndex', heatIndex);
            formData.append('temperature', temperature);
            formData.append('humidity', humidity);
            formData.append('classification', classification);
            formData.append('description', description);
            formData.append('precautions', precautions);
            formData.append('timestamp', timestamp); // Include timestamp
            formData.append('deviceId', deviceId); // Include the selected device ID

            fetch('send_suggestion.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(result => {
                alert(result);
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }

        // Reset the display to the default state
        function resetDisplay() {
            document.getElementById('currentHeatIndex').textContent = "Loading...";
            document.getElementById('currentTemperature').textContent = "Loading...";
            document.getElementById('currentHumidity').textContent = "Loading...";
            document.getElementById('currentUpdateTime').textContent = "Last updated: Loading...";
            document.getElementById('classification').textContent = "Loading...";
            document.getElementById('description').textContent = "Loading...";
            document.getElementById('safetyPrecautions').textContent = "Loading...";
        }

        // Fetch and display forecast data
        function fetchForecast() {
            const date = document.getElementById('forecastDate').value; // Get the selected date
            fetch(`fetch_forecast.php?date=${date}`)
                .then(response => response.json())
                .then(data => {
                    displayForecast(data);
                })
                .catch(error => {
                    console.error('Error fetching forecast data:', error);
                });
        }

        function displayForecast(data) {
            const forecastTableBody = document.getElementById('forecastTableBody');
            forecastTableBody.innerHTML = ''; // Clear previous data

            if (data && data.length > 0) {
                data.forEach(item => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${item.date}</td>
                        <td>${item.temperature_forecast}°C</td>
                        <td>${item.humidity_forecast}%</td>
                        <td>${item.heat_index_forecast}°C</td>
                    `;
                    forecastTableBody.appendChild(row);
                });
            } else {
                const row = document.createElement('tr');
                row.innerHTML = `<td colspan="4">No forecast data available for the selected date.</td>`;
                forecastTableBody.appendChild(row);
            }
        }

         // Cancel forecast class request
               // Send cancel class request from forecast
               function cancelForecastClass() {
            const date = document.getElementById('forecastDate').value; // Get the selected date

            // Check if a date is selected
            if (!date) {
                alert("Please select a date for the forecast.");
                return;
            }

            // Prompt the user for additional information
            const additionalInfo = prompt("Please provide any additional information for the cancellation:");

            // If the user cancels the prompt, do nothing
            if (additionalInfo === null) {
                return; // Exit the function
            }

            const formData = new FormData();
            formData.append('date', date);
            formData.append('suggestion', additionalInfo); // Use the additional info from the prompt

            // Send a request to the server to cancel the forecast
            fetch('send_cancel_class_forecast.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(result => {
                alert(result); // Display the response from the server
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }

        // Reset the display to the default state
        function resetDisplay() {
            document.getElementById('currentHeatIndex').textContent = "Loading...";
            document.getElementById('currentTemperature').textContent = "Loading...";
            document.getElementById('currentHumidity').textContent = "Loading...";
            document.getElementById('currentUpdateTime').textContent = "Last updated: Loading...";
            document.getElementById('classification').textContent = "Loading...";
            document.getElementById('description').textContent = "Loading...";
            document.getElementById('safetyPrecautions').textContent = "Loading...";
        }

        // Fetch and display forecast data
        function fetchForecast() {
            const date = document.getElementById('forecastDate').value; // Get the selected date
            fetch(`fetch_forecast.php?date=${date}`)
                .then(response => response.json())
                .then(data => {
                    displayForecast(data);
                })
                .catch(error => {
                    console.error('Error fetching forecast data:', error);
                });
        }
    </script>
</head>
<body>
<div class="container">
    <div class="header-section">
        <h1>Alerts and Notifications</h1>

        <div class="device-select-container">
            <label for="deviceSelect" class="device-label">Select Device:</label>
            <select id="deviceSelect" class="device-dropdown" onchange="changeDevice(this.value)">
                <option value="">Select a device</option>
                <?php foreach ($devices as $device): ?>
                    <option value="<?php echo $device['deviceId']; ?>"><?php echo htmlspecialchars($device['deviceName']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div id="monitoringData">
        <h2>Current Data:</h2>
        <div class="data-section">
            <div class="data-box">
                <p>Heat Index: <span id="currentHeatIndex">Loading...</span></p>
            </div>
            <div class="data-box">
                <p>Temperature: <span id="currentTemperature">Loading...</span></p>
            </div>
            <div class="data-box">
                <p>Humidity: <span id="currentHumidity">Loading...</span></p>
            </div>
        </div>
        <p>Last Updated: <span id="currentUpdateTime">Loading...</span></p>

        <h2>Safety Precautions:</h2>
        <p>Classification: <span id="classification">Loading...</span></p>
        <p>Description: <span id="description">Loading...</span></p>
        <p>Precautions: <span id="safetyPrecautions">Loading...</span></p>
    </div>
    
    <div class="button-group">
        <button class="cancel-class-button" onclick="sendCancelClass()">Cancel Class</button>
        <button class="send-suggestion-button" onclick="sendSuggestions()">Send Suggestions</button>
    </div>

    <div id="forecast-section">
        <h2>Forecast Data</h2>
        <div class="date-selector">
            <label for="forecastDate">Select Date for Forecast:</label>
            <input type="date" id="forecastDate" />
            <button onclick="fetchForecast()">Get Forecast</button>
            <button class="cancel-class-button" onclick="cancelForecastClass()">Cancel Class from Forecast</button>
        </div>
        <table class="forecast-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Temperature Forecast</th>
                    <th>Humidity Forecast</th>
                    <th>Heat Index Forecast</th>
                </tr>
            </thead>
            <tbody id="forecastTableBody">
                <!-- Forecast data will be dynamically inserted here -->
            </tbody>
        </table>
    </div>
</div>
<?php include 'footer.php'; ?>
</body>
</html>
