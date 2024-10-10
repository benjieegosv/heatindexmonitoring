<?php
include 'db_conn.php';

if (isset($_GET['deviceId'])) {
    $deviceId = intval($_GET['deviceId']);

    // Fetch latest monitoring data for the selected device
    $sql = "SELECT m.*, d.location 
            FROM monitoring m 
            JOIN device_info d ON m.deviceId = d.deviceId 
            WHERE m.deviceId = ? 
            ORDER BY m.timestamp DESC 
            LIMIT 1";
    
    $stmt = $link->prepare($sql);
    $stmt->bind_param("i", $deviceId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $data = $result->fetch_assoc();
        echo json_encode($data);
    } else {
        echo json_encode(['error' => 'No monitoring data available.']);
    }

    $stmt->close();
}

$link->close();