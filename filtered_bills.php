<?php
require_once('function.php');
dbconnect();
session_start();

if (!is_user()) {
    redirect('index.php');
}

$user = $_SESSION['username'];
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$user]);
$usid = $stmt->fetch(PDO::FETCH_ASSOC);
$uid = $usid['id'] ?? null;

include ('header.php');

// Get filter parameters
$customer_filter = $_GET['customer'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$order_status_filter = $_GET['order_status'] ?? '';

// Build WHERE clause for filters (MOVED THIS UP BEFORE USING IT)
$where_conditions = [];
$params = [];

if ($customer_filter) {
    $where_conditions[] = "(bh.customer_name LIKE :customer OR bh.customer_address LIKE :customer OR bh.customer_phone LIKE :customer)";
    $params[':customer'] = '%' . $customer_filter . '%';
}

if ($date_from && $date_to) {
    $where_conditions[] = "DATE(bh.billing_date) BETWEEN :date_from AND :date_to";
    $params[':date_from'] = $date_from;
    $params[':date_to'] = $date_to;
} elseif ($date_from) {
    $where_conditions[] = "DATE(bh.billing_date) >= :date_from";
    $params[':date_from'] = $date_from;
} elseif ($date_to) {
    $where_conditions[] = "DATE(bh.billing_date) <= :date_to";
    $params[':date_to'] = $date_to;
}

if ($order_status_filter == 'pending') {
    $where_conditions[] = "bh.order_status = 'pending'";
} elseif ($order_status_filter == 'collected') {
    $where_conditions[] = "bh.order_status = 'collected'";
} elseif ($order_status_filter == 'overdue') {
    $where_conditions[] = "bh.order_status = 'pending' AND bh.date_collected IS NOT NULL AND bh.date_collected < CURDATE()";
} elseif ($order_status_filter == 'today') {
    $where_conditions[] = "bh.order_status = 'pending' AND bh.date_collected = CURDATE()";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
?>

<link href="css/jquery.datatables.css" rel="stylesheet">
<style>
    .filtered-bills-container {
        padding: 20px;
    }
    .filter-info {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
        border-left: 4px solid #007bff;
    }
    .filter-info h4 {
        margin-top: 0;
        color: #007bff;
    }
    .back-button {
        margin-bottom: 20px;
    }
    .total-summary {
        background: #e9ecef;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
    }
    .total-summary h4 {
        margin: 0 0 10px 0;
    }
    .summary-stats {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
    }
    .stat-box {
        background: white;
        padding: 10px 15px;
        border-radius: 5px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .stat-label {
        font-size: 12px;
        color: #666;
        text-transform: uppercase;
    }
    .stat-value {
        font-size: 20px;
        font-weight: bold;
        color: #007bff;
    }
    
    /* Print styles for table only */
    @media print {
        /* Hide everything except the print area */
        body * {
            visibility: hidden;
        }
        
        .print-table-area, 
        .print-table-area * {
            visibility: visible;
        }
        
        .print-table-area {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            margin: 0;
            padding: 20px;
        }
        
        /* Hide DataTable controls when printing */
        .dataTables_length,
        .dataTables_filter,
        .dataTables_info,
        .dataTables_paginate,
        .no-print {
            display: none !important;
        }
        
        /* Table styling for print */
        table {
            width: 100% !important;
            border-collapse: collapse !important;
            font-size: 16px;
        }
        
        th, td {
            border: 1px solid #ddd !important;
            padding: 6px !important;
        }
        
        th {
            background-color: #f5f5f5 !important;
            font-weight: bold !important;
        }
        
        .print-header {
            text-align: center;
            margin-bottom: 20px;
            page-break-after: avoid;
        }
        
        .print-header h2 {
            margin: 0;
            font-size: 18px;
        }
        
        .print-summary {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        
        .print-footer {
            margin-top: 20px;
            text-align: center;
            font-size: 10px;
            page-break-before: avoid;
        }
        
        /* Ensure proper page breaks */
        tr {
            page-break-inside: avoid;
            page-break-after: avoid;
        }
        
        thead {
            display: table-header-group;
        }
        
        tfoot {
            display: table-footer-group;
        }
    }
   .dataTables_length select {
    width: 70px !important;
    height: 40px !important;
    padding: 5px 10px;
}

.panel {
    border: none;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 12px rgba(0,0,0,.08);
    margin-bottom: 20px;
    background: #fff;
}

.panel-heading {
    background: #fff !important;
    border-bottom: 1px solid #eee;
    padding: 15px 20px;
}

.panel-title {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
}

.panel-body {
    padding: 20px;
}

.customer-name-cell {
    max-width: 90px;
    word-wrap: break-word;
    white-space: normal !important;
}
</style>

<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Filtered Bills</h1>
        </div>
    </div>

    <div class="filtered-bills-container">
        <!-- Back Button -->
        <div class="back-button no-print">
            <button onclick="window.close();" class="btn btn-default">
                <i class="fa fa-arrow-left"></i> Close Window
            </button>
            <button onclick="printTableOnly();" class="btn btn-info">
                <i class="fa fa-print"></i> Print Records Table
            </button>
            <a href="view_billing.php" class="btn btn-primary">
                <i class="fa fa-arrow-left"></i> Back to Billing
            </a>
        </div>

        <?php
        // Display filter information
        ?>
        <div class="filter-info">
            <h4><i class="fa fa-filter"></i> Applied Filters:</h4>
            <ul style="margin-bottom: 0;">
                <?php if($customer_filter): ?>
                    <li><strong>Customer:</strong> <?php echo htmlspecialchars($customer_filter); ?></li>
                <?php endif; ?>
                <?php if($date_from && $date_to): ?>
                    <li><strong>Date Range:</strong> <?php echo date('d-m-Y', strtotime($date_from)); ?> to <?php echo date('d-m-Y', strtotime($date_to)); ?></li>
                <?php elseif($date_from): ?>
                    <li><strong>From Date:</strong> <?php echo date('d-m-Y', strtotime($date_from)); ?></li>
                <?php elseif($date_to): ?>
                    <li><strong>To Date:</strong> <?php echo date('d-m-Y', strtotime($date_to)); ?></li>
                <?php endif; ?>
                <?php if($order_status_filter): ?>
                    <li><strong>Order Status:</strong> <?php echo ucfirst($order_status_filter); ?></li>
                <?php endif; ?>
                <?php if(empty($customer_filter) && empty($date_from) && empty($date_to) && empty($order_status_filter)): ?>
                    <li>No filters applied - Showing all records</li>
                <?php endif; ?>
            </ul>
        </div>

        <?php
        // Calculate summary statistics with filters
        $summary_sql = "SELECT 
            COUNT(DISTINCT bh.id) as total_bills,
            SUM(bh.total_amount) as total_amount,
            SUM(bh.discount) as total_discount,
            SUM(bh.advance_payment) as total_advance,
            SUM(bh.remaining_amount) as total_remaining,
            COALESCE(SUM(bi.quantity), 0) as total_quantity,
            COALESCE(SUM(bi.suit_quantity), 0) as total_suit_quantity
            FROM billing_header bh 
            LEFT JOIN billing_items bi ON bh.id = bi.bill_id 
            $where_clause";
        
        $summary_stmt = $pdo->prepare($summary_sql);
        foreach ($params as $key => $value) {
            $summary_stmt->bindValue($key, $value);
        }
        $summary_stmt->execute();
        $summary = $summary_stmt->fetch(PDO::FETCH_ASSOC);
        ?>

        <!-- Printable Area -->
        <div class="print-table-area">
            <!-- Print Header -->
            <div class="print-header" style="display: none;">
                <h2>Filtered Bills Report</h2>
                <p>Generated on: <?php echo date('d-m-Y h:i A'); ?></p>
                <?php if($customer_filter || $date_from || $date_to || $order_status_filter): ?>
                    <p>
                        <strong>Filters Applied:</strong>
                        <?php 
                        $filter_texts = [];
                        if($customer_filter) $filter_texts[] = "Customer: " . htmlspecialchars($customer_filter);
                        if($date_from && $date_to) $filter_texts[] = "Date: " . date('d-m-Y', strtotime($date_from)) . " to " . date('d-m-Y', strtotime($date_to));
                        elseif($date_from) $filter_texts[] = "From: " . date('d-m-Y', strtotime($date_from));
                        elseif($date_to) $filter_texts[] = "Until: " . date('d-m-Y', strtotime($date_to));
                        if($order_status_filter) $filter_texts[] = "Status: " . ucfirst($order_status_filter);
                        echo implode(" | ", $filter_texts);
                        ?>
                    </p>
                <?php endif; ?>
                <hr>
            </div>

            <!-- Summary Stats -->
            <div class="total-summary">
                <h4><i class="fa fa-chart-bar"></i> Summary</h4>
                <div class="summary-stats">
                    <div class="stat-box">
                        <div class="stat-label">Total Bills</div>
                        <div class="stat-value"><?php echo $summary['total_bills'] ?? 0; ?></div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-label">Total Amount</div>
                        <div class="stat-value">Rs <?php echo number_format($summary['total_amount'] ?? 0, 2); ?></div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-label">Total Discount</div>
                        <div class="stat-value">Rs <?php echo number_format($summary['total_discount'] ?? 0, 2); ?></div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-label">Total Advance</div>
                        <div class="stat-value">Rs <?php echo number_format($summary['total_advance'] ?? 0, 2); ?></div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-label">Total Remaining</div>
                        <div class="stat-value">Rs <?php echo number_format($summary['total_remaining'] ?? 0, 2); ?></div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-label">Total Quantity</div>
                        <div class="stat-value"><?php echo number_format($summary['total_quantity'] ?? 0); ?></div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-label">Total Suits</div>
                        <div class="stat-value"><?php echo number_format($summary['total_suit_quantity'] ?? 0); ?></div>
                    </div>
                </div>
            </div>

            <!-- Billing Records Card -->
<div class="panel panel-default">
    
    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="fa fa-file-text"></i> Filter Records
        </h4>
    </div>

    <div class="panel-body">

        <!-- Filtered Bills Table -->
        <div class="table-responsive">
            <table class="table table-striped table-bordered" id="filteredBillsTable">
                <thead>
                    <tr>
                        <th>Bill No</th>
                        <th>Customer Name</th>
                        <th>Phone</th>
                        <th>Address</th>
                        <th class="text-right">Amount</th>
                        <th class="text-right">Discount</th>
                        <th class="text-right">Advance</th>
                        <th class="text-right">Remaining</th>
                        <th>Items/Qty</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th class="no-print">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Get all bills with item count and total quantity
                    $sql = "SELECT 
                        bh.*, 
                        COUNT(bi.id) as item_count,
                        COALESCE(SUM(bi.quantity), 0) as total_quantity,
                        COALESCE(SUM(bi.suit_quantity), 0) as total_suit_quantity
                        FROM billing_header bh 
                        LEFT JOIN billing_items bi ON bh.id = bi.bill_id 
                        $where_clause
                        GROUP BY bh.id 
                        ORDER BY bh.billing_date DESC, bh.id DESC";

                    $stmt = $pdo->prepare($sql);
                    foreach ($params as $key => $value) {
                        $stmt->bindValue($key, $value);
                    }
                    $stmt->execute();
                    $bills = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if(count($bills) > 0) {
                        foreach($bills as $bill) {

                            $order_status_class = '';
                            $order_status_text = '';

                            if($bill['order_status'] == 'collected') {
                                $order_status_class = 'label-success';
                                $order_status_text = '✓ Collected';
                            } else {
                                if(!empty($bill['date_collected']) && $bill['date_collected'] != '0000-00-00') {
                                    $collection_seconds = strtotime($bill['date_collected']);
                                    $today_seconds = strtotime(date('Y-m-d'));
                                    $diff_days = round(($collection_seconds - $today_seconds) / 86400);

                                    if($diff_days < 0) {
                                        $order_status_class = 'label-danger';
                                        $order_status_text = '⚠ Overdue';
                                    } elseif($diff_days == 0) {
                                        $order_status_class = 'label-info';
                                        $order_status_text = '📅 Due Today';
                                    } else {
                                        $order_status_class = 'label-warning';
                                        $order_status_text = '⏳ Pending';
                                    }
                                } else {
                                    $order_status_class = 'label-warning';
                                    $order_status_text = '⏳ Pending';
                                }
                            }

                            $payment_methods = [
                                'Cash' => 'success',
                                'Card' => 'primary',
                                'UPI' => 'info',
                                'Credit' => 'warning'
                            ];
                            $payment_class = $payment_methods[$bill['payment_method']] ?? 'default';
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($bill['bill_no']); ?></strong></td>
                               <td class="customer-name-cell">
    <?php 
    $names = explode(',', $bill['customer_name']);
    $display_names = array_map('trim', $names);
    echo implode('<br>', array_map('htmlspecialchars', $display_names));
    ?>
</td>
                                <td><?php echo !empty($bill['customer_phone']) ? htmlspecialchars($bill['customer_phone']) : 'N/A'; ?></td>
                                <td><?php echo !empty($bill['customer_address']) ? htmlspecialchars(substr($bill['customer_address'], 0, 50)) : 'N/A'; ?></td>
                                <td class="text-right">Rs <?php echo number_format($bill['total_amount'], 2); ?></td>
                                <td class="text-right">Rs <?php echo number_format($bill['discount'], 2); ?></td>
                                <td class="text-right">Rs <?php echo number_format($bill['advance_payment'], 2); ?></td>
                                <td class="text-right">
                                    <strong>Rs <?php echo number_format($bill['remaining_amount'], 2); ?></strong>
                                </td>
                                <td>
                                    <span class="label label-default">
                                        <?php echo $bill['item_count']; ?> items
                                    </span>

                                    <?php if($bill['total_quantity'] > 0): ?>
                                        <br>
                                        <small><?php echo $bill['total_quantity']; ?> pcs</small>
                                    <?php endif; ?>

                                    <?php if($bill['total_suit_quantity'] > 0): ?>
                                        <br>
                                        <span class="label label-primary">
                                            <?php echo $bill['total_suit_quantity']; ?> suits
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td><?php echo date('d/m/Y', strtotime($bill['billing_date'])); ?></td>

                                <td>
                                    <span class="label <?php echo $order_status_class; ?>">
                                        <?php echo $order_status_text; ?>
                                    </span>
                                    <br>
                                    <span class="label label-<?php echo $payment_class; ?>">
                                        <?php echo $bill['payment_method']; ?>
                                    </span>
                                </td>

                                <td class="no-print">
                                    <div class="btn-group btn-group-xs">
                                        <a href="print_bill.php?id=<?php echo $bill['id']; ?>"
                                           target="_blank"
                                           class="btn btn-info"
                                           title="Print Bill">
                                            <i class="fa fa-print"></i>
                                        </a>

                                        <a href="add_billing.php?edit_id=<?php echo $bill['id']; ?>"
                                           class="btn btn-warning"
                                           title="Edit Bill">
                                            <i class="fa fa-edit"></i>
                                        </a>

                                        <button type="button"
                                                class="btn btn-primary view-items"
                                                data-bill-id="<?php echo $bill['id']; ?>"
                                                data-bill-no="<?php echo htmlspecialchars($bill['bill_no']); ?>"
                                                data-toggle="modal"
                                                data-target="#itemsModal"
                                                title="View Items">
                                            <i class="fa fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php
                        }
                    } else {
                        ?>
                        <tr>
                            <td colspan="12" class="text-center">
                                <div style="padding:40px;">
                                    <i class="fa fa-search" style="font-size:48px;color:#dc3545;"></i>
                                    <h4>No records found</h4>
                                    <p>No bills match the applied filters.</p>
                                    <button onclick="window.close();" class="btn btn-default">
                                        Close Window
                                    </button>
                                </div>
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
            
            <!-- Print Footer -->
            <div class="print-footer" style="display: none;">
                <p>Bhatti Tailor & Fabric Management System</p>
                <p>Printed on: <?php echo date('d-m-Y h:i A'); ?></p>
            </div>
        </div>
        <!-- End Printable Area -->
    </div>
</div>

<!-- Items Modal -->
<div class="modal fade bill-items-modal" id="itemsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Bill Items - <span id="modalBillNo"></span></h4>
            </div>
            <div class="modal-body">
                <div id="modalLoading" class="text-center" style="padding: 30px;">
                    <i class="fa fa-spinner fa-spin fa-3x"></i>
                    <p>Loading items...</p>
                </div>
                <div id="modalContent" style="display: none;">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Brand/Item</th>
                                    <th class="text-right">Price</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-center">Suit Qty</th>
                                    <th class="text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody id="modalItemsBody"></tbody>
                            <tfoot id="modalItemsFooter"></tfoot>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <a href="#" id="modalPrintBtn" class="btn btn-primary" target="_blank">Print Bill</a>
            </div>
        </div>
    </div>
</div>

<script src="js/jquery.datatables.min.js"></script>
<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#filteredBillsTable').DataTable({
        "paging": true,
        "pageLength": 25,
        "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        "searching": true,
        "ordering": true,
        "info": true,
        "order": [[0, "desc"]],
        "language": {
            "search": "Search bills:",
            "lengthMenu": "Show _MENU_ entries",
            "info": "Showing _START_ to _END_ of _TOTAL_ entries"
        }
    });
    
    // View items functionality
    $(document).on('click', '.view-items', function() {
        var billId = $(this).data('bill-id');
        var billNo = $(this).data('bill-no');
        
        $('#modalBillNo').text(billNo);
        $('#modalPrintBtn').attr('href', 'print_bill.php?id=' + billId);
        
        $('#modalLoading').show();
        $('#modalContent').hide();
        
        $.ajax({
            url: 'get_bill_details.php',
            type: 'GET',
            data: { bill_id: billId },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    var itemsHtml = '';
                    var counter = 1;
                    var subtotal = 0;
                    
                    $.each(response.items, function(index, item) {
                        var itemTotal = parseFloat(item.price) * parseInt(item.quantity);
                        subtotal += itemTotal;
                        
                        itemsHtml += '<tr>' +
                            '<td class="text-center">' + counter + '</td>' +
                            '<td>' + item.brand_name + '</td>' +
                            '<td class="text-right">Rs ' + parseFloat(item.price).toFixed(2) + '</td>' +
                            '<td class="text-center">' + item.quantity + '</td>' +
                            '<td class="text-center">' + (item.suit_quantity || 0) + '</td>' +
                            '<td class="text-right">Rs ' + itemTotal.toFixed(2) + '</td>' +
                            '</tr>';
                        counter++;
                    });
                    
                    $('#modalItemsBody').html(itemsHtml);
                    
                    var footerHtml = '<tr class="active">' +
                        '<td colspan="5" class="text-right"><strong>Subtotal:</strong></td>' +
                        '<td class="text-right"><strong>Rs ' + subtotal.toFixed(2) + '</strong></td>' +
                        '</tr>' +
                        '<tr class="warning">' +
                        '<td colspan="5" class="text-right"><strong>Discount:</strong></td>' +
                        '<td class="text-right"><strong>- Rs ' + parseFloat(response.bill.discount).toFixed(2) + '</strong></td>' +
                        '</tr>' +
                        '<tr class="success">' +
                        '<td colspan="5" class="text-right"><strong>Total Amount:</strong></td>' +
                        '<td class="text-right"><strong>Rs ' + parseFloat(response.bill.total_amount).toFixed(2) + '</strong></td>' +
                        '</tr>';
                    
                    $('#modalItemsFooter').html(footerHtml);
                    
                    $('#modalLoading').hide();
                    $('#modalContent').show();
                } else {
                    $('#modalLoading').html('<div class="alert alert-danger">' + response.message + '</div>');
                }
            },
            error: function() {
                $('#modalLoading').html('<div class="alert alert-danger">Error loading bill details.</div>');
            }
        });
    });
});

