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

// Get filter parameters
$client_filter = $_GET['client'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query with filters
$query = "SELECT * FROM overall_income WHERE user_id = '$uid'";
$params = [];

if ($client_filter) {
    $query .= " AND client_name = ?";
    $params[] = $client_filter;
}

if ($date_from && $date_to) {
    $query .= " AND DATE(created_at) BETWEEN ? AND ?";
    $params[] = $date_from;
    $params[] = $date_to;
} elseif ($date_from) {
    $query .= " AND DATE(created_at) >= ?";
    $params[] = $date_from;
} elseif ($date_to) {
    $query .= " AND DATE(created_at) <= ?";
    $params[] = $date_to;
}

$query .= " ORDER BY created_at DESC";

// Get unique clients for dropdown
$clients = $pdo->query("SELECT DISTINCT client_name FROM overall_income WHERE user_id = '$uid' ORDER BY client_name");

include ('header.php');
?>

<link href="css/style.default.css" rel="stylesheet">
<link href="css/jquery.datatables.css" rel="stylesheet">
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
    }
    .print-only { display: none; }
    .btn-print-client {
        background-color: #5bc0de;
        border-color: #46b8da;
        color: white;
        padding: 1px 5px;
        font-size: 12px;
        margin-left: 2px;
    }
    .btn-print-client:hover {
        background-color: #31b0d5;
        border-color: #269abc;
    }

    table.dataTable td.dataTables_empty {
    text-align: center !important;
    vertical-align: middle !important;
    padding: 40px 20px;
}
</style>

<div class="pageheader">
    <h2><i class="fa fa-money"></i> Overall Income Records</h2>
    <div style="padding-top:6px !important;" class="btn-group mr-2 no-print">
        <a href="add_overall_income.php" class="btn btn-success"><i class="fa fa-plus"></i> Add New Record</a>
        <button class="btn btn-warning" onclick="printSummary()"><i class="fa fa-print"></i> Print Summary</button>
    </div>
</div>

