<?php
require_once 'config.php';

// Handle AJAX request untuk data GPS terbaru
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
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
        header('Content-Type: application/json');
        echo json_encode($response);
    } else {
        header('Content-Type: application/json');
        echo json_encode(array('error' => 'No data'));
    }
    $conn->close();
    exit;
}

// Query untuk mengambil data GPS terbaru
$sql = "SELECT * FROM gps_data ORDER BY timestamp DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GPS Tracking Dashboard</title>
    <!-- Tambahkan Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <!-- Font Awesome untuk ikon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-satellite-dish icon"></i>GPS Tracking Dashboard</h1>
            <div class="subtitle">Real-time Location Monitoring System</div>
        </div>
        
        <div class="info-grid">
            <!-- Include Map Section -->
            <?php include 'map.php'; ?>

            <!-- Include Data Table Section -->
            <?php include 'data_table.php'; ?>
            
            <!-- Include Statistics Section -->
            <?php include 'statistics.php'; ?>
        </div>
        
        <!-- Footer Information Section -->
        <div class="footer-info">
            <div class="info-cards">
                <div class="info-card">
                    <h4><i class="fas fa-info-circle icon"></i>System Information</h4>
                    <p>GPS Tracking Dashboard v1.0</p>
                    <p>Real-time monitoring with auto-refresh every 10 seconds</p>
                </div>
                <div class="info-card">
                    <h4><i class="fas fa-map-marker-alt icon"></i>Location Accuracy</h4>
                    <p>High precision GPS coordinates</p>
                    <p>Speed tracking in km/h with decimal precision</p>
                </div>
                <div class="info-card">
                    <h4><i class="fas fa-clock icon"></i>Data Updates</h4>
                    <p>Live location tracking</p>
                    <p>Automatic data synchronization</p>
                </div>
            </div>
        </div>




     </div>

    <script>
        // Auto-refresh semua komponen setiap 10 detik
        function refreshAllComponents() {
            // Update map marker
            if (typeof updateMapMarker === 'function') {
                updateMapMarker();
            }
            
            // Update data table
            if (typeof updateDataTable === 'function') {
                updateDataTable();
            }
            
            // Update statistics
            if (typeof updateStatistics === 'function') {
                updateStatistics();
            }
        }
        
        // Refresh setiap 10 detik
        setInterval(refreshAllComponents, 10000);
    </script>

    <?php
    $conn->close();
    ?>
</body>
</html>
