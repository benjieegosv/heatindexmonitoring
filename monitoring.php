<?php
// Include database connection and start session
include 'db_conn.php';
session_start();
date_default_timezone_set('Asia/Manila'); // Set timezone to Manila

// Function to calculate Heat Index based on Temperature and Relative Humidity
function calculateHeatIndex($T, $R) {
    $c1 = -8.78469475556;
    $c2 = 1.61139411;
    $c3 = 2.33854883889;
    $c4 = -0.14611605;
    $c5 = -0.012308094;
    $c6 = -0.0164248277778;
    $c7 = 0.002211732;
    $c8 = 0.00072546;
    $c9 = -0.000003582;

    $HI = $c1 + ($c2 * $T) + ($c3 * $R) + ($c4 * $T * $R) +
          ($c5 * pow($T, 2)) + ($c6 * pow($R, 2)) +
          ($c7 * pow($T, 2) * $R) + ($c8 * $T * pow($R, 2)) +
          ($c9 * pow($T, 2) * pow($R, 2));

    return round($HI, 2);
}

// Function to fetch and store today's weather data from Visual Crossing API
function fetchAndStoreWeatherData($link) {
    $apiUrl = "https://weather.visualcrossing.com/VisualCrossingWebServices/rest/services/timeline/barangay%20630/today?unitGroup=metric&include=days&key=CNZZKLRXMRGWHVNB7D3ZTV2NP&contentType=json";

    // Fetch data from the API
    $response = file_get_contents($apiUrl);
    if ($response === FALSE) {
        die('Error fetching data from Visual Crossing API.');
    }
    $data = json_decode($response, true);

    // Extract today's weather data
    $todayData = $data['days'][0];
    $date = $todayData['datetime'];
    $temperature = $todayData['temp'];
    $humidity = $todayData['humidity'];

    // Calculate the Heat Index using the extracted temperature and humidity
    $heatIndex = calculateHeatIndex($temperature, $humidity);

    // Insert the data into the database
    $location = "barangay 630";
    $source = "Visual Crossing";
    $sql = "INSERT INTO external_heat_index_data (location, date, temperature, humidity, heatIndex, source) 
            VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = $link->prepare($sql);
    if ($stmt === false) {
        die('Prepare failed: ' . htmlspecialchars($link->error));
    }

    $stmt->bind_param("ssddds", $location, $date, $temperature, $humidity, $heatIndex, $source);
    $stmt->execute();

    if ($stmt->error) {
        die('Execute failed: ' . htmlspecialchars($stmt->error));
    }

    $stmt->close();
}

// Check if user is not logged in and redirect to login page
if (!isset($_SESSION['accNum']) && !isset($_SESSION['adminAccNum']) && !isset($_SESSION['guestAccNum'])) {
    header("Location: login.php");
    exit();
}

// Initialize userType and user_id variables
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

// Include the appropriate header file based on user type
if ($userType === 'admin') {
    include 'header.php';
} elseif ($userType === 'staff') {
    include 'header_staff.php';
} elseif ($userType === 'guest') {
    include 'header_guest.php';
}

// Fetch device information for dropdown
$deviceSql = "SELECT deviceId, deviceName FROM device_info";
$deviceResult = $link->query($deviceSql);
$devices = [];
if ($deviceResult && $deviceResult->num_rows > 0) {
    while ($row = $deviceResult->fetch_assoc()) {
        $devices[] = $row;
    }
} else {
    echo "No devices found.";
}

// Fetch today's date
$today = new DateTime();
$todayFormatted = $today->format('Y-m-d');

// Check if it's midnight (12:00 AM) or later
$currentHour = date('H');

// Check the last weather data date from the external_heat_index_data table
$sql = "SELECT date FROM external_heat_index_data ORDER BY date DESC LIMIT 1";
$result = $link->query($sql);
$lastWeatherDate = null;

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $lastWeatherDate = $row['date'];
}

// Fetch the last forecast date from the forecast_data table
$sql = "SELECT date FROM forecast_data ORDER BY date DESC LIMIT 1";
$result = $link->query($sql);
$lastForecastDate = null;

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $lastForecastDate = $row['date'];
}

