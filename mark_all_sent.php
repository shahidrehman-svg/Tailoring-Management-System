<?php
require_once('function.php');
dbconnect();
session_start();

if (!is_user()) {
    die('Unauthorized');
}

// Mark all pending messages as sent
$pdo->query("UPDATE whatsapp_messages SET status = 'sent', sent_at = NOW() WHERE status = 'pending'");

header('Location: whatsapp_simple_send.php?msg=all_marked');
exit;
?>