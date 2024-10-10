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

// Determine the type of report (date range or monthly or next 7 days)
$reportType = $_GET['report_type'] ?? 'date_range'; // Default is date_range

// For Date Range Reports
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-1 week'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// For Monthly Reports
$year = $_GET['year'] ?? date('Y'); // Default to the current year if not provided

// Initialize arrays
$labels = [];
$temperatureData = [];
$humidityData = [];
$heatIndexData = [];
$months = [];
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

// Validate the date range and fetch data
if ($reportType == 'date_range') {
    if (!DateTime::createFromFormat('Y-m-d', $start_date) || !DateTime::createFromFormat('Y-m-d', $end_date) || $start_date > $end_date) {
        echo "Invalid date range. Please enter a valid date range.";
        exit();
    }

    $query = "SELECT location, temperature, humidity, heatIndex, DATE_FORMAT(date, '%Y-%m-%d') as formatted_date
              FROM external_heat_index_data
              WHERE date BETWEEN ? AND ? 
              ORDER BY date ASC";
    $stmt = $link->prepare($query);
    $stmt->bind_param('ss', $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    // Populate arrays for chart data
    while ($row = $result->fetch_assoc()) {
        $labels[] = $row['formatted_date'];
        $temperatureData[] = $row['temperature'];
        $humidityData[] = $row['humidity'];
        $heatIndexData[] = $row['heatIndex'];
    }
}

// For Monthly Reports
if ($reportType == 'monthly') {
    if (!is_numeric($year) || strlen($year) !== 4) {
        echo "Invalid year. Please enter a valid year.";
        exit();
    }

    $query = "
        SELECT 
            MONTH(date) AS month, 
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
        WHERE YEAR(date) = ?
        GROUP BY MONTH(date)
        ORDER BY MONTH(date)";

    $stmt = $link->prepare($query);
    $stmt->bind_param('s', $year);
    $stmt->execute();
    $monthly_result = $stmt->get_result();

    // Populate arrays for chart data
    while ($data = $monthly_result->fetch_assoc()) {
        $months[] = date('F', mktime(0, 0, 0, (int)$data['month'], 1)); // Convert month number to name
        $minTemperature[] = $data['min_temperature'];
        $maxTemperature[] = $data['max_temperature'];
        $avgTemperature[] = $data['avg_temperature'];
        $minHumidity[] = $data['min_humidity'];
        $maxHumidity[] = $data['max_humidity'];
        $avgHumidity[] = $data['avg_humidity'];
        $minHeatIndex[] = $data['min_heat_index'];
        $maxHeatIndex[] = $data['max_heat_index'];
        $avgHeatIndex[] = $data['avg_heat_index'];
    }
}

// Fetch forecast data from forecast.py for the next 7 days
if ($reportType == 'next_7_days') {
    $forecastDataRaw = shell_exec('C:/Python312/python.exe /xampp/htdocs/heatindexmonitoring-main/forecast.py 2>&1');  // Adjust the path to your forecast.py
    $forecastData = json_decode($forecastDataRaw, true);

    // Validate forecast data
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "Error fetching forecast data: " . json_last_error_msg();
        exit();
    }

    // Check if the forecastData is structured correctly
    if (empty($forecastData)) {
        echo "No forecast data available.";
        exit();
    }
}

