<?php
require_once('function.php');
dbconnect();
session_start();

if (!is_user()) {
    die(json_encode(['success' => false, 'error' => 'Unauthorized']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['customer_id'])) {
    $customerId = $_POST['customer_id'];
    $message = $_POST['message'] ?? '';
    
    $result = send_whatsapp_to_customer($customerId, $message);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $result['success'],
        'whatsapp_url' => $result['whatsapp_url'] ?? '',
        'customer_name' => $result['customer_name'] ?? '',
        'phone' => $result['phone'] ?? '',
        'error' => $result['error'] ?? ''
    ]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid request']);
?>