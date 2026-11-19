<?php
require_once('function.php');
dbconnect();
session_start();

if (!is_user()) {
    redirect('index.php');
}

// Set user variables BEFORE including header.php
$user = $_SESSION['username'];
$usid = $pdo->query("SELECT id FROM users WHERE username='".$user."'");
$usid = $usid->fetch(PDO::FETCH_ASSOC);
$uid = $usid['id'];

// You might also want to set these as session variables if header.php needs them
$_SESSION['user_id'] = $uid;
$_SESSION['username'] = $user;

// NOW include header.php - all variables are set
include ('header.php');
?>

<style>
    .dataTables_length select[name="inventoryTable_length"] {
    min-width: 60px !important;
    max-width: 60px !important;
    height: 36px !important;
}
</style>

<link href="css/jquery.datatables.css" rel="stylesheet">
<!-- If you don't have these CSS files, you can use CDN -->
<!--
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.24/css/jquery.dataTables.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/1.7.0/css/buttons.dataTables.min.css">
-->

<div class="pageheader">
    <div class="row">
        <div class="col-xs-6 col-sm-8 col-md-9">
            <h2><i class="fa fa-th-list"></i> Inventory List</h2>
        </div>
        <div class="col-xs-6 col-sm-4 col-md-3 text-right">
            <div class="btn-group mr5">
                <a href="add_inventory.php" class="btn btn-primary">
                    <i class="fa fa-plus"></i> Add New Item
                </a>
            </div>
        </div>
    </div>
</div>

