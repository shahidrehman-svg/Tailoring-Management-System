<?php
require_once('function.php');
dbconnect();
session_start();

if (!is_user()) {
    redirect('index.php');
}

// Get current date for template
$currentDate = date('d M Y');
?>
<!DOCTYPE html>
<html>
<head>
    <title>WhatsApp Bulk Sender - Manual Send</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="js/jquery-1.11.1.min.js"></script>
    <style>
        body { padding: 20px; background: #25D366; }
        .container { background: white; padding: 20px; border-radius: 10px; max-width: 1200px; }
        .whatsapp-link { 
            background: #25D366; 
            color: white; 
            padding: 12px 20px; 
            border-radius: 5px; 
            text-decoration: none; 
            display: block; 
            margin: 10px 0;
            transition: all 0.3s;
        }
        .whatsapp-link:hover { 
            background: #128C7E; 
            color: white; 
            text-decoration: none;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .customer-info { 
            background: #dcf8c6; 
            padding: 10px; 
            border-radius: 5px; 
            margin: 5px 0;
        }
        .customer-address {
            color: #555;
            font-size: 0.9em;
            margin-top: 3px;
            font-style: italic;
        }
        .progress-container { margin: 20px 0; }
        .completed { opacity: 0.6; }
        .customer-checkbox { display: block; padding: 8px; border-bottom: 1px solid #eee; cursor: pointer; }
        .customer-checkbox:hover { background: #f5f5f5; }
        .customer-checkbox.selected { background: #e8f4ff; border-left: 4px solid #25D366; }
        #searchCustomer { 
            margin-bottom: 15px; 
            padding: 10px 15px;
            border-radius: 5px;
            border: 1px solid #ddd;
            width: 100%;
        }
        .search-container { position: relative; }
        .search-container i { 
            position: absolute;
            right: 15px;
            top: 12px;
            color: #666;
        }
        .no-customers { 
            display: none; 
            text-align: center; 
            padding: 20px; 
            color: #666; 
            background: #f9f9f9;
            border-radius: 5px;
        }
        .customer-count {
            background: #25D366;
            color: white;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 12px;
            margin-left: 5px;
        }
        .customer-details {
            margin-left: 25px;
            font-size: 0.9em;
            color: #666;
        }
        .address-icon {
            color: #25D366;
            margin-right: 5px;
        }
        .collected-date {
            color: #075E54;
            font-weight: bold;
        }
        .collection-date-info {
            color: #d35400;
            font-size: 0.85em;
            margin-top: 3px;
            font-weight: bold;
        }
        .calendar-icon {
            color: #e74c3c;
            margin-right: 5px;
        }
        .template-preview {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 15px;
            display: none;
        }
        .preview-title {
            color: #25D366;
            font-weight: bold;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

<div class="container">
    <h1 style="color: #075E54;"><i class="fab fa-whatsapp"></i> WhatsApp Bulk Sender</h1>
    
    <?php
    if (isset($_POST['message']) && isset($_POST['customers'])) {
        $message = $_POST['message'];
        $customers = $_POST['customers'];
        $total = count($customers);
        
        echo "<div class='alert alert-success'>
                <h4><i class='fa fa-check-circle'></i> $total WhatsApp Links Generated!</h4>
                <p><strong>Instructions:</strong></p>
                <ol>
                    <li>Click the first WhatsApp link below</li>
                    <li>Wait for WhatsApp Web to load completely</li>
                    <li><strong>MANUALLY click the green send button</strong></li>
                    <li>Close the WhatsApp tab</li>
                    <li>Click the next link and repeat</li>
                </ol>
            </div>";
        
        echo "<div class='progress-container'>
                <div class='progress'>
                    <div class='progress-bar progress-bar-striped' id='progressBar' style='width: 0%'></div>
                </div>
                <p id='progressText'>Progress: 0/$total</p>
            </div>";
        
        echo "<div id='whatsappLinks'>";
        
        $counter = 1;
        foreach ($customers as $customerId) {
            // Fetch customer details
            $stmt = $pdo->prepare("SELECT fullname, phonenumber, address FROM customer WHERE id = ?");
            $stmt->execute([$customerId]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($customer) {
                // Fetch measurements for this customer
                $measurementsText = "";
                $measurementsData = [];
                
                try {
                    $stmt = $pdo->prepare("SELECT measurement.*, part.title, type.title as type_name FROM measurement
                              JOIN part ON measurement.part_id = part.id
                              JOIN type ON part.type = type.id
                          WHERE customer_id = ? AND measurement.measurement != '' 
                          ORDER BY type.title, part.title");
                    $stmt->execute([$customerId]);
                    $measurements = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if ($measurements) {
                        // Organize measurements by type
                        $measurementsByType = [];
                        foreach ($measurements as $m) {
                            $measurementsByType[$m['type_name']][] = $m;
                        }
                        
                        // Create measurements table in the format shown in your image
                        $measurementsText = "📏 *Measurement Details*\n";
                        $measurementsText .= "════════════════════════\n\n";
                        
                        foreach ($measurementsByType as $type => $items) {
                            $measurementsText .= "*" . strtoupper($type) . "*\n";
                            foreach ($items as $item) {
                                $title = htmlspecialchars($item['title']);
                                $value = htmlspecialchars($item['measurement']);
                                $measurementsText .= "• $title: *$value*\n";
                            }
                            $measurementsText .= "\n";
                        }
                        
                        // Create alternative table format (like your image)
                        // $measurementsTable = "📏 *Measurement Table*\n";
                        // $measurementsTable .= "═══════════════════════\n\n";
                        
                        // Common measurement titles in Urdu/English
                        $measurementMapping = [
                            'lambai' => 'Lambai',
                            'bazoo' => 'Bazoo',
                            'teera' => 'Teera', 
                            'galla' => 'Galla',
                            'chest' => 'Chest',
                            'ghera' => 'Ghera',
                            'shalwar' => 'Shalwar',
                            'paincha' => 'Paincha',
                            'pocket' => 'Pocket',
                            'cuff' => 'Cuff',
                            'patti' => 'Patti'
                        ];
                        
                        // Find and organize specific measurements
                        $foundMeasurements = [];
                        foreach ($measurements as $m) {
                            $title = strtolower(trim($m['title']));
                            foreach ($measurementMapping as $key => $displayName) {
                                if (strpos($title, $key) !== false) {
                                    $foundMeasurements[$displayName] = $m['measurement'];
                                    break;
                                }
                            }
                        }
                        
                        // Create table in your format
                        if (!empty($foundMeasurements)) {
                            $measurementsTable .= "─────────────────────\n";
                            $measurementsTable .= "   MEASUREMENTS      \n";
                            $measurementsTable .= "─────────────────────\n";
                            
                            foreach ($measurementMapping as $displayName) {
                                if (isset($foundMeasurements[$displayName])) {
                                    $value = $foundMeasurements[$displayName];
                                    // Format line with proper spacing
                                    $line = sprintf(" %-12s %7s ", $displayName, $value);
                                    $measurementsTable .= $line . "\n";
                                }
                            }
                            
                            // $measurementsTable .= "─────────────────────\n\n";
                        }
                    }
                } catch (Exception $e) {
                    // Measurements table might not exist
                    error_log("Could not fetch measurements: " . $e->getMessage());
                }
                
                // Replace variables in the message for this specific customer
                $personalizedMessage = $message;
                $personalizedMessage = str_replace('{name}', $customer['fullname'], $personalizedMessage);
                $personalizedMessage = str_replace('{phone}', $customer['phonenumber'], $personalizedMessage);
                $personalizedMessage = str_replace('{address}', $customer['address'], $personalizedMessage);
                
                // Get collection date from order table
                $collectionDate = 'Not specified';
                try {
                    // Get collection date from order table - FIXED: using `order` table with customer column
                    $orderStmt = $pdo->prepare("SELECT date_collected FROM `order` WHERE customer = ? ORDER BY id DESC LIMIT 1");
                    $orderStmt->execute([$customerId]);
                    $order = $orderStmt->fetch(PDO::FETCH_ASSOC);
                    if ($order && !empty($order['date_collected'])) {
                        $collectionDate = date('d M Y', strtotime($order['date_collected']));
                    }
                } catch (Exception $e) {
                    // Order table doesn't exist or doesn't have date_collected
                    error_log("Could not fetch collection date: " . $e->getMessage());
                }
                
                $personalizedMessage = str_replace('{date_collected}', $collectionDate, $personalizedMessage);
                
                // Current date
                $currentDate = date('d M Y');
                $personalizedMessage = str_replace('{date}', $currentDate, $personalizedMessage);
                
                // Replace measurements placeholder
                $personalizedMessage = str_replace('{measurements}', $measurementsText, $personalizedMessage);
                $personalizedMessage = str_replace('{measurements_table}', $measurementsTable ?? '', $personalizedMessage);
                
                $phone = preg_replace('/[^0-9]/', '', $customer['phonenumber']);
                
                // Format for Pakistan numbers
                if (strlen($phone) == 10) {
                    $phone = '92' . $phone;
                } elseif (strlen($phone) == 11 && $phone[0] == '0') {
                    $phone = '92' . substr($phone, 1);
                }
                
                $encodedMessage = urlencode($personalizedMessage);
                $whatsappUrl = "https://web.whatsapp.com/send?phone=$phone&text=$encodedMessage";
                
                echo "<div class='customer-item' id='customer-$counter'>
                        <div class='customer-info'>
                            <strong>#{$counter}. {$customer['fullname']}</strong><br>
                            Phone: {$customer['phonenumber']}";
                
                // Display address if available
                if (!empty($customer['address'])) {
                    echo "<div class='customer-address'>
                            <i class='fas fa-map-marker-alt address-icon'></i> 
                            Address: " . htmlspecialchars($customer['address']) . "
                          </div>";
                }
                
                // Display collection date if available
                if ($collectionDate !== 'Not specified') {
                    echo "<div class='collection-date-info'>
                            <i class='fas fa-calendar-alt calendar-icon'></i> 
                            Collection Date: $collectionDate
                          </div>";
                }
                
                echo "</div>
                        <a href='$whatsappUrl' target='_blank' class='whatsapp-link' data-index='$counter'>
                            <i class='fab fa-whatsapp'></i> Send to {$customer['fullname']}
                        </a>
                      </div>";
                
                // Log to database
                try {
                    $pdo->exec("CREATE TABLE IF NOT EXISTS whatsapp_messages (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        customer_id INT,
                        customer_name VARCHAR(255),
                        phone VARCHAR(20),
                        message TEXT,
                        sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        status ENUM('pending', 'sent', 'failed') DEFAULT 'pending'
                    )");
                    
                    $stmt = $pdo->prepare("INSERT INTO whatsapp_messages (customer_id, customer_name, phone, message) VALUES (?, ?, ?, ?)");
                    $stmt->execute([
                        $customerId, 
                        $customer['fullname'], 
                        $customer['phonenumber'], 
                        $personalizedMessage
                    ]);
                } catch (Exception $e) {
                    error_log("Database error: " . $e->getMessage());
                }
                
                $counter++;
            }
        }
        
        echo "</div>";
        
        echo "<hr>
              <div class='text-center'>
                <button onclick='markAllAsSent()' class='btn btn-success'>
                    <i class='fa fa-check'></i> Mark All as Sent
                </button>
                <a href='whatsapp_simple_send.php' class='btn btn-primary'>
                    <i class='fa fa-redo'></i> Send More Messages
                </a>
              </div>";
        
        echo "<script>
            var total = $total;
            var completed = 0;
            
            // Update progress when links are clicked
            $('.whatsapp-link').click(function() {
                var index = $(this).data('index');
                $('#customer-' + index).addClass('completed');
                
                // Update progress
                completed++;
                var percent = (completed / total) * 100;
                $('#progressBar').css('width', percent + '%');
                $('#progressText').text('Progress: ' + completed + '/' + total);
                
                // If last one, show completion message
                if (completed === total) {
                    $('#progressText').html('<span class=\"text-success\"><i class=\"fa fa-check\"></i> All messages sent!</span>');
                }
            });
            
            function markAllAsSent() {
                if (confirm('Mark all messages as sent?')) {
                    window.location.href = 'mark_all_sent.php';
                }
            }
        </script>";
        
        exit;
    }
    ?>
    
    <div class="row">
        <div class="col-md-8">
            <form method="post">
                <div class="form-group">
                    <label><strong>Select Customers:</strong> 
                        <span class="customer-count" id="selectedCount">0 selected</span>
                    </label><br>
                    
                    <!-- Search Bar -->
                    <div class="search-container">
                        <input type="text" id="searchCustomer" placeholder="Search customers by name, phone or address...">
                        <i class="fas fa-search"></i>
                    </div>
                    
                    <div id="customerList" style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;">
                    <?php
                    // Fetch customers and their collection dates
                    $customers = $pdo->query("SELECT id, fullname, phonenumber, address FROM customer WHERE phonenumber != '' AND phonenumber IS NOT NULL ORDER BY fullname");
                    $totalCustomers = 0;
                    
                    // Store customer data in JavaScript array for auto-fill
                    $customersData = [];
                    
                    while ($c = $customers->fetch(PDO::FETCH_ASSOC)) {
                        // Get collection date from order table if available
                        $collectionDate = '';
                        try {
                            // Get collection date from order table - FIXED: using `order` table with customer column
                            $orderStmt = $pdo->prepare("SELECT date_collected FROM `order` WHERE customer = ? ORDER BY id DESC LIMIT 1");
                            $orderStmt->execute([$c['id']]);
                            $order = $orderStmt->fetch(PDO::FETCH_ASSOC);
                            if ($order && !empty($order['date_collected'])) {
                                $collectionDate = date('d M Y', strtotime($order['date_collected']));
                            }
                        } catch (Exception $e) {
                            // Do nothing if order table doesn't exist
                        }
                        
                        // Store customer data for JavaScript
                        $customersData[] = [
                            'id' => $c['id'],
                            'name' => $c['fullname'],
                            'phone' => $c['phonenumber'],
                            'address' => $c['address'],
                            'date_collected' => $collectionDate
                        ];
                        
                        echo "<div class='customer-checkbox' data-customer-id='{$c['id']}'>
                                <label>
                                    <input type='checkbox' name='customers[]' value='{$c['id']}' class='customer-checkbox-input'>
                                    <strong>{$c['fullname']}</strong> - {$c['phonenumber']}";
                        
                        // Display address if available
                        if (!empty($c['address'])) {
                            echo "<div class='customer-details'>
                                    <i class='fa fa-map-marker-alt address-icon'></i> 
                                    " . htmlspecialchars($c['address']) . "
                                  </div>";
                        }
                        
                        // Display collection date if available
                        if (!empty($collectionDate)) {
                            echo "<div class='customer-details'>
                                    <i class='fas fa-calendar-alt calendar-icon'></i> 
                                    Collection Date: $collectionDate
                                  </div>";
                        }
                        
                        echo "</label>
                              </div>";
                        $totalCustomers++;
                    }
                    ?>
                    <div class="no-customers" id="noCustomersFound">
                        <i class="fas fa-search fa-2x" style="color: #ccc; margin-bottom: 10px;"></i>
                        <p>No customers found matching your search</p>
                    </div>
                    </div>
                    
                    <div style="margin-top: 10px;">
                        <button type="button" onclick="$('.customer-checkbox-input').prop('checked', true).trigger('change')" class="btn btn-sm btn-success">
                            <i class="fas fa-check-square"></i> Select All
                        </button>
                        <button type="button" onclick="$('.customer-checkbox-input').prop('checked', false).trigger('change')" class="btn btn-sm btn-danger">
                            <i class="far fa-square"></i> Deselect All
                        </button>
                        <span class="pull-right text-muted" id="totalCustomers">Total: <?php echo $totalCustomers; ?> customers</span>
                    </div>
                </div>
                
                <!-- Template Preview -->
                <div class="template-preview" id="templatePreview">
                    <div class="preview-title"><i class="fas fa-eye"></i> Template Preview (First Selected Customer)</div>
                    <div id="previewContent"></div>
                </div>
                
                <div class="form-group">
                    <label><strong>Message Template:</strong></label>
                    <select class="form-control" id="templateSelect" onchange="applyTemplate(this.value)">
                        <option value="">Select Template</option>
                        <option value="order_ready">Order Ready for Collection</option>
                        <option value="payment_reminder">Payment Reminder</option>
                        <option value="delivery">Delivery Update</option>
                        <option value="new_arrival">New Fabric Arrival</option>
                        <option value="measurement">Measurement Reminder</option>
                        <option value="measurement_table">Measurement Table</option>
                        <option value="new_booking">New Booking Confirmation</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label><strong>Your Message:</strong></label>
                    <textarea name="message" id="message" class="form-control" rows="6" placeholder="Type your message here..." required></textarea>
                    <small class="text-muted">You can use variables: {name}, {phone}, {address}, {date_collected}, {date}, {measurements}, {measurements_table}</small>
                </div>
                
                <button type="submit" class="btn btn-success btn-lg btn-block">
                    <i class="fab fa-whatsapp"></i> Generate WhatsApp Links
                </button>
            </form>
        </div>
        
        <div class="col-md-4">
            <div class="alert alert-info">
                <h5><i class="fa fa-info-circle"></i> How to Use:</h5>
                <ol>
                    <li>Select customers (first selected customer's details will auto-fill template)</li>
                    <li>Choose template or type message</li>
                    <li>Click "Generate WhatsApp Links"</li>
                    <li>Click each WhatsApp link that appears</li>
                    <li>Wait for WhatsApp Web to load</li>
                    <li><strong>MANUALLY click the green send button</strong></li>
                    <li>Close tab and click next link</li>
                </ol>
                
                <h5><i class="fa fa-lightbulb"></i> Tips:</h5>
                <ul>
                    <li>Make sure you're logged into WhatsApp Web</li>
                    <li>Keep your phone connected to internet</li>
                    <li>Close previous WhatsApp tabs</li>
                    <li>Work in a new browser window</li>
                    <li>Select customers first, then choose template for auto-fill</li>
                </ul>
                
                <h5><i class="fa fa-map-marker-alt"></i> Address Information:</h5>
                <p>Customer addresses are displayed to help identify them. You can also search by address in the search bar.</p>
                
                <h5><i class="fa fa-ruler"></i> New: Measurement Table Template:</h5>
                <p>Select "Measurement Table" template to send measurements in a formatted table like:</p>
                <pre style="background: #f8f9fa; padding: 10px; border-radius: 5px; font-size: 12px;">
─────────────────────
   MEASUREMENTS     
─────────────────────
 Lambai        39.5 
 Bazoo      22.5-6.5
 Teera           17 
 Galla           13 
 Chest           18 
 Ghera           19 
─────────────────────</pre>
                
                <h5><i class="fa fa-calendar-check"></i> Template Variables:</h5>
                <ul>
                    <li><code>{name}</code> - Customer Name</li>
                    <li><code>{phone}</code> - Customer Phone</li>
                    <li><code>{address}</code> - Customer Address</li>
                    <li><code>{date_collected}</code> - Collection Date</li>
                    <li><code>{date}</code> - Today's Date (<?php echo $currentDate; ?>)</li>
                    <li><code>{measurements}</code> - Measurements list</li>
                    <li><code>{measurements_table}</code> - Formatted measurement table</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
// Store customer data from PHP
var customersData = <?php echo json_encode($customersData); ?>;
var currentDate = "<?php echo $currentDate; ?>";

// Search functionality
$('#searchCustomer').on('keyup', function() {
    var searchTerm = $(this).val().toLowerCase();
    var visibleCount = 0;
    
    $('.customer-checkbox').each(function() {
        var name = $(this).find('strong').text().toLowerCase();
        var phone = $(this).find('strong').next().text().toLowerCase();
        var address = $(this).find('.customer-details').text().toLowerCase();
        
        if (name.includes(searchTerm) || 
            phone.includes(searchTerm) || 
            address.includes(searchTerm)) {
            $(this).show();
            visibleCount++;
        } else {
            $(this).hide();
        }
    });
    
    if (visibleCount === 0) {
        $('#noCustomersFound').show();
    } else {
        $('#noCustomersFound').hide();
    }
});

// Update selected count and auto-fill template
function updateSelectedCount() {
    var selected = $('.customer-checkbox-input:checked').length;
    $('#selectedCount').text(selected + ' selected');
    
    // Update checkbox styling
    $('.customer-checkbox').removeClass('selected');
    $('.customer-checkbox-input:checked').closest('.customer-checkbox').addClass('selected');
    
    // Auto-fill template with first selected customer's details
    if (selected > 0) {
        var firstCustomerId = $('.customer-checkbox-input:checked').first().val();
        autoFillTemplate(firstCustomerId);
    } else {
        // Hide preview if no customers selected
        $('#templatePreview').hide();
    }
}

// Auto-fill template with customer details
function autoFillTemplate(customerId) {
    // Find customer data
    var customer = customersData.find(function(c) {
        return c.id == customerId;
    });
    
    if (customer) {
        // Get current template value
        var templateType = $('#templateSelect').val();
        
        // If measurement_table template is selected, auto-fill it
        if (templateType === 'measurement_table') {
            applyTemplateWithCustomer('measurement_table', customer);
        } else if (templateType === 'new_booking') {
            applyTemplateWithCustomer('new_booking', customer);
        }
        
        // Show preview
        showTemplatePreview(customer);
    }
}

// Show template preview
function showTemplatePreview(customer) {
    var previewHtml = '';
    previewHtml += '<strong>Customer:</strong> ' + customer.name + '<br>';
    previewHtml += '<strong>Phone:</strong> ' + customer.phone + '<br>';
    previewHtml += '<strong>Address:</strong> ' + (customer.address || 'Not specified') + '<br>';
    previewHtml += '<strong>Collection Date:</strong> ' + (customer.date_collected || 'Not specified') + '<br>';
    
    $('#previewContent').html(previewHtml);
    $('#templatePreview').show();
}

// Apply template with customer data
function applyTemplateWithCustomer(templateType, customer) {
    var templates = {
        'order_ready': '🎉 **ORDER READY FOR COLLECTION** 🎉\n\nDear ' + customer.name + ',\n\nYour order is ready for collection! Please visit our shop to pick it up.\n\n📍 Shop Address: BHATTI CLOTH & TAILOR\n🕒 Timing: 9:00 AM - 9:00 PM\n📱 Phone: 0300-1234567\n\nThank you for your business!\n\nBest regards,\nBHATTI CLOTH & TAILOR',
        
        'payment_reminder': '💰 **PAYMENT REMINDER** 💰\n\nDear ' + customer.name + ',\n\nThis is a friendly reminder about your pending payment. Please clear your outstanding amount at your earliest convenience.\n\nYou can visit our shop or make a bank transfer.\n\nThank you for your cooperation!\n\nBHATTI CLOTH & TAILOR',
        
        'delivery': '🚚 **DELIVERY UPDATE** 🚚\n\nDear ' + customer.name + ',\n\nYour order is out for delivery today! Please keep your phone handy.\n\nDelivery Address: ' + (customer.address || 'Your address') + '\n\nOur delivery agent will call you before arrival.\n\nDelivery Time: 10 AM - 6 PM\n\nThank you!\n\nBHATTI CLOTH & TAILOR',
        
        'new_arrival': '🌟 **NEW FABRIC ARRIVAL** 🌟\n\nDear ' + customer.name + ',\n\nWe have received new fabric stock! Visit our shop to see the latest collection.\n\nSpecial discounts for regular customers!\n\n📍 BHATTI CLOTH & TAILOR\n\nHurry, limited stock available!',
        
        'measurement': '📏 **MEASUREMENT REMINDER** 📏\n\nDear ' + customer.name + ',\n\nThis is a reminder for your scheduled measurement appointment. Please visit our shop at your convenience.\n\n📍 Shop Address: BHATTI CLOTH & TAILOR\n🕒 Timing: 9 AM - 9 PM\n\nThank you!\n\nBHATTI CLOTH & TAILOR',
        
        'measurement_table': '📏 *Measurement Details*\n════════════════════════\n\n' +
                            '*Customer Information:*\n' +
                            '👤 Name: ' + customer.name + '\n' +
                            '📱 Phone: ' + customer.phone + '\n' +
                            '📍 Address: ' + (customer.address || 'Not specified') + '\n' +
                            '📅 Date: ' + currentDate + '\n\n' +
                            '{measurements_table}\n' +
                            '────────────────────\n' +
                            '📍 *BHATTI TAILOR & FABRIC*\n' +
                            '🕒 9:00 AM - 8:00 PM\n' +
                            '📱 03445470084\n\n' +
                            'Thank you for choosing us!',
        
        'new_booking': '✅ **NEW BOOKING CONFIRMATION** ✅\n\nDear ' + customer.name + ',\n\nThank you for your new booking with BHATTI CLOTH & TAILOR!\n\n📝 **Booking Details:**\n👤 Customer Name: ' + customer.name + '\n📱 Phone: ' + customer.phone + '\n📍 Address: ' + (customer.address || 'Not specified') + '\n📅 Collection Date: ' + (customer.date_collected || 'To be confirmed') + '\n📅 Booking Date: ' + currentDate + '\n\nYour booking has been confirmed and is now in progress. We will notify you when your order is ready for collection.\n\n📍 Shop Address: BHATTI CLOTH & TAILOR\n🕒 Timing: 9:00 AM - 9:00 PM\n📱 Contact: 03445470084\n\nThank you for choosing us!\n\nBest regards,\nBHATTI CLOTH & TAILOR'
    };
    
    if (templates[templateType]) {
        $('#message').val(templates[templateType]);
    }
}

// Original applyTemplate function (for when no customer is selected)
function applyTemplate(templateType) {
    var selectedCount = $('.customer-checkbox-input:checked').length;
    
    if (selectedCount > 0 && (templateType === 'measurement_table' || templateType === 'new_booking')) {
        // If customers are selected and measurement_table or new_booking template is chosen, auto-fill with first customer
        var firstCustomerId = $('.customer-checkbox-input:checked').first().val();
        autoFillTemplate(firstCustomerId);
    } else {
        // Show generic template with placeholders
        var templates = {
            'order_ready': '🎉 **ORDER READY FOR COLLECTION** 🎉\n\nDear {name},\n\nYour order is ready for collection! Please visit our shop to pick it up.\n\n📍 Shop Address: BHATTI CLOTH & TAILOR\n🕒 Timing: 9:00 AM - 9:00 PM\n📱 Phone: 0300-1234567\n\nThank you for your business!\n\nBest regards,\nBHATTI CLOTH & TAILOR',
            
            'payment_reminder': '💰 **PAYMENT REMINDER** 💰\n\nDear {name},\n\nThis is a friendly reminder about your pending payment. Please clear your outstanding amount at your earliest convenience.\n\nYou can visit our shop or make a bank transfer.\n\nThank you for your cooperation!\n\nBHATTI CLOTH & TAILOR',
            
            'delivery': '🚚 **DELIVERY UPDATE** 🚚\n\nDear {name},\n\nYour order is out for delivery today! Please keep your phone handy.\n\nDelivery Address: {address}\n\nOur delivery agent will call you before arrival.\n\nDelivery Time: 10 AM - 6 PM\n\nThank you!\n\nBHATTI CLOTH & TAILOR',
            
            'new_arrival': '🌟 **NEW FABRIC ARRIVAL** 🌟\n\nDear {name},\n\nWe have received new fabric stock! Visit our shop to see the latest collection.\n\nSpecial discounts for regular customers!\n\n📍 BHATTI CLOTH & TAILOR\n\nHurry, limited stock available!',
            
            'measurement': '📏 **MEASUREMENT REMinder** 📏\n\nDear {name},\n\nThis is a reminder for your scheduled measurement appointment. Please visit our shop at your convenience.\n\n📍 Shop Address: BHATTI CLOTH & TAILOR\n🕒 Timing: 9 AM - 9 PM\n\nThank you!\n\nBHATTI CLOTH & TAILOR',
            
            'measurement_table': '📏 *Measurement Details*\n════════════════════════\n\n' +
                                '*Customer Information:*\n' +
                                '👤 Name: {name}\n' +
                                '📱 Phone: {phone}\n' +
                                '📍 Address: {address}\n' +
                                '📅 Date: ' + currentDate + '\n\n' +
                                '{measurements_table}\n' +
                                '────────────────────\n' +
                                '📍 *BHATTI TAILOR & FABRIC*\n' +
                                '🕒 9:00 AM - 8:00 PM\n' +
                                '📱 03445470084\n\n' +
                                'Thank you for choosing us!',
            
            'new_booking': '✅ **NEW BOOKING CONFIRMATION** ✅\n\nDear {name},\n\nThank you for your new booking with BHATTI TAILOR & FABRIC!\n\n📝 **Booking Details:**\n👤 Customer Name: {name}\n📱 Phone: {phone}\n📍 Address: {address}\n📅 Collection Date: {date_collected}\n📅 Booking Date: ' + currentDate + '\n\nYour booking has been confirmed and is now in progress. We will notify you when your order is ready for collection.\n\n📍 Shop Address: BHATTI TAILOR & FABRIC\n🕒 Timing: 9:00 AM - 8:00 PM\n📱 Contact: 03445470084\n\nThank you for choosing us!\n\nBest regards,\nBHATTI TAILOR & FABRIC'
        };
        
        if (templates[templateType]) {
            $('#message').val(templates[templateType]);
            $('#templatePreview').hide();
        }
    }
}

// Initialize count
updateSelectedCount();

// Update count when checkboxes change
$('.customer-checkbox-input').change(function() {
    updateSelectedCount();
    
    // If measurement_table template is selected, update it with first selected customer
    var templateType = $('#templateSelect').val();
    if (templateType === 'measurement_table' || templateType === 'new_booking') {
        var selectedCount = $('.customer-checkbox-input:checked').length;
        if (selectedCount > 0) {
            var firstCustomerId = $('.customer-checkbox-input:checked').first().val();
            autoFillTemplate(firstCustomerId);
        }
    }
});

// Select all when clicking on the row
$('.customer-checkbox').click(function(e) {
    if (!$(e.target).is('input') && !$(e.target).is('a')) {
        var checkbox = $(this).find('.customer-checkbox-input');
        checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
    }
});
</script>

</body>
</html>