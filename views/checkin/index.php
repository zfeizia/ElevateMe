<?php

require_once __DIR__ . '/../../config/bootstrap.php';

$auth->requireRole(['admin', 'organizer']);

$ticket_model = new TicketModel();
$hasil_checkin = null;

if (isset($_POST['checkin'])) {
    $auth->verifyCsrfToken($_POST['csrf_token'] ?? '');

    $ticket_code = strtoupper(trim($_POST['ticket_code'] ?? ''));

    if (empty($ticket_code)) {
        $hasil_checkin = ['success' => false, 'message' => 'Kode tiket wajib diisi.'];
    } else {
        $hasil_checkin = $ticket_model->checkIn($ticket_code);
    }
}

$page_title = 'Check-in Peserta';
?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<?php show_flash($auth); ?>

<div class="row g-4">

    <!-- ── Form Check-in ── -->
    <div class="col-md-6">
        <div class="card card-elevate p-4">
            <h5 class="fw-bold mb-1" style="color: var(--color-accent);">
                <i class="bi bi-qr-code-scan"></i> Check-in Peserta
            </h5>
            <p class="text-muted small mb-4">Masukkan kode tiket peserta untuk memvalidasi kehadiran.</p>

            <form method="POST" action="">
                <?= csrf_input($auth) ?>

                <div class="mb-3">
                    <label for="ticket_code" class="form-label fw-semibold">Kode Tiket</label>
                    <input type="text" id="ticket_code" name="ticket_code"
                           class="form-control form-control-lg font-monospace text-center"
                           placeholder="EVT-XXXX-NNNNNN"
                           value="<?= isset($_POST['ticket_code']) ? e(strtoupper(trim($_POST['ticket_code']))) : '' ?>"
                           style="letter-spacing: 2px; font-size: 1rem;"
                           autofocus autocomplete="off"
                           oninput="this.value = this.value.toUpperCase()">
                    <div class="form-text">Format: EVT-XXXX-NNNNNN (otomatis uppercase)</div>
                </div>

                <div class="d-grid">
                    <button type="submit" name="checkin" class="btn btn-cta btn-lg fw-semibold">
                        <i class="bi bi-patch-check"></i> Validasi Check-in
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ── Hasil Validasi ── -->
    <div class="col-md-6">
        <?php if ($hasil_checkin !== null): ?>

            <?php if ($hasil_checkin['success']): ?>
                <!-- Check-in berhasil -->
                <div class="card card-elevate p-4 border-success" style="border-width: 2px !important;">
                    <div class="text-center mb-3">
                        <div style="font-size: 4rem; color: #10b981;">
                            <i class="bi bi-patch-check-fill"></i>
                        </div>
                        <h5 class="fw-bold text-success">Check-in Berhasil!</h5>
                        <p class="text-muted small mb-0"><?= e($hasil_checkin['message']) ?></p>
                    </div>
                    <?php $tiket = $hasil_checkin['tiket']; ?>
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-muted small" width="40%">Kode Tiket</td>
                            <td class="fw-bold font-monospace small"><?= e($tiket['ticket_code']) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Nama</td>
                            <td class="fw-semibold"><?= e($tiket['nama_pembeli']) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Tipe Tiket</td>
                            <td><?= e($tiket['tipe_tiket']) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Event</td>
                            <td><?= e($tiket['judul_event']) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Check-in Pada</td>
                            <td class="fw-semibold text-success">
                                <?= date('H:i:s', strtotime($tiket['checked_in_at'])) ?>,
                                <?= tanggal_id($tiket['checked_in_at']) ?>
                            </td>
                        </tr>
                    </table>
                </div>

            <?php else: ?>
                <!-- Check-in gagal -->
                <div class="card card-elevate p-4 border-danger" style="border-width: 2px !important;">
                    <div class="text-center mb-3">
                        <div style="font-size: 4rem; color: #ef4444;">
                            <i class="bi bi-x-circle-fill"></i>
                        </div>
                        <h5 class="fw-bold text-danger">Check-in Gagal</h5>
                        <p class="text-muted small mb-0"><?= e($hasil_checkin['message']) ?></p>
                    </div>

                    <?php if (isset($hasil_checkin['tiket'])): ?>
                        <?php $tiket = $hasil_checkin['tiket']; ?>
                        <div class="small text-center text-muted">
                            Tiket: <strong><?= e($tiket['ticket_code']) ?></strong>
                            <span class="ms-1">
                                <?php if ($tiket['status'] === 'checked_in'): ?>
                                    <span class="badge bg-primary">Sudah Check-in</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Dibatalkan</span>
                                <?php endif; ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- Placeholder sebelum ada pencarian -->
            <div class="card card-elevate p-5 text-center h-100 d-flex align-items-center justify-content-center"
                 style="min-height: 200px;">
                <i class="bi bi-qr-code" style="font-size: 3rem; color: #ccc;"></i>
                <p class="text-muted mt-2 mb-0 small">
                    Hasil validasi tiket akan muncul di sini
                </p>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- ── Riwayat Check-in Hari Ini ── -->
