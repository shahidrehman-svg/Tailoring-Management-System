<?php
require_once('function.php');
dbconnect();
session_start();

// Get settings
$query = $pdo->query("SELECT hide_add_order, hide_view_orders FROM general_setting WHERE id='1'");
$menu = $query->fetch(PDO::FETCH_ASSOC);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Sidebar Test</title>
    <link href='../css/style.default.css' rel='stylesheet'>
</head>
<body>

<div style='background: red; color: white; padding: 10px; margin: 10px;'>
    <strong>TEST FILE:</strong> test_sidebar.php<br>
    <strong>hide_add_order value:</strong> " . $menu['hide_add_order'] . " 
    (" . ($menu['hide_add_order'] == 1 ? 'HIDDEN' : 'VISIBLE') . ")<br>
    <strong>hide_view_orders value:</strong> " . $menu['hide_view_orders'] . " 
    (" . ($menu['hide_view_orders'] == 1 ? 'HIDDEN' : 'VISIBLE') . ")
</div>

<div style='display: flex;'>
    <!-- Sidebar -->
    <div style='width: 250px; background: #333; color: white; min-height: 500px; padding: 20px;'>
        <h3>Sidebar Menu</h3>
        <ul>
            <li><a href='../home.php' style='color: white;'>Dashboard</a></li>
            <li><a href='../calendar/' style='color: white;'>Calendar</a></li>";
            
            if($menu['hide_add_order'] != 1) {
                echo "<li style='color: green;'>✓ Add Order (SHOWING)</li>
                <li><a href='../orderadd.php' style='color: white;'>Add Order</a></li>";
            } else {
                echo "<li style='color: red;'>✗ Add Order (HIDDEN - value=1)</li>";
            }
            
            if($menu['hide_view_orders'] != 1) {
                echo "<li style='color: green;'>✓ View Orders (SHOWING)</li>
                <li><a href='../orderlist.php' style='color: white;'>View/Edit Orders</a></li>";
            } else {
                echo "<li style='color: red;'>✗ View Orders (HIDDEN - value=1)</li>";
            }
            
        echo "</ul>
    </div>
    
    <!-- Main Content -->
    <div style='flex: 1; padding: 20px;'>
        <h1>Main Content Area</h1>
        <p>This is a test page to verify sidebar hiding works.</p>
        <p>If you see <span style='color: red;'>RED text (HIDDEN)</span> for Add Order and View Orders, 
        then the database values are correct.</p>
        <p>If you see <span style='color: green;'>GREEN text (SHOWING)</span>, then the hiding is NOT working.</p>
    </div>
</div>

</body>
</html>";
?>