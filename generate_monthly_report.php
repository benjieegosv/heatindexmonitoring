<?php
// Include database connection
include 'db_conn.php';
require 'vendor/autoload.php'; // Include PhpSpreadsheet and TCPDF

// Only start session if none exists
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get the download format, month, and year from the request
$format = $_GET['download'];
$selectedMonth = $_GET['month'];
$selectedYear = $_GET['year'];
$monthName = date('F', mktime(0, 0, 0, $selectedMonth, 1)); // Get month name
$filename = "monthly_report_{$monthName}_{$selectedYear}"; // Use month name in file name

// Ensure the report name is within the allowed length (50 characters)
$reportName = "Monthly Report for {$monthName}/{$selectedYear}";
if (strlen($reportName) > 50) {
    $reportName = substr($reportName, 0, 50);  // Truncate if the report name exceeds the limit
}

// Directory to save the file (adjust the path accordingly)
$saveDirectory = 'C:/xampp/htdocs/heatindexmonitoring-main/ReportMonthly/';
$filePath = ''; // This will store the full path to the saved file

// Prepare the SQL query for insertion later (we will update this after generating the file)
$generatedBy = ''; // This will store the user's full name (admin or staff)
$reportType = 'monthly'; // Hardcoded report type, but it can now be up to 50 characters

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

// Fetch individual records for the detailed table
$dataQuery = "
    SELECT 
        DAY(date) AS day, 
        location, 
        temperature, 
        humidity, 
        heatIndex 
    FROM external_heat_index_data 
    WHERE MONTH(date) = ? AND YEAR(date) = ?
    ORDER BY date ASC";

$stmt = $link->prepare($dataQuery);
$stmt->bind_param('ii', $selectedMonth, $selectedYear);
$stmt->execute();
$data_result = $stmt->get_result();

