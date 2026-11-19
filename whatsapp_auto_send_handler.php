<?php
require_once('function.php');
dbconnect();
session_start();

if (!is_user()) {
    die(json_encode(['success' => false, 'error' => 'Unauthorized']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    if ($_POST['action'] === 'get_next_customer') {
        if (!isset($_SESSION['whatsapp_data'])) {
            die(json_encode(['success' => false, 'error' => 'No data in session']));
        }
        
        $data = $_SESSION['whatsapp_data'];
        $customerIds = $data['customer_ids'];
        $message = $data['message'];
        $currentIndex = $data['current_index'];
        $total = $data['total'];
        
        if ($currentIndex >= $total) {
            // All done
            unset($_SESSION['whatsapp_data']);
            die(json_encode(['success' => true, 'finished' => true]));
        }
        
        $customerId = $customerIds[$currentIndex];
        
        // Get customer details
        $stmt = $pdo->prepare("SELECT id, fullname, phonenumber FROM customer WHERE id = ?");
        $stmt->execute([$customerId]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$customer) {
            // Skip this customer and move to next
            $_SESSION['whatsapp_data']['current_index'] = $currentIndex + 1;
            die(json_encode(['success' => false, 'error' => 'Customer not found', 'skip' => true]));
        }
        
        // Format phone for WhatsApp
        $phone = preg_replace('/[^0-9]/', '', $customer['phonenumber']);
        if (strlen($phone) == 10) {
            $phone = '92' . $phone;
        } elseif (strlen($phone) == 11 && $phone[0] == '0') {
            $phone = '92' . substr($phone, 1);
        }
        
        // Create WhatsApp URL with auto-send JavaScript
        $encodedMessage = urlencode($message);
        
        // Create JavaScript to auto-send
        $javascriptCode = "
            // Wait for WhatsApp to load
            setTimeout(function() {
                // Find message input
                var messageInput = document.querySelector('div[contenteditable=\"true\"][data-tab=\"10\"]');
                if (messageInput) {
                    // Clear any existing text
                    messageInput.innerHTML = '';
                    
                    // Create text node with message
                    var textNode = document.createTextNode(decodeURIComponent('$encodedMessage'));
                    messageInput.appendChild(textNode);
                    
                    // Trigger input event
                    var event = new Event('input', { bubbles: true });
                    messageInput.dispatchEvent(event);
                    
                    // Wait 1 second then click send button
                    setTimeout(function() {
                        var sendButton = document.querySelector('button[data-tab=\"10\"]');
                        if (sendButton) {
                            sendButton.click();
                            // Close tab after 3 seconds
                            setTimeout(function() {
                                window.close();
                            }, 3000);
                        }
                    }, 1000);
                }
            }, 5000);
        ";
        
        // URL encode the JavaScript
        $encodedJS = urlencode($javascriptCode);
        
        // Create WhatsApp URL
        $whatsappUrl = "https://web.whatsapp.com/send?phone=$phone";
        
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
            
            $stmt = $pdo->prepare("INSERT INTO whatsapp_messages 
                                  (customer_id, customer_name, phone, message, status) 
                                  VALUES (?, ?, ?, ?, 'sent')");
            $stmt->execute([$customerId, $customer['fullname'], $customer['phonenumber'], $message]);
        } catch (Exception $e) {
            // Ignore errors
        }
        
        // Update session
        $_SESSION['whatsapp_data']['current_index'] = $currentIndex + 1;
        
        // Return data
        echo json_encode([
            'success' => true,
            'whatsapp_url' => $whatsappUrl,
            'customer_name' => $customer['fullname'],
            'phone' => $customer['phonenumber'],
            'current_index' => $currentIndex + 1,
            'total' => $total,
            'javascript_code' => $javascriptCode
        ]);
        exit;
    }
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
?>