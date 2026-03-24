<?php
require_once __DIR__ . '/../../config/bootstrap.php';

$auth->requireLogin();

$kode         = trim($_GET['kode'] ?? '');
$ticket_model = new TicketModel();

if (empty($kode)) {
    header('Location: ' . BASE_URL . '/views/dashboard/attendee.php');
    exit;
}

$tiket = $ticket_model->getByTicketCode($kode);

if (!$tiket) {
    $auth->setFlash('error', 'Tiket tidak ditemukan.');
    header('Location: ' . BASE_URL . '/views/dashboard/attendee.php');
    exit;
}

$is_owner = ($tiket['buyer_id'] == $auth->getUserId());
$is_staff = in_array($auth->getRole(), ['admin', 'organizer']);

if (!$is_owner && !$is_staff) {
    http_response_code(403);
    include __DIR__ . '/../layouts/403.php';
    exit;
}

// Generate QR Code ASCII Art
$qr_html = $ticket_model->generateAsciiQR($tiket['ticket_code']);

$page_title = 'Detail Tiket — ' . $tiket['ticket_code'];
?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<?php show_flash($auth); ?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">

        <!-- ── Header Tiket ── -->
        <div class="text-center mb-4">
            <h4 class="fw-bold" style="color: var(--color-accent);">
                <i class="bi bi-ticket-perforated-fill"></i> Detail Tiket
            </h4>
        </div>

        <!-- ── Kartu Tiket ── -->
        <div class="card card-elevate overflow-hidden">

            <!-- Header kartu -->
            <div class="p-4 text-white text-center"
                 style="background: linear-gradient(135deg, #7A1B22, #9B222B);">
                <div class="fw-bold fs-5 mb-1"><?= e($tiket['judul_event']) ?></div>
                <div class="small opacity-75">
                    <i class="bi bi-geo-alt-fill"></i> <?= e($tiket['location']) ?>
                </div>
                <div class="small opacity-75">
                    <i class="bi bi-calendar3"></i> <?= tanggal_id($tiket['start_date']) ?>
                </div>
            </div>

            <div style="border-top: 3px dashed #e8e0d8; position:relative;">
                <div style="width:24px;height:24px;background:var(--color-bg);border-radius:50%;
                            position:absolute;top:-12px;left:-12px;border:2px solid #e8e0d8;"></div>
                <div style="width:24px;height:24px;background:var(--color-bg);border-radius:50%;
                            position:absolute;top:-12px;right:-12px;border:2px solid #e8e0d8;"></div>
            </div>

            <div class="p-4">

                <!-- Info Tiket -->
                <div class="row g-3 mb-4">
                    <div class="col-6">
                        <div class="small text-muted">Nama Pembeli</div>
                        <div class="fw-semibold"><?= e($tiket['nama_pembeli']) ?></div>
                    </div>
                    <div class="col-6">
                        <div class="small text-muted">Tipe Tiket</div>
                        <div class="fw-semibold"><?= e($tiket['tipe_tiket']) ?></div>
                    </div>
                    <div class="col-6">
                        <div class="small text-muted">Kode Tiket</div>
                        <div class="fw-bold font-monospace" style="color: var(--color-accent); font-size: 0.85rem;">
                            <?= e($tiket['ticket_code']) ?>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="small text-muted">Status</div>
                        <div>
                            <?php if ($tiket['status'] === 'valid'): ?>
                                <span class="badge bg-success">
                                    <i class="bi bi-check-circle"></i> Valid
                                </span>
                            <?php elseif ($tiket['status'] === 'checked_in'): ?>
                                <span class="badge bg-primary">
                                    <i class="bi bi-patch-check-fill"></i> Sudah Check-in
                                </span>
                            <?php else: ?>
                                <span class="badge bg-danger">
                                    <i class="bi bi-x-circle"></i> Dibatalkan
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="small text-muted">Tanggal Beli</div>
                        <div class="fw-semibold small">
                            <?= tanggal_id($tiket['purchase_date']) ?>
                            <?= date('H:i', strtotime($tiket['purchase_date'])) ?>
                        </div>
                    </div>
                    <?php if ($tiket['checked_in_at']): ?>
                        <div class="col-6">
                            <div class="small text-muted">Waktu Check-in</div>
                            <div class="fw-semibold small text-success">
                                <?= tanggal_id($tiket['checked_in_at']) ?>
                                <?= date('H:i', strtotime($tiket['checked_in_at'])) ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- ── Simulasi QR Code ASCII Art ── -->
                <div class="text-center mb-4">
                    <div class="small text-muted mb-2 fw-semibold">
                        <i class="bi bi-qr-code"></i> QR Code Tiket (Simulasi)
                    </div>
                    <div class="d-inline-block p-3 rounded-2"
                         style="background: #fff; border: 1px solid #e8e0d8;">
                        <?= $qr_html ?>
                        <div class="font-monospace small mt-2" style="font-size: 10px; color: #888;">
                            <?= e($tiket['ticket_code']) ?>
                        </div>
                    </div>
                </div>

                <!-- ── Tombol Aksi ── -->
                <div class="d-flex gap-2 flex-wrap">
                    <button onclick="window.print()" class="btn btn-outline-cta btn-sm flex-fill">
                        <i class="bi bi-printer"></i> Cetak Tiket
                    </button>
                    <?php if ($is_owner && $tiket['status'] === 'valid'): ?>
                        <form method="POST"
                              action="<?= BASE_URL ?>/views/tickets/batalkan.php"
                              onsubmit="return confirm('Yakin ingin membatalkan tiket ini? Tindakan tidak bisa dibatalkan.')">
                            <?= csrf_input($auth) ?>
                            <input type="hidden" name="ticket_id" value="<?= $tiket['id'] ?>">
                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                <i class="bi bi-x-circle"></i> Batalkan
                            </button>
                        </form>
                    <?php endif; ?>
                    <a href="<?= BASE_URL ?>/views/dashboard/attendee.php"
                       class="btn btn-outline-secondary btn-sm flex-fill">
                        <i class="bi bi-arrow-left"></i> Tiket Saya
                    </a>
                </div>

            </div>
        </div>

    </div>
</div>

<!-- Print-friendly style -->
<style>
@media print {
    nav, footer, .btn, .breadcrumb { display: none !important; }
    body { background: #fff !important; }
    .card { box-shadow: none !important; border: 1px solid #ccc !important; }
}
</style>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
