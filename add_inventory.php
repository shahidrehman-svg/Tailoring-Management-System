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
include ('header.php');
?>

<div class="pageheader">
    <h2><i class="fa fa-shopping-cart"></i> Add New Inventory Item</h2>
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
                        // Insert into inventory table
                        $stmt = $pdo->prepare("INSERT INTO inventory (brand_name, quantity, price) 
                                               VALUES (:brand_name, :quantity, :price)");
                        
                        $stmt->execute([
                            ':brand_name' => $brand_name,
                            ':quantity' => $quantity,
                            ':price' => $price
                        ]);
                        
                        echo "<div class='alert alert-success alert-dismissable'>
                            <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>
                            <strong>Success!</strong> Inventory item added successfully!
                        </div>";
                        
                        // Clear form fields
                        $_POST = array();
                        
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
                        <input name="brand_name" value="<?php echo isset($_POST['brand_name']) ? htmlspecialchars($_POST['brand_name']) : ''; ?>" 
                               class="form-control" type="text" placeholder="Enter brand name" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="col-sm-3 control-label">Quantity <span class="text-danger">*</span></label>
                    <div class="col-sm-6">
                        <input name="quantity" value="<?php echo isset($_POST['quantity']) ? $_POST['quantity'] : '1'; ?>" 
                               class="form-control" type="number" min="0" placeholder="Enter quantity" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="col-sm-3 control-label">Price (Rs) <span class="text-danger">*</span></label>
                    <div class="col-sm-6">
                        <div class="input-group">
                            <span class="input-group-addon">Rs</span>
                            <input name="price" value="<?php echo isset($_POST['price']) ? $_POST['price'] : ''; ?>" 
                                   class="form-control" type="number" step="0.01" min="0.01" placeholder="0.00" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="col-sm-6 col-sm-offset-3">
                        <br/>
                        <button type="submit" class="btn btn-lg btn-primary btn-block">
                            <i class="fa fa-plus"></i> Add to Inventory
                        </button>
                        <br/>
                        <a href="view_inventory.php" class="btn btn-default btn-block">
                            <i class="fa fa-list"></i> View Inventory
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
include ('footer.php');
?>