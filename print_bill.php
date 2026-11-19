<?php
require_once('function.php');
dbconnect();
session_start();

if (!is_user()) {
    redirect('index.php');
}

$bill_id = $_GET['id'] ?? 0;

// Get bill header
$stmt = $pdo->prepare("SELECT bh.*, u.username 
                       FROM billing_header bh 
                       LEFT JOIN users u ON bh.user_id = u.id 
                       WHERE bh.id = ?");
$stmt->execute([$bill_id]);
$bill = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$bill) {
    die("Bill not found!");
}

// Get bill items
$stmt = $pdo->prepare("SELECT * FROM billing_items WHERE bill_id = ? ORDER BY id");
$stmt->execute([$bill_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Bill <?php echo $bill['bill_no']; ?></title>
    <style>
        body { 
            font-family: 'Courier New', monospace; 
            margin: 0; 
            padding: 0;
            font-size: 20px;
            width: 80mm;
            margin: 0 auto;
            color: #000000 !important;
            font-weight: bold; 
        }
        
        .invoice { 
            /* width: 100%; */
            padding: 10px;
        }
        
        .header { 
            text-align: center; 
            margin-bottom: 5px;
        }
        
        .shop-name { 
            font-size: 14px; 
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .shop-address { 
            font-size: 10px;
            line-height: 1.2;
            color:black;
        }
        
        .title { 
            text-align: center; 
            font-weight: bold; 
            margin: 5px 0;
            font-size: 13px;
        }
        
        .divider {
            border-top: 1px dashed #000;
            margin: 5px 0;
        }
        
        .details { 
            margin-bottom: 8px;
            font-size: 11px;
        }
        
        .details table { 
            width: 100%; 
        }
        
        .details td { 
            padding: 1px 0;
            vertical-align: top;
        }
        
        .details td:first-child {
            width: 30%;
        }
        
        .items { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 5px 0; 
            font-size: 11px;
        }
        
        .items th { 
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 2px 0;
            text-align: left;
        }
        
        .items td { 
            padding: 2px 0;
            border-bottom: 1px dashed #ccc;
        }
        
        .items th:nth-child(1),
        .items td:nth-child(1) {
            width: 40%;
        }
        
        .items th:nth-child(2),
        .items td:nth-child(2) {
            width: 15%;
            text-align: center;
        }
        
        .items th:nth-child(3),
        .items td:nth-child(3) {
            width: 25%;
            text-align: right;
            padding-right: 5px;
        }
        
        .items th:nth-child(4),
        .items td:nth-child(4) {
            width: 20%;
            text-align: right;
        }
        
        .total { 
            width: 100%; 
            margin-top: 8px;
            font-size: 11px;
        }
        
        .total td { 
            padding: 2px 0;
        }
        
        .total tr:last-child {
            border-top: 2px solid #000;
            font-weight: bold;
            font-size: 12px;
        }
        
        .amount { 
            text-align: right;
        }
        
        .payment-info {
            margin: 8px 0;
            font-size: 11px;
            line-height: 1.3;
        }
        
        .footer { 
            text-align: center; 
            margin-top: 10px; 
            font-size: 10px; 
            border-top: 1px dashed #000; 
            padding-top: 5px;
            line-height: 1.2;
        }
        
        .no-print { 
            display: none; 
        }
        
        /* Watermark - only for print */
        @media print {
            body {
                width: 80mm;
                margin: 0;
                padding: 0;
            }
            
            .invoice {
                padding: 3mm;
            }
            
            .watermark {
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%) rotate(-45deg);
                opacity: 0.05;
                font-size: 60px;
                font-weight: bold;
                color: #ccc;
                white-space: nowrap;
                pointer-events: none;
                z-index: -1;
            }
        }
        
        @media screen {
            body {
                border: 1px solid #ccc;
                margin-top: 20px;
                margin-bottom: 20px;
                box-shadow: 0 0 10px rgba(0,0,0,0.1);
            }
            
            .watermark {
                display: none;
            }
            
            .no-print {
                display: block;
                text-align: center;
                margin-top: 20px;
                padding: 10px;
            }
            
            .no-print button {
                margin: 0 5px;
                padding: 8px 15px;
                background: #007bff;
                color: white;
                border: none;
                border-radius: 4px;
                cursor: pointer;
            }
            
            .no-print button:hover {
                background: #0056b3;
            }
        }
    </style>
</head>
<body>
    <!-- Watermark Text -->
    <div class="watermark">TAILOR SHOP</div>
    
    <div class="invoice">
        <div class="header">
            <img src="img/logo_pic.png" style="width:70px; height: 60px;">
            <div class="shop-name">Bhatti Tailors & Fabrics</div>
            <div class="shop-address">Jalal Arcade, Mohra Chappar Stop Chakri Road Rawalpindi</div>
            <div class="shop-address">Phone: 03135086286</div>
        </div>
        
        <div class="divider"></div>
        
        <div class="title">TAX INVOICE</div>
        
        <div class="divider"></div>
        
        <div class="details">
            <table>
                <tr>
                    <td><strong>Bill No:</strong></td>
                    <td><?php echo $bill['bill_no']; ?></td>
                </tr>
                <tr>
                    <td><strong>Date:</strong></td>
                    <td><?php echo date('d/m/Y', strtotime($bill['created_at'] ?? $bill['billing_date'])); ?></td>
                </tr>
                <tr>
                    <td><strong>Time:</strong></td>
                    <td><?php echo date('h:i A', strtotime($bill['created_at'] ?? $bill['billing_date'])); ?></td>
                </tr>
                <tr>
                    <td><strong>Customer:</strong></td>
                    <td><?php echo htmlspecialchars($bill['customer_name']); ?></td>
                </tr>
                <?php if(!empty($bill['customer_phone']) && $bill['customer_phone'] !== 'N/A'): ?>
                <tr>
                    <td><strong>Phone:</strong></td>
                    <td><?php echo htmlspecialchars($bill['customer_phone']); ?></td>
                </tr>
                <?php endif; ?>
                <?php if(!empty($bill['customer_address']) && $bill['customer_address'] !== 'N/A'): ?>
                <tr>
                    <td><strong>Size No:</strong></td>
                    <td><?php echo htmlspecialchars($bill['customer_address']); ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
        
        <div class="divider"></div>
        
        <table class="items">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Qty</th>
                    <th>Rate</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $total_items = 0;
                foreach($items as $item): 
                    $total_items++;
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['brand_name']); ?></td>
                    <td><?php echo $item['quantity']; ?></td>
                    <td>Rs:<?php echo number_format($item['price'], 2); ?></td>
                    <td>Rs:<?php echo number_format($item['item_total'], 2); ?></td>
                </tr>
                <?php if($item['stitching_quantity'] > 0): 
                    $total_items++;
                ?>
                <tr>
                    <td style="padding-left: 10px;">↳ Stitching</td>
                    <td><?php echo $item['stitching_quantity']; ?></td>
                    <td>Rs:<?php echo number_format($item['stitching_amount'], 2); ?></td>
                    <td>Rs:<?php echo number_format($item['item_stitching_total'], 2); ?></td>
                </tr>
                <?php endif; ?>
                <?php endforeach; ?>
                
                <!-- Fill empty rows for better alignment -->
                <?php for($i = $total_items; $i < 5; $i++): ?>
                <tr>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>
                <?php endfor; ?>
            </tbody>
        </table>
        
        <div class="divider"></div>
        
        <table class="total">
            <tr>
                <td>Items Subtotal:</td>
                <td class="amount">Rs:<?php echo number_format($bill['subtotal'], 2); ?></td>
            </tr>
            <?php if($bill['stitching_total'] > 0): ?>
            <tr>
                <td>Stitching Total:</td>
                <td class="amount">Rs:<?php echo number_format($bill['stitching_total'], 2); ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <td>Total Before Discount:</td>
                <td class="amount">Rs:<?php echo number_format($bill['subtotal'] + $bill['stitching_total'], 2); ?></td>
            </tr>
            <?php if($bill['discount'] > 0): ?>
            <!-- <tr>
                <td>Discount:</td>
                <td class="amount">- Rs<?php echo number_format($bill['discount'], 2); ?></td>
            </tr> -->
            <?php endif; ?>
            <tr>
                <td><strong>TOTAL:</strong></td>
                <td class="amount"><strong>Rs:<?php echo number_format($bill['total_amount'], 2); ?></strong></td>
            </tr>
        </table>
        
        <div class="divider"></div>
        
        <!-- <div class="payment-info">
            <strong>Payment:</strong> <?php echo $bill['payment_method']; ?><br>
            <?php if(!empty($bill['notes'])): ?>
                <strong>Notes:</strong> <?php echo htmlspecialchars($bill['notes']); ?><br>
            <?php endif; ?>
            <strong>Sales Person:</strong> <?php echo htmlspecialchars($bill['username']); ?>
        </div> -->
        
        <!-- <div class="divider"></div> -->
        
        <div class="footer">
            Thank you for your business!<br>
            ** This is a computer generated bill **
        </div>
        
        <div class="no-print">
            <button onclick="window.print()">Print Bill</button>
            <button onclick="window.close()">Close</button>
        </div>
    </div>
    
    <script>
    window.onload = function() {
        // Auto print after 1 second
        setTimeout(function() {
            window.print();
        }, 1000);
    };
    </script>
</body>
</html>