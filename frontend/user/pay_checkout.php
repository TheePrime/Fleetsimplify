<?php
session_start();
require_once '../../backend/config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    header("Location: ../login.php");
    exit();
}

$request_id = $_GET['id'] ?? null;
$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['user_email'] ?? 'driver@example.com'; // We need email for Paystack

if (!$request_id) {
    echo "Invalid Request ID";
    exit();
}

// Fetch request
$stmt = $pdo->prepare("
    SELECT r.*, u.email as driver_email, m.name as mechanic_name
    FROM requests r
    JOIN users u ON r.driver_id = u.id
    LEFT JOIN mechanics mech ON r.mechanic_id = mech.id
    LEFT JOIN users m ON mech.user_id = m.id
    WHERE r.id = ? AND r.driver_id = ?
");
$stmt->execute([$request_id, $user_id]);
$requestData = $stmt->fetch();

if (!$requestData) {
    echo "Request not found.";
    exit();
}

if ($requestData['payment_status'] === 'Paid') {
    header("Location: dashboard.php?tab=requests");
    exit();
}

$driver_email = $requestData['driver_email'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout - Paystack</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f8fafc; margin: 0; display: flex; align-items: center; justify-content: center; min-height: 100vh; color: #0f172a; }
        .card { background: white; padding: 2.5rem; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); width: 100%; max-width: 450px; text-align: center; }
        .info { background: #e0f2fe; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; text-align: left; font-size: 0.9rem; }
        .form-group { margin-bottom: 1.5rem; text-align: left; }
        label { display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.9rem; }
        input[type="number"] { width: 100%; padding: 0.8rem; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 1rem; font-family: inherit; box-sizing: border-box; }
        input[type="number"]:focus { outline: none; border-color: #0ea5e9; }
        .btn { display: block; width: 100%; padding: 0.9rem; background: #10b981; color: white; text-align: center; font-weight: 600; font-size: 1rem; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; transition: background 0.2s; }
        .btn:hover { background: #059669; }
        .btn-cancel { background: #94a3b8; margin-top: 1rem; }
        .btn-cancel:hover { background: #64748b; }
        
        #loadingMsg { display: none; margin-top: 1rem; font-size: 0.85rem; color: #0ea5e9; font-weight: 500; }
    </style>
</head>
<body>

<div class="card">
    <h2 style="margin-top:0;">Secure Payment</h2>
    
    <div class="info">
        <strong>Service:</strong> Roadside Assistance<br>
        <strong>Mechanic:</strong> <?= htmlspecialchars($requestData['mechanic_name'] ?? 'Unknown') ?><br>
        <strong>Vehicle:</strong> <?= htmlspecialchars($requestData['vehicle_type']) ?>
    </div>

    <form id="paymentForm">
        <div class="form-group">
            <label for="amount">Enter Agreed Amount (KES)</label>
            <input type="number" id="amount" required min="1" placeholder="e.g. 1500">
        </div>
        
        <button type="submit" class="btn">Pay with Paystack</button>
        <a href="dashboard.php?tab=requests" class="btn btn-cancel">Cancel</a>
    </form>

    <div id="loadingMsg">Verifying transaction, please wait...</div>
</div>

<script src="https://js.paystack.co/v1/inline.js"></script>
<script>
const paymentForm = document.getElementById('paymentForm');
const amountInput = document.getElementById('amount');
const loadingMsg = document.getElementById('loadingMsg');

paymentForm.addEventListener('submit', function(e) {
    e.preventDefault();
    payWithPaystack();
});

function payWithPaystack() {
    const amountStr = amountInput.value.trim();
    if (!amountStr || isNaN(amountStr) || parseFloat(amountStr) <= 0) {
        alert("Please enter a valid amount.");
        return;
    }

    const amountKobo = Math.round(parseFloat(amountStr) * 100); // Paystack expects lowest currency unit (e.g. Kobo/Cents). For KES, it's cents.
    
    let handler = PaystackPop.setup({
        key: 'pk_test_YOUR_PAYSTACK_PUBLIC_KEY', // Replace with your public key
        email: '<?= htmlspecialchars($driver_email) ?>',
        amount: amountKobo,
        currency: 'KES',
        ref: 'REQ_' + <?= $request_id ?> + '_' + Math.floor((Math.random() * 1000000000) + 1),
        onClose: function() {
            alert('Payment window closed.');
        },
        callback: function(response) {
            // Payment completed! Verify on backend.
            loadingMsg.style.display = 'block';
            paymentForm.style.display = 'none';

            fetch('../../backend/api/verify_paystack.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    reference: response.reference,
                    request_id: <?= $request_id ?>,
                    amount: parseFloat(amountStr)
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('Payment successful!');
                    window.location.href = 'dashboard.php?tab=requests';
                } else {
                    alert('Verification failed: ' + data.message);
                    window.location.reload();
                }
            })
            .catch(err => {
                console.error(err);
                alert('An error occurred while verifying the payment.');
                window.location.reload();
            });
        }
    });

    handler.openIframe();
}
</script>

</body>
</html>
