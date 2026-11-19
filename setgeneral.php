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
include ('header.php');
?>

<style>
/* Modern Toggle Switch Styles */
.switch-container {
    display: flex;
    align-items: center;
    gap: 15px;
}

.switch {
    position: relative;
    display: inline-block;
    width: 60px;
    height: 34px;
}

.switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
    border-radius: 34px;
}

.slider:before {
    position: absolute;
    content: "";
    height: 26px;
    width: 26px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}

input:checked + .slider {
    background-color: #2196F3;
}

input:checked + .slider:before {
    transform: translateX(26px);
}

/* Toggle label */
.toggle-label {
    font-size: 14px;
    font-weight: normal;
}

.toggle-status {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    margin-left: 10px;
}

.status-hidden {
    background-color: #dc3545;
    color: white;
}

.status-visible {
    background-color: #28a745;
    color: white;
}

/* Section styling */
.settings-section {
    background: #f9f9f9;
    padding: 20px;
    margin-bottom: 25px;
    border-radius: 8px;
    border: 1px solid #e0e0e0;
}

.settings-section h4 {
    margin-top: 0;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #ddd;
    color: #333;
}

.menu-item-row {
    padding: 12px 0;
    border-bottom: 1px solid #eee;
}

.menu-item-row:last-child {
    border-bottom: none;
}

.menu-icon {
    width: 30px;
    display: inline-block;
    color: #555;
}
</style>

<div class="pageheader">
    <h2><i class="fa fa-cog"></i> General Setting</h2>
    <div class="breadcrumb-wrapper">
        <span class="label">Manage System Settings</span>
    </div>
</div>

