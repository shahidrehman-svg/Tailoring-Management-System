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

// Initialize variables
$edit_mode = false;
$transaction_id = 0;
$transaction_data = [];
$success = '';
$error = '';

// Check if we're in edit mode
if (isset($_GET['edit_id'])) {
    $edit_mode = true;
    $transaction_id = $_GET['edit_id'];
    
    // Fetch existing transaction data
    try {
        $stmt = $pdo->prepare("SELECT * FROM staff_customer_transactions WHERE id = :id");
        $stmt->execute([':id' => $transaction_id]);
        $transaction_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$transaction_data) {
            $error = "Transaction not found!";
            $edit_mode = false;
        }
    } catch (PDOException $e) {
        $error = "Error fetching transaction: " . $e->getMessage();
        $edit_mode = false;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $staff_id = $_POST['staff_id'] ?? '';
    $customer_codes = $_POST['customer_codes'] ?? [];
    $unit_amount = $_POST['unit_amount'] ?? '';
    $quantity = $_POST['quantity'] ?? '';
    $additional_amount = $_POST['additional_amount'] ?? '0';
    $advance_amount = $_POST['advance_amount'] ?? '0';
    $calculated_amount = $_POST['calculated_amount'] ?? '';
    $design_types = $_POST['design_types'] ?? [];
    $transaction_date = $_POST['transaction_date'] ?? date('Y-m-d');
    $form_transaction_id = $_POST['transaction_id'] ?? 0;
    
    // Basic validation - removed design_types from required fields
    if (empty($staff_id) || empty($customer_codes) || empty($unit_amount) || empty($quantity) || empty($calculated_amount) || empty($transaction_date)) {
        $error = "Please fill in all required fields!";
    } else {
        // Convert arrays to comma-separated strings
        $customer_codes_str = is_array($customer_codes) ? implode(',', $customer_codes) : $customer_codes;
        $design_types_str = is_array($design_types) ? implode(',', $design_types) : $design_types;
        
        // Calculate base amount for reference (quantity × unit amount)
        $base_amount = $quantity * $unit_amount;
        
        try {
            // Check and add columns if they don't exist
            $columns_to_check = [
                'quantity' => 'INT DEFAULT 1',
                'unit_amount' => 'DECIMAL(10,2) DEFAULT 0.00',
                'additional_amount' => 'DECIMAL(10,2) DEFAULT 0.00',
                'advance_amount' => 'DECIMAL(10,2) DEFAULT 0.00',
                'calculated_amount' => 'DECIMAL(10,2) DEFAULT 0.00',
                'base_amount' => 'DECIMAL(10,2) DEFAULT 0.00',
                'design_type' => 'VARCHAR(255) DEFAULT NULL',
                'transaction_date' => 'DATE DEFAULT NULL'
            ];
            
            foreach ($columns_to_check as $column_name => $column_definition) {
                $table_check = $pdo->query("SHOW COLUMNS FROM staff_customer_transactions LIKE '$column_name'");
                if ($table_check->rowCount() == 0) {
                    $pdo->exec("ALTER TABLE staff_customer_transactions ADD COLUMN $column_name $column_definition");
                }
            }
            
            if ($edit_mode && $form_transaction_id) {
                // UPDATE existing transaction
                $stmt = $pdo->prepare("UPDATE staff_customer_transactions 
                                       SET staff_id = :staff_id, 
                                           customer_codes = :customer_codes, 
                                           unit_amount = :unit_amount, 
                                           quantity = :quantity,
                                           additional_amount = :additional_amount,
                                           advance_amount = :advance_amount,
                                           calculated_amount = :calculated_amount,
                                           base_amount = :base_amount,
                                           design_type = :design_type,
                                           transaction_date = :transaction_date
                                       WHERE id = :id");
                
                $stmt->execute([
                    ':staff_id' => $staff_id,
                    ':customer_codes' => $customer_codes_str,
                    ':unit_amount' => $unit_amount,
                    ':quantity' => $quantity,
                    ':additional_amount' => $additional_amount,
                    ':advance_amount' => $advance_amount,
                    ':calculated_amount' => $calculated_amount,
                    ':base_amount' => $base_amount,
                    ':design_type' => $design_types_str,
                    ':transaction_date' => $transaction_date,
                    ':id' => $form_transaction_id
                ]);
                
                $success = "Transaction updated successfully!<br>" .
                          "Date: " . date('d-m-Y', strtotime($transaction_date)) . "<br>" .
                          "Design Types: " . ($design_types_str ? $design_types_str : 'None selected') . "<br>" .
                          "Base Amount: Rs. " . number_format($base_amount, 2) . "<br>" .
                          "Additional Amount: Rs. " . number_format($additional_amount, 2) . "<br>" .
                          "Advance Amount: Rs. " . number_format($advance_amount, 2) . "<br>" .
                          "Remaining Balance: Rs. " . number_format($calculated_amount - $advance_amount, 2);
                
            } else {
                // INSERT new transaction
                $stmt = $pdo->prepare("INSERT INTO staff_customer_transactions 
                                      (staff_id, customer_codes, unit_amount, quantity, 
                                       additional_amount, advance_amount, calculated_amount, 
                                       base_amount, design_type, transaction_date) 
                                       VALUES (:staff_id, :customer_codes, :unit_amount, :quantity, 
                                               :additional_amount, :advance_amount, :calculated_amount, 
                                               :base_amount, :design_type, :transaction_date)");
                
                $stmt->execute([
                    ':staff_id' => $staff_id,
                    ':customer_codes' => $customer_codes_str,
                    ':unit_amount' => $unit_amount,
                    ':quantity' => $quantity,
                    ':additional_amount' => $additional_amount,
                    ':advance_amount' => $advance_amount,
                    ':calculated_amount' => $calculated_amount,
                    ':base_amount' => $base_amount,
                    ':design_type' => $design_types_str,
                    ':transaction_date' => $transaction_date
                ]);
                
                $last_id = $pdo->lastInsertId();
                
                $success = "Transaction saved successfully!<br>" .
                          "Transaction ID: " . $last_id . "<br>" .
                          "Date: " . date('d-m-Y', strtotime($transaction_date)) . "<br>" .
                          "Design Types: " . ($design_types_str ? $design_types_str : 'None selected') . "<br>" .
                          "Base Amount: Rs. " . number_format($base_amount, 2) . "<br>" .
                          "Additional Amount: Rs. " . number_format($additional_amount, 2) . "<br>" .
                          "Advance Amount: Rs. " . number_format($advance_amount, 2) . "<br>" .
                          "Remaining Balance: Rs. " . number_format($calculated_amount - $advance_amount, 2);
                
                // Clear form data after successful insert
                if (!$edit_mode) {
                    $_POST = [];
                    $staff_id = $customer_codes = $unit_amount = $quantity = $additional_amount = $advance_amount = $calculated_amount = $design_types = '';
                    $transaction_date = date('Y-m-d');
                }
            }
        } catch (PDOException $e) {
            $error = "Error saving transaction: " . $e->getMessage();
        }
    }
}

// Fetch customers from database
$customers = [];
try {
    $tableExists = $pdo->query("SHOW TABLES LIKE 'customer'")->rowCount() > 0;
    
    if ($tableExists) {
        $query = "SELECT id as customer_code, fullname as customer_name, address 
                  FROM customer 
                  ORDER BY fullname";
        
        $stmt = $pdo->query($query);
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    if (empty($error)) {
        $error = "Error fetching customers: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $edit_mode ? 'Edit' : 'Add'; ?> Transaction</title>
    <link href="css/style.default.css" rel="stylesheet">
    <!-- Select2 CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container--default .select2-selection--multiple {
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .panel {
            margin-top: 20px;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        .alert-success {
            color: #3c763d;
            background-color: #dff0d8;
            border-color: #d6e9c6;
        }
        .alert-danger {
            color: #a94442;
            background-color: #f2dede;
            border-color: #ebccd1;
        }
        .btn-loading {
            position: relative;
            color: transparent !important;
        }
        .btn-loading:after {
            content: '';
            position: absolute;
            left: 50%;
            top: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 2px solid #fff;
            border-top: 2px solid transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .calculated-field {
            background-color: #f0f8ff;
            border: 2px dashed #4CAF50;
        }
        .additional-field {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
        }
        .advance-field {
            background-color: #e8f5e9;
            border: 1px solid #a5d6a7;
        }
        .calculation-box {
            background-color: #e8f5e9;
            border: 1px solid #c8e6c9;
            padding: 15px;
            border-radius: 5px;
            margin-top: 10px;
        }
        .calculation-step {
            margin-bottom: 5px;
            font-size: 14px;
        }
        .final-total {
            font-size: 18px;
            font-weight: bold;
            color: #2e7d32;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 2px solid #4caf50;
        }
        .amount-details {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        .design-field {
            background-color: #f3e5f5;
            border: 1px solid #ce93d8;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: #9c27b0;
            color: white;
            border: none;
        }
        .remaining-balance {
            font-size: 14px;
            font-weight: bold;
            color: #e67e22;
            margin-top: 5px;
        }
        .balance-positive {
            color: #27ae60;
        }
        .balance-negative {
            color: #e74c3c;
        }
        .optional-label {
            font-weight: normal;
            color: #999;
            font-size: 12px;
        }
        .date-field {
            background-color: #e3f2fd;
            border: 1px solid #90caf9;
        }
    </style>
</head>
<body>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header"><?php echo $edit_mode ? 'Edit Transaction' : 'Add New Transaction'; ?></h1>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <strong>Success!</strong><br><?php echo $success; ?>
                    <div style="margin-top: 10px;">
                        <?php if ($edit_mode): ?>
                            <a href="transaction_form.php" class="btn btn-success btn-sm">
                                <i class="fa fa-plus"></i> Add New Transaction
                            </a>
                        <?php else: ?>
                            <a href="transaction_form.php" class="btn btn-success btn-sm">
                                <i class="fa fa-plus"></i> Add Another
                            </a>
                        <?php endif; ?>
                        <a href="transaction_view.php" class="btn btn-info btn-sm">
                            <i class="fa fa-list"></i> View All
                        </a>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <strong>Error!</strong> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <div class="panel panel-primary">
                <div class="panel-heading" style="background-color: black;">
                    <h3 style="color:#ff9c14;" class="panel-title"><?php echo $edit_mode ? 'Edit Transaction Details' : 'Transaction Details'; ?></h3>
                </div>
                <div class="panel-body">
                    <form method="POST" action="" id="transactionForm">
                        <?php if ($edit_mode): ?>
                            <input type="hidden" name="transaction_id" value="<?php echo $transaction_id; ?>">
                        <?php endif; ?>
                        
                        <!-- Basic Information Row -->
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="staff_id">Select Staff *</label>
                                    <select class="form-control" id="staff_id" name="staff_id" required>
                                        <option value="">-- Select Staff --</option>
                                        <?php
                                        $staff_query = $pdo->query("SELECT id, fullname FROM staff ORDER BY fullname");
                                        while ($staff = $staff_query->fetch(PDO::FETCH_ASSOC)) {
                                            $selected = '';
                                            if ($edit_mode && isset($transaction_data['staff_id']) && $transaction_data['staff_id'] == $staff['id']) {
                                                $selected = 'selected';
                                            } elseif (isset($_POST['staff_id']) && $_POST['staff_id'] == $staff['id']) {
                                                $selected = 'selected';
                                            }
                                            echo "<option value='{$staff['id']}' $selected>{$staff['fullname']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="transaction_date">Transaction Date *</label>
                                    <input type="date" class="form-control date-field" id="transaction_date" 
                                           name="transaction_date" required
                                           value="<?php 
                                               if ($edit_mode && isset($transaction_data['transaction_date'])) {
                                                   echo $transaction_data['transaction_date'];
                                               } elseif (isset($_POST['transaction_date']) && !$success) {
                                                   echo htmlspecialchars($_POST['transaction_date']);
                                               } else {
                                                   echo date('Y-m-d');
                                               }
                                           ?>">
                                    <small class="text-muted">Select the transaction date</small>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="design_types">Design Type(s) <span class="optional-label">(Optional)</span></label>
                                    <select class="form-control design-field select2-design" id="design_types" 
                                            name="design_types[]" multiple="multiple">
                                        <?php
                                        $design_options = ['Motta Chamak', 'Full Double Chamak','Full Double', 'Paincha Jali', 'Design', 'Shirt', 'Full Single Chamak'];
                                        $selected_designs = [];
                                        
                                        if ($edit_mode && isset($transaction_data['design_type'])) {
                                            $selected_designs = explode(',', $transaction_data['design_type']);
                                        } elseif (isset($_POST['design_types']) && !$success) {
                                            $selected_designs = $_POST['design_types'];
                                        }
                                        
                                        foreach ($design_options as $design) {
                                            $selected = in_array($design, $selected_designs) ? 'selected' : '';
                                            echo "<option value='$design' $selected>$design</option>";
                                        }
                                        ?>
                                    </select>
                                    <small class="text-muted">Optional: Select one or more design types</small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Quantity Row -->
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="quantity">Quantity *</label>
                                    <select class="form-control" id="quantity" name="quantity" required>
                                        <option value="">-- Select Quantity --</option>
                                        <?php
                                        $current_quantity = '';
                                        if ($edit_mode && isset($transaction_data['quantity'])) {
                                            $current_quantity = $transaction_data['quantity'];
                                        } elseif (isset($_POST['quantity'])) {
                                            $current_quantity = $_POST['quantity'];
                                        }
                                        
                                        for ($i = 1; $i <= 8; $i++) {
                                            $selected = ($current_quantity == $i) ? 'selected' : '';
                                            echo "<option value='$i' $selected>$i</option>";
                                        }
                                        
                                        if ($current_quantity > 20) {
                                            echo "<option value='{$current_quantity}' selected>{$current_quantity} (Custom)</option>";
                                        }
                                        ?>
                                        <option value="other" <?php echo ($current_quantity == 'other') ? 'selected' : ''; ?>>More than 8...</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="unit_amount">Unit Amount *</label>
                                    <input type="number" class="form-control" id="unit_amount" name="unit_amount" 
                                           step="0.01" min="0" required placeholder="Enter unit amount"
                                           value="<?php 
                                               if ($edit_mode && isset($transaction_data['unit_amount'])) {
                                                   echo $transaction_data['unit_amount'];
                                               } elseif (isset($_POST['unit_amount']) && !$success) {
                                                   echo htmlspecialchars($_POST['unit_amount']);
                                               }
                                           ?>">
                                    <small class="text-muted">Amount for one unit/item</small>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="additional_amount">Additional Amount *</label>
                                    <input type="number" class="form-control additional-field" id="additional_amount" 
                                           name="additional_amount" step="0.01" min="0" required 
                                           placeholder="Enter additional amount"
                                           value="<?php 
                                               if ($edit_mode && isset($transaction_data['additional_amount'])) {
                                                   echo $transaction_data['additional_amount'];
                                               } elseif (isset($_POST['additional_amount']) && !$success) {
                                                   echo htmlspecialchars($_POST['additional_amount']);
                                               } else {
                                                   echo '0';
                                               }
                                           ?>">
                                    <small class="text-muted">Extra amount to add (required, enter 0 if none)</small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Amount Row -->
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="calculated_amount">Total Amount *</label>
                                    <input type="number" class="form-control calculated-field" id="calculated_amount" 
                                           name="calculated_amount" step="0.01" min="0" required 
                                           placeholder="Auto-calculated total amount" readonly
                                           value="<?php 
                                               if ($edit_mode && isset($transaction_data['calculated_amount'])) {
                                                   echo $transaction_data['calculated_amount'];
                                               } elseif (isset($_POST['calculated_amount']) && !$success) {
                                                   echo htmlspecialchars($_POST['calculated_amount']);
                                               } else {
                                                   $calc_qty = isset($_POST['quantity']) ? $_POST['quantity'] : (isset($transaction_data['quantity']) ? $transaction_data['quantity'] : '');
                                                   $calc_unit = isset($_POST['unit_amount']) ? $_POST['unit_amount'] : (isset($transaction_data['unit_amount']) ? $transaction_data['unit_amount'] : '');
                                                   $calc_add = isset($_POST['additional_amount']) ? $_POST['additional_amount'] : (isset($transaction_data['additional_amount']) ? $transaction_data['additional_amount'] : 0);
                                                   
                                                   if (is_numeric($calc_qty) && is_numeric($calc_unit) && $calc_qty > 0) {
                                                       $total = ($calc_qty * $calc_unit) + $calc_add;
                                                       echo number_format($total, 2);
                                                   }
                                               }
                                           ?>">
                                    <div class="amount-details">Total amount to be paid</div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="advance_amount">Advance Amount</label>
                                    <input type="number" class="form-control advance-field" id="advance_amount" 
                                           name="advance_amount" step="0.01" min="0" 
                                           placeholder="Enter advance amount paid"
                                           value="<?php 
                                               if ($edit_mode && isset($transaction_data['advance_amount'])) {
                                                   echo $transaction_data['advance_amount'];
                                               } elseif (isset($_POST['advance_amount']) && !$success) {
                                                   echo htmlspecialchars($_POST['advance_amount']);
                                               } else {
                                                   echo '0';
                                               }
                                           ?>">
                                    <small class="text-muted">Amount paid in advance (if any)</small>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Remaining Balance</label>
                                    <input type="text" class="form-control" id="remaining_balance" 
                                           readonly placeholder="Auto-calculated balance"
                                           style="background-color: #f8f9fa; font-weight: bold;">
                                    <div class="remaining-balance" id="balanceStatus"></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Calculation Breakdown -->
                        <div class="row">
                            <div class="col-md-8">
                                <div class="calculation-box" id="calculationBreakdown" style="display: none;">
                                    <h5>Calculation Breakdown:</h5>
                                    <div class="calculation-step">
                                        <strong>1. Quantity:</strong> <span id="displayQuantity">0</span> units
                                    </div>
                                    <div class="calculation-step">
                                        <strong>2. Unit Amount:</strong> Rs. <span id="displayUnitAmount">0.00</span>
                                    </div>
                                    <div class="calculation-step">
                                        <strong>3. Base Amount (Quantity × Unit):</strong> Rs. <span id="baseAmount">0.00</span>
                                    </div>
                                    <div class="calculation-step">
                                        <strong>4. Additional Amount:</strong> Rs. <span id="additionalDisplay">0.00</span>
                                    </div>
                                    <div class="calculation-step">
                                        <strong>5. Total Amount:</strong> Rs. <span id="totalDisplay">0.00</span>
                                    </div>
                                    <div class="calculation-step">
                                        <strong>6. Advance Amount Paid:</strong> Rs. <span id="advanceDisplay">0.00</span>
                                    </div>
                                    <div class="calculation-step final-total">
                                        <strong>7. Remaining Balance:</strong> Rs. <span id="balanceDisplay">0.00</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Customer Selection -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="customer_codes">Select Customer(s) *</label>
                                    <select class="form-control select2-multiple" id="customer_codes" 
                                            name="customer_codes[]" multiple="multiple" required>
                                        <option value="">-- Select Customers --</option>
                                        <?php
                                        if (!empty($customers)) {
                                            $selected_codes = [];
                                            if ($edit_mode && isset($transaction_data['customer_codes'])) {
                                                $selected_codes = explode(',', $transaction_data['customer_codes']);
                                            } elseif (isset($_POST['customer_codes']) && !$success) {
                                                $selected_codes = $_POST['customer_codes'];
                                            }
                                            
                                            foreach ($customers as $customer) {
                                                $address = !empty($customer['address']) ? htmlspecialchars($customer['address']) : 'No address available';
                                                $customer_code = htmlspecialchars($customer['customer_code']);
                                                $customer_name = htmlspecialchars($customer['customer_name']);
                                                
                                                $selected = in_array($address, $selected_codes) ? 'selected' : '';
                                                
                                                echo "<option value='{$address}' $selected 
                                                      data-address='" . htmlspecialchars($address, ENT_QUOTES) . "'
                                                      data-id='{$customer_code}'
                                                      data-name='{$customer_name}'>
                                                      {$address} - {$customer_name}
                                                      </option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                    <small class="text-muted">Type to search, select multiple customers</small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="row" style="padding-top:30px;">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-success" id="submitBtn">
                                    <i class="fa fa-<?php echo $edit_mode ? 'edit' : 'save'; ?>"></i> 
                                    <?php echo $edit_mode ? 'Update Transaction' : 'Save Transaction'; ?>
                                </button>
                                <button type="reset" class="btn btn-default" id="resetForm">
                                    <i class="fa fa-refresh"></i> Reset Form
                                </button>
                                <a href="transaction_view.php" class="btn btn-info">
                                    <i class="fa fa-list"></i> View All Transactions
                                </a>
                                <a href="staffview.php" class="btn btn-primary">
                                    <i class="fa fa-users"></i> Back to Staff
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- jQuery (already loaded in header.php) -->
<!-- Select2 JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize Select2 for customer dropdown
    $('#customer_codes').select2({
        placeholder: "Search and select customers...",
        allowClear: true,
        width: '100%'
    });
    
    // Initialize Select2 for design types dropdown
    $('#design_types').select2({
        placeholder: "Select design type(s)... (Optional)",
        allowClear: true,
        width: '100%',
        closeOnSelect: false
    });
    
    // Handle custom quantity input
    $('#quantity').on('change', function() {
        if ($(this).val() === 'other') {
            var customQty = prompt('Please enter quantity:');
            if (customQty !== null && customQty !== '' && !isNaN(customQty) && parseInt(customQty) > 0) {
                $(this).append('<option value="' + customQty + '" selected>' + customQty + ' (Custom)</option>');
                $(this).val(customQty);
                updateCalculation();
            } else {
                $(this).val('');
                updateCalculation();
            }
        } else {
            updateCalculation();
        }
    });
    
    // Pre-select custom quantity if it exists
    var currentQty = $('#quantity').val();
    if (currentQty > 20 && currentQty !== 'other') {
        $('#quantity').append('<option value="' + currentQty + '" selected>' + currentQty + ' (Custom)</option>');
        $('#quantity').val(currentQty);
    }
    
    // Update calculation when any field changes
    $('#quantity, #unit_amount, #additional_amount, #advance_amount').on('input change', function() {
        updateCalculation();
    });
    
    // Update calculation function
    function updateCalculation() {
        var quantity = parseFloat($('#quantity').val()) || 0;
        var unitAmount = parseFloat($('#unit_amount').val()) || 0;
        var additionalAmount = parseFloat($('#additional_amount').val()) || 0;
        var advanceAmount = parseFloat($('#advance_amount').val()) || 0;
        
        // Update display values
        $('#displayQuantity').text(quantity);
        $('#displayUnitAmount').text(unitAmount.toFixed(2));
        $('#advanceDisplay').text(advanceAmount.toFixed(2));
        
        if (quantity > 0 && unitAmount >= 0) {
            var baseAmount = quantity * unitAmount;
            var total = baseAmount + additionalAmount;
            var remaining = total - advanceAmount;
            
            // Update calculated amount field (total)
            $('#calculated_amount').val(total.toFixed(2));
            
            // Update remaining balance display
            $('#remaining_balance').val(remaining.toFixed(2));
            
            // Update calculation breakdown
            $('#baseAmount').text(baseAmount.toFixed(2));
            $('#additionalDisplay').text(additionalAmount.toFixed(2));
            $('#totalDisplay').text(total.toFixed(2));
            $('#balanceDisplay').text(remaining.toFixed(2));
            
            // Update balance status with color coding
            var balanceStatus = $('#balanceStatus');
            if (remaining > 0) {
                balanceStatus.html('<span class="balance-positive">✓ Pending Balance: Rs. ' + remaining.toFixed(2) + ' to be paid</span>');
            } else if (remaining < 0) {
                balanceStatus.html('<span class="balance-negative">⚠️ Overpayment: Rs. ' + Math.abs(remaining).toFixed(2) + ' excess payment</span>');
            } else {
                balanceStatus.html('<span class="balance-positive">✓ Fully Paid - No balance remaining</span>');
            }
            
            // Show calculation breakdown
            $('#calculationBreakdown').show();
        } else {
            $('#calculated_amount').val('');
            $('#remaining_balance').val('');
            $('#calculationBreakdown').hide();
        }
    }
    
    // Initial calculation if values exist
    if ($('#quantity').val() && $('#unit_amount').val()) {
        updateCalculation();
    }
    
    // Form validation
    $('#transactionForm').on('submit', function(e) {
        var staff_id = $('#staff_id').val();
        var customer_codes = $('#customer_codes').val();
        var unit_amount = $('#unit_amount').val();
        var quantity = $('#quantity').val();
        var additional_amount = $('#additional_amount').val();
        var calculated_amount = $('#calculated_amount').val();
        var advance_amount = $('#advance_amount').val();
        var transaction_date = $('#transaction_date').val();
        
        // Validation
        if (!staff_id) {
            alert('Please select a staff member.');
            e.preventDefault();
            return false;
        }
        
        if (!transaction_date) {
            alert('Please select a transaction date.');
            e.preventDefault();
            return false;
        }
        
        if (!quantity || quantity <= 0) {
            alert('Please select a valid quantity.');
            e.preventDefault();
            return false;
        }
        
        if (!customer_codes || customer_codes.length === 0) {
            alert('Please select at least one customer.');
            e.preventDefault();
            return false;
        }
        
        if (!unit_amount || unit_amount < 0) {
            alert('Please enter a valid unit amount.');
            e.preventDefault();
            return false;
        }
        
        if (additional_amount === '' || additional_amount < 0) {
            alert('Please enter a valid additional amount (enter 0 if none).');
            e.preventDefault();
            return false;
        }
        
        if (advance_amount === '' || advance_amount < 0) {
            alert('Please enter a valid advance amount (enter 0 if none).');
            e.preventDefault();
            return false;
        }
        
        if (!calculated_amount || calculated_amount <= 0) {
            alert('Please make sure calculated amount is generated properly.');
            e.preventDefault();
            return false;
        }
        
        // Show confirmation
        var totalAmount = parseFloat(calculated_amount);
        var advance = parseFloat(advance_amount);
        var remaining = totalAmount - advance;
        var designTypes = $('#design_types').val() || [];
        var designTypesText = designTypes.length > 0 ? designTypes.join(', ') : 'None selected';
        
        var confirmMessage = "Transaction Details to be saved:\n\n" +
                            "Date: " + transaction_date + "\n" +
                            "Design Types: " + designTypesText + "\n" +
                            "Quantity: " + quantity + "\n" +
                            "Unit Amount: Rs. " + parseFloat(unit_amount).toFixed(2) + "\n" +
                            "Additional Amount: Rs. " + parseFloat(additional_amount).toFixed(2) + "\n" +
                            "Total Amount: Rs. " + totalAmount.toFixed(2) + "\n" +
                            "Advance Amount: Rs. " + advance.toFixed(2) + "\n" +
                            "Remaining Balance: Rs. " + remaining.toFixed(2) + "\n\n" +
                            "Click OK to save this transaction.";
        
        if (!confirm(confirmMessage)) {
            e.preventDefault();
            return false;
        }
        
        // Loading indicator
        var $btn = $('#submitBtn');
        $btn.addClass('btn-loading').prop('disabled', true);
        
        return true;
    });
    
    // Handle form reset
    $('#resetForm').on('click', function(e) {
        e.preventDefault();
        if (confirm('Are you sure you want to reset all fields?')) {
            $('#transactionForm')[0].reset();
            $('#customer_codes').val(null).trigger('change');
            $('#design_types').val(null).trigger('change');
            $('#calculated_amount').val('');
            $('#remaining_balance').val('');
            $('#calculationBreakdown').hide();
            $('#submitBtn').removeClass('btn-loading').prop('disabled', false);
            // Set default date
            $('#transaction_date').val('<?php echo date('Y-m-d'); ?>');
            updateCalculation();
        }
    });
});
</script>

<?php include('footer.php'); ?>
</body>
</html>