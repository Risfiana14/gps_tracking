<?php
// Konfigurasi database
$host = 'localhost';
$db = 'gps_tracking';
$user = 'root';
$pass = '';

// Koneksi ke database
$conn = new mysqli($host, $user, $pass, $db);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Fungsi auto-cleanup database
function auto_cleanup_database($conn) {
    // Hapus data lebih dari 7 hari
    $cleanup_old = "DELETE FROM gps_data WHERE timestamp < DATE_SUB(NOW(), INTERVAL 7 DAY)";
    $conn->query($cleanup_old);
    
    // Batasi maksimal 1000 record (hapus yang paling lama)
    $count_query = "SELECT COUNT(*) as total FROM gps_data";
    $count_result = $conn->query($count_query);
    if ($count_result) {
        $count_row = $count_result->fetch_assoc();
        if ($count_row['total'] > 1000) {
            $delete_excess = "DELETE FROM gps_data ORDER BY timestamp ASC LIMIT " . ($count_row['total'] - 1000);
            $conn->query($delete_excess);
        }
    }
}

// Jalankan auto-cleanup
auto_cleanup_database($conn);
?>