<?php
require_once('function.php');
dbconnect();
session_start();

// Set timezone to Pakistan (or your local timezone)
date_default_timezone_set('Asia/Karachi');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bill_id = $_POST['bill_id'] ?? 0;
    $order_status = $_POST['order_status'] ?? '';
    $date_collected = $_POST['date_collected'] ?? date('Y-m-d'); // Use server date as fallback
    
    // If date_collected is empty or not provided, use current server date
    if (empty($date_collected)) {
        $date_collected = date('Y-m-d');
    }
    
    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_collected)) {
        $date_collected = date('Y-m-d');
    }
    
    $stmt = $pdo->prepare("UPDATE billing_header 
                           SET order_status = ?, 
                               date_collected = ? 
                           WHERE id = ?");
    
    if ($stmt->execute([$order_status, $date_collected, $bill_id])) {
        echo json_encode(['success' => true, 'message' => 'Order updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}
?>