<?php
require_once __DIR__ . '/../../config/bootstrap.php';

$auth->requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/views/dashboard/attendee.php');
    exit;
}

$auth->verifyCsrfToken($_POST['csrf_token'] ?? '');

$ticket_id    = (int) ($_POST['ticket_id'] ?? 0);
$ticket_model = new TicketModel();
$hasil        = $ticket_model->cancelTicket($ticket_id, $auth->getUserId());

$auth->setFlash($hasil['success'] ? 'success' : 'error', $hasil['message']);
header('Location: ' . BASE_URL . '/views/dashboard/attendee.php');
exit;