// If it's midnight or later and the last weather date is not today, fetch weather data
if ($currentHour === '00' || ($currentHour > '00' && $lastWeatherDate !== $todayFormatted)) {
    // Fetch the latest weather data from Visual Crossing
    fetchAndStoreWeatherData($link);

    // Truncate the existing forecast data
    $link->query("TRUNCATE TABLE forecast_data");

    // Run the Python script for forecasting
    $forecastDataRaw = shell_exec('C:/Python312/python.exe /xampp/htdocs/heatindexmonitoring-main/forecast.py 2>&1');

    // Decode the JSON output from the Python script
    $forecastData = json_decode($forecastDataRaw, true);

    // Save the forecast data to the database
    foreach ($forecastData as $data) {
        $insertQuery = "
            INSERT INTO forecast_data (date, temperature_forecast, humidity_forecast, heat_index_forecast, location) 
            VALUES (?, ?, ?, ?, ?)
        ";
        $stmt = $link->prepare($insertQuery);
        $stmt->bind_param("ssdds", $data['date'], $data['temperature_forecast'], $data['humidity_forecast'], $data['heat_index_forecast'], $data['location']);
        if (!$stmt->execute()) {
            echo 'Error storing forecast data: ' . htmlspecialchars($stmt->error);
        }
    }
}

// Fetch the latest monitoring data and device location from the database
$sql = "SELECT m.*, d.location 
        FROM monitoring m 
        JOIN device_info d ON m.deviceId = d.deviceId 
        ORDER BY m.timestamp DESC 
        LIMIT 1";
$result = $link->query($sql);

// Initialize current data to avoid errors
$currentData = null;

if ($result && $result->num_rows > 0) {
    $currentData = $result->fetch_assoc();
} 

// Fetch the latest forecasted data to display to the user
$sql = "SELECT * FROM forecast_data ORDER BY date ASC LIMIT 7"; // Fetch the last 7 days of forecast
$forecastResult = $link->query($sql);

// Initialize latestForecastData as an empty array
$latestForecastData = [];

if ($forecastResult) { // Check if the query was successful
    if ($forecastResult->num_rows > 0) {
        $latestForecastData = $forecastResult->fetch_all(MYSQLI_ASSOC); // Fetch all rows
    } else {
        // No rows returned
        echo 'No forecast data available.';
    }
} else {
    // Handle SQL query error
    echo 'Error fetching forecast data: ' . htmlspecialchars($link->error);
}

// Close the database connection
$link->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weather Monitoring</title>
    <style>
        /* Add your CSS styling here */
        .compare-container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f5f5f5;
            flex-wrap: wrap; /* Ensures cards align properly when more are added */
        }

        h4 {
            color: #808080;
        }

        .back-button {
            position: absolute;
            top: 20px;
            left: 20px;
            text-decoration: none;
            color: white; /* Change text color to white for contrast */
            font-size: 18px;
            display: flex;
            align-items: center;
            padding: 10px 15px; /* Add padding for better touch area */
            background-color: #800000; /* Maroon background color */
            border-radius: 5px; /* Rounded corners for the button */
            transition: background-color 0.3s ease, transform 0.3s ease; /* Smooth transitions for hover effect */
        }

        .back-button:hover {
            background-color: #a00000; /* Darker maroon on hover */
            transform: scale(1.05); /* Slightly enlarge button on hover */
        }

        .card {
            background-color: #2c3e50;
            border-radius: 20px;
            padding: 30px;
            width: 300px;
            margin: 10px;
            text-align: center;
            color: white;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            position: relative; /* Added for positioning */
        }

        .card .header {
            font-size: 18px;
            margin-bottom: 20px;
        }

        .card .circle {
            background: radial-gradient(circle, #fbc531, #f39c12);
            width: 150px;
            height: 150px;
            border-radius: 50%;
            margin: 0 auto;
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            position: relative; /* Added for positioning */
        }

        .card .circle .data-value {
            font-size: 32px; /* Adjust font size to emphasize the heat index */
            font-weight: bold; /* Bold text for emphasis */
            line-height: 1.2; /* Adjust line height for better spacing */
        }

        .card .details {
            margin-top: 20px;
            font-size: 14px;
            line-height: 1.5;
        }

        .data-title {
            font-weight: bold;
            margin: 10px 0 5px; /* Spacing between titles and values */
        }

        .data-value {
            font-size: 16px; /* Adjust font size as needed */
        }

        .small-text {
            font-size: 12px;
            color: #b0b0b0; /* Optional color for small text */
        }

        .thermometer-icon {
            position: absolute;
            bottom: 10px; /* Position at the bottom of the card */
            right: 10px; /* Position to the right */
            color: white; /* Icon color */
            font-size: 24px; /* Icon size */
        }

        .forecast-bar.safe {
            background-color: blue;  /* Safe is usually indicated with cool colors */
        }

        .forecast-bar.caution {
            background-color: green;  /* 27°C to 32°C (Caution) */
        }

        .forecast-bar.extreme-caution {
            background-color: yellow;  /* 32°C to 41°C (Extreme Caution) */
        }

        .forecast-bar.danger {
            background-color: orange;  /* 41°C to 54°C (Danger) */
        }

        .forecast-bar.extreme-danger {
            background-color: red;  /* Above 54°C (Extreme Danger) */
        } 

        .device-select-container {
            width: 90%;
            max-width: 400px;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .device-select-label {
            display: block;
            margin-bottom: 10px;
            font-weight: bold;
            color: #343a40;
        }

        #deviceSelect, 
        #compareDeviceSelect {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background-color: #fff;
            color: #333;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        #deviceSelect:focus,
        #compareDeviceSelect:focus {
            border-color: #800000; /* Maroon color on focus */
            outline: none;
        }

        /* Optional styles for better appearance */
        option {
            padding: 10px; /* Increase padding for options */
        }
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
                align-items: center;
                height: auto; /* Allow container height to adjust */
            }

            .card {
                width: 90%;
                margin: 10px 0;
            }
        }
    </style>
