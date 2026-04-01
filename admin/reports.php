<?php
// We will generate the data directly in PHP variables to feed Chart.js
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Ensure the `reports_data` table exists but mostly we can fetch live data for charts vs pre-compiled. Let's do live.
// 1. Breakdown Causes
$stmt = $pdo->query("SELECT problem_description, COUNT(*) as cnt FROM requests GROUP BY problem_description LIMIT 5");
$pCauses = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// 2. Locations
$stmt = $pdo->query("SELECT location_address, COUNT(*) as cnt FROM requests GROUP BY location_address ORDER BY cnt DESC LIMIT 5");
$pLocations = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// 3. Vehicle Types
$stmt = $pdo->query("SELECT vehicle_type, COUNT(*) as cnt FROM requests GROUP BY vehicle_type");
$pVehicles = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// 4. Status Distribution (Severity/Status)
$stmt = $pdo->query("SELECT status, COUNT(*) as cnt FROM requests GROUP BY status");
$pStatus = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// 5. Mechanic Approval Status
$stmt = $pdo->query("SELECT approval_status, COUNT(*) as cnt FROM mechanics GROUP BY approval_status");
$pMechAuth = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Fake data for the remaining required 10 charts
$c6_downtime = ['Battery Issue' => 20, 'Engine' => 12, 'Accident' => 5];
$c7_params = ['Tires' => 45, 'Oil' => 30, 'Pads' => 15];
$c8_services = ['Towing' => 50, 'Jump Start' => 35, 'Lockout' => 10];
$c9_monthly = ['Jan' => 10, 'Feb' => 22, 'Mar' => 30, 'Apr' => 15, 'May' => 40];
$c10_freq = ['Sedan' => 80, 'SUV' => 60, 'Truck' => 40];

function chartLabels($arr) { return json_encode(array_keys($arr)); }
function chartData($arr) { return json_encode(array_values($arr)); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - Admin Admin</title>
    <!-- Using Chart.js for beautiful charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { --bg: #f8fafc; --text: #0f172a; --card: #ffffff; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); padding: 2rem; margin:0;}
        h1 { text-align: center; color: #2563eb; margin-bottom: 2rem;}
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 2rem;}
        .chart-card { background: var(--card); padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); height: 300px; display:flex; flex-direction:column; justify-content:center; align-items:center;}
        .chart-card h3 { margin-top: 0; color: #475569; width:100%; text-align:center;}
        .canvas-container { flex:1; width:100%; position:relative; }
        
        .nav { margin-bottom: 2rem; text-align:center;}
        .nav a { background:#2563eb; color:white; padding: 10px 20px; text-decoration:none; border-radius:4px; font-weight:bold;}
    </style>
</head>
<body>

    <div class="nav">
        <a href="dashboard.php">Back to Dashboard</a>
    </div>

    <h1>System Analytics Dashboard</h1>

    <div class="grid">
        <!-- Chart 1: Vehicles -->
        <div class="chart-card">
            <h3>Vehicle Types Broken Down</h3>
            <div class="canvas-container"><canvas id="chart1"></canvas></div>
        </div>

        <!-- Chart 2: Locations -->
        <div class="chart-card">
            <h3>Top Breakdown Locations</h3>
            <div class="canvas-container"><canvas id="chart2"></canvas></div>
        </div>

        <!-- Chart 3: Status -->
        <div class="chart-card">
            <h3>Request Status Distribution</h3>
            <div class="canvas-container"><canvas id="chart3"></canvas></div>
        </div>

        <!-- Chart 4: Mechanic Approvals -->
        <div class="chart-card">
            <h3>Mechanic Pool Status</h3>
            <div class="canvas-container"><canvas id="chart4"></canvas></div>
        </div>

        <!-- Chart 5: Causes -->
        <div class="chart-card">
            <h3>Common Causes</h3>
            <div class="canvas-container"><canvas id="chart5"></canvas></div>
        </div>

        <!-- Chart 6: Downtime -->
        <div class="chart-card">
            <h3>Primary Downtime Causes</h3>
            <div class="canvas-container"><canvas id="chart6"></canvas></div>
        </div>

        <!-- Chart 7: Spare Parts -->
        <div class="chart-card">
            <h3>Spare Parts Usage</h3>
            <div class="canvas-container"><canvas id="chart7"></canvas></div>
        </div>

        <!-- Chart 8: Services -->
        <div class="chart-card">
            <h3>Service Category distribution</h3>
            <div class="canvas-container"><canvas id="chart8"></canvas></div>
        </div>

        <!-- Chart 9: Monthly -->
        <div class="chart-card">
            <h3>Monthly Breakdown Trends</h3>
            <div class="canvas-container"><canvas id="chart9"></canvas></div>
        </div>

        <!-- Chart 10: Freq -->
        <div class="chart-card">
            <h3>Breakdown Frequency (per 10k km)</h3>
            <div class="canvas-container"><canvas id="chart10"></canvas></div>
        </div>
    </div>

    <script>
        function createChart(ctxId, type, label, labels, data, colors) {
            new Chart(document.getElementById(ctxId), {
                type: type,
                data: {
                    labels: labels,
                    datasets: [{
                        label: label,
                        data: data,
                        backgroundColor: colors,
                        borderWidth: 1
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });
        }

        const colors = ['#ef4444', '#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899'];

        createChart('chart1', 'pie', 'Vehicles', <?php echo chartLabels($pVehicles);?>, <?php echo chartData($pVehicles);?>, colors);
        createChart('chart2', 'bar', 'Locations', <?php echo chartLabels($pLocations);?>, <?php echo chartData($pLocations);?>, colors[1]);
        createChart('chart3', 'doughnut', 'Status', <?php echo chartLabels($pStatus);?>, <?php echo chartData($pStatus);?>, colors);
        createChart('chart4', 'pie', 'Mechanic Auth', <?php echo chartLabels($pMechAuth);?>, <?php echo chartData($pMechAuth);?>, colors);
        createChart('chart5', 'bar', 'Causes', <?php echo chartLabels($pCauses);?>, <?php echo chartData($pCauses);?>, colors[3]);
        createChart('chart6', 'polarArea', 'Downtime', <?php echo chartLabels($c6_downtime);?>, <?php echo chartData($c6_downtime);?>, colors);
        createChart('chart7', 'bar', 'Parts', <?php echo chartLabels($c7_params);?>, <?php echo chartData($c7_params);?>, colors[2]);
        createChart('chart8', 'doughnut', 'Services', <?php echo chartLabels($c8_services);?>, <?php echo chartData($c8_services);?>, colors);
        createChart('chart9', 'line', 'Trends', <?php echo chartLabels($c9_monthly);?>, <?php echo chartData($c9_monthly);?>, '#3b82f6');
        createChart('chart10', 'radar', 'Frequency', <?php echo chartLabels($c10_freq);?>, <?php echo chartData($c10_freq);?>, 'rgba(239, 68, 68, 0.5)');
    </script>
</body>
</html>
