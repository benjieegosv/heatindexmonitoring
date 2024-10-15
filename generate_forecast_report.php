<?php
// Include database connection
include 'db_conn.php'; 
require 'vendor/autoload.php'; // PhpSpreadsheet and TCPDF

// Only start session if none exists
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Validate the report type
if (!isset($_GET['report_type']) || $_GET['report_type'] !== 'next_7_days') {
    exit('Invalid report type.');
}

// Define the directory to save the report
$saveDirectory = 'C:/xampp/htdocs/heatindexmonitoring-main/ReportForecast/';
$filename = "forecast_report_" . date('Y-m-d');

// Prepare the SQL query for insertion later
$generatedBy = ''; // This will store the user's full name (admin or staff)
$reportType = 'next_7_days'; // Define the report type

// Determine the user (admin or staff) who generated the report
if (isset($_SESSION['adminAccNum'])) {
    // Fetch admin details
    $adminQuery = "SELECT firstName, lastName FROM admin_account WHERE accNum = ?";
    $stmt = $link->prepare($adminQuery);
    $stmt->bind_param("i", $_SESSION['adminAccNum']);
    $stmt->execute();
    $adminResult = $stmt->get_result();
    if ($adminRow = $adminResult->fetch_assoc()) {
        $generatedBy = $adminRow['firstName'] . ' ' . $adminRow['lastName'];
    }
} elseif (isset($_SESSION['accNum'])) {
    // Fetch staff details
    $staffQuery = "SELECT firstName, lastName FROM staff_account WHERE accNum = ?";
    $stmt = $link->prepare($staffQuery);
    $stmt->bind_param("i", $_SESSION['accNum']);
    $stmt->execute();
    $staffResult = $stmt->get_result();
    if ($staffRow = $staffResult->fetch_assoc()) {
        $generatedBy = $staffRow['firstName'] . ' ' . $staffRow['lastName'];
    }
}

// Fetch forecast data directly from the forecast_data table
$query = "
    SELECT location, temperature_forecast, humidity_forecast, heat_index_forecast, date 
    FROM forecast_data 
    WHERE DATE(date) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) 
    ORDER BY date ASC";

$stmt = $link->prepare($query);
$stmt->execute();
$result = $stmt->get_result();

// Validate forecast data
if ($result->num_rows === 0) {
    exit('No forecast data available for the next 7 days.');
}

// Initialize summary calculations
$summary = [
    'avg_temperature' => 0,
    'max_temperature' => PHP_FLOAT_MIN,
    'min_temperature' => PHP_FLOAT_MAX,
    'avg_humidity' => 0,
    'max_humidity' => PHP_FLOAT_MIN,
    'min_humidity' => PHP_FLOAT_MAX,
    'avg_heat_index' => 0,
    'max_heat_index' => PHP_FLOAT_MIN,
    'min_heat_index' => PHP_FLOAT_MAX,
];

// Calculate summary values
$totalTemperature = 0;
$totalHumidity = 0;
$totalHeatIndex = 0;
$temperatureCount = 0;

