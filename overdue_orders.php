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
<link href="../bower_components/datatables-plugins/integration/bootstrap/3/dataTables.bootstrap.css" rel="stylesheet">
<link href="../bower_components/datatables-responsive/css/dataTables.responsive.css" rel="stylesheet">

<style>
    /* Blinking animation for the status column only */
    @keyframes blink {
        0% { background-color: #ffcccc; color: #000; }
        50% { background-color: #ff6600; color: #fff; }
        100% { background-color: #ffcccc; color: #000; }
    }
    
    .blinking-text {
        animation: blink 1s infinite;
        padding: 5px 10px;
        border-radius: 4px;
        font-weight: bold;
        text-align: center;
        display: inline-block;
        width: 100%;
    }
    
    /* Different colors for different urgency levels */
    .urgent-high {
        background-color: #ff4444;
        color: white;
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: bold;
    }
    
    .urgent-medium {
        background-color: #ff9933;
        color: white;
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: bold;
    }
    
    .urgent-low {
        background-color: #ffcc00;
        color: #333;
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: bold;
    }
    
    .info-box {
        background-color: #f8f9fa;
        border-left: 4px solid #ff6600;
        padding: 10px 15px;
        margin-bottom: 20px;
        border-radius: 4px;
    }
</style>

<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">
                Upcoming Collection Deadlines 
                <small>(To be collected in next 2 days)</small>
                <a href="orderlist.php" class="btn btn-primary pull-right">
                    <i class="fa fa-arrow-left"></i> Back to All Orders
                </a>
            </h1>
        </div>
    </div>

    <div class="info-box">
        <i class="fa fa-clock-o" style="color: #ff6600;"></i> 
        <strong>Upcoming Deadlines:</strong> 
        Orders listed below need to be collected within the next <strong>2 days</strong> (including today). 
        The <strong style="color: #ff6600;">"Status" column</strong> will blink to highlight urgent orders.
    </div>

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
                </div>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-striped" id="upcomingTable">
                        <thead>
                            <tr>
                                <th width="20px"><input type="checkbox" id="selectAllCheckbox" style="width: 14px; height: 14px; margin: 0;"></th>
                                <th>Sr.No</th>
                                <th>Customer</th>
                                <th>Address</th>
                                <th>Date Received</th>
                                <th>Date Collected</th>
                                <th>Days Remaining</th>
                                <th>Urgency</th>
                                <th>Amount</th>
                                <th>Paid</th>
                                <th>Balance</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Query to get orders with date_collected in the next 2 days (including today)
                            $query = "SELECT o.id, o.customer, o.description, o.date_received, o.amount, o.paid, o.date_collected, c.fullname, c.address 
                                     FROM `order` o 
                                     LEFT JOIN customer c ON o.customer = c.id 
                                     WHERE o.date_collected IS NOT NULL 
                                     AND o.date_collected BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 2 DAY)
                                     ORDER BY o.date_collected ASC";
                            
                            $ddaa = $pdo->query($query);
                            $counter = 1;
                            $today = new DateTime();
                            $today->setTime(0, 0, 0); // Reset time to midnight for accurate comparison
                            
                            if ($ddaa->rowCount() == 0) {
                                echo "<tr><td colspan='13' style='text-align: center; padding: 50px;'>
                                        <i class='fa fa-check-circle' style='font-size: 48px; color: #5cb85c;'></i>
                                        <h3>No Upcoming Deadlines!</h3>
                                        <p>All orders have collection dates beyond 2 days from now.</p>
                                       </td></tr>";
                            } else {
                                while ($data = $ddaa->fetch(PDO::FETCH_ASSOC)) {
                                    // Get customer name and address with fallback values
                                    $customer_name = $data['fullname'] ?? "Unknown Customer";
                                    $customer_address = $data['address'] ?? "No Address";
                                    
                                    // Format dates for better display
                                    $date_received_formatted = !empty($data['date_received']) ? date('d-m-Y', strtotime($data['date_received'])) : 'Not set';
                                    $date_collected_formatted = !empty($data['date_collected']) ? date('d-m-Y', strtotime($data['date_collected'])) : 'Not set';
                                    
                                    // Calculate balance
                                    $balance = $data['amount'] - $data['paid'];
                                    
                                    // Calculate days remaining until collection
                                    $collectedDate = new DateTime($data['date_collected']);
                                    $collectedDate->setTime(0, 0, 0);
                                    $interval = $today->diff($collectedDate);
                                    $daysRemaining = $interval->days;
                                    
                                    // If collection date is today, days remaining = 0
                                    if ($collectedDate == $today) {
                                        $daysRemaining = 0;
                                    }
                                    
                                    // Determine urgency and status message
                                    $urgencyClass = '';
                                    $statusMessage = '';
                                    $blinkStatus = false;
                                    
                                    if ($daysRemaining == 0) {
                                        // Today
                                        $urgencyClass = 'urgent-high';
                                        $statusMessage = '⚠️ DUE TODAY!';
                                        $blinkStatus = true;
                                    } elseif ($daysRemaining == 1) {
                                        // Tomorrow
                                        $urgencyClass = 'urgent-medium';
                                        $statusMessage = '⚠️ Due Tomorrow';
                                        $blinkStatus = true;
                                    } elseif ($daysRemaining == 2) {
                                        // Day after tomorrow
                                        $urgencyClass = 'urgent-low';
                                        $statusMessage = '⚠️ Due in 2 Days';
                                        $blinkStatus = true; // Still blink but with different intensity
                                    }
                                    
                                    // For today's orders, also make the whole row stand out
                                    $rowStyle = ($daysRemaining == 0) ? 'style="background-color: #fff3f3;"' : '';
                                    
                                    echo "<tr $rowStyle>
                                        <td style='padding: 4px 8px; text-align: center;'>
                                            <input type='checkbox' name='selected_orders[]' value='$data[id]' class='order-checkbox' style='width: 14px; height: 14px; margin: 0;'>
                                         </td>
                                         <td>$counter</td>
                                         <td>$customer_name</td>
                                         <td>$customer_address</td>
                                         <td>$date_received_formatted</td>
                                         <td><strong>$date_collected_formatted</strong></td>
                                         <td>
                                            <span class='$urgencyClass'>
                                                " . ($daysRemaining == 0 ? 'Today!' : "$daysRemaining day(s)") . "
                                            </span>
                                         </td>
                                         <td>
                                            " . ($daysRemaining == 0 ? '<span class="label label-danger">Very Urgent</span>' : 
                                                ($daysRemaining == 1 ? '<span class="label label-warning">Urgent</span>' : 
                                                '<span class="label label-info">Upcoming</span>')) . "
                                         </td>
                                         <td>Rs {$data['amount']}</td>
                                         <td>Rs {$data['paid']}</td>
                                         <td>Rs $balance</td>
                                         <td>
                                            <div class='blinking-text'>
                                                <i class='fa fa-bell'></i> $statusMessage
                                            </div>
                                         </td>
                                         <td>
                                            <a href='addpayment.php?id={$data['id']}' class='btn btn-success btn-xs'>Add Payment</a>
                                            <a href='printinvoice.php?id={$data['id']}' class='btn btn-warning btn-xs'>Receipt</a>
                                            <a href='orderedit.php?id={$data['id']}' class='btn btn-info btn-xs' title='Update'>
                                                <i class='fa fa-edit'></i>
                                            </a>
                                            <a href='upcoming_orders.php?id={$data['id']}' class='btn btn-danger btn-xs' title='Delete' onclick='return confirm(\"Are you sure?\")'>
                                                <i class='fa fa-trash text-white'></i>
                                            </a>
                                         </td>
                                     </tr>";
                                    $counter++;
                                }
                            }
                            ?>
                        </tbody>
                     </table>
                </div>
            </div>
        </div>
    </form>
</div>

<?php
include('footer.php');
?>
<script src="js/jquery.datatables.min.js"></script>
<script src="js/select2.min.js"></script>

<script>
    jQuery(document).ready(function() {
        "use strict";
        
        // Initialize DataTable
        $('#upcomingTable').dataTable({
            "sPaginationType": "full_numbers",
            "order": [[6, "asc"]] // Sort by days remaining (ascending - closest deadlines first)
        });
        
        // Select2
        jQuery('select').select2({
            minimumResultsForSearch: -1
        });
        jQuery('select').removeClass('form-control');
        
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