<?php
require_once('function.php');
dbconnect();
session_start();

if (!is_user()) {
    redirect('index.php');
}

// Get user ID FIRST - before including header.php
$user = $_SESSION['username'];
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$user]);
$usid = $stmt->fetch(PDO::FETCH_ASSOC);
$uid = $usid['id'] ?? null;

// Set user variables that might be needed in header.php
$_SESSION['user_id'] = $uid;
$_SESSION['username'] = $user;

// Handle deletion with prepared statement (security fix)
if(isset($_GET["id"]) && is_numeric($_GET["id"])){
    $customerid = (int)$_GET["id"];
    $stmt = $pdo->prepare("DELETE FROM customer WHERE id = ?");
    $stmt->execute([$customerid]);
    
    // Redirect to prevent refresh issues
    header("Location: customerview.php?deleted=1");
    exit;
}

// NOW include header.php after all user variables are set
include ('header.php');
?>
<link href="css/style.default.css" rel="stylesheet">
<link href="css/jquery.datatables.css" rel="stylesheet">
<link href="../bower_components/datatables-plugins/integration/bootstrap/3/dataTables.bootstrap.css" rel="stylesheet">
<link href="../bower_components/datatables-responsive/css/dataTables.responsive.css" rel="stylesheet">

<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">All CUSTOMERS</h1>
        </div>
    </div>
    
    <div class="contentpanel">
        <div class="panel panel-default">
            <div class="panel-body">
                <div class="clearfix mb30">
                    <div class="pull-right">
                        <!-- Export Dropdown Button -->
                        <div class="btn-group">
                            <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown">
                                <i class="glyphicon glyphicon-download-alt"></i> Export <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu" role="menu">
                                <li><a href="export_excel.php"><i class="glyphicon glyphicon-file"></i> Export to Excel</a></li>
                                <li><a href="export_csv.php"><i class="glyphicon glyphicon-save-file"></i> Export to CSV</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped" id="customerTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Full Name</th>
                                <th>Size No</th>
                                <th>Phone Number</th>
                                <th>Sex</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // OPTIMIZATION 1: Use prepared statements
                            // OPTIMIZATION 2: Fetch all data at once instead of row by row
                            // OPTIMIZATION 3: Reduce database queries
                            
                            $stmt = $pdo->query("SELECT id, address, fullname, phonenumber, sex FROM customer ORDER BY id");
                            $allCustomers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            // OPTIMIZATION 4: Get all measurement statuses in one query
                            $customerIds = array_column($allCustomers, 'id');
                            $measurementStatus = [];
                            
                            if (!empty($customerIds)) {
                                $placeholders = implode(',', array_fill(0, count($customerIds), '?'));
                                $stmt = $pdo->prepare("SELECT DISTINCT customer_id FROM measurement WHERE customer_id IN ($placeholders)");
                                $stmt->execute($customerIds);
                                $measuredCustomers = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
                                
                                // Create a lookup array
                                foreach ($measuredCustomers as $customerId) {
                                    $measurementStatus[$customerId] = true;
                                }
                            }
                            
                            // Generate table rows
                            foreach ($allCustomers as $data) {
                                $sex = ($data['sex'] == 0) ? 'Male' : 'Female';
                                $customerId = (int)$data['id'];
                                
                                // Check measurement status from pre-fetched data
                                if (isset($measurementStatus[$customerId])) {
                                    $measure = '<a href="editmeasurement.php?id='.$customerId.'" class="btn btn-warning btn-xs">Edit Meas</a>';
                                } else {
                                    $measure = '<a href="addmeasurement.php?id='.$customerId.'" class="btn btn-warning btn-xs">Add Meas</a>';
                                }
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($data['id']); ?></td>
                                    <td><a href='customeredit.php?id=<?php echo $customerId; ?>'><?php echo htmlspecialchars($data['fullname']); ?></a></td>
                                    <td><?php echo htmlspecialchars($data['address']); ?></td>
                                    <td><?php echo htmlspecialchars($data['phonenumber']); ?></td>
                                    <td><?php echo $sex; ?></td>
                                    <td style="text-align:center">
                                        <a href='orderadd.php?id=<?php echo $customerId; ?>' class='btn btn-success btn-xs'>New Order</a>
                                        <?php echo $measure; ?>
                                        <a href='sms.php?id=<?php echo $customerId; ?>' class='btn btn-info btn-xs'>SMS</a>
                                        <a href='email.php?id=<?php echo $customerId; ?>' class='btn btn-info btn-xs'>EMAIL</a>
                                        <a href='customerview.php?id=<?php echo $customerId; ?>' onclick="return confirm('Are you sure you want to delete customer <?php echo htmlspecialchars($data['fullname']); ?>?');">
                                            <button type='button' class='btn btn-danger btn-xs'>DELETE</button>
                                        </a>
                                    </td>
                                </tr>
                                <?php
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include ('footer.php'); ?>

<script src="js/jquery.datatables.min.js"></script>
<script src="js/select2.min.js"></script>
<script>
jQuery(document).ready(function() {
    "use strict";
    
    // Initialize DataTable with server-side processing option for large datasets
    jQuery('#customerTable').dataTable({
        "paging": true,
        "pageLength": 10,
        "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        "processing": true,
        "deferRender": true, // Improves performance for large datasets
        "order": [[0, "desc"]]
    });
    
    // Select2 initialization
    jQuery('select').select2({
        minimumResultsForSearch: -1
    }).removeClass('form-control');
});
</script>
</body>
</html>