function printTableOnly() {
    // Get the printable area
    var printContents = document.querySelector('.print-table-area').cloneNode(true);
    
    // Remove DataTable control elements from the cloned content
    var dataTableControls = printContents.querySelectorAll('.dataTables_length, .dataTables_filter, .dataTables_info, .dataTables_paginate');
    dataTableControls.forEach(function(control) {
        if (control) control.remove();
    });
    
    // Also remove any DataTable search input and its wrapper
    var searchInput = printContents.querySelector('.dataTables_filter');
    if (searchInput) searchInput.remove();
    
    var lengthMenu = printContents.querySelector('.dataTables_length');
    if (lengthMenu) lengthMenu.remove();
    
    // Remove the DataTable's default search box if present
    var searchBox = printContents.querySelector('input[type="search"]');
    if (searchBox && searchBox.closest('.dataTables_filter')) {
        searchBox.closest('.dataTables_filter').remove();
    }
    
    // Remove the "Show X entries" dropdown
    var lengthSelect = printContents.querySelector('select[name="filteredBillsTable_length"]');
    if (lengthSelect && lengthSelect.closest('.dataTables_length')) {
        lengthSelect.closest('.dataTables_length').remove();
    }
    
    // Remove action buttons column from print (last column)
    var rows = printContents.querySelectorAll('tr');
    rows.forEach(function(row) {
        var lastCell = row.querySelector('td:last-child, th:last-child');
        if (lastCell && (lastCell.innerText.includes('Actions') || lastCell.querySelector('.btn-group'))) {
            lastCell.remove();
        }
    });
    
    // Create a new window for printing
    var printWindow = window.open('', '_blank', 'width=1200,height=800');
    
    // Write the print content with LARGER font sizes for PDF
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Filtered Bills Report</title>
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                
                body {
                    font-family: Arial, sans-serif;
                    margin: 30px;
                    padding: 0;
                    background: white;
                }
                
                .print-header, .print-footer {
                    text-align: center;
                }
                
                .print-header {
                    margin-bottom: 35px;
                    padding-bottom: 20px;
                    border-bottom: 3px solid #333;
                }
                
                .print-header h2 {
                    margin: 0 0 10px 0;
                    color: #333;
                    font-size: 32px;
                    font-weight: bold;
                }
                
                .print-header p {
                    margin: 8px 0;
                    color: #555;
                    font-size: 18px;
                }
                
                .total-summary {
                    margin-bottom: 35px;
                    page-break-inside: avoid;
                }
                
                .total-summary h4 {
                    font-size: 24px;
                    margin-bottom: 20px;
                    color: #333;
                }
                
                .summary-stats {
                    display: flex;
                    gap: 25px;
                    flex-wrap: wrap;
                    margin: 20px 0;
                }
                
                .stat-box {
                    border: 2px solid #ddd;
                    padding: 15px 25px;
                    border-radius: 8px;
                    background: #f9f9f9;
                    min-width: 160px;
                }
                
                .stat-label {
                    font-size: 16px;
                    color: #666;
                    text-transform: uppercase;
                    margin-bottom: 10px;
                    font-weight: bold;
                }
                
                .stat-value {
                    font-size: 28px;
                    font-weight: bold;
                    color: #007bff;
                }
                
                /* LARGER TABLE FONTS FOR PDF */
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 25px;
                    font-size: 18px !important;
                }
                
                th, td {
                    border: 1px solid #aaa;
                    padding: 15px 12px !important;
                    text-align: left;
                    vertical-align: top;
                }
                
                th {
                    background-color: #f0f0f0;
                    font-weight: bold;
                    font-size: 20px !important;
                }
                
                td {
                    font-size: 18px !important;
                }
                
                .text-right {
                    text-align: right;
                }
                
                .text-center {
                    text-align: center;
                }
                
                .label {
                    display: inline-block;
                    padding: 6px 12px;
                    border-radius: 4px;
                    font-size: 16px !important;
                    font-weight: bold;
                    margin: 2px 0;
                }
                
                .label-success {
                    background-color: #d4edda;
                    color: #155724;
                }
                
                .label-danger {
                    background-color: #f8d7da;
                    color: #721c24;
                }
                
                .label-warning {
                    background-color: #fff3cd;
                    color: #856404;
                }
                
                .label-info {
                    background-color: #d1ecf1;
                    color: #0c5460;
                }
                
                .label-default {
                    background-color: #e9ecef;
                    color: #495057;
                }
                
                .label-primary {
                    background-color: #cce5ff;
                    color: #004085;
                }
                
                .print-footer {
                    margin-top: 35px;
                    padding-top: 20px;
                    border-top: 2px solid #ddd;
                    font-size: 16px;
                    text-align: center;
                }
                
                strong {
                    font-size: 18px;
                }
                
                small {
                    font-size: 15px;
                }
                
                /* Ensure DataTable elements are hidden */
                .dataTables_length,
                .dataTables_filter,
                .dataTables_info,
                .dataTables_paginate {
                    display: none !important;
                }
                
                @media print {
                    body {
                        margin: 0.5in;
                        padding: 0;
                    }
                    
                    .summary-stats {
                        break-inside: avoid;
                    }
                    
                    tr {
                        break-inside: avoid;
                        page-break-inside: avoid;
                    }
                    
                    thead {
                        display: table-header-group;
                    }
                    
                    th, td {
                        padding: 12px 10px !important;
                    }
                    
                    /* Extra print-only hiding */
                    .dataTables_length,
                    .dataTables_filter,
                    .dataTables_info,
                    .dataTables_paginate,
                    input[type="search"],
                    select[name*="length"] {
                        display: none !important;
                    }
                }
            </style>
        </head>
        <body>
            ${printContents.outerHTML}
            <script>
                window.onload = function() {
                    // Small delay to ensure all styles are applied
                    setTimeout(function() {
                        window.print();
                        setTimeout(function() {
                            window.close();
                        }, 500);
                    }, 200);
                };
            <\/script>
        </body>
        </html>
    `);
    
    printWindow.document.close();
    printWindow.focus();
}
</script>

<?php
include ('footer.php');
?>