<?php
// Include database connection and start session
include 'db_conn.php';
require 'vendor/autoload.php'; // Include for PhpSpreadsheet and TCPDF
session_start();

// Check if user is logged in
if (!isset($_SESSION['accNum']) && !isset($_SESSION['adminAccNum'])) {
    header("Location: login.php");
    exit();
}

// Initialize userType variable
$userType = isset($_SESSION['adminAccNum']) ? 'admin' : (isset($_SESSION['accNum']) ? 'staff' : 'unknown');
if ($userType === 'unknown') {
    echo "Error: User type not recognized.";
    exit();
}

// Handling download requests
$isDownload = isset($_GET['download']);

// User type check and header inclusion only if not a download
if (!$isDownload) {
    // Include the appropriate header file based on user type
    if ($userType === 'admin') {
        include 'header.php';
    } elseif ($userType === 'staff') {
        include 'header_staff.php';
    }
}

// Determine the type of report (date range, yearly, or next 7 days)
$reportType = $_GET['report_type'] ?? 'date_range'; // Default is date_range

// For Date Range Reports
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-1 week'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Capture the startYear and endYear from the GET parameters
$startYear = $_GET['startYear'] ?? date('Y'); // Default to the current year if not provided
$endYear = $_GET['endYear'] ?? date('Y'); // Default to the current year if not provided

// For Monthly Reports
$selectedMonth = $_GET['month'] ?? date('m'); // Default to the current month
$selectedYear = $_GET['year'] ?? date('Y'); // Default to the current year

// Initialize arrays
$labels = [];
$temperatureData = [];
$humidityData = [];
$heatIndexData = [];
$monthlyData = [];
$years = [];
$minTemperature = [];
$maxTemperature = [];
$avgTemperature = [];
$minHumidity = [];
$maxHumidity = [];
$avgHumidity = [];
$minHeatIndex = [];
$maxHeatIndex = [];
$avgHeatIndex = [];
$forecastData = [];

// Check if it's a date range report
if ($reportType == 'date_range') {
    if (!DateTime::createFromFormat('Y-m-d', $start_date) || !DateTime::createFromFormat('Y-m-d', $end_date) || $start_date > $end_date) {
        exit();
    }

    // Fetch data for the summary report
    $summaryQuery = "
        SELECT 
            MAX(temperature) AS max_temperature, 
            MIN(temperature) AS min_temperature, 
            AVG(temperature) AS avg_temperature,
            MAX(humidity) AS max_humidity, 
            MIN(humidity) AS min_humidity, 
            AVG(humidity) AS avg_humidity,
            MAX(heatIndex) AS max_heat_index, 
            MIN(heatIndex) AS min_heat_index, 
            AVG(heatIndex) AS avg_heat_index
        FROM external_heat_index_data
        WHERE date BETWEEN ? AND ?";

    $stmt = $link->prepare($summaryQuery);
    $stmt->bind_param('ss', $start_date, $end_date);
    $stmt->execute();
    $summary_result = $stmt->get_result();
    $summary = $summary_result->fetch_assoc();

    // Fetch the date and value for the max heat index
    $maxHeatIndexQuery = "
        SELECT DATE_FORMAT(date, '%Y-%m-%d') AS max_heat_index_day, heatIndex AS max_heat_index_value
        FROM external_heat_index_data
        WHERE date BETWEEN ? AND ? 
        ORDER BY heatIndex DESC
        LIMIT 1";
    $stmt = $link->prepare($maxHeatIndexQuery);
    $stmt->bind_param('ss', $start_date, $end_date);
    $stmt->execute();
    $maxHeatIndexResult = $stmt->get_result();
    $maxHeatIndexData = $maxHeatIndexResult->fetch_assoc();
    $maxHeatIndexDay = $maxHeatIndexData['max_heat_index_day'] ?? 'N/A'; // Prevents error if no data
    $maxHeatIndexValue = $maxHeatIndexData['max_heat_index_value'] ?? 'N/A'; // Prevents error if no data

    // Fetch the date and value for the min heat index
    $minHeatIndexQuery = "
        SELECT DATE_FORMAT(date, '%Y-%m-%d') AS min_heat_index_day, heatIndex AS min_heat_index_value
        FROM external_heat_index_data
        WHERE date BETWEEN ? AND ? 
        ORDER BY heatIndex ASC
        LIMIT 1";
    $stmt = $link->prepare($minHeatIndexQuery);
    $stmt->bind_param('ss', $start_date, $end_date);
    $stmt->execute();
    $minHeatIndexResult = $stmt->get_result();
    $minHeatIndexData = $minHeatIndexResult->fetch_assoc();
    $minHeatIndexDay = $minHeatIndexData['min_heat_index_day'] ?? 'N/A'; // Prevents error if no data
    $minHeatIndexValue = $minHeatIndexData['min_heat_index_value'] ?? 'N/A'; // Prevents error if no data

    // Fetch individual records for the detailed table
    $query = "
        SELECT location, temperature, humidity, heatIndex, DATE_FORMAT(date, '%Y-%m-%d') as formatted_date
        FROM external_heat_index_data
        WHERE date BETWEEN ? AND ?
        ORDER BY date ASC";
    $stmt = $link->prepare($query);
    $stmt->bind_param('ss', $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $labels[] = $row['formatted_date'];
        $temperatureData[] = $row['temperature'];
        $humidityData[] = $row['humidity'];
        $heatIndexData[] = $row['heatIndex'];
    }
}

