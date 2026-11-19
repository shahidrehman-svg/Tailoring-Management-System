<?php
require_once('function.php');
dbconnect();
session_start();

if (!is_user()) {
    redirect('index.php');
}

// Get customer ID from URL
$customer_id = $_GET["id"];

// Get order information for this customer
$stmt = $pdo->prepare("SELECT * FROM `order` WHERE customer = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$customer_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

// For checkbox states, we need the order ID
$order_id = $order ? $order['id'] : 0;
?>

<?php
$user = $_SESSION['username'];
$usid = $pdo->query("SELECT id FROM users WHERE username='".$user."' ");
$usid = $usid->fetch(PDO::FETCH_ASSOC);
$uid = $usid['id'];
include ('header.php');
?>

<style type="text/css" media="print">
  @page {
    size: auto;
    margin: 0mm;
  }

  /* body {
    background-color: #FFFFFF;
    margin: 0px;
    padding: 20px;
  } */

  b,
  strong {
    font-weight: 100 !important;
  }
  
  /* Suit design styles */
  .suit-design {
    font-size: 14px;
    line-height: 1.8;
  }
  
  .suit-design .div {
    cursor: pointer;
    display: inline-block;
    margin-right: 5px;
    padding: 2px 5px;
  }
  
  .suit-design .div:hover {
    background-color: #f0f0f0;
  }
  
  .suit-design .check {
    margin-right: 3px;
  }
  
  .separator {
    display: inline-block;
    margin: 0 5px;
    color: #666;
  }
  
  .column {
    display: inline-block;
  }
</style>

<script>
  var orderId = <?php echo json_encode($order_id); ?>;
  
  function mPrint(el) {
    var restorepage = document.body.innerHTML;
    var printcontent = document.getElementById(el).innerHTML;
    document.body.innerHTML = printcontent;
    $('.printbtn, .no-print, .checkbox-container').each(function(index,ele) {
        $(this).remove();
    })
    window.print();
    document.body.innerHTML = restorepage;
    location.reload();
  }
  
  function toggleCheck(event) {
    if (!orderId || orderId == 0) {
        alert("No order found for this customer. Please create an order first.");
        return;
    }
    
    event.stopPropagation(); 
    var checkSpan = event.currentTarget.querySelector(".check");
    var checkboxId = event.currentTarget.getAttribute("data-id");
    
    // Check if this is the specific element that needs cross behavior
    if (checkboxId === "edge-1") {
        // For edge-1: cycle through tick -> cross -> none
        var currentState = checkSpan.getAttribute("data-state") || "none";
        var isChecked = 0;
        
        if (currentState === "none") {
            // First click: show tick
            checkSpan.innerHTML = "✔️";
            checkSpan.style.display = "inline";
            checkSpan.setAttribute("data-state", "tick");
            checkSpan.style.color = "green";
            isChecked = 1; // 1 = tick
        } else if (currentState === "tick") {
            // Second click: show cross
            checkSpan.innerHTML = "❌";
            checkSpan.style.display = "inline";
            checkSpan.setAttribute("data-state", "cross");
            checkSpan.style.color = "red";
            isChecked = 2; // 2 = cross
        } else {
            // Third click: hide
            checkSpan.style.display = "none";
            checkSpan.setAttribute("data-state", "none");
            checkSpan.innerHTML = "✔️"; // Reset to tick for next time
            isChecked = 0; // 0 = none
        }
        
        // Save to database
        saveToDatabase(orderId, checkboxId, isChecked);
    } else {
        // For all other elements: normal tick behavior
        var currentState = checkSpan.getAttribute("data-state") || "none";
        var isChecked = (currentState === "tick") ? 0 : 1;
        
        if (currentState === "tick") {
            checkSpan.style.display = "none";
            checkSpan.setAttribute("data-state", "none");
        } else {
            checkSpan.innerHTML = "✔️";
            checkSpan.style.display = "inline";
            checkSpan.setAttribute("data-state", "tick");
        }
        
        // Save to database
        saveToDatabase(orderId, checkboxId, isChecked);
    }
  }
  
  function saveToDatabase(orderId, checkboxId, isChecked) {
    var xhr = new XMLHttpRequest();
    xhr.open("POST", "save_checkbox_state.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.status !== 'success') {
                        console.error('Failed to save checkbox state: ' + response.message);
                    } else {
                        console.log('Saved successfully:', checkboxId, '=', isChecked);
                    }
                } catch (e) {
                    console.error('Error parsing response:', e);
                }
            } else {
                console.error('Request failed with status:', xhr.status);
            }
        }
    };
    
    xhr.send("order_id=" + orderId + 
             "&checkbox_id=" + encodeURIComponent(checkboxId) + 
             "&is_checked=" + isChecked);
  }
  
  function initializeCheckStates() {
    if (!orderId || orderId == 0) return;
    
    // Fetch all checkbox states from database
    var xhr = new XMLHttpRequest();
    xhr.open("GET", "fetch_checkbox_states.php?order_id=" + orderId, true);
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                console.log('Fetched states:', response);
                
                if (response.status === 'success') {
                    var checkedItems = response.data || {};
                    var stateTypes = response.state_types || {};
                    
                    // Get all checkbox divs
                    var checkboxDivs = document.querySelectorAll(".suit-design [data-id]");
                    
                    checkboxDivs.forEach(function(div) {
                        var id = div.getAttribute("data-id");
                        var checkSpan = div.querySelector(".check");
                        if (!checkSpan) return;
                        
                        if (id === "edge-1") {
                            // Handle edge-1 with 3 states
                            var state = stateTypes[id] || "none";
                            var isChecked = checkedItems[id] || 0;
                            
                            checkSpan.setAttribute("data-state", state);
                            
                            if (state === "tick") {
                                checkSpan.innerHTML = "✔️";
                                checkSpan.style.display = "inline";
                                checkSpan.style.color = "green";
                            } else if (state === "cross") {
                                checkSpan.innerHTML = "❌";
                                checkSpan.style.display = "inline";
                                checkSpan.style.color = "red";
                            } else {
                                checkSpan.style.display = "none";
                                checkSpan.innerHTML = "✔️";
                            }
                        } else {
                            // Normal checkboxes
                            var isChecked = checkedItems[id] || 0;
                            var state = stateTypes[id] || (isChecked ? "tick" : "none");
                            
                            checkSpan.setAttribute("data-state", state);
                            
                            if (state === "tick") {
                                checkSpan.innerHTML = "✔️";
                                checkSpan.style.display = "inline";
                            } else {
                                checkSpan.style.display = "none";
                            }
                        }
                    });
                } else {
                    console.error('Failed to fetch checkbox states: ' + response.message);
                }
            } catch (e) {
                console.error('Error parsing response:', e);
            }
        }
    };
    xhr.send();
  }
  
  // Initialize when DOM is loaded
  document.addEventListener("DOMContentLoaded", function() {
    initializeCheckStates();
    
    // Add event listeners to all checkbox divs
    var checkboxes = document.querySelectorAll(".suit-design [data-id]");
    
    checkboxes.forEach(function(div) {
        div.addEventListener("click", toggleCheck);
    });
    
    // Add clear all button functionality
    var clearBtn = document.getElementById('clearAllBtn');
    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            if (confirm("Are you sure you want to clear all checkboxes?")) {
                clearAllCheckboxes();
            }
        });
    }
  });
  
  function clearAllCheckboxes() {
    if (!orderId || orderId == 0) {
        alert("No order found for this customer.");
        return;
    }
    
    // Clear all checkboxes visually
    document.querySelectorAll(".suit-design [data-id]").forEach(function(div) {
        var id = div.getAttribute("data-id");
        var checkSpan = div.querySelector(".check");
        if (checkSpan) {
            checkSpan.style.display = "none";
            checkSpan.setAttribute("data-state", "none");
            checkSpan.innerHTML = "✔️";
            checkSpan.style.color = "";
            
            // Save to database
            saveToDatabase(orderId, id, 0);
        }
    });
    
    alert("All checkboxes cleared!");
  }
