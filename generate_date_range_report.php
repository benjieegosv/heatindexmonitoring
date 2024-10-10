<?php
// Include database connection
include 'db_conn.php'; 

// Get the download format, start and end date from the request
$format = $_GET['download'];
$start_date = $_GET['start_date'];
$end_date = $_GET['end_date'];
$filename = "report_{$start_date}_to_{$end_date}";

// Ensure the report name is within the allowed length (50 characters)
$reportName = "Report {$start_date} to {$end_date}";
if (strlen($reportName) > 50) {
    $reportName = substr($reportName, 0, 50);  // Truncate if the report name exceeds the limit
}

// Directory to save the file (adjust the path accordingly)
$saveDirectory = 'C:/xampp/htdocs/heatindexmonitoring-main/ReportDateRange/';
$filePath = ''; // This will store the full path to the saved file

// Prepare the SQL query for insertion later (we will update this after generating the file)
$generatedBy = ''; // This will store the user's full name (admin or staff)
$reportType = 'date_range'; // Hardcoded report type, but it can now be up to 50 characters

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

// Switch case to handle different download formats
switch ($format) {
    case 'csv':
        $filePath = $saveDirectory . $filename . '.csv'; // Path where the file will be saved
        $output = fopen($filePath, 'w'); // Save the file to the specified path
        fputcsv($output, ['Location', 'Temperature', 'Humidity', 'Heat Index', 'Date']);
        foreach ($result as $row) {
            fputcsv($output, [
                $row['location'],
                $row['temperature'],
                $row['humidity'],
                $row['heatIndex'],
                $row['formatted_date']
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
        $sheet->fromArray(['Location', 'Temperature', 'Humidity', 'Heat Index', 'Date'], NULL, 'A1');
        $rowCount = 2; // Start from the second row because the first row is for headers
        foreach ($result as $row) {
            $sheet->fromArray([
                $row['location'],
                $row['temperature'],
                $row['humidity'],
                $row['heatIndex'],
                $row['formatted_date']
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
        $html = '<h1>Monitoring Report from ' . htmlspecialchars($start_date) . ' to ' . htmlspecialchars($end_date) . '</h1>'
              . '<table border="1" cellspacing="0" cellpadding="4">'
              . '<thead><tr><th>Location</th><th>Temperature</th><th>Humidity</th><th>Heat Index</th><th>Date</th></tr></thead><tbody>';
        foreach ($result as $row) {
            $html .= sprintf(
                '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                htmlspecialchars($row['location']),
                htmlspecialchars($row['temperature']),
                htmlspecialchars($row['humidity']),
                htmlspecialchars($row['heatIndex']),
                htmlspecialchars($row['formatted_date'])
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