// For Monthly Reports
if ($reportType == 'monthly') {
    // Validate the selected month and year
    if (!is_numeric($selectedMonth) || !is_numeric($selectedYear) || $selectedMonth < 1 || $selectedMonth > 12) {
        exit();
    }

    // Fetch summary data for the selected month and year
    $summaryQuery = "
        SELECT 
            MAX(temperature) AS max_temperature, 
            MIN(temperature) AS min_temperature, 
            AVG(temperature) AS avg_temperature,
            MAX(humidity) AS max_humidity, 
            MIN(humidity) AS min_humidity, 
            AVG(humidity) AS avg_humidity,
            MAX(heatIndex) AS max_heat_index, 
            MIN(heatIndex) AS min_heat_index, 
            AVG(heatIndex) AS avg_heat_index
        FROM external_heat_index_data
        WHERE MONTH(date) = ? AND YEAR(date) = ?";

    $stmt = $link->prepare($summaryQuery);
    $stmt->bind_param('ii', $selectedMonth, $selectedYear);
    $stmt->execute();
    $summary_result = $stmt->get_result();
    $summary = $summary_result->fetch_assoc();

    // Fetch the date and value for the max heat index
    $maxHeatIndexQuery = "
        SELECT DATE_FORMAT(date, '%Y-%m-%d') AS max_heat_index_day, heatIndex AS max_heat_index_value
        FROM external_heat_index_data
        WHERE MONTH(date) = ? AND YEAR(date) = ? 
        ORDER BY heatIndex DESC
        LIMIT 1";
    $stmt = $link->prepare($maxHeatIndexQuery);
    $stmt->bind_param('ii', $selectedMonth, $selectedYear);
    $stmt->execute();
    $maxHeatIndexResult = $stmt->get_result();
    $maxHeatIndexData = $maxHeatIndexResult->fetch_assoc();
    $maxHeatIndexDay = $maxHeatIndexData['max_heat_index_day'] ?? 'N/A'; // Prevents error if no data
    $maxHeatIndexValue = $maxHeatIndexData['max_heat_index_value'] ?? 'N/A'; // Prevents error if no data

    // Fetch the date and value for the min heat index
    $minHeatIndexQuery = "
        SELECT DATE_FORMAT(date, '%Y-%m-%d') AS min_heat_index_day, heatIndex AS min_heat_index_value
        FROM external_heat_index_data
        WHERE MONTH(date) = ? AND YEAR(date) = ?
        ORDER BY heatIndex ASC
        LIMIT 1";
    $stmt = $link->prepare($minHeatIndexQuery);
    $stmt->bind_param('ii', $selectedMonth, $selectedYear);
    $stmt->execute();
    $minHeatIndexResult = $stmt->get_result();
    $minHeatIndexData = $minHeatIndexResult->fetch_assoc();
    $minHeatIndexDay = $minHeatIndexData['min_heat_index_day'] ?? 'N/A'; // Prevents error if no data
    $minHeatIndexValue = $minHeatIndexData['min_heat_index_value'] ?? 'N/A'; // Prevents error if no data
    
    // Fetch data for the selected month and year
    $query = "
        SELECT 
            DAY(date) AS day, 
            temperature, 
            humidity, 
            heatIndex 
        FROM external_heat_index_data 
        WHERE MONTH(date) = ? AND YEAR(date) = ?
        ORDER BY date ASC";

    $stmt = $link->prepare($query);
    $stmt->bind_param('ii', $selectedMonth, $selectedYear);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $monthlyData[] = [
            'day' => $row['day'],
            'temperature' => $row['temperature'],
            'humidity' => $row['humidity'],
            'heatIndex' => $row['heatIndex'],
        ];
    }
}

// For Yearly Reports
if ($reportType == 'yearly') {
    // Validate that both startYear and endYear are numeric and within a valid range
    if (!is_numeric($startYear) || strlen($startYear) !== 4 || !is_numeric($endYear) || strlen($endYear) !== 4 || $startYear > $endYear) {
        exit();
    }

    // Fetch data for the yearly report
    $query = "
        SELECT 
            YEAR(date) AS year, 
            MIN(temperature) AS min_temperature, 
            MAX(temperature) AS max_temperature,
            MIN(humidity) AS min_humidity,
            MAX(humidity) AS max_humidity,
            MIN(heatIndex) AS min_heat_index,
            MAX(heatIndex) AS max_heat_index,
            AVG(temperature) AS avg_temperature,
            AVG(humidity) AS avg_humidity,
            AVG(heatIndex) AS avg_heat_index
        FROM external_heat_index_data
        WHERE YEAR(date) BETWEEN ? AND ?
        GROUP BY YEAR(date)
        ORDER BY YEAR(date)";

    $stmt = $link->prepare($query);
    $stmt->bind_param('ii', $startYear, $endYear);
    $stmt->execute();
    $yearly_result = $stmt->get_result();

    // Fetch the maximum heat index value within the year range
    $maxHeatIndexQuery = "
    SELECT 
        YEAR(date) AS max_heat_index_year, 
        MAX(heatIndex) AS max_heat_index_value
    FROM external_heat_index_data
    WHERE YEAR(date) BETWEEN ? AND ?
    GROUP BY max_heat_index_year
    ORDER BY max_heat_index_value DESC
    LIMIT 1";

    $stmt = $link->prepare($maxHeatIndexQuery);
    $stmt->bind_param('ii', $startYear, $endYear); // Bind startYear and endYear
    $stmt->execute();
    $maxHeatIndexResult = $stmt->get_result();
    $maxHeatIndexData = $maxHeatIndexResult->fetch_assoc();
    $maxHeatIndexYear = $maxHeatIndexData['max_heat_index_year'] ?? 'N/A'; // Prevents error if no data
    $maxHeatIndexValue = $maxHeatIndexData['max_heat_index_value'] ?? 'N/A'; // Prevents error if no data

    // Fetch the minimum heat index value within the year range
    $minHeatIndexQuery = "
    SELECT 
        YEAR(date) AS min_heat_index_year, 
        MIN(heatIndex) AS min_heat_index_value
    FROM external_heat_index_data
    WHERE YEAR(date) BETWEEN ? AND ?
    GROUP BY min_heat_index_year
    ORDER BY min_heat_index_value ASC
    LIMIT 1";

    $stmt = $link->prepare($minHeatIndexQuery);
    $stmt->bind_param('ii', $startYear, $endYear); // Bind startYear and endYear
    $stmt->execute();
    $minHeatIndexResult = $stmt->get_result();
    $minHeatIndexData = $minHeatIndexResult->fetch_assoc();
    $minHeatIndexYear = $minHeatIndexData['min_heat_index_year'] ?? 'N/A'; // Prevents error if no data
    $minHeatIndexValue = $minHeatIndexData['min_heat_index_value'] ?? 'N/A'; // Prevents error if no data

    // Populate arrays for chart data
    while ($row = $yearly_result->fetch_assoc()) {
        $years[] = $row['year'];
        $minTemperature[] = $row['min_temperature'];
        $maxTemperature[] = $row['max_temperature'];
        $avgTemperature[] = $row['avg_temperature'];
        $minHumidity[] = $row['min_humidity'];
        $maxHumidity[] = $row['max_humidity'];
        $avgHumidity[] = $row['avg_humidity'];
        $minHeatIndex[] = $row['min_heat_index'];
        $maxHeatIndex[] = $row['max_heat_index'];
        $avgHeatIndex[] = $row['avg_heat_index'];
    }
}

