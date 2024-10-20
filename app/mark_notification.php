<?php
require '../includes/db.php';
require '../includes/functions.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $notification_id = $_POST['notification_id'];
    mark_notification_as_read($notification_id);
    header('Location: dashboard.php');
    exit();
}
