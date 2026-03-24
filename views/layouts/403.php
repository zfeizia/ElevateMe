<?php

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/bootstrap.php';
}
$page_title = '403 — Akses Ditolak';
?>
<?php require_once __DIR__ . '/header.php'; ?>

<div class="text-center py-5">
    <div style="font-size: 5rem; color: var(--color-accent);">
        <i class="bi bi-shield-x"></i>
    </div>
    <h2 class="fw-bold mt-3 mb-2" style="color: var(--color-text);">Akses Ditolak</h2>
    <p class="text-muted mb-4">
        Kamu tidak memiliki izin untuk mengakses halaman ini.<br>
        Role kamu saat ini: <strong><?= e($auth->getRole() ?? 'Belum Login') ?></strong>
    </p>
    <a href="<?= BASE_URL ?>/index.php" class="btn btn-cta fw-semibold">
        <i class="bi bi-house"></i> Kembali ke Beranda
    </a>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