// Next 7 Days Forecast Report Section
if ($reportType == 'next_7_days') {
    // Fetch forecast data from forecast_data table
    $query = "
        SELECT location, temperature_forecast, humidity_forecast, heat_index_forecast, date 
        FROM forecast_data 
        WHERE DATE(date) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) 
        ORDER BY date ASC";
    
    $stmt = $link->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();

    // Initialize summary variables
    $summary = [
        'avg_temperature' => 0,
        'max_temperature' => PHP_FLOAT_MIN,
        'min_temperature' => PHP_FLOAT_MAX,
        'avg_humidity' => 0,
        'max_humidity' => PHP_FLOAT_MIN,
        'min_humidity' => PHP_FLOAT_MAX,
        'max_heat_index' => PHP_FLOAT_MIN,
        'min_heat_index' => PHP_FLOAT_MAX,
        'avg_heat_index' => 0,
    ];

    // Initialize array for forecast data
    $forecastData = [];

    // Collect forecast data and calculate summary values
    while ($row = $result->fetch_assoc()) {
        $forecastData[] = $row;

        // Update summary statistics
        $temperature = $row['temperature_forecast'];
        $humidity = $row['humidity_forecast'];
        $heatIndex = $row['heat_index_forecast'];

        // Accumulate for averages
        $summary['avg_temperature'] += $temperature;
        $summary['avg_humidity'] += $humidity;
        $summary['avg_heat_index'] += $heatIndex;

        // Max/Min comparisons
        if ($summary['max_temperature'] < $temperature) {
            $summary['max_temperature'] = $temperature;
        }
        if ($summary['min_temperature'] > $temperature) {
            $summary['min_temperature'] = $temperature;
        }
        if ($summary['max_humidity'] < $humidity) {
            $summary['max_humidity'] = $humidity;
        }
        if ($summary['min_humidity'] > $humidity) {
            $summary['min_humidity'] = $humidity;
        }
        if ($summary['max_heat_index'] < $heatIndex) {
            $summary['max_heat_index'] = $heatIndex;
        }
        if ($summary['min_heat_index'] > $heatIndex) {
            $summary['min_heat_index'] = $heatIndex;
        }
    }

    // Calculate averages if data is present
    $forecastCount = count($forecastData);
    if ($forecastCount > 0) {
        $summary['avg_temperature'] /= $forecastCount;
        $summary['avg_humidity'] /= $forecastCount;
        $summary['avg_heat_index'] /= $forecastCount;

        // Initialize variables for max and min heat index dates
        $maxHeatIndexDay = '';
        $minHeatIndexDay = '';

        // Find the days for the max and min heat index from the forecasted data
        foreach ($forecastData as $data) {
            if ($data['heat_index_forecast'] == $summary['max_heat_index']) {
                $maxHeatIndexDay = $data['date'];
            }
            if ($data['heat_index_forecast'] == $summary['min_heat_index']) {
                $minHeatIndexDay = $data['date'];
            }
        }
        
        // Format max and min heat index to two decimal points
        $maxHeatIndexValue = number_format($summary['max_heat_index'], 2);
        $minHeatIndexValue = number_format($summary['min_heat_index'], 2);
    } else {
        // If no forecast data is available
        $maxHeatIndexValue = 'N/A';
        $minHeatIndexValue = 'N/A';
        $maxHeatIndexDay = 'N/A';
        $minHeatIndexDay = 'N/A';
    }
}

