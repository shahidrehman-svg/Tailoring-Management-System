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
$usid = $pdo->query("SELECT id FROM users WHERE username='".$user."'");
$usid = $usid->fetch(PDO::FETCH_ASSOC);
 $uid = $usid['id'];
 include ('header.php');
 ?>



 
        <div id="page-wrapper">
            <div class="row">
                <div class="col-lg-12">
                    <h1 class="page-header">Add Measurement</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <!-- /.row -->
            <div class="row">
                
                <div class="col-md-6">
				
				
	

		<?php
if($_POST)
{
    $id = $_GET["id"];
    $insertCount = 0;
    $errors = [];
    
    foreach ($_POST as $part_id => $measurement_value)
    {
        // Sanitize values
        $part_id = trim($part_id);
        $measurement_value = trim($measurement_value);
        
        // Skip if measurement value is empty
        if(empty($measurement_value)) {
            continue;
        }
        
        // Verify that the part_id exists in the database (optional but recommended)
        $check = $pdo->prepare("SELECT id FROM part WHERE id = ?");
        $check->execute([$part_id]);
        $part_exists = $check->fetch(PDO::FETCH_ASSOC);
        
        if(!$part_exists) {
            $errors[] = "Part ID $part_id does not exist";
            continue;
        }
        
        try {
            // Insert measurement - using part_id directly from the form field name
            $stmt = $pdo->prepare("INSERT INTO `measurement` (`customer_id`, `part_id`, `measurement`) VALUES (?, ?, ?)");
            $result = $stmt->execute([$id, $part_id, $measurement_value]);
            
            if($result) {
                $insertCount++;
            } else {
                $errors[] = "Failed to insert measurement for part ID: $part_id";
            }
            
        } catch(PDOException $e) {
            $errors[] = "Database error for part ID $part_id: " . $e->getMessage();
        }
    }
    
    // Display success/error message
    if($insertCount > 0) {
        echo "<div class='alert alert-success alert-dismissable'>
        <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>	
         Measurement added successfully!";
        
        if(!empty($errors)) {
            echo "<br><small>Some errors occurred: " . implode(', ', $errors) . "</small>";
        }
        
        echo "</div>";
    } else {
        echo "<div class='alert alert-danger alert-dismissable'>
        <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>	
        No measurements were added. Errors:<br>";
        
        foreach($errors as $error) {
            echo "- $error<br>";
        }
        
        echo "</div>";
    }
    
    echo "<meta http-equiv='refresh' content='2; url=orderadd.php?id=$id' /> ";
}
?>
		


	 <script>
  $(function() {
    $( "#datepicker" ).datepicker();
  });
  </script>		
				
				
				
				
				
				    <form action="addmeasurement.php?id=<?php echo ($_GET['id']);?>" method="post">
		
                    <div class="form-group">
<p style="font-weight:100; color:#666; font-size:24px;">					
					<?php
$id = $_GET["id"];
$ddaa = $pdo->query("SELECT fullname,sex FROM customer where id = '$id'");
$dda = $ddaa->fetch(PDO::FETCH_ASSOC);
    echo($dda["fullname"]);
?></p>

</div>
              
        <?php
$type = $pdo->query("SELECT id, title FROM type where sex= '$dda[sex]'");

while ($typee = $type->fetch(PDO::FETCH_ASSOC))
{
	echo('<br/><br/><br/><div><p style="font-size:22px; color="#000"; font-weight="700"">'.$typee["title"].'</p><br/>');
	 echo('<table>'); 
	$ddaa = $pdo->query("SELECT id, title, image FROM part where type='$typee[id]'");
    while ($data = $ddaa->fetch(PDO::FETCH_ASSOC))
    {
		$img = 'img/part/'.$data["image"];
		echo "<tr><td style='width:100px; height: 40px; margin: 20px;'><img src='$img' width='50px' /></td><td style='width:150px; height: 40px; margin: 20px;'><label>$data[title]</label></td>";
		echo "<td><input type='text' name='$data[id]' style='width:200px; height: 40px; margin: 20px;' /></td></tr>";
		
	}
	echo('</table>'); 
	echo('</div>');
}
?> 
      
               
					<input type="submit" class="btn btn-lg btn-success btn-block" value="ADD">
			    	</form>
                </div>
						
						
						
						
						
				
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