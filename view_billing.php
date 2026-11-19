<?php
require_once('function.php');
dbconnect();
session_start();

if (!is_user()) {
    redirect('index.php');
}

$user = $_SESSION['username'];
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$user]);
$usid = $stmt->fetch(PDO::FETCH_ASSOC);
$uid = $usid['id'] ?? null;

include ('header.php');

// Get filter parameters
$customer_filter = $_GET['customer'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$order_status_filter = $_GET['order_status'] ?? '';
$payment_status_filter = $_GET['payment_status'] ?? '';
?>

<link href="css/jquery.datatables.css" rel="stylesheet">
<style>
.dropdown-menu {
    position: absolute;
    top: 100%;
    left: 106px;
    z-index: 1000;
    display: none;
    float: left;
    min-width: 160px;
    padding: 5px 0;
    margin: 2px 0 0;
    font-size: 14px;
    text-align: left;
    list-style: none;
    background-color: #fff;
    background-clip: padding-box;
    border: 1px solid #ccc;
    border-radius: 4px;
    box-shadow: 0 6px 12px rgba(0,0,0,.175);
}

.dropdown-menu.open {
    display: block;
}

.customer-option {
    border-bottom: 1px solid #eee;
}

.customer-option:last-child {
    border-bottom: none;
}

.customer-option:hover,
.customer-option.active {
    background-color: #f5f5f5;
}

.customer-option a:hover {
    text-decoration: none;
}

.customer-option small {
    font-size: 11px;
}

#customerSearch:focus {
    outline: none;
    border-color: #66afe9;
    box-shadow: inset 0 1px 1px rgba(0,0,0,.075), 0 0 8px rgba(102,175,233,.6);
}

#selectedCustomerDetails {
    border-left: 3px solid #2196f3;
    background-color: #f5f5f5;
}

/* Search highlight */
.search-highlight {
    background-color: yellow;
    font-weight: bold;
}
</style>

