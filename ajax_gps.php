<?php
require_once 'config.php';

// Handle AJAX request untuk data GPS terbaru
header('Content-Type: application/json');

$sql_ajax = "SELECT * FROM gps_data ORDER BY timestamp DESC LIMIT 1";
$result_ajax = $conn->query($sql_ajax);

if ($result_ajax->num_rows > 0) {
    $row = $result_ajax->fetch_assoc();
    $response = array(
        'latitude' => $row['latitude'],
        'longitude' => $row['longitude'],
        'speed' => $row['speed'],
        'timestamp' => date('H:i:s - d-m-Y', strtotime($row['timestamp']))
    );
    echo json_encode($response);
} else {
    echo json_encode(array('error' => 'No data'));
}

$conn->close();
?>