<div class="contentpanel">
    <div class="panel panel-default no-print">
        <div class="panel-heading">
            <h4 class="panel-title">Filter Records</h4>
        </div>
        <div class="panel-body">
            <form method="GET" action="" class="form-inline">
                <div class="form-group">
                    <label>Customer Name:</label>
                    <select name="client" id="clientSelect" class="form-control select2-client" style="width: 200px; margin-right: 15px;">
                        <option value="">All Customers</option>
                        <?php while ($client = $clients->fetch(PDO::FETCH_ASSOC)): ?>
                            <option value="<?php echo htmlspecialchars($client['client_name']); ?>" <?php echo ($client_filter == $client['client_name']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($client['client_name']); ?>
                            </option>
                        <?php endwhile; ?>
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
                <a href="overall_income_list.php" class="btn btn-default">Clear</a>
            </form>
        </div>
    </div>
    
    <div class="panel panel-default">
        <div class="panel-body">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            
            <div class="table-responsive">
                <table class="table table-striped" id="incomeTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Name</th>
                            <th>Stitching</th>
                            <th>Qty 1</th>
                            <th>Clothes</th>
                            <th>Qty 2</th>
                            <th>Amount</th>
                            <th>Paid</th>
                            <th>Balance</th>
                            <th class="no-print">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->prepare($query);
                        $stmt->execute($params);
                        
                        $serial = 1;
                        $total_amount = 0;
                        $total_paid = 0;
                        $total_balance = 0;
                        $total_qty1 = 0;
                        $total_qty2 = 0;
                        $client_totals = [];
                        $displayed_clients = []; 
                        
                        while ($income = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $total_amount += $income['amount'];
                            $total_paid += $income['paid'];
                            $total_balance += $income['balance'];
                            $total_qty1 += $income['quantity_1'];
                            $total_qty2 += $income['quantity_2'];
                            
                            // Track client-wise totals
                            $client_name = $income['client_name'];
                            if (!isset($client_totals[$client_name])) {
                                $client_totals[$client_name] = [
                                    'amount' => 0,
                                    'paid' => 0,
                                    'balance' => 0,
                                    'count' => 0,
                                    'qty1' => 0,
                                    'qty2' => 0,
                                    'stitching_types' => [],
                                    'clothes_types' => []
                                ];
                                $displayed_clients[$client_name] = true;
                            }
                            $client_totals[$client_name]['amount'] += $income['amount'];
                            $client_totals[$client_name]['paid'] += $income['paid'];
                            $client_totals[$client_name]['balance'] += $income['balance'];
                            $client_totals[$client_name]['count']++;
                            $client_totals[$client_name]['qty1'] += $income['quantity_1'];
                            $client_totals[$client_name]['qty2'] += $income['quantity_2'];
                            
                            // Track stitching types
                            if ($income['stitching'] && !in_array($income['stitching'], $client_totals[$client_name]['stitching_types'])) {
                                $client_totals[$client_name]['stitching_types'][] = $income['stitching'];
                            }
                            
                            // Track clothes types
                            if ($income['clothes'] && !in_array($income['clothes'], $client_totals[$client_name]['clothes_types'])) {
                                $client_totals[$client_name]['clothes_types'][] = $income['clothes'];
                            }
                            
                            // Color code the balance amount
                            $balance_display = '';
                            if ($income['balance'] > 0) {
                                $balance_display = '<span style="color:red; font-weight:bold;">' . number_format($income['balance'], 2) . '</span>';
                            } else if ($income['balance'] < 0) {
                                $balance_display = '<span style="color:orange; font-weight:bold;">' . number_format($income['balance'], 2) . '</span>';
                            } else {
                                $balance_display = '<span style="color:green; font-weight:bold;">' . number_format($income['balance'], 2) . '</span>';
                            }
                            
                            // Check if this is the first row for this client
                            $is_first_client_row = false;
                            if ($client_totals[$client_name]['count'] == 1) {
                                $is_first_client_row = true;
                            }
                            
                            echo "<tr>
                                    <td>" . date('d-m-Y', strtotime($income['created_at'])) . "</td>
                                    <td>{$income['client_name']}</td>
                                    <td>" . number_format($income['stitching'] * $income['quantity_1'], 2) . "</td>
                                    <td>{$income['quantity_1']}</td>
                                   <td>" . number_format($income['clothes'] * $income['quantity_2'], 2) . "</td>
                                    <td>{$income['quantity_2']}</td>
                                    <td>" . number_format($income['amount'], 2) . "</td>
                                    <td>" . number_format($income['paid'], 2) . "</td>
                                    <td>{$balance_display}</td>
                                    <td class='no-print'>
                                        <a href='edit_overall_income.php?id={$income['id']}' class='btn btn-info btn-xs'>Edit</a>
                                        <a href='delete_overall_income.php?id={$income['id']}' class='btn btn-danger btn-xs' onclick='return confirm(\"Delete this record?\")'>Delete</a>";
                            
                            
                            
                            // echo "</td>
                            //     </tr>";
                            $serial++;
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Summary Cards -->
            <!-- Summary Cards - DISPLAYED FIRST -->
<div class="" style="margin-bottom: 20px;">
    <!-- Stitching Amount -->
    <div class="col-md-3">
        <div class="panel panel-warning">
            <div class="panel-heading">
                <h3 class="panel-title">Stitching</h3>
            </div>
            <div class="panel-body text-center">
                <?php
                // Calculate total stitching amount: stitching * quantity_1
                $total_stitching_amount = 0;
                $stmt_stitch = $pdo->prepare($query);
                $stmt_stitch->execute($params);
                
                while ($row = $stmt_stitch->fetch(PDO::FETCH_ASSOC)) {
                    if (!empty($row['stitching']) && !empty($row['quantity_1'])) {
                        $total_stitching_amount += ($row['stitching'] * $row['quantity_1']);
                    }
                }
                ?>
                <h2><?php echo number_format($total_stitching_amount, 2); ?></h2>
                <small>Stitching Income</small>
            </div>
        </div>
    </div>
    
    <!-- Clothes Amount -->
    <div class="col-md-3">
        <div class="panel" style="border-color: #59359a;">
            <div class="panel-heading" style="background-color: #59359a; border-color: #59359a;">
                <h3 style="color:white;" class="panel-title">Clothes</h3>
            </div>
            <div class="panel-body text-center">
                <?php
                // Calculate total clothes amount: clothes * quantity_2
                $total_clothes_amount = 0;
                $stmt_clothes = $pdo->prepare($query);
                $stmt_clothes->execute($params);
                
                while ($row = $stmt_clothes->fetch(PDO::FETCH_ASSOC)) {
                    if (!empty($row['clothes']) && !empty($row['quantity_2'])) {
                        $total_clothes_amount += ($row['clothes'] * $row['quantity_2']);
                    }
                }
                ?>
                <h2 style="color: #636e7b;"><?php echo number_format($total_clothes_amount, 2); ?></h2>
                <small style="color: #636e7b;">Clothes Income</small>
            </div>
        </div>
    </div>
    
    <!-- Total Income -->
    <div class="col-md-2">
        <div class="panel panel-success">
            <div class="panel-heading">
                <h3 class="panel-title">Total Amount</h3>
            </div>
            <div class="panel-body text-center">
                <h2><?php echo number_format($total_amount, 2); ?></h2>
            </div>
        </div>
    </div>
    
    <!-- Total Received -->
    <div class="col-md-2">
        <div class="panel panel-info">
            <div class="panel-heading">
                <h3 class="panel-title">Paid</h3>
            </div>
            <div class="panel-body text-center">
                <h2><?php echo number_format($total_paid, 2); ?></h2>
            </div>
        </div>
    </div>
    
    <!-- Total Balance (Pending) -->
    <div class="col-md-2">
        <div class="panel panel-<?php echo $total_balance > 0 ? 'danger' : ($total_balance < 0 ? 'warning' : 'success'); ?>">
            <div class="panel-heading">
                <h3 class="panel-title">Pending</h3>
            </div>
            <div class="panel-body text-center">
                <h2><?php echo number_format($total_balance, 2); ?></h2>
            </div>
        </div>
    </div>
</div> <!-- Closing div for row no-print -->
            
            <!-- Print Summary Section -->
            <div id="printSummary" class="print-only" style="display: none;">

            <?php 
            // Get all created_at dates from the fetched records
$created_dates = [];
foreach ($client_totals as $client_name => $totals) {
    foreach ($totals['stitching_types'] as $index => $stitch) {
        // Note: $totals array does not store created_at, so we need to collect dates while looping $stmt earlier
    }
}

// Better: Collect created_at dates while looping $stmt (your main table loop)
$all_created_dates = [];
$stmt = $pdo->prepare($query);
$stmt->execute($params);
while ($income = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $all_created_dates[] = $income['created_at'];
}

// Determine earliest and latest date
if (!empty($all_created_dates)) {
    $min_date = date('d-m-Y', strtotime(min($all_created_dates)));
    $max_date = date('d-m-Y', strtotime(max($all_created_dates)));
} else {
    $min_date = $max_date = date('d-m-Y'); // fallback
}

            
            
            ?>
                <div class="print-summary">
                    <div class="print-header">
                        <h2>Bhatti Tailors & Fabrics - Summary Report</h2>
                        <h4>
    <?php 
    if ($min_date == $max_date) {
        echo $min_date;
    } else {
        echo $min_date . ' to ' . $max_date;
    }
    ?>
</h4>
                        <?php if ($date_from || $date_to): ?>
                            <p>Period: <?php echo $date_from ? date('d-m-Y', strtotime($date_from)) : 'Start'; ?> 
                            to <?php echo $date_to ? date('d-m-Y', strtotime($date_to)) : 'End'; ?></p>
                        <?php endif; ?>
                        <hr>
                    </div>
                    
                    <h3>Customer-wise Detailed Summary</h3>
                    <table border="1" class="summary-table" style="width: 100%; margin-top: 20px;">
                        <thead>
                            <tr>
                                <th>Customer Name</th>
                                <th>Orders</th>
                                <th>Stitching</th>
                                 <th>Qty 1</th>
                                <th>Clothes</th>
                                <th>Qty 2</th>
                                <th>Total Amount</th>
                                <th>Total Paid</th>
                                <th>Total Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $grand_total_qty1 = 0;
                            $grand_total_qty2 = 0;
                            $grand_total_amount = 0;
                            $grand_total_paid = 0;
                            $grand_total_balance = 0;
                            $grand_total_orders = 0;
                            
                            foreach ($client_totals as $client_name => $totals): 
                                $grand_total_qty1 += $totals['qty1'];
                                $grand_total_qty2 += $totals['qty2'];
                                $grand_total_amount += $totals['amount'];
                                $grand_total_paid += $totals['paid'];
                                $grand_total_balance += $totals['balance'];
                                $grand_total_orders += $totals['count'];
                                
                                // Format stitching types (max 2 per line)
                                $stitching_display = '';
                                if (!empty($totals['stitching_types'])) {
                                    $stitching_chunks = array_chunk($totals['stitching_types'], 2);
                                    foreach ($stitching_chunks as $chunk) {
                                        $stitching_display .= implode(', ', $chunk) . "<br>";
                                    }
                                } else {
                                    $stitching_display = '-';
                                }
                                
                                // Format clothes types (max 2 per line)
                                $clothes_display = '';
                                if (!empty($totals['clothes_types'])) {
                                    $clothes_chunks = array_chunk($totals['clothes_types'], 2);
                                    foreach ($clothes_chunks as $chunk) {
                                        $clothes_display .= implode(', ', $chunk) . "<br>";
                                    }
                                } else {
                                    $clothes_display = '-';
                                }
                                
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
                                <td><?php echo $client_name; ?></td>
                                <td><?php echo $totals['count']; ?></td>
                               <td><?php 
    // Convert to float, removing any commas
    $stitching = floatval(str_replace(',', '', $stitching_display));
    $qty1 = floatval($totals['qty1']);
    echo number_format($stitching * $qty1, 2); 
?></td>
                                <td><?php echo $totals['qty1']; ?></td>
                                <td><?php 
    // Remove comma from number before calculation
    $clothes_clean = str_replace(',', '', $clothes_display);
    echo number_format(floatval($clothes_clean) * floatval($totals['qty2']), 2); 
?></td>
                                
                                <td><?php echo $totals['qty2']; ?></td>
                                <td><?php echo number_format($totals['amount'], 2); ?></td>
                                <td><?php echo number_format($totals['paid'], 2); ?></td>
                                <td <?php echo $balance_class; ?>><?php echo number_format($totals['balance'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <!-- Grand Total Row -->
                            <tr class="total-row">
                                <td><strong>GRAND TOTAL</strong></td>
                                <td><strong><?php echo $grand_total_orders; ?></strong></td>
                                <td><?php echo number_format($total_stitching_amount, 2); ?></td>
                                <td><strong><?php echo $grand_total_qty1; ?></strong></td>
                                <td><?php echo number_format($total_clothes_amount, 2); ?></td>
                                
                                <td><strong><?php echo $grand_total_qty2; ?></strong></td>
                                <td><strong><?php echo number_format($grand_total_amount, 2); ?></strong></td>
                                <td><strong><?php echo number_format($grand_total_paid, 2); ?></strong></td>
                                <td <?php 
                                    if ($grand_total_balance > 0) {
                                        echo 'style="color:red; font-weight:bold;"';
                                    } elseif ($grand_total_balance < 0) {
                                        echo 'style="color:orange; font-weight:bold;"';
                                    } else {
                                        echo 'style="color:green; font-weight:bold;"';
                                    }
                                ?>><strong><?php echo number_format($grand_total_balance, 2); ?></strong></td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <!-- Additional Summary Statistics -->
                    <div style="margin-top: 20px;">
                        <h4>Summary Statistics</h4>
                        <table border="1" style="width: 100%; margin-top: 10px;">
                            <tr>
                                <td><strong>Total Customers:</strong></td>
                                <td><?php echo count($client_totals); ?></td>
                                <td><strong>Total Orders:</strong></td>
                                <td><?php echo $grand_total_orders; ?></td>
                                <td><strong>Total Items (Qty1+Qty2):</strong></td>
                                <td><?php echo $grand_total_qty1 + $grand_total_qty2; ?></td>
                            </tr>
                            <tr>
                                <td><strong>Total Qty 1:</strong></td>
                                <td><?php echo $grand_total_qty1; ?></td>
                                <td><strong>Total Qty 2:</strong></td>
                                <td><?php echo $grand_total_qty2; ?></td>
                                <td><strong>Average per Order:</strong></td>
                                <td><?php echo $grand_total_orders > 0 ? number_format($grand_total_amount / $grand_total_orders, 2) : '0.00'; ?></td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="print-footer">
                        <p>Generated on: <?php 
    if ($min_date == $max_date) {
        echo $min_date;
    } else {
        echo $min_date . ' to ' . $max_date;
    }
    ?></p>
                        <p>Bhatti Tailors & Fabrics Management System</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="js/jquery.datatables.min.js"></script>
<script src="js/select2.min.js"></script>

<script>
jQuery(document).ready(function() {
    jQuery('#incomeTable').DataTable({
        "pageLength": 10,
        "order": [[0, "desc"]],

        "language": {
            "emptyTable": `
                <div style="padding:25px;">
                    <i class="fa fa-line-chart" style="font-size:40px; color:#17a2b8; margin-bottom:10px;"></i>
                    <div style="font-size:18px; font-weight:600;">No income records found</div>
                    <div style="color:#777; margin-top:5px;">Start by adding your first income entry</div>

                    <div style="margin-top:12px;">
                        <a href="add_overall_income.php" class="btn btn-primary btn-sm">Add Income</a>
                    </div>
                </div>
            `,

            "zeroRecords": `
                <div style="padding:25px;">
                    <i class="fa fa-search" style="font-size:40px; color:#dc3545; margin-bottom:10px;"></i>
                    <div style="font-size:18px; font-weight:600;">No matching records found</div>
                    <div style="color:#777; margin-top:5px;">Try changing your search or filters</div>
                </div>
            `
        }
    });

    jQuery('select').select2({
        minimumResultsForSearch: -1
    });
});

jQuery(document).ready(function() {
    // Check if DataTable is already initialized
    if (!jQuery.fn.DataTable.isDataTable('#incomeTable')) {
        jQuery('#incomeTable').dataTable({
            "pageLength": 10,
            "order": [[0, "desc"]]
        });
    }
    
    // Initialize Select2 for client dropdown with search functionality
    jQuery('#clientSelect').select2({
        placeholder: "Search customer...",
        allowClear: true,
        width: '200px'
    });
    
    // For other select elements (if any), keep them without search
    jQuery('select:not(.select2-client)').select2({
        minimumResultsForSearch: -1
    });
});

function printSummary() {
    var originalContents = document.body.innerHTML;
    var summaryContent = document.getElementById('printSummary').innerHTML;
    
    document.body.innerHTML = summaryContent;
    window.print();
    document.body.innerHTML = originalContents;
    location.reload();
}

// MD5 function remains the same...
</script>

<?php include ('footer.php'); ?>