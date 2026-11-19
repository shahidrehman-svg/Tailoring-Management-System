<?php
/* $baseurl = "http://demo.tailor.sarutech.com";	
$dbname = "osaru_demo_tailor";
$dbhost = "localhost";
$dbuser = "osaru_tech";
$dbpass = "Sr123Th24"; */


$baseurl = "http://localhost/tailor/";	
$dbname = "tailor";
$dbhost = "localhost";
$dbuser = "root";
$dbpass = "";


error_reporting(E_ALL);
function connectdb()
{
    global $dbname, $dbuser, $dbhost, $dbpass;
    $conms = @mysql_connect($dbhost,$dbuser,$dbpass); //connect mysql
	mysql_set_charset('utf8', $conms);
    if(!$conms) return false;
    $condb = @mysql_select_db($dbname);
    if(!$condb) return false;
    return true;
}



function dbconnect()
{
	global $pdo;

	try {
		$pdo = new PDO('mysql:host='.$GLOBALS['dbhost'].';dbname='.$GLOBALS['dbname'].'', $GLOBALS['dbuser'], $GLOBALS['dbpass']);
	} catch (PDOException $e) {
		die('MySQL connection fail! ' . $e->getMessage());
	}
}


function insert_new_user($username, $password)
{
	# checking username is already taken
	if (username_exists($username))
		return false;

	# insert new user info
	global $pdo;
	$stmt = $pdo->prepare('
		INSERT INTO users
		(username, password)
		values (:username, :password)');

	$stmt->execute( array(':username' => $username, ':password' => md5($password)) );

	if ($pdo->lastInsertId())
		return true;
	else
		return false;
}

function username_exists($username)
{
	global $pdo;
	
	$stmt = $pdo->prepare('
		SELECT id
		FROM users
		WHERE username = :username
		LIMIT 1');

	$stmt->execute( array('username' => $username) );
	return $stmt->fetchColumn();
}

function attempt($username, $password)
{
	global $pdo;
	
	$stmt = $pdo->prepare('
		SELECT id, username
		FROM users
		WHERE username = :username AND password = :password
		LIMIT 1');

	$stmt->execute(array(':username' => $username, 'password' => md5($password)));

	if ($data = $stmt->fetch( PDO::FETCH_OBJ )) {
		# set session
		$_SESSION['username'] = $data->username;
		return true;
	} else {
		return false;
	}
}

function is_user()
{
	if (isset($_SESSION['username']))
		return true;
}

function redirect($url)
{
	header('Location: ' .$url);
	exit;
}

function valid_username($str){
	return preg_match('/^[a-z0-9_-]{3,16}$/', $str);
}

function valid_password($str){
	return preg_match('/^[a-z0-9_-]{6,18}$/', $str);
}

// ===========================================================================
// WHATSAPP FUNCTIONS
// ===========================================================================

/**
 * Generate WhatsApp URL for a phone number
 */
function generate_whatsapp_url($phone, $message) {
    // Clean phone number
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Pakistan number formatting
    if (strlen($phone) == 10) {
        $phone = '92' . $phone; // Add Pakistan country code
    } elseif (strlen($phone) == 11 && $phone[0] == '0') {
        $phone = '92' . substr($phone, 1);
    }
    
    // Encode message
    $encodedMessage = urlencode($message);
    
    return "https://web.whatsapp.com/send?phone=$phone&text=$encodedMessage";
}

/**
 * Send WhatsApp message to customer
 */
function send_whatsapp_to_customer($customerId, $message) {
    global $pdo;
    
    // Get customer details
    $stmt = $pdo->prepare("SELECT id, fullname, phonenumber FROM customer WHERE id = ?");
    $stmt->execute([$customerId]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$customer || empty($customer['phonenumber'])) {
        return ['success' => false, 'error' => 'Customer or phone number not found'];
    }
    
    $phone = $customer['phonenumber'];
    $customerName = $customer['fullname'];
    
    // Generate WhatsApp URL
    $whatsappUrl = generate_whatsapp_url($phone, $message);
    
    // Log to database
    $logId = log_whatsapp_message($customerId, $customerName, $phone, $message, 'pending');
    
    return [
        'success' => true,
        'whatsapp_url' => $whatsappUrl,
        'customer_name' => $customerName,
        'phone' => $phone,
        'log_id' => $logId
    ];
}

/**
 * Log WhatsApp message to database
 */
function log_whatsapp_message($customerId, $customerName, $phone, $message, $status = 'pending') {
    global $pdo;
    
    // Create table if doesn't exist
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS whatsapp_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id INT,
            customer_name VARCHAR(255),
            phone VARCHAR(20),
            message TEXT,
            sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
            response TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    } catch (Exception $e) {
        // Table might already exist
    }
    
    $stmt = $pdo->prepare("INSERT INTO whatsapp_messages 
                          (customer_id, customer_name, phone, message, status) 
                          VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$customerId, $customerName, $phone, $message, $status]);
    
    return $pdo->lastInsertId();
}

/**
 * Update WhatsApp message status
 */
function update_whatsapp_status($messageId, $status) {
    global $pdo;
    
    $stmt = $pdo->prepare("UPDATE whatsapp_messages SET status = ?, sent_at = NOW() WHERE id = ?");
    return $stmt->execute([$status, $messageId]);
}

/**
 * Get WhatsApp message history
 */
function get_whatsapp_history($limit = 100) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM whatsapp_messages ORDER BY sent_at DESC LIMIT ?");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Send bulk WhatsApp messages
 */
function send_bulk_whatsapp($customerIds, $message) {
    $results = [];
    
    foreach ($customerIds as $customerId) {
        $result = send_whatsapp_to_customer($customerId, $message);
        $results[] = array_merge(['customer_id' => $customerId], $result);
    }
    
    return $results;
}

/**
 * Get customer WhatsApp-ready numbers
 */
function get_whatsapp_customers() {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT id, fullname, phonenumber, city 
        FROM customer 
        WHERE phonenumber IS NOT NULL 
        AND phonenumber != ''
        AND LENGTH(phonenumber) >= 10
        ORDER BY fullname
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Format phone number for display
 */
function format_phone_display($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    if (strlen($phone) == 10) {
        return substr($phone, 0, 4) . '-' . substr($phone, 4, 3) . '-' . substr($phone, 7, 3);
    } elseif (strlen($phone) == 11) {
        return substr($phone, 0, 4) . '-' . substr($phone, 4, 3) . '-' . substr($phone, 7, 4);
    }
    
    return $phone;
}

/**
 * Detect Pakistani mobile carrier
 */
function detect_pakistani_carrier($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Remove country code if present
    if (substr($phone, 0, 2) == '92') {
        $phone = '0' . substr($phone, 2);
    }
    
    // Jazz/Warid: 0300-0307, 0320-0325
    if (preg_match('/^03(0[0-7]|2[0-5])/', $phone)) {
        return 'Jazz';
    }
    
    // Telenor: 0340-0347
    if (preg_match('/^034[0-7]/', $phone)) {
        return 'Telenor';
    }
    
    // Zong: 0310-0319
    if (preg_match('/^031[0-9]/', $phone)) {
        return 'Zong';
    }
    
    // Ufone: 0330-0337
    if (preg_match('/^033[0-7]/', $phone)) {
        return 'Ufone';
    }
    
    return 'Unknown';
}

/**
 * Create WhatsApp message templates
 */
function get_whatsapp_templates() {
    return [
        'greeting' => [
            'name' => 'Greeting',
            'message' => 'Hello! This is [Business Name]. Thank you for being our valued customer. 😊'
        ],
        'order_update' => [
            'name' => 'Order Update',
            'message' => 'Dear customer, your order has been processed and will be delivered soon. 📦\n\nTracking ID: [TRACKING]\nEstimated Delivery: [DATE]'
        ],
        'payment_reminder' => [
            'name' => 'Payment Reminder',
            'message' => 'Reminder: Your payment of [AMOUNT] is due. Please clear it at your earliest convenience. 💰\n\nThank you!'
        ],
        'delivery' => [
            'name' => 'Delivery Notification',
            'message' => 'Good news! Your order is out for delivery today. 🚚\n\nPlease keep your phone handy. Our delivery agent will call you before arrival.'
        ],
        'promotion' => [
            'name' => 'Promotional Offer',
            'message' => '🌟 Special Offer Just For You! 🌟\n\nGet 20% off on your next purchase!\nUse code: SAVE20\n\nValid until [DATE]'
        ],
        'measurement_reminder' => [
            'name' => 'Measurement Reminder',
            'message' => 'Dear customer, this is a reminder for your scheduled measurement appointment. Please visit our shop at your convenience. 📏'
        ],
        'pickup_ready' => [
            'name' => 'Order Ready for Pickup',
            'message' => 'Your order is ready for pickup! 🎉\n\nPlease visit our shop to collect your order.\n\nThank you for your business!'
        ]
    ];
}

/**
 * Get WhatsApp statistics
 */
function get_whatsapp_stats() {
    global $pdo;
    
    try {
        $stats = [];
        
        // Total messages
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM whatsapp_messages");
        $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        
        // Sent messages
        $stmt = $pdo->query("SELECT COUNT(*) as sent FROM whatsapp_messages WHERE status = 'sent'");
        $stats['sent'] = $stmt->fetch(PDO::FETCH_ASSOC)['sent'] ?? 0;
        
        // Failed messages
        $stmt = $pdo->query("SELECT COUNT(*) as failed FROM whatsapp_messages WHERE status = 'failed'");
        $stats['failed'] = $stmt->fetch(PDO::FETCH_ASSOC)['failed'] ?? 0;
        
        // Pending messages
        $stmt = $pdo->query("SELECT COUNT(*) as pending FROM whatsapp_messages WHERE status = 'pending'");
        $stats['pending'] = $stmt->fetch(PDO::FETCH_ASSOC)['pending'] ?? 0;
        
        // Today's messages
        $stmt = $pdo->query("SELECT COUNT(*) as today FROM whatsapp_messages WHERE DATE(sent_at) = CURDATE()");
        $stats['today'] = $stmt->fetch(PDO::FETCH_ASSOC)['today'] ?? 0;
        
        return $stats;
    } catch (Exception $e) {
        return ['total' => 0, 'sent' => 0, 'failed' => 0, 'pending' => 0, 'today' => 0];
    }
}

/**
 * Clean old WhatsApp messages (older than 90 days)
 */
function clean_old_whatsapp_messages($days = 90) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM whatsapp_messages WHERE sent_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
        return $stmt->execute([$days]);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Export WhatsApp messages to CSV
 */
function export_whatsapp_messages_csv() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT * FROM whatsapp_messages ORDER BY sent_at DESC");
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($messages)) {
        return false;
    }
    
    // Create CSV content
    $csv = "ID,Customer Name,Phone,Message,Status,Sent At\n";
    
    foreach ($messages as $msg) {
        $csv .= '"' . $msg['id'] . '",';
        $csv .= '"' . str_replace('"', '""', $msg['customer_name']) . '",';
        $csv .= '"' . $msg['phone'] . '",';
        $csv .= '"' . str_replace('"', '""', $msg['message']) . '",';
        $csv .= '"' . $msg['status'] . '",';
        $csv .= '"' . $msg['sent_at'] . "\"\n";
    }
    
    return $csv;
}

