<?php
// Set the content type to JSON
header('Content-Type: application/json');

// Include your database connection file
include 'db_conn.php'; // Ensure this file contains the code to connect to your MySQL database

// Check if the date parameter is provided
if (!isset($_GET['date'])) {
    echo json_encode(['error' => 'No date provided.']);
    exit();
}

// Get the date from the query parameters
$date = $_GET['date'];

// Prepare and execute the SQL query to get the forecast data for the specified date
$sql = "SELECT date, temperature_forecast, humidity_forecast, heat_index_forecast
        FROM forecast_data 
        WHERE date = ?";

// Prepare the statement
$stmt = $link->prepare($sql);

if ($stmt) {
    // Bind the date parameter
    $stmt->bind_param("s", $date);
    $stmt->execute();
    
    // Get the result
    $result = $stmt->get_result();
    
    // Check if any data was returned
    if ($result->num_rows > 0) {
        // Fetch the data into an array
        $forecastData = $result->fetch_all(MYSQLI_ASSOC);
        
        // Output the result as JSON
        echo json_encode($forecastData);
    } else {
        // No forecast data for the selected date
        echo json_encode(['message' => 'No forecast data available for the selected date.']);
    }
    
    // Close the statement
    $stmt->close();
} else {
    // Prepare failed
    echo json_encode(['error' => 'Failed to prepare the SQL statement.']);
}

// Close the database connection
$link->close();
?>
