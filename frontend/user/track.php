<?php
session_start();
require_once '../../backend/config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    header("Location: ../login.php");
    exit();
}

$request_id = $_GET['id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$request_id) {
    echo "Invalid Request ID";
    exit();
}

// Fetch request and mechanic details
$stmt = $pdo->prepare("
    SELECT r.*, m.latitude as mech_lat, m.longitude as mech_lng, u.name as mechanic_name, u.phone as mechanic_phone
    FROM requests r
    JOIN mechanics m ON r.mechanic_id = m.id
    JOIN users u ON m.user_id = u.id
    WHERE r.id = ? AND r.driver_id = ?
");
$stmt->execute([$request_id, $user_id]);
$requestData = $stmt->fetch();

if (!$requestData) {
    echo "Request not found or not assigned yet.";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Track Mechanic - Roadside Assistance</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
    <style>
        body { font-family: 'Poppins', 'Inter', 'Roboto', sans-serif; background: #f8fafc; margin:0; padding:2rem; color: #0f172a; }
        .card { max-width: 800px; margin: 0 auto; background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .info-box { background: #e0f2fe; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;}
        #map { width: 100%; height: 400px; background: #e2e8f0; border-radius: 8px; z-index: 1; }
        .btn { display: inline-block; padding: 0.6rem 1.2rem; background: #2563eb; color: white; text-decoration: none; border-radius: 4px; margin-top: 1rem; font-weight: 500; }
        .btn-gray { background: #e2e8f0; color: #0f172a; }
        .stats-bar { display: flex; justify-content: space-between; margin-top: 1rem; background: #f1f5f9; padding: 1rem; border-radius: 6px; font-weight: 500; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Live Tracking</h2>
        
        <div class="info-box">
            <p><strong>Mechanic:</strong> <?php echo htmlspecialchars($requestData['mechanic_name']); ?></p>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($requestData['mechanic_phone']); ?></p>
            <p><strong>Status:</strong> <span id="reqStatus" style="font-weight:bold; color:#d97706;"><?php echo htmlspecialchars($requestData['status']); ?></span></p>
        </div>

        <div id="map"></div>

        <div class="stats-bar">
            <div>Distance: <span id="distance" style="color: #2563eb;">Calculating...</span></div>
            <div>ETA: <span id="eta" style="color: #059669;">Calculating...</span></div>
        </div>

        <div style="margin-top: 1.5rem; display: flex; gap: 1rem;">
            <a href="dashboard.php" class="btn btn-gray">Back to Dashboard</a>
            <a href="../chat/chat_ui.php?request_id=<?php echo $request_id; ?>" class="btn">💬 Chat with Mechanic</a>
        </div>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script>
        const reqId = <?php echo json_encode($request_id); ?>;
        const initialDriverLat = <?php echo json_encode((float)$requestData['location_lat']); ?>;
        const initialDriverLng = <?php echo json_encode((float)$requestData['location_lng']); ?>;
        
        let map, driverMarker, mechanicMarker;
        let routeLine = null;

        // Custom Marker Icons
        const driverIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41]
        });

        const mechanicIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-orange.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41]
        });

        function initMap() {
            // Initialize Leaflet Map
            map = L.map('map').setView([initialDriverLat, initialDriverLng], 14);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            driverMarker = L.marker([initialDriverLat, initialDriverLng], {icon: driverIcon}).addTo(map)
                .bindPopup("Your Location");

            // Mechanic marker initially hidden until coordinates fetched
            mechanicMarker = L.marker([initialDriverLat, initialDriverLng], {icon: mechanicIcon});

            // Start polling every 5 seconds
            fetchLocations(); 
            setInterval(fetchLocations, 5000);
        }

        function fetchLocations() {
            fetch(`../../backend/api/get_location.php?req_id=${reqId}`)
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    document.getElementById('reqStatus').innerText = data.data.status;
                    
                    if(data.data.mech_lat && data.data.mech_lng) {
                        const mechLat = parseFloat(data.data.mech_lat);
                        const mechLng = parseFloat(data.data.mech_lng);
                        const driverLat = parseFloat(data.data.driver_lat);
                        const driverLng = parseFloat(data.data.driver_lng);

                        if(!map.hasLayer(mechanicMarker)) {
                            mechanicMarker.addTo(map).bindPopup("Mechanic Location");
                        }
                        
                        mechanicMarker.setLatLng([mechLat, mechLng]);

                        // Draw a straight line between the two points
                        if (routeLine) {
                            map.removeLayer(routeLine);
                        }
                        routeLine = L.polyline([[driverLat, driverLng], [mechLat, mechLng]], {color: '#2563eb', weight: 4}).addTo(map);

                        // Fit bounds so both markers are visible
                        const group = new L.featureGroup([driverMarker, mechanicMarker]);
                        map.fitBounds(group.getBounds().pad(0.2));

                        // Calculate straight-line distance and ETA
                        calculateDistanceAndETA(driverLat, driverLng, mechLat, mechLng);
                    } else {
                        document.getElementById('distance').innerText = "Location unavailable";
                        document.getElementById('eta').innerText = "--";
                    }
                }
            })
            .catch(err => console.error("Error fetching location:", err));
        }

        // Haversine formula to accurately calculate direct distance between driver and mechanic
        function calculateDistanceAndETA(lat1, lon1, lat2, lon2) {
            var R = 6371; // km
            var dLat = toRad(lat2-lat1);
            var dLon = toRad(lon2-lon1);
            var l1 = toRad(lat1);
            var l2 = toRad(lat2);
            var a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                Math.sin(dLon/2) * Math.sin(dLon/2) * Math.cos(l1) * Math.cos(l2); 
            var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a)); 
            var dist = R * c;
            
            document.getElementById('distance').innerText = dist.toFixed(2) + " km away";
            // Rough city driving estimate: ~30km/h average speed (1 km = 2 mins)
            const mins = Math.ceil(dist * 2);
            document.getElementById('eta').innerText = "~" + mins + " mins estimated";
        }
        
        function toRad(Value) { return Value * Math.PI / 180; }

        // Start initialization on window load
        window.onload = initMap;
    </script>
</body>
</html>