$forecastData = [];
while ($data = $result->fetch_assoc()) {
    $forecastData[] = $data;
    $temperature = $data['temperature_forecast'];
    $humidity = $data['humidity_forecast'];
    $heatIndex = $data['heat_index_forecast'];

    // Accumulate for averages
    $totalTemperature += $temperature;
    $totalHumidity += $humidity;
    $totalHeatIndex += $heatIndex;
    $temperatureCount++;

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

// Calculate averages
if ($temperatureCount > 0) {
    $summary['avg_temperature'] = $totalTemperature / $temperatureCount;
    $summary['avg_humidity'] = $totalHumidity / $temperatureCount;
    $summary['avg_heat_index'] = $totalHeatIndex / $temperatureCount;
} else {
    // Handle case where there's no data
    exit('No data available for calculations.');
}

// Generate report based on the requested format
$fileFormat = $_GET['download'] ?? 'xlsx'; // Default to xlsx
$filePath = $saveDirectory . $filename . ".$fileFormat";

switch ($fileFormat) {
    case 'csv':
        // Create CSV report
        $file = fopen($filePath, 'w');
        
        // Write summary to the file
        fputcsv($file, ['Metric', 'Max', 'Average', 'Min']);
        fputcsv($file, ['Temperature (°C)', number_format($summary['max_temperature'], 2), number_format($summary['avg_temperature'], 2), number_format($summary['min_temperature'], 2)]);
        fputcsv($file, ['Humidity (%)', number_format($summary['max_humidity'], 2), number_format($summary['avg_humidity'], 2), number_format($summary['min_humidity'], 2)]);
        fputcsv($file, ['Heat Index (°C)', number_format($summary['max_heat_index'], 2), number_format($summary['avg_heat_index'], 2), number_format($summary['min_heat_index'], 2)]);
        fputcsv($file, []); // Empty line for separation

        // Write forecast data to the file
        fputcsv($file, ['Date', 'Location', 'Temperature (°C)', 'Humidity (%)', 'Heat Index (°C)']);
        foreach ($forecastData as $data) {
            fputcsv($file, [
                $data['date'],
                $data['location'],
                number_format($data['temperature_forecast'], 2),
                number_format($data['humidity_forecast'], 2),
                number_format($data['heat_index_forecast'], 2),
            ]);
        }

        fclose($file);
        // Download the report
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        readfile($filePath); // Serve the saved file for download
        break;

    case 'excel':
        // Create Excel report
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Write summary to the Excel sheet
        $sheet->fromArray(['Metric', 'Max', 'Average', 'Min'], NULL, 'A1');
        $sheet->fromArray(['Temperature (°C)', number_format($summary['max_temperature'], 2), number_format($summary['avg_temperature'], 2), number_format($summary['min_temperature'], 2)], NULL, 'A2');
        $sheet->fromArray(['Humidity (%)', number_format($summary['max_humidity'], 2), number_format($summary['avg_humidity'], 2), number_format($summary['min_humidity'], 2)], NULL, 'A3');
        $sheet->fromArray(['Heat Index (°C)', number_format($summary['max_heat_index'], 2), number_format($summary['avg_heat_index'], 2), number_format($summary['min_heat_index'], 2)], NULL, 'A4');
        
        // Start the observation data after a blank row
        $sheet->fromArray(['Location', 'Temperature', 'Humidity', 'Heat Index', 'Date'], NULL, 'A6');
        $rowCount = 7; // Start from row 7 for observation table
        foreach ($forecastData as $data) {
            $sheet->fromArray([
                $data['location'],
                number_format($data['temperature_forecast'], 2),
                number_format($data['humidity_forecast'], 2),
                number_format($data['heat_index_forecast'], 2),
                $data['date']
            ], NULL, 'A' . $rowCount);
            $rowCount++;
        }

        $filePath = $saveDirectory . $filename . '.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($filePath); // Save the Excel file to the specified path

        // Now force the file to download from the saved location
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        readfile($filePath); // Serve the saved file for download
        break;

    case 'pdf':
        // Create PDF report
        $pdf = new \TCPDF();
        $pdf->AddPage();

        // Add summary table to the PDF
        $html = '<h1>Forecast Summary</h1>';
        $html .= '<table border="1" cellspacing="0" cellpadding="4"><thead><tr><th>Metric</th><th>Max</th><th>Average</th><th>Min</th></tr></thead><tbody>';
        
        // For Temperature
        $html .= sprintf('<tr><td>Temperature (°C)</td><td>%.2f</td><td>%.2f</td><td>%.2f</td></tr>',
            floatval($summary['max_temperature']),
            floatval($summary['avg_temperature']),
            floatval($summary['min_temperature'])
        );

        // For Humidity
        $html .= sprintf('<tr><td>Humidity (%%)</td><td>%.2f</td><td>%.2f</td><td>%.2f</td></tr>',
            floatval($summary['max_humidity']),
            floatval($summary['avg_humidity']),
            floatval($summary['min_humidity'])
        );

        // For Heat Index
        $html .= sprintf('<tr><td>Heat Index (°C)</td><td>%.2f</td><td>%.2f</td><td>%.2f</td></tr>',
            floatval($summary['max_heat_index']),
            floatval($summary['avg_heat_index']),
            floatval($summary['min_heat_index'])
        );

        $html .= '</tbody></table><br>';

        // Add observation table to the PDF
        $html .= '<h2>Forecast Observations</h2>';
        $html .= '<table border="1" cellspacing="0" cellpadding="4">';
        $html .= '<thead><tr><th>Location</th><th>Temperature</th><th>Humidity</th><th>Heat Index</th><th>Date</th></tr></thead><tbody>';
        
        foreach ($forecastData as $data) {
            $html .= sprintf('<tr><td>%s</td><td>%.2f</td><td>%.2f</td><td>%.2f</td><td>%s</td></tr>',
                htmlspecialchars($data['location']),
                floatval($data['temperature_forecast']),
                floatval($data['humidity_forecast']),
                floatval($data['heat_index_forecast']),
                htmlspecialchars($data['date'])
            );
        }
        $html .= '</tbody></table>';
        $pdf->writeHTML($html);
        
        // Save the PDF file to the specified path
        $pdf->Output($filePath, 'F');

        // Download the report
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        readfile($filePath); // Serve the saved file for download
        break;
}

// Save report details into the report_files table after the report is generated
if ($filePath !== '') {
    // Insert the report details including the report name
    $reportName = "Forecast Report - " . date('Y-m-d'); // Define a report name
    $stmt = $link->prepare("INSERT INTO report_files (report_name, report_type, file_path, file_format, generated_by) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $reportName, $reportType, $filePath, $fileFormat, $generatedBy);
    $stmt->execute();
}

exit();
?>
