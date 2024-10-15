<?php
require 'vendor/autoload.php'; // Load PhpSpreadsheet and TCPDF
include 'db_conn.php'; // Include database connection

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Retrieve the year range from GET parameters
if (!isset($_GET['startYear']) || !isset($_GET['endYear'])) {
    echo "Year range is not specified.";
    exit();
}

$startYear = htmlspecialchars($_GET['startYear']); // Retrieve the start year for the report
$endYear = htmlspecialchars($_GET['endYear']); // Retrieve the end year for the report

// Determine the requested file format (default is CSV)
$format = $_GET['download'] ?? 'csv';

// Query the database to get the yearly data
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

// Prepare the statement
$stmt = $link->prepare($query);
$stmt->bind_param('ii', $startYear, $endYear); // Bind startYear and endYear as integers
$stmt->execute();
$result = $stmt->get_result();

// Check if data was found
if ($result->num_rows === 0) {
    echo "No data available for the selected year range.";
    exit();
}

// Prepare the data from the result
$data_to_export = [];
while ($row = $result->fetch_assoc()) {
    $data_to_export[] = [
        'year' => $row['year'],
        'min_temperature' => number_format($row['min_temperature'], 2),
        'max_temperature' => number_format($row['max_temperature'], 2),
        'min_humidity' => number_format($row['min_humidity'], 2),
        'max_humidity' => number_format($row['max_humidity'], 2),
        'min_heat_index' => number_format($row['min_heat_index'], 2),
        'max_heat_index' => number_format($row['max_heat_index'], 2),
        'avg_temperature' => number_format($row['avg_temperature'], 2),
        'avg_humidity' => number_format($row['avg_humidity'], 2),
        'avg_heat_index' => number_format($row['avg_heat_index'], 2)
    ];
}

// Define headers for the report
$headers = ['Year', 'Min Temperature (°C)', 'Max Temperature (°C)', 'Min Humidity (%)', 'Max Humidity (%)', 'Min Heat Index (°C)', 'Max Heat Index (°C)', 'Average Temperature (°C)', 'Average Humidity (%)', 'Average Heat Index (°C)'];

// Define the directory to save the file (adjust the path accordingly)
$saveDirectory = 'C:/xampp/htdocs/heatindexmonitoring-main/ReportYearly/'; // Adjust path as necessary
$filename = "yearly_report_{$startYear}_to_{$endYear}_" . date('Y-m-d');

// Generate a report name (up to 50 characters)
$reportName = "Yearly Report from {$startYear} to {$endYear}";

// Prepare the "generated_by" field
$generatedBy = ''; // This will store the user's full name (admin or staff)

// Determine the user (admin or staff) who generated the report
if (isset($_SESSION['adminAccNum'])) {
    // Fetch admin details
    $adminQuery = "SELECT firstName, lastName FROM admin_account WHERE accNum = ?";
    $stmtUser = $link->prepare($adminQuery);
    $stmtUser->bind_param("i", $_SESSION['adminAccNum']);
    $stmtUser->execute();
    $adminResult = $stmtUser->get_result();
    if ($adminRow = $adminResult->fetch_assoc()) {
        $generatedBy = $adminRow['firstName'] . ' ' . $adminRow['lastName'];
    }
} elseif (isset($_SESSION['accNum'])) {
    // Fetch staff details
    $staffQuery = "SELECT firstName, lastName FROM staff_account WHERE accNum = ?";
    $stmtUser = $link->prepare($staffQuery);
    $stmtUser->bind_param("i", $_SESSION['accNum']);
    $stmtUser->execute();
    $staffResult = $stmtUser->get_result();
    if ($staffRow = $staffResult->fetch_assoc()) {
        $generatedBy = $staffRow['firstName'] . ' ' . $staffRow['lastName'];
    }
}

// Proceed to generate the report based on the format (CSV, Excel, PDF)
if ($format === 'csv') {
    $filePath = $saveDirectory . $filename . '.csv';
    
    // Save the CSV file to the specified directory
    $output = fopen($filePath, 'w');
    fputcsv($output, $headers); // Write headers to the CSV file
    foreach ($data_to_export as $row) {
        fputcsv($output, $row); // Write each row of data to the CSV
    }
    fclose($output);
    
    // Now serve the file for download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
    readfile($filePath); // Serve the file
}

// Generate Excel file
if ($format === 'excel') {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set headers in Excel sheet
    $sheet->fromArray($headers, NULL, 'A1');

    // Populate rows with data
    $rowCount = 2;
    foreach ($data_to_export as $row) {
        $sheet->fromArray(array_values($row), NULL, 'A' . $rowCount);
        $rowCount++;
    }

    // Save the Excel file to the specified directory
    $filePath = $saveDirectory . $filename . '.xlsx';
    $writer = new Xlsx($spreadsheet);
    $writer->save($filePath);

    // Now serve the file for download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
    readfile($filePath); // Serve the file
}

// Generate PDF file
if ($format === 'pdf') {
    ob_clean(); // Clean output buffer

    // Initialize TCPDF directly without the "use" statement
    $pdf = new TCPDF();
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 12);

    // Create HTML content for the PDF, with the year range in the title
    $html = "<h2>Yearly Monitoring Report from $startYear to $endYear</h2>"; // Add the year range in the title
    $html .= '<table border="1" cellpadding="4">';
    $html .= '<thead><tr>';
    foreach ($headers as $header) {
        $html .= "<th>$header</th>";
    }
    $html .= '</tr></thead><tbody>';

    // Populate the table with data
    foreach ($data_to_export as $row) {
        $html .= '<tr>';
        foreach ($row as $value) {
            $html .= "<td>$value</td>";
        }
        $html .= '</tr>';
    }
    $html .= '</tbody></table>';

    // Write the HTML to the PDF
    $pdf->writeHTML($html, true, false, true, false, '');

    // Save the PDF file to the specified directory
    $filePath = $saveDirectory . $filename . '.pdf';
    $pdf->Output($filePath, 'F'); // Save the PDF

    // Now serve the file for download
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
    readfile($filePath); // Serve the file
}

// Save report details into the report_files table after the report is generated
if (isset($filePath) && $filePath !== '') {
    $stmtInsert = $link->prepare("INSERT INTO report_files (report_name, report_type, file_path, file_format, generated_by) VALUES (?, ?, ?, ?, ?)");
    $reportType = "yearly"; // Hardcoded as "yearly" for this report type
    $stmtInsert->bind_param("sssss", $reportName, $reportType, $filePath, $format, $generatedBy);
    $stmtInsert->execute();
}

exit();
?>