// Switch case to handle different download formats
switch ($format) {
    case 'csv':
        $filePath = $saveDirectory . $filename . '.csv'; // Path where the file will be saved
        $output = fopen($filePath, 'w'); // Save the file to the specified path

        // Add summary table headers and values
        fputcsv($output, ['Metric', 'Max', 'Average', 'Min']);
        fputcsv($output, ['Temperature (°C)', $summary['max_temperature'], number_format($summary['avg_temperature'], 2), $summary['min_temperature']]);
        fputcsv($output, ['Humidity (%)', $summary['max_humidity'], number_format($summary['avg_humidity'], 2), $summary['min_humidity']]);
        fputcsv($output, ['Heat Index (°C)', $summary['max_heat_index'], number_format($summary['avg_heat_index'], 2), $summary['min_heat_index']]);
        fputcsv($output, []); // Blank row for separation

        // Add observation table headers and values
        fputcsv($output, ['Day', 'Location', 'Temperature (°C)', 'Humidity (%)', 'Heat Index (°C)']);
        while ($row = $data_result->fetch_assoc()) {
            fputcsv($output, [
                $row['day'],
                $row['location'],
                number_format($row['temperature'], 2),
                number_format($row['humidity'], 2),
                number_format($row['heatIndex'], 2)
            ]);
        }
        fclose($output);

        // Now force the file to download from the saved location
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        readfile($filePath); // Serve the saved file for download
        break;

    case 'excel':
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Add summary table headers and values
        $sheet->fromArray(['Metric', 'Max', 'Average', 'Min'], NULL, 'A1');
        $sheet->fromArray(['Temperature (°C)', $summary['max_temperature'], number_format($summary['avg_temperature'], 2), $summary['min_temperature']], NULL, 'A2');
        $sheet->fromArray(['Humidity (%)', $summary['max_humidity'], number_format($summary['avg_humidity'], 2), $summary['min_humidity']], NULL, 'A3');
        $sheet->fromArray(['Heat Index (°C)', $summary['max_heat_index'], number_format($summary['avg_heat_index'], 2), $summary['min_heat_index']], NULL, 'A4');
        
        // Start the observation data after a blank row
        $sheet->fromArray(['Day', 'Location', 'Temperature (°C)', 'Humidity (%)', 'Heat Index (°C)'], NULL, 'A6');
        $rowCount = 7; // Start from row 7 for observation table
        while ($row = $data_result->fetch_assoc()) {
            $sheet->fromArray([
                $row['day'],
                $row['location'],
                number_format($row['temperature'], 2),
                number_format($row['humidity'], 2),
                number_format($row['heatIndex'], 2)
            ], NULL, 'A' . $rowCount);
            $rowCount++;
        }

        $filePath = $saveDirectory . $filename . '.xlsx'; // Path where the file will be saved
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($filePath); // Save the Excel file to the specified path

        // Now force the file to download from the saved location
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
        readfile($filePath); // Serve the saved file for download
        break;

    case 'pdf':
        $pdf = new TCPDF();
        $pdf->AddPage();

        // Add summary table to the PDF
        $html = '<h1>Monthly Report for ' . htmlspecialchars($monthName) . '/' . htmlspecialchars($selectedYear) . '</h1>';
        $html .= '<h2>Summary of Report</h2>';
        $html .= '<table border="1" cellspacing="0" cellpadding="4">';
        $html .= '<thead><tr><th>Metric</th><th>Max</th><th>Average</th><th>Min</th></tr></thead>';
        $html .= '<tbody>';

        // For Temperature
        $html .= sprintf(
            '<tr><td>Temperature (°C)</td><td>%.2f</td><td>%.2f</td><td>%.2f</td></tr>',
            floatval($summary['max_temperature']),
            floatval($summary['avg_temperature']),
            floatval($summary['min_temperature'])
        );

        // For Humidity
        $html .= sprintf(
            '<tr><td>Humidity (%%)</td><td>%.2f</td><td>%.2f</td><td>%.2f</td></tr>',
            floatval($summary['max_humidity']),
            floatval($summary['avg_humidity']),
            floatval($summary['min_humidity'])
        );

        // For Heat Index
        $html .= sprintf(
            '<tr><td>Heat Index (°C)</td><td>%.2f</td><td>%.2f</td><td>%.2f</td></tr>',
            floatval($summary['max_heat_index']),
            floatval($summary['avg_heat_index']),
            floatval($summary['min_heat_index'])
        );

        $html .= '</tbody></table><br>';

        // Add observation table to the PDF
        $html .= '<h2>Observations</h2>';
        $html .= '<table border="1" cellspacing="0" cellpadding="4">';
        $html .= '<thead><tr><th>Day</th><th>Location</th><th>Temperature</th><th>Humidity</th><th>Heat Index</th></tr></thead><tbody>';
        
        // Re-fetch the data as it might have been consumed by previous formats
        $stmt = $link->prepare($dataQuery);
        $stmt->bind_param('ii', $selectedMonth, $selectedYear);
        $stmt->execute();
        $data_result = $stmt->get_result();
        
        while ($row = $data_result->fetch_assoc()) {
            $html .= sprintf(
                '<tr><td>%s</td><td>%s</td><td>%.2f</td><td>%.2f</td><td>%.2f</td></tr>',
                htmlspecialchars($row['day']),
                htmlspecialchars($row['location']),
                floatval($row['temperature']),
                floatval($row['humidity']),
                floatval($row['heatIndex'])
            );
        }
        $html .= '</tbody></table>';
        $pdf->writeHTML($html, true, false, true, false, '');

        $filePath = $saveDirectory . $filename . '.pdf'; // Path where the file will be saved
        $pdf->Output($filePath, 'F'); // Save the PDF file to the specified path

        // Now force the file to download from the saved location
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '.pdf"');
        readfile($filePath); // Serve the saved file for download
        break;
}

// Save report details into the report_files table after the report is generated
if ($filePath !== '') {
    // Insert the report details including the report name (limited to 50 characters)
    $stmt = $link->prepare("INSERT INTO report_files (report_name, report_type, file_path, file_format, generated_by) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $reportName, $reportType, $filePath, $format, $generatedBy);
    $stmt->execute();
}

exit();
?>