<div class="card card-elevate p-4 mt-4">
    <h6 class="fw-bold mb-3" style="color: var(--color-accent);">
        <i class="bi bi-list-check"></i> Riwayat Check-in Hari Ini
    </h6>
    <?php
    // Query check-in hari ini untuk organizer yang login
    $conn = Database::getInstance()->getConnection();

    if ($auth->getRole() === 'admin' || $auth->getRole() === 'organizer') {
        // Admin lihat semua
        $stmt = $conn->prepare(
            "SELECT t.ticket_code, t.checked_in_at, u.username AS nama_pembeli,
                    tt.name AS tipe_tiket, e.title AS judul_event
             FROM tickets t
             JOIN users u         ON t.buyer_id = u.id
             JOIN ticket_types tt ON t.ticket_type_id = tt.id
             JOIN events e        ON tt.event_id = e.id
             WHERE DATE(t.checked_in_at) = CURDATE()
             ORDER BY t.checked_in_at DESC
             LIMIT 20"
        );
        $stmt->execute();
    } else {
        // Organizer hanya lihat event miliknya
        $stmt = $conn->prepare(
            "SELECT t.ticket_code, t.checked_in_at, u.username AS nama_pembeli,
                    tt.name AS tipe_tiket, e.title AS judul_event
             FROM tickets t
             JOIN users u         ON t.buyer_id = u.id
             JOIN ticket_types tt ON t.ticket_type_id = tt.id
             JOIN events e        ON tt.event_id = e.id
             WHERE DATE(t.checked_in_at) = CURDATE()
               AND e.organizer_id = :oid
             ORDER BY t.checked_in_at DESC
             LIMIT 20"
        );
        $stmt->execute([':oid' => $auth->getUserId()]);
    }

    $riwayat = $stmt->fetchAll();
    ?>

    <?php if (empty($riwayat)): ?>
        <p class="text-muted small mb-0">Belum ada check-in hari ini.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead class="table-elevate">
                    <tr>
                        <th>Waktu</th>
                        <th>Kode Tiket</th>
                        <th>Nama Peserta</th>
                        <th>Tipe Tiket</th>
                        <th>Event</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($riwayat as $item): ?>
                        <tr>
                            <td class="small font-monospace">
                                <?= date('H:i:s', strtotime($item['checked_in_at'])) ?>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border font-monospace" style="font-size: 10px;">
                                    <?= e($item['ticket_code']) ?>
                                </span>
                            </td>
                            <td><?= e($item['nama_pembeli']) ?></td>
                            <td><span class="badge" style="background: var(--color-accent);"><?= e($item['tipe_tiket']) ?></span></td>
                            <td class="small"><?= e($item['judul_event']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
