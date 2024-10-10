<?php
require 'vendor/autoload.php'; // For PhpSpreadsheet and TCPDF
include 'db_conn.php'; // Include database connection

// Fetch forecast data from forecast.py
$forecastDataRaw = shell_exec('C:/Python312/python.exe /xampp/htdocs/heatindexmonitoring-main/forecast.py 2>&1');  // Adjust the path to your forecast.py
$forecastData = json_decode($forecastDataRaw, true);

// Validate the forecast data
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "Error fetching forecast data.";
    exit();
}

// Check if a report type is specified
$reportType = $_GET['report_type'] ?? '';

if ($reportType == 'next_7_days') {
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

    // Define the directory to save the report
    $saveDirectory = 'C:/xampp/htdocs/heatindexmonitoring-main/ReportForecast/';
    $filename = "forecast_report_" . date('Y-m-d');

    // Generate a report name with the current date
    $reportName = "7-Day Forecast Report " . date('Y-m-d');

    // Handle downloads based on user request
    if (isset($_GET['download'])) {
        $format = $_GET['download'];

        // CSV download
        if ($format == 'csv') {
            $filePath = $saveDirectory . $filename . '.csv';
            
            // Save CSV file to the specified directory
            $output = fopen($filePath, 'w');
            fputcsv($output, ['Location', 'Temperature (°C)', 'Humidity (%)', 'Heat Index (°C)', 'Date']);
            foreach ($forecastData as $data) {
                fputcsv($output, [
                    $data['location'],
                    number_format($data['temperature_forecast'], 2), // Format to 2 decimal points
                    number_format($data['humidity_forecast'], 2),    // Format to 2 decimal points
                    number_format($data['heat_index_forecast'], 2),  // Format to 2 decimal points
                    $data['date']
                ]);
            }
            fclose($output);
            
            // Now serve the file for download
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
            readfile($filePath);
        }

        // Excel download
        if ($format == 'excel') {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Forecast Report');

            $sheet->setCellValue('A1', 'Location');
            $sheet->setCellValue('B1', 'Temperature (°C)');
            $sheet->setCellValue('C1', 'Humidity (%)');
            $sheet->setCellValue('D1', 'Heat Index (°C)');
            $sheet->setCellValue('E1', 'Date');

            $row = 2;
            foreach ($forecastData as $data) {
                $sheet->setCellValue("A$row", $data['location']);
                $sheet->setCellValue("B$row", number_format($data['temperature_forecast'], 2)); // Format to 2 decimal points
                $sheet->setCellValue("C$row", number_format($data['humidity_forecast'], 2));    // Format to 2 decimal points
                $sheet->setCellValue("D$row", number_format($data['heat_index_forecast'], 2));  // Format to 2 decimal points
                $sheet->setCellValue("E$row", $data['date']);
                $row++;
            }

            $filePath = $saveDirectory . $filename . '.xlsx';
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save($filePath);

            // Now serve the file for download
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
            readfile($filePath);
        }

        // PDF download
        if ($format == 'pdf') {
            $pdf = new TCPDF();
            $pdf->AddPage();
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 10, '7-Day Forecast Report', 0, 1, 'C');

            $html = '<table border="1" cellpadding="5">
                        <thead>
                            <tr>
                                <th>Location</th>
                                <th>Temperature (°C)</th>
                                <th>Humidity (%)</th>
                                <th>Heat Index (°C)</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>';
            foreach ($forecastData as $data) {
                $html .= '<tr>
                            <td>' . htmlspecialchars($data['location']) . '</td>
                            <td>' . htmlspecialchars(number_format($data['temperature_forecast'], 2)) . '</td>
                            <td>' . htmlspecialchars(number_format($data['humidity_forecast'], 2)) . '</td>
                            <td>' . htmlspecialchars(number_format($data['heat_index_forecast'], 2)) . '</td>
                            <td>' . htmlspecialchars($data['date']) . '</td>
                          </tr>';
            }
            $html .= '</tbody></table>';

            $pdf->writeHTML($html);
            $filePath = $saveDirectory . $filename . '.pdf';
            $pdf->Output($filePath, 'F');

            // Now serve the file for download
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
            readfile($filePath);
        }

        // Save report details into the report_files table after the report is generated
        if ($filePath !== '') {
            $stmtInsert = $link->prepare("INSERT INTO report_files (report_name, report_type, file_path, file_format, generated_by) VALUES (?, ?, ?, ?, ?)");
            $reportType = "next_7_days"; // Set as "next_7_days" for this forecast report
            $stmtInsert->bind_param("sssss", $reportName, $reportType, $filePath, $format, $generatedBy);
            $stmtInsert->execute();
        }
    }
}