</script>

<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Edit Measurement</h1>
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->
    <div class="row">
        
        <!-- Start: Left Column - Edit Measurement Form -->
        <div class="col-md-6">
            <?php
            if ($_POST) {
                $id = $_GET["id"];
                foreach ($_POST as $key => $value) {
                    $parts = $pdo->query("SELECT id FROM part WHERE title='".$key."'");
                    $parts = $parts->fetch(PDO::FETCH_ASSOC);
                    $part = @$parts['id'];
                    $res = $pdo->exec("UPDATE `measurement` SET `measurement`='$value' WHERE `customer_id` = '$id' AND `part_id` ='$key'");
                }
                echo "<div class='alert alert-success alert-dismissable'>
                        <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>
                        Measurements Edit Successfully!
                    </div>
                    <meta http-equiv='refresh' content='2; url=orderadd.php?id=$id' />";
            }
            ?>

            <form action="editmeasurement.php?id=<?php echo ($_GET['id']);?>" method="post">
                <div class="form-group">
                    <p style="font-weight:100; color:#666; font-size:24px;">                    
                    <?php
                    $id = $_GET["id"];
                    $dda = $pdo->query("SELECT fullname, sex FROM customer where id = '$id'");
                    $dda = $dda->fetch(PDO::FETCH_ASSOC);
                    echo($dda['fullname']);
                    ?></p>
                </div>
                
                <?php
                $type = $pdo->query("SELECT id, title FROM type where sex= '$dda[sex]'");
                while ($typee = $type->fetch(PDO::FETCH_ASSOC)) {
                    echo('<br/><div><p style="font-size:22px; color:#000; font-weight:700;">'.$typee['title'].'</p>');
                    echo('<table class="table table-bordered">'); 
                    $ddaa = $pdo->query("SELECT id, title, image FROM part where type='$typee[id]'");
                    while ($data = $ddaa->fetch(PDO::FETCH_ASSOC)) {
                        $mes = $pdo->query("SELECT measurement FROM measurement where customer_id = '$id' AND part_id = '$data[id]'");
                        $mes = $mes->fetch(PDO::FETCH_ASSOC);
                        if ($mes == false) {
                            $pdo->exec("INSERT INTO `measurement`(`customer_id`, `part_id`, `measurement`) VALUES ('$id' ,'$data[id]','')");
                            $mes = $pdo->query("SELECT measurement FROM measurement where customer_id = '$id' AND part_id = '$data[id]'");
                            $mes = $mes->fetch(PDO::FETCH_ASSOC);
                        }
                        if (!$data['image']) $data['image'] = "tailor.png";
                        $img = 'img/part/'.$data['image'];
                        echo "<tr>
                                <td style='width:80px;'><a href='$img' target='_blank'><img src='$img' width='40px' /></a></td>
                                <td><label><a href='partedit.php?id=$data[id]'>$data[title]</a></label></td>
                                <td><input type='text' name='$data[id]' value='$mes[measurement]' class='form-control' /></td>
                              </tr>";
                    }
                    echo('</table>'); 
                    echo('</div>');
                }
                ?> 
                <input type="submit" class="btn btn-lg btn-success btn-block" value="Update">
            </form>
        </div>
        <!-- End: Left Column -->
        
        <!-- Start: Right Column - Print Invoice with Checkboxes -->
        <div class="col-md-6">
            <div style="height:87px" class="shahid"></div> 
            <div class="panel panel-default">
                <div class="panel-body">
                    <?php if($order): ?>
                    <div id="print-invoice-section">
                        
                        <div style="padding: 20px; background: #fff; border: 1px solid #ddd; max-width: 700px; margin: 0 auto;">
                            <div style="margin-bottom: 20px; text-align: center;">
                                
                            </div>
                            <div class="main-meas" style="display: flex;">
                                <div class="checkbox-container" style="width: 100%;">
                                    <!-- SUIT DESIGN WITH CLICKABLE CHECKBOXES -->
                                    <div class="suit-design-parent">
                                        <div class="suit-design">
                                           <!-- Pockets - Separate columns in one row -->
