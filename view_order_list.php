<?php
require_once('function.php');
dbconnect();
session_start();

if (!is_user()) {
    redirect('index.php');
}

?>

<?php
$user = $_SESSION['username'];
$usid = $pdo->query("SELECT id FROM users WHERE username='" . $user . "'");
$usid = $usid->fetch(PDO::FETCH_ASSOC);
$uid = $usid['id'];
include('header.php');

// Handle delete
if (isset($_GET["id"])) {
    $id = $_GET["id"];
    $pdo->exec("DELETE FROM `order` WHERE id='$id'");

    echo "<div class='alert alert-success alert-dismissable'>
    <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>	
    Order Deleted Successfully!
    </div>";
}

// Handle print selected
if (isset($_POST['print_selected']) && isset($_POST['selected_orders'])) {
    $_SESSION['selected_orders'] = $_POST['selected_orders'];
    echo "<script>window.open('print_selected_invoices.php', '_blank');</script>";
}
?>
<link href="css/style.default.css" rel="stylesheet">
<link href="css/jquery.datatables.css" rel="stylesheet">
<!-- DataTables CSS -->
<link href="../bower_components/datatables-plugins/integration/bootstrap/3/dataTables.bootstrap.css" rel="stylesheet">
<!-- DataTables Responsive CSS -->
<link href="../bower_components/datatables-responsive/css/dataTables.responsive.css" rel="stylesheet">

<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">View Orders</h1>
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->

    <!-- Print Selected Form -->
    <form method="POST" action="" id="printForm">
        <div class="row" style="margin-bottom: 15px;">
            <div class="col-lg-12">
                <div class="btn-group">
                    <button type="button" class="btn btn-primary" onclick="selectAll()">Select All</button>
                    <button type="button" class="btn btn-primary" onclick="deselectAll()">Deselect All</button>
                    <button type="submit" name="print_selected" class="btn btn-success">
                        <i class="fa fa-print"></i> Print Selected
                    </button>
                    <!-- NEW BUTTON: Navigate to overdue orders page -->
                    <a href="overdue_orders.php" class="btn btn-danger">
                        <i class="fa fa-exclamation-triangle"></i> Upcoming Orders (2+ Days)
                    </a>
                </div>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-body">
                <div class="clearfix mb30"></div>
                <div class="table-responsive">
                    <table class="table table-striped" id="table2">
                        <thead>
                            <tr>
                                <th width="20px"><input type="checkbox" id="selectAllCheckbox" style="width: 14px; height: 14px; margin: 0;"></th>
                                <th>Sr.No</th>
                                <th style="width:80px;">Customer</th>
                                <th>Address</th>
                                <th>Date Received</th>
                                <th>Date Collected</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Modified query - removed amount, paid from selection
                            $ddaa = $pdo->query("SELECT o.id, o.customer, o.description, o.date_received, o.date_collected, c.fullname, c.address 
                                                 FROM `order` o 
                                                 LEFT JOIN customer c ON o.customer = c.id 
                                                 ORDER BY o.id DESC");
                            
                            $counter = 1; // For serial number
                            
                            while ($data = $ddaa->fetch(PDO::FETCH_ASSOC)) {
                                $day = date('D', strtotime($data['date_collected']));
                                
                                // Get customer name and address with fallback values
                                $customer_name = $data['fullname'] ?? "Unknown Customer";
                                $customer_address = $data['address'] ?? "No Address";
                                
                                // Format dates for better display
                                $date_received_formatted = !empty($data['date_received']) ? date('d-m-Y', strtotime($data['date_received'])) : 'Not set';
                                $date_collected_formatted = !empty($data['date_collected']) ? date('d-m-Y', strtotime($data['date_collected'])) : 'Not set';
                                
                                echo "<tr>
                                    <td style='padding: 4px 8px; text-align: center;'><input type='checkbox' name='selected_orders[]' value='$data[id]' class='order-checkbox' style='width: 14px; height: 14px; margin: 0;'></td>
                                    <td>$counter</td>
                                    <td>$customer_name</td>
                                    <td>$customer_address</td>
                                    <td>$date_received_formatted</td>
                                    <td>$date_collected_formatted</td>
                                    <td>
                                        <a href='addpayment.php?id=$data[id]' class='btn btn-success btn-xs'>Add Payment</a>
                                        <a href='printinvoice.php?id=$data[id]' class='btn btn-warning btn-xs'>Receipt</a>
                                        <a href='orderedit.php?id=$data[id]' class='btn btn-info btn-xs' title='Update'>
                                            <i class='fa fa-edit'></i>
                                        </a>
                                        <a href='orderlist.php?id=$data[id]' class='btn btn-danger btn-xs' title='Delete'>
                                            <i class='fa fa-trash text-white'></i>
                                        </a>
                                    </td>
                                </tr>";
                                
                                $counter++;
                            }
                            ?>
                        </tbody>
                     </table>
                </div><!-- table-responsive -->
            </div>
        </div>
    </form>
</div><!-- contentpanel -->
</div>
<!-- /#page-wrapper -->

<?php
include('footer.php');
?>
<script src="js/jquery.datatables.min.js"></script>
<script src="js/select2.min.js"></script>

<script>
    jQuery(document).ready(function() {
        "use strict";
        jQuery('#table1').dataTable();
        jQuery('#table2').dataTable({
            "sPaginationType": "full_numbers"
        });
        // Select2
        jQuery('select').select2({
            minimumResultsForSearch: -1
        });
        jQuery('select').removeClass('form-control');
        
        // Delete row in a table
        jQuery('.delete-row').click(function() {
            var c = confirm("Continue delete?");
            if (c)
                jQuery(this).closest('tr').fadeOut(function() {
                    jQuery(this).remove();
                });
            return false;
        });
        
        // Select All checkbox functionality
        $('#selectAllCheckbox').click(function() {
            $('.order-checkbox').prop('checked', this.checked);
        });
        
        // Individual checkbox click
        $('.order-checkbox').click(function() {
            if($('.order-checkbox:checked').length == $('.order-checkbox').length) {
                $('#selectAllCheckbox').prop('checked', true);
            } else {
                $('#selectAllCheckbox').prop('checked', false);
            }
        });
    });
    
    function selectAll() {
        $('.order-checkbox').prop('checked', true);
        $('#selectAllCheckbox').prop('checked', true);
    }
    
    function deselectAll() {
        $('.order-checkbox').prop('checked', false);
        $('#selectAllCheckbox').prop('checked', false);
    }
</script>
</body>
</html>