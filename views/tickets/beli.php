<?php
require_once __DIR__ . '/../../config/bootstrap.php';

// Wajib login untuk beli tiket
$auth->requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$auth->verifyCsrfToken($_POST['csrf_token'] ?? '');

$ticket_type_id = (int) ($_POST['ticket_type_id'] ?? 0);
$event_title    = trim($_POST['event_title'] ?? '');
$redirect_back  = $_POST['redirect_back'] ?? BASE_URL . '/index.php';
$buyer_id       = $auth->getUserId();

if ($ticket_type_id <= 0) {
    $auth->setFlash('error', 'Tipe tiket tidak valid.');
    header('Location: ' . $redirect_back);
    exit;
}

$ticket_type_model = new TicketTypeModel();
$tipe = $ticket_type_model->getById($ticket_type_id);

if (!$tipe) {
    $auth->setFlash('error', 'Tipe tiket tidak ditemukan.');
    header('Location: ' . $redirect_back);
    exit;
}

$ticket_model = new TicketModel();
$hasil = $ticket_model->purchaseTicket($ticket_type_id, $buyer_id, $event_title);

if ($hasil['success']) {
    $auth->setFlash('success', '🎉 Tiket berhasil dibeli! Kode tiketmu: ' . $hasil['ticket_code']);
    // Redirect ke halaman detail tiket yang baru dibeli
    header('Location: ' . BASE_URL . '/views/tickets/detail.php?kode=' . urlencode($hasil['ticket_code']));
    exit;
} else {
    $auth->setFlash('error', $hasil['message']);
    header('Location: ' . $redirect_back);
    exit;
}
