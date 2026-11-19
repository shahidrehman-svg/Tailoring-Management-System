<?php
require_once('function.php');
dbconnect();
session_start();

if (!is_user()) {
    redirect('index.php');
}

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=inventory_' . date('Y-m-d') . '.csv');

// Create output stream
$output = fopen('php://output', 'w');

// Add CSV headers
fputcsv($output, ['ID', 'Brand Name', 'Quantity', 'Price', 'Total Value', 'Created At', 'Updated At']);

// Build query
$search = isset($_GET['search']) ? $_GET['search'] : '';
$where = '';
$params = [];

if(!empty($search)) {
    $where = " WHERE brand_name LIKE ?";
    $params[] = "%$search%";
}

// Fetch data
$sql = "SELECT * FROM inventory $where ORDER BY brand_name";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

// Add rows to CSV
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $total_value = $row['quantity'] * $row['price'];
    fputcsv($output, [
        $row['id'],
        $row['brand_name'],
        $row['quantity'],
        '$' . number_format($row['price'], 2),
        '$' . number_format($total_value, 2),
        $row['created_at'],
        $row['updated_at']
    ]);
}

fclose($output);
exit();
?>