// If user requested a download, handle it
if ($isDownload) {
    // Logic for downloading reports
    if ($reportType == 'date_range') {
        include 'generate_date_range_report.php'; // This handles date range report downloads
    } elseif ($reportType == 'monthly') {
        include 'generate_monthly_report.php'; // This handles monthly report downloads
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
            <option value="next_7_days" <?= $reportType == 'next_7_days' ? 'selected' : '' ?>>Next 7 Days</option>
        </select>
    </form>
</div>

<!-- Date Range Report Section -->
<?php if ($reportType == 'date_range'): ?>
<div class="container">
    <h4>Date Range-based Report</h4>
    <div class="report-container">
        <form action="" method="get">
            <input type="hidden" name="report_type" value="date_range">
            <label for="start_date">Start Date:</label>
            <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" required>
            <label for="end_date">End Date:</label>
            <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" required>
            <button type="submit">Generate Report</button>
        </form>

        <?php if ($reportType == 'date_range' && count($labels) > 0): ?>
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
    <h4>Monthly Report</h4>
    <div class="report-container">
        <form action="" method="get">
            <input type="hidden" name="report_type" value="monthly">
            <label for="year">Year:</label>
            <input type="number" id="year" name="year" value="<?= htmlspecialchars($year) ?>" required min="1900" max="2100">
            <button type="submit">Generate Report</button>
        </form>

        <?php if ($reportType == 'monthly' && count($months) > 0): ?>
        <table>
            <thead>
            <tr>
                <th>Month</th>
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
            for ($i = 0; $i < count($months); $i++): ?>
                <tr>
                    <td><?= htmlspecialchars($months[$i]) ?></td>
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

        <div class="chart-container">
            <canvas id="temperatureChart"></canvas>
            <canvas id="humidityChart"></canvas>
            <canvas id="heatIndexChart"></canvas>
        </div>

        <div class="download-container">
            <a href="?download=csv&year=<?= htmlspecialchars($year) ?>&report_type=monthly">Download as CSV</a>
            <a href="?download=excel&year=<?= htmlspecialchars($year) ?>&report_type=monthly">Download as Excel</a>
            <a href="?download=pdf&year=<?= htmlspecialchars($year) ?>&report_type=monthly">Download as PDF</a>
        </div>
        
        <?php else: ?>
            <p>No data available for the selected year.</p>
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

        <div class="chart-container">
            <canvas id="forecastChart"></canvas>
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
    const monthlyLabels = <?= json_encode($months) ?>;
    const monthlyMinTemperature = <?= json_encode($minTemperature) ?>;
    const monthlyMaxTemperature = <?= json_encode($maxTemperature) ?>;
    const monthlyAvgTemperature = <?= json_encode($avgTemperature) ?>;
    const monthlyMinHumidity = <?= json_encode($minHumidity) ?>;
    const monthlyMaxHumidity = <?= json_encode($maxHumidity) ?>;
    const monthlyAvgHumidity = <?= json_encode($avgHumidity) ?>;
    const monthlyMinHeatIndex = <?= json_encode($minHeatIndex) ?>;
    const monthlyMaxHeatIndex = <?= json_encode($maxHeatIndex) ?>;
    const monthlyAvgHeatIndex = <?= json_encode($avgHeatIndex) ?>;

    // Data for Next 7 Days Chart
    const next7DaysLabels = <?= json_encode(array_column($forecastData, 'date')) ?>;
    const next7DaysTemperatureData = <?= json_encode(array_column($forecastData, 'temperature_forecast')) ?>;
    const next7DaysHumidityData = <?= json_encode(array_column($forecastData, 'humidity_forecast')) ?>;
    const next7DaysHeatIndexData = <?= json_encode(array_column($forecastData, 'heat_index_forecast')) ?>;

    // Call the appropriate chart rendering functions
    if (reportType === 'date_range' && dateRangeLabels.length > 0) {
        renderDateRangeChart(dateRangeLabels, dateRangeTemperatureData, dateRangeHumidityData, dateRangeHeatIndexData);
    }

    if (reportType === 'monthly' && monthlyLabels.length > 0) {
        renderMonthlyCharts(
            monthlyLabels,
            monthlyMinTemperature,
            monthlyMaxTemperature,
            monthlyAvgTemperature,
            monthlyMinHumidity,
            monthlyMaxHumidity,
            monthlyAvgHumidity,
            monthlyMinHeatIndex,
            monthlyMaxHeatIndex,
            monthlyAvgHeatIndex
        );
    }

    if (reportType === 'next_7_days' && next7DaysLabels.length > 0) {
        renderNext7DaysChart(next7DaysLabels, next7DaysTemperatureData, next7DaysHumidityData, next7DaysHeatIndexData);
    }
</script>

</body>
</html>

<?php
$link->close();
?>
