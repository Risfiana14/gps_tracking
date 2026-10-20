<?php
require_once 'config.php';
?>

<!-- Data Table Section -->
<div class="data-section">
    <h3 class="section-title"><i class="icon">📋</i> Recent GPS Data</h3>
    
    <div class="table-container" style="max-height: 350px; overflow-y: auto; border-radius: 6px; border: 1px solid #ddd;">
        <table id="gpsTable" style="border: none;">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>Latitude</th>
                    <th>Longitude</th>
                    <th>Speed (km/h)</th>
                    <th>Device Name</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT * FROM gps_data ORDER BY timestamp DESC";
                $result = $conn->query($sql);
                    
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . date('H:i:s - d-m-Y', strtotime($row["timestamp"])) . "</td>";
                        echo "<td>" . number_format((float)$row["latitude"], 6) . "</td>";
                        echo "<td>" . number_format((float)$row["longitude"], 6) . "</td>";
                        echo "<td>" . number_format((float)$row["speed"], 1) . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='4' class='no-data'>No GPS data available</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // Fungsi untuk update tabel data
    function updateDataTable() {
        fetch('ajax_table.php')
            .then(response => response.text())
            .then(html => {
                // Update tbody langsung dengan HTML response
                const currentTableBody = document.querySelector('#gpsTable tbody');
                if (currentTableBody) {
                    currentTableBody.innerHTML = html;
                }
            })
            .catch(error => {
                console.log('Error updating table:', error);
            });
    }
    
    // Auto-refresh data table setiap 10 detik
    setInterval(updateDataTable, 10000);
</script>