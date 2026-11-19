<?php
require_once('function.php');
dbconnect();
session_start();

if (!is_user()) {
  echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
  exit();
}

$order_id = $_GET['order_id'];

try {
    // Fetch checkbox states
    $stmt = $pdo->prepare("SELECT checkbox_id, is_checked FROM customer_checkboxes WHERE order_id = :order_id");
    $stmt->execute(['order_id' => $order_id]);
    $checkbox_states = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $data = [];
    $state_types = [];
    
    // List of 3-state checkboxes
    $three_state_checkboxes = ["edge-1", "design-3c", "design-3d"];
    
    foreach ($checkbox_states as $state) {
        $checkbox_id = $state['checkbox_id'];
        $is_checked = intval($state['is_checked']);
        
        // Store the raw is_checked value
        $data[$checkbox_id] = $is_checked;
        
        // Convert to state_type based on checkbox type
        if (in_array($checkbox_id, $three_state_checkboxes)) {
            // For 3-state checkboxes: 0=none, 1=tick, 2=cross
            if ($is_checked === 2) {
                $state_types[$checkbox_id] = "cross";
            } elseif ($is_checked === 1) {
                $state_types[$checkbox_id] = "tick";
            } else {
                $state_types[$checkbox_id] = "none";
            }
        } else {
            // For other checkboxes: 1=tick, 0=none
            $state_types[$checkbox_id] = ($is_checked === 1) ? "tick" : "none";
        }
    }
    
    echo json_encode([
        'status' => 'success', 
        'data' => $data,
        'state_types' => $state_types
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>