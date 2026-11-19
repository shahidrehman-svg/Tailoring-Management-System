<?php
require_once('function.php');
dbconnect();
session_start();

if (!is_user()) {
    redirect('index.php');
}

// Get item ID
$id = $_GET['id'] ?? 0;

// Fetch item details
$stmt = $pdo->prepare("SELECT * FROM inventory WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$item) {
    echo "<script>alert('Item not found!'); window.location='view_inventory.php';</script>";
    exit();
}

include ('header.php');
?>

<div class="pageheader">
    <h2><i class="fa fa-edit"></i> Edit Inventory Item</h2>
</div>

<div class="contentpanel">
    <div class="panel panel-default">
        <div class="panel-body">
            
            <?php
            if($_POST) {
                $brand_name = $_POST["brand_name"];
                $quantity = $_POST["quantity"];
                $price = $_POST["price"];
                
                $error = 0;
                $errors = array();
                
                // Validation
                if(trim($brand_name) == "") {
                    $error = 1;
                    $errors[] = "Brand Name cannot be empty!";
                }
                
                if(!is_numeric($quantity) || $quantity < 0) {
                    $error = 1;
                    $errors[] = "Quantity must be a valid number!";
                }
                
                if(!is_numeric($price) || $price <= 0) {
                    $error = 1;
                    $errors[] = "Price must be a valid number greater than 0!";
                }
                
                // Show all errors
                if($error == 1) {
                    foreach($errors as $err) {
                        echo "<div class='alert alert-danger alert-dismissable'>
                            <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>
                            $err
                        </div>";
                    }
                } else {
                    try {
                        // Update inventory item
                        $stmt = $pdo->prepare("UPDATE inventory SET 
                            brand_name = :brand_name, 
                            quantity = :quantity, 
                            price = :price 
                            WHERE id = :id");
                        
                        $stmt->execute([
                            ':brand_name' => $brand_name,
                            ':quantity' => $quantity,
                            ':price' => $price,
                            ':id' => $id
                        ]);
                        
                        echo "<div class='alert alert-success alert-dismissable'>
                            <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>
                            <strong>Success!</strong> Inventory item updated successfully!
                        </div>";
                        
                        // Refresh item data
                        $stmt = $pdo->prepare("SELECT * FROM inventory WHERE id = ?");
                        $stmt->execute([$id]);
                        $item = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                    } catch(PDOException $e) {
                        echo "<div class='alert alert-danger alert-dismissable'>
                            <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>
                            <strong>Error!</strong> " . $e->getMessage() . "
                        </div>";
                    }
                }
            }
            ?>
            
            <form action="" method="post" class="form-horizontal">
                <div class="form-group">
                    <label class="col-sm-3 control-label">Brand Name <span class="text-danger">*</span></label>
                    <div class="col-sm-6">
                        <input name="brand_name" value="<?php echo htmlspecialchars($item['brand_name']); ?>" 
                               class="form-control" type="text" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="col-sm-3 control-label">Quantity <span class="text-danger">*</span></label>
                    <div class="col-sm-6">
                        <input name="quantity" value="<?php echo $item['quantity']; ?>" 
                               class="form-control" type="number" min="0" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="col-sm-3 control-label">Price ($) <span class="text-danger">*</span></label>
                    <div class="col-sm-6">
                        <div class="input-group">
                            <span class="input-group-addon">$</span>
                            <input name="price" value="<?php echo $item['price']; ?>" 
                                   class="form-control" type="number" step="0.01" min="0.01" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="col-sm-6 col-sm-offset-3">
                        <br/>
                        <button type="submit" class="btn btn-lg btn-primary btn-block">
                            <i class="fa fa-save"></i> Update Item
                        </button>
                        <br/>
                        <a href="view_inventory.php" class="btn btn-default btn-block">
                            <i class="fa fa-arrow-left"></i> Back to Inventory
                        </a>
                    </div>
                </div>
            </form>
            
            <hr>
            
            <div class="well">
                <h4>Item Information:</h4>
                <p><strong>Created:</strong> <?php echo date('F d, Y h:i A', strtotime($item['created_at'])); ?></p>
                <p><strong>Last Updated:</strong> <?php echo date('F d, Y h:i A', strtotime($item['updated_at'])); ?></p>
                <p><strong>Current Value:</strong> $<?php echo number_format($item['quantity'] * $item['price'], 2); ?></p>
            </div>
        </div>
    </div>
</div>

<?php
include ('footer.php');
?>