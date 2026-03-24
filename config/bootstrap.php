<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('BASE_PATH', dirname(__DIR__));
define('BASE_URL',  'http://localhost/elevate_me'); // ← TANPA slash di akhir

require_once BASE_PATH . '/classes/Database.php';
require_once BASE_PATH . '/classes/BaseModel.php';
require_once BASE_PATH . '/classes/Auth.php';
require_once BASE_PATH . '/classes/EventModel.php';
require_once BASE_PATH . '/classes/TicketTypeModel.php';
require_once BASE_PATH . '/classes/TicketModel.php';
require_once BASE_PATH . '/classes/SpeakerModel.php';
require_once BASE_PATH . '/classes/UserModel.php';

$auth = new Auth();

function e($string)
{
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

function rupiah($angka)
{
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

function tanggal_id($date_string)
{
    if (!$date_string) return '-';

    $bulan = [
        1=>'Januari', 2=>'Februari', 3=>'Maret', 4=>'April',
        5=>'Mei', 6=>'Juni', 7=>'Juli', 8=>'Agustus',
        9=>'September', 10=>'Oktober', 11=>'November', 12=>'Desember',
    ];

    $timestamp = strtotime($date_string);
    return date('d', $timestamp) . ' ' . $bulan[(int) date('n', $timestamp)] . ' ' . date('Y', $timestamp);
}

function csrf_input($auth)
{
    $token = $auth->generateCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

function show_flash($auth)
{
    $flashes = $auth->getFlash();
    if (empty($flashes)) return;

    $type_map = [
        'success' => ['bg'=>'#d1fae5','border'=>'#10b981','text'=>'#065f46'],
        'error'   => ['bg'=>'#fee2e2','border'=>'#ef4444','text'=>'#991b1b'],
        'warning' => ['bg'=>'#fef9c3','border'=>'#f59e0b','text'=>'#92400e'],
        'info'    => ['bg'=>'#dbeafe','border'=>'#3b82f6','text'=>'#1e40af'],
    ];

    foreach ($flashes as $type => $message) {
        $c = $type_map[$type] ?? $type_map['info'];
        echo '<div style="background:' . $c['bg'] . ';border-left:4px solid ' . $c['border'] . ';color:' . $c['text'] . ';padding:12px 16px;margin-bottom:16px;border-radius:4px;font-family:sans-serif;font-size:14px;">' . e($message) . '</div>';
    }
}
