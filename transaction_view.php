<?php
require_once('function.php');
dbconnect();
session_start();

if (!is_user()) {
    redirect('index.php');
}

$user = $_SESSION['username'];
$usid = $pdo->query("SELECT id FROM users WHERE username='" . $user . "'");
$usid = $usid->fetch(PDO::FETCH_ASSOC);
$uid = $usid['id'];

include('header.php');

// Handle delete action
if (isset($_GET["delete_id"])) {
    $delete_id = $_GET["delete_id"];
    try {
        $pdo->exec("DELETE FROM staff_customer_transactions WHERE id='" . $delete_id . "'");
        $success = "Transaction deleted successfully!";
    } catch (PDOException $e) {
        $error = "Error deleting transaction: " . $e->getMessage();
    }
}

// Get filter parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$staff_id = isset($_GET['staff_id']) ? $_GET['staff_id'] : '';
$design_filter = isset($_GET['design_filter']) ? $_GET['design_filter'] : '';

// Fetch all staff for dropdown
$staff_list = $pdo->query("SELECT id, fullname FROM staff ORDER BY fullname")->fetchAll(PDO::FETCH_ASSOC);

// Fetch all design types for filter dropdown
$design_list = $pdo->query("SELECT DISTINCT design_type FROM staff_customer_transactions WHERE design_type IS NOT NULL AND design_type != '' ORDER BY design_type")->fetchAll(PDO::FETCH_ASSOC);

// Fetch all customers for name lookup
$customers_list = [];
$customers_query = $pdo->query("SELECT id, fullname, address FROM customer");
while ($customer = $customers_query->fetch(PDO::FETCH_ASSOC)) {
    $customers_list[$customer['address']] = $customer['fullname'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Transactions</title>
    <link href="css/style.default.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="css/jquery.datatables.css" rel="stylesheet">
    <!-- Datepicker CSS -->
    <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <style>
        .panel {
            margin-top: 20px;
        }
        .btn-xs {
            margin-right: 5px;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #dff0d8;
            border-color: #d6e9c6;
            color: #3c763d;
        }
        .alert-danger {
            background-color: #f2dede;
            border-color: #ebccd1;
            color: #a94442;
        }
        #transactionsTable_length select {
            width: 60px !important;
            height: 40px !important;
            padding: 5px 10px !important;
            border-radius: 4px !important;
            border: 1px solid #ddd !important;
            margin: 0 5px !important;
            display: inline-block !important;
        }
        #transactionsTable_length label {
            font-weight: normal;
            margin-bottom: 0;
        }
        .filter-panel {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #eee;
        }
        .filter-panel label {
            font-weight: bold;
            margin-right: 10px;
        }
        .filter-group {
            margin-bottom: 10px;
        }
        .date-input {
            width: 150px;
            display: inline-block;
            margin-right: 15px;
        }
        .staff-input {
            width: 200px;
            display: inline-block;
            margin-right: 15px;
        }
        .design-input {
            width: 180px;
            display: inline-block;
            margin-right: 15px;
        }
        .filter-btn {
            margin-right: 5px;
        }
        .filter-tags {
            margin-top: 10px;
        }
        .filter-tag {
            background: #5bc0de;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            margin-right: 5px;
            font-size: 12px;
            display: inline-block;
        }
        .filter-tag .remove {
            margin-left: 5px;
            cursor: pointer;
        }
        .amount-column {
            text-align: right;
            font-family: monospace;
        }
        .calculation-info {
            font-size: 11px;
            color: #666;
            font-style: italic;
        }
        .additional-amount {
            color: #e67e22;
        }
        .advance-amount {
            color: #27ae60;
            font-weight: bold;
        }
        .balance-positive {
            color: #e74c3c;
            font-weight: bold;
        }
        .balance-zero {
            color: #27ae60;
            font-weight: bold;
        }
        .design-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .design-motta {
            background-color: #e8f5e9;
            color: #2e7d32;
            border-left: 3px solid #4caf50;
        }
        .design-full {
            background-color: #e3f2fd;
            color: #1565c0;
            border-left: 3px solid #2196f3;
        }
        .design-paincha {
            background-color: #fff3e0;
            color: #e65100;
            border-left: 3px solid #ff9800;
        }
        .design-other {
            background-color: #f3e5f5;
            color: #6a1b9a;
            border-left: 3px solid #9c27b0;
        }
        .design-default {
            background-color: #f5f5f5;
            color: #616161;
            border-left: 3px solid #9e9e9e;
        }
        .customer-item {
            padding: 5px 0;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
        }
        .customer-item:last-child {
            border-bottom: none;
        }
        .customer-name {
            font-weight: bold;
            color: #333;
            min-width: 120px;
        }
        .customer-address {
            font-size: 12px;
            color: #666;
            margin-left: 10px;
        }
        /* Expanded Size Nos Column - No Scroll */
        .size-nos-cell {
            min-width: 220px;
            max-width: 500px;
            width: auto;
            background-color: #fafafa;
        }
        .customer-list {
            display: block;
            max-height: none;
            overflow-y: visible;
        }
        /* Balance column stacked styling */
        .balance-stack {
            text-align: right;
            min-width: 120px;
        }
        .advance-row {
            font-size: 12px;
            color: #27ae60;
            border-bottom: 1px dashed #ddd;
            padding-bottom: 5px;
            margin-bottom: 5px;
        }
        .remaining-row {
            font-size: 14px;
            font-weight: bold;
            padding-top: 2px;
        }
        .balance-label {
            font-size: 10px;
            color: #888;
            margin-right: 5px;
        }
        /* Make table more compact for other columns */
        #transactionsTable td, #transactionsTable th {
            vertical-align: middle;
            padding: 8px;
        }
        .action-buttons {
            white-space: nowrap;
            min-width: 100px;
        }
    </style>
