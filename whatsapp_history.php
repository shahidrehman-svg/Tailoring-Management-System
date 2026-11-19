<?php
require_once('function.php');
dbconnect();
session_start();

if (!is_user()) {
    redirect('index.php');
}

$user = $_SESSION['username'];
include ('header.php');

// Get all history
$history = get_whatsapp_history(500);
$stats = get_whatsapp_stats();
?>

<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">
                <i class="fa fa-history"></i> WhatsApp Message History
            </h1>
        </div>
    </div>
    
    <!-- Statistics -->
    <div class="row">
        <div class="col-md-3 col-sm-6">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <div class="row">
                        <div class="col-xs-3">
                            <i class="fa fa-envelope fa-5x"></i>
                        </div>
                        <div class="col-xs-9 text-right">
                            <div class="huge"><?php echo $stats['total']; ?></div>
                            <div>Total Messages</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="panel panel-green">
                <div class="panel-heading">
                    <div class="row">
                        <div class="col-xs-3">
                            <i class="fa fa-check fa-5x"></i>
                        </div>
                        <div class="col-xs-9 text-right">
                            <div class="huge"><?php echo $stats['sent']; ?></div>
                            <div>Sent</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="panel panel-yellow">
                <div class="panel-heading">
                    <div class="row">
                        <div class="col-xs-3">
                            <i class="fa fa-clock-o fa-5x"></i>
                        </div>
                        <div class="col-xs-9 text-right">
                            <div class="huge"><?php echo $stats['pending']; ?></div>
                            <div>Pending</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="panel panel-red">
                <div class="panel-heading">
                    <div class="row">
                        <div class="col-xs-3">
                            <i class="fa fa-times fa-5x"></i>
                        </div>
                        <div class="col-xs-9 text-right">
                            <div class="huge"><?php echo $stats['failed']; ?></div>
                            <div>Failed</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Actions -->
    <div class="row" style="margin-bottom: 20px;">
        <div class="col-md-12">
            <a href="whatsapp_send.php" class="btn btn-primary">
                <i class="fab fa-whatsapp"></i> Send New Message
            </a>
            <a href="whatsapp_export.php" class="btn btn-success">
                <i class="fa fa-download"></i> Export to CSV
            </a>
            <a href="whatsapp_clean.php" class="btn btn-warning" onclick="return confirm('Clean messages older than 90 days?')">
                <i class="fa fa-trash"></i> Clean Old Messages
            </a>
        </div>
    </div>
    
    <!-- History Table -->
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-list"></i> Message History
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Date & Time</th>
                                    <th>Customer</th>
                                    <th>Phone</th>
                                    <th>Message</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($history as $msg): ?>
                                <tr>
                                    <td><?php echo $msg['id']; ?></td>
                                    <td><?php echo date('d M Y h:i A', strtotime($msg['sent_at'])); ?></td>
                                    <td>
                                        <a href="customer.php?id=<?php echo $msg['customer_id']; ?>">
                                            <?php echo htmlspecialchars($msg['customer_name']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($msg['phone']); ?></td>
                                    <td>
                                        <button class="btn btn-xs btn-info" data-toggle="modal" data-target="#messageModal<?php echo $msg['id']; ?>">
                                            View Message
                                        </button>
                                    </td>
                                    <td>
                                        <?php 
                                        $status_class = ($msg['status'] == 'sent') ? 'success' : 
                                                       (($msg['status'] == 'failed') ? 'danger' : 'warning');
                                        ?>
                                        <span class="label label-<?php echo $status_class; ?>">
                                            <?php echo ucfirst($msg['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="whatsapp_resend.php?id=<?php echo $msg['id']; ?>" class="btn btn-xs btn-primary">
                                            <i class="fa fa-repeat"></i> Resend
                                        </a>
                                    </td>
                                </tr>
                                
                                <!-- Message Modal -->
                                <div class="modal fade" id="messageModal<?php echo $msg['id']; ?>" tabindex="-1" role="dialog">
                                    <div class="modal-dialog modal-lg" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                <h4 class="modal-title">Message Details</h4>
                                            </div>
                                            <div class="modal-body">
                                                <div class="well" style="background: #dcf8c6; padding: 15px; border-radius: 5px;">
                                                    <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                                </div>
                                                <p><strong>Customer:</strong> <?php echo htmlspecialchars($msg['customer_name']); ?></p>
                                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($msg['phone']); ?></p>
                                                <p><strong>Sent At:</strong> <?php echo date('d M Y h:i A', strtotime($msg['sent_at'])); ?></p>
                                                <p><strong>Status:</strong> <?php echo ucfirst($msg['status']); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include ('footer.php'); ?>