<?php
// Include database connection
include 'db_conn.php';

// Retrieve the heat index from the query parameter
if (isset($_GET['heatIndex'])) {
    $heatIndex = floatval($_GET['heatIndex']);

    // Query to fetch safety precautions based on heat index using minHeatIndex and maxHeatIndex from heat_index_levels table
    $sql = "SELECT classification, description, safety_precautions 
            FROM heat_index_levels 
            WHERE ? BETWEEN minHeatIndex AND maxHeatIndex
            LIMIT 1";

    $stmt = $link->prepare($sql);
    
    // Bind the heat index value to the query
    $stmt->bind_param("d", $heatIndex);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $precautions = $result->fetch_assoc();
        // Return the data as JSON
        echo json_encode($precautions);
    } else {
        // If no data is found, return a default response
        echo json_encode([
            'classification' => 'Unknown',
            'description' => 'No data available for this heat index.',
            'safety_precautions' => 'Please be cautious.'
        ]);
    }

    $stmt->close();
    $link->close();
} else {
    // If heatIndex is not provided, return an error response
    echo json_encode([
        'classification' => 'Error',
        'description' => 'Heat index not provided.',
        'safety_precautions' => 'Please provide a valid heat index.'
    ]);
}