<div class="div" data-id="pockets-1" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px;; padding:0px;margin-bottom:6px;margin-left:0px;border:0px;">
  <div class="column" style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 5px;">
    <!-- Column 5 -->

    <div class="div" data-id="pockets-6" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>4 پاکٹ
    </div>
    <!-- Column 4 -->
    <div class="div" data-id="pockets-5" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>فرنٹ
    </div>

        <!-- Column 2 -->
    <div class="div" data-id="pockets-3" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>2 سائیڈ
    </div>

     <!-- Column 3 -->
    <div class="div" data-id="pockets-4" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>سائیڈ 1
    </div>

    <!-- Column 1 -->
    <div class="div" data-id="pockets-2" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>شلوار
    </div>
    
    
    
  </div>
</div>
<!-- Pockets -->

      <!-- Collar - Separate columns in one row -->
<div class="div" data-id="collar-1" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px;;padding:0px;margin-bottom:6px;margin-left:0px;border:0px;">
  <div class="column" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 5px;">
    
    <!-- Column 4 -->
    <div class="div" data-id="collar-6" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>کالر
    </div>

    <!-- Column 3 -->
    <div class="div" data-id="collar-5" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>نارمل
    </div>

    <!-- Column 1 -->
    <div class="div" data-id="collar-7" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>فرنچ
    </div>
    
    <!-- Column 2 -->
    <div class="div" data-id="collar-4" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>چھوٹا
    </div>
    
    
  </div>
