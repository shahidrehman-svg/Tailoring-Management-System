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

if (isset($_GET["id"])) {
    $id = $_GET["id"];
    $pdo->exec("DELETE FROM `order` WHERE id='$id'");

    echo "<div class='alert alert-success alert-dismissable'>
    <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>	
    Order Deleted Successfully!
    </div>";
}

// Get filter parameters
$client_filter = $_GET['client'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Get unique customers for dropdown - FIXED QUERY
$customers_query = "SELECT DISTINCT c.id, c.fullname 
                   FROM customer c 
                   WHERE EXISTS (SELECT 1 FROM `order` o WHERE o.customer = c.id)
                   ORDER BY c.fullname";
$customers = $pdo->query($customers_query);
?>
<link href="css/style.default.css" rel="stylesheet">
<link href="css/jquery.datatables.css" rel="stylesheet">
<!-- DataTables CSS -->
<link href="../bower_components/datatables-plugins/integration/bootstrap/3/dataTables.bootstrap.css" rel="stylesheet">
<!-- DataTables Responsive CSS -->
<link href="../bower_components/datatables-responsive/css/dataTables.responsive.css" rel="stylesheet">

<style>
    @media print {
        .no-print { display: none !important; }
        .print-only { display: block !important; }
        body { font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 5px; }
        .print-header { text-align: center; margin-bottom: 20px; }
        .print-summary { margin-top: 20px; }
        .print-footer { margin-top: 30px; text-align: center; font-size: 10px; }
        .summary-table th, .summary-table td {
            padding: 4px;
            font-size: 11px;
        }
        .total-row {
            font-weight: bold;
            background-color: #f5f5f5 !important;
        }
        .summary-cards-table {
            width: 100%;
            margin-bottom: 20px;
            border: 2px solid #333;
        }
        .summary-cards-table td {
            text-align: center;
            padding: 10px;
            border: 1px solid #ddd;
        }
        .summary-cards-table .card-title {
            font-weight: bold;
            font-size: 14px;
        }
        .summary-cards-table .card-value {
            font-size: 18px;
            font-weight: bold;
            margin-top: 5px;
        }
    }
    .print-only { display: none; }
    .today-order {
        background-color: #d9edf7 !important;
        font-weight: bold;
    }


/* Ensure DataTables controls remain unaffected */
.dataTables_wrapper .dataTables_length select {
    display: inline-block !important;
    width:50px !important;
    height: 40px !important;
}

table.dataTable td.dataTables_empty {
    text-align: center !important;
    padding: 40px 20px;
}

</style>

<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">View Orders 
            </h1>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="panel panel-default no-print">
        <div class="panel-heading clearfix">
            <h4 class="panel-title pull-left">Filter Orders</h4>
            <div class="pull-right">
                <button class="btn btn-warning" onclick="printSummary()"><i class="fa fa-print"></i> Print Summary</button>
            </div>
        </div>
        <div class="panel-body">
            <form method="GET" action="" class="form-inline">
                <div class="form-group">
                    <label>Customer Name:</label>
                    <select name="client" class="form-control" style="width: 200px; margin-right: 15px;">
                        <option value="">All Customers</option>
                        <?php 
                        if ($customers && $customers->rowCount() > 0):
                            while ($customer = $customers->fetch(PDO::FETCH_ASSOC)): 
                        ?>
                            <option value="<?php echo $customer['id']; ?>" <?php echo ($client_filter == $customer['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($customer['fullname']); ?>
                            </option>
                        <?php 
                            endwhile;
                        else:
                        ?>
                            <option value="">No customers found</option>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>From Date:</label>
                    <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>" style="margin-right: 15px;">
                </div>
                
                <div class="form-group">
                    <label>To Date:</label>
                    <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>" style="margin-right: 15px;">
                </div>
                
                <button type="submit" class="btn btn-primary"><i class="fa fa-filter"></i> Filter</button>
                <a href="?" class="btn btn-default">Clear</a>
               
               
            </form>
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-body">
            <div class="clearfix mb30"></div>
            <div class="table-responsive">
                <table class="table table-striped" id="table2">
                    <thead>
                        <tr>
                            <th>Gen.No</th>
                            <th>Customer Name</th>
                            <th>Sr.No</th>
                            <th>Date Collect</th>
                            <th>Date Received</th>
                            <th>Amount</th>
                            <th>Paid</th>
                            <th>Balance</th>
                            <th class="no-print">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Build query with filters
                        $query = "SELECT o.id, o.customer, o.description, o.date_received, o.amount, o.paid, o.date_collected, c.fullname, c.address 
                                 FROM `order` o 
                                 LEFT JOIN customer c ON o.customer = c.id";
                        
                        $where_conditions = [];
                        $params = [];
                        
                        if ($client_filter) {
                            $where_conditions[] = "o.customer = ?";
                            $params[] = $client_filter;
                        }
                        
                        if ($date_from && $date_to) {
                            $where_conditions[] = "DATE(o.date_received) BETWEEN ? AND ?";
                            $params[] = $date_from;
                            $params[] = $date_to;
                        } elseif ($date_from) {
                            $where_conditions[] = "DATE(o.date_received) >= ?";
                            $params[] = $date_from;
                        } elseif ($date_to) {
                            $where_conditions[] = "DATE(o.date_received) <= ?";
                            $params[] = $date_to;
                        }
                        
                        if (!empty($where_conditions)) {
                            $query .= " WHERE " . implode(" AND ", $where_conditions);
                        }
                        
                        $query .= " ORDER BY o.date_received DESC";
                        
                        // Prepare and execute the query
                        $stmt = $pdo->prepare($query);
                        $stmt->execute($params);
                        
                        $counter = 1;
                        $total_amount = 0;
                        $total_paid = 0;
                        $total_balance = 0;
                        $customer_totals = [];
                        $today_orders_count = 0;
                        $today_orders_amount = 0;
                        $current_date = date('Y-m-d');
                        
                        if ($stmt->rowCount() > 0):
                            while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                // Get customer name and address with fallback values
                                $customer_name = $data['fullname'] ?? "Unknown Customer";
                                $customer_address = $data['address'] ?? "No Address";
                                
                                // Calculate balance
                                $balance = $data['amount'] - $data['paid'];
                                
                                // Add to totals
                                $total_amount += $data['amount'];
                                $total_paid += $data['paid'];
                                $total_balance += $balance;
                                
                                // Check if order is from today
                                $order_date = date('Y-m-d', strtotime($data['date_received']));
                                $is_today = ($order_date == $current_date);
                                $row_class = $is_today ? 'class="today-order"' : '';
                                
                                if ($is_today) {
                                    $today_orders_count++;
                                    $today_orders_amount += $data['amount'];
                                }
                                
                                // Track customer-wise totals
                                if (!isset($customer_totals[$customer_name])) {
                                    $customer_totals[$customer_name] = [
                                        'amount' => 0,
                                        'paid' => 0,
                                        'balance' => 0,
                                        'count' => 0
                                    ];
                                }
                                $customer_totals[$customer_name]['amount'] += $data['amount'];
                                $customer_totals[$customer_name]['paid'] += $data['paid'];
                                $customer_totals[$customer_name]['balance'] += $balance;
                                $customer_totals[$customer_name]['count']++;
                                
                                // Color code the balance amount
                                $balance_display = '';
                                if ($balance > 0) {
                                    $balance_display = '<span style="color:red; font-weight:bold;">' . number_format($balance, 2) . '</span>';
                                } else if ($balance < 0) {
                                    $balance_display = '<span style="color:orange; font-weight:bold;">' . number_format($balance, 2) . '</span>';
                                } else {
                                    $balance_display = '<span style="color:green; font-weight:bold;">' . number_format($balance, 2) . '</span>';
                                }
                                
                                // Format the date like your income page
                                $date_received_display = date('d-m-Y', strtotime($data['date_received']));
                                
                                echo "<tr $row_class>
                                    <td>$counter</td>
                                    <td>$customer_name</td>
                                    <td>$customer_address</td>
                                    <td>$data[date_collected]</td>
                                    <td>$date_received_display</td>
                                    <td>Rs " . number_format($data['amount'], 2) . "</td>
                                    <td>Rs " . number_format($data['paid'], 2) . "</td>
                                    <td>$balance_display</td>
                                    <td class='no-print'>
                                        <a href='orderedit.php?id=$data[id]' class='btn btn-info btn-xs'>Update</a>
                                    </td>
                                </tr>";
                                
                                $counter++;
                            }
                        else:
                            // echo "<tr><td colspan='9' class='text-center'>No orders found</td></tr>";
                        endif;
                        ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($stmt->rowCount() > 0): ?>
            <!-- Summary Cards -->
            <div class="row no-print" style="margin-bottom: 20px;">
                
                <!-- Total Orders -->
                <div class="col-md-3">
                    <div class="panel panel-warning">
                        <div class="panel-heading">
                            <h3 class="panel-title">Total Orders</h3>
                        </div>
                        <div class="panel-body text-center">
                            <h2><?php echo ($counter - 1); ?></h2>
                        </div>
                    </div>
                </div>
                
                <!-- Total Amount -->
                <div class="col-md-3">
                    <div class="panel panel-primary">
                        <div class="panel-heading">
                            <h3 class="panel-title">Total Amount</h3>
                        </div>
                        <div class="panel-body text-center">
                            <h2>Rs <?php echo number_format($total_amount, 2); ?></h2>
                        </div>
                    </div>
                </div>
                
                <!-- Total Paid -->
                <div class="col-md-3">
                    <div class="panel panel-success">
                        <div class="panel-heading">
                            <h3 class="panel-title">Total Paid</h3>
                        </div>
                        <div class="panel-body text-center">
                            <h2>Rs <?php echo number_format($total_paid, 2); ?></h2>
                        </div>
                    </div>
                </div>
           
           <div class="col-md-3">
                    <div class="panel panel-<?php echo $total_balance > 0 ? 'danger' : ($total_balance < 0 ? 'warning' : 'success'); ?>">
                        <div class="panel-heading">
                            <h3 class="panel-title">Total Remaining</h3>
                        </div>
                        <div class="panel-body text-center">
                            <h2>Rs <?php echo number_format($total_balance, 2); ?></h2>
                            <?php if ($total_balance > 0): ?>
                                <h4 class="text-danger">(Amount Due)</h4>
                            <?php elseif ($total_balance < 0): ?>
                                <h4 class="text-warning">(Overpayment)</h4>
                            <?php else: ?>
                                <h4 class="text-success">(Fully Paid)</h4>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
           
            </div>
            
            <!-- Print Summary Section -->
            <div id="printSummary" class="print-only" style="display: none;">
                <div class="print-summary">
                    <div class="print-header">
                        <h2>Orders Summary Report</h2>
                        <?php if ($date_from || $date_to): ?>
                            <p>Period: <?php echo $date_from ? date('d-m-Y', strtotime($date_from)) : 'Start'; ?> 
                            to <?php echo $date_to ? date('d-m-Y', strtotime($date_to)) : 'End'; ?></p>
                        <?php endif; ?>
                        <?php if ($client_filter): ?>
                            <?php 
                            $cust_name = $pdo->query("SELECT fullname FROM customer WHERE id='$client_filter'")->fetchColumn();
                            ?>
                            <p>Customer: <?php echo $cust_name ?: 'Selected Customer'; ?></p>
                        <?php endif; ?>
                        <hr>
                    </div>
                    
                    <!-- Summary Cards in Table Format for Print -->
                    <table class="summary-cards-table">
                        <tr>
                            <!-- <td>
                                <div class="card-title">Today's Orders</div>
                                <div class="card-value"><?php echo $today_orders_count; ?></div>
                                <div>Amount: Rs <?php echo number_format($today_orders_amount, 2); ?></div>
                            </td> -->
                            <td style="padding: 15px 20px;">
                                <div class="card-title">Total Orders</div>
                                <div class="card-value"><?php echo ($counter - 1); ?></div>
                            </td>
                            <td style="padding: 15px 20px;">
                                <div class="card-title">Total Amount</div>
                                <div class="card-value">Rs <?php echo number_format($total_amount, 2); ?></div>
                            </td style="padding: 15px 20px;">
                            <td>
                                <div class="card-title">Total Paid</div>
                                <div class="card-value">Rs <?php echo number_format($total_paid, 2); ?></div>
                            </td>
                            <td  colspan="4" style="padding: 15px 20px; <?php 
                                echo $total_balance > 0 ? 'background-color: #f2dede; color: #a94442;' : 
                                    ($total_balance < 0 ? 'background-color: #fcf8e3; color: #8a6d3b;' : 
                                    'background-color: #dff0d8; color: #3c763d;'); ?>">
                                <div class="card-title">Total Remaining</div>
                                <div class="card-value" style="font-size: 24px;">Rs <?php echo number_format($total_balance, 2); ?></div>
                                <div style="font-size: 14px;">
                                    <?php if ($total_balance > 0): ?>
                                        (Amount Due)
                                    <?php elseif ($total_balance < 0): ?>
                                        (Overpayment)
                                    <?php else: ?>
                                        (Fully Paid)
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            
                        </tr>
                    </table>
                    
                    <h3>Customer-wise Summary</h3>
                    <table border="1" class="summary-table" style="width: 100%; margin-top: 20px;">
                        <thead>
                            <tr>
                                <th>Customer Name</th>
                                <th>Orders</th>
                                <th>Total Amount</th>
                                <th>Total Paid</th>
                                <th>Total Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $grand_total_orders = 0;
                            $grand_total_amount = 0;
                            $grand_total_paid = 0;
                            $grand_total_balance = 0;
                            
                            foreach ($customer_totals as $customer_name => $totals): 
                                $grand_total_orders += $totals['count'];
                                $grand_total_amount += $totals['amount'];
                                $grand_total_paid += $totals['paid'];
                                $grand_total_balance += $totals['balance'];
                                
                                // Color code balance
                                $balance_class = '';
                                if ($totals['balance'] > 0) {
                                    $balance_class = 'style="color:red; font-weight:bold;"';
                                } elseif ($totals['balance'] < 0) {
                                    $balance_class = 'style="color:orange; font-weight:bold;"';
                                } else {
                                    $balance_class = 'style="color:green; font-weight:bold;"';
                                }
                            ?>
                            <tr>
                                <td><?php echo $customer_name; ?></td>
                                <td><?php echo $totals['count']; ?></td>
                                <td>Rs <?php echo number_format($totals['amount'], 2); ?></td>
                                <td>Rs <?php echo number_format($totals['paid'], 2); ?></td>
                                <td <?php echo $balance_class; ?>>Rs <?php echo number_format($totals['balance'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <!-- Grand Total Row -->
                            <tr class="total-row">
                                <td><strong>GRAND TOTAL</strong></td>
                                <td><strong><?php echo $grand_total_orders; ?></strong></td>
                                <td><strong>Rs <?php echo number_format($grand_total_amount, 2); ?></strong></td>
                                <td><strong>Rs <?php echo number_format($grand_total_paid, 2); ?></strong></td>
                                <td <?php 
                                    if ($grand_total_balance > 0) {
                                        echo 'style="color:red; font-weight:bold;"';
                                    } elseif ($grand_total_balance < 0) {
                                        echo 'style="color:orange; font-weight:bold;"';
                                    } else {
                                        echo 'style="color:green; font-weight:bold;"';
                                    }
                                ?>><strong>Rs <?php echo number_format($grand_total_balance, 2); ?></strong></td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <!-- Additional Summary Statistics -->
                    <div style="margin-top: 20px;">
                        <h4>Summary Statistics</h4>
                        <table border="1" style="width: 100%; margin-top: 10px;">
                            <tr>
                                <td><strong>Total Customers:</strong></td>
                                <td><?php echo count($customer_totals); ?></td>
                                <td><strong>Today's Orders:</strong></td>
                                <td><?php echo $today_orders_count; ?></td>
                                <td><strong>Average per Order:</strong></td>
                                <td>Rs <?php echo $grand_total_orders > 0 ? number_format($grand_total_amount / $grand_total_orders, 2) : '0.00'; ?></td>
                            </tr>
                            <tr>
                                <td><strong>Payment Percentage:</strong></td>
                                <td colspan="2"><?php echo $grand_total_amount > 0 ? number_format(($grand_total_paid / $grand_total_amount) * 100, 2) : '0.00'; ?>%</td>
                                <td><strong>Balance Percentage:</strong></td>
                                <td colspan="2"><?php echo $grand_total_amount > 0 ? number_format(($grand_total_balance / $grand_total_amount) * 100, 2) : '0.00'; ?>%</td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="print-footer">
                        <p>Generated on: <?php echo date('d-m-Y h:i A'); ?></p>
                        <p>Tailoring Management System</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
include('footer.php');
?>
<script src="js/jquery.datatables.min.js"></script>
<script src="js/select2.min.js"></script>

<script>
jQuery(document).ready(function() {
    jQuery('#table2').DataTable({
        "pageLength": 10,
        "order": [[4, "desc"]],

        "language": {
            "zeroRecords": `
                <div style="padding:20px;">
                    <i class="fa fa-search" style="font-size:40px; color:#dc3545; margin-bottom:10px;"></i>
                    <div style="font-size:18px; font-weight:600;">No matching records found</div>
                    <div style="color:#777; margin-top:5px;">Try adjusting your filters</div>
                </div>
            `,
            "emptyTable": `
                <div style="padding:20px;">
                    <i class="fa fa-inbox" style="font-size:40px; color:#17a2b8; margin-bottom:10px;"></i>
                    <div style="font-size:18px; font-weight:600;">No records found</div>
                    <div style="color:#777; margin-top:5px;">No data available in this table</div>
                </div>
            `
        }
    });

    jQuery('select[name="client"]').select2({
        placeholder: "Select Customer",
        allowClear: true
    });

    jQuery('select[name="status"]').select2();
});

function printSummary() {
    var content = document.getElementById("printSummary").innerHTML;

    var printWindow = window.open("", "_blank", "width=900,height=650");
    printWindow.document.write(`
        <html>
        <head>
            <title>Print Summary</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    font-size: 12px;
                    position: relative;
                    margin: 0;
                    padding: 20px;
                }

                /* WATERMARK */
                #watermark {
                    position: fixed;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    opacity: 0.08;
                    z-index: 0;
                    pointer-events: none;
                    width: 300px;
                    height: auto;
                }

                /* Content should be above watermark */
                .print-summary {
                    position: relative;
                    z-index: 1;
                }
            </style>
        </head>
        <body>
            <img src="img/logo_pic.png" id="watermark" />
            <div class="print-summary">
                ${content}
            </div>
        </body>
        </html>
    `);

    printWindow.document.close();
    printWindow.focus();

    setTimeout(() => {
        printWindow.print();
        printWindow.close();
    }, 500);
}

</script>
</body>
</html>