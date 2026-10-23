<?php
require_once 'config.php';

// Set header untuk JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Diurutkan berdasarkan timestamp untuk membuat jejak yang benar
    $sql = "SELECT latitude, longitude, speed, timestamp 
            FROM gps_data 
            WHERE latitude IS NOT NULL 
            AND longitude IS NOT NULL 
            AND latitude != 0 
            AND longitude != 0
            ORDER BY timestamp ASC";
    $result = $conn->query($sql);
    $trackData = array();
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $trackData[] = array(
                'latitude' => floatval($row['latitude']),
                'longitude' => floatval($row['longitude']),
                'speed' => floatval($row['speed']),
                'timestamp' => $row['timestamp']
            );
        }
    }
    
    // Return data sebagai JSON
    echo json_encode($trackData);
    
} catch (Exception $e) {
    // Return error sebagai JSON
    echo json_encode(array(
        'error' => 'Database error: ' . $e->getMessage()
    ));
}

$conn->close();
?>