</div>
<!-- Collar - Second section (4 items) -->
<div class="div" data-id="collar-1" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px;;padding:0px;margin-bottom:6px;margin-left:0px;border:0px;">
  <div class="column" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 5px;">
    
    <!-- Column 3 -->
    <div class="div" data-id="collar-2" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>کٹ بین
    </div>

    <!-- Column 4 -->
    <div class="div" data-id="collar-3" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>فل بین
    </div>

    <!-- Column 1 -->
    <div class="div" data-id="collar-9" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>چورس 
    </div>
    
    <!-- Column 2 -->
    <div class="div" data-id="collar-8" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>گول 
    </div>
    
    
  </div>
</div>
<!-- Collar -->

      <!-- Kuff - Separate columns in one row -->
<div class="div" data-id="kuff-1" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px;;padding:0px;margin-bottom:6px;margin-left:0px;border:0px;">
  <div class="column" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 5px;">
    
      <!-- Column 4 -->
    <div class="div" data-id="kuff-5" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>کف چورس
    </div>  

    <!-- Column 3 -->
    <div class="div" data-id="kuff-4" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>گول
    </div>

        <!-- Column 2 -->
    <div class="div" data-id="kuff-3" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>سٹڈ
    </div>

    <!-- Column 1 -->
    <div class="div" data-id="kuff-2" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>گول بازو
    </div>
    
    
  </div>
</div>
<!-- Kuff -->

     <!-- Button - Separate columns in one row -->
<div class="div" data-id="button-1" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px;;padding:0px;margin-bottom:6px;margin-left:0px;border:0px;">
  <div class="column" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 5px;">
    
    <!-- Column 4 -->
    <div class="div" data-id="button-5" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>فینسی بٹن
    </div>

    <!-- Column 1 -->
    <div class="div" data-id="button-2" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>میٹل
    </div>
    
    <!-- Column 2 -->
    <div class="div" data-id="button-3" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>رنگ
    </div>
    
    <!-- Column 3 -->
    <div class="div" data-id="button-4" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>سادہ
    </div>
    
    
  </div>
</div>
<!-- Button -->

    <!-- Ghera - 3 items in one row -->
<div class="div" data-id="ghera-1" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px;;padding:0px;margin-bottom:6px;margin-left:0px;border:0px;">
  <div class="column" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 5px;">
    
    <!-- Column 1 -->
    <div class="div" data-id="ghera-2" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>چورس دامن
    </div>
    
    <!-- Column 2 -->
    <div class="div" data-id="ghera-3" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>گھیرا گول
    </div>
    
    <!-- Column 3 -->
    <div class="div" data-id="ghera-4" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>گھیرا ڈبل
    </div>
    
  </div>
</div>
<!-- Ghera -->

      <!-- Patti - 4 items in one row -->
<div class="div" data-id="patti-1" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px;;padding:0px;margin-bottom:6px;margin-left:0px;border:0px;">
  <div class="column" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 5px;">
    
    <!-- Column 1 - نوک -->
    <div class="div" data-id="patti-2" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>نوک
    </div>
    
    <!-- Column 2 - پٹی ایک انچ -->
    <div class="div" data-id="patti-2b" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>پٹی ایک انچ
    </div>
    
    <!-- Column 3 - پٹی پونا -->
    <div class="div" data-id="patti-3" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>پٹی پونا
    </div>
    
    <!-- Column 4 - پٹی سوا -->
    <div class="div" data-id="patti-4" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>پٹی سوا
    </div>
    
  </div>
</div>
<!-- Patti -->

      <!-- Chamak - 5 items in one row -->
<div class="div" data-id="chamak-1" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px;;padding:0px;margin-bottom:6px;margin-left:0px;border:0px;">
  <div class="column" style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 5px;">
    
    <!-- Column 2 -->
    <div class="div" data-id="chamak-3" style="font-family: inherit; direction: rtl; text-align: center;width: 110px;padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>فل ڈبل چمک
    </div>

    <!-- Column 1 -->
    <div class="div" data-id="chamak-2" style="font-family: inherit; direction: rtl; text-align: center;width: 100px; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>موٹا چمک
    </div>

    <!-- Column 3 -->
    <div class="div" data-id="chamak-4" style="font-family: inherit; direction: rtl; text-align: center;    width: 104px;
 padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>چمک سنگل
    </div>
    
  </div>
</div>
<!-- Chamak -->


      <!-- Chamak - 5 items in one row -->