// If user requested a download, handle it
if ($isDownload) {
    // Logic for downloading reports
    if ($reportType == 'date_range') {
        include 'generate_date_range_report.php'; // This handles date range report downloads
    } elseif ($reportType == 'monthly') {
        include 'generate_monthly_report.php'; // This handles yearly report downloads
    } elseif ($reportType == 'yearly') {
        include 'generate_yearly_report.php'; // This handles yearly report downloads
    } elseif ($reportType == 'next_7_days') {
        include 'generate_forecast_report.php'; // This handles next 7 days report downloads
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Report</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="report_charts.js"></script> <!-- Include the separated chart script -->
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
        
        h2 {
            font-weight: bold;
            color: #3C000B;
            text-align: center;
            text-transform: uppercase;
            padding: 15px 0;
            margin-bottom: 30px;
            letter-spacing: 1px;
        }

        h4 {
            color: #808080;
            text-align: center;
        }
        
        /* General container styling */
        .container {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto 30px auto; /* Added margin-bottom for spacing between containers */
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
        }
        
        /* Report container - scrollable when needed */
        .report-container {
            width: 100%;
            margin: 20px 0 30px 0; /* Added margin-bottom for spacing */
            background-color: #fff;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            max-height: 600px; /* Increased height to accommodate charts */
            overflow-y: auto; /* Make this scrollable if content exceeds height */
        }

        /* Form styling */
        form {
            display: flex;
            flex-direction: column;
            gap: 15px; /* Adds space between form elements */
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
        }

        label, select, input {
            font-size: 16px;
            color: #333;
            width: 100%;
        }

        /* Style for form labels */
        label {
            font-weight: bold;
            margin-bottom: 5px;
        }

        /* Styling dropdown (select) and inputs */
        select, input {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            background-color: #fff;
            color: #333;
            transition: border-color 0.3s ease;
        }

        select:focus, input:focus {
            border-color: #800000; /* Maroon color on focus */
            outline: none;
        }

        /* Button styling */
        button {
            padding: 12px 20px;
            background-color: #800000; /* Maroon button */
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #660000; /* Darker maroon on hover */
        }

        /* Table styling */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: center;
            font-size: 14px;
        }

        th {
            background-color: #800000; /* Maroon header background */
            color: #fff; /* White text */
            font-weight: bold;
        }

        /* Download button container */
        .download-container {
            display: flex;
            justify-content: center;
            gap: 15px;
            padding: 15px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            width: 100%;
            max-width: 1200px;
            margin: 0 auto 30px auto; /* Added margin-bottom for spacing */
        }

        .download-container a {
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
            color: #fff;
            background-color: #800000; /* Maroon background for download buttons */
            padding: 10px 20px;
            border-radius: 4px;
            transition: background-color 0.3s ease;
        }

        .download-container a:hover {
            background-color: #660000; /* Darker maroon on hover */
        }

        /* Media query for responsiveness */
        @media (max-width: 768px) {
            .container {
                width: 95%;
                padding: 10px;
            }

            .report-container {
                max-height: none; /* Remove max-height on smaller screens */
            }

            .download-container {
                flex-direction: column;
                gap: 10px;
            }
        }

        /* Chart container styling */
        .chart-container {
            width: 100%;
            margin-top: 30px;
        }

        .chart-container canvas {
            width: 100% !important;
            height: auto !important;
            margin-bottom: 30px;
        }

        .back-link {
            display: inline-block;
            padding: 10px 20px;
            margin-bottom: 20px;
            background-color: #800000;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .back-link:hover {
            background-color: #0056b3;
        }

        .heat-index-day-container,
        .heat-index-month-container,
        .heat-index-year-container,
        .heat-index-forecast-container {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            margin-top: 20px;
        }

        /* Box styling for max and min heat index */
        .heat-index-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 45%; /* Take up equal space */
            background-color: #f9f9f9;
        }

        /* Styling for max heat index */
        .max-heat-index {
            border-left: 6px solid red;
        }

        /* Styling for min heat index */
        .min-heat-index {
            border-left: 6px solid green;
        }

        /* Label styling */
        .label {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        /* Value styling */
        .value {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }

        /* Date styling */
        .date {
            font-size: 16px;
            color: #666;
            margin-top: 5px;
        }

        /* Max heat index specific value color */
        .max-heat-index .value {
            color: red;
        }

        /* Min heat index specific value color */
        .min-heat-index .value {
            color: green;
        }

    </style>
</head>
<body>
<a href="reports.php" class="back-link">Back</a>
<h2>Monitoring Data Report</h2>
<div class="container">
    <form action="" method="get">
        <label for="report_type">Report Type:</label>
        <select id="report_type" name="report_type" onchange="this.form.submit()">
            <option value="date_range" <?= $reportType == 'date_range' ? 'selected' : '' ?>>Date Range</option>
            <option value="monthly" <?= $reportType == 'monthly' ? 'selected' : '' ?>>Monthly</option>
            <option value="yearly" <?= $reportType == 'yearly' ? 'selected' : '' ?>>Yearly</option>
            <option value="next_7_days" <?= $reportType == 'next_7_days' ? 'selected' : '' ?>>Next 7 Days</option>
        </select>
    </form>
</div>

<!-- Date Range Report Section -->
<?php if ($reportType == 'date_range'): ?>
    <div class="container">
    <h4>Date Range-based Report</h4>
    <form action="" method="get">
        <input type="hidden" name="report_type" value="date_range">
        <label for="start_date">Start Date:</label>
        <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" required>
        <label for="end_date">End Date:</label>
        <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" required>
        <button type="submit">Generate Report</button>
    </form>

    <!-- Main Report Table -->
    <div class="report-container">
        <!-- Summary Table -->
        <div class="summary-container">
            <h4>Summary of Report</h4>
            <table class="summary-table">
                <thead>
                    <tr>
                        <th>Metric</th>
                        <th>Max</th>
                        <th>Average</th>
                        <th>Min</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Temperature (°C)</td>
                        <td><?= $summary['max_temperature'] ?></td>
                        <td><?= number_format($summary['avg_temperature'],2) ?></td>
                        <td><?= $summary['min_temperature'] ?></td>
                    </tr>
                    <tr>
                        <td>Humidity (%)</td>
                        <td><?= $summary['max_humidity'] ?></td>
                        <td><?= number_format($summary['avg_humidity'],2) ?></td>
                        <td><?= $summary['min_humidity'] ?></td>
                    </tr>
                    <tr>
                        <td>Heat Index (°C)</td>
                        <td><?= $summary['max_heat_index'] ?></td>
                        <td><?= number_format($summary['avg_heat_index'],2) ?></td>
                        <td><?= $summary['min_heat_index'] ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <?php if ($result->num_rows > 0): ?>
        <h4>Observations</h4>
        <table>
            <thead>
            <tr>
                <th>Location</th>
                <th>Temperature (°C)</th>
                <th>Humidity (%)</th>
                <th>Heat Index</th>
                <th>Date</th>
            </tr>
            </thead>
            <tbody>
            <?php
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['location']) ?></td>
                    <td><?= htmlspecialchars($row['temperature']) ?></td>
                    <td><?= htmlspecialchars($row['humidity']) ?></td>
                    <td><?= htmlspecialchars($row['heatIndex']) ?></td>
                    <td><?= htmlspecialchars($row['formatted_date']) ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Max and Min Heat Index Day Containers -->
    <div class="heat-index-day-container">
        <div class="heat-index-box max-heat-index">
            <span class="label">Max Heat Index:</span>
            <span class="value"><?= htmlspecialchars($maxHeatIndexValue) ?>°C</span>
            <span class="date">on <?= htmlspecialchars($maxHeatIndexDay) ?></span>
        </div>
        <div class="heat-index-box min-heat-index">
            <span class="label">Min Heat Index:</span>
            <span class="value"><?= htmlspecialchars($minHeatIndexValue) ?>°C</span>
            <span class="date">on <?= htmlspecialchars($minHeatIndexDay) ?></span>
        </div>
    </div>

        <div class="chart-container">
            <canvas id="reportChart"></canvas>
        </div>

        <div class="download-container">
            <a href="?download=csv&start_date=<?= htmlspecialchars($start_date) ?>&end_date=<?= htmlspecialchars($end_date) ?>&report_type=date_range">Download as CSV</a>
            <a href="?download=excel&start_date=<?= htmlspecialchars($start_date) ?>&end_date=<?= htmlspecialchars($end_date) ?>&report_type=date_range">Download as Excel</a>
            <a href="?download=pdf&start_date=<?= htmlspecialchars($start_date) ?>&end_date=<?= htmlspecialchars($end_date) ?>&report_type=date_range">Download as PDF</a>
        </div>
        <?php elseif (isset($_GET['start_date'])): ?>
            <p>No data available for the selected date range.</p>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Monthly Report Section -->
