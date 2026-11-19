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
<style type="text/css" media="print">
  @page {
    size: auto;
    /* auto is the current printer page size */
    margin: 0mm;
    /* this affects the margin in the printer settings */
  }

  body {
    background-color: #FFFFFF;
    margin: 0px;
    /* the margin on the content before printing */
    padding: 20px;
  }

  b,
  strong {
    font-weight: 100 !important;
  }

  @media print {
    .printbtn {
      display: none;
    }
  }
</style>

<script>
  function printContent(el) {
    var restorepage = document.body.innerHTML;
    var printcontent = document.getElementById(el).innerHTML;
    document.body.innerHTML = printcontent;
    window.print();
    document.body.innerHTML = restorepage;
  }

  function mPrint(el) {
    var restorepage = document.body.innerHTML;
    var printcontent = document.getElementById(el).innerHTML;
    document.body.innerHTML = printcontent;
    $('.printbtn').each(function(index,ele) {
        $(this).remove();
    })

    window.print();
    document.body.innerHTML = restorepage;
  }
</script>

<div class="pageheader">
  <h2><i class="fa fa-th-list"></i>Print Receipt</h2>
</div>


<div class="contentpanel" id="all-p">
  <div class="pull-right">
    <a href="" onClick="printContent('ass')" class="btn btn-info printbtn"><i class="icon-print icon-large"></i> Print Order</a>
    <a href="" onClick="mPrint('all-p')" class="btn btn-info printbtn"><i class="icon-print icon-large"></i> Print All</a>
  </div>
  <div id="ass" style="
    
    padding: 20px;
    margin: 0 auto;
    max-width: 700px;
    background: #fff;"><br />



   <div style="margin-bottom: 20px;">
   <h1 style="font-weight: 700; margin-top: 0; font-size: 24px;">Bhatti Tailors</h1>
    <p>We Propose New Style Every Day</p>
    <p>Sadiq Ali Market Zyarat Road Pabbi</p>
    <p>Abdul Jabbar, 0313-5086286</p>
   </div>

    <?php

    $id = $_GET["id"];

    $order = $pdo->query("SELECT * FROM `order` WHERE id='" . $id . "'");
    $order = $order->fetch(PDO::FETCH_ASSOC);

    $customer = $pdo->query("SELECT fullname, phonenumber, address FROM customer WHERE id='" . $order['customer'] . "'");
    $customer = $customer->fetch(PDO::FETCH_ASSOC);
    $staff = $pdo->query("SELECT fullname FROM staff WHERE id='" . $order['received_by'] . "'");
    $staff = $staff->fetch(PDO::FETCH_ASSOC);
    ?>

    <div class="row">
      <div class="col-md-12">
      <table class="table table-bordered">
        <thead>
          <tr>
            <th>NAME</th>
            <th>Size No</th>
            <th>PHONE</th>
            <th>Date</th>
            <th>Paid</th>
            <th>Total</th>
          </tr>
        </thead>
        <tbody>
        <tr>
            <td><div><?php echo $customer['fullname']; ?></div></td>
            <td> <div><?php echo $customer['address']; ?></div></td>
            <td><div><?php echo $customer['phonenumber']; ?></div></td>
            <td><div><?php echo $order['date_collected']; ?></div></td>
            <td><div>Rs. <?php echo number_format(($order['paid']), 2); ?></div></td>
            <td> <div>Rs. <?php echo $order['amount']; ?></div></td>
          </tr>
        </tbody>
      </table>
        <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 15px;
        border-top: 1px solid #e6e6e6; padding-top:10px;">
          <div><b>NAME</b></div>
          <div><?php echo $customer['fullname']; ?></div>
        </div>
        <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 15px; border-top: 1px solid #e6e6e6; padding-top:10px;">
          <div><b>Size No </b></div>
          <div><?php echo $customer['address']; ?></div>
        </div>
        <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 15px;border-top: 1px solid #e6e6e6; padding-top:10px;">
          <div><b>PHONE </b></div>
          <div><?php echo $customer['phonenumber']; ?></div>
        </div>
        <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 15px;border-top: 1px solid #e6e6e6; padding-top:10px;">
          <div><b>Date </b></div>
          <div><?php echo $order['date_collected']; ?></div>
        </div>

        <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 15px;border-top: 1px solid #e6e6e6; padding-top:10px; background: #e6e6e6; padding: 10px 10px;">
          <div><b>Paid </b></div>
          <div>Rs. <?php echo number_format(($order['paid']), 2); ?></div>
          
        </div>

        <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 15px;border-top: 1px solid #e6e6e6; padding-top:10px; background: #e6e6e6; padding: 10px 10px;">
          <div><b>Remaining </b></div>
          <div>Rs. <?php echo number_format(($order['amount'] - $order['paid']), 2); ?></div>
        </div>
        <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 15px;border-top: 1px solid #e6e6e6; padding-top:10px; background: #e6e6e6;  padding: 10px 10px;">
          <div><b>Total Amount </b></div>
          <div>Rs. <?php echo $order['amount']; ?></div>
         
        </div>



      </div>


      <div class="col-md-6"></div>

    </div><!-- ROW-->



    <!-- <div class="table-responsive">
    
      <div style='margin-left:40%;'><b>Staff Sign</b></div><br /><br />
      <div style='margin-left:40%;'><b>Customer Sign</b></div>

    </div> -->


    <br /><br />



  </div>

  <?php

  $id = $_GET["id"];

  $measurements_rows = $pdo->query("SELECT measurement.*,part.title, `type`.title as type_name  FROM `measurement`
            JOIN part ON measurement.part_id = part.id
            JOIN `type` ON part.type = `type`.id
         WHERE customer_id='" . $order['customer'] . "' AND measurement.measurement !=''");
  $measurements = [];

  while ($row = $measurements_rows->fetch(PDO::FETCH_ASSOC)) {
    $measurements[$row['type_name']][] = $row;
  }

  ?>

  <div id="m-print" style="
    
    padding: 20px;
    margin: 20px auto 0;
    width: 370px;
    background: #fff; "><br />



    <h1 style="text-align:center; font-weight: 700;"><?= isset($measurements[0]) ? $measurements[0]['type_name'] : '' ?></h1>



    <div class="row">
      <div class="col-md-12">

        <?php foreach ($measurements as $type => $mrs) : ?>

          <div>

            <a style="text-align:center; display: table; margin: 0 auto;" class="btn btn-primary btn-xs printbtn" href="javascript:;" onClick="mPrint('<?= strtolower($type) ?>')"><i class="icon-print icon-large"></i> Print Mesurement</a>
            <div id="<?= strtolower($type) ?>">
              <h5 style="text-align:center; font-weight: 700;"><?php echo $customer['fullname']; ?></h5>
              <h5 style="text-align:center; font-weight: 700;"><?php echo $customer['address']; ?></h5>
              <h5 style="text-align:center; font-weight: 700;"><?= $type ?></h5>
              <?php foreach ($mrs as  $m) { ?>
                <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 10px;border-top: 1px solid #e6e6e6; padding-top:10px;">
                  <div><b><?= $m['title'] ?></b></div>
                  <div><?= $m['measurement'] ?></div>
                </div>

              <?php } ?>
            </div>
          </div>
        <?php endforeach; ?>




      </div>


      <div class="col-md-6"></div>

    </div><!-- ROW-->



    <!-- <div class="table-responsive">
    
      <div style='margin-left:40%;'><b>Staff Sign</b></div><br /><br />
      <div style='margin-left:40%;'><b>Customer Sign</b></div>

    </div> -->


    <br /><br />



  </div>

  <div class="pull-right">
    <a href="" onClick="mPrint('m-print')" class="btn btn-info printbtn"><i class="icon-print icon-large"></i> Print Mesurement</a>
  </div>


</div><!-- contentpanel -->






</section>


<?php
include('footer.php');
?>

<script src="js/jquery.datatables.min.js"></script>
<script src="js/select2.min.js"></script>

<script>
  jQuery(document).ready(function() {

    "use strict";

    jQuery('#table1').dataTable();

    jQuery('#table2').dataTable({
      "sPaginationType": "full_numbers"
    });

    // Select2
    jQuery('select').select2({
      minimumResultsForSearch: -1
    });

    jQuery('select').removeClass('form-control');

    // Delete row in a table
    jQuery('.delete-row').click(function() {
      var c = confirm("Continue delete?");
      if (c)
        jQuery(this).closest('tr').fadeOut(function() {
          jQuery(this).remove();
        });

      return false;
    });

    // Show aciton upon row hover
    jQuery('.table-hidaction tbody tr').hover(function() {
      jQuery(this).find('.table-action-hide a').animate({
        opacity: 1
      });
    }, function() {
      jQuery(this).find('.table-action-hide a').animate({
        opacity: 0
      });
    });


  });
</script>



</body>

</html>