/**
 * Get customer by phone number
 */
function get_customer_by_phone($phone) {
    global $pdo;
    
    $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
    
    $stmt = $pdo->prepare("
        SELECT id, fullname, phonenumber, city, email 
        FROM customer 
        WHERE REPLACE(phonenumber, ' ', '') LIKE ?
        LIMIT 1
    ");
    $stmt->execute(['%' . $cleanPhone . '%']);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Validate WhatsApp phone number
 */
function validate_whatsapp_phone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Check if it's a valid Pakistani mobile number
    if (strlen($phone) == 10) {
        // Check prefix
        $prefix = substr($phone, 0, 3);
        $validPrefixes = ['300', '301', '302', '303', '304', '305', '306', '307',
                         '320', '321', '322', '323', '324', '325',
                         '330', '331', '332', '333', '334', '335', '336', '337',
                         '340', '341', '342', '343', '344', '345', '346', '347',
                         '310', '311', '312', '313', '314', '315', '316', '317', '318', '319'];
        
        return in_array($prefix, $validPrefixes);
    } elseif (strlen($phone) == 11 && $phone[0] == '0') {
        $prefix = substr($phone, 1, 3);
        $validPrefixes = ['300', '301', '302', '303', '304', '305', '306', '307',
                         '320', '321', '322', '323', '324', '325',
                         '330', '331', '332', '333', '334', '335', '336', '337',
                         '340', '341', '342', '343', '344', '345', '346', '347',
                         '310', '311', '312', '313', '314', '315', '316', '317', '318', '319'];
        
        return in_array($prefix, $validPrefixes);
    }
    
    return false;
}

/**
 * Send WhatsApp message via AJAX
 */
function ajax_send_whatsapp($customerId, $message) {
    $result = send_whatsapp_to_customer($customerId, $message);
    
    if ($result['success']) {
        // Return data for JavaScript to handle
        return [
            'success' => true,
            'url' => $result['whatsapp_url'],
            'customer' => $result['customer_name'],
            'phone' => $result['phone'],
            'message' => 'WhatsApp ready to send'
        ];
    }
    
    return ['success' => false, 'error' => $result['error']];
}

?>