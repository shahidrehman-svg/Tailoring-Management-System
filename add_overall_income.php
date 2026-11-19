<?php
require_once('function.php');
dbconnect();
session_start();

if (!is_user()) {
    redirect('index.php');
}

$user = $_SESSION['username'];
$usid = $pdo->query("SELECT id FROM users WHERE username='".$user."'");
$usid = $usid->fetch(PDO::FETCH_ASSOC);
$uid = $usid['id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $client_name = $_POST['client_name'];
        $product = $_POST['product'];
        $stitching = $_POST['stitching'];
        $clothes = $_POST['clothes'];
        $quantity_1 = $_POST['quantity_1'];
        $quantity_2 = $_POST['quantity_2'];
        $amount = $_POST['amount']; // Get the amount from form (can be manual or calculated)
        $paid = $_POST['paid'];
        
        // Calculate balance based on the provided amount
        $balance = $amount - $paid;
        
        $stmt = $pdo->prepare("INSERT INTO overall_income (client_name, product, stitching, clothes, quantity_1, quantity_2, amount, paid, balance, user_id) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([$client_name, $product, $stitching, $clothes, $quantity_1, $quantity_2, $amount, $paid, $balance, $uid]);
        
        $_SESSION['success'] = "Overall Income record added successfully!";
        redirect('overall_income_list.php');
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Include header WITHOUT extra JS/CSS that might conflict
ob_start();
include ('header.php');
$header_content = ob_get_clean();

// Remove any conflicting JS/CSS links that might be in header
$header_content = str_replace(
    ['<link href="css/jquery.datatables.css" rel="stylesheet">', 
     '<script src="js/jquery.datatables.min.js"></script>'],
    '', 
    $header_content
);

echo $header_content;
?>

<div class="pageheader">
    <h2><i class="fa fa-money"></i> Add Overall Income</h2>
</div>

<div class="contentpanel">
    <div class="panel panel-default">
        <div class="panel-heading">
            <h4 class="panel-title">Overall Income Details</h4>
        </div>
        <div class="panel-body">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" class="form-horizontal" id="incomeForm">
                <div class="form-group">
                    <label class="col-sm-3 control-label">Client Name <span class="text-danger">*</span></label>
                    <div class="col-sm-6">
                        <input type="text" name="client_name" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="col-sm-3 control-label">Product</label>
                    <div class="col-sm-6">
                        <input type="text" name="product" class="form-control">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="col-sm-3 control-label">Stitching Amount <span class="text-danger">*</span></label>
                    <div class="col-sm-6">
                        <input type="number" name="stitching" id="stitching" class="form-control" step="0.01" min="0" required value="0">
                        <small class="text-muted">Price per unit stitching</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="col-sm-3 control-label">Clothes Amount</label>
                    <div class="col-sm-6">
                        <input type="number" name="clothes" id="clothes" class="form-control" step="0.01" min="0" value="0">
                        <small class="text-muted">Price per unit clothes/material</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="col-sm-3 control-label">Quantity 1 <span class="text-danger">*</span></label>
                    <div class="col-sm-6">
                        <input type="number" name="quantity_1" id="quantity_1" class="form-control" min="0" value="1" required>
                        <small class="text-muted">Number of stitching items</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="col-sm-3 control-label">Quantity 2</label>
                    <div class="col-sm-6">
                        <input type="number" name="quantity_2" id="quantity_2" class="form-control" min="0" value="0">
                        <small class="text-muted">Number of clothes/material items</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="col-sm-3 control-label">Total Amount</label>
                    <div class="col-sm-6">
                        <input type="number" name="amount" id="amount" class="form-control" step="0.01" min="0" value="0">
                        <small class="text-muted">
                            <span id="calculationHint">Auto-calculated: (Stitching Amount × Quantity 1) + (Clothes Amount × Quantity 2)</span>
                            <br>
                            <span class="text-info"><i class="fa fa-info-circle"></i> You can edit this field manually if needed</span>
                        </small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="col-sm-3 control-label">Amount Paid</label>
                    <div class="col-sm-6">
                        <input type="number" name="paid" id="paid" class="form-control" step="0.01" min="0" value="0">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="col-sm-3 control-label">Balance Amount</label>
                    <div class="col-sm-6">
                        <input type="number" name="balance" id="balance" class="form-control" step="0.01" readonly style="background-color: #f5f5f5;">
                        <small class="text-muted">Balance = Total Amount - Paid</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="col-sm-3"></div>
                    <div class="col-sm-6">
                        <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save Income Record</button>
                        <button type="reset" class="btn btn-default">Reset</button>
                        <button type="button" id="recalculateBtn" class="btn btn-info">
                            <i class="fa fa-calculator"></i> Recalculate Total
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include ('footer.php'); ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Fix menu toggle button
    var menuToggle = document.querySelector('.menutoggle');
    if (menuToggle) {
        menuToggle.addEventListener('click', function(e) {
            e.preventDefault();
            var body = document.querySelector('body');
            if (body.classList.contains('menu-hidden')) {
                body.classList.remove('menu-hidden');
            } else {
                body.classList.add('menu-hidden');
            }
        });
    }
    
    var isManualEdit = false;
    var lastCalculatedAmount = 0;
    
    // Function to calculate total amount from inputs
    function calculateTotalFromInputs() {
        var stitching = parseFloat(document.getElementById('stitching').value) || 0;
        var clothes = parseFloat(document.getElementById('clothes').value) || 0;
        var quantity1 = parseFloat(document.getElementById('quantity_1').value) || 0;
        var quantity2 = parseFloat(document.getElementById('quantity_2').value) || 0;
        
        var stitchingAmount = stitching * quantity1;
        var clothesAmount = clothes * quantity2;
        return stitchingAmount + clothesAmount;
    }
    
    // Calculate balance only
    function calculateBalance() {
        var amountInput = document.getElementById('amount');
        var paidInput = document.getElementById('paid');
        var balanceInput = document.getElementById('balance');
        
        if (amountInput && paidInput && balanceInput) {
            var amount = parseFloat(amountInput.value) || 0;
            var paid = parseFloat(paidInput.value) || 0;
            var balance = amount - paid;
            
            balanceInput.value = balance.toFixed(2);
            
            // Color code based on balance
            if (balance > 0) {
                balanceInput.style.color = 'red';
                balanceInput.style.fontWeight = 'bold';
            } else if (balance < 0) {
                balanceInput.style.color = 'orange';
                balanceInput.style.fontWeight = 'bold';
            } else {
                balanceInput.style.color = 'green';
                balanceInput.style.fontWeight = 'bold';
            }
        }
    }
    
    // Function to auto-calculate and update the amount field
    function autoCalculateAndUpdate() {
        if (!isManualEdit) {
            var calculatedAmount = calculateTotalFromInputs();
            var amountInput = document.getElementById('amount');
            
            amountInput.value = calculatedAmount.toFixed(2);
            lastCalculatedAmount = calculatedAmount;
            
            // Update calculation hint
            var hintElement = document.getElementById('calculationHint');
            if (hintElement) {
                hintElement.innerHTML = 'Auto-calculated: (Stitching Amount × Quantity 1) + (Clothes Amount × Quantity 2)';
            }
        }
        calculateBalance();
    }
    
    // Function to manually recalculate
    function recalculateTotal() {
        var calculatedAmount = calculateTotalFromInputs();
        var amountInput = document.getElementById('amount');
        
        amountInput.value = calculatedAmount.toFixed(2);
        lastCalculatedAmount = calculatedAmount;
        isManualEdit = false;
        
        // Update calculation hint
        var hintElement = document.getElementById('calculationHint');
        if (hintElement) {
            hintElement.innerHTML = 'Auto-calculated: (Stitching Amount × Quantity 1) + (Clothes Amount × Quantity 2)';
        }
        
        calculateBalance();
    }
    
    // Attach event listeners for calculation inputs
    var stitchingInput = document.getElementById('stitching');
    var clothesInput = document.getElementById('clothes');
    var quantity1Input = document.getElementById('quantity_1');
    var quantity2Input = document.getElementById('quantity_2');
    var amountInput = document.getElementById('amount');
    var paidInput = document.getElementById('paid');
    var recalculateBtn = document.getElementById('recalculateBtn');
    
    if (stitchingInput && clothesInput && quantity1Input && quantity2Input && amountInput && paidInput) {
        // Auto-calculate when any of these inputs change
        stitchingInput.addEventListener('input', function() {
            if (!isManualEdit) {
                autoCalculateAndUpdate();
            } else {
                calculateBalance();
            }
        });
        
        clothesInput.addEventListener('input', function() {
            if (!isManualEdit) {
                autoCalculateAndUpdate();
            } else {
                calculateBalance();
            }
        });
        
        quantity1Input.addEventListener('input', function() {
            if (!isManualEdit) {
                autoCalculateAndUpdate();
            } else {
                calculateBalance();
            }
        });
        
        quantity2Input.addEventListener('input', function() {
            if (!isManualEdit) {
                autoCalculateAndUpdate();
            } else {
                calculateBalance();
            }
        });
        
        // When user manually edits amount field
        amountInput.addEventListener('input', function() {
            isManualEdit = true;
            var hintElement = document.getElementById('calculationHint');
            if (hintElement) {
                hintElement.innerHTML = 'Manual entry - click "Recalculate Total" to auto-calculate';
            }
            calculateBalance();
        });
        
        amountInput.addEventListener('change', function() {
            calculateBalance();
        });
        
        paidInput.addEventListener('input', calculateBalance);
        
        // Recalculate button
        if (recalculateBtn) {
            recalculateBtn.addEventListener('click', recalculateTotal);
        }
        
        // Initialize calculation
        autoCalculateAndUpdate();
    }
    
    // Form reset functionality
    var resetBtn = document.querySelector('button[type="reset"]');
    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            setTimeout(function() {
                isManualEdit = false;
                autoCalculateAndUpdate();
            }, 10);
        });
    }
    
    // Initialize select dropdowns if select2 is available
    if (typeof jQuery !== 'undefined' && jQuery().select2) {
        jQuery('select').select2({
            minimumResultsForSearch: -1
        });
    }
});
</script>