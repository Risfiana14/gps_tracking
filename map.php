<?php
require_once 'config.php';
?>

<!-- Map Section -->
<div class="map-section">
    <h3 class="section-title"><i class="icon">📍</i> Live Location</h3>
    <div id="map"></div>
</div>

<!-- Tambahkan Leaflet JS -->
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<script>
    // Peta dengan koordinat awal
    var map = L.map('map').setView([0, 0], 2);

    // Clean map dengan layer putih bersih
    var cleanLayer = L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        attribution: false,
        subdomains: 'abcd',
        maxZoom: 19
    }).addTo(map);
    
    // Variabel untuk historical track
    var trackCoordinates = [];
    var trackPolyline = null;
    
    // Custom marker icon dengan tema gelap dan icon kereta
    var customIcon = L.divIcon({
        className: 'custom-marker',
        html: '<div style="background: linear-gradient(135deg, #3498db, #2980b9); width: 24px; height: 24px; border-radius: 50%; border: 2px solid #34495e; box-shadow: 0 3px 8px rgba(52, 152, 219, 0.4); display: flex; align-items: center; justify-content: center;"><i class="fas fa-train" style="color: white; font-size: 12px;"></i></div>',
        iconSize: [28, 28],
        iconAnchor: [14, 14]
    });

    // Tidak perlu marker dari PHP, biarkan realtimeUpdate yang handle
    // Marker akan dibuat secara dinamis oleh JavaScript
    
    var currentMarker = null;
    
    // Fungsi untuk menghitung jarak antara dua titik (Haversine formula)
    function calculateDistance(lat1, lon1, lat2, lon2) {
        const R = 6371; // Radius bumi dalam km
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                Math.sin(dLon/2) * Math.sin(dLon/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return R * c;
    }
    
    // Fungsi untuk memuat dan menampilkan historical track
    function loadHistoricalTrack() {
        fetch('ajax_historical.php')
            .then(response => response.json())
            .then(data => {
                if (data && data.length > 0) {
                    trackCoordinates = [];
                    
                    // Konversi data ke koordinat
                    for (let i = 0; i < data.length; i++) {
                        const point = [parseFloat(data[i].latitude), parseFloat(data[i].longitude)];
                        trackCoordinates.push(point);
                    }
                    
                    // Hapus polyline lama jika ada
                    if (trackPolyline) {
                        map.removeLayer(trackPolyline);
                    }
                    
                    // Buat polyline untuk jejak perjalanan
                    trackPolyline = L.polyline(trackCoordinates, {
                        color: '#e74c3c',
                        weight: 3,
                        opacity: 0.8,
                        smoothFactor: 1
                    }).addTo(map);
                    
                    // Jangan buat marker di sini, biarkan realtimeUpdate yang handle marker real-time
                    // Historical track hanya menampilkan polyline saja
                    
                    // Tidak ada auto zoom untuk menghindari gangguan pada user
                    // Biarkan user mengatur zoom sendiri
                }
            })
            .catch(error => {
                console.log('Error loading track data:', error);
            });
    }
    
    // Fungsi untuk update marker peta (tetap ada untuk kompatibilitas)
    function updateMapMarker() {
        // Gunakan loadHistoricalTrack untuk update yang lebih lengkap
        loadHistoricalTrack();
    }
    
    // Variabel untuk animasi smooth movement
    var isAnimating = false;
    var lastKnownPosition = null;
    var userInteracted = false;
    var realtimeMarker = null; // Marker khusus untuk real-time tracking
    var interactionTimer = null;
    
    // Deteksi interaksi user dengan peta - auto follow kembali setelah 5 detik
    function resetAutoFollow() {
        userInteracted = true;
        if (interactionTimer) {
            clearTimeout(interactionTimer);
        }
        // Kembali auto follow setelah 5 detik tidak ada interaksi
        interactionTimer = setTimeout(function() {
            userInteracted = false;
        }, 5000);
    }
    
    map.on('zoomstart', resetAutoFollow);
    map.on('movestart', resetAutoFollow);
    map.on('dragstart', resetAutoFollow);
    
    // Fungsi untuk animasi smooth movement marker
    function animateMarkerTo(newLatLng, duration = 1000) {
        if (!currentMarker || isAnimating) return;
        
        isAnimating = true;
        const startLatLng = currentMarker.getLatLng();
        const startTime = Date.now();
        
        function animate() {
            const elapsed = Date.now() - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            // Interpolasi linear antara posisi lama dan baru
            const lat = startLatLng.lat + (newLatLng.lat - startLatLng.lat) * progress;
            const lng = startLatLng.lng + (newLatLng.lng - startLatLng.lng) * progress;
            
            currentMarker.setLatLng([lat, lng]);
            
            if (progress < 1) {
                requestAnimationFrame(animate);
            } else {
                isAnimating = false;
            }
        }
        
        animate();
    }
    
    // Fungsi realtime update dengan animasi smooth
    function realtimeUpdate() {
        fetch('ajax_gps.php')
            .then(response => response.json())
            .then(data => {
                if (data && data.latitude && data.longitude) {
                    const newPosition = [parseFloat(data.latitude), parseFloat(data.longitude)];
                    
                    // Jika marker real-time belum ada, buat marker baru
                    if (!realtimeMarker) {
                        realtimeMarker = L.marker(newPosition, {icon: customIcon})
                            .addTo(map)
                            .bindPopup('<div style="text-align: center; font-weight: bold;"><i class="fas fa-map-marker-alt" style="color: #e74c3c;"></i> Lokasi Real-time<br><small>' + data.timestamp + '</small><br><small>Speed: ' + parseFloat(data.speed).toFixed(1) + ' km/h</small></div>');
                        // Hanya set view jika user belum berinteraksi
                        if (!userInteracted) {
                            map.setView(newPosition, 16);
                        }
                        lastKnownPosition = newPosition;
                        currentMarker = realtimeMarker; // Untuk kompatibilitas dengan animasi
                    } else {
                        // Animasi smooth movement ke posisi baru
                        const currentPos = realtimeMarker.getLatLng();
                        const distance = map.distance(currentPos, newPosition);
                        
                        // Hanya animate jika ada pergerakan signifikan (> 1 meter)
                        if (distance > 1) {
                            currentMarker = realtimeMarker; // Set untuk animasi
                            animateMarkerTo({lat: newPosition[0], lng: newPosition[1]}, 800);
                            
                            // Update popup dengan data terbaru
                            realtimeMarker.setPopupContent('<div style="text-align: center; font-weight: bold;"><i class="fas fa-map-marker-alt" style="color: #e74c3c;"></i> Lokasi Real-time<br><small>' + data.timestamp + '</small><br><small>Speed: ' + parseFloat(data.speed).toFixed(1) + ' km/h</small></div>');
                            
                            // Update garis merah secara real-time
                            if (trackCoordinates.length > 0) {
                                trackCoordinates.push(newPosition);
                                if (trackPolyline) {
                                    map.removeLayer(trackPolyline);
                                }
                                trackPolyline = L.polyline(trackCoordinates, {
                                    color: '#e74c3c',
                                    weight: 3,
                                    opacity: 0.8,
                                    smoothFactor: 1
                                }).addTo(map);
                            }
                            
                            // Auto-follow marker tanpa mengubah zoom level
                            if (!userInteracted) {
                                // Gunakan panTo untuk mengikuti tanpa mengubah zoom
                                map.panTo(newPosition);
                            }
                        }
                        
                        lastKnownPosition = newPosition;
                    }
                }
            })
            .catch(error => {
                console.log('Error in realtime update:', error);
            });
    }
    
    // Load initial track
    loadHistoricalTrack();
    
    // Realtime update setiap 1 detik untuk tracking smooth seperti Google Maps
    setInterval(realtimeUpdate, 1000);
    
    // Update historical track setiap 30 detik (lebih jarang karena tidak perlu sering)
    setInterval(loadHistoricalTrack, 30000);
</script>