<div class="contentpanel">
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div style="display: block;" class="panel-body panel-body-nopadding">
                    
                    <form action="" method="post" class="form-horizontal form-bordered">
                    
                    <?php
                    // Handle form submission
                    if($_POST) {
                        $sitename = $_POST["sitename"];
                        $mobile = $_POST["mobile"];
                        $email = $_POST["email"];
                        $currency = $_POST["currency"];
                        $sms = $_POST["sms"];
                        
                        // Get all menu visibility settings
                        $hide_add_order = isset($_POST["hide_add_order"]) ? 1 : 0;
                        $hide_view_orders = isset($_POST["hide_view_orders"]) ? 1 : 0;
                        $hide_add_customer = isset($_POST["hide_add_customer"]) ? 1 : 0;
                        $hide_view_customer = isset($_POST["hide_view_customer"]) ? 1 : 0;
                        $hide_sms_list = isset($_POST["hide_sms_list"]) ? 1 : 0;
                        $hide_email_list = isset($_POST["hide_email_list"]) ? 1 : 0;
                        $hide_staff_management = isset($_POST["hide_staff_management"]) ? 1 : 0;
                        $hide_expense_management = isset($_POST["hide_expense_management"]) ? 1 : 0;
                        $hide_income_management = isset($_POST["hide_income_management"]) ? 1 : 0;
                        $hide_measurement_settings = isset($_POST["hide_measurement_settings"]) ? 1 : 0;
                        $hide_inventory = isset($_POST["hide_inventory"]) ? 1 : 0;
                        $hide_customer_billing = isset($_POST["hide_customer_billing"]) ? 1 : 0;
                        
                        $res = $pdo->exec("UPDATE general_setting SET 
                            sitename='".$sitename."', 
                            mobile='".$mobile."', 
                            sms='".$sms."', 
                            email='".$email."', 
                            currency='".$currency."',
                            hide_add_order='".$hide_add_order."',
                            hide_view_orders='".$hide_view_orders."',
                            hide_add_customer='".$hide_add_customer."',
                            hide_view_customer='".$hide_view_customer."',
                            hide_sms_list='".$hide_sms_list."',
                            hide_email_list='".$hide_email_list."',
                            hide_staff_management='".$hide_staff_management."',
                            hide_expense_management='".$hide_expense_management."',
                            hide_income_management='".$hide_income_management."',
                            hide_measurement_settings='".$hide_measurement_settings."',
                            hide_inventory='".$hide_inventory."',
                            hide_customer_billing='".$hide_customer_billing."'
                            WHERE id='1'");
                            
                        if($res !== false) {
                            echo "<div class='alert alert-success alert-dismissable'>
                                    <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>    
                                    <i class='fa fa-check-circle'></i> Settings Updated Successfully!
                                  </div>";
                        } else {
                            echo "<div class='alert alert-danger alert-dismissable'>
                                    <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>    
                                    <i class='fa fa-exclamation-triangle'></i> Some Problem Occurs, Please Try Again. 
                                  </div>";
                        }
                    }
                    
                    // Fetch current settings
                    $data = $pdo->query("SELECT * FROM general_setting WHERE id='1'");
                    $data = $data->fetch(PDO::FETCH_ASSOC);
                    ?>
                    
                    <!-- Basic Settings Section -->
                    <div class="settings-section">
                        <h4><i class="fa fa-building"></i> Company Information</h4>
                        
                        <div class="form-group">
                            <label class="col-sm-3 control-label">COMPANY NAME</label>
                            <div class="col-sm-6">
                                <input name="sitename" value="<?php echo $data['sitename']; ?>" class="form-control" type="text">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="col-sm-3 control-label">Mobile</label>
                            <div class="col-sm-6">
                                <input name="mobile" value="<?php echo $data['mobile']; ?>" class="form-control" type="text">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="col-sm-3 control-label">Email</label>
                            <div class="col-sm-6">
                                <input name="email" value="<?php echo $data['email']; ?>" class="form-control" type="text">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="col-sm-3 control-label">Currency Symbol</label>
                            <div class="col-sm-6">
                                <input name="currency" value="<?php echo $data['currency']; ?>" class="form-control" type="text">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sidebar Menu Visibility Section with Toggle Switches -->
                    <div class="settings-section">
                        <h4><i class="fa fa-eye-slash"></i> Sidebar Menu Visibility</h4>
                        <p class="help-block" style="margin-bottom: 20px;">
                            <i class="fa fa-info-circle"></i> Toggle the switch ON to hide the menu item from the sidebar. Toggle OFF to show it.
                        </p>
                        
                        <!-- Hide Add Order -->
                        <div class="menu-item-row">
                            <div class="row">
                                <div class="col-sm-4">
                                    <strong><i class="fa fa-shopping-cart menu-icon"></i> Add Order</strong>
                                </div>
                                <div class="col-sm-8">
                                    <div class="switch-container">
                                        <label class="switch">
                                            <input type="checkbox" name="hide_add_order" value="1" 
                                                onchange="updateStatus(this, 'Add Order')"
                                                <?php echo (isset($data['hide_add_order']) && $data['hide_add_order'] == 1) ? 'checked' : ''; ?>>
                                            <span class="slider"></span>
                                        </label>
                                        <span class="toggle-label">
                                            <?php echo (isset($data['hide_add_order']) && $data['hide_add_order'] == 1) ? 'Hidden' : 'Visible'; ?>
                                        </span>
                                        <span class="toggle-status <?php echo (isset($data['hide_add_order']) && $data['hide_add_order'] == 1) ? 'status-hidden' : 'status-visible'; ?>">
                                            <?php echo (isset($data['hide_add_order']) && $data['hide_add_order'] == 1) ? 'HIDDEN' : 'VISIBLE'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Hide View/Edit Orders -->
                        <div class="menu-item-row">
                            <div class="row">
                                <div class="col-sm-4">
                                    <strong><i class="fa fa-list-alt menu-icon"></i> View/Edit Orders</strong>
                                </div>
                                <div class="col-sm-8">
                                    <div class="switch-container">
                                        <label class="switch">
                                            <input type="checkbox" name="hide_view_orders" value="1" 
                                                onchange="updateStatus(this, 'View Orders')"
                                                <?php echo (isset($data['hide_view_orders']) && $data['hide_view_orders'] == 1) ? 'checked' : ''; ?>>
                                            <span class="slider"></span>
                                        </label>
                                        <span class="toggle-label">
                                            <?php echo (isset($data['hide_view_orders']) && $data['hide_view_orders'] == 1) ? 'Hidden' : 'Visible'; ?>
                                        </span>
                                        <span class="toggle-status <?php echo (isset($data['hide_view_orders']) && $data['hide_view_orders'] == 1) ? 'status-hidden' : 'status-visible'; ?>">
                                            <?php echo (isset($data['hide_view_orders']) && $data['hide_view_orders'] == 1) ? 'HIDDEN' : 'VISIBLE'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Hide Add Customer -->
                        <div class="menu-item-row">
                            <div class="row">
                                <div class="col-sm-4">
                                    <strong><i class="fa fa-user-plus menu-icon"></i> Add Customer</strong>
                                </div>
                                <div class="col-sm-8">
                                    <div class="switch-container">
                                        <label class="switch">
                                            <input type="checkbox" name="hide_add_customer" value="1" 
                                                onchange="updateStatus(this, 'Add Customer')"
                                                <?php echo (isset($data['hide_add_customer']) && $data['hide_add_customer'] == 1) ? 'checked' : ''; ?>>
                                            <span class="slider"></span>
                                        </label>
                                        <span class="toggle-label">
                                            <?php echo (isset($data['hide_add_customer']) && $data['hide_add_customer'] == 1) ? 'Hidden' : 'Visible'; ?>
                                        </span>
                                        <span class="toggle-status <?php echo (isset($data['hide_add_customer']) && $data['hide_add_customer'] == 1) ? 'status-hidden' : 'status-visible'; ?>">
                                            <?php echo (isset($data['hide_add_customer']) && $data['hide_add_customer'] == 1) ? 'HIDDEN' : 'VISIBLE'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Hide View/Edit Customer -->
                        <div class="menu-item-row">
                            <div class="row">
                                <div class="col-sm-4">
                                    <strong><i class="fa fa-users menu-icon"></i> View/Edit Customer</strong>
                                </div>
                                <div class="col-sm-8">
                                    <div class="switch-container">
                                        <label class="switch">
                                            <input type="checkbox" name="hide_view_customer" value="1" 
                                                onchange="updateStatus(this, 'View Customer')"
                                                <?php echo (isset($data['hide_view_customer']) && $data['hide_view_customer'] == 1) ? 'checked' : ''; ?>>
                                            <span class="slider"></span>
                                        </label>
                                        <span class="toggle-label">
                                            <?php echo (isset($data['hide_view_customer']) && $data['hide_view_customer'] == 1) ? 'Hidden' : 'Visible'; ?>
                                        </span>
                                        <span class="toggle-status <?php echo (isset($data['hide_view_customer']) && $data['hide_view_customer'] == 1) ? 'status-hidden' : 'status-visible'; ?>">
                                            <?php echo (isset($data['hide_view_customer']) && $data['hide_view_customer'] == 1) ? 'HIDDEN' : 'VISIBLE'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Hide SENT MESSAGES -->
                        <div class="menu-item-row">
                            <div class="row">
                                <div class="col-sm-4">
                                    <strong><i class="fa fa-envelope-o menu-icon"></i> SENT MESSAGES</strong>
                                </div>
                                <div class="col-sm-8">
                                    <div class="switch-container">
                                        <label class="switch">
                                            <input type="checkbox" name="hide_sms_list" value="1" 
                                                onchange="updateStatus(this, 'SMS Messages')"
                                                <?php echo (isset($data['hide_sms_list']) && $data['hide_sms_list'] == 1) ? 'checked' : ''; ?>>
                                            <span class="slider"></span>
                                        </label>
                                        <span class="toggle-label">
                                            <?php echo (isset($data['hide_sms_list']) && $data['hide_sms_list'] == 1) ? 'Hidden' : 'Visible'; ?>
                                        </span>
                                        <span class="toggle-status <?php echo (isset($data['hide_sms_list']) && $data['hide_sms_list'] == 1) ? 'status-hidden' : 'status-visible'; ?>">
                                            <?php echo (isset($data['hide_sms_list']) && $data['hide_sms_list'] == 1) ? 'HIDDEN' : 'VISIBLE'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Hide SENT EMAILS -->
                        <div class="menu-item-row">
                            <div class="row">
                                <div class="col-sm-4">
                                    <strong><i class="fa fa-envelope menu-icon"></i> SENT EMAILS</strong>
                                </div>
                                <div class="col-sm-8">
                                    <div class="switch-container">
                                        <label class="switch">
                                            <input type="checkbox" name="hide_email_list" value="1" 
                                                onchange="updateStatus(this, 'Emails')"
                                                <?php echo (isset($data['hide_email_list']) && $data['hide_email_list'] == 1) ? 'checked' : ''; ?>>
                                            <span class="slider"></span>
                                        </label>
                                        <span class="toggle-label">
                                            <?php echo (isset($data['hide_email_list']) && $data['hide_email_list'] == 1) ? 'Hidden' : 'Visible'; ?>
                                        </span>
                                        <span class="toggle-status <?php echo (isset($data['hide_email_list']) && $data['hide_email_list'] == 1) ? 'status-hidden' : 'status-visible'; ?>">
                                            <?php echo (isset($data['hide_email_list']) && $data['hide_email_list'] == 1) ? 'HIDDEN' : 'VISIBLE'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Hide Staff Management Section -->
                        <div class="menu-item-row">
                            <div class="row">
                                <div class="col-sm-4">
                                    <strong><i class="fa fa-users menu-icon"></i> Staff Management (Full Section)</strong>
                                </div>
                                <div class="col-sm-8">
                                    <div class="switch-container">
                                        <label class="switch">
                                            <input type="checkbox" name="hide_staff_management" value="1" 
                                                onchange="updateStatus(this, 'Staff Management')"
                                                <?php echo (isset($data['hide_staff_management']) && $data['hide_staff_management'] == 1) ? 'checked' : ''; ?>>
                                            <span class="slider"></span>
                                        </label>
                                        <span class="toggle-label">
                                            <?php echo (isset($data['hide_staff_management']) && $data['hide_staff_management'] == 1) ? 'Hidden' : 'Visible'; ?>
                                        </span>
                                        <span class="toggle-status <?php echo (isset($data['hide_staff_management']) && $data['hide_staff_management'] == 1) ? 'status-hidden' : 'status-visible'; ?>">
                                            <?php echo (isset($data['hide_staff_management']) && $data['hide_staff_management'] == 1) ? 'HIDDEN' : 'VISIBLE'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Hide Expense Management Section -->
                        <div class="menu-item-row">
                            <div class="row">
                                <div class="col-sm-4">
                                    <strong><i class="fa fa-credit-card menu-icon"></i> Expense Management (Full Section)</strong>
                                </div>
                                <div class="col-sm-8">
                                    <div class="switch-container">
                                        <label class="switch">
                                            <input type="checkbox" name="hide_expense_management" value="1" 
                                                onchange="updateStatus(this, 'Expense Management')"
                                                <?php echo (isset($data['hide_expense_management']) && $data['hide_expense_management'] == 1) ? 'checked' : ''; ?>>
                                            <span class="slider"></span>
                                        </label>
                                        <span class="toggle-label">
                                            <?php echo (isset($data['hide_expense_management']) && $data['hide_expense_management'] == 1) ? 'Hidden' : 'Visible'; ?>
                                        </span>
                                        <span class="toggle-status <?php echo (isset($data['hide_expense_management']) && $data['hide_expense_management'] == 1) ? 'status-hidden' : 'status-visible'; ?>">
                                            <?php echo (isset($data['hide_expense_management']) && $data['hide_expense_management'] == 1) ? 'HIDDEN' : 'VISIBLE'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Hide Income Management Section -->
                        <div class="menu-item-row">
                            <div class="row">
                                <div class="col-sm-4">
                                    <strong><i class="fa fa-money menu-icon"></i> Income Management (Full Section)</strong>
                                </div>
                                <div class="col-sm-8">
                                    <div class="switch-container">
                                        <label class="switch">
                                            <input type="checkbox" name="hide_income_management" value="1" 
                                                onchange="updateStatus(this, 'Income Management')"
                                                <?php echo (isset($data['hide_income_management']) && $data['hide_income_management'] == 1) ? 'checked' : ''; ?>>
                                            <span class="slider"></span>
                                        </label>
                                        <span class="toggle-label">
                                            <?php echo (isset($data['hide_income_management']) && $data['hide_income_management'] == 1) ? 'Hidden' : 'Visible'; ?>
                                        </span>
                                        <span class="toggle-status <?php echo (isset($data['hide_income_management']) && $data['hide_income_management'] == 1) ? 'status-hidden' : 'status-visible'; ?>">
                                            <?php echo (isset($data['hide_income_management']) && $data['hide_income_management'] == 1) ? 'HIDDEN' : 'VISIBLE'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Hide Measurement Settings Section -->
                        <div class="menu-item-row">
                            <div class="row">
                                <div class="col-sm-4">
                                    <strong><i class="fa fa-sliders menu-icon"></i> Measurement Settings (Full Section)</strong>
                                </div>
                                <div class="col-sm-8">
                                    <div class="switch-container">
                                        <label class="switch">
                                            <input type="checkbox" name="hide_measurement_settings" value="1" 
                                                onchange="updateStatus(this, 'Measurement Settings')"
                                                <?php echo (isset($data['hide_measurement_settings']) && $data['hide_measurement_settings'] == 1) ? 'checked' : ''; ?>>
                                            <span class="slider"></span>
                                        </label>
                                        <span class="toggle-label">
                                            <?php echo (isset($data['hide_measurement_settings']) && $data['hide_measurement_settings'] == 1) ? 'Hidden' : 'Visible'; ?>
                                        </span>
                                        <span class="toggle-status <?php echo (isset($data['hide_measurement_settings']) && $data['hide_measurement_settings'] == 1) ? 'status-hidden' : 'status-visible'; ?>">
                                            <?php echo (isset($data['hide_measurement_settings']) && $data['hide_measurement_settings'] == 1) ? 'HIDDEN' : 'VISIBLE'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- NEW: Hide Inventory Section -->
                        <div class="menu-item-row">
                            <div class="row">
                                <div class="col-sm-4">
                                    <strong><i class="fa fa-cubes menu-icon"></i> Inventory (Full Section)</strong>
                                </div>
                                <div class="col-sm-8">
                                    <div class="switch-container">
                                        <label class="switch">
                                            <input type="checkbox" name="hide_inventory" value="1" 
                                                onchange="updateStatus(this, 'Inventory')"
                                                <?php echo (isset($data['hide_inventory']) && $data['hide_inventory'] == 1) ? 'checked' : ''; ?>>
                                            <span class="slider"></span>
                                        </label>
                                        <span class="toggle-label">
                                            <?php echo (isset($data['hide_inventory']) && $data['hide_inventory'] == 1) ? 'Hidden' : 'Visible'; ?>
                                        </span>
                                        <span class="toggle-status <?php echo (isset($data['hide_inventory']) && $data['hide_inventory'] == 1) ? 'status-hidden' : 'status-visible'; ?>">
                                            <?php echo (isset($data['hide_inventory']) && $data['hide_inventory'] == 1) ? 'HIDDEN' : 'VISIBLE'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- NEW: Hide Customer Billing Section -->
                        <div class="menu-item-row">
                            <div class="row">
                                <div class="col-sm-4">
                                    <strong><i class="fa fa-file-text-o menu-icon"></i> Customer Billing (Full Section)</strong>
                                </div>
                                <div class="col-sm-8">
                                    <div class="switch-container">
                                        <label class="switch">
                                            <input type="checkbox" name="hide_customer_billing" value="1" 
                                                onchange="updateStatus(this, 'Customer Billing')"
                                                <?php echo (isset($data['hide_customer_billing']) && $data['hide_customer_billing'] == 1) ? 'checked' : ''; ?>>
                                            <span class="slider"></span>
                                        </label>
                                        <span class="toggle-label">
                                            <?php echo (isset($data['hide_customer_billing']) && $data['hide_customer_billing'] == 1) ? 'Hidden' : 'Visible'; ?>
                                        </span>
                                        <span class="toggle-status <?php echo (isset($data['hide_customer_billing']) && $data['hide_customer_billing'] == 1) ? 'status-hidden' : 'status-visible'; ?>">
                                            <?php echo (isset($data['hide_customer_billing']) && $data['hide_customer_billing'] == 1) ? 'HIDDEN' : 'VISIBLE'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- SMS API Section -->
                    <div class="settings-section">
                        <h4><i class="fa fa-mobile"></i> SMS API Configuration</h4>
                        
                        <div class="form-group">
                            <label class="col-sm-3 control-label">SMS API URL</label>
                            <div class="col-sm-9">
                                <textarea rows="4" name="sms" class="form-control"><?php echo $data['sms']; ?></textarea>
                                <p class="help-block" style="margin-top: 10px;">
                                    <i class="fa fa-info-circle"></i> Replace recipient number with <strong>[TO]</strong> and message with <strong>[MESSAGE]</strong>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                </div><!-- panel-body -->
                
                <div style="display: block;" class="panel-footer">
                    <div class="row">
                        <div class="col-sm-12 text-center">
                            <button class="btn btn-primary btn-lg">
                                <i class="fa fa-save"></i> Save All Settings
                            </button>
                            <button type="reset" class="btn btn-default btn-lg">
                                <i class="fa fa-refresh"></i> Reset
                            </button>
                        </div>
                    </div>
                    </form>
                </div><!-- panel-footer -->
            </div><!-- panel -->
        </div><!-- col-md-12 -->
    </div><!-- row -->
</div><!-- contentpanel -->

<script>
// Function to update status display when toggle changes
function updateStatus(toggle, menuName) {
    var container = $(toggle).closest('.menu-item-row');
    var statusSpan = container.find('.toggle-status');
    var labelSpan = container.find('.toggle-label');
    
    if($(toggle).is(':checked')) {
        statusSpan.removeClass('status-visible').addClass('status-hidden').text('HIDDEN');
        labelSpan.text('Hidden');
    } else {
        statusSpan.removeClass('status-hidden').addClass('status-visible').text('VISIBLE');
        labelSpan.text('Visible');
    }
}

$(document).ready(function() {
    // Auto-update status on toggle change
    $('.switch input').on('change', function() {
        var container = $(this).closest('.menu-item-row');
        var statusSpan = container.find('.toggle-status');
        var labelSpan = container.find('.toggle-label');
        
        if($(this).is(':checked')) {
            statusSpan.removeClass('status-visible').addClass('status-hidden').text('HIDDEN');
            labelSpan.text('Hidden');
        } else {
            statusSpan.removeClass('status-hidden').addClass('status-visible').text('VISIBLE');
            labelSpan.text('Visible');
        }
    });
});
</script>

<?php include ('footer.php'); ?>