</head>
<body>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">All Transactions</h1>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="panel panel-default">
                <div class="panel-heading clearfix" style="background-color: black;">
                    <h3 style="color:#ff9c14;" class="panel-title pull-left" style="padding-top: 7.5px;">Staff Payment Records</h3>
                    <div class="pull-right">
                        <a href="staffview.php" class="btn btn-primary btn-sm">
                            <i class="fa fa-users"></i> Back to Staff
                        </a>
                        <a href="transaction_form.php" class="btn btn-success btn-sm">
                            <i class="fa fa-plus"></i> Add New Record
                        </a>
                    </div>
                </div>
                
                <!-- Filter Panel -->
                <div class="filter-panel">
                    <form method="GET" action="" class="form-inline">
                        <div class="filter-group" style="display: flex; flex-wrap: nowrap; align-items: center; gap: 10px;">
                            <label style="white-space: nowrap;">Filter by:</label>
                            
                            <div class="form-group">
                                <input type="text" class="form-control datepicker" id="start_date" name="start_date" 
                                       placeholder="Start Date" value="<?php echo htmlspecialchars($start_date); ?>" 
                                       style="width: 120px;">
                            </div>
                            
                            <div class="form-group">
                                <input type="text" class="form-control datepicker" id="end_date" name="end_date" 
                                       placeholder="End Date" value="<?php echo htmlspecialchars($end_date); ?>" 
                                       style="width: 120px;">
                            </div>
                            
                            <div class="form-group">
                                <select class="form-control" id="staff_id" name="staff_id" style="width: 150px;">
                                    <option value="">All Staff</option>
                                    <?php foreach ($staff_list as $staff): ?>
                                        <option value="<?php echo $staff['id']; ?>" 
                                            <?php echo ($staff_id == $staff['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($staff['fullname']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <select class="form-control" id="design_filter" name="design_filter" style="width: 150px;">
                                    <option value="">All Designs</option>
                                    <?php foreach ($design_list as $design): ?>
                                        <option value="<?php echo htmlspecialchars($design['design_type']); ?>" 
                                            <?php echo ($design_filter == $design['design_type']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($design['design_type']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary filter-btn" style="white-space: nowrap;">
                                <i class="fa fa-filter"></i> Apply Filters
                            </button>
                            
                            <a href="transaction_view.php" class="btn btn-default filter-btn" style="white-space: nowrap;">
                                <i class="fa fa-refresh"></i> Clear All
                            </a>
                        </div>
                        
                        <!-- Active Filters Display -->
                        <?php if ($start_date || $end_date || $staff_id || $design_filter): ?>
                        <div class="filter-tags" style="margin-top: 10px;">
                            <strong>Active Filters:</strong>
                            <?php if ($start_date): ?>
                                <span class="filter-tag">
                                    From: <?php echo $start_date; ?>
                                    <a href="transaction_view.php?<?php 
                                        echo http_build_query(array_filter([
                                            'end_date' => $end_date,
                                            'staff_id' => $staff_id,
                                            'design_filter' => $design_filter
                                        ])); 
                                    ?>" class="remove">×</a>
                                </span>
                            <?php endif; ?>
                            
                            <?php if ($end_date): ?>
                                <span class="filter-tag">
                                    To: <?php echo $end_date; ?>
                                    <a href="transaction_view.php?<?php 
                                        echo http_build_query(array_filter([
                                            'start_date' => $start_date,
                                            'staff_id' => $staff_id,
                                            'design_filter' => $design_filter
                                        ])); 
                                    ?>" class="remove">×</a>
                                </span>
                            <?php endif; ?>
                            
                            <?php if ($staff_id): 
                                $selected_staff_name = '';
                                foreach ($staff_list as $staff) {
                                    if ($staff['id'] == $staff_id) {
                                        $selected_staff_name = $staff['fullname'];
                                        break;
                                    }
                                }
                            ?>
                                <span class="filter-tag">
                                    Staff: <?php echo htmlspecialchars($selected_staff_name); ?>
                                    <a href="transaction_view.php?<?php 
                                        echo http_build_query(array_filter([
                                            'start_date' => $start_date,
                                            'end_date' => $end_date,
                                            'design_filter' => $design_filter
                                        ])); 
                                    ?>" class="remove">×</a>
                                </span>
                            <?php endif; ?>
                            
                            <?php if ($design_filter): ?>
                                <span class="filter-tag">
                                    Design: <?php echo htmlspecialchars($design_filter); ?>
                                    <a href="transaction_view.php?<?php 
                                        echo http_build_query(array_filter([
                                            'start_date' => $start_date,
                                            'end_date' => $end_date,
                                            'staff_id' => $staff_id
                                        ])); 
                                    ?>" class="remove">×</a>
                                </span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </form>
                </div>
                
                <div class="panel-body">  
                    <div class="table-responsive">
                        <table class="table table-striped" id="transactionsTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Date</th>
                                    <th>Staff Name</th>
                                    <th>Design Type</th>
                                    <th>Size Nos (Customer Details)</th>
                                    <th>Qty</th>
                                    <th>Unit Amt</th>
                                    <th>Design Amt</th>
                                    <th>Balance (Advance / Remaining)</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $totalQuantity = 0;
                                $totalUnitAmount = 0;
                                $totalAdditionalAmount = 0;
                                $totalAdvanceAmount = 0;
                                $totalCalculatedAmount = 0;
                                $totalRemainingBalance = 0;
                                $filteredQuantity = 0;
                                $filteredUnitAmount = 0;
                                $filteredAdditionalAmount = 0;
                                $filteredAdvanceAmount = 0;
                                $filteredCalculatedAmount = 0;
                                $filteredRemainingBalance = 0;
                                
                                // Build query with filters
                                $query = "SELECT t.*, s.fullname as staff_name 
                                          FROM staff_customer_transactions t 
                                          LEFT JOIN staff s ON t.staff_id = s.id";
                                
                                $where_clauses = [];
                                $params = [];
                                
                                // Add date filters
                                if ($start_date) {
                                    $where_clauses[] = "DATE(t.transaction_date) >= :start_date";
                                    $params[':start_date'] = date('Y-m-d', strtotime($start_date));
                                }
                                
                                if ($end_date) {
                                    $where_clauses[] = "DATE(t.transaction_date) <= :end_date";
                                    $params[':end_date'] = date('Y-m-d', strtotime($end_date));
                                }
                                
                                // Add staff filter
                                if ($staff_id) {
                                    $where_clauses[] = "t.staff_id = :staff_id";
                                    $params[':staff_id'] = $staff_id;
                                }
                                
                                // Add design filter
                                if ($design_filter) {
                                    $where_clauses[] = "t.design_type LIKE :design_filter";
                                    $params[':design_filter'] = '%' . $design_filter . '%';
                                }
                                
                                // Add WHERE clause if filters exist
                                if (!empty($where_clauses)) {
                                    $query .= " WHERE " . implode(" AND ", $where_clauses);
                                }
                                
                                $query .= " ORDER BY t.transaction_date DESC";
                                
                                // Get total stats for all records (for comparison)
                                $total_query = "SELECT 
                                                COUNT(*) as total_count, 
                                                SUM(COALESCE(quantity, 1)) as total_quantity,
                                                SUM(COALESCE(unit_amount, 0)) as total_unit_amount,
                                                SUM(COALESCE(additional_amount, 0)) as total_additional_amount,
                                                SUM(COALESCE(advance_amount, 0)) as total_advance_amount,
                                                SUM(COALESCE(calculated_amount, 0)) as total_calculated_amount
                                                FROM staff_customer_transactions";
                                $total_result = $pdo->query($total_query)->fetch(PDO::FETCH_ASSOC);
                                $all_records_quantity = $total_result['total_quantity'] ?? 0;
                                $all_records_unit_amount = $total_result['total_unit_amount'] ?? 0;
                                $all_records_additional = $total_result['total_additional_amount'] ?? 0;
                                $all_records_advance = $total_result['total_advance_amount'] ?? 0;
                                $all_records_calculated = $total_result['total_calculated_amount'] ?? 0;
                                $all_records_remaining = $all_records_calculated - $all_records_advance;
                                
                                // Prepare and execute filtered query
                                $stmt = $pdo->prepare($query);
                                foreach ($params as $key => $value) {
                                    $stmt->bindValue($key, $value);
                                }
                                $stmt->execute();
                                
                                // Get filtered stats
                                $filtered_stats_query = str_replace("SELECT t.*, s.fullname as staff_name", 
                                                                   "SELECT COUNT(*) as filtered_count, 
                                                                    SUM(COALESCE(t.quantity, 1)) as filtered_quantity,
                                                                    SUM(COALESCE(t.unit_amount, 0)) as filtered_unit_amount,
                                                                    SUM(COALESCE(t.additional_amount, 0)) as filtered_additional_amount,
                                                                    SUM(COALESCE(t.advance_amount, 0)) as filtered_advance_amount,
                                                                    SUM(COALESCE(t.calculated_amount, 0)) as filtered_calculated_amount", 
                                                                   $query);
                                $filtered_stats_query = preg_replace('/ORDER BY.*$/', '', $filtered_stats_query);
                                $filtered_stmt = $pdo->prepare($filtered_stats_query);
                                foreach ($params as $key => $value) {
                                    $filtered_stmt->bindValue($key, $value);
                                }
                                $filtered_stmt->execute();
                                $filtered_stats = $filtered_stmt->fetch(PDO::FETCH_ASSOC);
                                $filtered_count = $filtered_stats['filtered_count'] ?? 0;
                                $filtered_quantity = $filtered_stats['filtered_quantity'] ?? 0;
                                $filtered_unit_amount = $filtered_stats['filtered_unit_amount'] ?? 0;
                                $filtered_additional = $filtered_stats['filtered_additional_amount'] ?? 0;
                                $filtered_advance = $filtered_stats['filtered_advance_amount'] ?? 0;
                                $filtered_calculated = $filtered_stats['filtered_calculated_amount'] ?? 0;
                                $filtered_remaining = $filtered_calculated - $filtered_advance;
                                
                                // Function to get design badge class
                                function getDesignBadgeClass($design_type) {
                                    switch($design_type) {
                                        case 'Motta Chamak':
                                            return 'design-motta';
                                        case 'Full Double Chamak':
                                            return 'design-full';
                                        case 'Paincha Karai':
                                            return 'design-paincha';
                                        case 'Design':
                                            return 'design-other';
                                        default:
                                            return 'design-default';
                                    }
                                }
                                
                                // Increase display limit to show more customers
                                $display_limit = 10;
                                
                                if ($stmt->rowCount() > 0) {
                                    while ($transaction = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                        $quantity = $transaction['quantity'] ?? 1;
                                        $unit_amount = $transaction['unit_amount'] ?? 0;
                                        $additional_amount = $transaction['additional_amount'] ?? 0;
                                        $advance_amount = $transaction['advance_amount'] ?? 0;
                                        $calculated_amount = $transaction['calculated_amount'] ?? 0;
                                        $remaining_balance = $calculated_amount - $advance_amount;
                                        $design_type = $transaction['design_type'] ?? 'Not Specified';
                                        
                                        $totalQuantity += $quantity;
                                        $totalUnitAmount += $unit_amount;
                                        $totalAdditionalAmount += $additional_amount;
                                        $totalAdvanceAmount += $advance_amount;
                                        $totalCalculatedAmount += $calculated_amount;
                                        $totalRemainingBalance += $remaining_balance;
                                        
                                        // Format customer codes with names - Show ALL customers (no limit)
                                        $customer_codes = explode(',', $transaction['customer_codes']);
                                        $code_count = count($customer_codes);
                                        
                                        // Build customer list with names - Show ALL customers
                                        $customers_display = '';
                                        $display_count = 0;
                                        
                                        foreach ($customer_codes as $customer_address) {
                                            $customer_address = trim($customer_address);
                                            $customer_name = isset($customers_list[$customer_address]) ? $customers_list[$customer_address] : 'Unknown';
                                            
                                            $customers_display .= '<div class="customer-item">';
                                            $customers_display .= '<span class="customer-name">' . htmlspecialchars($customer_name) . '</span>';
                                            $customers_display .= '<span class="customer-address">(' . htmlspecialchars($customer_address) . ')</span>';
                                            $customers_display .= '</div>';
                                            $display_count++;
                                        }
                                        
                                        // Format date
                                        $date = date('d/m/Y', strtotime($transaction['transaction_date']));
                                        
                                        // Get badge class for design
                                        $badge_class = getDesignBadgeClass($design_type);
                                        
                                        // Determine balance class for remaining amount
                                        $remaining_class = ($remaining_balance > 0) ? 'balance-positive' : (($remaining_balance == 0) ? 'balance-zero' : '');
                                        $remaining_text = ($remaining_balance > 0) ? 'Due' : (($remaining_balance == 0) ? 'Paid' : 'Overpaid');
                                        
                                        echo "<tr>
                                                <td>{$transaction['id']}</td>
                                                <td>{$date}</td>
                                                <td>{$transaction['staff_name']}</td>
                                                <td>
                                                    <span class='design-badge {$badge_class}'>
                                                        <i class='fa fa-paint-brush'></i> " . htmlspecialchars($design_type) . "
                                                    </span>
                                                </td>
                                                <td class='size-nos-cell'>
                                                    <div class='customer-list'>
                                                        {$customers_display}
                                                    </div>
                                                <td class='amount-column'>{$quantity}</td>
                                                <td class='amount-column'>Rs." . number_format($unit_amount, 2) . "</td>
                                                <td class='amount-column'>";
                                        
                                        // Show additional amount
                                        if ($additional_amount > 0) {
                                            echo "<span class='additional-amount'>Rs." . number_format($additional_amount, 2) . "</span>";
                                        } else {
                                            echo "Rs. " . number_format($additional_amount, 2);
                                        }
                                        
                                        // Show Balance column with stacked advance and remaining
                                        echo "</td>
                                                <td class='amount-column balance-stack'>
                                                    <div class='advance-row'>
                                                        <span class='balance-label'>Advance:</span>
                                                        <span class='advance-amount'>Rs. " . number_format($advance_amount, 2) . "</span>
                                                    </div>
                                                    <div class='remaining-row {$remaining_class}'>
                                                        <span class='balance-label'>" . $remaining_text . ":</span>
                                                        Rs. " . number_format(abs($remaining_balance), 2) . "
                                                    </div>
                                                </td>
                                                <td class='action-buttons' style='white-space: nowrap;'>
                                                    <a href='transaction_detail.php?id={$transaction['id']}' 
                                                       class='btn btn-info btn-xs' title='View Details'>
                                                       <i class='fa fa-eye'></i>
                                                    </a>
                                                    <a href='transaction_form.php?edit_id={$transaction['id']}' 
                                                       class='btn btn-warning btn-xs' title='Edit'>
                                                       <i class='fa fa-edit'></i>
                                                    </a>
                                                    <a href='transaction_view.php?delete_id={$transaction['id']}' 
                                                       class='btn btn-danger btn-xs' 
                                                       onclick='return confirm(\"Are you sure you want to delete this transaction?\")'
                                                       title='Delete'>
                                                       <i class='fa fa-trash'></i>
                                                    </a>
                                                  </div>
                                                </td>
                                               </tr>";
                                    }
                                } else {
                                    echo "";
                                }
                                ?>
                            </tbody>
                        </table>
                        
                        <!-- Totals display -->
                        <div class="well" style="margin-top: 20px;">
                            <div class="row">
                                <div class="col-md-6">
                                    <h4>Filtered Totals (<?php echo $filtered_count; ?> records):</h4>
                                    <table class="table table-bordered" style="width: auto;">
                                        <tr>
                                            <th><strong>Total Quantity:</strong></th>
                                            <td class='amount-column'><?php echo number_format($filtered_quantity); ?> Suits</td>
                                        </tr>
                                        <!-- <tr>
                                            <th><strong>Total Unit Amount:</strong></th>
                                            <td class='amount-column'>Rs. <?php echo number_format($filtered_unit_amount, 2); ?></td>
                                        </tr> -->
                                        <tr>
                                            <th><strong>Total Design Amount:</strong></th>
                                            <td class='amount-column'>Rs. <?php echo number_format($filtered_additional, 2); ?></td>
                                        </tr>
                                        <tr>
                                            <th><strong>Total Advance Amount:</strong></th>
                                            <td class='amount-column' style="color: #27ae60; font-weight: bold;">
                                                Rs. <?php echo number_format($filtered_advance, 2); ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><strong>Total Calculated Amount:</strong></th>
                                            <td class='amount-column'>
                                                <strong>Rs. <?php echo number_format($filtered_calculated, 2); ?></strong>
                                             </td>
                                        </tr>
                                        <tr style="background-color: #f9f9f9;">
                                            <th><strong>Total Remaining Balance:</strong></th>
                                            <td class='amount-column' style="color: #e74c3c; font-weight: bold; font-size: 16px;">
                                                Rs. <?php echo number_format($filtered_remaining, 2); ?>
                                             </td>
                                        </tr>
                                    </table>
                                </div>
                                
                                <?php if ($start_date || $end_date || $staff_id || $design_filter): ?>
                                <div class="col-md-6">
                                    <h4>Overall Totals (All Records):</h4>
                                    <table class="table table-bordered" style="width: auto;">
                                        <tr>
                                            <th><strong>Total Quantity:</strong></th>
                                            <td class='amount-column'><?php echo number_format($all_records_quantity); ?> Suits</td>
                                        </tr>
                                        <tr>
                                            <th><strong>Total Unit Amount:</strong></th>
                                            <td class='amount-column'>Rs. <?php echo number_format($all_records_unit_amount, 2); ?></td>
                                        </tr>
                                        <tr>
                                            <th><strong>Total Design Amount:</strong></th>
                                            <td class='amount-column'>Rs. <?php echo number_format($all_records_additional, 2); ?></td>
                                        </tr>
                                        <tr>
                                            <th><strong>Total Advance Amount:</strong></th>
                                            <td class='amount-column' style="color: #27ae60; font-weight: bold;">
                                                Rs. <?php echo number_format($all_records_advance, 2); ?>
                                             </td>
                                        </tr>
                                        <tr>
                                            <th><strong>Total Calculated Amount:</strong></th>
                                            <td class='amount-column'>
                                                <strong>Rs. <?php echo number_format($all_records_calculated, 2); ?></strong>
                                             </td>
                                         </tr>
                                        <tr style="background-color: #f9f9f9;">
                                            <th><strong>Total Remaining Balance:</strong></th>
                                            <td class='amount-column' style="color: #e74c3c; font-weight: bold; font-size: 16px;">
                                                Rs. <?php echo number_format($all_records_remaining, 2); ?>
                                             </td>
                                         </tr>
                                    </table>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- DataTables JS -->
<script src="js/jquery.datatables.min.js"></script>
<!-- jQuery UI for datepicker -->
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>

<script>
jQuery(document).ready(function() {
    // Initialize datepicker
    $('.datepicker').datepicker({
        dateFormat: 'yy-mm-dd',
        changeMonth: true,
        changeYear: true,
        yearRange: '2020:2030'
    });
    
    // Initialize DataTables with responsive settings
    var table = jQuery('#transactionsTable').DataTable({
        "pageLength": 10,
        "order": [[1, 'desc']],
        "columnDefs": [
            { "orderable": false, "targets": [9] },
            { "className": "amount-column", "targets": [5, 6, 7, 8] }
        ],
        "dom": '<"top"lf>rt<"bottom"ip><"clear">',
        "language": {
            "lengthMenu": "Show _MENU_ entries",
            "zeroRecords": "No transactions found",
            "info": "Showing _START_ to _END_ of _TOTAL_ entries",
            "infoEmpty": "Showing 0 to 0 of 0 entries",
            "infoFiltered": "(filtered from _MAX_ total entries)",
            "search": "Search:"
        },
        "autoWidth": false,
        "scrollX": true,
        "scrollCollapse": true
    });
    
    <?php if ($start_date || $end_date || $staff_id || $design_filter): ?>
    setTimeout(function() {
        var info = table.page.info();
        $('.dataTables_info').html(
            'Showing 1 to ' + info.end + ' of ' + <?php echo $filtered_count; ?> + ' entries' +
            ' (filtered from ' + <?php echo $total_result['total_count'] ?? 0; ?> + ' total entries)'
        );
    }, 100);
    <?php endif; ?>
});
</script>

<?php include('footer.php'); ?>
</body>
</html>