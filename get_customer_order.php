<?php
require_once('function.php');
dbconnect();
session_start();

header('Content-Type: application/json');

if (!is_user()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if(isset($_GET['customer_id'])) {
    $customer_id = intval($_GET['customer_id']);
    
    $stmt = $pdo->prepare("SELECT * FROM `order` WHERE customer = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$customer_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($order) {
        echo json_encode(['success' => true, 'order' => $order]);
    } else {
        echo json_encode(['success' => true, 'order' => null]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No customer ID provided']);
}
?>