<?php
require_once __DIR__ . '/../../config/bootstrap.php';

$auth->requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$auth->verifyCsrfToken($_POST['csrf_token'] ?? '');

$ticket_type_id = (int) ($_POST['ticket_type_id'] ?? 0);
$redirect_back  = $_POST['redirect_back'] ?? BASE_URL . '/index.php';
$waitlist_model = new WaitlistModel();

$hasil = $waitlist_model->register($ticket_type_id, $auth->getUserId());

$auth->setFlash($hasil['success'] ? 'success' : 'warning', $hasil['message']);
header('Location: ' . $redirect_back);
exit;
