<?php
// get_bill_details.php
require_once('function.php');
dbconnect();
session_start();

if (!is_user()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$bill_id = $_GET['bill_id'] ?? 0;

if($bill_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid bill ID']);
    exit;
}

try {
    // Get bill header
    $stmt = $pdo->prepare("SELECT * FROM billing_header WHERE id = ?");
    $stmt->execute([$bill_id]);
    $bill = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$bill) {
        echo json_encode(['success' => false, 'message' => 'Bill not found']);
        exit;
    }
    
    // Format billing date
    $bill['billing_date'] = date('d/m/Y h:i A', strtotime($bill['billing_date']));
    
    // Get bill items
    $stmt = $pdo->prepare("SELECT * FROM billing_items WHERE bill_id = ? ORDER BY id");
    $stmt->execute([$bill_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'bill' => $bill,
        'items' => $items
    ]);
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>