<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    header('Location: ../login.php');
    exit();
}

$request_id = $_GET['id'] ?? null;
$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['user_email'] ?? 'driver@example.com';

if (!$request_id) {
    echo 'Invalid Request ID';
    exit();
}

$stmt = $pdo->prepare("\n    SELECT r.*, u.email AS driver_email, m.name AS mechanic_name\n    FROM requests r\n    JOIN users u ON r.driver_id = u.id\n    LEFT JOIN mechanics mech ON r.mechanic_id = mech.id\n    LEFT JOIN users m ON mech.user_id = m.id\n    WHERE r.id = ? AND r.driver_id = ?\n");
$stmt->execute([$request_id, $user_id]);
$requestData = $stmt->fetch();

if (!$requestData) {
    echo 'Request not found.';
    exit();
}

if ($requestData['payment_status'] === 'Paid') {
    header('Location: dashboard.php?tab=requests');
    exit();
}

if ($requestData['status'] !== 'Completed') {
    echo 'This request is not ready for payment yet.';
    exit();
}

$agreedAmount = isset($requestData['agreed_amount']) ? (float)$requestData['agreed_amount'] : 0;
if ($agreedAmount <= 0) {
    echo 'Invoice amount has not been set by the mechanic yet.';
    exit();
}

$paymentMethod = strtolower($_GET['method'] ?? 'paystack');
$methodLabel = match ($paymentMethod) {
    'mpesa' => 'M-Pesa',
    'card' => 'Card',
    default => 'Secure Checkout',
};

$paystackPublicKey = env_value('PAYSTACK_PUBLIC_KEY', '');

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
        .fixed-amount { font-size: 1.05rem; font-weight: 700; color: #0f172a; margin-top: 0.4rem; }
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
        <strong>Vehicle:</strong> <?= htmlspecialchars($requestData['vehicle_type']) ?><br>
        <strong>Method:</strong> <?= htmlspecialchars($methodLabel) ?><br>
        <strong>Amount to Pay:</strong> <span class="fixed-amount">KES <?= number_format($agreedAmount, 2) ?></span>
    </div>

    <form id="paymentForm">
        <button type="submit" class="btn">Pay KES <?= number_format($agreedAmount, 2) ?> with <?= htmlspecialchars($methodLabel) ?></button>
        <a href="dashboard.php?tab=requests" class="btn btn-cancel">Cancel</a>
    </form>

    <div id="loadingMsg">Verifying transaction, please wait...</div>
</div>

<script src="https://js.paystack.co/v1/inline.js"></script>
<script>
const paymentForm = document.getElementById('paymentForm');
const loadingMsg = document.getElementById('loadingMsg');
const agreedAmount = <?= json_encode($agreedAmount) ?>;
const paystackPublicKey = <?= json_encode($paystackPublicKey) ?>;

paymentForm.addEventListener('submit', function(e) {
    e.preventDefault();
    payWithPaystack();
});

function payWithPaystack() {
    if (!paystackPublicKey) {
        alert('Payment is not configured yet. Missing PAYSTACK_PUBLIC_KEY in .env');
        return;
    }

    const amountKobo = Math.round(parseFloat(agreedAmount) * 100);

    let handler = PaystackPop.setup({
        key: paystackPublicKey,
        email: '<?= htmlspecialchars($driver_email) ?>',
        amount: amountKobo,
        currency: 'KES',
        ref: 'REQ_' + <?= $request_id ?> + '_' + Math.floor((Math.random() * 1000000000) + 1),
        onClose: function() {
            alert('Payment window closed.');
        },
        callback: function(response) {
            loadingMsg.style.display = 'block';
            paymentForm.style.display = 'none';

            fetch('../backend/api/verify_paystack.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    reference: response.reference,
                    request_id: <?= $request_id ?>
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
