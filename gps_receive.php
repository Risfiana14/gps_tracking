<?php
// Debug: tampilkan data yang diterima
error_log("POST data: " . print_r($_POST, true));
error_log("GET data: " . print_r($_GET, true));
error_log("Raw input: " . file_get_contents('php://input'));

// Dapatkan data GPS dari POST atau GET dengan validasi
$lat = isset($_POST['lat']) ? $_POST['lat'] : (isset($_GET['lat']) ? $_GET['lat'] : null);
$lon = isset($_POST['lon']) ? $_POST['lon'] : (isset($_GET['lon']) ? $_GET['lon'] : null);
$speed = isset($_POST['speed']) ? $_POST['speed'] : (isset($_GET['speed']) ? $_GET['speed'] : 0);
$device_name = isset($_POST['device_name']) ? $_POST['device_name'] : (isset($_GET['device_name']) ? $_GET['device_name'] : 'UNKNOWN'); 

// Hapus unit "km/h" dari speed jika ada
$speed = str_replace(' km/h', '', $speed);
$speed = trim($speed);

// Validasi data GPS
if ($lat === null || $lon === null) {
    echo "Error: Data GPS tidak lengkap";
    exit;
}

// Koneksi ke MySQL
$conn = new mysqli('localhost', 'root', '', 'gps_tracking');

// Cek koneksi
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Query untuk memasukkan data ke MySQL
$sql = "INSERT INTO gps_data (latitude, longitude, speed, device_name) VALUES ('$lat', '$lon', '$speed', '$device_name')";

if ($conn->query($sql) === TRUE) {
    // Opsi 1: Hapus data lebih dari 7 hari
    $delete_old = "DELETE FROM gps_data WHERE timestamp < DATE_SUB(NOW(), INTERVAL 7 DAY)";
    $deleted_old = $conn->query($delete_old);
    
    // Opsi 2: Batasi maksimal 1000 record (hapus yang paling lama)
    $count_query = "SELECT COUNT(*) as total FROM gps_data";
    $count_result = $conn->query($count_query);
    $count_row = $count_result->fetch_assoc();
    
    $deleted_excess = 0;
    if ($count_row['total'] > 1000) {
        $excess_count = $count_row['total'] - 1000;
        $delete_excess = "DELETE FROM gps_data ORDER BY timestamp ASC LIMIT " . $excess_count;
        if ($conn->query($delete_excess)) {
            $deleted_excess = $excess_count;
        }
    }
    
    // Response untuk RouterOS dengan informasi cleanup
    $response = array(
        'status' => 'success',
        'message' => 'Data berhasil disimpan',
        'cleanup' => array(
            'old_data_deleted' => $deleted_old ? 'yes' : 'no',
            'excess_deleted' => $deleted_excess,
            'total_records' => $count_row['total'],
            'suggestion' => 'RouterOS: Bersihkan log lama dengan /log print where time<(now-7d) | remove'
        )
    );
    
    // Kirim response dalam format yang mudah dibaca RouterOS
    echo "SUCCESS: Data saved. DB cleaned. Total records: " . $count_row['total'];
    if ($deleted_excess > 0) {
        echo ". Deleted " . $deleted_excess . " old records";
    }
    
} else {
    echo "ERROR: " . $conn->error;
}

$conn->close();
?>
