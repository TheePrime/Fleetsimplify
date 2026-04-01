<?php
session_start();
require_once '../config/db.php';

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
    <style>
        body { font-family: 'Poppins', 'Inter', 'Roboto', sans-serif; background: #f8fafc; margin:0; padding:2rem; color: #0f172a; }
        .card { max-width: 600px; margin: 0 auto; background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .info-box { background: #e0f2fe; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;}
        .map-placeholder { width: 100%; height: 300px; background: #e2e8f0; border-radius: 8px; display:flex; justify-content:center; align-items:center; flex-direction: column;}
        .btn { display: inline-block; padding: 0.5rem 1rem; background: #2563eb; color: white; text-decoration: none; border-radius: 4px; margin-top: 1rem;}
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

        <!-- Fake Map Representation since we can't load Google Maps easily without an API key -->
        <div class="map-placeholder">
            <h3 style="margin: 0;">🗺️ Live Map Tracking</h3>
            <p style="color:#64748b;">Distance: <span id="distance">Calculating...</span></p>
            <p style="color:#64748b;">ETA: <span id="eta">Calculating...</span></p>
        </div>

        <div style="margin-top: 1rem; display: flex; gap: 1rem;">
            <a href="dashboard.php" class="btn" style="background: #e2e8f0; color: #0f172a;">Back to Dashboard</a>
            <a href="../chat/chat_ui.php?request_id=<?php echo $request_id; ?>" class="btn">Chat with Mechanic</a>
        </div>
    </div>

    <!-- Data passing to JS -->
    <script>
        const reqId = <?php echo json_encode($request_id); ?>;
        const driverLat = <?php echo json_encode((float)$requestData['location_lat']); ?>;
        const driverLng = <?php echo json_encode((float)$requestData['location_lng']); ?>;
        
        // Polling function to get updated mechanic location
        function fetchMechanicLocation() {
            fetch(`get_mechanic_location.php?req_id=${reqId}`)
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    document.getElementById('reqStatus').innerText = data.data.status;
                    
                    if(data.data.mech_lat && data.data.mech_lng) {
                        // Simple Haversine formula calculation for distance
                        const dist = calcCrow(driverLat, driverLng, data.data.mech_lat, data.data.mech_lng);
                        document.getElementById('distance').innerText = dist.toFixed(2) + " km away";
                        
                        // Roughly 1 km = 2 mins in city traffic
                        const mins = Math.ceil(dist * 2);
                        document.getElementById('eta').innerText = mins + " mins";
                    } else {
                        document.getElementById('distance').innerText = "Location unavailable";
                        document.getElementById('eta').innerText = "--";
                    }
                }
            })
            .catch(err => console.error(err));
        }

        // Haversine formula
        function calcCrow(lat1, lon1, lat2, lon2) {
            var R = 6371; // km
            var dLat = toRad(lat2-lat1);
            var dLon = toRad(lon2-lon1);
            var lat1 = toRad(lat1);
            var lat2 = toRad(lat2);
            var a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                Math.sin(dLon/2) * Math.sin(dLon/2) * Math.cos(lat1) * Math.cos(lat2); 
            var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a)); 
            return R * c;
        }
        function toRad(Value) { return Value * Math.PI / 180; }

        // Start polling every 5 seconds
        fetchMechanicLocation(); // Initial call
        setInterval(fetchMechanicLocation, 5000);
    </script>
</body>
</html>
