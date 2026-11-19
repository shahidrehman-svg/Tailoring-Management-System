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

// Get transaction ID
if (!isset($_GET['id'])) {
    redirect('transaction_view.php');
}

$transaction_id = $_GET['id'];

// Fetch transaction details
$query = "SELECT t.*, s.fullname as staff_name, s.phonenumber as staff_phone
          FROM staff_customer_transactions t 
          LEFT JOIN staff s ON t.staff_id = s.id 
          WHERE t.id = :id";
          
$stmt = $pdo->prepare($query);
$stmt->execute([':id' => $transaction_id]);
$transaction = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$transaction) {
    redirect('transaction_view.php');
}

// Fetch customers list for name lookup
$customers_list = [];
$customers_query = $pdo->query("SELECT id, fullname, address FROM customer");
while ($customer = $customers_query->fetch(PDO::FETCH_ASSOC)) {
    $customers_list[$customer['address']] = [
        'name' => $customer['fullname'],
        'id' => $customer['id']
    ];
}

include('header.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Details</title>
    <link href="css/style.default.css" rel="stylesheet">
    <style>
        .detail-panel {
            margin-top: 20px;
        }
        .detail-row {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .detail-label {
            font-weight: bold;
            color: #555;
        }
        .detail-value {
            color: #333;
        }
        .customer-card {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px 15px;
            border-radius: 8px;
            margin: 5px;
            font-size: 13px;
            min-width: 200px;
        }
        .customer-name {
            font-weight: bold;
            font-size: 14px;
        }
        .customer-address {
            font-size: 11px;
            opacity: 0.9;
            margin-top: 3px;
        }
        .amount-box {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            border-left: 4px solid #28a745;
        }
        .additional-amount-box {
            background: #fff3cd;
            padding: 10px;
            border-radius: 5px;
            border-left: 4px solid #ffc107;
        }
        .advance-amount-box {
            background: #d1ecf1;
            padding: 10px;
            border-radius: 5px;
            border-left: 4px solid #17a2b8;
        }
        .balance-box {
            background: #d4edda;
            padding: 10px;
            border-radius: 5px;
            border-left: 4px solid #28a745;
        }
        .calculation-box {
            background: #e7f3fe;
            padding: 10px;
            border-radius: 5px;
            border-left: 4px solid #17a2b8;
            margin-top: 10px;
        }
        .calculation-formula {
            font-family: monospace;
            font-size: 14px;
            color: #495057;
        }
        .amount-row {
            margin-bottom: 15px;
        }
        .additional-amount-label {
            color: #e67e22;
            font-weight: bold;
        }
        .advance-amount-label {
            color: #138496;
            font-weight: bold;
        }
        .calculation-step {
            margin-bottom: 5px;
            padding: 5px;
            background: white;
            border-radius: 3px;
        }
        .final-calculation {
            font-size: 16px;
            font-weight: bold;
            color: #2c3e50;
            background: #d4edda;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
        }
        .customer-list-container {
            max-height: 300px;
            overflow-y: auto;
            padding: 10px;
            background: #f9f9f9;
            border-radius: 8px;
        }
        .design-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
        }
        .design-motta {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #4caf50;
        }
        .design-full {
            background-color: #e3f2fd;
            color: #1565c0;
            border: 1px solid #2196f3;
        }
        .design-paincha {
            background-color: #fff3e0;
            color: #e65100;
            border: 1px solid #ff9800;
        }
        .design-other {
            background-color: #f3e5f5;
            color: #6a1b9a;
            border: 1px solid #9c27b0;
        }
        .design-default {
            background-color: #f5f5f5;
            color: #616161;
            border: 1px solid #9e9e9e;
        }
        .balance-positive {
            color: #e74c3c;
            font-weight: bold;
        }
        .balance-zero {
            color: #27ae60;
            font-weight: bold;
        }
        .balance-negative {
            color: #e67e22;
            font-weight: bold;
        }
    </style>
</head>
<body>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Transaction Details</h1>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-primary detail-panel">
                <div style="background-color: black;" class="panel-heading">
                    <h3 style="color:#ff9c14;" class="panel-title">Transaction #<?php echo $transaction['id']; ?></h3>
                </div>
                <div class="panel-body">
                    <!-- Basic Information -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="detail-row">
                                <div class="detail-label">Staff Name:</div>
                                <div class="detail-value"><?php echo htmlspecialchars($transaction['staff_name']); ?></div>
                            </div>
                            
                            <div class="detail-row">
                                <div class="detail-label">Staff Phone:</div>
                                <div class="detail-value"><?php echo htmlspecialchars($transaction['staff_phone']); ?></div>
                            </div>
                            
                            <div class="detail-row">
                                <div class="detail-label">Design Type(s):</div>
                                <div class="detail-value">
                                    <?php
                                    $design_types = explode(',', $transaction['design_type'] ?? 'Not Specified');
                                    foreach ($design_types as $design) {
                                        $design = trim($design);
                                        $badge_class = 'design-default';
                                        switch($design) {
                                            case 'Motta Chamak':
                                                $badge_class = 'design-motta';
                                                break;
                                            case 'Full Double Chamak':
                                                $badge_class = 'design-full';
                                                break;
                                            case 'Paincha Jali':
                                            case 'Paincha Karai':
                                                $badge_class = 'design-paincha';
                                                break;
                                            case 'Design':
                                                $badge_class = 'design-other';
                                                break;
                                        }
                                        echo "<span class='design-badge {$badge_class}'>" . htmlspecialchars($design) . "</span> ";
                                    }
                                    ?>
                                </div>
                            </div>
                            
                            <div class="detail-row">
                                <div class="detail-label">Transaction Date:</div>
                                <div class="detail-value">
                                    <?php echo date('F d, Y h:i A', strtotime($transaction['transaction_date'])); ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="detail-row">
                                <div class="detail-label">Customer Details:</div>
                                <div class="detail-value">
                                    <div class="customer-list-container">
                                        <?php
                                        $customer_codes = explode(',', $transaction['customer_codes']);
                                        foreach ($customer_codes as $customer_address) {
                                            $customer_address = trim($customer_address);
                                            $customer_name = isset($customers_list[$customer_address]) ? $customers_list[$customer_address]['name'] : 'Unknown Customer';
                                            ?>
                                            <div class="customer-card">
                                                <div class="customer-name">
                                                    <i class="fa fa-user"></i> <?php echo htmlspecialchars($customer_name); ?>
                                                </div>
                                                <div class="customer-address">
                                                   Size No: <?php echo htmlspecialchars($customer_address); ?>
                                                </div>
                                            </div>
                                            <?php
                                        }
                                        ?>
                                    </div>
                                    <small class="text-muted">Total: <?php echo count($customer_codes); ?> customer(s)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Amount Details -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="detail-row">
                                <h4>Amount Details</h4>
                                
                                <!-- Amount boxes -->
                                <div class="row amount-row">
                                    <div class="col-md-3">
                                        <div class="amount-box">
                                            <div class="detail-label">Quantity:</div>
                                            <div class="detail-value" style="font-size: 18px;">
                                                <?php 
                                                $quantity = isset($transaction['quantity']) ? $transaction['quantity'] : 1;
                                                echo $quantity;
                                                ?>
                                            </div>
                                            <small class="text-muted">Number of units</small>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <div class="amount-box">
                                            <div class="detail-label">Unit Amount:</div>
                                            <div class="detail-value" style="font-size: 18px;">
                                                Rs. <?php 
                                                $unit_amount = isset($transaction['unit_amount']) ? $transaction['unit_amount'] : 0;
                                                echo number_format($unit_amount, 2); 
                                                ?>
                                            </div>
                                            <small class="text-muted">Amount per unit</small>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <div class="additional-amount-box">
                                            <div class="detail-label additional-amount-label">Design Amount:</div>
                                            <div class="detail-value" style="font-size: 18px;">
                                                Rs. <?php 
                                                $additional_amount = isset($transaction['additional_amount']) ? $transaction['additional_amount'] : 0;
                                                echo number_format($additional_amount, 2); 
                                                ?>
                                            </div>
                                            <small class="text-muted">Extra amount added</small>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <div class="advance-amount-box">
                                            <div class="detail-label advance-amount-label">Advance Amount:</div>
                                            <div class="detail-value" style="font-size: 18px;">
                                                Rs. <?php 
                                                $advance_amount = isset($transaction['advance_amount']) ? $transaction['advance_amount'] : 0;
                                                echo number_format($advance_amount, 2); 
                                                ?>
                                            </div>
                                            <small class="text-muted">Amount paid in advance</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Total and Balance Row -->
                                <div class="row amount-row">
                                    <div class="col-md-6">
                                        <div class="amount-box" style="border-left-color: #007bff;">
                                            <div class="detail-label">Total Amount:</div>
                                            <div class="detail-value" style="font-size: 20px; font-weight: bold; color: #007bff;">
                                                Rs. <?php 
                                                $calculated_amount = isset($transaction['calculated_amount']) ? $transaction['calculated_amount'] : 0;
                                                echo number_format($calculated_amount, 2); 
                                                ?>
                                            </div>
                                            <small class="text-muted">Final total amount</small>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <?php
                                        $remaining_balance = $calculated_amount - $advance_amount;
                                        $balance_class = ($remaining_balance > 0) ? 'balance-positive' : (($remaining_balance == 0) ? 'balance-zero' : 'balance-negative');
                                        $balance_text = ($remaining_balance > 0) ? 'Due Amount' : (($remaining_balance == 0) ? 'Fully Paid' : 'Overpaid');
                                        ?>
                                        <div class="balance-box">
                                            <div class="detail-label"><?php echo $balance_text; ?>:</div>
                                            <div class="detail-value" style="font-size: 20px; font-weight: bold; color: <?php echo ($remaining_balance > 0) ? '#e74c3c' : (($remaining_balance == 0) ? '#27ae60' : '#e67e22'); ?>;">
                                                Rs. <?php echo number_format(abs($remaining_balance), 2); ?>
                                                <?php if ($remaining_balance < 0): ?>
                                                    <small>(Overpayment)</small>
                                                <?php endif; ?>
                                            </div>
                                            <small class="text-muted">
                                                <?php if ($remaining_balance > 0): ?>
                                                    Remaining amount to be paid
                                                <?php elseif ($remaining_balance == 0): ?>
                                                    Transaction fully settled
                                                <?php else: ?>
                                                    Excess amount paid
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Calculation Formula -->
                                <div class="calculation-box">
                                    <div class="detail-label">Calculation Formula:</div>
                                    <div class="calculation-formula">
                                        <?php
                                        // Calculate base amount
                                        $base_amount = $quantity * $unit_amount;
                                        
                                        // Display step-by-step calculation
                                        echo "<div class='calculation-step'>";
                                        echo "<strong>Step 1:</strong> Base Amount (Quantity × Unit Amount)<br>";
                                        echo "&nbsp;&nbsp;&nbsp;&nbsp;{$quantity} × Rs. " . number_format($unit_amount, 2) . " = Rs. " . number_format($base_amount, 2);
                                        echo "</div>";
                                        
                                        if ($additional_amount > 0) {
                                            echo "<div class='calculation-step'>";
                                            echo "<strong>Step 2:</strong> Add Design Amount<br>";
                                            echo "&nbsp;&nbsp;&nbsp;&nbsp;Rs. " . number_format($base_amount, 2) . " + Rs. " . number_format($additional_amount, 2) . " = Rs. " . number_format(($base_amount + $additional_amount), 2);
                                            echo "</div>";
                                        } else {
                                            echo "<div class='calculation-step'>";
                                            echo "<strong>Step 2:</strong> Design Amount<br>";
                                            echo "&nbsp;&nbsp;&nbsp;&nbsp;Rs. 0.00 (No additional amount)";
                                            echo "</div>";
                                        }
                                        
                                        $after_design = $base_amount + $additional_amount;
                                        echo "<div class='calculation-step'>";
                                        echo "<strong>Step 3:</strong> Total Amount<br>";
                                        echo "&nbsp;&nbsp;&nbsp;&nbsp;= Rs. " . number_format($after_design, 2);
                                        echo "</div>";
                                        
                                        if ($advance_amount > 0) {
                                            echo "<div class='calculation-step'>";
                                            echo "<strong>Step 4:</strong> Advance Payment<br>";
                                            echo "&nbsp;&nbsp;&nbsp;&nbsp;Rs. " . number_format($advance_amount, 2) . " paid in advance";
                                            echo "</div>";
                                            
                                            echo "<div class='calculation-step'>";
                                            echo "<strong>Step 5:</strong> Remaining Balance<br>";
                                            echo "&nbsp;&nbsp;&nbsp;&nbsp;Rs. " . number_format($after_design, 2) . " - Rs. " . number_format($advance_amount, 2) . " = Rs. " . number_format($remaining_balance, 2);
                                            echo "</div>";
                                        }
                                        
                                        echo "<div class='final-calculation'>";
                                        echo "Final Calculated Amount: Rs. " . number_format($calculated_amount, 2);
                                        if ($advance_amount > 0) {
                                            echo "<br>Advance Paid: Rs. " . number_format($advance_amount, 2);
                                            echo "<br>Remaining Balance: Rs. " . number_format($remaining_balance, 2);
                                        }
                                        echo "</div>";
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- For backward compatibility: Show old amount field if calculated_amount doesn't exist -->
                    <?php if (!isset($transaction['calculated_amount']) && isset($transaction['amount'])): ?>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="detail-row">
                                <div class="detail-label">Amount (Old Format):</div>
                                <div class="detail-value">
                                    <strong>Rs. <?php echo number_format($transaction['amount'], 2); ?></strong>
                                </div>
                                <small class="text-muted">Note: This is using the old transaction format without additional amount calculation</small>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Remarks Section -->
                    <?php if (!empty($transaction['remarks'])): ?>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="detail-row">
                                <div class="detail-label">Remarks:</div>
                                <div class="detail-value" style="background: #f9f9f9; padding: 10px; border-radius: 4px;">
                                    <?php echo nl2br(htmlspecialchars($transaction['remarks'])); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Action Buttons -->
                    <div class="row">
                        <div class="col-md-12">
                            <hr>
                            <a href="transaction_view.php" class="btn btn-default">
                                <i class="fa fa-arrow-left"></i> Back to List
                            </a>
                            <a href="transaction_form.php?edit_id=<?php echo $transaction['id']; ?>" class="btn btn-warning">
                                <i class="fa fa-edit"></i> Edit Transaction
                            </a>
                            <a href="transaction_view.php?delete_id=<?php echo $transaction['id']; ?>" 
                               class="btn btn-danger"
                               onclick="return confirm('Are you sure you want to delete this transaction?')">
                                <i class="fa fa-trash"></i> Delete Transaction
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>
</body>
</html>