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

// Fetch all customers with their measurement status
$stmt = $pdo->query("SELECT id, fullname, address, phonenumber, sex FROM customer ORDER BY id");
$allCustomers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get measurement statuses
$customerIds = array_column($allCustomers, 'id');
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

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="customers_export_' . date('Y-m-d_H-i-s') . '.xls"');
header('Cache-Control: max-age=0');

// Create HTML table for Excel with styling
echo '<html>';
echo '<head>';
echo '<meta charset="UTF-8">';
echo '<title>Customers Export</title>';
echo '<style>';
echo 'th { background-color: #4CAF50; color: white; padding: 8px; text-align: left; }';
echo 'td { padding: 8px; border: 1px solid #ddd; }';
echo 'tr:nth-child(even) { background-color: #f2f2f2; }';
echo '.header { font-size: 18px; font-weight: bold; margin-bottom: 20px; }';
echo '</style>';
echo '</head>';
echo '<body>';

// Add title
echo '<div class="header">Customer Report - Generated on: ' . date('Y-m-d H:i:s') . '</div>';
echo '<br/>';

// Create table
echo '<table border="1" cellpadding="5" cellspacing="0">';
echo '<thead>';
echo '<tr>';
echo '<th style="background-color:#4CAF50; color:white;">ID</th>';
echo '<th style="background-color:#4CAF50; color:white;">Full Name</th>';
echo '<th style="background-color:#4CAF50; color:white;">Address</th>';
echo '<th style="background-color:#4CAF50; color:white;">Phone Number</th>';
echo '<th style="background-color:#4CAF50; color:white;">Sex</th>';
echo '<th style="background-color:#4CAF50; color:white;">Measurement Status</th>';
echo '<th style="background-color:#4CAF50; color:white;">Available Actions</th>';
echo '</tr>';
echo '</thead>';
echo '<tbody>';

foreach ($allCustomers as $data) {
    $sex = ($data['sex'] == 0) ? 'Male' : 'Female';
    $customerId = (int)$data['id'];
    
    // Check measurement status
    $hasMeasurement = isset($measurementStatus[$customerId]) ? 'Yes (Has Measurements)' : 'No (No Measurements Yet)';
    
    // Determine available actions
    $actions = [];
    $actions[] = '• New Order';
    $actions[] = ($hasMeasurement == 'Yes (Has Measurements)') ? '• Edit Measurement' : '• Add Measurement';
    $actions[] = '• Send SMS';
    $actions[] = '• Send Email';
    $actionsString = implode("\n", $actions);
    
    echo '<tr>';
    echo '<td>' . htmlspecialchars($data['id']) . '</td>';
    echo '<td>' . htmlspecialchars($data['fullname']) . '</td>';
    echo '<td>' . htmlspecialchars($data['address']) . '</td>';
    echo '<td>' . htmlspecialchars($data['phonenumber']) . '</td>';
    echo '<td>' . $sex . '</td>';
    echo '<td>' . $hasMeasurement . '</td>';
    echo '<td style="white-space: pre-line;">' . $actionsString . '</td>';
    echo '</tr>';
}

echo '</tbody>';
echo '</table>';

// Add summary
echo '<br/><br/>';
echo '<div style="margin-top: 20px;">';
echo '<strong>Summary:</strong><br/>';
echo 'Total Customers: ' . count($allCustomers) . '<br/>';
echo 'Customers with Measurements: ' . count($measurementStatus) . '<br/>';
echo 'Customers without Measurements: ' . (count($allCustomers) - count($measurementStatus)) . '<br/>';
echo 'Export Date: ' . date('Y-m-d H:i:s') . '<br/>';
echo '</div>';

echo '</body>';
echo '</html>';
exit;
?>