<div class="div" data-id="chamak-1" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px;;padding:0px;margin-bottom:6px;margin-left:0px;border:0px;">
  <div class="column" style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 5px;">
    
    
    <!-- Column 4 -->
    <div class="div" data-id="chamak-5" style="font-family: inherit; direction: rtl; text-align: center;width: 80px; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>فل ڈبل
    </div>
    
    <!-- Column 5 -->
    <div class="div" data-id="chamak-6" style="font-family: inherit;width: 108px; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>فرنٹ ڈبل
    </div>
    <!-- Column 1 -->
    <div class="div" data-id="pant-3" style="font-family: inherit; direction: rtl; text-align: center;width: 97px; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="font-family: Arial, sans-serif;">✔️</span>پانچہ کڑھائی
    </div>
    
    <!-- Column 2 -->
    <div class="div" data-id="pant-4" style="font-family: inherit; direction: rtl; text-align: center;width: 85px; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="font-family: Arial, sans-serif;">✔️</span>پانچہ جالی
    </div>
  </div>
</div>
<!-- Chamak -->
      
   <!-- Shalwar - 5 items in one row -->
<div class="div10" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px;;padding:0px;margin-bottom:6px;margin-left:0px;border:0px;">
  <div class="column" style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 5px;">
    
     <!-- Column 5 -->
    <div class="div" data-id="gaddi-1" style="font-family: inherit; direction: rtl; text-align: center;width:80px; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="font-family: Arial, sans-serif;">✔️</span>گڈی 
    </div>
    
    <!-- Column 3 -->
    <div class="div" data-id="pant-2" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="font-family: Arial, sans-serif;">✔️</span>شیپ 
    </div>
    
    <!-- Column 4 -->
    <div class="div" data-id="pant-1" style="font-family: inherit; direction: rtl; text-align: center;width: 92px; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="font-family: Arial, sans-serif;">✔️</span>فل ٹراؤزر
    </div>

    <!-- Column 4 -->
    <div class="div" data-id="pant-3" style="font-family: inherit; direction: rtl; text-align: center;width: 92px; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="font-family: Arial, sans-serif;">✔️</span> ٹراؤزر
    </div>
    
   
    
  </div>
</div>
<!-- Shalwar -->
      
      <!-- Additional Unique IDs -->
      <!-- Design items - Row 1 (4 items) -->
<div style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px;margin-left:0px;border:0px;">
  <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 5px;">
    
    <div class="div" data-id="design-1" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>ڈیزائن 
    </div>
    
    <div class="div" data-id="design-2" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>SF بکرم
    </div>
    
    <div class="div" data-id="design-3a" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>باریک سلائی
    </div>
<!--     
    <div class="div" data-id="design-3b" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>کڑھائی
    </div> -->
    
  </div>
</div>

<!-- Design items - Row 2 (3 items) -->
<div style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px;; margin-bottom: 8px;padding:0px;margin-bottom:6px;margin-left:0px;border:0px;">
  <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 5px;">
    
    <div class="div" data-id="design-3c" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>ڈبل بٹن
    </div>
    
    <div class="div" data-id="edge-1" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>چاک پٹی کاج
    </div>
    
    <div class="div" data-id="design-3d" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>چاک پٹی لیبل
    </div>
    
  </div>
</div>
                                    </div>
                                </div>
                                
                                <!-- Measurements Display -->
                                <div style="width: 0%; border-left: 1px solid #e6e6e6; margin-left: 10px;">
                                    <?php
                                    // Fetch measurements using prepared statement
                                    $stmt = $pdo->prepare("SELECT measurement.*, part.title, type.title as type_name FROM measurement
                                                JOIN part ON measurement.part_id = part.id
                                                JOIN type ON part.type = type.id
                                            WHERE customer_id = ? AND measurement.measurement != ''");
                                    $stmt->execute([$customer_id]);
                                    $measurements_rows = $stmt;
                                    
                                    $measurements = [];
                                    while ($row = $measurements_rows->fetch(PDO::FETCH_ASSOC)) {
                                        $measurements[$row['type_name']][] = $row;
                                    }
                                    ?>
                                    
                                    <?php foreach ($measurements as $type => $mrs) : ?>
                                        
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">
                        <p>No order found for this customer.</p>
                        <a href="orderadd.php?id=<?php echo $customer_id; ?>" class="btn btn-primary">
                            Create New Order
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <!-- End: Right Column -->
        
    </div>
    <!-- /.row -->
</div>
<!-- /#page-wrapper -->

<script src="js/bootstrap-timepicker.min.js"></script>

<script>
    jQuery(document).ready(function(){
        jQuery("#ssn").mask("999-99-9999");
        
        // Time Picker
        jQuery('#timepicker').timepicker({defaultTIme: false});
        jQuery('#timepicker2').timepicker({showMeridian: false});
        jQuery('#timepicker3').timepicker({minuteStep: 15});
    });
</script>

<?php
include ('footer.php');
?>