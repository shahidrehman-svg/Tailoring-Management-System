<?php
require_once('function.php');
dbconnect();
session_start();

if (!is_user()) {
    redirect('index.php');
}

// Fix header issue by not including header.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Auto Send WhatsApp - BHATTI CLOTH & TAILOR</title>
  
  <link href="css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <script src="js/jquery-1.11.1.min.js"></script>
  <script src="js/bootstrap.min.js"></script>
  
  <style>
  body { background: #f5f5f5; font-family: 'Segoe UI'; }
  .container { max-width: 1400px; margin: 20px auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
  .header { background: #25D366; color: white; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
  .panel { border: 1px solid #ddd; border-radius: 5px; margin-bottom: 20px; }
  .panel-heading { background: #075E54; color: white; padding: 10px 15px; border-radius: 5px 5px 0 0; }
  .panel-body { padding: 15px; }
  .whatsapp-preview { background: #dcf8c6; border: 1px solid #a5d6a7; padding: 15px; border-radius: 5px; min-height: 100px; margin: 10px 0; }
  .customer-table { max-height: 400px; overflow-y: auto; }
  .btn-whatsapp { background: #25D366; color: white; border: none; }
  .btn-whatsapp:hover { background: #1da851; color: white; }
  #progressBar { transition: width 0.5s; }
  </style>
</head>
<body>

<div class="container">
  <div class="header text-center">
    <h1><i class="fab fa-whatsapp"></i> Auto Send WhatsApp Messages</h1>
    <p>Messages will be sent automatically after WhatsApp Web opens</p>
    <div class="text-right">
      <a href="home.php" class="btn btn-default">← Dashboard</a>
    </div>
  </div>
  
  <?php
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_bulk'])) {
      $customerIds = $_POST['customers'] ?? [];
      $message = $_POST['message'] ?? '';
      
      if (empty($customerIds) || empty($message)) {
          echo "<div class='alert alert-danger'>Please select customers and enter a message!</div>";
      } else {
          // Store in session
          $_SESSION['whatsapp_data'] = [
              'customer_ids' => $customerIds,
              'message' => $message,
              'current_index' => 0,
              'total' => count($customerIds)
          ];
          
          echo "<div class='alert alert-success'>
                  <h4><i class='fa fa-check'></i> Ready to Send!</h4>
                  <p>Selected: " . count($customerIds) . " customers</p>
                  <button class='btn btn-whatsapp btn-lg' onclick='startAutoSend()'>
                    <i class='fab fa-whatsapp'></i> Start Auto Sending
                  </button>
                  <div class='progress' style='margin-top: 20px;'>
                    <div id='progressBar' class='progress-bar progress-bar-striped' style='width: 0%'></div>
                  </div>
                  <div id='statusText' class='mt-2'></div>
                </div>";
      }
  }
  ?>
  
  <div class="row">
    <!-- Customer Selection -->
    <div class="col-md-6">
      <div class="panel">
        <div class="panel-heading">
          <h4><i class="fa fa-users"></i> Select Customers</h4>
        </div>
        <div class="panel-body customer-table">
          <table class="table table-bordered table-hover">
            <thead>
              <tr>
                <th><input type="checkbox" id="selectAll"> All</th>
                <th>Customer</th>
                <th>Phone</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $customers = $pdo->query("SELECT id, fullname, phonenumber FROM customer WHERE phonenumber IS NOT NULL AND phonenumber != '' ORDER BY fullname");
              while ($cust = $customers->fetch(PDO::FETCH_ASSOC)) {
                  echo "<tr>
                          <td><input type='checkbox' name='customers[]' value='{$cust['id']}' class='customer-check' data-phone='{$cust['phonenumber']}' data-name='{$cust['fullname']}'></td>
                          <td>{$cust['fullname']}</td>
                          <td>{$cust['phonenumber']}</td>
                        </tr>";
              }
              ?>
            </tbody>
          </table>
          <p><strong>Selected: <span id="selectedCount">0</span> customers</strong></p>
        </div>
      </div>
    </div>
    
    <!-- Message Box -->
    <div class="col-md-6">
      <div class="panel">
        <div class="panel-heading">
          <h4><i class="fa fa-comment"></i> Compose Message</h4>
        </div>
        <div class="panel-body">
          <form method="post" id="whatsappForm">
            <div class="form-group">
              <label>Quick Templates</label>
              <select class="form-control" onchange="applyTemplate(this.value)">
                <option value="">Select Template</option>
                <option value="order_ready">Order Ready</option>
                <option value="payment">Payment Reminder</option>
                <option value="delivery">Delivery Update</option>
                <option value="measurement">Measurement Reminder</option>
              </select>
            </div>
            
            <div class="form-group">
              <label>Your Message <span class="badge" id="charCount">0</span></label>
              <textarea name="message" id="message" class="form-control" rows="8" required></textarea>
            </div>
            
            <div class="form-group">
              <label>Preview:</label>
              <div class="whatsapp-preview" id="messagePreview"></div>
            </div>
            
            <button type="submit" name="send_bulk" class="btn btn-whatsapp btn-block btn-lg">
              <i class="fab fa-whatsapp"></i> Prepare WhatsApp Messages
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// Auto-send function
function startAutoSend() {
    $('#statusText').html('<i class="fa fa-spinner fa-spin"></i> Starting...');
    
    // Get first customer
    $.ajax({
        url: 'whatsapp_auto_send_handler.php',
        type: 'POST',
        data: { action: 'get_next_customer' },
        success: function(response) {
            var data = JSON.parse(response);
            if (data.success) {
                // Update progress
                var percent = (data.current_index / data.total) * 100;
                $('#progressBar').css('width', percent + '%');
                $('#statusText').html('<i class="fa fa-spinner fa-spin"></i> Opening WhatsApp for ' + data.customer_name + '...');
                
                // Open WhatsApp Web
                window.open(data.whatsapp_url, '_blank');
                
                // Auto-send after 8 seconds (time for WhatsApp to load)
                setTimeout(function() {
                    // This JavaScript will be injected to auto-send
                    var autoSendScript = `
                        // Wait for WhatsApp to load
                        setTimeout(function() {
                            // Find send button and click it
                            var sendButton = document.querySelector('button[data-tab="10"]');
                            if (sendButton) {
                                sendButton.click();
                                window.close(); // Close tab after sending
                            }
                        }, 3000);
                    `;
                    
                    // Open new tab with auto-send script
                    var newTab = window.open('javascript:(function(){' + encodeURIComponent(autoSendScript) + '})()', '_blank');
                    
                    // After 10 seconds, send next customer
                    setTimeout(function() {
                        sendNextCustomer();
                    }, 10000);
                    
                }, 8000);
            } else {
                $('#statusText').html('<div class="alert alert-danger">Error: ' + data.error + '</div>');
            }
        }
    });
}

function sendNextCustomer() {
    $.ajax({
        url: 'whatsapp_auto_send_handler.php',
        type: 'POST',
        data: { action: 'get_next_customer' },
        success: function(response) {
            var data = JSON.parse(response);
            if (data.success) {
                if (data.finished) {
                    $('#statusText').html('<div class="alert alert-success"><i class="fa fa-check"></i> All messages sent successfully!</div>');
                    $('#progressBar').css('width', '100%').removeClass('progress-bar-striped');
                } else {
                    // Update progress
                    var percent = (data.current_index / data.total) * 100;
                    $('#progressBar').css('width', percent + '%');
                    $('#statusText').html('<i class="fa fa-spinner fa-spin"></i> Sending to ' + data.customer_name + '... (' + data.current_index + '/' + data.total + ')');
                    
                    // Open WhatsApp Web for next customer
                    window.open(data.whatsapp_url, '_blank');
                    
                    // Auto-send after 8 seconds
                    setTimeout(function() {
                        sendNextCustomer();
                    }, 8000);
                }
            }
        }
    });
}

// Form handling
$('#selectAll').change(function() {
    $('.customer-check').prop('checked', this.checked);
    updateSelectedCount();
});

function updateSelectedCount() {
    var count = $('.customer-check:checked').length;
    $('#selectedCount').text(count);
}

$('#message').on('input', function() {
    var length = $(this).val().length;
    $('#charCount').text(length);
    $('#messagePreview').html($(this).val().replace(/\n/g, '<br>'));
});

function applyTemplate(template) {
    var templates = {
        'order_ready': '🎉 **ORDER READY!** 🎉\n\nDear Customer,\n\nYour order is ready for collection! Please visit our shop to pick it up.\n\n📍 Shop Address: [Your Address]\n🕒 Timing: 9 AM - 9 PM\n\nThank you for your business!\n\nBHATTI CLOTH & TAILOR',
        'payment': '💰 **PAYMENT REMINDER** 💰\n\nDear Customer,\n\nThis is a friendly reminder about your pending payment. Please clear your outstanding amount at your earliest convenience.\n\nThank you for your cooperation!\n\nBHATTI CLOTH & TAILOR',
        'delivery': '🚚 **DELIVERY UPDATE** 🚚\n\nDear Customer,\n\nYour order is out for delivery today! Please keep your phone handy. Our delivery agent will call you before arrival.\n\nThank you!\n\nBHATTI CLOTH & TAILOR',
        'measurement': '📏 **MEASUREMENT REMINDER** 📏\n\nDear Customer,\n\nThis is a reminder for your scheduled measurement appointment. Please visit our shop at your convenience.\n\n📍 Shop Address: [Your Address]\n🕒 Timing: 9 AM - 9 PM\n\nThank you!\n\nBHATTI CLOTH & TAILOR'
    };
    
    if (templates[template]) {
        $('#message').val(templates[template]);
        $('#message').trigger('input');
    }
}

$('#whatsappForm').submit(function(e) {
    var selected = $('.customer-check:checked').length;
    var message = $('#message').val().trim();
    
    if (selected === 0) {
        alert('Please select customers!');
        e.preventDefault();
        return false;
    }
    
    if (message.length === 0) {
        alert('Please enter a message!');
        e.preventDefault();
        return false;
    }
    
    if (!confirm('This will send WhatsApp messages to ' + selected + ' customers. Continue?')) {
        e.preventDefault();
        return false;
    }
    
    return true;
});

$(document).ready(function() {
    $('.customer-check').change(updateSelectedCount);
    updateSelectedCount();
    $('#messagePreview').html('Message preview will appear here...');
});
</script>

</body>
</html>