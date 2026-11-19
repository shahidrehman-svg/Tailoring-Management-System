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

$id = $_GET['id'] ?? 0;

try {
    $stmt = $pdo->prepare("DELETE FROM overall_income WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $uid]);
    
    $_SESSION['success'] = "Record deleted successfully!";
} catch (PDOException $e) {
    $_SESSION['error'] = "Error deleting record: " . $e->getMessage();
}

redirect('overall_income_list.php');
?>