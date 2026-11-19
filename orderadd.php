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
            <h1 class="page-header">Add Order</h1>
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->
    <div class="row">

        <div class="col-md-10 col-md-offset-1">

            <?php

            if ($_POST) {

                $customer = $_POST["customer"];
                $desc = $_POST["desc"];
                // Use current date if date_received is empty, otherwise use the posted value
                $date_received = !empty($_POST["date_received"]) ? $_POST["date_received"] : date('Y-m-d');
                $completed = $_POST["completed"];
                $date_collected = $_POST["date_collected"];
                $amount = $_POST["amount"];
                $paid = $_POST["paid"];
                $balance = $_POST["balance"];
                $received_by = $_POST["received_by"];

                // Calculate balance if not posted
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

                // FIXED: Using prepared statement and backticks for 'order' table
                $stmt = $pdo->prepare("INSERT INTO `order` (customer, description, amount, paid, balance, received_by, date_received, completed, date_collected) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $res = $stmt->execute([$customer, $desc, $amount, $paid, $balance, $received_by, $date_received, $completed, $date_collected]);
                
                $cid = $pdo->lastInsertId();
                
                // Also using prepared statement for calendar insert
                $stmt2 = $pdo->prepare("INSERT INTO calendar (title, description, start, end, allDay, color, url, category, user_id) VALUES (?, ?, ?, ?, 'true', ?, ?, 'Orders', ?)");
                $res2 = $stmt2->execute([$name, $desc, $date_received, $date_collected, $color, "../orderedit.php?id=$cid", $uid]);
                
                if ($res) {

                    echo "<div class='alert alert-success alert-dismissable'>
                    <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>	
                    Order Added Successfully!
                    </div>";
                } else {
                    echo "<div class='alert alert-danger alert-dismissable'>
                    <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>	
                    Error adding order. Please try again.
                    </div>";
                }
            }
            
            // Get current date for the input field
            $currentDate = date('Y-m-d');
            ?>

            <script>
                $(function() {
                    $("#datepicker").datepicker();
                });

                // Function to calculate balance
                function calculateBalance() {
                    var amount = document.getElementById('amount').value;
                    var paid = document.getElementById('paid').value;

                    // Convert to numbers, handle empty values
                    amount = amount ? parseFloat(amount) : 0;
                    paid = paid ? parseFloat(paid) : 0;

                    // Calculate balance
                    var balance = amount - paid;

                    // Update balance field
                    document.getElementById('balance').value = balance.toFixed(2);

                    // Optional: Add color coding for balance
                    var balanceField = document.getElementById('balance');
                    if (balance > 0) {
                        balanceField.style.color = '#d9534f'; // Red for positive balance (amount due)
                    } else if (balance < 0) {
                        balanceField.style.color = '#f0ad4e'; // Orange for overpayment
                    } else {
                        balanceField.style.color = '#5cb85c'; // Green for fully paid
                    }
                }

                // Add event listeners to amount and paid fields
                document.addEventListener('DOMContentLoaded', function() {
                    document.getElementById('amount').addEventListener('input', calculateBalance);
                    document.getElementById('amount').addEventListener('change', calculateBalance);
                    document.getElementById('paid').addEventListener('input', calculateBalance);
                    document.getElementById('paid').addEventListener('change', calculateBalance);

                    // Calculate initial balance on page load
                    calculateBalance();
                });
            </script>

            <form action="orderadd.php" method="post">

                <div class="form-group">

                    <label>Select Customer</label>

                    <select name="customer" class="form-control">
                        <option value="0">Please Select a Customer</option>
                        <?php

                        $ddaa = $pdo->query("SELECT id, fullname FROM customer ORDER BY id");
                        while ($data = $ddaa->fetch(PDO::FETCH_ASSOC)) {
                            if (isset($_GET['id']) && $data['id'] == $_GET['id']) {
                                echo "<option value='$data[id]' selected='selected'>$data[fullname]</option>";
                            } else {
                                echo "<option value='$data[id]'>$data[fullname]</option>";
                            }
                        }
                        ?>

                    </select><br />

                </div>
                
                <div class="form-group">
                    <label>Description</label><br />
                    <input type="text" name="desc" style="width:200px; height: 40px;" /><br /><br />
                </div>

                <div class="form-group">
                    <label>Date Received</label><br />
                    <input type="date" name="date_received" style="width:200px; height: 40px;" value="<?php echo $currentDate; ?>" /><br /><br />
                </div>

                <div class="form-group">

                    <label>Received By</label>

                    <select name="received_by" class="form-control">
                        <?php

                        $ddaa = $pdo->query("SELECT id, fullname FROM staff ORDER BY id");

                        while ($data = $ddaa->fetch(PDO::FETCH_ASSOC)) {
                            echo "<option value='$data[id]'>$data[fullname]</option>";
                        }
                        ?>

                    </select><br />
                </div>

                <div class="form-group">
                    <label>Amount</label><br />
                    <?php echo ($currency); ?> <input type="text" id="amount" name="amount" style="width:200px; height: 40px;" /><br /><br />
                </div>
                
                <div class="form-group">
                    <label>Paid</label><br />
                    <?php echo ($currency); ?> <input type="text" id="paid" name="paid" style="width:200px; height: 40px;" /><br /><br />
                </div>
                
                <div class="form-group">
                    <label>Balance (Amount - Paid)</label><br />
                    <?php echo ($currency); ?> <input type="text" id="balance" name="balance" style="width:200px; height: 40px;" readonly /><br /><br />
                </div>
                
                <div class="form-group">
                    <label>Completed?</label><br />
                    <select name="completed" class="form-control">
                        <option value='No'>No</option>
                        <option value='Yes'>Yes</option>
                    </select><br /><br />
                </div>
                
                <div class="form-group">
                    <label>Date to Collect</label><br />
                    <input type="date" name="date_collected" style="width:200px; height: 40px;" /><br /><br />
                </div>
                
                <input type="submit" class="btn btn-lg btn-success btn-block" value="ADD">
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