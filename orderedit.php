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
?>

<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Update Order</h1>
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->
    <div class="row">

        <div class="col-md-10 col-md-offset-1">

            <?php
            $eid = $_GET["id"];

            if ($_POST) {

                $customer = $_POST["customer"];
                $desc = $_POST["desc"];
                $date_received = $_POST["date_received"];
                $completed = $_POST["completed"];
                $date_collected = $_POST["date_collected"];
                $amount = $_POST["amount"];
                $paid = $_POST["paid"];
                $balance = $_POST["balance"];
                $received_by = $_POST["received_by"];

                // Calculate balance if not posted (same as orderadd.php)
                if (!isset($balance) || $balance === '') {
                    $balance = $amount - $paid;
                }

                $name = $pdo->query("SELECT fullname FROM customer WHERE id='" . $customer . "'");
                $name = $name->fetch(PDO::FETCH_ASSOC);
                $name = $name['fullname'] . ": " . substr($desc, 0, 100);

                if ($completed == 'No') {
                    $color = '#a00000';
                } else {
                    $color = '#00a014';
                }

                ///////////////////////-------------------->> Catid  ki 0??
                $error = 0;

                if ($customer == 0) {
                    $err1 = 1;
                }
                if (isset($err1))
                    $error = $err1;;


                if (!isset($error) || $error == 0) {

                    // FIXED: Use backticks for 'order' table and prepared statement
                    $stmt = $pdo->prepare("UPDATE `order` SET customer=?, description=?, received_by=?, amount=?, paid=?, balance=?, date_received=?, completed=?, date_collected=? WHERE id=?");
                    $res = $stmt->execute([$customer, $desc, $received_by, $amount, $paid, $balance, $date_received, $completed, $date_collected, $eid]);

                    // FIXED: The WHERE clause should reference a column that links to order ID, not 'order'
                    // Assuming there's a column like 'order_id' or 'reference_id' in calendar table
                    $stmt2 = $pdo->prepare("UPDATE calendar SET title=?, description=?, start=?, end=?, allDay='true', color=?, url=?, category='Orders', user_id=? WHERE url LIKE ?");
                    $url_pattern = "%orderedit.php?id=" . $eid . "%";
                    $res2 = $stmt2->execute([$name, $desc, $date_received, $date_collected, $color, "../orderedit.php?id=$eid", $uid, $url_pattern]);

                    if ($res) {
                        echo "<div class='alert alert-success alert-dismissable'>
                            <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>	
                            UPDATED Successfully!
                            </div>";
                    } else {
                        echo "<div class='alert alert-danger alert-dismissable'>
                            <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>	
                            Some Problem Occurs, Please Try Again. 
                            </div>";
                    }
                }
            }
            ?>

            <script>
                $(function() {
                    $("#datepicker").datepicker();
                });

                // Function to calculate balance - SAME AS orderadd.php
                function calculateBalance() {
                    var amount = document.getElementById('amount');
                    var paid = document.getElementById('paid');
                    
                    // Check if elements exist before using them
                    if (!amount || !paid) {
                        console.error('Amount or Paid field not found');
                        return;
                    }
                    
                    var amountValue = amount.value;
                    var paidValue = paid.value;
                    
                    // Convert to numbers, handle empty values
                    amountValue = amountValue ? parseFloat(amountValue) : 0;
                    paidValue = paidValue ? parseFloat(paidValue) : 0;
                    
                    // Calculate balance
                    var balance = amountValue - paidValue;
                    
                    // Update balance field
                    var balanceField = document.getElementById('balance');
                    if (balanceField) {
                        balanceField.value = balance.toFixed(2);
                        
                        // Optional: Add color coding for balance
                        if (balance > 0) {
                            balanceField.style.color = '#d9534f'; // Red for positive balance (amount due)
                        } else if (balance < 0) {
                            balanceField.style.color = '#f0ad4e'; // Orange for overpayment
                        } else {
                            balanceField.style.color = '#5cb85c'; // Green for fully paid
                        }
                    }
                }

                // Add event listeners to amount and paid fields - SAME AS orderadd.php
                document.addEventListener('DOMContentLoaded', function() {
                    // Check if elements exist before adding event listeners
                    var amountField = document.getElementById('amount');
                    var paidField = document.getElementById('paid');
                    
                    if (amountField) {
                        amountField.addEventListener('input', calculateBalance);
                        amountField.addEventListener('change', calculateBalance);
                    } else {
                        console.error('Amount field not found');
                    }
                    
                    if (paidField) {
                        paidField.addEventListener('input', calculateBalance);
                        paidField.addEventListener('change', calculateBalance);
                    } else {
                        console.error('Paid field not found');
                    }
                    
                    // Calculate initial balance on page load
                    calculateBalance();
                });
            </script>

            <?php
            // FIXED: Use backticks for 'order' table
            $stmt = $pdo->prepare("SELECT * FROM `order` WHERE id = ?");
            $stmt->execute([$eid]);
            $old = $stmt->fetch(PDO::FETCH_ASSOC);

            // Calculate balance for display - same logic as orderadd.php
            $balance_display = isset($old['balance']) ? $old['balance'] : ($old['amount'] - $old['paid']);
            
            // Get current date for default value
            $currentDate = date('Y-m-d');
            
            // Use current date for the field value (not the stored date)
            $date_received_value = $currentDate;
            ?>

            <form action="orderedit.php?id=<?php echo $eid ?>" method="post">

                <div class="form-group">
                    <label>Select Customer</label>
                    <select name="customer" id="customer_name" class="form-control">
                        <option value="0">Please Select a Customer</option>
                        <?php
                        $ddaa = $pdo->query("SELECT id, fullname FROM customer ORDER BY fullname");
                        while ($data = $ddaa->fetch(PDO::FETCH_ASSOC)) {
                            $selected = ($old['customer'] == $data['id']) ? 'selected="selected"' : '';
                            echo "<option value='$data[id]' $selected>" . htmlspecialchars($data['fullname']) . "</option>";
                        }
                        ?>
                    </select><br />
                </div>

                <div class="form-group">
                    <label>Description</label><br />
                    <input type="text" name="desc" style="width:200px; height: 40px;" value="<?php echo htmlspecialchars($old['description']) ?>" /><br /><br />
                </div>

                <div class="form-group">
                    <label>Received By</label>
                    <select name="received_by" class="form-control">
                        <?php
                        $ddaa = $pdo->query("SELECT id, fullname FROM staff ORDER BY id");
                        while ($data = $ddaa->fetch(PDO::FETCH_ASSOC)) {
                            if ($old['received_by'] == $data['id']) {
                                echo "<option value='$data[id]' selected='selected'>" . htmlspecialchars($data['fullname']) . "</option>";
                            } else {
                                echo "<option value='$data[id]'>" . htmlspecialchars($data['fullname']) . "</option>";
                            }
                        }
                        ?>
                    </select><br />
                </div>

                <div class="form-group">
                    <label>Amount</label><br />
                    <?php echo ($currency); ?> <input type="text" id="amount" name="amount" style="width:200px; height: 40px;" value="<?php echo htmlspecialchars($old['amount']) ?>" /><br /><br />
                </div>

                <div class="form-group">
                    <label>Paid</label><br />
                    <?php echo ($currency); ?> <input type="text" id="paid" name="paid" style="width:200px; height: 40px;" value="<?php echo htmlspecialchars($old['paid']) ?>" /><br /><br />
                </div>

                <!-- BALANCE FIELD - SAME AS orderadd.php -->
                <div class="form-group">
                    <label>Balance (Amount - Paid)</label><br />
                    <?php echo ($currency); ?> <input type="text" id="balance" name="balance" style="width:200px; height: 40px;" value="<?php echo number_format($balance_display, 2) ?>" readonly /><br /><br />
                </div>

                <div class="form-group">
                    <label>Date Received</label><br />
                    <input type="date" name="date_received" style="width:200px; height: 40px;" value="<?php echo $date_received_value; ?>" /><br />
                    <small class="text-muted">Previous date: <?php echo !empty($old['date_received']) ? date('d-m-Y', strtotime($old['date_received'])) : 'Not set'; ?></small>
                    <br /><br />
                </div>

                <div class="form-group">
                    <label>Completed?</label><br />
                    <select name="completed" class="form-control">
                        <option value='No' <?php echo ($old['completed'] == 'No') ? 'selected' : ''; ?>>No</option>
                        <option value='Yes' <?php echo ($old['completed'] == 'Yes') ? 'selected' : ''; ?>>Yes</option>
                    </select>
                    <br /><br />
                </div>

                <div class="form-group">
                    <label>Date to Collect</label><br />
                    <input type="date" name="date_collected" style="width:200px; height: 40px;" value="<?php echo htmlspecialchars($old['date_collected']); ?>" /><br /><br />
                </div>
                
                <input type="submit" class="btn btn-lg btn-success btn-block" value="Update">
            </form>
        </div>
    </div>
    <!-- /.row -->
</div>
<!-- /#page-wrapper -->

<script src="js/bootstrap-timepicker.min.js"></script>

<script>
    jQuery(document).ready(function() {
        jQuery("#ssn").mask("999-99-9999");

        // Time Picker
        jQuery('#timepicker').timepicker({
            defaultTIme: false
        });
        jQuery('#timepicker2').timepicker({
            showMeridian: false
        });
        jQuery('#timepicker3').timepicker({
            minuteStep: 15
        });
    });
</script>

<?php
include('footer.php');
?>