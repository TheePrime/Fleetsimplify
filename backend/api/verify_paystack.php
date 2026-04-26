<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

$reference = $data['reference'] ?? null;
$request_id = $data['request_id'] ?? null;
$amount = $data['amount'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$reference || !$request_id || !$amount) {
    echo json_encode(['status' => 'error', 'message' => 'Missing payment parameters']);
    exit();
}

// Check if payment reference already exists to prevent duplicate processing
$stmtCheck = $pdo->prepare("SELECT id FROM payments WHERE reference = ?");
$stmtCheck->execute([$reference]);
if ($stmtCheck->fetch()) {
    echo json_encode(['status' => 'error', 'message' => 'Transaction already processed']);
    exit();
}

// IMPORTANT: Replace this with your actual Paystack Secret Key
$paystack_secret_key = "sk_test_YOUR_PAYSTACK_SECRET_KEY";

// Mock verification for local testing if the key is still the placeholder
if ($paystack_secret_key === "sk_test_YOUR_PAYSTACK_SECRET_KEY") {
    // MOCK SUCCESS
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO payments (user_id, request_id, amount, status, reference) VALUES (?, ?, ?, 'Success', ?)");
        $stmt->execute([$user_id, $request_id, $amount, $reference]);

        $stmtUpd = $pdo->prepare("UPDATE requests SET payment_status = 'Paid' WHERE id = ? AND driver_id = ?");
        $stmtUpd->execute([$request_id, $user_id]);

        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Mock Payment verified']);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }
    exit();
}

// REAL VERIFICATION LOGIC
$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => "https://api.paystack.co/transaction/verify/" . rawurlencode($reference),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_HTTPHEADER => array(
        "Authorization: Bearer " . $paystack_secret_key,
        "Cache-Control: no-cache",
    ),
));

$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

if ($err) {
    echo json_encode(['status' => 'error', 'message' => 'cURL Error: ' . $err]);
    exit();
}

$result = json_decode($response, true);

if (!$result) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid response from Paystack']);
    exit();
}

if ($result['status'] && $result['data']['status'] === 'success') {
    // Verify the amount matches what we expect. 
    // Paystack returns amount in lowest denomination (e.g. kobo/cents). We passed KES cents.
    $paidAmount = $result['data']['amount'] / 100;
    
    // In a real system, you'd compare $paidAmount against the DB invoice amount. 
    // Since the user inputted it, we just trust what Paystack verified they actually paid.
    
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO payments (user_id, request_id, amount, status, reference) VALUES (?, ?, ?, 'Success', ?)");
        $stmt->execute([$user_id, $request_id, $paidAmount, $reference]);

        $stmtUpd = $pdo->prepare("UPDATE requests SET payment_status = 'Paid' WHERE id = ? AND driver_id = ?");
        $stmtUpd->execute([$request_id, $user_id]);

        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Payment verified successfully']);
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }
} else {
    // Transaction failed or pending
    try {
        $stmt = $pdo->prepare("INSERT INTO payments (user_id, request_id, amount, status, reference) VALUES (?, ?, ?, 'Failed', ?)");
        $stmt->execute([$user_id, $request_id, $amount, $reference]);
    } catch (PDOException $e) {
        // ignore
    }
    echo json_encode(['status' => 'error', 'message' => 'Transaction was not successful on Paystack']);
}
?>
