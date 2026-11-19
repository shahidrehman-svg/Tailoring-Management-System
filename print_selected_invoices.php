<?php
require_once('function.php');
dbconnect();
session_start();

if (!is_user() || !isset($_SESSION['selected_orders'])) {
    redirect('index.php');
}

// Get selected order IDs
$selected_ids = $_SESSION['selected_orders'];

// Create a comma-separated list for SQL query
$ids_str = implode(',', array_map('intval', $selected_ids));

// Fetch selected orders
$orders_query = $pdo->query("SELECT o.*, c.fullname, c.address 
                            FROM `order` o 
                            LEFT JOIN customer c ON o.customer = c.id 
                            WHERE o.id IN ($ids_str) 
                            ORDER BY o.id DESC");

// Count total orders
$total_orders = $pdo->query("SELECT COUNT(*) as count FROM `order` WHERE id IN ($ids_str)")->fetch(PDO::FETCH_ASSOC)['count'];

// Start HTML output
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Selected Orders</title>
    <style>
        @page {
            size: A4;
            margin: 10mm;
        }
        
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 10px;
            font-size: 12px;
        }
        
        .page-container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .invoice-wrapper {
            margin-bottom: 20px;
            border: 1px solid #ddd;
            padding: 15px;
            page-break-inside: avoid;
        }
        
        .invoice-header {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #000;
        }
        
        .company-info {
            text-align: center;
            margin-bottom: 10px;
        }
        
        .company-info h3 {
            margin: 5px 0;
            font-size: 16px;
        }
        
        .invoice-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f9f9f9;
            border-radius: 5px;
        }
        
        .customer-info, .order-info {
            flex: 1;
        }
        
        .customer-info p, .order-info p {
            margin: 3px 0;
            font-size: 11px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 11px;
        }
        
        th, td {
            border: 1px solid #000;
            padding: 6px;
            text-align: left;
        }
        
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        
        .total-row {
            font-weight: bold;
            background-color: #f9f9f9;
        }
        
        .invoice-footer {
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px dashed #000;
            text-align: center;
            font-size: 10px;
        }
        
        .signature-area {
            margin-top: 20px;
            text-align: right;
        }
        
        .separator {
            height: 10px;
            border-bottom: 1px dashed #ccc;
            margin: 15px 0;
        }
        
        .print-controls {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #ccc;
        }
        
        @media print {
            .no-print {
                display: none;
            }
            
            .invoice-wrapper {
                border: 1px solid #ddd;
                margin-bottom: 15px;
            }
            
            .separator {
                border-bottom: 1px dashed #ccc;
            }
            
            body {
                font-size: 11px;
                padding: 5px;
            }
        }
        
        /* Force all invoices on one page */
        .force-one-page {
            page-break-inside: avoid;
            break-inside: avoid;
        }
    </style>
</head>
<body>
    <div class="page-container">
        <div class="print-controls no-print">
            <button onclick="window.print()" style="padding: 10px 20px; background: #4CAF50; color: white; border: none; cursor: pointer;">
                Print All Invoices
            </button>
            <button onclick="window.close()" style="padding: 10px 20px; background: #f44336; color: white; border: none; cursor: pointer; margin-left: 10px;">
                Close Window
            </button>
            <p style="margin-top: 10px; color: #666;">Showing <?php echo $total_orders; ?> selected orders on one page</p>
        </div>
        
        <?php
        $counter = 1;
        while ($order = $orders_query->fetch(PDO::FETCH_ASSOC)) {
            $customer_name = $order['fullname'] ?? "Unknown Customer";
            $customer_address = $order['address'] ?? "No Address";
            $balance = $order['amount'] - $order['paid'];
        ?>
        
        <div class="invoice-wrapper force-one-page">
            <!-- Invoice Header -->
            <div class="invoice-header">
                <div style="text-align: center;">
                    <img src="img/logo_pic.png" style="width: 60px; height: 50px; display: block; margin: 0 auto 5px;">
                    <h3>Bhatti Tailors & Fabrics</h3>
                    <!-- <p style="font-size: 11px; margin: 0;">Invoice</p> -->
                </div>
            </div>
            
            <!-- Order Details Table -->
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Size No</th>
                        <th>Date Collected</th>
                        <th>Amount</th>
                        <th>Paid</th>
                        <th>Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php echo $counter; ?></td>
                        <td><?php echo $order['fullname']; ?></td>
                        <td><?php echo $order['address']; ?></td>
                        <td><?php echo date('d/m/Y', strtotime($order['date_collected'])); ?></td>
                        <td style="text-align: right;">Rs <?php echo number_format($order['amount'], 2); ?></td>
                        <td style="text-align: right;">Rs <?php echo number_format($order['paid'], 2); ?></td>
                        <td style="text-align: right;">Rs <?php echo number_format($balance, 2); ?></td>
                    </tr>
                    <tr class="total-row">
                        <td colspan="6" style="text-align: right;"><strong>Total Balance:</strong></td>
                        <td style="text-align: right;"><strong>Rs <?php echo number_format($balance, 2); ?></strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <?php
            // Add separator between invoices (except after last one)
            if ($counter < $total_orders) {
                echo '<div class="separator"></div>';
            }
            
            $counter++;
        }
        ?>
        
    </div>
    
    <script>
        // Auto print when page loads
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 1000);
        }
        
        // Adjust layout for printing
        window.onbeforeprint = function() {
            // Check if content is too long
            var pageHeight = window.innerHeight;
            var contentHeight = document.body.scrollHeight;
            
            if (contentHeight > pageHeight * 1.5) {
                // Reduce spacing for very long content
                var invoices = document.querySelectorAll('.invoice-wrapper');
                invoices.forEach(function(invoice) {
                    invoice.style.marginBottom = '10px';
                    invoice.style.padding = '10px';
                });
                
                var separators = document.querySelectorAll('.separator');
                separators.forEach(function(separator) {
                    separator.style.margin = '10px 0';
                    separator.style.height = '5px';
                });
            }
        };
    </script>
</body>
</html>
<?php
// Clear session after printing
unset($_SESSION['selected_orders']);
?>