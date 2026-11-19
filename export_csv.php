<?php
require_once('function.php');
dbconnect();
session_start();

if (!is_user()) {
    redirect('index.php');
}

// Get user info
$user = $_SESSION['username'];
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$user]);
$usid = $stmt->fetch(PDO::FETCH_ASSOC);
$uid = $usid['id'] ?? null;

// Fetch all customers
$stmt = $pdo->query("SELECT id, fullname, address, phonenumber, 
                     CASE WHEN sex = 0 THEN 'Male' ELSE 'Female' END as sex 
                     FROM customer ORDER BY id");
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get measurement statuses
$customerIds = array_column($customers, 'id');
$measurementStatus = [];

if (!empty($customerIds)) {
    $placeholders = implode(',', array_fill(0, count($customerIds), '?'));
    $stmt = $pdo->prepare("SELECT DISTINCT customer_id FROM measurement WHERE customer_id IN ($placeholders)");
    $stmt->execute($customerIds);
    $measuredCustomers = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    
    foreach ($measuredCustomers as $customerId) {
        $measurementStatus[$customerId] = true;
    }
}

// Set headers for CSV download
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="customers_' . date('Y-m-d_H-i-s') . '.csv"');
header('Cache-Control: max-age=0');

// Open output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel compatibility (handles special characters)
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add headers
fputcsv($output, ['ID', 'Full Name', 'Address', 'Phone Number', 'Sex', 'Has Measurements', 'Export Date']);

// Add data rows
foreach ($customers as $customer) {
    $hasMeasurement = isset($measurementStatus[$customer['id']]) ? 'Yes' : 'No';
    
    fputcsv($output, [
        $customer['id'],
        $customer['fullname'],
        $customer['address'],
        $customer['phonenumber'],
        $customer['sex'],
        $hasMeasurement,
        date('Y-m-d H:i:s')
    ]);
}

// Close the output stream
fclose($output);
exit;
?>