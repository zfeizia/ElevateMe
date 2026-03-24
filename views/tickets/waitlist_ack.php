<?php

require_once __DIR__ . '/../../config/bootstrap.php';

$auth->requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/views/dashboard/attendee.php?tab=waitlist');
    exit;
}

$auth->verifyCsrfToken($_POST['csrf_token'] ?? '');

$waitlist_id    = (int) ($_POST['waitlist_id'] ?? 0);
$waitlist_model = new WaitlistModel();
$waitlist_model->markAsRead($waitlist_id, $auth->getUserId());

$auth->setFlash('info', 'Notifikasi ditandai sudah dibaca.');
header('Location: ' . BASE_URL . '/views/dashboard/attendee.php?tab=waitlist');
exit;
