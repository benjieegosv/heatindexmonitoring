<?php
// Include database connection and start session
include 'db_conn.php'; 
session_start();

// Check if user is logged in
if (!isset($_SESSION['accNum'])) {
    header("Location: login.php");
    exit();
}

// Retrieve user information from session
$user_id = $_SESSION['accNum'];

// Determine user type
$sql = "
    SELECT 'admin' AS user_type FROM admin_account WHERE accNum = ?
    UNION
    SELECT 'staff' AS user_type FROM staff_account WHERE accNum = ?
";
$stmt = $link->prepare($sql);

if ($stmt) {
    $stmt->bind_param("ii", $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_type = $result->num_rows > 0 ? $result->fetch_assoc()['user_type'] : null;
    $stmt->close();
} else {
    die("Prepare failed: " . $link->error);
}

// Include the appropriate header file based on user type
if ($user_type === 'admin') {
    include 'header.php';
} elseif ($user_type === 'staff') {
    include 'header_staff.php';
} else {
    echo "Error: User type not recognized.";
    exit();
}

$link->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weather Monitoring</title>
    <style>
        /* Add necessary styles here */
    </style>
</head>
<body>
    <div id="monitoring-section" class="data-container">
        <h2>Today's Monitoring</h2>
        <div id="forecast-container" class="forecast-container">
            <!-- Forecast items will be dynamically inserted here -->
        </div>
        <h3>Current Heat Index Data</h3>
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
                <p id="currentLocation" class="data-value small-text">Loading...</p>
            </div>
        </div>
        <p id="currentUpdateTime" class="small-text">Last updated: Loading...</p>
        <div class="button-group">
            <button id="weekly-data">Weekly Data</button>
            <button id="compare-data">Compare</button>
        </div>
    </div>
    
    <div id="compare-section" class="data-container" style="display:none;">
        <h3>Current Heat Index Data</h3>
        <p class="data-title">Temperature:</p>
        <p id="compareTemperature" class="data-value">Loading...</p>
        <p class="data-title">Humidity:</p>
        <p id="compareHumidity" class="data-value">Loading...</p>
        <p class="data-title heat-index">Heat Index:</p>
        <p id="compareHeatIndex" class="data-value heat-index">Loading...</p>
        <p id="compareUpdateTime" class="small-text">Last updated: Loading...</p>
        <p id="compareLocation" class="small-text">Location: Loading...</p>
        <button id="back-btn">Back</button>
    </div>

    <script>
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

        function formatDate(dateStr) {
            const date = new Date(dateStr);
            return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
        }

        function fetchWeatherData() {
            fetch("https://weather.visualcrossing.com/VisualCrossingWebServices/rest/services/timeline/barangay%20630?unitGroup=metric&include=current&key=FEBB37WH6QUV6B8ZUDTWN8AWG&contentType=json")
                .then(response => response.json())
                .then(data => {
                    const currentData = data.currentConditions;
                    if (currentData) {
                        const temperature = currentData.temp;
                        const humidity = currentData.humidity;
                        const updateTime = currentData.datetime;
                        const location = data.address;
                        const heatIndexValue = heatIndex(temperature, humidity);
                        document.getElementById('currentTemperature').innerText = temperature + "°C";
                        document.getElementById('currentHumidity').innerText = humidity + "%";
                        document.getElementById('currentHeatIndex').innerText = heatIndexValue.toFixed(2) + "°C";
                        document.getElementById('currentUpdateTime').innerText = `Last updated: ${formatDate(updateTime)}`;
                        document.getElementById('currentLocation').innerText = `Location: ${location}`;
                    } else {
                        document.getElementById('currentTemperature').innerText = "Data not available";
                        document.getElementById('currentHumidity').innerText = "Data not available";
                        document.getElementById('currentHeatIndex').innerText = "Data not available";
                        document.getElementById('currentUpdateTime').innerText = "Last updated: Data not available";
                        document.getElementById('currentLocation').innerText = "Location: Data not available";
                    }
                })
                .catch(err => console.error('Error fetching data:', err));
        }

        function fetchForecastData() {
            const url = "https://weather.visualcrossing.com/VisualCrossingWebServices/rest/services/timeline/barangay%20630?unitGroup=metric&key=FEBB37WH6QUV6B8ZUDTWN8AWG&contentType=json&include=forecast";
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    const forecastContainer = document.getElementById('forecast-container');
                    forecastContainer.innerHTML = '';

                    if (data && data.days) {
                        data.days.forEach(day => {
                            const date = new Date(day.datetime);
                            const formattedDate = formatDate(day.datetime);
                            const temp = day.temp;
                            const humidity = day.humidity;
                            const heatIndexValue = heatIndex(temp, humidity);
                            let forecastClass = 'danger';

                            if (heatIndexValue < 27) {
                                forecastClass = 'safe';
                            } else if (heatIndexValue < 32) {
                                forecastClass = 'caution';
                            } else if (heatIndexValue < 40) {
                                forecastClass = 'extreme-caution';
                            }

                            const forecastElement = document.createElement('div');
                            forecastElement.className = 'forecast-day';
                            forecastElement.innerHTML = `
                                <div class="date">${formattedDate}</div>
                                <div class="forecast-bar ${forecastClass}" title="Heat Index: ${heatIndexValue.toFixed(2)}°C"></div>
                                <div>${temp}°C</div>
                            `;
                            forecastContainer.appendChild(forecastElement);
                        });
                    } else {
                        forecastContainer.innerHTML = 'No forecast data available';
                    }
                })
                .catch(err => {
                    console.error('Error fetching forecast data:', err);
                    document.getElementById('forecast-container').innerHTML = 'Error fetching data.';
                });
        }

        document.getElementById('weekly-data').addEventListener('click', fetchForecastData);

        document.getElementById('compare-data').addEventListener('click', function() {
            document.getElementById('monitoring-section').style.display = 'none';
            document.getElementById('compare-section').style.display = 'block';
            fetchWeatherData(); 
        });

        document.getElementById('back-btn').addEventListener('click', function() {
            document.getElementById('compare-section').style.display = 'none';
            document.getElementById('monitoring-section').style.display = 'block';
        });

        // Initialize with the monitoring section displayed
        document.getElementById('monitoring-section').style.display = 'block';
        fetchWeatherData(); 
        fetchForecastData(); 
    </script>
</body>
</html>
