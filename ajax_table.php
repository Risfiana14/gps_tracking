<?php
require_once 'config.php';

// Set header untuk HTML response
header('Content-Type: text/html; charset=utf-8');

// Query untuk mengambil data GPS terbaru
$sql = "SELECT * FROM gps_data ORDER BY timestamp DESC";
$result = $conn->query($sql);

// Generate HTML untuk tbody
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . date('H:i:s - d-m-Y', strtotime($row["timestamp"])) . "</td>";
        echo "<td>" . number_format((float)$row["latitude"], 6) . "</td>";
        echo "<td>" . number_format((float)$row["longitude"], 6) . "</td>";
        echo "<td>" . number_format((float)$row["speed"], 1) . "</td>";
        echo "<td>" . htmlspecialchars($row["device_name"] ?? '') . "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='5' class='no-data'>No GPS data available</td></tr>";
}

$conn->close();
?>