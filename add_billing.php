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

// Check if editing an existing bill
$edit_id = isset($_GET['edit_id']) ? intval($_GET['edit_id']) : 0;
$is_edit = ($edit_id > 0);
$bill_data = null;
$bill_items = [];

if ($is_edit) {
    // Fetch bill header data
    $stmt = $pdo->prepare("SELECT * FROM billing_header WHERE id = ?");
    $stmt->execute([$edit_id]);
    $bill_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$bill_data) {
        redirect('view_billing.php');
    }
    
    // Fetch bill items
    $stmt = $pdo->prepare("SELECT * FROM billing_items WHERE bill_id = ?");
    $stmt->execute([$edit_id]);
    $bill_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function generateBillNo() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT bill_no FROM billing_header ORDER BY id DESC LIMIT 1");
    $last_bill = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($last_bill) {
        $last_num = intval($last_bill['bill_no']);
        $new_num = $last_num + 1;
    } else {
        $new_num = 1;
    }
    
    return str_pad($new_num, 6, '0', STR_PAD_LEFT);
}

$bill_no = $is_edit ? $bill_data['bill_no'] : generateBillNo();

include ('header.php');
?>

<link href="css/select2.min.css" rel="stylesheet">
<style>
    .select2-container--default .select2-selection--single {
        height: 34px;
        border: 1px solid #ccc;
        border-radius: 4px;
    }
    .form-group {
        margin-bottom: 15px;
    }
    .items-table {
        margin-bottom: 20px;
    }
    .items-table th {
        background-color: #f5f5f5;
        vertical-align: middle;
    }
    .items-table td {
        vertical-align: middle;
        padding: 8px;
    }
    .items-table .form-control {
        height: 32px;
        padding: 4px 8px;
        font-size: 13px;
        width: 100%;
        min-width: 80px;
    }
    .brand-input-group {
        display: flex;
        gap: 5px;
        align-items: center;
        flex-wrap: nowrap;
    }
    .brand-select {
        flex: 2;
        min-width: 150px;
    }
    .brand-manual {
        flex: 1;
        min-width: 120px;
    }
    .brand-toggle-btn {
        white-space: nowrap;
        padding: 4px 8px;
        font-size: 12px;
    }
    .advance-info {
        background-color: #e8f5e9;
        border-left: 4px solid #4caf50;
        padding: 10px;
        margin-top: 10px;
        border-radius: 4px;
    }
    .item-total {
        font-weight: bold;
        color: #27ae60;
    }
    @media (max-width: 768px) {
        .brand-input-group {
            flex-wrap: wrap;
        }
        .brand-select, .brand-manual {
            min-width: 100%;
        }
        .items-table .form-control {
            width: 100%;
        }
    }
</style>