<style>
    .bill-items-modal .modal-lg {
        max-width: 800px;
    }
    .items-table-modal {
        font-size: 13px;
    }
    .items-table-modal th {
        background-color: #f5f5f5;
    }
    .bill-no {
        font-weight: bold;
        color: #333;
    }
    .customer-name {
        font-weight: 600;
    }
    .total-amount {
        font-weight: bold;
        color: #27ae60;
    }
    .badge-items {
        background-color: #3498db;
        cursor: pointer;
    }
    .summary-card {
        border-radius: 5px;
        padding: 15px;
        margin-bottom: 15px;
        text-align: center;
    }

    .dataTables_length select[name="billingTable_length"] {
        min-width: 60px !important;
        max-width: 60px !important;
        height: 36px !important;
    }
    .summary-card .number {
        font-size: 24px;
        font-weight: bold;
        margin-bottom: 5px;
    }
    .summary-card .label {
        font-size: 12px;
        color: #666;
        text-transform: uppercase;
    }
    .summary-card.bills { background-color: #e3f2fd; border-left: 4px solid #2196f3; }
    .summary-card.advance { background-color: #e8f5e9; border-left: 4px solid #4caf50; }
    .summary-card.discount { background-color: #ffebee; border-left: 4px solid #f44336; }
    .summary-card.amount { background-color: #fff3e0; border-left: 4px solid #ff9800; }
    .summary-card.quantity { background-color: #f3e5f5; border-left: 4px solid #9c27b0; }
    .summary-card.suit-quantity { background-color: #e0f7fa; border-left: 4px solid #00bcd4; }
    .summary-card.collected-suit { background-color: #d4edda; border-left: 4px solid #28a745; }
    
    /* Order Status Styles */
    .order-status-collected {
        background-color: #28a745;
        color: white;
        padding: 3px 8px;
        border-radius: 3px;
        font-size: 11px;
        font-weight: bold;
        display: inline-block;
    }
    .order-status-pending {
        background-color: #ffc107;
        color: #856404;
        padding: 3px 8px;
        border-radius: 3px;
        font-size: 11px;
        font-weight: bold;
        display: inline-block;
    }
    .order-status-overdue {
        background-color: #dc3545;
        color: white;
        padding: 3px 8px;
        border-radius: 3px;
        font-size: 11px;
        font-weight: bold;
        display: inline-block;
        animation: pulse 1.5s infinite;
    }
    .order-status-today {
        background-color: #17a2b8;
        color: white;
        padding: 3px 8px;
        border-radius: 3px;
        font-size: 11px;
        font-weight: bold;
        display: inline-block;
        animation: pulse 1.5s infinite;
    }
    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.7; }
        100% { opacity: 1; }
    }
    .btn-mark-collected {
        background-color: #28a745;
        color: white;
        border: none;
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 10px;
        cursor: pointer;
    }
    .btn-mark-collected:hover {
        background-color: #218838;
    }
    
    @media print {
        .no-print { display: none !important; }
        .print-only { display: block !important; }
        body { font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 5px; }
        .print-header { text-align: center; margin-bottom: 20px; }
        .print-summary { margin-top: 20px; }
        .print-footer { margin-top: 30px; text-align: center; font-size: 10px; }
        .summary-cards-table {
            width: 100%;
            margin-bottom: 20px;
            border: 2px solid #333;
        }
        .summary-cards-table td {
            text-align: center;
            padding: 10px;
            border: 1px solid #ddd;
        }
        .summary-cards-table .card-title {
            font-weight: bold;
            font-size: 14px;
        }
        .summary-cards-table .card-value {
            font-size: 18px;
            font-weight: bold;
            margin-top: 5px;
        }
    }
    .print-only { display: none; }
    
    /* Action buttons styling */
    .btn-group-xs .btn {
        padding: 2px 6px;
        font-size: 11px;
        line-height: 1.4;
        border-radius: 2px;
    }
    .btn-group-xs .btn-edit {
        background-color: #f0ad4e;
        color: white;
    }
    .btn-group-xs .btn-edit:hover {
        background-color: #ec971f;
    }

    table.dataTable td.dataTables_empty {
    text-align: center !important;
    vertical-align: middle !important;
    padding: 40px 20px;
}
</style>

<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Billing Management</h1>
        </div>
    </div>

    <?php
    // Build WHERE clause for filters
    $where_conditions = [];
    $params = [];
    
    if ($customer_filter) {
        // Search in customer name, address, AND phone
        $where_conditions[] = "(bh.customer_name LIKE :customer OR bh.customer_address LIKE :customer OR bh.customer_phone LIKE :customer)";
        $params[':customer'] = '%' . $customer_filter . '%';
    }
    
    if ($date_from && $date_to) {
        $where_conditions[] = "DATE(bh.billing_date) BETWEEN :date_from AND :date_to";
        $params[':date_from'] = $date_from;
        $params[':date_to'] = $date_to;
    } elseif ($date_from) {
        $where_conditions[] = "DATE(bh.billing_date) >= :date_from";
        $params[':date_from'] = $date_from;
    } elseif ($date_to) {
        $where_conditions[] = "DATE(bh.billing_date) <= :date_to";
        $params[':date_to'] = $date_to;
    }
    
    // Add order status filter
    if ($order_status_filter == 'pending') {
        $where_conditions[] = "bh.order_status = 'pending'";
    } elseif ($order_status_filter == 'collected') {
        $where_conditions[] = "bh.order_status = 'collected'";
    } elseif ($order_status_filter == 'overdue') {
        $where_conditions[] = "bh.order_status = 'pending' AND bh.date_collected IS NOT NULL AND bh.date_collected < CURDATE()";
    } elseif ($order_status_filter == 'today') {
        $where_conditions[] = "bh.order_status = 'pending' AND bh.date_collected = CURDATE()";
    }
    
    // Add payment status filter
    if ($payment_status_filter == 'partial') {
        $where_conditions[] = "bh.advance_payment > 0 AND bh.advance_payment < bh.total_amount";
    } elseif ($payment_status_filter == 'paid') {
        $where_conditions[] = "bh.advance_payment >= bh.total_amount";
    } elseif ($payment_status_filter == 'pending') {
        $where_conditions[] = "bh.advance_payment = 0";
    }
    
    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    // Calculate summary statistics with filters
    $summary_sql = "SELECT 
        COUNT(DISTINCT bh.id) as total_bills,
        SUM(bh.total_amount) as total_amount,
        SUM(bh.discount) as total_discount,
        SUM(bh.advance_payment) as total_advance,
        SUM(bh.subtotal) as total_subtotal,
        SUM(bh.stitching_total) as total_stitching,
        SUM(bh.remaining_amount) as total_remaining
        FROM billing_header bh 
        $where_clause";
    
    $summary_stmt = $pdo->prepare($summary_sql);
    foreach ($params as $key => $value) {
        $summary_stmt->bindValue($key, $value);
    }
    $summary_stmt->execute();
    $summary = $summary_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Calculate total quantity (sum of all quantities from billing_items)
    $quantity_sql = "SELECT COALESCE(SUM(bi.quantity), 0) as total_quantity 
                     FROM billing_items bi 
                     INNER JOIN billing_header bh ON bi.bill_id = bh.id 
                     $where_clause";
    
    $quantity_stmt = $pdo->prepare($quantity_sql);
    foreach ($params as $key => $value) {
        $quantity_stmt->bindValue($key, $value);
    }
    $quantity_stmt->execute();
    $quantity_result = $quantity_stmt->fetch(PDO::FETCH_ASSOC);
    $total_quantity = $quantity_result['total_quantity'] ?? 0;
    
    // Calculate total PENDING suit quantity
    $suit_conditions = [];
    $suit_params = [];

    // Only include active pending orders (not collected, not overdue)
    $suit_conditions[] = "bh.order_status = 'pending'";
    $suit_conditions[] = "(bh.date_collected IS NULL OR bh.date_collected >= CURDATE())";

    // Add customer filter if present
    if ($customer_filter) {
        $suit_conditions[] = "(bh.customer_name LIKE :suit_customer OR bh.customer_address LIKE :suit_customer OR bh.customer_phone LIKE :suit_customer)";
        $suit_params[':suit_customer'] = '%' . $customer_filter . '%';
    }

    // Add date filters if present
    if ($date_from && $date_to) {
        $suit_conditions[] = "DATE(bh.billing_date) BETWEEN :suit_date_from AND :suit_date_to";
        $suit_params[':suit_date_from'] = $date_from;
        $suit_params[':suit_date_to'] = $date_to;
    } elseif ($date_from) {
        $suit_conditions[] = "DATE(bh.billing_date) >= :suit_date_from";
        $suit_params[':suit_date_from'] = $date_from;
    } elseif ($date_to) {
        $suit_conditions[] = "DATE(bh.billing_date) <= :suit_date_to";
        $suit_params[':suit_date_to'] = $date_to;
    }

    $suit_where_clause = !empty($suit_conditions) ? "WHERE " . implode(" AND ", $suit_conditions) : "";

    $suit_quantity_sql = "SELECT COALESCE(SUM(bi.suit_quantity), 0) as total_suit_quantity 
                          FROM billing_items bi 
                          INNER JOIN billing_header bh ON bi.bill_id = bh.id 
                          $suit_where_clause";

    $suit_quantity_stmt = $pdo->prepare($suit_quantity_sql);
    foreach ($suit_params as $key => $value) {
        $suit_quantity_stmt->bindValue($key, $value);
    }
    $suit_quantity_stmt->execute();
    $suit_quantity_result = $suit_quantity_stmt->fetch(PDO::FETCH_ASSOC);
    $total_pending_suit_quantity = $suit_quantity_result['total_suit_quantity'] ?? 0;
    
    // Calculate total COLLECTED suit quantity
    $collected_suit_conditions = [];
    $collected_suit_params = [];

    // Only include collected orders
    $collected_suit_conditions[] = "bh.order_status = 'collected'";

    // Add customer filter if present
    if ($customer_filter) {
        $collected_suit_conditions[] = "(bh.customer_name LIKE :collected_customer OR bh.customer_address LIKE :collected_customer OR bh.customer_phone LIKE :collected_customer)";
        $collected_suit_params[':collected_customer'] = '%' . $customer_filter . '%';
    }

    // Add date filters if present
    if ($date_from && $date_to) {
        $collected_suit_conditions[] = "DATE(bh.billing_date) BETWEEN :collected_date_from AND :collected_date_to";
        $collected_suit_params[':collected_date_from'] = $date_from;
        $collected_suit_params[':collected_date_to'] = $date_to;
    } elseif ($date_from) {
        $collected_suit_conditions[] = "DATE(bh.billing_date) >= :collected_date_from";
        $collected_suit_params[':collected_date_from'] = $date_from;
    } elseif ($date_to) {
        $collected_suit_conditions[] = "DATE(bh.billing_date) <= :collected_date_to";
        $collected_suit_params[':collected_date_to'] = $date_to;
    }

    $collected_suit_where_clause = !empty($collected_suit_conditions) ? "WHERE " . implode(" AND ", $collected_suit_conditions) : "";

    $collected_suit_quantity_sql = "SELECT COALESCE(SUM(bi.suit_quantity), 0) as total_collected_suit_quantity 
                                    FROM billing_items bi 
                                    INNER JOIN billing_header bh ON bi.bill_id = bh.id 
                                    $collected_suit_where_clause";

    $collected_suit_quantity_stmt = $pdo->prepare($collected_suit_quantity_sql);
    foreach ($collected_suit_params as $key => $value) {
        $collected_suit_quantity_stmt->bindValue($key, $value);
    }
    $collected_suit_quantity_stmt->execute();
    $collected_suit_quantity_result = $collected_suit_quantity_stmt->fetch(PDO::FETCH_ASSOC);
    $total_collected_suit_quantity = $collected_suit_quantity_result['total_collected_suit_quantity'] ?? 0;
    
    // Get order status counts for summary
    $status_counts = $pdo->query("SELECT 
        SUM(CASE WHEN order_status = 'collected' THEN 1 ELSE 0 END) as collected,
        SUM(CASE WHEN order_status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN order_status = 'pending' AND date_collected IS NOT NULL AND date_collected < CURDATE() THEN 1 ELSE 0 END) as overdue,
        SUM(CASE WHEN order_status = 'pending' AND date_collected = CURDATE() THEN 1 ELSE 0 END) as today
        FROM billing_header")->fetch(PDO::FETCH_ASSOC);
    ?>
    
    <!-- Summary Cards - Including Total Suit Quantity Cards -->
    <div class="row no-print">
        <div class="col-md-4 col-sm-6">
            <div class="summary-card bills">
                <div class="number"><?php echo $summary['total_bills'] ?? 0; ?></div>
                <div class="label">Total Bills</div>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="summary-card suit-quantity">
                <div class="number"><?php echo number_format($total_pending_suit_quantity); ?></div>
                <div class="label">Pending Suit Qty</div>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="summary-card discount">
                <div class="number">Rs<?php echo number_format($summary['total_remaining'] ?? 0, 2); ?></div>
                <div class="label">Total Remaining</div>
            </div>
        </div>
    </div>

    <!-- Second row for Total Amount -->
    <div class="row no-print">
        <div class="col-md-4 col-sm-6">
            <div class="summary-card collected-suit">
                <div class="number"><?php echo number_format($total_collected_suit_quantity); ?></div>
                <div class="label">Collected Suit Qty</div>
            </div>
        </div>
        <div class="col-md-4 col-md-6">
            <div class="summary-card amount">
                <div class="number">Rs<?php echo number_format($summary['total_amount'] ?? 0, 2); ?></div>
                <div class="label">Total Amount</div>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="summary-card advance">
                <div class="number">Rs<?php echo number_format($summary['total_advance'] ?? 0, 2); ?></div>
                <div class="label">Total Advance</div>
            </div>
        </div>
    </div>
    
    <!-- Optional: Show Additional Info Row -->
    <?php if(($summary['total_remaining'] ?? 0) > 0): ?>
    <div class="row no-print">
        <div class="col-md-12">
            <div class="alert alert-warning">
                <i class="fa fa-info-circle"></i> 
                Total Discount: <strong>Rs <?php echo number_format($summary['total_discount'] ?? 0, 2); ?></strong>
                | Total Items Quantity: <strong><?php echo number_format($total_quantity); ?></strong> pieces
                | Pending Suits: <strong><?php echo number_format($total_pending_suit_quantity); ?></strong>
                | Collected Suits: <strong><?php echo number_format($total_collected_suit_quantity); ?></strong>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="contentpanel">
        <div class="panel panel-default">
            <div class="panel-heading clearfix">
                <h4 class="panel-title pull-left">Filter Bills</h4>
                <div class="pull-right">
                    <button type="button" class="btn btn-success btn-sm" onclick="viewFilteredRecords()" style="margin-left:5px;">
                        <i class="fa fa-eye"></i> View Filtered Records
                    </button>
                    <button class="btn btn-warning btn-sm no-print" onclick="printSummary()">
                        <i class="fa fa-print"></i> Print Summary
                    </button>
                    <a href="add_billing.php" class="btn btn-primary btn-sm">
                        <i class="fa fa-plus"></i> Create New Bill
                    </a>
                </div>
            </div>
            
            <!-- Filter Form -->
            <div class="panel-body no-print">
                <form method="GET" action="" class="form-inline">
                    <div class="form-group" style="position: relative; display: inline-block; margin-right: 15px;">
                        <label>Customer Name:</label>
                        <button type="button" class="btn btn-default dropdown-toggle" 
                                id="customerDropdownBtn" 
                                data-toggle="dropdown" 
                                aria-haspopup="true" 
                                aria-expanded="false"
                                style="width: 220px; text-align: left; background-color: white; border: 1px solid #ccc; padding: 6px 12px;">
                            <span id="selectedCustomer"><?php echo htmlspecialchars($customer_filter) ?: 'All Customers'; ?></span>
                            <span class="caret" style="float: right; margin-top: 8px;"></span>
                        </button>
                        
                        <div class="dropdown-menu" aria-labelledby="customerDropdownBtn" 
                             style="width: 220px; padding: 10px; max-height: 400px; overflow-y: auto;">
                            
                            <!-- Search Input Inside Dropdown -->
                            <div style="padding: 0 5px 10px 5px;">
                                <input type="text" 
                                       id="customerSearch" 
                                       class="form-control input-sm" 
                                       placeholder="Search by name, phone or address..."
                                       autocomplete="off"
                                       style="width: 100%;">
                            </div>
                            
                            <!-- Dropdown Items Container -->
                            <div id="customerList" style="max-height: 330px; overflow-y: auto;">
                                <div class="customer-option" data-value="" data-name="All Customers" data-phone="" data-address="">
                                    <a href="#" style="display: block; padding: 8px 10px; color: #333; text-decoration: none;">
                                        <strong>All Customers</strong>
                                    </a>
                                </div>
                                <?php 
                                $customers_query = "SELECT DISTINCT 
                                                    bh.customer_name, 
                                                    bh.customer_phone, 
                                                    bh.customer_address 
                                                   FROM billing_header bh 
                                                   ORDER BY bh.customer_name";
                                $customers = $pdo->query($customers_query);
                                if ($customers && $customers->rowCount() > 0):
                                    while ($customer = $customers->fetch(PDO::FETCH_ASSOC)): 
                                ?>
                                    <div class="customer-option" 
                                         data-value="<?php echo htmlspecialchars($customer['customer_name']); ?>" 
                                         data-name="<?php echo htmlspecialchars($customer['customer_name']); ?>"
                                         data-phone="<?php echo htmlspecialchars($customer['customer_phone']); ?>"
                                         data-address="<?php echo htmlspecialchars($customer['customer_address']); ?>">
                                        <a href="#" style="display: block; padding: 8px 10px; color: #333; text-decoration: none;">
                                            <strong><?php echo htmlspecialchars($customer['customer_name']); ?></strong>
                                            <?php if(!empty($customer['customer_phone'])): ?>
                                                <br><small style="color: #666;">📞 <?php echo htmlspecialchars($customer['customer_phone']); ?></small>
                                            <?php endif; ?>
                                            <?php if(!empty($customer['customer_address'])): ?>
                                                <br><small style="color: #888;">S/N: <?php echo htmlspecialchars(substr($customer['customer_address'], 0, 50)); ?></small>
                                            <?php endif; ?>
                                        </a>
                                    </div>
                                <?php 
                                    endwhile;
                                endif;
                                ?>
                            </div>
                        </div>
                        
                        <!-- Hidden inputs for form submission -->
                        <input type="hidden" name="customer" id="selectedCustomerValue" value="<?php echo htmlspecialchars($customer_filter); ?>">
                        <input type="hidden" name="customer_phone" id="selectedCustomerPhone">
                        <input type="hidden" name="customer_address" id="selectedCustomerAddress">
                    </div>
                    
                    <div class="form-group">
                        <label>From Date:</label>
                        <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>" style="margin-right: 15px;">
                    </div>
                    
                    <div class="form-group">
                        <label>To Date:</label>
                        <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>" style="margin-right: 15px;">
                    </div>
                    <button style="margin-top:10px" type="submit" class="btn btn-primary"><i class="fa fa-filter"></i> Filter</button>
                    <a style="margin-top:10px" href="?" class="btn btn-default">Clear</a>
                    <div style="padding-top:12px" class="form-group">
                        <label>Order Status:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label>
                        <select name="order_status" class="form-control" style="width: 200px; margin-right: 15px;">
                            <option value="">All Orders</option>
                            <option value="pending" <?php echo ($order_status_filter == 'pending') ? 'selected' : ''; ?>>Pending Collection</option>
                            <option value="overdue" <?php echo ($order_status_filter == 'overdue') ? 'selected' : ''; ?>>Overdue</option>
                            <option value="today" <?php echo ($order_status_filter == 'today') ? 'selected' : ''; ?>>Due Today</option>
                            <option value="collected" <?php echo ($order_status_filter == 'collected') ? 'selected' : ''; ?>>Collected</option>
                        </select>
                    </div>
                    
                    <div style="padding-top:12px" class="form-group">
                        <label>Payment Status:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label>
                        <select name="payment_status" class="form-control" style="width: 200px; margin-right: 15px;">
                            <option value="">All Payments</option>
                            <option value="partial" <?php echo ($payment_status_filter == 'partial') ? 'selected' : ''; ?>>Partial Payment</option>
                            <option value="paid" <?php echo ($payment_status_filter == 'paid') ? 'selected' : ''; ?>>Fully Paid</option>
                            <option value="pending" <?php echo ($payment_status_filter == 'pending') ? 'selected' : ''; ?>>No Payment</option>
                        </select>
                    </div>
                </form>
            </div>
            
            <div class="panel-body">
                <?php
                // Handle delete action
                if(isset($_GET['delete'])) {
                    $id = $_GET['delete'];
                    try {
                        $pdo->beginTransaction();
                        
                        $stmt = $pdo->prepare("DELETE FROM billing_items WHERE bill_id = ?");
                        $stmt->execute([$id]);
                        
                        $stmt = $pdo->prepare("DELETE FROM billing_header WHERE id = ?");
                        $stmt->execute([$id]);
                        
                        $pdo->commit();
                        
                        echo "<div class='alert alert-success alert-dismissable'>
                            <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>
                            <i class='fa fa-check'></i> Bill deleted successfully!
                        </div>";
                        
                    } catch(PDOException $e) {
                        $pdo->rollBack();
                        echo "<div class='alert alert-danger alert-dismissable'>
                            <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>
                            <i class='fa fa-exclamation-triangle'></i> Error deleting bill: " . $e->getMessage() . "
                        </div>";
                    }
                }
                
                // Handle edit message
                if(isset($_GET['updated'])) {
                    echo "<div class='alert alert-success alert-dismissable'>
                        <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>
                        <i class='fa fa-check'></i> Bill updated successfully!
                    </div>";
                }
                
                if(isset($_GET['created'])) {
                    echo "<div class='alert alert-success alert-dismissable'>
                        <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>
                        <i class='fa fa-check'></i> New bill created successfully!
                    </div>";
                }
                
                if(isset($_GET['printed'])) {
                    echo "<div class='alert alert-info alert-dismissable'>
                        <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>
                        <i class='fa fa-print'></i> Bill printed successfully!
                    </div>";
                }
                
                // Handle order marked as collected via AJAX
                if(isset($_GET['order_marked'])) {
                    echo "<div class='alert alert-success alert-dismissable'>
                        <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>
                        <i class='fa fa-check'></i> Order marked as collected successfully!
                    </div>";
                }
                ?>
                
                <div class="table-responsive">
                    <table class="table table-striped table-bordered" id="billingTable">
                        <thead>
                            <tr>
                                <th>Bill No</th>
                                <th>Customer</th>
                                <th>Contact</th>
                                <th>Items & Qty</th>
                                <th class="text-right">Amount</th>
                                <th>Payment</th>
                                <th>Date</th>
                                <th>Order Status</th>
                                <th>Collection Date</th>
                                <th class="no-print">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Get all bills with item count and total quantity
                            $sql = "SELECT 
                                bh.*, 
                                COUNT(bi.id) as item_count,
                                COALESCE(SUM(bi.quantity), 0) as total_quantity,
                                COALESCE(SUM(bi.suit_quantity), 0) as total_suit_quantity
                                FROM billing_header bh 
                                LEFT JOIN billing_items bi ON bh.id = bi.bill_id 
                                $where_clause
                                GROUP BY bh.id 
                                ORDER BY bh.billing_date DESC, bh.id DESC";

                            $stmt = $pdo->prepare($sql);
                            foreach ($params as $key => $value) {
                                $stmt->bindValue($key, $value);
                            }
                            $stmt->execute();
                            $bills = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            // Calculate totals for print summary
                            $total_amount_sum = 0;
                            $total_discount_sum = 0;
                            $total_advance_sum = 0;
                            $customer_totals = [];

                            // Arrays to store order status counts
                            $pending_count = 0;
                            $collected_count = 0;
                            $overdue_count = 0;
                            $today_count = 0;

                            if(count($bills) > 0) {
                                foreach($bills as $bill) {
                                    // Determine order collection status from billing_header
                                    $order_status_class = '';
                                    $order_status_text = '';
                                    $collection_date = $bill['date_collected'];
                                    $days_status = '';
                                    $can_mark_collected = false;
                                    
                                    if($bill['order_status'] == 'collected') {
                                        $order_status_class = 'order-status-collected';
                                        $order_status_text = '✓ Collected';
                                        $collected_count++;
                                        $can_mark_collected = false;
                                    } else {
                                        // Pending status - check if overdue or due today
                                        $pending_count++;
                                        $can_mark_collected = true;
                                        
                                        if(!empty($collection_date) && $collection_date != '0000-00-00') {
                                            $collection_seconds = strtotime($collection_date);
                                            $today_seconds = strtotime(date('Y-m-d'));
                                            $diff_seconds = $collection_seconds - $today_seconds;
                                            $diff_days = round($diff_seconds / 86400);
                                            
                                            if($diff_days < 0) {
                                                $overdue_days = abs($diff_days);
                                                $order_status_class = 'order-status-overdue';
                                                $order_status_text = '⚠ OVERDUE';
                                                $days_status = 'Overdue by ' . $overdue_days . ' day(s)';
                                                $overdue_count++;
                                            } 
                                            elseif($diff_days == 0) {
                                                $order_status_class = 'order-status-today';
                                                $order_status_text = '📅 Due TODAY';
                                                $days_status = 'Due today!';
                                                $today_count++;
                                            } 
                                            else {
                                                $order_status_class = 'order-status-pending';
                                                $order_status_text = '⏳ Pending';
                                                $days_status = 'Due in ' . $diff_days . ' day(s)';
                                            }
                                        } else {
                                            $order_status_class = 'order-status-pending';
                                            $order_status_text = '⏳ Pending';
                                            $days_status = 'No collection date set';
                                        }
                                    }
                                    
                                    $total_amount_sum += $bill['total_amount'];
                                    $total_discount_sum += $bill['discount'];
                                    $total_advance_sum += $bill['advance_payment'];
                                    
                                    // Track customer-wise totals
                                    if (!isset($customer_totals[$bill['customer_name']])) {
                                        $customer_totals[$bill['customer_name']] = [
                                            'amount' => 0,
                                            'discount' => 0,
                                            'advance' => 0,
                                            'count' => 0,
                                            'phone' => $bill['customer_phone']
                                        ];
                                    }
                                    $customer_totals[$bill['customer_name']]['amount'] += $bill['total_amount'];
                                    $customer_totals[$bill['customer_name']]['discount'] += $bill['discount'];
                                    $customer_totals[$bill['customer_name']]['advance'] += $bill['advance_payment'];
                                    $customer_totals[$bill['customer_name']]['count']++;
                                    
                                    // Payment method status
                                    $status_class = '';
                                    $status_text = '';
                                    
                                    switch($bill['payment_method']) {
                                        case 'Cash':
                                            $status_class = 'success';
                                            $status_text = 'Cash';
                                            break;
                                        case 'Card':
                                            $status_class = 'primary';
                                            $status_text = 'EasyPaisa';
                                            break;
                                        case 'UPI':
                                            $status_class = 'info';
                                            $status_text = 'Bank';
                                            break;
                                        case 'Credit':
                                            $status_class = 'warning';
                                            $status_text = 'POS';
                                            break;
                                        default:
                                            $status_class = 'default';
                                            $status_text = $bill['payment_method'];
                                            break;
                                    }
                                    
                                    // Payment status
                                    $payment_status_class = '';
                                    $payment_status_text = '';
                                    if($bill['advance_payment'] == 0) {
                                        $payment_status_class = 'default';
                                        $payment_status_text = 'Pending';
                                    } elseif($bill['advance_payment'] > 0 && $bill['advance_payment'] < $bill['total_amount']) {
                                        $payment_status_class = 'warning';
                                        $payment_status_text = 'Partial';
                                    } elseif($bill['advance_payment'] >= $bill['total_amount']) {
                                        $payment_status_class = 'success';
                                        $payment_status_text = 'Paid';
                                    } else {
                                        $payment_status_class = 'default';
                                        $payment_status_text = 'Pending';
                                    }
                                    
                                    // Prepare items display with quantities
                                    $items_display = '';
                                    if($bill['item_count'] > 0) {
                                        $items_display = '<span class="badge badge-items" data-toggle="tooltip" title="' . $bill['total_quantity'] . ' total pieces, ' . $bill['total_suit_quantity'] . ' suits">';
                                        $items_display .= $bill['item_count'] . ' item(s)';
                                        $items_display .= '</span><br>';
                                        
                                        if($bill['total_suit_quantity'] > 0) {
                                            $items_display .= '<small class="text-info">👔 Suits: ' . $bill['total_suit_quantity'] . '</small><br>';
                                        }
                                        
                                        $items_display .= '<button class="btn btn-xs btn-default view-items mt-1" 
                                                                data-bill-id="' . $bill['id'] . '"
                                                                data-bill-no="' . htmlspecialchars($bill['bill_no']) . '"
                                                                data-toggle="modal" data-target="#itemsModal"
                                                                style="margin-top: 5px;">
                                                                <i class="fa fa-list"></i> Details
                                                            </button>';
                                    } else {
                                        $items_display = '<span class="text-muted">No items</span>';
                                    }
                                    ?>
                                    <tr>
                                        <td>
                                            <span class="bill-no"><?php echo htmlspecialchars($bill['bill_no']); ?></span>
                                        </td>
                                        <td>
                                            <span class="customer-name"><?php echo htmlspecialchars($bill['customer_name']); ?></span><br>
                                            <small class="text-muted"><?php 
                                                if(!empty($bill['customer_address'])) {
                                                    echo substr(htmlspecialchars($bill['customer_address']), 0, 30);
                                                    if(strlen($bill['customer_address']) > 30) echo '...';
                                                }
                                            ?></small>
                                        </td>
                                        <td>
                                            <?php echo !empty($bill['customer_phone']) ? htmlspecialchars($bill['customer_phone']) : '<span class="text-muted">N/A</span>'; ?>
                                            <?php if($bill['advance_payment'] > 0): ?>
                                                <br><small class="text-info">Adv: Rs<?php echo number_format($bill['advance_payment'], 2); ?></small>
                                            <?php endif; ?>
                                            <?php if($bill['remaining_amount'] > 0): ?>
                                                <br><small class="text-warning">Rem: Rs<?php echo number_format($bill['remaining_amount'], 2); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-left">
                                            <?php echo $items_display; ?>
                                        </td>
                                        <td class="text-right total-amount">
                                            Rs<?php echo number_format($bill['total_amount'], 2); ?><br>
                                            <small class="text-muted">
                                                <span class="text-danger">-Rs<?php echo number_format($bill['discount'], 2); ?></span>
                                            </small>
                                        </td>
                                        <td>
                                            <span class="label label-<?php echo $status_class; ?>">
                                                <?php echo $status_text; ?>
                                            </span>
                                            <br>
                                            <span class="label label-<?php echo $payment_status_class; ?>">
                                                <?php echo $payment_status_text; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo date('d/m/Y', strtotime($bill['billing_date'])); ?><br>
                                            <small><?php echo date('h:i A', strtotime($bill['billing_date'])); ?></small>
                                        </td>
                                        <td class="text-center">
                                            <span class="<?php echo $order_status_class; ?>">
                                                <?php echo $order_status_text; ?>
                                            </span>
                                            <?php if($days_status): ?>
                                                <br><small><?php echo $days_status; ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if(!empty($collection_date) && $collection_date != '0000-00-00'): ?>
                                                <?php echo date('d/m/Y', strtotime($collection_date)); ?>
                                            <?php else: ?>
                                                <span class="text-muted">Not set</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="no-print">
                                            <div class="btn-group btn-group-xs">
                                                <a href="print_bill.php?id=<?php echo $bill['id']; ?>" 
                                                   target="_blank" class="btn btn-info" title="Print Bill">
                                                    <i class="fa fa-print"></i>
                                                </a>
                                                <a href="add_billing.php?edit_id=<?php echo $bill['id']; ?>" 
                                                   class="btn btn-warning" title="Edit Bill">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-primary view-items" 
                                                        data-bill-id="<?php echo $bill['id']; ?>"
                                                        data-bill-no="<?php echo htmlspecialchars($bill['bill_no']); ?>"
                                                        data-toggle="modal" data-target="#itemsModal"
                                                        title="View Items">
                                                    <i class="fa fa-eye"></i>
                                                </button>
                                                <?php if($can_mark_collected): ?>
                                                <button type="button" class="btn btn-success" 
                                                        onclick="markOrderCollected(<?php echo $bill['id']; ?>, '<?php echo addslashes($bill['customer_name']); ?>')"
                                                        title="Mark as Collected">
                                                    <i class="fa fa-check"></i>
                                                </button>
                                                <?php endif; ?>
                                                <a href="?delete=<?php echo $bill['id']; ?>&customer=<?php echo urlencode($customer_filter); ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>&order_status=<?php echo $order_status_filter; ?>&payment_status=<?php echo urlencode($payment_status_filter); ?>" 
                                                   class="btn btn-danger" 
                                                   onclick="return confirm('Are you sure you want to delete bill <?php echo $bill['bill_no']; ?>?\\n\\nCustomer: <?php echo addslashes($bill['customer_name']); ?>\\nAmount: Rs<?php echo $bill['total_amount']; ?>\\n\\nThis action cannot be undone!')" 
                                                   title="Delete Bill">
                                                    <i class="fa fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            } 
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Items Modal -->
<div class="modal fade bill-items-modal" id="itemsModal" tabindex="-1" role="dialog" aria-labelledby="itemsModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="itemsModalLabel">
                    <i class="fa fa-list"></i> Bill Items - <span id="modalBillNo"></span>
                </h4>
            </div>
            <div class="modal-body">
                <div id="modalLoading" class="text-center" style="padding: 30px;">
                    <i class="fa fa-spinner fa-spin fa-3x"></i>
                    <p>Loading items...</p>
                </div>
                <div id="modalContent" style="display: none;">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <strong>Customer:</strong> <span id="modalCustomer"></span><br>
                                <strong>Date:</strong> <span id="modalDate"></span>
                                <br><strong>Order Status:</strong> <span id="modalOrderStatus"></span>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered items-table-modal">
                            <thead>
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="25%">Brand/Item</th>
                                    <th width="10%" class="text-right">Price</th>
                                    <th width="10%" class="text-center">Qty</th>
                                    <th width="10%" class="text-center">Suit Qty</th>
                                    <th width="15%" class="text-right">Stitching Amt</th>
                                    <th width="10%" class="text-center">Stitch Qty</th>
                                    <th width="12%" class="text-right">Item Total</th>
                                    <th width="13%" class="text-right">Stitch Total</th>
                                </tr>
                            </thead>
                            <tbody id="modalItemsBody">
                            </tbody>
                            <tfoot id="modalItemsFooter">
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <i class="fa fa-times"></i> Close
                </button>
                <a href="#" id="modalPrintBtn" target="_blank" class="btn btn-primary">
                    <i class="fa fa-print"></i> Print Bill
                </a>
                <a href="#" id="modalEditBtn" target="_blank" class="btn btn-warning">
                    <i class="fa fa-edit"></i> Edit Bill
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Print Summary Section -->
<div id="printSummary" class="print-only" style="display: none;">
    <div class="print-summary">
        <div class="print-header">
            <h2>Bills Summary Report</h2>
            <?php if ($date_from || $date_to): ?>
                <p>Period: <?php echo $date_from ? date('d-m-Y', strtotime($date_from)) : 'Start'; ?> 
                to <?php echo $date_to ? date('d-m-Y', strtotime($date_to)) : 'End'; ?></p>
            <?php endif; ?>
            <?php if ($customer_filter): ?>
                <p>Customer: <?php echo htmlspecialchars($customer_filter); ?></p>
            <?php endif; ?>
            <?php if ($order_status_filter): ?>
                <p>Order Status: <?php echo ucfirst($order_status_filter); ?></p>
            <?php endif; ?>
            <?php if ($payment_status_filter): ?>
                <p>Payment Status: <?php echo ucfirst($payment_status_filter); ?></p>
            <?php endif; ?>
            <hr>
        </div>
        
        <h3>Summary Statistics</h3>
        <table border="1" class="summary-table" style="width: 100%; margin-bottom: 20px;">
            <thead>
                <tr>
                    <th>Metric</th>
                    <th>Value</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Total Bills</td>
                    <td class="text-right"><?php echo $summary['total_bills'] ?? 0; ?></td>
                </tr>
                <tr>
                    <td>Total Quantity (Pieces)</td>
                    <td class="text-right"><?php echo number_format($total_quantity); ?></td>
                </tr>
                <tr>
                    <td>Pending Suit Quantity</td>
                    <td class="text-right"><?php echo number_format($total_pending_suit_quantity); ?></td>
                </tr>
                <tr>
                    <td>Collected Suit Quantity</td>
                    <td class="text-right"><?php echo number_format($total_collected_suit_quantity); ?></td>
                </tr>
                <tr>
                    <td>Total Amount</td>
                    <td class="text-right">Rs <?php echo number_format($summary['total_amount'] ?? 0, 2); ?></td>
                </tr>
                <tr>
                    <td>Total Discount</td>
                    <td class="text-right">Rs <?php echo number_format($summary['total_discount'] ?? 0, 2); ?></td>
                </tr>
                <tr>
                    <td>Total Advance</td>
                    <td class="text-right">Rs <?php echo number_format($summary['total_advance'] ?? 0, 2); ?></td>
                </tr>
                <tr>
                    <td>Total Remaining</td>
                    <td class="text-right">Rs <?php echo number_format($summary['total_remaining'] ?? 0, 2); ?></td>
                </tr>
            </tbody>
        </table>
        
        <h3>Order Collection Summary</h3>
        <table border="1" class="summary-table" style="width: 100%; margin-bottom: 20px;">
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Count</th>
                </tr>
            </thead>
            <tbody>
                <tr class="success">
                    <td>✓ Collected</td>
                    <td class="text-center"><?php echo $status_counts['collected'] ?? 0; ?></td>
                </tr>
                <tr class="warning">
                    <td>⏳ Pending</td>
                    <td class="text-center"><?php echo $status_counts['pending'] ?? 0; ?></td>
                </tr>
                <tr class="info">
                    <td>📅 Due Today</td>
                    <td class="text-center"><?php echo $status_counts['today'] ?? 0; ?></td>
                </tr>
                <tr class="danger">
                    <td>⚠ Overdue</td>
                    <td class="text-center"><?php echo $status_counts['overdue'] ?? 0; ?></td>
                </tr>
            </tbody>
        </table>
        
        <div class="print-footer">
            <p>Generated on: <?php echo date('d-m-Y h:i A'); ?></p>
            <p>Bhatti Tailor & Fabric Management System</p>
        </div>
    </div>
</div>

<script src="js/jquery.datatables.min.js"></script>
<script>
$(document).ready(function() {
    // Initialize DataTable
    var table = $('#billingTable').DataTable({
        "paging": true,
        "pageLength": 10,
        "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        "searching": true,
        "ordering": true,
        "info": true,
        "order": [[0, "desc"]],
        "language": {
            "lengthMenu": "Show _MENU_ entries",
            "zeroRecords": `
                <div style="padding:20px;">
                    <i class="fa fa-search" style="font-size:40px; color:#dc3545; margin-bottom:10px;"></i>
                    <div style="font-size:18px; font-weight:600;">No matching records found</div>
                    <div style="color:#777; margin-top:5px;">Try adjusting your search or filters</div>
                </div>
            `,
            "emptyTable": `
                <div style="padding:20px;">
                    <i class="fa fa-file-text-o" style="font-size:40px; color:#17a2b8; margin-bottom:10px;"></i>
                    <div style="font-size:18px; font-weight:600;">No bills found</div>
                    <div style="color:#777; margin-top:5px;">No bills are available in the system yet</div>
                    <div style="margin-top:10px;">
                        <a href="?" class="btn btn-default btn-sm">Clear Filters</a>
                        <a href="add_billing.php" class="btn btn-primary btn-sm">Create Bill</a>
                    </div>
                </div>
            `,
            "info": "Showing _START_ to _END_ of _TOTAL_ entries",
            "infoEmpty": "Showing 0 to 0 of 0 entries",
            "infoFiltered": "(filtered from _MAX_ total entries)",
            "search": "Search bills...",
            "paginate": {
                "first": "First",
                "last": "Last",
                "next": "Next",
                "previous": "Previous"
            }
        },
        "columnDefs": [
            { "orderable": false, "targets": [9] },
            { "className": "text-right", "targets": [4] },
            { "className": "text-center", "targets": [3, 5, 7, 8] }
        ]
    });
    
    $('[data-toggle="tooltip"]').tooltip();
    
    // View items modal
    $(document).on('click', '.view-items', function() {
        var billId = $(this).data('bill-id');
        var billNo = $(this).data('bill-no');
        
        $('#modalBillNo').text(billNo);
        $('#modalPrintBtn').attr('href', 'print_bill.php?id=' + billId);
        $('#modalEditBtn').attr('href', 'add_billing.php?edit_id=' + billId);
        
        $('#modalLoading').show();
        $('#modalContent').hide();
        
        $.ajax({
            url: 'get_bill_details.php',
            type: 'GET',
            data: { bill_id: billId },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#modalCustomer').html(response.bill.customer_name + 
                        (response.bill.customer_phone ? ' (' + response.bill.customer_phone + ')' : ''));
                    $('#modalDate').text(response.bill.billing_date);
                    
                    var orderStatusText = '';
                    if(response.bill.order_status == 'collected') {
                        orderStatusText = '<span class="label label-success">✓ Collected</span>';
                        if(response.bill.date_collected) {
                            orderStatusText += ' on ' + response.bill.date_collected;
                        }
                    } else if(response.bill.order_status == 'pending') {
                        orderStatusText = '<span class="label label-warning">⏳ Pending</span>';
                        if(response.bill.date_collected) {
                            orderStatusText += ' - Due: ' + response.bill.date_collected;
                        }
                    }
                    $('#modalOrderStatus').html(orderStatusText);
                    
                    var itemsHtml = '';
                    var itemCounter = 1;
                    var subtotal = 0;
                    var stitchingTotal = 0;
                    
                    $.each(response.items, function(index, item) {
                        var itemTotal = parseFloat(item.price) * parseInt(item.quantity);
                        var itemStitchingTotal = parseFloat(item.stitching_amount) * parseInt(item.stitching_quantity);
                        var suitQty = item.suit_quantity || 0;
                        
                        itemsHtml += '<tr>' +
                            '<td class="text-center">' + itemCounter + '</td>' +
                            '<td>' + item.brand_name + '</td>' +
                            '<td class="text-right">Rs' + parseFloat(item.price).toFixed(2) + '</td>' +
                            '<td class="text-center">' + item.quantity + '</td>' +
                            '<td class="text-center">' + (suitQty > 0 ? suitQty : '-') + '</td>' +
                            '<td class="text-right">' + 
                                (parseFloat(item.stitching_amount) > 0 ? 'Rs' + parseFloat(item.stitching_amount).toFixed(2) : '-') + 
                            '</td>' +
                            '<td class="text-center">' + 
                                (parseInt(item.stitching_quantity) > 0 ? item.stitching_quantity : '-') + 
                            '</td>' +
                            '<td class="text-right">Rs' + itemTotal.toFixed(2) + '</td>' +
                            '<td class="text-right">' + 
                                (itemStitchingTotal > 0 ? 'Rs' + itemStitchingTotal.toFixed(2) : '-') + 
                            '</td>' +
                            '</tr>';
                        
                        subtotal += itemTotal;
                        stitchingTotal += itemStitchingTotal;
                        itemCounter++;
                    });
                    
                    $('#modalItemsBody').html(itemsHtml);
                    
                    var grandTotal = subtotal + stitchingTotal;
                    var discount = parseFloat(response.bill.discount);
                    var advance = parseFloat(response.bill.advance_payment) || 0;
                    var finalTotal = grandTotal - discount - advance;
                    var remaining = finalTotal - advance;
                    
                    var footerHtml = '<tr class="active">' +
                        '<td colspan="7" class="text-right"><strong>Items Subtotal:</strong></td>' +
                        '<td colspan="2" class="text-right"><strong>Rs ' + subtotal.toFixed(2) + '</strong></td>' +
                        '</tr>' +
                        '<tr>' +
                        '<td colspan="7" class="text-right">Stitching Total:</td>' +
                        '<td colspan="2" class="text-right">Rs ' + stitchingTotal.toFixed(2) + '</td>' +
                        '</tr>' +
                        '<tr class="info">' +
                        '<td colspan="7" class="text-right"><strong>Grand Total:</strong></td>' +
                        '<td colspan="2" class="text-right"><strong>Rs ' + grandTotal.toFixed(2) + '</strong></td>' +
                        '</tr>' +
                        '<tr class="danger">' +
                        '<td colspan="7" class="text-right">Discount:</td>' +
                        '<td colspan="2" class="text-right">- Rs ' + discount.toFixed(2) + '</td>' +
                        '</tr>' +
                        '<tr class="warning">' +
                        '<td colspan="7" class="text-right">Advance Payment:</td>' +
                        '<td colspan="2" class="text-right">- Rs ' + advance.toFixed(2) + '</td>' +
                        '</tr>' +
                        '<tr class="success">' +
                        '<td colspan="7" class="text-right"><strong>Final Amount:</strong></td>' +
                        '<td colspan="2" class="text-right"><strong>Rs ' + finalTotal.toFixed(2) + '</strong></td>' +
                        '</tr>';
                    
                    if(remaining > 0) {
                        footerHtml += '<tr class="warning">' +
                            '<td colspan="7" class="text-right"><strong>Remaining Amount:</strong></td>' +
                            '<td colspan="2" class="text-right"><strong>Rs ' + remaining.toFixed(2) + '</strong></td>' +
                            '</tr>';
                    }
                    
                    $('#modalItemsFooter').html(footerHtml);
                    
                    $('#modalLoading').hide();
                    $('#modalContent').show();
                } else {
                    $('#modalLoading').html('<div class="alert alert-danger">' + response.message + '</div>');
                }
            },
            error: function() {
                $('#modalLoading').html('<div class="alert alert-danger">Error loading bill details. Please try again.</div>');
            }
        });
    });
    
    $('#itemsModal').on('hidden.bs.modal', function () {
        $('#modalLoading').html('<i class="fa fa-spinner fa-spin fa-3x"></i><p>Loading items...</p>').show();
        $('#modalContent').hide();
        $('#modalItemsBody').html('');
        $('#modalItemsFooter').html('');
    });

    // ========== CUSTOMER DROPDOWN FUNCTIONALITY ==========
    var $dropdownBtn = $('#customerDropdownBtn');
    var $dropdownMenu = $('.dropdown-menu');
    var $searchInput = $('#customerSearch');
    var $customerOptions = $('.customer-option');
    var $selectedCustomer = $('#selectedCustomer');
    var $selectedCustomerValue = $('#selectedCustomerValue');
    var $selectedCustomerPhone = $('#selectedCustomerPhone');
    var $selectedCustomerAddress = $('#selectedCustomerAddress');
    var $selectedCustomerDetails = $('#selectedCustomerDetails');
    var $displayCustomerName = $('#displayCustomerName');
    var $displayCustomerPhone = $('#displayCustomerPhone');
    var $displayCustomerAddress = $('#displayCustomerAddress');
    
    // Toggle dropdown
    $dropdownBtn.on('click', function(e) {
        e.stopPropagation();
        $dropdownMenu.toggleClass('open');
        if ($dropdownMenu.hasClass('open')) {
            setTimeout(function() {
                $searchInput.focus();
            }, 100);
            $searchInput.val('');
            filterCustomers('');
            rebuildOriginalOptions();
        }
    });
    
    // Stop propagation on dropdown menu clicks
    $dropdownMenu.on('click', function(e) {
        e.stopPropagation();
    });
    
    $searchInput.on('click', function(e) {
        e.stopPropagation();
    });
    
    // Filter customers
    $searchInput.on('keyup', function(e) {
        e.stopPropagation();
        var searchTerm = $(this).val().toLowerCase();
        filterCustomers(searchTerm);
        if (searchTerm !== '') {
            highlightSearchTerm(searchTerm);
        } else {
            rebuildOriginalOptions();
        }
    });
    
    function filterCustomers(searchTerm) {
        // Clean search text
        searchTerm = $.trim(searchTerm.toLowerCase());

        // Remove multiple spaces
        searchTerm = searchTerm.replace(/\s+/g, ' ');

        if (searchTerm === '') {
            $customerOptions.show();
            return;
        }

        $customerOptions.each(function () {
            // Get values safely
            var customerName = String($(this).data('name') || '')
                .toLowerCase()
                .replace(/\s+/g, ' ')
                .trim();

            var customerPhone = String($(this).data('phone') || '')
                .toLowerCase()
                .replace(/\s+/g, ' ')
                .trim();

            var customerAddress = String($(this).data('address') || '')
                .toLowerCase()
                .replace(/\s+/g, ' ')
                .trim();

            // Combined searchable text
            var fullText = customerName + ' ' + customerPhone + ' ' + customerAddress;

            // Better search matching
            if (fullText.includes(searchTerm)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    }
    
    function highlightSearchTerm(searchTerm) {
        if (!searchTerm) return;
        var regex = new RegExp('(' + searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
        
        $customerOptions.each(function() {
            var $this = $(this);
            var value = $this.data('value');
            if (value === '') return;
            
            var name = $this.data('name');
            var phone = $this.data('phone');
            var address = $this.data('address');
            
            var highlightedName = name.replace(regex, '<span class="search-highlight">$1</span>');
            var html = '<a href="#" style="display: block; padding: 8px 10px; color: #333; text-decoration: none;">';
            html += '<strong>' + highlightedName + '</strong>';
            if (phone) {
                var highlightedPhone = phone.replace(regex, '<span class="search-highlight">$1</span>');
                html += '<br><small style="color: #666;">📞 ' + highlightedPhone + '</small>';
            }
            if (address) {
                var highlightedAddress = address.substring(0, 50).replace(regex, '<span class="search-highlight">$1</span>');
                html += '<br><small style="color: #888;">S/N: ' + highlightedAddress + '</small>';
            }
            html += '</a>';
            $this.html(html);
        });
    }
    
    function rebuildOriginalOptions() {
        $customerOptions.each(function() {
            var $this = $(this);
            var value = $this.data('value');
            var name = $this.data('name');
            var phone = $this.data('phone');
            var address = $this.data('address');
            
            if (value === '') {
                $this.html('<a href="#" style="display: block; padding: 8px 10px; color: #333; text-decoration: none;"><strong>All Customers</strong></a>');
            } else {
                var html = '<a href="#" style="display: block; padding: 8px 10px; color: #333; text-decoration: none;">';
                html += '<strong>' + name + '</strong>';
                if (phone) {
                    html += '<br><small style="color: #666;">📞 ' + phone + '</small>';
                }
                if (address) {
                    html += '<br><small style="color: #888;">S/N: ' + address.substring(0, 50) + '</small>';
                }
                html += '</a>';
                $this.html(html);
            }
        });
    }
    
    // Handle customer selection - JUST SELECT, DON'T SUBMIT
    $customerOptions.off('click').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var customerValue = $(this).data('value');
        var customerName = $(this).data('name');
        var customerPhone = $(this).data('phone') || '';
        var customerAddress = $(this).data('address') || '';
        
        $selectedCustomer.text(customerName);
        $selectedCustomerValue.val(customerValue);
        $selectedCustomerPhone.val(customerPhone);
        $selectedCustomerAddress.val(customerAddress);
        
        if (customerValue !== '') {
            $displayCustomerName.html('<strong>Name:</strong> ' + customerName);
            $displayCustomerPhone.html('<strong>Phone:</strong> ' + (customerPhone || 'N/A'));
            $displayCustomerAddress.html('<strong>Address:</strong> ' + (customerAddress || 'N/A'));
            $selectedCustomerDetails.show();
        } else {
            $selectedCustomerDetails.hide();
        }
        
        $dropdownMenu.removeClass('open');
    });
    
    // Close dropdown when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.form-group').length && 
            !$(e.target).closest('.dropdown-menu').length && 
            !$(e.target).is('#customerDropdownBtn')) {
            $dropdownMenu.removeClass('open');
        }
    });
    
    // Keyboard navigation
    $searchInput.on('keydown', function(e) {
        var $visibleOptions = $customerOptions.filter(':visible');
        var $highlighted = $customerOptions.filter('.active');
        var index = $visibleOptions.index($highlighted);
        
        switch(e.keyCode) {
            case 40:
                e.preventDefault();
                e.stopPropagation();
                if ($highlighted.length === 0 || index === $visibleOptions.length - 1) {
                    $customerOptions.removeClass('active');
                    $visibleOptions.first().addClass('active');
                } else {
                    $highlighted.removeClass('active');
                    $visibleOptions.eq(index + 1).addClass('active');
                }
                break;
            case 38:
                e.preventDefault();
                e.stopPropagation();
                if ($highlighted.length === 0 || index === 0) {
                    $customerOptions.removeClass('active');
                    $visibleOptions.last().addClass('active');
                } else {
                    $highlighted.removeClass('active');
                    $visibleOptions.eq(index - 1).addClass('active');
                }
                break;
            case 13:
                e.preventDefault();
                e.stopPropagation();
                if ($highlighted.length) {
                    $highlighted.find('a').trigger('click');
                }
                break;
            case 27:
                e.preventDefault();
                e.stopPropagation();
                $dropdownMenu.removeClass('open');
                break;
        }
    });
    
    $dropdownMenu.on('mouseleave', function() {
        $customerOptions.removeClass('active');
    });
    
    if ($selectedCustomerValue.val()) {
        var selectedName = $selectedCustomer.text();
        var selectedPhone = $selectedCustomerPhone.val();
        var selectedAddress = $selectedCustomerAddress.val();
        
        if (selectedName && selectedName !== 'All Customers') {
            $displayCustomerName.html('<strong>Name:</strong> ' + selectedName);
            $displayCustomerPhone.html('<strong>Phone:</strong> ' + (selectedPhone || 'N/A'));
            $displayCustomerAddress.html('<strong>Address:</strong> ' + (selectedAddress || 'N/A'));
            $selectedCustomerDetails.show();
        }
    }
});

function printSummary() {
    // Get the current filter values to apply the same filters in print
    var customer = $('#selectedCustomerValue').val();
    var dateFrom = $('input[name="date_from"]').val();
    var dateTo = $('input[name="date_to"]').val();
    var orderStatus = $('select[name="order_status"]').val();
    var paymentStatus = $('select[name="payment_status"]').val();
    
    // Open print window with all bills
    var printWindow = window.open("", "_blank", "width=1200,height=800");
    printWindow.document.write(`
        <html>
        <head>
            <title>Bills Summary Report</title>
            <style>
                body { 
                    font-family: Arial, sans-serif; 
                    font-size: 12px; 
                    margin: 0; 
                    padding: 20px; 
                }
                .print-header {
                    text-align: center;
                    margin-bottom: 20px;
                    padding-bottom: 10px;
                    border-bottom: 2px solid #333;
                }
                .print-header h2 {
                    margin: 0;
                    color: #333;
                }
                .print-header p {
                    margin: 5px 0;
                    color: #666;
                }
                .summary-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 20px;
                }
                .summary-table th, 
                .summary-table td {
                    border: 1px solid #ddd;
                    padding: 8px;
                    text-align: left;
                }
                .summary-table th {
                    background-color: #f5f5f5;
                    font-weight: bold;
                }
                .bills-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 20px;
                    font-size: 16px;
                }
                .bills-table th, 
                .bills-table td {
                    border: 1px solid #ddd;
                    padding: 6px;
                    text-align: left;
                }
                .bills-table th {
                    background-color: #f0f0f0;
                    font-weight: bold;
                }
                .text-right {
                    text-align: right;
                }
                .text-center {
                    text-align: center;
                }
                .print-footer {
                    margin-top: 30px;
                    text-align: center;
                    font-size: 10px;
                    padding-top: 10px;
                    border-top: 1px solid #ddd;
                }
                .page-break {
                    page-break-before: always;
                }
                .total-row {
                    font-weight: bold;
                    background-color: #f5f5f5;
                }
            </style>
        </head>
        <body>
            <div class="print-summary">
                <div class="print-header">
                    <h2>Bills Summary Report</h2>
                    <p>Generated on: <?php echo date('d-m-Y h:i A'); ?></p>
                    <p>Period: <?php echo $date_from ? date('d-m-Y', strtotime($date_from)) : 'Start'; ?> 
                    to <?php echo $date_to ? date('d-m-Y', strtotime($date_to)) : 'End'; ?></p>
                    <?php if ($customer_filter): ?>
                        <p>Customer: <?php echo htmlspecialchars($customer_filter); ?></p>
                    <?php endif; ?>
                    <?php if ($order_status_filter): ?>
                        <p>Order Status: <?php echo ucfirst($order_status_filter); ?></p>
                    <?php endif; ?>
                    <?php if ($payment_status_filter): ?>
                        <p>Payment Status: <?php echo ucfirst($payment_status_filter); ?></p>
                    <?php endif; ?>
                </div>
                
                <h3>Financial Summary</h3>
                <table class="summary-table">
                    <thead>
                        <tr>
                            <th>Metric</th>
                            <th class="text-right">Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Total Bills</td>
                            <td class="text-right"><?php echo $summary['total_bills'] ?? 0; ?></td>
                        </tr>
                        <tr>
                            <td>Total Amount</td>
                            <td class="text-right">Rs <?php echo number_format($summary['total_amount'] ?? 0, 2); ?></td>
                        </tr>
                        <tr>
                            <td>Total Advance</td>
                            <td class="text-right">Rs <?php echo number_format($summary['total_advance'] ?? 0, 2); ?></td>
                        </tr>
                        <tr>
                            <td>Total Remaining</td>
                            <td class="text-right"><strong>Rs <?php echo number_format($summary['total_remaining'] ?? 0, 2); ?></strong></td>
                        </tr>
                        <tr>
                            <td>Total Discount</td>
                            <td class="text-right">Rs <?php echo number_format($summary['total_discount'] ?? 0, 2); ?></td>
                        </tr>
                        <tr>
                            <td>Total Quantity (Pieces)</td>
                            <td class="text-right"><?php echo number_format($total_quantity); ?></td>
                        </tr>
                        <tr>
                            <td>Pending Suit Quantity</td>
                            <td class="text-right"><?php echo number_format($total_pending_suit_quantity); ?></td>
                        </tr>
                        <tr>
                            <td>Collected Suit Quantity</td>
                            <td class="text-right"><?php echo number_format($total_collected_suit_quantity); ?></td>
                        </tr>
                    </tbody>
                </table>
                
                <h3>Detailed Bills List</h3>
                <table class="bills-table">
                    <thead>
                        <tr>
                            <th>Bill No</th>
                            <th>Customer Name</th>
                            <th>Phone</th>
                            <th>Size No</th>
                            <th class="text-center">Items</th>
                            <th class="text-right">Amount</th>
                            <th class="text-right">Discount</th>
                            <th class="text-right">Advance</th>
                            <th class="text-right">Remaining</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Fetch all bills with current filters for printing
                        $print_sql = "SELECT 
                            bh.*, 
                            COUNT(bi.id) as item_count,
                            COALESCE(SUM(bi.quantity), 0) as total_quantity,
                            COALESCE(SUM(bi.suit_quantity), 0) as total_suit_quantity
                            FROM billing_header bh 
                            LEFT JOIN billing_items bi ON bh.id = bi.bill_id 
                            $where_clause
                            GROUP BY bh.id 
                            ORDER BY bh.billing_date DESC, bh.id DESC";
                        
                        $print_stmt = $pdo->prepare($print_sql);
                        foreach ($params as $key => $value) {
                            $print_stmt->bindValue($key, $value);
                        }
                        $print_stmt->execute();
                        $print_bills = $print_stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        $grand_total_amount = 0;
                        $grand_total_discount = 0;
                        $grand_total_advance = 0;
                        $grand_total_remaining = 0;
                        
                        if(count($print_bills) > 0) {
                            foreach($print_bills as $bill) {
                                $grand_total_amount += $bill['total_amount'];
                                $grand_total_discount += $bill['discount'];
                                $grand_total_advance += $bill['advance_payment'];
                                $grand_total_remaining += $bill['remaining_amount'];
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($bill['bill_no']); ?></td>
                                    <td><?php echo htmlspecialchars($bill['customer_name']); ?></td>
                                    <td><?php echo !empty($bill['customer_phone']) ? htmlspecialchars($bill['customer_phone']) : 'N/A'; ?></td>
                                    <td><?php 
                                        $address = !empty($bill['customer_address']) ? htmlspecialchars($bill['customer_address']) : 'N/A';
                                        echo strlen($address) > 50 ? substr($address, 0, 50) . '...' : $address;
                                    ?></td>
                                    <td class="text-center">
                                        <?php echo $bill['item_count']; ?> item(s)
                                        <?php if($bill['total_suit_quantity'] > 0): ?>
                                            <br><small><?php echo $bill['total_suit_quantity']; ?> suits</small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-right">Rs <?php echo number_format($bill['total_amount'], 2); ?></td>
                                    <td class="text-right">Rs <?php echo number_format($bill['discount'], 2); ?></td>
                                    <td class="text-right">Rs <?php echo number_format($bill['advance_payment'], 2); ?></td>
                                    <td class="text-right"><strong>Rs <?php echo number_format($bill['remaining_amount'], 2); ?></strong></td>
                                    <td class="text-center"><?php echo date('d/m/Y', strtotime($bill['billing_date'])); ?></td>
                                </tr>
                                <?php
                            }
                        } else {
                            ?>
                            <tr>
                                <td colspan="10" class="text-center">No bills found matching the criteria</td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                    <tfoot>
                        <tr class="total-row">
                            <td colspan="4" class="text-right"><strong>GRAND TOTAL:</strong></td>
                            <td class="text-center"><strong><?php echo count($print_bills); ?> Bills</strong></td>
                            <td class="text-right"><strong>Rs <?php echo number_format($grand_total_amount, 2); ?></strong></td>
                            <td class="text-right"><strong>Rs <?php echo number_format($grand_total_discount, 2); ?></strong></td>
                            <td class="text-right"><strong>Rs <?php echo number_format($grand_total_advance, 2); ?></strong></td>
                            <td class="text-right"><strong>Rs <?php echo number_format($grand_total_remaining, 2); ?></strong></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
                
                <div class="print-footer">
                    <p>Bhatti Tailor & Fabric Management System</p>
                </div>
            </div>
        </body>
        </html>
    `);
    
    printWindow.document.close();
    printWindow.focus();
    
    setTimeout(() => {
        printWindow.print();
        printWindow.close();
    }, 500);
}

function viewFilteredRecords() {
    // Get current filter values
    var customer = $('#selectedCustomerValue').val();
    var dateFrom = $('input[name="date_from"]').val();
    var dateTo = $('input[name="date_to"]').val();
    var orderStatus = $('select[name="order_status"]').val();
    var paymentStatus = $('select[name="payment_status"]').val();
    
    // Build URL parameters
    var params = [];
    if (customer) params.push('customer=' + encodeURIComponent(customer));
    if (dateFrom) params.push('date_from=' + encodeURIComponent(dateFrom));
    if (dateTo) params.push('date_to=' + encodeURIComponent(dateTo));
    if (orderStatus) params.push('order_status=' + encodeURIComponent(orderStatus));
    if (paymentStatus) params.push('payment_status=' + encodeURIComponent(paymentStatus));
    
    // Navigate to filtered bills page
    var url = 'filtered_bills.php';
    if (params.length > 0) {
        url += '?' + params.join('&');
    }
    
    // Open in new tab or same window (change _blank to _self for same window)
    window.open(url, '_blank');
}

function markOrderCollected(billId, customerName) {
    if(confirm('Mark order for ' + customerName + ' as COLLECTED?\n\nThis will confirm that the customer has received their suit.')) {
        var now = new Date();
        var year = now.getFullYear();
        var month = String(now.getMonth() + 1).padStart(2, '0');
        var day = String(now.getDate()).padStart(2, '0');
        var hours = String(now.getHours()).padStart(2, '0');
        var minutes = String(now.getMinutes()).padStart(2, '0');
        var seconds = String(now.getSeconds()).padStart(2, '0');
        
        var localDate = year + '-' + month + '-' + day;
        var localTime = hours + ':' + minutes + ':' + seconds;
        var localDateTime = localDate + ' ' + localTime;
        
        $.ajax({
            url: 'update_order_status.php',
            type: 'POST',
            data: {
                bill_id: billId,
                order_status: 'collected',
                date_collected: localDate,
                time_collected: localTime,
                datetime_collected: localDateTime
            },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    alert('✓ Order marked as collected successfully at ' + localTime + '!');
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Error updating order status. Please try again.');
            }
        });
    }
}
</script>

<?php
include ('footer.php');
?>