<?php if ($reportType == 'monthly'): ?>
<div class="container">
    <h4>Monthly Report for <?= htmlspecialchars($selectedMonth) ?>/<?= htmlspecialchars($selectedYear) ?></h4>
    <form action="" method="get">
        <input type="hidden" name="report_type" value="monthly">
        <label for="month">Select Month:</label>
        <select id="month" name="month">
            <?php for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?= $m ?>" <?= $m == $selectedMonth ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $m, 1)) ?></option>
            <?php endfor; ?>
        </select>
        <label for="year">Select Year:</label>
        <input type="number" id="year" name="year" value="<?= htmlspecialchars($selectedYear) ?>" required min="1900" max="2100">
        <button type="submit">Generate Monthly Report</button>
    </form>

    <div class="report-container">
        <?php if (!empty($monthlyData)): ?>
            <!-- Summary Table -->
            <div class="summary-container">
                <h4>Summary of Report</h4>
                <table class="summary-table">
                    <thead>
                        <tr>
                            <th>Metric</th>
                            <th>Max</th>
                            <th>Average</th>
                            <th>Min</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Temperature (°C)</td>
                            <td><?= isset($summary['max_temperature']) ? number_format($summary['max_temperature'], 2) : 'N/A' ?></td>
                            <td><?= isset($summary['avg_temperature']) ? number_format($summary['avg_temperature'], 2) : 'N/A' ?></td>
                            <td><?= isset($summary['min_temperature']) ? number_format($summary['min_temperature'], 2) : 'N/A' ?></td>
                        </tr>
                        <tr>
                            <td>Humidity (%)</td>
                            <td><?= isset($summary['max_humidity']) ? number_format($summary['max_humidity'], 2) : 'N/A' ?></td>
                            <td><?= isset($summary['avg_humidity']) ? number_format($summary['avg_humidity'], 2) : 'N/A' ?></td>
                            <td><?= isset($summary['min_humidity']) ? number_format($summary['min_humidity'], 2) : 'N/A' ?></td>
                        </tr>
                        <tr>
                            <td>Heat Index (°C)</td>
                            <td><?= isset($summary['max_heat_index']) ? number_format($summary['max_heat_index'], 2) : 'N/A' ?></td>
                            <td><?= isset($summary['avg_heat_index']) ? number_format($summary['avg_heat_index'], 2) : 'N/A' ?></td>
                            <td><?= isset($summary['min_heat_index']) ? number_format($summary['min_heat_index'], 2) : 'N/A' ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Day</th>
                        <th>Temperature (°C)</th>
                        <th>Humidity (%)</th>
                        <th>Heat Index (°C)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($monthlyData as $data): ?>
                        <tr>
                            <td><?= htmlspecialchars($data['day']) ?></td>
                            <td><?= htmlspecialchars(number_format($data['temperature'], 2)) ?></td>
                            <td><?= htmlspecialchars(number_format($data['humidity'], 2)) ?></td>
                            <td><?= htmlspecialchars(number_format($data['heatIndex'], 2)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Max and Min Heat Index Day Containers -->
            <div class="heat-index-month-container">
                <div class="heat-index-box max-heat-index">
                    <span class="label">Max Heat Index:</span>
                    <span class="value"><?= htmlspecialchars($maxHeatIndexValue) ?>°C</span>
                    <span class="date">on <?= htmlspecialchars($maxHeatIndexDay) ?></span>
                </div>
                <div class="heat-index-box min-heat-index">
                    <span class="label">Min Heat Index:</span>
                    <span class="value"><?= htmlspecialchars($minHeatIndexValue) ?>°C</span>
                    <span class="date">on <?= htmlspecialchars($minHeatIndexDay) ?></span>
                </div>
            </div>

            <div class="chart-container">
                <canvas id="monthlyChart"></canvas>
            </div>

            <div class="download-container">
                <a href="generate_monthly_report.php?month=<?= htmlspecialchars($selectedMonth) ?>&year=<?= htmlspecialchars($selectedYear) ?>&download=csv">Download as CSV</a>
                <a href="generate_monthly_report.php?month=<?= htmlspecialchars($selectedMonth) ?>&year=<?= htmlspecialchars($selectedYear) ?>&download=excel">Download as Excel</a>
                <a href="generate_monthly_report.php?month=<?= htmlspecialchars($selectedMonth) ?>&year=<?= htmlspecialchars($selectedYear) ?>&download=pdf">Download as PDF</a>
            </div>
        <?php else: ?>
            <p>No data available for the selected month <?= htmlspecialchars($selectedMonth) ?> of the year <?= htmlspecialchars($selectedYear) ?>.</p>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Yearly Report Section -->
<?php if ($reportType == 'yearly'): ?>
    <div class="container">
        <h4>Yearly Report</h4>
        <div class="report-container">
            <form action="" method="get">
                <input type="hidden" name="report_type" value="yearly">
                <label for="startYear">Start Year:</label>
                <input type="number" id="startYear" name="startYear" value="<?= htmlspecialchars($startYear ?? '') ?>" required min="1900" max="2100">  
                <label for="endYear">End Year:</label>
                <input type="number" id="endYear" name="endYear" value="<?= htmlspecialchars($endYear ?? '') ?>" required min="1900" max="2100"> 
                <button type="submit">Generate Yearly Report</button>
            </form>
        </div>

        <?php if (isset($years) && count($years) > 0): ?>
        <table>
            <thead>
            <tr>
                <th>Year</th>
                <th>Min Temperature (°C)</th>
                <th>Max Temperature (°C)</th>
                <th>Min Humidity (%)</th>
                <th>Max Humidity (%)</th>
                <th>Min Heat Index (°C)</th>
                <th>Max Heat Index (°C)</th>
                <th>Average Temperature (°C)</th>
                <th>Average Humidity (%)</th>
                <th>Average Heat Index (°C)</th>
            </tr>
            </thead>
            <tbody>
            <?php
            // Populate the data for the table
            for ($i = 0; $i < count($years); $i++): ?>
                <tr>
                    <td><?= htmlspecialchars($years[$i]) ?></td>
                    <td><?= htmlspecialchars(number_format($minTemperature[$i], 2)) ?></td>
                    <td><?= htmlspecialchars(number_format($maxTemperature[$i], 2)) ?></td>
                    <td><?= htmlspecialchars(number_format($minHumidity[$i], 2)) ?></td>
                    <td><?= htmlspecialchars(number_format($maxHumidity[$i], 2)) ?></td>
                    <td><?= htmlspecialchars(number_format($minHeatIndex[$i], 2)) ?></td>
                    <td><?= htmlspecialchars(number_format($maxHeatIndex[$i], 2)) ?></td>
                    <td><?= htmlspecialchars(number_format($avgTemperature[$i], 2)) ?></td>
                    <td><?= htmlspecialchars(number_format($avgHumidity[$i], 2)) ?></td>
                    <td><?= htmlspecialchars(number_format($avgHeatIndex[$i], 2)) ?></td>
                </tr>
            <?php endfor; ?>
            </tbody>
        </table>

        <!-- Max and Min Heat Index Day Containers -->
        <div class="heat-index-year-container">
            <div class="heat-index-box max-heat-index">
                <span class="label">Max Heat Index:</span>
                <span class="value"><?= htmlspecialchars($maxHeatIndexValue) ?>°C</span>
                <span class="date">on <?= htmlspecialchars($maxHeatIndexYear) ?></span>
            </div>
            <div class="heat-index-box min-heat-index">
                <span class="label">Min Heat Index:</span>
                <span class="value"><?= htmlspecialchars($minHeatIndexValue) ?>°C</span>
                <span class="date">on <?= htmlspecialchars($minHeatIndexYear) ?></span>
            </div>
        </div>

        <div class="chart-container">
            <canvas id="temperatureChart"></canvas>
            <canvas id="humidityChart"></canvas>
            <canvas id="heatIndexChart"></canvas>
        </div>


        <div class="download-container">
            <a href="?download=csv&startYear=<?= htmlspecialchars($startYear) ?>&endYear=<?= htmlspecialchars($endYear) ?>&report_type=yearly">Download as CSV</a>
            <a href="?download=excel&startYear=<?= htmlspecialchars($startYear) ?>&endYear=<?= htmlspecialchars($endYear) ?>&report_type=yearly">Download as Excel</a>
            <a href="?download=pdf&startYear=<?= htmlspecialchars($startYear) ?>&endYear=<?= htmlspecialchars($endYear) ?>&report_type=yearly">Download as PDF</a>
        </div>

        <?php else: ?>
            <p>No data available for the selected year range.</p>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>


<!-- Next 7 Days Forecast Report Section -->
<?php if ($reportType == 'next_7_days'): ?>
<div class="container">
    <h4>7-Day Forecast Report</h4>
    <div class="report-container">
        <?php if (count($forecastData) > 0): ?>
            <!-- Summary of Forecast Data -->
            <h4>Summary of Next 7 Days Forecast</h4>
            <table class="summary-table">
                <thead>
                    <tr>
                        <th>Metric</th>
                        <th>Max</th>
                        <th>Average</th>
                        <th>Min</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Temperature (°C)</td>
                        <td><?= htmlspecialchars(number_format($summary['max_temperature'], 2)) ?></td>
                        <td><?= htmlspecialchars(number_format($summary['avg_temperature'], 2)) ?></td>
                        <td><?= htmlspecialchars(number_format($summary['min_temperature'], 2)) ?></td>
                    </tr>
                    <tr>
                        <td>Humidity (%)</td>
                        <td><?= htmlspecialchars(number_format($summary['max_humidity'], 2)) ?></td>
                        <td><?= htmlspecialchars(number_format($summary['avg_humidity'], 2)) ?></td>
                        <td><?= htmlspecialchars(number_format($summary['min_humidity'], 2)) ?></td>
                    </tr>
                    <tr>
                        <td>Heat Index (°C)</td>
                        <td><?= htmlspecialchars(number_format($summary['max_heat_index'], 2)) ?></td>
                        <td><?= htmlspecialchars(number_format($summary['avg_heat_index'], 2)) ?></td>
                        <td><?= htmlspecialchars(number_format($summary['min_heat_index'], 2)) ?></td>
                    </tr>
                </tbody>
            </table>

            <!-- Observations Table -->
            <h4>Forecast Observations</h4>
            <table>
                <thead>
                    <tr>
                        <th>Location</th>
                        <th>Temperature (°C)</th>
                        <th>Humidity (%)</th>
                        <th>Heat Index (°C)</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($forecastData as $data): ?>
                        <tr>
                            <td><?= htmlspecialchars($data['location']) ?></td>
                            <td><?= htmlspecialchars(number_format($data['temperature_forecast'], 2)) ?></td>
                            <td><?= htmlspecialchars(number_format($data['humidity_forecast'], 2)) ?></td>
                            <td><?= htmlspecialchars(number_format($data['heat_index_forecast'], 2)) ?></td>
                            <td><?= htmlspecialchars($data['date']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Max and Min Heat Index Day Containers -->
            <div class="heat-index-forecast-container">
                <div class="heat-index-box max-heat-index">
                    <span class="label">Max Heat Index:</span>
                    <span class="value"><?= htmlspecialchars($maxHeatIndexValue) ?>°C</span>
                    <span class="date">on <?= htmlspecialchars($maxHeatIndexDay) ?></span>
                </div>
                <div class="heat-index-box min-heat-index">
                    <span class="label">Min Heat Index:</span>
                    <span class="value"><?= htmlspecialchars($minHeatIndexValue) ?>°C</span>
                    <span class="date">on <?= htmlspecialchars($minHeatIndexDay) ?></span>
                </div>
            </div>

            <div class="chart-container">
                <canvas id="forecastChart"></canvas>
            </div>

            <!-- Display the saved plot image -->
            <div class="plot-container">
                <h4>Regression Analysis</h4>
                <img src="Images/heat_index_plot.png" alt="Temperature vs Heat Index Plot" style="max-width: 100%; height: auto;">
            </div>

            <div class="download-container">
                <a href="?download=csv&report_type=next_7_days">Download as CSV</a>
                <a href="?download=excel&report_type=next_7_days">Download as Excel</a>
                <a href="?download=pdf&report_type=next_7_days">Download as PDF</a>
            </div>

        <?php else: ?>
            <p>No forecast data available.</p>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>


<script>
    const reportType = '<?= $reportType ?>';

    // Data for Date Range Chart
    const dateRangeLabels = <?= json_encode($labels) ?>;
    const dateRangeTemperatureData = <?= json_encode($temperatureData) ?>;
    const dateRangeHumidityData = <?= json_encode($humidityData) ?>;
    const dateRangeHeatIndexData = <?= json_encode($heatIndexData) ?>;

    // Data for Monthly Chart
    const monthlyLabels = <?= json_encode(array_column($monthlyData, 'day')) ?>;
    const monthlyTemperatureData = <?= json_encode(array_column($monthlyData, 'temperature')) ?>;
    const monthlyHumidityData = <?= json_encode(array_column($monthlyData, 'humidity')) ?>;
    const monthlyHeatIndexData = <?= json_encode(array_column($monthlyData, 'heatIndex')) ?>;

    // Data for Yearly Chart
    const yearlyLabels = <?= json_encode($years) ?>;
    const yearlyMinTemperature = <?= json_encode($minTemperature) ?>;
    const yearlyMaxTemperature = <?= json_encode($maxTemperature) ?>;
    const yearlyAvgTemperature = <?= json_encode($avgTemperature) ?>;
    const yearlyMinHumidity = <?= json_encode($minHumidity) ?>;
    const yearlyMaxHumidity = <?= json_encode($maxHumidity) ?>;
    const yearlyAvgHumidity = <?= json_encode($avgHumidity) ?>;
    const yearlyMinHeatIndex = <?= json_encode($minHeatIndex) ?>;
    const yearlyMaxHeatIndex = <?= json_encode($maxHeatIndex) ?>;
    const yearlyAvgHeatIndex = <?= json_encode($avgHeatIndex) ?>;

    // Data for Next 7 Days Chart
    const next7DaysLabels = <?= json_encode(array_column($forecastData, 'date')) ?>;
    const next7DaysTemperatureData = <?= json_encode(array_column($forecastData, 'temperature_forecast')) ?>;
    const next7DaysHumidityData = <?= json_encode(array_column($forecastData, 'humidity_forecast')) ?>;
    const next7DaysHeatIndexData = <?= json_encode(array_column($forecastData, 'heat_index_forecast')) ?>;

    // Ensure to call the rendering functions when the DOM is fully loaded
    document.addEventListener('DOMContentLoaded', function () {
        // Call the appropriate chart rendering functions
        if (reportType === 'date_range' && dateRangeLabels.length > 0) {
            renderDateRangeChart(
                dateRangeLabels, 
                dateRangeTemperatureData, 
                dateRangeHumidityData, 
                dateRangeHeatIndexData
            );
        }

        // Call the appropriate chart rendering functions for Monthly Report
        if (reportType === 'monthly' && monthlyLabels.length > 0) {
            renderMonthlyChart(monthlyLabels, monthlyTemperatureData, monthlyHumidityData, monthlyHeatIndexData);
        }

        
        // Call the appropriate chart rendering functions for Yearly Report
        if (reportType === 'yearly' && yearlyLabels.length > 0) {
            renderTemperatureChart(yearlyLabels, yearlyMinTemperature, yearlyMaxTemperature, yearlyAvgTemperature);
            renderHumidityChart(yearlyLabels, yearlyMinHumidity, yearlyMaxHumidity, yearlyAvgHumidity);
            renderHeatIndexChart(yearlyLabels, yearlyMinHeatIndex, yearlyMaxHeatIndex, yearlyAvgHeatIndex);
        }

        // Call the rendering function for the Next 7 Days
        if (reportType === 'next_7_days' && next7DaysLabels.length > 0) {
            renderNext7DaysChart(next7DaysLabels, next7DaysTemperatureData, next7DaysHumidityData, next7DaysHeatIndexData);
        }
    });

    // Function to render the monthly chart
function renderMonthlyChart(monthlyLabels, monthlyTemperatureData, monthlyHumidityData, monthlyHeatIndexData) {
    const ctxMonthly = document.getElementById('monthlyChart').getContext('2d');
    new Chart(ctxMonthly, {
        type: 'line',
        data: {
            labels: monthlyLabels,
            datasets: [
                {
                    label: 'Temperature (°C)',
                    data: monthlyTemperatureData,
                    borderColor: 'rgba(255, 165, 0, 1)',
                    backgroundColor: 'rgba(255, 165, 0, 0.2)',
                    fill: false,
                    tension: 0.1
                },
                {
                    label: 'Humidity (%)',
                    data: monthlyHumidityData,
                    borderColor: 'rgba(54, 162, 235, 1)',
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    fill: false,
                    tension: 0.1
                },
                {
                    label: 'Heat Index (°C)',
                    data: monthlyHeatIndexData,
                    borderColor: 'rgba(128, 0, 0, 1)',
                    backgroundColor: 'rgba(128, 0, 0, 0.2)',
                    fill: false,
                    tension: 0.1
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Monthly Overview'
                }
            },
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Day'
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Value'
                    }
                }
            }
        }
    });
}

    // Function to render the temperature chart