<div class="contentpanel">
    <div class="panel panel-default">
        <div class="panel-body">
            
            <?php
            // Handle delete action
            if(isset($_GET['delete'])) {
                $id = $_GET['delete'];
                $stmt = $pdo->prepare("DELETE FROM inventory WHERE id = ?");
                if($stmt->execute([$id])) {
                    echo "<div class='alert alert-success alert-dismissable'>
                        <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>
                        Item deleted successfully!
                    </div>";
                }
            }
            
            // Handle update quantity
            if(isset($_POST['update_quantity'])) {
                $id = $_POST['id'];
                $quantity = $_POST['quantity'];
                
                $stmt = $pdo->prepare("UPDATE inventory SET quantity = ? WHERE id = ?");
                if($stmt->execute([$quantity, $id])) {
                    echo "<div class='alert alert-success alert-dismissable'>
                        <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>
                        Quantity updated successfully!
                    </div>";
                }
            }
            ?>
            
            <div class="table-responsive">
                <table class="table table-striped table-bordered" id="inventoryTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Brand Name</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Total Value</th>
                            <th>Added On</th>
                            <th>Last Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->query("SELECT * FROM inventory ORDER BY brand_name");
                        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        if(count($items) > 0) {
                            foreach($items as $item) {
                                $total_value = $item['quantity'] * $item['price'];
                                ?>
                                <tr>
                                    <td><?php echo $item['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($item['brand_name']); ?></strong></td>
                                    <td>
                                        <form method="post" class="form-inline" style="display: inline;">
                                            <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                            <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                                   class="form-control input-sm" style="width: 80px;" min="0">
                                            <button type="submit" name="update_quantity" class="btn btn-xs btn-success">
                                                <i class="fa fa-check"></i>
                                            </button>
                                        </form>
                                    </td>
                                    <td>Rs<?php echo number_format($item['price'], 2); ?></td>
                                    <td>Rs<?php echo number_format($total_value, 2); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($item['created_at'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($item['updated_at'])); ?></td>
                                    <td>
                                        <a href="edit_inventory.php?id=<?php echo $item['id']; ?>" class="btn btn-xs btn-primary" title="Edit">
                                            <i class="fa fa-pencil"></i>
                                        </a>
                                        <a href="?delete=<?php echo $item['id']; ?>" class="btn btn-xs btn-danger" 
                                           onclick="return confirm('Are you sure you want to delete this item?')" title="Delete">
                                            <i class="fa fa-trash-o"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php
                            }
                        } else {
                            ?>
                            <tr>
                                <td colspan="8" class="text-center">
                                    <div class="alert alert-info">
                                        <i class="fa fa-info-circle"></i> No inventory items found. 
                                        <a href="add_inventory.php">Add your first item</a>
                                    </div>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
                
                <?php if(count($items) > 0) { 
                // Calculate total inventory value
                $stmt = $pdo->query("SELECT 
                    COUNT(*) as total_items,
                    SUM(quantity) as total_quantity,
                    SUM(quantity * price) as total_value 
                    FROM inventory");
                $summary = $stmt->fetch(PDO::FETCH_ASSOC);
                ?>
                <div class="well">
                    <div class="row">
                        <div class="col-md-3">
                            <h4>Total Items: <strong id="summary-total-items"><?php echo $summary['total_items']; ?></strong></h4>
                        </div>
                        <div class="col-md-3">
                            <h4>Total Quantity: <strong id="summary-total-quantity"><?php echo $summary['total_quantity']; ?></strong></h4>
                        </div>
                        <div class="col-md-6">
                            <h4>Total Inventory Value: <strong class="text-success" id="summary-total-value">Rs<?php echo number_format($summary['total_value'] ?? 0, 2); ?></strong></h4>
                        </div>
                    </div>
                </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<?php
include ('footer.php');
?>

<script src="js/jquery.datatables.min.js"></script>
<!-- If you don't have local files, use CDN -->
<!--
<script type="text/javascript" src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/1.7.0/js/dataTables.buttons.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/1.7.0/js/buttons.html5.min.js"></script>
-->

<script>
jQuery(document).ready(function() {
    "use strict";
    
    // Initialize DataTable
    var table = jQuery('#inventoryTable').DataTable({
        "paging": true,
        "pageLength": 10,
        "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        "searching": true,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "responsive": true,
        "dom": 'lBfrtip',
        "buttons": [
            {
                extend: 'copy',
                className: 'btn btn-default',
                text: '<i class="fa fa-copy"></i> Copy'
            },
            {
                extend: 'csv',
                className: 'btn btn-default',
                text: '<i class="fa fa-file-text-o"></i> CSV'
            },
            {
                extend: 'excel',
                className: 'btn btn-default',
                text: '<i class="fa fa-file-excel-o"></i> Excel'
            },
            {
                extend: 'print',
                className: 'btn btn-default',
                text: '<i class="fa fa-print"></i> Print'
            }
        ],
        "language": {
            "lengthMenu": "Show _MENU_ entries",
            "zeroRecords": "No matching records found",
            "info": "Showing _START_ to _END_ of _TOTAL_ entries",
            "infoEmpty": "Showing 0 to 0 of 0 entries",
            "infoFiltered": "(filtered from _MAX_ total entries)",
            "search": "Search:",
            "paginate": {
                "first": "First",
                "last": "Last",
                "next": "Next",
                "previous": "Previous"
            }
        },
        "drawCallback": function(settings) {
            // Calculate totals for filtered data
            calculateFilteredTotals();
        }
    });
    
    // Function to calculate totals for filtered data
    function calculateFilteredTotals() {
        var api = table.api();
        var filteredData = api.rows({filter: 'applied'}).data();
        
        var totalItems = filteredData.count();
        var totalQuantity = 0;
        var totalValue = 0;
        
        // Calculate from filtered rows
        filteredData.each(function(row) {
            // Extract quantity from the input field in the form
            var quantityInput = jQuery(row[2]).find('input[name="quantity"]').val();
            var quantity = parseInt(quantityInput) || 0;
            
            // Extract price (remove $ and commas)
            var priceText = row[3].replace(/[^\d.-]/g, '');
            var price = parseFloat(priceText) || 0;
            
            totalQuantity += quantity;
            totalValue += (quantity * price);
        });
        
        // Update summary
        jQuery('#summary-total-items').text(totalItems);
        jQuery('#summary-total-quantity').text(totalQuantity);
        jQuery('#summary-total-value').text('$' + totalValue.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,'));
    }
    
    // Recalculate totals when quantity is updated
    jQuery(document).on('submit', 'form[method="post"]', function(e) {
        // Use setTimeout to recalculate after form submission
        setTimeout(function() {
            calculateFilteredTotals();
        }, 100);
    });
    
    // Initialize totals calculation
    calculateFilteredTotals();
    
    // Delete confirmation
    jQuery('#inventoryTable').on('click', 'a.btn-danger', function(e) {
        if(!confirm('Are you sure you want to delete this item?')) {
            e.preventDefault();
        }
    });
});
</script>

