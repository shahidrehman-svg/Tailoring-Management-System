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

$id = $_GET['id'] ?? 0;

// Fetch existing record
$stmt = $pdo->query("SELECT * FROM overall_income WHERE id = '$id' AND user_id = '$uid'");
$income = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$income) {
    $_SESSION['error'] = "Record not found!";
    redirect('overall_income_list.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $client_name = $_POST['client_name'];
        $product = $_POST['product'];
        $stitching = $_POST['stitching'];
        $clothes = $_POST['clothes'];
        $quantity_1 = $_POST['quantity_1'];
        $quantity_2 = $_POST['quantity_2'];
        $amount = $_POST['amount'];
        $paid = $_POST['paid'];
        
        $balance = $amount - $paid;
        
        $stmt = $pdo->prepare("UPDATE overall_income SET 
                              client_name = ?, 
                              product = ?, 
                              stitching = ?, 
                              clothes = ?, 
                              quantity_1 = ?, 
                              quantity_2 = ?, 
                              amount = ?, 
                              paid = ?, 
                              balance = ?
                              WHERE id = ? AND user_id = ?");
        
        $stmt->execute([$client_name, $product, $stitching, $clothes, $quantity_1, $quantity_2, $amount, $paid, $balance, $id, $uid]);
        
        $_SESSION['success'] = "Overall Income record updated successfully!";
        redirect('overall_income_list.php');
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

ob_start();
include ('header.php');
$header_content = ob_get_clean();

$header_content = str_replace(
    ['<link href="css/jquery.datatables.css" rel="stylesheet">', 
     '<script src="js/jquery.datatables.min.js"></script>'],
    '', 
    $header_content
);

echo $header_content;
?>

<div class="pageheader">
    <h2><i class="fa fa-edit"></i> Edit Overall Income Record</h2>
</div>

<div class="contentpanel">
    <div class="panel panel-default">
        <div class="panel-heading">
            <h4 class="panel-title">Edit Income Details</h4>
        </div>
        <div class="panel-body">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" class="form-horizontal" id="incomeForm">
                <div class="form-group">
                    <label class="col-sm-3 control-label">Client Name <span class="text-danger">*</span></label>
                    <div class="col-sm-6">
                        <input type="text" name="client_name" class="form-control" value="<?php echo htmlspecialchars($income['client_name']); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="col-sm-3 control-label">Product</label>
                    <div class="col-sm-6">
                        <input type="text" name="product" class="form-control" value="<?php echo htmlspecialchars($income['product']); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="col-sm-3 control-label">Stitching Amount <span class="text-danger">*</span></label>
                    <div class="col-sm-6">
                        <input type="number" name="stitching" id="stitching" class="form-control" step="0.01" min="0" required 
                               value="<?php echo number_format($income['stitching'], 2, '.', ''); ?>">
                        <small class="text-muted">Price per unit stitching</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="col-sm-3 control-label">Clothes Amount</label>
                    <div class="col-sm-6">
                        <input type="number" name="clothes" id="clothes" class="form-control" step="0.01" min="0" 
                               value="<?php echo number_format($income['clothes'], 2, '.', ''); ?>">
                        <small class="text-muted">Price per unit clothes/material</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="col-sm-3 control-label">Quantity 1 <span class="text-danger">*</span></label>
                    <div class="col-sm-6">
                        <input type="number" name="quantity_1" id="quantity_1" class="form-control" min="0" required 
                               value="<?php echo $income['quantity_1']; ?>">
                        <small class="text-muted">Number of stitching items</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="col-sm-3 control-label">Quantity 2</label>
                    <div class="col-sm-6">
                        <input type="number" name="quantity_2" id="quantity_2" class="form-control" min="0" 
                               value="<?php echo $income['quantity_2']; ?>">
                        <small class="text-muted">Number of clothes/material items</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="col-sm-3 control-label">Total Amount</label>
                    <div class="col-sm-6">
                        <input type="number" name="amount" id="amount" class="form-control" step="0.01" min="0" 
                               value="<?php echo number_format($income['amount'], 2, '.', ''); ?>">
                        <small class="text-muted">
                            <span id="calculationHint">Saved amount: <?php echo number_format($income['amount'], 2); ?></span>
                            <br>
                            <span class="text-info"><i class="fa fa-info-circle"></i> Auto-updates when quantities change</span>
                        </small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="col-sm-3 control-label">Amount Paid</label>
                    <div class="col-sm-6">
                        <input type="number" name="paid" id="paid" class="form-control" step="0.01" min="0" 
                               value="<?php echo number_format($income['paid'], 2, '.', ''); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="col-sm-3 control-label">Balance Amount</label>
                    <div class="col-sm-6">
                        <input type="number" name="balance" id="balance" class="form-control" step="0.01" readonly 
                               value="<?php echo number_format($income['balance'], 2, '.', ''); ?>" style="background-color: #f5f5f5;">
                        <small class="text-muted">Balance = Total Amount - Paid</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="col-sm-3"></div>
                    <div class="col-sm-6">
                        <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Update Record</button>
                        <a href="overall_income_list.php" class="btn btn-default">Cancel</a>
                        <button type="button" id="recalculateBtn" class="btn btn-info">
                            <i class="fa fa-calculator"></i> Recalculate
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include ('footer.php'); ?>

<!-- Simple JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Page loaded - showing saved amount');
    
    // Fix menu toggle
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
    
    // FIRST: Show the saved amount from PHP
    var savedAmount = <?php echo $income['amount']; ?>;
    var amountInput = document.getElementById('amount');
    amountInput.value = savedAmount.toFixed(2);
    
    console.log('Saved amount loaded:', savedAmount);
    
    // Store original values
    var originalQty1 = <?php echo $income['quantity_1']; ?>;
    var originalQty2 = <?php echo $income['quantity_2']; ?>;
    var originalStitching = <?php echo $income['stitching']; ?>;
    var originalClothes = <?php echo $income['clothes']; ?>;
    
    // Track current values
    var currentQty1 = originalQty1;
    var currentQty2 = originalQty2;
    var currentStitching = originalStitching;
    var currentClothes = originalClothes;
    
    // Function to calculate balance
    function calculateBalance() {
        var amountInput = document.getElementById('amount');
        var paidInput = document.getElementById('paid');
        var balanceInput = document.getElementById('balance');
        
        if (amountInput && paidInput && balanceInput) {
            var amount = parseFloat(amountInput.value) || 0;
            var paid = parseFloat(paidInput.value) || 0;
            var balance = amount - paid;
            
            balanceInput.value = balance.toFixed(2);
            
            // Color coding
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
    
    // Function to update total when quantities change
    function updateTotalForQuantityChange() {
        var stitching = parseFloat(document.getElementById('stitching').value) || 0;
        var clothes = parseFloat(document.getElementById('clothes').value) || 0;
        var qty1 = parseInt(document.getElementById('quantity_1').value) || 0;
        var qty2 = parseInt(document.getElementById('quantity_2').value) || 0;
        var amountInput = document.getElementById('amount');
        
        // Get current amount
        var currentAmount = parseFloat(amountInput.value) || savedAmount;
        
        // Calculate adjustment based on quantity changes
        var qty1Diff = qty1 - currentQty1;
        var qty2Diff = qty2 - currentQty2;
        
        var adjustment = 0;
        if (qty1Diff !== 0) {
            adjustment += qty1Diff * stitching;
        }
        if (qty2Diff !== 0) {
            adjustment += qty2Diff * clothes;
        }
        
        // Update the total
        var newAmount = currentAmount + adjustment;
        if (newAmount < 0) newAmount = 0;
        
        amountInput.value = newAmount.toFixed(2);
        
        // Update current quantities
        currentQty1 = qty1;
        currentQty2 = qty2;
        
        // Update hint
        var hintElement = document.getElementById('calculationHint');
        if (hintElement && adjustment !== 0) {
            hintElement.innerHTML = 'Adjusted: ' + (adjustment > 0 ? '+' : '') + adjustment.toFixed(2);
        }
        
        calculateBalance();
    }
    
    // Function to update total when prices change
    function updateTotalForPriceChange() {
        var stitching = parseFloat(document.getElementById('stitching').value) || 0;
        var clothes = parseFloat(document.getElementById('clothes').value) || 0;
        var qty1 = parseInt(document.getElementById('quantity_1').value) || 0;
        var qty2 = parseInt(document.getElementById('quantity_2').value) || 0;
        var amountInput = document.getElementById('amount');
        
        // Get current amount
        var currentAmount = parseFloat(amountInput.value) || savedAmount;
        
        // Calculate adjustment based on price changes
        var stitchingDiff = stitching - currentStitching;
        var clothesDiff = clothes - currentClothes;
        
        var adjustment = 0;
        if (stitchingDiff !== 0) {
            adjustment += stitchingDiff * qty1;
        }
        if (clothesDiff !== 0) {
            adjustment += clothesDiff * qty2;
        }
        
        // Update the total
        var newAmount = currentAmount + adjustment;
        if (newAmount < 0) newAmount = 0;
        
        amountInput.value = newAmount.toFixed(2);
        
        // Update current prices
        currentStitching = stitching;
        currentClothes = clothes;
        
        // Update hint
        var hintElement = document.getElementById('calculationHint');
        if (hintElement && adjustment !== 0) {
            hintElement.innerHTML = 'Adjusted: ' + (adjustment > 0 ? '+' : '') + adjustment.toFixed(2);
        }
        
        calculateBalance();
    }
    
    // Function to recalculate from scratch
    function recalculateFromScratch() {
        var stitching = parseFloat(document.getElementById('stitching').value) || 0;
        var clothes = parseFloat(document.getElementById('clothes').value) || 0;
        var qty1 = parseInt(document.getElementById('quantity_1').value) || 0;
        var qty2 = parseInt(document.getElementById('quantity_2').value) || 0;
        var amountInput = document.getElementById('amount');
        
        var newAmount = (stitching * qty1) + (clothes * qty2);
        amountInput.value = newAmount.toFixed(2);
        
        // Update current values
        currentQty1 = qty1;
        currentQty2 = qty2;
        currentStitching = stitching;
        currentClothes = clothes;
        
        var hintElement = document.getElementById('calculationHint');
        if (hintElement) {
            hintElement.innerHTML = 'Recalculated from scratch: ' + newAmount.toFixed(2);
        }
        
        calculateBalance();
    }
    
    // Get input elements
    var stitchingInput = document.getElementById('stitching');
    var clothesInput = document.getElementById('clothes');
    var quantity1Input = document.getElementById('quantity_1');
    var quantity2Input = document.getElementById('quantity_2');
    var paidInput = document.getElementById('paid');
    var recalculateBtn = document.getElementById('recalculateBtn');
    
    if (stitchingInput && clothesInput && quantity1Input && quantity2Input && amountInput && paidInput) {
        // Calculate initial balance
        calculateBalance();
        
        // Set up event listeners
        quantity1Input.addEventListener('input', updateTotalForQuantityChange);
        quantity2Input.addEventListener('input', updateTotalForQuantityChange);
        stitchingInput.addEventListener('input', updateTotalForPriceChange);
        clothesInput.addEventListener('input', updateTotalForPriceChange);
        
        paidInput.addEventListener('input', calculateBalance);
        
        if (recalculateBtn) {
            recalculateBtn.addEventListener('click', recalculateFromScratch);
        }
    }
    
    // Initialize select2 if available
    if (typeof jQuery !== 'undefined' && jQuery().select2) {
        jQuery('select').select2({
            minimumResultsForSearch: -1
        });
    }
});
</script>