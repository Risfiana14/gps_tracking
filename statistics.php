<?php
require_once 'config.php';
?>

<!-- Statistics Section -->
<div class="stats-section">
    <h3 class="section-title"><i class="icon">📊</i> Statistics</h3>
    <?php
    // Hitung statistik dengan query langsung
    $stats_query = "SELECT 
        COUNT(*) as total_records,
        AVG(CASE WHEN speed > 0 THEN speed END) as avg_speed,
        MAX(speed) as max_speed
        FROM gps_data";
    $stats_result = $conn->query($stats_query);
    $stats = $stats_result->fetch_assoc();
    
    $total_records = $stats['total_records'];
    $avg_speed = $stats['avg_speed'];
    $max_speed = $stats['max_speed'];
    
    // Hitung accuracy berdasarkan konsistensi data GPS dari 10 data terakhir
    $accuracy_query = "SELECT latitude, longitude FROM gps_data ORDER BY timestamp DESC LIMIT 10";
    $accuracy_result = $conn->query($accuracy_query);
    $accuracy_percentage = 0;
    
    if ($accuracy_result->num_rows >= 3) {
        $coords = [];
        while($coord = $accuracy_result->fetch_assoc()) {
            $coords[] = ['lat' => (float)$coord['latitude'], 'lng' => (float)$coord['longitude']];
        }
        
        // Hitung jarak rata-rata antar titik berurutan
        $total_distance = 0;
        $distance_count = 0;
        
        for($i = 0; $i < count($coords) - 1; $i++) {
            $lat1 = $coords[$i]['lat'];
            $lng1 = $coords[$i]['lng'];
            $lat2 = $coords[$i+1]['lat'];
            $lng2 = $coords[$i+1]['lng'];
            
            // Hitung jarak menggunakan formula haversine (dalam meter)
            $earth_radius = 6371000; // meter
            $dlat = deg2rad($lat2 - $lat1);
            $dlng = deg2rad($lng2 - $lng1);
            $a = sin($dlat/2) * sin($dlat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dlng/2) * sin($dlng/2);
            $c = 2 * atan2(sqrt($a), sqrt(1-$a));
            $distance = $earth_radius * $c;
            
            $total_distance += $distance;
            $distance_count++;
        }
        
        if ($distance_count > 0) {
            $avg_distance = $total_distance / $distance_count;
            // Accuracy berdasarkan konsistensi: semakin kecil variasi jarak, semakin akurat
            // Asumsi: jarak < 10m = sangat akurat (95-100%), 10-50m = akurat (80-95%), >50m = kurang akurat
            if ($avg_distance < 10) {
                $accuracy_percentage = 95 + (5 * (10 - $avg_distance) / 10);
            } elseif ($avg_distance < 50) {
                $accuracy_percentage = 80 + (15 * (50 - $avg_distance) / 40);
            } else {
                $accuracy_percentage = max(50, 80 - ($avg_distance - 50) / 10);
            }
            $accuracy_percentage = min(100, max(50, $accuracy_percentage));
        }
    }
    ?>
    
    <div class="stat-item">
        <i class="icon">🚗</i>
        <span class="stat-label">Total Records:</span>
        <span class="stat-value"><?php echo $total_records; ?></span>
    </div>
    
    <div class="stat-item">
        <i class="icon">⚡</i>
        <span class="stat-label">Average Speed:</span>
        <span class="stat-value"><?php echo number_format((float)$avg_speed, 1); ?> km/h</span>
    </div>
    
    <div class="stat-item">
        <i class="icon">🚀</i>
        <span class="stat-label">Max Speed:</span>
        <span class="stat-value"><?php echo number_format((float)$max_speed, 1); ?> km/h</span>
    </div>
    
    <div class="stat-item">
        <i class="icon">🎯</i>
        <span class="stat-label">GPS Accuracy:</span>
        <span class="stat-value"><?php echo number_format($accuracy_percentage, 1); ?>%</span>
    </div>
    
    <?php 
    // Query untuk data terbaru untuk statistik
    $sql_latest_stat = "SELECT * FROM gps_data ORDER BY timestamp DESC LIMIT 1";
    $result_latest_stat = $conn->query($sql_latest_stat);
    $latest_stat = $result_latest_stat->num_rows > 0 ? $result_latest_stat->fetch_assoc() : null;
    ?>
    <div class="stat-item">
        <i class="icon">🕒</i>
        <span class="stat-label">Last Update:</span>
        <span class="stat-value"><?php echo $latest_stat ? date('H:i:s - d-m-Y', strtotime($latest_stat['timestamp'])) : 'No data'; ?></span>
    </div>
</div>

<script>
    // Fungsi untuk update statistik
    function updateStatistics() {
        fetch('ajax_stats.php')
            .then(response => response.text())
            .then(html => {
                // Update konten stats-section langsung dengan HTML response
                const currentStatsSection = document.querySelector('.stats-section');
                if (currentStatsSection) {
                    currentStatsSection.innerHTML = html;
                }
            })
            .catch(error => {
                console.log('Error updating statistics:', error);
            });
    }
    
    // Auto-refresh statistik setiap 10 detik
    setInterval(updateStatistics, 10000);
</script>