<?php
require_once '../includes/config.php';
redirect_if_not_logged_in();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notification_id'])) {
    mark_notification_as_read((int) $_POST['notification_id'], $_SESSION['user_id']);
}
redirect('/app/dashboard.php');