</head>
<body>
<div id="monitoring-section" class="data-container">
    <h2>Today's Monitoring</h2>
    <h4>7-Day Forecast</h4>
    <div id="forecast-container" class="forecast-container">
        <!-- Forecast items will be dynamically inserted here -->
    </div>
    <h4>Current Heat Index Data</h4>
    <div class="device-select-container">
        <label for="deviceSelect" class="device-select-label">Select Device</label>
        <select id="deviceSelect">
            <?php foreach ($devices as $device): ?>
                <option value="<?php echo htmlspecialchars($device['deviceId']); ?>"><?php echo htmlspecialchars($device['deviceName']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="data-row">
        <div class="data-box">
            <p class="data-title">Temperature:</p>
            <p id="currentTemperature" class="data-value">Loading...</p>
        </div>
        <div class="data-box">
            <p class="data-title">Humidity:</p>
            <p id="currentHumidity" class="data-value">Loading...</p>
        </div>
        <div class="data-box">
            <p class="data-title">Heat Index:</p>
            <p id="currentHeatIndex" class="data-value heat-index">Loading...</p>
        </div>
        <div class="data-box">
            <p class="data-title">Location:</p>
            <p id="currentLocation" class="data-value">Loading...</p>
        </div>
    </div>
    <p id="currentUpdateTime" class="small-text">Last updated: Loading...</p>
    <div class="button-group">
        <button id="compare-data">Compare</button>
    </div>
</div>

<div id="compare-section" class="data-container" style="display:none;">
    <h2>Compare Heat Index Data</h2>
    
    <div class="device-select-container">
        <label for="deviceSelect" class="device-select-label">Select Device to Compare</label>
        <select id="compareDeviceSelect">
            <?php foreach ($devices as $device): ?>
                <option value="<?php echo htmlspecialchars($device['deviceId']); ?>"><?php echo htmlspecialchars($device['deviceName']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <div class="compare-container">
        <!-- Left Side for Visual Crossing Data -->
        <div class="card compare-left">
            <div class="header">Current Heat Index Data from Visual Crossing</div>
            <div class="circle">
                <span id="visualHeatIndex" class="data-value heat-index">Loading...</span>
            </div>
            <div class="details">
                <p class="data-title">Temperature:</p>
                <p id="visualTemperature" class="data-value">Loading...</p>
                <p class="data-title">Humidity:</p>
                <p id="visualHumidity" class="data-value">Loading...</p>
                <p class="data-title">Location:</p>
                <p id="visualLocation" class="data-value small-text">Loading...</p>
                <p id="visualUpdateTime" class="small-text">Last updated: Loading...</p>
            </div>
        </div>
        
        <!-- Right Side for Monitoring Data -->
        <div class="card compare-right">
            <div class="header">Latest Heat Index Data from Monitoring</div>
            <div class="circle">
                <span id="monitoringHeatIndex" class="data-value heat-index">Loading...</span>
            </div>
            <div class="details">
                <p class="data-title">Temperature:</p>
                <p id="monitoringTemperature" class="data-value">Loading...</p>
                <p class="data-title">Humidity:</p>
                <p id="monitoringHumidity" class="data-value">Loading...</p>
                <p class="data-title">Location:</p>
                <p id="monitoringLocation" class="data-value small-text">Loading...</p>
                <p id="monitoringUpdateTime" class="small-text">Last updated: Loading...</p>
            </div>
        </div>
    </div>
    
    <button id="back-btn">Back</button>
</div>

<script>
    const ws = new WebSocket(`ws://192.168.247.185:8080`); // Update with your domain and WebSocket port

    ws.onmessage = function(event) {
        const data = JSON.parse(event.data);
        // Update the current data displayed
        document.getElementById('currentTemperature').innerText = data.temperature + "°C";
        document.getElementById('currentHumidity').innerText = data.humidity + "%";
        document.getElementById('currentHeatIndex').innerText = data.heat_index + "°C";
        document.getElementById('currentUpdateTime').innerText = `Last updated: ${new Date(data.timestamp).toLocaleString()}`;
        document.getElementById('currentLocation').innerText = `Location: Your Location`; // Update as needed
    };

    let pollingInterval;

    document.getElementById('deviceSelect').addEventListener('change', function() {
        const selectedDeviceId = this.value;

        if (selectedDeviceId) {
            // Stop any existing polling
            clearInterval(pollingInterval);
            
            // Fetch data for the selected device
            fetch(`fetch_data.php?deviceId=${selectedDeviceId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('currentTemperature').innerText = data.temperature + "°C";
                    document.getElementById('currentHumidity').innerText = data.humidity + "%";
                    document.getElementById('currentHeatIndex').innerText = data.heat_index + "°C";
                    document.getElementById('currentLocation').innerText = data.location;
                    document.getElementById('currentUpdateTime').innerText = `Last updated: ${new Date(data.timestamp).toLocaleString()}`;
                })
                .catch(err => console.error('Error fetching data:', err));

            // Start polling every 5 seconds (5000 milliseconds)
            pollingInterval = setInterval(() => {
                fetch(`fetch_data.php?deviceId=${selectedDeviceId}`)
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('currentTemperature').innerText = data.temperature + "°C";
                        document.getElementById('currentHumidity').innerText = data.humidity + "%";
                        document.getElementById('currentHeatIndex').innerText = data.heat_index + "°C";
                        document.getElementById('currentLocation').innerText = data.location;
                        document.getElementById('currentUpdateTime').innerText = `Last updated: ${new Date(data.timestamp).toLocaleString()}`;
                    })
                    .catch(err => console.error('Error fetching data:', err));
            }, 30000); // Fetch every 30 seconds

        } else {
            // Reset the displayed data if no device is selected
            document.getElementById('currentTemperature').innerText = "Loading...";
            document.getElementById('currentHumidity').innerText = "Loading...";
            document.getElementById('currentHeatIndex').innerText = "Loading...";
            document.getElementById('currentLocation').innerText = "Loading...";
            document.getElementById('currentUpdateTime').innerText = "Last updated: Loading...";
            
            // Clear polling interval
            clearInterval(pollingInterval);
        }
    });

    function displayForecastData(forecastData) {
        const forecastContainer = document.getElementById('forecast-container');
        forecastContainer.innerHTML = ''; // Clear previous forecast

        // Check if forecastData is not an array or is empty
        if (!Array.isArray(forecastData) || forecastData.length === 0) {
            const noDataMessage = document.createElement('div');
            noDataMessage.className = 'no-forecast-message';
            noDataMessage.innerText = 'No forecast data available';
            forecastContainer.appendChild(noDataMessage);
            return;
        }

        // Loop through the forecast data and display it
        forecastData.forEach(day => {
            const formattedDate = new Date(day.date).toLocaleDateString();
            const temp = parseFloat(day.temperature_forecast).toFixed(2);
            const humidity = parseFloat(day.humidity_forecast).toFixed(2);
            const heatIndex = parseFloat(day.heat_index_forecast).toFixed(2);

            let forecastClass = 'safe'; // Default
            if (heatIndex >= 54) {
                forecastClass = 'extreme-danger';
            } else if (heatIndex >= 41) {
                forecastClass = 'danger';
            } else if (heatIndex >= 32) {
                forecastClass = 'extreme-caution';
            } else if (heatIndex >= 27) {
                forecastClass = 'caution';
            }

            const forecastElement = document.createElement('div');
            forecastElement.className = 'forecast-day';

            forecastElement.innerHTML = `
                <div class="date">${formattedDate}</div>
                <div class="forecast-bar ${forecastClass}" title="Heat Index: ${heatIndex}°C"></div>
                <div><strong>Heat Index: ${heatIndex}°C</strong></div>
                <div>Temperature: ${temp}°C</div>
                <div>Humidity: ${humidity}%</div>
            `;

            forecastContainer.appendChild(forecastElement);
        });
    }

    // Fetch forecast data from PHP and display it when the page loads
    document.addEventListener("DOMContentLoaded", function() {
        const forecastData = <?php echo json_encode($latestForecastData); ?>; // Ensure this is the correct PHP variable
        displayForecastData(forecastData);
    });
    
    // Helper function to format the date into a more readable format
    function formatDate(dateString) {
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        return new Date(dateString).toLocaleDateString(undefined, options);
    }

    // Fetch Visual Crossing Data
    function fetchLatestVisualData() {
        const url = "https://weather.visualcrossing.com/VisualCrossingWebServices/rest/services/timeline/barangay%20630?unitGroup=metric&key=FEBB37WH6QUV6B8ZUDTWN8AWG&contentType=json";
        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                if (data) {
                    document.getElementById('visualTemperature').textContent = `${data.currentConditions.temp}°C`;
                    document.getElementById('visualHumidity').textContent = `${data.currentConditions.humidity}%`;
                    document.getElementById('visualHeatIndex').textContent = `${heatIndex(data.currentConditions.temp, data.currentConditions.humidity).toFixed(2)}°C`;
                    document.getElementById('visualLocation').textContent = data.resolvedAddress;
                    document.getElementById('visualUpdateTime').textContent = `Last updated: ${data.currentConditions.datetime}`;
                }
            })
            .catch(err => console.error('Error fetching visual data:', err));
    }

    // Fetch monitoring data based on selected device for comparison
    document.getElementById('compareDeviceSelect').addEventListener('change', function() {
        const selectedDeviceId = this.value;

        if (selectedDeviceId) {
            fetch(`fetch_data.php?deviceId=${selectedDeviceId}`)
                .then(response => response.json())
                .then(data => {
                    // Update the compare section data
                    document.getElementById('monitoringTemperature').innerText = data.temperature + "°C";
                    document.getElementById('monitoringHumidity').innerText = data.humidity + "%";
                    document.getElementById('monitoringHeatIndex').innerText = data.heat_index + "°C";
                    document.getElementById('monitoringLocation').innerText = data.location;
                    document.getElementById('monitoringUpdateTime').innerText = `Last updated: ${new Date(data.timestamp).toLocaleString()}`;
                })
                .catch(err => console.error('Error fetching monitoring data:', err));
        } else {
            // Reset the displayed data if no device is selected
            document.getElementById('monitoringTemperature').innerText = "Loading...";
            document.getElementById('monitoringHumidity').innerText = "Loading...";
            document.getElementById('monitoringHeatIndex').innerText = "Loading...";
            document.getElementById('monitoringLocation').innerText = "Loading...";
            document.getElementById('monitoringUpdateTime').innerText = "Last updated: Loading...";
        }
    });

    // Existing event listener for the compare button
    document.getElementById('compare-data').addEventListener('click', function() {
        document.getElementById('monitoring-section').style.display = 'none';
        document.getElementById('compare-section').style.display = 'block';
        fetchLatestVisualData(); // Fetch visual data when comparing
    });

    document.getElementById('back-btn').addEventListener('click', function() {
        document.getElementById('compare-section').style.display = 'none';
        document.getElementById('monitoring-section').style.display = 'block';
    });

    function heatIndex(T, R) {
        const c1 = -8.78469475556;
        const c2 = 1.61139411;
        const c3 = 2.33854883889;
        const c4 = -0.14611605;
        const c5 = -0.012308094;
        const c6 = -0.0164248277778;
        const c7 = 0.002211732;
        const c8 = 0.00072546;
        const c9 = -0.000003582;

        const HI = c1 + (c2 * T) + (c3 * R) + (c4 * T * R) + (c5 * Math.pow(T, 2)) + (c6 * Math.pow(R, 2)) +
                    (c7 * Math.pow(T, 2) * R) + (c8 * T * Math.pow(R, 2)) + (c9 * Math.pow(T, 2) * Math.pow(R, 2));
        return HI;
    }

    // Event listener for weekly data button (reused for displaying data)
    document.getElementById('weekly-data').addEventListener('click', function() {
        displayForecastData(forecastData);
    });

    // Event listener to return to the monitoring section
    document.getElementById('back-btn').addEventListener('click', function() {
        document.getElementById('compare-section').style.display = 'none';
        document.getElementById('monitoring-section').style.display = 'block';
    });
</script>
<?php include 'footer.php'; ?>
</body>
</html>