function renderTemperatureChart(yearlyLabels, yearlyMinTemperature, yearlyMaxTemperature, yearlyAvgTemperature) {
    const ctxTemp = document.getElementById('temperatureChart').getContext('2d');
    new Chart(ctxTemp, {
        type: 'line',
        data: {
            labels: yearlyLabels,
            datasets: [
                {
                    label: 'Min Temperature (°C)',
                    data: yearlyMinTemperature,
                    borderColor: 'rgba(54, 162, 235, 1)',
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    fill: false,
                    tension: 0.1
                },
                {
                    label: 'Max Temperature (°C)',
                    data: yearlyMaxTemperature,
                    borderColor: 'rgba(255, 99, 132, 1)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    fill: false,
                    tension: 0.1
                },
                {
                    label: 'Avg Temperature (°C)',
                    data: yearlyAvgTemperature,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    fill: false,
                    tension: 0.1
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Yearly Temperature Overview'
                }
            },
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Year'
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Temperature (°C)'
                    }
                }
            }
        }
    });
}

// Function to render the humidity chart
function renderHumidityChart(yearlyLabels, yearlyMinHumidity, yearlyMaxHumidity, yearlyAvgHumidity) {
    const ctxHumidity = document.getElementById('humidityChart').getContext('2d');
    new Chart(ctxHumidity, {
        type: 'line',
        data: {
            labels: yearlyLabels,
            datasets: [
                {
                    label: 'Min Humidity (%)',
                    data: yearlyMinHumidity,
                    borderColor: 'rgba(54, 162, 235, 1)',
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    fill: false,
                    tension: 0.1
                },
                {
                    label: 'Max Humidity (%)',
                    data: yearlyMaxHumidity,
                    borderColor: 'rgba(255, 99, 132, 1)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    fill: false,
                    tension: 0.1
                },
                {
                    label: 'Avg Humidity (%)',
                    data: yearlyAvgHumidity,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    fill: false,
                    tension: 0.1
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Yearly Humidity Overview'
                }
            },
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Year'
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Humidity (%)'
                    }
                }
            }
        }
    });
}

// Function to render the heat index chart
function renderHeatIndexChart(yearlyLabels, yearlyMinHeatIndex, yearlyMaxHeatIndex, yearlyAvgHeatIndex) {
    const ctxHeatIndex = document.getElementById('heatIndexChart').getContext('2d');
    new Chart(ctxHeatIndex, {
        type: 'line',
        data: {
            labels: yearlyLabels,
            datasets: [
                {
                    label: 'Min Heat Index (°C)',
                    data: yearlyMinHeatIndex,
                    borderColor: 'rgba(54, 162, 235, 1)',
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    fill: false,
                    tension: 0.1
                },
                {
                    label: 'Max Heat Index (°C)',
                    data: yearlyMaxHeatIndex,
                    borderColor: 'rgba(255, 99, 132, 1)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    fill: false,
                    tension: 0.1
                },
                {
                    label: 'Avg Heat Index (°C)',
                    data: yearlyAvgHeatIndex,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    fill: false,
                    tension: 0.1
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Yearly Heat Index Overview'
                }
            },
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Year'
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Heat Index (°C)'
                    }
                }
            }
        }
    });
}
</script>


<?php include 'footer.php'; ?>

</body>
</html>

<?php
$link->close();
?>