<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">
                <?php echo $is_edit ? 'Edit Bill' : 'Create New Bill'; ?> 
                <small class="text-muted">Bill No: <?php echo $bill_no; ?></small>
                <?php if($is_edit): ?>
                    <a href="add_billing.php" class="btn btn-primary btn-sm pull-right">
                        <i class="fa fa-plus"></i> Create New Bill
                    </a>
                <?php endif; ?>
            </h1>
        </div>
    </div>
    
    <div class="contentpanel">
        <div class="panel panel-default">
            <div class="panel-body">
                
                <?php
                if($_POST) {
                    $errors = [];
                    
                    // Get form data
                    $customer_id = intval($_POST['customer_id'] ?? 0);
                    $customer_name = trim($_POST['customer_name'] ?? '');
                    $customer_phone = trim($_POST['customer_phone'] ?? '');
                    $customer_address = trim($_POST['customer_address'] ?? '');
                    $payment_method = trim($_POST['payment_method'] ?? 'Cash');
                    $discount = floatval($_POST['discount'] ?? 0);
                    $advance = floatval($_POST['advance'] ?? 0);
                    $notes = trim($_POST['notes'] ?? '');
                    $date_collected = $_POST['date_collected'] ?? null;
                    $order_status = $_POST['order_status'] ?? 'pending';
                    
                    // Get items data
                    $items = [];
                    if(isset($_POST['item_brand']) && is_array($_POST['item_brand'])) {
                        foreach($_POST['item_brand'] as $index => $brand) {
                            $manual_brand = trim($_POST['item_brand_manual'][$index] ?? '');
                            $final_brand = !empty($manual_brand) ? $manual_brand : $brand;
                            
                            $price = floatval($_POST['item_price'][$index] ?? 0);
                            $quantity = intval($_POST['item_quantity'][$index] ?? 0);
                            $suit_quantity = intval($_POST['item_suit_quantity'][$index] ?? 0);
                            
                            if(!empty($final_brand) || $price > 0 || $quantity > 0 || $suit_quantity > 0) {
                                if(empty($final_brand)) {
                                    $final_brand = 'Other Item';
                                }
                                $items[] = [
                                    'brand' => $final_brand,
                                    'price' => $price,
                                    'quantity' => $quantity > 0 ? $quantity : 1,
                                    'suit_quantity' => $suit_quantity,
                                    'stitching_amount' => floatval($_POST['item_stitching_amount'][$index] ?? 0),
                                    'stitching_quantity' => intval($_POST['item_stitching_quantity'][$index] ?? 0)
                                ];
                            }
                        }
                    }
                    
                    // Get customer details
                    $customer = [];
                    if($customer_id > 0) {
                        $stmt = $pdo->prepare("SELECT fullname, phonenumber, address FROM customer WHERE id = ?");
                        $stmt->execute([$customer_id]);
                        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if($customer) {
                            $customer['fullname'] = !empty(trim($customer['fullname'])) ? $customer['fullname'] : 'N/A';
                            $customer['phonenumber'] = !empty(trim($customer['phonenumber'])) ? $customer['phonenumber'] : 'N/A';
                            $customer['address'] = !empty(trim($customer['address'])) ? $customer['address'] : 'N/A';
                        } else {
                            $customer = [
                                'fullname' => 'N/A',
                                'phonenumber' => 'N/A',
                                'address' => 'N/A'
                            ];
                        }
                    } else {
                        $customer = [
                            'fullname' => !empty($customer_name) ? $customer_name : 'Walk-in Customer',
                            'phonenumber' => !empty($customer_phone) ? $customer_phone : 'N/A',
                            'address' => !empty($customer_address) ? $customer_address : 'N/A'
                        ];
                    }
                    
                    // Validate items
                    foreach($items as $index => $item) {
                        if($item['price'] < 0) $errors[] = "Price cannot be negative for item " . ($index + 1);
                        if($item['quantity'] < 0) $errors[] = "Quantity cannot be negative for item " . ($index + 1);
                        if($item['suit_quantity'] < 0) $errors[] = "Suit quantity cannot be negative for item " . ($index + 1);
                        if($item['stitching_amount'] < 0) $errors[] = "Stitching amount cannot be negative for item " . ($index + 1);
                        if($item['stitching_quantity'] < 0) $errors[] = "Stitching quantity cannot be negative for item " . ($index + 1);
                    }
                    
                    if($discount < 0) $errors[] = "Discount cannot be negative";
                    if($advance < 0) $errors[] = "Advance payment cannot be negative";
                    
                    // Calculate totals
                    $subtotal = 0;
                    $stitching_total = 0;
                    
                    foreach($items as $item) {
                        $subtotal += $item['price'] * $item['quantity'];
                        $stitching_total += $item['stitching_amount'] * $item['stitching_quantity'];
                    }
                    
                    $total_before_discount = $subtotal + $stitching_total;
                    $total_amount = $total_before_discount - $discount;
                    $remaining_amount = $total_amount - $advance;
                    
                    if($total_amount < 0) $errors[] = "Discount cannot be greater than total amount";
                    if($advance > $total_amount) $errors[] = "Advance payment cannot be greater than total amount";
                    
                    if(empty($errors)) {
                        try {
                            $pdo->beginTransaction();
                            
                            if ($is_edit) {
                                // UPDATE existing bill
                                $stmt = $pdo->prepare("UPDATE billing_header SET 
                                    customer_id = ?, customer_name = ?, customer_phone = ?, customer_address = ?,
                                    subtotal = ?, stitching_total = ?, discount = ?, advance_payment = ?, 
                                    total_amount = ?, remaining_amount = ?, payment_method = ?, notes = ?, 
                                    date_collected = ?, order_status = ?, user_id = ?
                                    WHERE id = ?");
                                
                                $stmt->execute([
                                    $customer_id > 0 ? $customer_id : NULL,
                                    $customer['fullname'],
                                    $customer['phonenumber'],
                                    $customer['address'],
                                    $subtotal,
                                    $stitching_total,
                                    $discount,
                                    $advance,
                                    $total_amount,
                                    $remaining_amount,
                                    $payment_method,
                                    $notes,
                                    !empty($date_collected) ? $date_collected : null,
                                    $order_status,
                                    $uid,
                                    $edit_id
                                ]);
                                
                                // Delete old items
                                $stmt = $pdo->prepare("DELETE FROM billing_items WHERE bill_id = ?");
                                $stmt->execute([$edit_id]);
                                
                                $bill_id = $edit_id;
                            } else {
                                // INSERT new bill
                                $stmt = $pdo->prepare("INSERT INTO billing_header (
                                    bill_no, customer_id, customer_name, customer_phone, customer_address,
                                    subtotal, stitching_total, discount, advance_payment, total_amount, remaining_amount,
                                    payment_method, notes, date_collected, order_status, user_id
                                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                                
                                $stmt->execute([
                                    $bill_no,
                                    $customer_id > 0 ? $customer_id : NULL,
                                    $customer['fullname'],
                                    $customer['phonenumber'],
                                    $customer['address'],
                                    $subtotal,
                                    $stitching_total,
                                    $discount,
                                    $advance,
                                    $total_amount,
                                    $remaining_amount,
                                    $payment_method,
                                    $notes,
                                    !empty($date_collected) ? $date_collected : null,
                                    $order_status,
                                    $uid
                                ]);
                                
                                $bill_id = $pdo->lastInsertId();
                            }
                            
                            // Insert new items with suit_quantity
                            if(!empty($items)) {
                                $stmt = $pdo->prepare("INSERT INTO billing_items (
                                    bill_id, brand_name, price, quantity, suit_quantity,
                                    stitching_amount, stitching_quantity,
                                    item_total, item_stitching_total
                                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                                
                                foreach($items as $item) {
                                    $item_total = $item['price'] * $item['quantity'];
                                    $item_stitching_total = $item['stitching_amount'] * $item['stitching_quantity'];
                                    
                                    $stmt->execute([
                                        $bill_id,
                                        $item['brand'],
                                        $item['price'],
                                        $item['quantity'],
                                        $item['suit_quantity'],
                                        $item['stitching_amount'],
                                        $item['stitching_quantity'],
                                        $item_total,
                                        $item_stitching_total
                                    ]);
                                }
                            }
                            
                            $pdo->commit();
                            
                            $message = $is_edit ? 'Bill updated successfully!' : 'Bill created successfully!';
                            echo '<div class="alert alert-success alert-dismissable">
                                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                                <strong>Success!</strong> ' . $message . ' 
                                <a href="print_bill.php?id='.$bill_id.'" target="_blank" class="btn btn-xs btn-info">
                                    <i class="fa fa-print"></i> Print Bill
                                </a>
                                <a href="add_billing.php" class="btn btn-xs btn-primary">
                                    <i class="fa fa-plus"></i> Create Another Bill
                                </a>
                                <a href="view_billing.php" class="btn btn-xs btn-default">
                                    <i class="fa fa-list"></i> View All Bills
                                </a>
                            </div>';
                            
                            if(!$is_edit) {
                                $_POST = [];
                                $bill_no = generateBillNo();
                            }
                            
                        } catch(PDOException $e) {
                            $pdo->rollBack();
                            $errors[] = "Database error: " . $e->getMessage();
                        }
                    }
                    
                    // Show errors
                    if(!empty($errors)) {
                        echo '<div class="alert alert-danger alert-dismissable">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            <ul style="margin-bottom: 0;">';
                        foreach($errors as $error) {
                            echo '<li>' . htmlspecialchars($error) . '</li>';
                        }
                        echo '</ul></div>';
                    }
                }
                ?>
                
                <form action="" method="post" id="billingForm">
                    <input type="hidden" name="bill_no" value="<?php echo $bill_no; ?>">
                    <?php if($is_edit): ?>
                        <input type="hidden" name="edit_id" value="<?php echo $edit_id; ?>">
                    <?php endif; ?>
                    
                    <div class="row">
                        <!-- Customer Information -->
                        <div class="col-md-6">
                            <div class="panel panel-primary">
                                <div class="panel-heading">
                                    <h3 class="panel-title">Customer Information</h3>
                                </div>
                                <div class="panel-body">
                                    <div class="form-group">
                                        <label>Select Customer</label>
                                        <select name="customer_id" id="customerSelect" class="form-control">
                                            <option value="0">-- Select Customer (Walk-in) --</option>
                                            <?php
                                            $stmt = $pdo->query("SELECT id, fullname, phonenumber, address FROM customer ORDER BY fullname");
                                            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                                $selected = false;
                                                if ($is_edit && $bill_data['customer_id'] == $row['id']) {
                                                    $selected = true;
                                                } elseif (isset($_POST['customer_id']) && $_POST['customer_id'] == $row['id']) {
                                                    $selected = true;
                                                }
                                                $selected_attr = $selected ? 'selected' : '';
                                                
                                                // Create display text with name and address
                                                $display_text = htmlspecialchars($row['fullname']);
                                                if (!empty($row['address'])) {
                                                    $display_text .= ' - ' . htmlspecialchars($row['address']);
                                                }
                                                
                                                echo '<option value="'.$row['id'].'" 
                                                        data-phone="'.htmlspecialchars($row['phonenumber'] ?? 'N/A').'" 
                                                        data-address="'.htmlspecialchars($row['address'] ?? 'N/A').'"
                                                        data-name="'.htmlspecialchars($row['fullname'] ?? 'N/A').'"
                                                        '.$selected_attr.'>
                                                        '.$display_text.'
                                                    </option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Customer Name</label>
                                        <input type="text" name="customer_name" id="customerName" class="form-control" 
                                               value="<?php 
                                                   if($is_edit && !isset($_POST['customer_name'])) {
                                                       echo htmlspecialchars($bill_data['customer_name'] ?? '');
                                                   } else {
                                                       echo isset($_POST['customer_name']) ? htmlspecialchars($_POST['customer_name']) : '';
                                                   }
                                               ?>"
                                               placeholder="Enter customer name">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Phone</label>
                                        <input type="text" name="customer_phone" id="customerPhone" class="form-control" 
                                               value="<?php 
                                                   if($is_edit && !isset($_POST['customer_phone'])) {
                                                       echo htmlspecialchars($bill_data['customer_phone'] ?? '');
                                                   } else {
                                                       echo isset($_POST['customer_phone']) ? htmlspecialchars($_POST['customer_phone']) : '';
                                                   }
                                               ?>"
                                               placeholder="Enter phone number">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Size No</label>
                                        <textarea name="customer_address" id="customerAddress" class="form-control" rows="2" 
                                                  placeholder="Enter address"><?php 
                                                      if($is_edit && !isset($_POST['customer_address'])) {
                                                          echo htmlspecialchars($bill_data['customer_address'] ?? '');
                                                      } else {
                                                          echo isset($_POST['customer_address']) ? htmlspecialchars($_POST['customer_address']) : '';
                                                      }
                                                  ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Payment Information -->
                        <div class="col-md-6">
                            <div class="panel panel-info">
                                <div class="panel-heading">
                                    <h3 class="panel-title">Payment Information</h3>
                                </div>
                                <div class="panel-body">
                                    <div class="form-group">
                                        <label>Discount (Rs)</label>
                                        <input type="number" name="discount" id="discount" class="form-control" 
                                               step="0.01" min="0" value="<?php 
                                                   if($is_edit && !isset($_POST['discount'])) {
                                                       echo $bill_data['discount'] ?? 0;
                                                   } else {
                                                       echo isset($_POST['discount']) ? $_POST['discount'] : '0';
                                                   }
                                               ?>">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Adv/Rec Payment (Rs)</label>
                                        <input type="number" name="advance" id="advance" class="form-control" 
                                               step="0.01" min="0" value="<?php 
                                                   if($is_edit && !isset($_POST['advance'])) {
                                                       echo $bill_data['advance_payment'] ?? 0;
                                                   } else {
                                                       echo isset($_POST['advance']) ? $_POST['advance'] : '0';
                                                   }
                                               ?>">
                                        <small class="text-muted">Amount paid in advance</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Payment Method</label>
                                        <select name="payment_method" class="form-control">
                                            <option value="Cash" <?php 
                                                $pm = '';
                                                if($is_edit && !isset($_POST['payment_method'])) {
                                                    $pm = $bill_data['payment_method'] ?? '';
                                                } else {
                                                    $pm = $_POST['payment_method'] ?? '';
                                                }
                                                echo ($pm == 'Cash') ? 'selected' : '';
                                            ?>>Cash</option>
                                            <option value="Card" <?php echo ($pm == 'Card') ? 'selected' : ''; ?>>EasyPaisa</option>
                                            <option value="UPI" <?php echo ($pm == 'UPI') ? 'selected' : ''; ?>>Bank</option>
                                            <option value="Credit" <?php echo ($pm == 'Credit') ? 'selected' : ''; ?>>POS</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Notes</label>
                                        <textarea name="notes" class="form-control" rows="2" placeholder="Any additional notes"><?php 
                                            if($is_edit && !isset($_POST['notes'])) {
                                                echo htmlspecialchars($bill_data['notes'] ?? '');
                                            } else {
                                                echo isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : '';
                                            }
                                        ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Order Information Section -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="panel panel-warning">
                                <div class="panel-heading">
                                    <h3 class="panel-title">Order Collection Status</h3>
                                </div>
                                <div class="panel-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Date to Collect</label>
                                                <input type="date" name="date_collected" id="date_collected" class="form-control" 
                                                       value="<?php 
                                                           if($is_edit && !isset($_POST['date_collected'])) {
                                                               echo $bill_data['date_collected'] ?? '';
                                                           } else {
                                                               echo isset($_POST['date_collected']) ? $_POST['date_collected'] : '';
                                                           }
                                                       ?>">
                                                <small class="text-muted">When should the customer collect the order?</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Order Status</label>
                                                <select name="order_status" class="form-control">
                                                    <option value="pending" <?php 
                                                        if($is_edit && !isset($_POST['order_status'])) {
                                                            echo ($bill_data['order_status'] == 'pending') ? 'selected' : '';
                                                        } elseif(isset($_POST['order_status']) && $_POST['order_status'] == 'pending') {
                                                            echo 'selected';
                                                        } elseif(!$is_edit && !isset($_POST['order_status'])) {
                                                            echo 'selected';
                                                        }
                                                    ?>>Pending - Not Collected</option>
                                                    <option value="collected" <?php 
                                                        if($is_edit && !isset($_POST['order_status'])) {
                                                            echo ($bill_data['order_status'] == 'collected') ? 'selected' : '';
                                                        } elseif(isset($_POST['order_status']) && $_POST['order_status'] == 'collected') {
                                                            echo 'selected';
                                                        }
                                                    ?>>Collected - Customer Received</option>
                                                </select>
                                                <small class="text-muted">Current status of the order</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Items Section -->
                    <div class="panel panel-default">
                        <div class="panel-heading clearfix">
                            <h3 class="panel-title pull-left">Bill Items</h3>
                            <button type="button" id="addItemBtn" class="btn btn-success btn-xs pull-right">
                                <i class="fa fa-plus"></i> Add Item
                            </button>
                        </div>
                        <div class="panel-body">
                            <div class="empty-bill-message" id="emptyBillMessage" style="display: none;">
                                <i class="fa fa-info-circle"></i> No items added.
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered items-table" id="itemsTable">
                                    <thead>
                                        <tr>
                                            <th width="4%">#</th>
                                            <th width="25%">Brand/Item</th>
                                            <th width="10%">Price (Rs)</th>
                                            <th width="7%">Qty</th>
                                            <th width="7%">Suit Qty</th>
                                            <th width="10%">Stitching</th>
                                            <th width="7%">Stitch Qty</th>
                                            <th width="10%">Item Total</th>
                                            <th width="5%">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="itemsBody">
                                        <?php if($is_edit && !empty($bill_items)): ?>
                                            <?php foreach($bill_items as $index => $item): ?>
                                                <?php 
                                                $item_total = $item['price'] * $item['quantity'];
                                                $item_stitching_total = $item['stitching_amount'] * $item['stitching_quantity'];
                                                $total_item_amount = $item_total + $item_stitching_total;
                                                ?>
                                                <tr class="item-row" id="itemRow_<?php echo $index; ?>">
                                                    <td class="row-number text-center"><?php echo $index + 1; ?></td>
                                                    <td class="brand-cell">
                                                        <div class="brand-input-group">
                                                            <select name="item_brand[]" class="form-control brand-select">
                                                                <option value="">-- Select Item --</option>
                                                                <?php
                                                                $stmt = $pdo->query("SELECT DISTINCT brand_name, price FROM inventory ORDER BY brand_name");
                                                                while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                                                    $selected = ($row['brand_name'] == $item['brand_name']) ? 'selected' : '';
                                                                    echo '<option value="'.htmlspecialchars($row['brand_name']).'" data-price="'.$row['price'].'" '.$selected.'>'.htmlspecialchars($row['brand_name']).'</option>';
                                                                }
                                                                ?>
                                                            </select>
                                                            <input type="text" name="item_brand_manual[]" class="form-control brand-manual" placeholder="New brand" value="<?php echo htmlspecialchars($item['brand_name']); ?>" style="display: none;">
                                                            <button type="button" class="btn btn-default btn-xs brand-toggle-btn" title="Switch to manual entry">
                                                                <i class="fa fa-keyboard-o"></i>
                                                            </button>
                                                        </div>
                                                    </span>
                                                    <td>
                                                        <input type="number" name="item_price[]" class="form-control item-price" 
                                                               step="0.01" min="0" value="<?php echo $item['price']; ?>">
                                                    </span>
                                                    <td>
                                                        <input type="number" name="item_quantity[]" class="form-control item-quantity" 
                                                               min="0" value="<?php echo $item['quantity']; ?>">
                                                    </span>
                                                    <td class="suit-quantity-cell">
                                                        <input type="number" name="item_suit_quantity[]" class="form-control item-suit-quantity" 
                                                               min="0" value="<?php echo $item['suit_quantity'] ?? 0; ?>">
                                                    </span>
                                                    <td>
                                                        <input type="number" name="item_stitching_amount[]" class="form-control item-stitching-amount" 
                                                               step="0.01" min="0" value="<?php echo $item['stitching_amount']; ?>">
                                                    </span>
                                                    <td>
                                                        <input type="number" name="item_stitching_quantity[]" class="form-control item-stitching-quantity" 
                                                               min="0" value="<?php echo $item['stitching_quantity']; ?>">
                                                    </span>
                                                    <td>
                                                        <span class="item-total">Rs <?php echo number_format($total_item_amount, 2); ?></span>
                                                    </span>
                                                    <td class="text-center">
                                                        <button type="button" class="btn btn-danger btn-xs remove-item">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </span>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr class="item-row" id="itemRow_0">
                                                <td class="row-number text-center">1</span>
                                                <td class="brand-cell">
                                                    <div class="brand-input-group">
                                                        <select name="item_brand[]" class="form-control brand-select">
                                                            <option value="">-- Select Item --</option>
                                                            <?php
                                                            $stmt = $pdo->query("SELECT DISTINCT brand_name, price FROM inventory ORDER BY brand_name");
                                                            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                                                echo '<option value="'.htmlspecialchars($row['brand_name']).'" data-price="'.$row['price'].'">'.htmlspecialchars($row['brand_name']).'</option>';
                                                            }
                                                            ?>
                                                        </select>
                                                        <input type="text" name="item_brand_manual[]" class="form-control brand-manual" placeholder="New brand" style="display: none;">
                                                        <button type="button" class="btn btn-default btn-xs brand-toggle-btn" title="Switch to manual entry">
                                                            <i class="fa fa-keyboard-o"></i>
                                                        </button>
                                                    </div>
                                                </span>
                                                <td>
                                                    <input type="number" name="item_price[]" class="form-control item-price" 
                                                           step="0.01" min="0" value="0">
                                                </span>
                                                <td>
                                                    <input type="number" name="item_quantity[]" class="form-control item-quantity" 
                                                           min="0" value="1">
                                                </span>
                                                <td class="suit-quantity-cell">
                                                    <input type="number" name="item_suit_quantity[]" class="form-control item-suit-quantity" 
                                                           min="0" value="0">
                                                </span>
                                                <td>
                                                    <input type="number" name="item_stitching_amount[]" class="form-control item-stitching-amount" 
                                                           step="0.01" min="0" value="0">
                                                </span>
                                                <td>
                                                    <input type="number" name="item_stitching_quantity[]" class="form-control item-stitching-quantity" 
                                                           min="0" value="0">
                                                </span>
                                                <td>
                                                    <span class="item-total">Rs 0.00</span>
                                                </span>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-danger btn-xs remove-item" disabled>
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </span>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Bill Summary -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="panel panel-success">
                                <div class="panel-heading">
                                    <h3 class="panel-title">Bill Summary</h3>
                                </div>
                                <div class="panel-body">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <table class="table table-bordered">
                                                <tr>
                                                    <td width="70%"><strong>Items Subtotal:</strong></span>
                                                    <td width="30%" class="text-right" id="itemsSubtotal">Rs 0.00</span>
                                                </tr>
                                                <tr>
                                                    <td><strong>Stitching Total:</strong></span>
                                                    <td class="text-right" id="stitchingTotalDisplay">Rs 0.00</span>
                                                </tr>
                                                <tr>
                                                    <td><strong>Total Before Discount:</strong></span>
                                                    <td class="text-right" id="totalBeforeDiscount">Rs 0.00</span>
                                                </tr>
                                                <tr>
                                                    <td><strong>Discount:</strong></span>
                                                    <td class="text-right text-danger" id="discountDisplay">- Rs 0.00</span>
                                                </tr>
                                                <tr>
                                                    <td><strong>Advance Payment:</strong></span>
                                                    <td class="text-right text-info" id="advanceDisplay">- Rs 0.00</span>
                                                </tr>
                                                <tr class="active">
                                                    <td><strong>Total Amount:</strong></span>
                                                    <td class="text-right"><h4 id="totalAmountDisplay" style="margin: 0; color: #27ae60;">Rs 0.00</h4></span>
                                                </tr>
                                                <tr id="remainingRow" style="display: none;">
                                                    <td><strong>Remaining Amount:</strong></span>
                                                    <td class="text-right"><strong id="remainingAmountDisplay" style="color: #f0ad4e;">Rs 0.00</strong></span>
                                                </tr>
                                            </table>
                                            <div id="advanceInfo" class="advance-info" style="display: none;">
                                                <i class="fa fa-info-circle"></i> 
                                                <span id="advanceInfoText"></span>
                                            </div>
                                        </div>
                                        <div class="col-md-4 text-center">
                                            <div style="margin-top: 20px;">
                                                <button type="submit" class="btn btn-primary btn-lg">
                                                    <i class="fa fa-save"></i> <?php echo $is_edit ? 'Update Bill' : 'Generate Bill'; ?>
                                                </button>
                                                <a href="view_billing.php" class="btn btn-default btn-lg" style="margin-top: 10px;">
                                                    <i class="fa fa-list"></i> View All Bills
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="js/select2.min.js"></script>
<script>
$(document).ready(function() {
    let itemCounter = <?php echo $is_edit ? count($bill_items) : 0; ?>;
    
    // Initialize Select2 for customer dropdown
    $('#customerSelect').select2({
        placeholder: "Select customer",
        allowClear: true,
        width: '100%'
    });
    
    // When customer is selected
    $('#customerSelect').on('change', function() {
        var selectedOption = $(this).find(':selected');
        var customerId = $(this).val();
        
        if (customerId > 0) {
            $('#customerName').val(selectedOption.data('name') || '').prop('disabled', true);
            $('#customerPhone').val(selectedOption.data('phone') || '').prop('disabled', true);
            $('#customerAddress').val(selectedOption.data('address') || '').prop('disabled', true);
        } else {
            $('#customerName').val('').prop('disabled', false);
            $('#customerPhone').val('').prop('disabled', false);
            $('#customerAddress').val('').prop('disabled', false);
        }
    });
    
    // Toggle between dropdown and manual input
    $(document).on('click', '.brand-toggle-btn', function() {
        const brandCell = $(this).closest('.brand-cell');
        const selectEl = brandCell.find('.brand-select');
        const manualInput = brandCell.find('.brand-manual');
        const toggleBtn = $(this);
        
        if (manualInput.is(':visible')) {
            const manualValue = manualInput.val();
            manualInput.hide();
            selectEl.show();
            toggleBtn.html('<i class="fa fa-keyboard-o"></i>');
            
            if (manualValue) {
                let matched = false;
                selectEl.find('option').each(function() {
                    if ($(this).val() === manualValue) {
                        $(this).prop('selected', true);
                        matched = true;
                        return false;
                    }
                });
                if (!matched && selectEl.find('option[value=""]').length) {
                    selectEl.val('').trigger('change');
                }
                selectEl.trigger('change');
            }
        } else {
            const selectedValue = selectEl.val();
            selectEl.hide();
            manualInput.show().val(selectedValue && selectedValue !== '' ? selectedValue : '');
            toggleBtn.html('<i class="fa fa-list"></i>');
        }
    });
    
    // When manual brand input changes
    $(document).on('blur', '.brand-manual', function() {
        const brandCell = $(this).closest('.brand-cell');
        const priceInput = brandCell.closest('.item-row').find('.item-price');
        const manualValue = $(this).val().trim();
        
        if (manualValue) {
            let found = false;
            $('.brand-select option').each(function() {
                if ($(this).val() === manualValue) {
                    const price = $(this).data('price') || 0;
                    if (price > 0 && (priceInput.val() == 0 || priceInput.val() == '')) {
                        priceInput.val(price);
                    }
                    found = true;
                    return false;
                }
            });
        }
        calculateItemTotal($(this).closest('.item-row'));
        calculateTotals();
    });
    
    // Add item button click
    $('#addItemBtn').click(function() {
        itemCounter++;
        const brandOptions = <?php 
            $options = [];
            $stmt = $pdo->query("SELECT DISTINCT brand_name, price FROM inventory ORDER BY brand_name");
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $options[] = $row;
            }
            echo json_encode($options);
        ?>;
        
        let optionsHtml = '<option value="">-- Select Item --</option>';
        brandOptions.forEach(opt => {
            optionsHtml += `<option value="${escapeHtml(opt.brand_name)}" data-price="${opt.price}">${escapeHtml(opt.brand_name)}</option>`;
        });
        
        const newRow = `
            <tr class="item-row" id="itemRow_${itemCounter}">
                <td class="row-number text-center">${itemCounter + 1}</td>
                <td class="brand-cell">
                    <div class="brand-input-group">
                        <select name="item_brand[]" class="form-control brand-select">
                            ${optionsHtml}
                        </select>
                        <input type="text" name="item_brand_manual[]" class="form-control brand-manual" placeholder="New brand" style="display: none;">
                        <button type="button" class="btn btn-default btn-xs brand-toggle-btn" title="Switch to manual entry">
                            <i class="fa fa-keyboard-o"></i>
                        </button>
                    </div>
                </span>
                <tr>
                    <input type="number" name="item_price[]" class="form-control item-price" step="0.01" min="0" value="0">
                </span>
                <tr>
                    <input type="number" name="item_quantity[]" class="form-control item-quantity" min="0" value="1">
                </span>
                <td class="suit-quantity-cell">
                    <input type="number" name="item_suit_quantity[]" class="form-control item-suit-quantity" min="0" value="0">
                </span>
                <tr>
                    <input type="number" name="item_stitching_amount[]" class="form-control item-stitching-amount" step="0.01" min="0" value="0">
                </span>
                <tr>
                    <input type="number" name="item_stitching_quantity[]" class="form-control item-stitching-quantity" min="0" value="0">
                </span>
                <tr>
                    <span class="item-total">Rs 0.00</span>
                </span>
                <td class="text-center">
                    <button type="button" class="btn btn-danger btn-xs remove-item">
                        <i class="fa fa-trash"></i>
                    </button>
                </span>
            </table>
        `;
        $('#itemsBody').append(newRow);
        updateRowNumbers();
        checkEmptyItems();
        calculateTotals();
    });
    
    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/[&<>]/g, function(m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        });
    }
    
    // Remove item
    $(document).on('click', '.remove-item', function() {
        if($('.item-row').length > 1) {
            $(this).closest('.item-row').remove();
            updateRowNumbers();
            checkEmptyItems();
            calculateTotals();
        }
    });
    
    // When brand is selected
    $(document).on('change', '.brand-select', function() {
        const selectedOption = $(this).find(':selected');
        const price = selectedOption.data('price') || 0;
        if(price > 0) {
            const priceInput = $(this).closest('.item-row').find('.item-price');
            if (priceInput.val() == 0 || priceInput.val() == '') {
                priceInput.val(price);
            }
        }
        calculateItemTotal($(this).closest('.item-row'));
        calculateTotals();
    });
    
    // Calculate item total when inputs change
    $(document).on('input', '.item-price, .item-quantity, .item-suit-quantity, .item-stitching-amount, .item-stitching-quantity', function() {
        calculateItemTotal($(this).closest('.item-row'));
        calculateTotals();
    });
    
    // Discount and advance input change
    $('#discount, #advance').on('input', calculateTotals);
    
    // Calculate item total for a specific row
    function calculateItemTotal(row) {
        const price = parseFloat(row.find('.item-price').val()) || 0;
        const quantity = parseInt(row.find('.item-quantity').val()) || 0;
        const suitQuantity = parseInt(row.find('.item-suit-quantity').val()) || 0;
        const stitchingAmount = parseFloat(row.find('.item-stitching-amount').val()) || 0;
        const stitchingQty = parseInt(row.find('.item-stitching-quantity').val()) || 0;
        
        // Note: suit quantity doesn't affect the price calculation by default
        // You can modify this logic if suit quantity should affect the total
        const itemTotal = price * quantity;
        const itemStitchingTotal = stitchingAmount * stitchingQty;
        const total = itemTotal + itemStitchingTotal;
        
        row.find('.item-total').text('Rs ' + total.toFixed(2));
    }
    
    // Check if there are any items with data
    function checkEmptyItems() {
        let hasItems = false;
        $('.item-row').each(function() {
            const brand = $(this).find('.brand-select').val();
            const manualBrand = $(this).find('.brand-manual').val();
            const finalBrand = manualBrand ? manualBrand : brand;
            const price = parseFloat($(this).find('.item-price').val()) || 0;
            const quantity = parseInt($(this).find('.item-quantity').val()) || 0;
            
            if(finalBrand || price > 0 || quantity > 0) {
                hasItems = true;
            }
        });
        
        if(!hasItems && $('.item-row').length === 1) {
            $('#emptyBillMessage').show();
        } else {
            $('#emptyBillMessage').hide();
        }
    }
    
    // Calculate all totals
    function calculateTotals() {
        let itemsSubtotal = 0;
        let stitchingTotal = 0;
        
        $('.item-row').each(function() {
            const price = parseFloat($(this).find('.item-price').val()) || 0;
            const quantity = parseInt($(this).find('.item-quantity').val()) || 0;
            const stitchingAmount = parseFloat($(this).find('.item-stitching-amount').val()) || 0;
            const stitchingQty = parseInt($(this).find('.item-stitching-quantity').val()) || 0;
            
            itemsSubtotal += (price * quantity);
            stitchingTotal += (stitchingAmount * stitchingQty);
        });
        
        const discount = parseFloat($('#discount').val()) || 0;
        const advance = parseFloat($('#advance').val()) || 0;
        const totalBeforeDiscount = itemsSubtotal + stitchingTotal;
        let totalAmount = totalBeforeDiscount - discount;
        
        let finalDiscount = discount;
        if(totalAmount < 0) {
            totalAmount = 0;
            finalDiscount = totalBeforeDiscount;
            $('#discount').val(totalBeforeDiscount);
        }
        
        let finalAdvance = advance;
        let remainingAmount = totalAmount - advance;
        if(advance > totalAmount) {
            finalAdvance = totalAmount;
            remainingAmount = 0;
            $('#advance').val(totalAmount);
        }
        
        $('#itemsSubtotal').text('Rs ' + itemsSubtotal.toFixed(2));
        $('#stitchingTotalDisplay').text('Rs ' + stitchingTotal.toFixed(2));
        $('#totalBeforeDiscount').text('Rs ' + totalBeforeDiscount.toFixed(2));
        $('#discountDisplay').text('- Rs ' + finalDiscount.toFixed(2));
        $('#advanceDisplay').text('- Rs ' + finalAdvance.toFixed(2));
        $('#totalAmountDisplay').text('Rs ' + totalAmount.toFixed(2));
        
        if(finalAdvance > 0) {
            if(remainingAmount > 0) {
                $('#remainingRow').show();
                $('#remainingAmountDisplay').text('Rs ' + remainingAmount.toFixed(2));
                $('#advanceInfo').show();
                $('#advanceInfoText').html('Advance payment of Rs ' + finalAdvance.toFixed(2) + ' received. Remaining balance of Rs ' + remainingAmount.toFixed(2) + ' is due.');
            } else if(remainingAmount === 0) {
                $('#remainingRow').hide();
                $('#advanceInfo').show();
                $('#advanceInfoText').html('Full payment received! Advance payment of Rs ' + finalAdvance.toFixed(2) + ' clears the total amount.');
            } else {
                $('#remainingRow').hide();
                $('#advanceInfo').hide();
            }
        } else {
            $('#remainingRow').hide();
            $('#advanceInfo').hide();
        }
    }
    
    // Update row numbers
    function updateRowNumbers() {
        $('.item-row').each(function(index) {
            $(this).find('.row-number').text(index + 1);
            
            const removeBtn = $(this).find('.remove-item');
            if($('.item-row').length > 1) {
                removeBtn.prop('disabled', false);
            } else {
                removeBtn.prop('disabled', true);
            }
        });
    }
    
    // Calculate totals for existing rows
    $('.item-row').each(function() {
        calculateItemTotal($(this));
    });
    
    // Initial calculations
    calculateTotals();
    checkEmptyItems();
    
    // Trigger change event on page load if a customer was selected in edit mode
    <?php if($is_edit && $bill_data['customer_id'] > 0): ?>
        $('#customerSelect').val(<?php echo $bill_data['customer_id']; ?>).trigger('change');
    <?php elseif(isset($_POST['customer_id']) && $_POST['customer_id'] > 0): ?>
        $('#customerSelect').trigger('change');
    <?php endif; ?>
});
</script>

<?php
include ('footer.php');
?>