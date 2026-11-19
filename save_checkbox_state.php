<?php
require_once('function.php');
dbconnect();
session_start();

if (!is_user()) {
  echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
  exit();
}

$order_id = $_POST['order_id'];
$checkbox_id = $_POST['checkbox_id'];
$is_checked = $_POST['is_checked'];

// Convert to integer
$is_checked = intval($is_checked);

// List of checkboxes that support 3 states (tick, cross, none)
$three_state_checkboxes = ["edge-1", "design-3c", "design-3d"];

if (in_array($checkbox_id, $three_state_checkboxes)) {
    // For 3-state checkboxes, accept 0, 1, or 2
    if (!in_array($is_checked, [0, 1, 2])) {
        $is_checked = 0; // Default to none
    }
} else {
    // For other checkboxes, normalize to 0 or 1
    $is_checked = $is_checked ? 1 : 0;
}

try {
    // Update or insert the checkbox state
    $stmt = $pdo->prepare("INSERT INTO customer_checkboxes (order_id, checkbox_id, is_checked) VALUES (:order_id, :checkbox_id, :is_checked) ON DUPLICATE KEY UPDATE is_checked = :is_checked");
    $stmt->execute(['order_id' => $order_id, 'checkbox_id' => $checkbox_id, 'is_checked' => $is_checked]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save checkbox state']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>