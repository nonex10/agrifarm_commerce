<?php
require_once '../config.php';
session_start();

$transaction_uuid = $_GET['transaction_uuid'] ?? null;

if ($transaction_uuid) {
    $clean_order_id = (int) str_replace("ORD-", "", $transaction_uuid);
    
    // Switch tracking status to Failed
    try {
        $updateStmt = $pdo->prepare("UPDATE orders SET status = 'Failed' WHERE id = :id AND status = 'Pending'");
        $updateStmt->bindValue(':id', $clean_order_id, PDO::PARAM_INT);
        $updateStmt->execute();
    } catch (Exception $e) {
        // Suppress failure logs to client
    }
}

$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . dirname(dirname(dirname($_SERVER['SCRIPT_NAME'])));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Transaction Cancelled</title>
    <style>
        body { font-family: sans-serif; text-align: center; padding: 50px; background-color: #fdf2f2; color: #9b1c1c; }
        .card { background: white; padding: 30px; border-radius: 8px; display: inline-block; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .btn { display: inline-block; padding: 10px 20px; background: #dc2626; color: white; text-decoration: none; border-radius: 4px; margin-top: 15px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Payment Cancelled</h2>
        <p>Your payment attempt via eSewa was interrupted or cancelled. No funds have been removed.</p>
        <a href="<?php echo $base_url; ?>/pages/checkout.html" class="btn">Return to Checkout</a>
    </div>
</body>
</html>