<?php

require_once __DIR__ . '/../../config/bootstrap.php';

$auth->requireRole('admin');

$user_model        = new UserModel();
$event_model       = new EventModel();
$ticket_type_model = new TicketTypeModel();
$ticket_model      = new TicketModel();

$conn = Database::getInstance()->getConnection();

// Total per role
$stmt = $conn->query(
    "SELECT role, COUNT(*) AS total FROM users GROUP BY role"
);
$user_stats = [];
foreach ($stmt->fetchAll() as $row) {
    $user_stats[$row['role']] = $row['total'];
}

// Total event per status
$stmt = $conn->query(
    "SELECT status, COUNT(*) AS total FROM events GROUP BY status"
);
$event_stats = [];
foreach ($stmt->fetchAll() as $row) {
    $event_stats[$row['status']] = $row['total'];
}

// Total tiket + revenue + check-in
$stmt = $conn->query(
    "SELECT
        COUNT(*)                                            AS total_tiket,
        COUNT(CASE WHEN t.status = 'valid'      THEN 1 END) AS tiket_valid,
        COUNT(CASE WHEN t.status = 'checked_in' THEN 1 END) AS tiket_checkin,
        COUNT(CASE WHEN t.status = 'cancelled'  THEN 1 END) AS tiket_cancel
     FROM tickets t"
);
$tiket_stats = $stmt->fetch();

// Total revenue dari semua tiket yang tidak dibatalkan
$stmt = $conn->query(
    "SELECT COALESCE(SUM(tt.price), 0) AS total_revenue
     FROM tickets t
     JOIN ticket_types tt ON t.ticket_type_id = tt.id
     WHERE t.status != 'cancelled'"
);
$total_revenue = (float) $stmt->fetchColumn();

$stmt = $conn->query(
    "SELECT e.title, e.status, u.username AS organizer,
            SUM(tt.sold) AS total_terjual
     FROM events e
     JOIN ticket_types tt ON e.id = tt.event_id
     JOIN users u          ON e.organizer_id = u.id
     GROUP BY e.id
     ORDER BY total_terjual DESC
     LIMIT 5"
);
$top_events = $stmt->fetchAll();

$stmt = $conn->query(
    "SELECT DATE(created_at) AS tgl, COUNT(*) AS jumlah
     FROM users
     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
     GROUP BY DATE(created_at)
     ORDER BY tgl ASC"
);
$registrasi_harian = $stmt->fetchAll();

$stmt = $conn->query(
    "SELECT DATE(purchase_date) AS tgl, COUNT(*) AS jumlah
     FROM tickets
     WHERE purchase_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
       AND status != 'cancelled'
     GROUP BY DATE(purchase_date)
     ORDER BY tgl ASC"
);
$penjualan_harian = $stmt->fetchAll();

$stmt = $conn->prepare(
    "SELECT * FROM users ORDER BY created_at DESC LIMIT 5"
);
$stmt->execute();
$user_terbaru = $stmt->fetchAll();

$page_title = 'Dashboard Admin';
?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<?php show_flash($auth); ?>

<!-- ── Header ── -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color: var(--color-accent);">
            <i class="bi bi-shield-fill"></i> Dashboard Super Admin
        </h4>
        <p class="text-muted small mb-0">Ringkasan seluruh aktivitas sistem ElevateMe.</p>
    </div>
    <span class="badge px-3 py-2" style="background: var(--color-accent); font-size: 13px;">
        <i class="bi bi-calendar3"></i> <?= tanggal_id(date('Y-m-d')) ?>
    </span>
</div>

<div class="row g-3 mb-4">

    <!-- Total User -->
    <div class="col-6 col-md-3">
        <div class="card card-elevate p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 p-2" style="background: #fde8ea;">
                    <i class="bi bi-people-fill" style="font-size:1.6rem; color: var(--color-accent);"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold" style="color: var(--color-text);">
                        <?= array_sum($user_stats) ?>
                    </div>
                    <div class="small text-muted">Total User</div>
                </div>
            </div>
            <div class="mt-2 d-flex gap-2 flex-wrap">
                <span class="badge badge-admin">Admin: <?= $user_stats['admin'] ?? 0 ?></span>
                <span class="badge badge-organizer">Org: <?= $user_stats['organizer'] ?? 0 ?></span>
                <span class="badge badge-attendee">Att: <?= $user_stats['attendee'] ?? 0 ?></span>
            </div>
        </div>
    </div>

    <!-- Total Event -->
    <div class="col-6 col-md-3">
        <div class="card card-elevate p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 p-2" style="background: #dcfce7;">
                    <i class="bi bi-calendar-event-fill" style="font-size:1.6rem; color: #16a34a;"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold" style="color: var(--color-text);">
                        <?= array_sum($event_stats) ?>
                    </div>
                    <div class="small text-muted">Total Event</div>
                </div>
            </div>
            <div class="mt-2 d-flex gap-2 flex-wrap">
                <span class="badge bg-success">Live: <?= $event_stats['published'] ?? 0 ?></span>
                <span class="badge bg-warning text-dark">Draft: <?= $event_stats['draft'] ?? 0 ?></span>
                <span class="badge bg-secondary">Done: <?= $event_stats['completed'] ?? 0 ?></span>
            </div>
        </div>
    </div>

    <!-- Total Tiket -->
    <div class="col-6 col-md-3">
        <div class="card card-elevate p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 p-2" style="background: #dbeafe;">
                    <i class="bi bi-ticket-perforated-fill" style="font-size:1.6rem; color: #2563eb;"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold" style="color: var(--color-text);">
                        <?= $tiket_stats['total_tiket'] ?>
                    </div>
                    <div class="small text-muted">Total Tiket</div>
                </div>
            </div>
            <div class="mt-2 d-flex gap-2 flex-wrap">
                <span class="badge bg-success">Valid: <?= $tiket_stats['tiket_valid'] ?></span>
                <span class="badge bg-primary">Hadir: <?= $tiket_stats['tiket_checkin'] ?></span>
                <span class="badge bg-danger">Batal: <?= $tiket_stats['tiket_cancel'] ?></span>
            </div>
        </div>
    </div>

    <!-- Total Revenue -->
    <div class="col-6 col-md-3">
        <div class="card card-elevate p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 p-2" style="background: #fef9c3;">
                    <i class="bi bi-cash-stack" style="font-size:1.6rem; color: #c4830a;"></i>
                </div>
                <div>
                    <div class="fw-bold" style="color: var(--color-text); font-size: 1.1rem;">
                        <?= rupiah($total_revenue) ?>
                    </div>
                    <div class="small text-muted">Total Revenue</div>
                </div>
            </div>
            <div class="mt-2">
                <span class="small text-muted">
                    Rata-rata <?= $tiket_stats['total_tiket'] > 0
                        ? rupiah($total_revenue / $tiket_stats['total_tiket'])
                        : 'Rp 0' ?>/tiket
                </span>
            </div>
        </div>
    </div>

</div>

<div class="row g-4 mb-4">

    <div class="col-md-7">
        <div class="card card-elevate p-4">
            <h6 class="fw-bold mb-3" style="color: var(--color-accent);">
                <i class="bi bi-graph-up-arrow"></i> Penjualan Tiket 7 Hari Terakhir
            </h6>
            <?php
            // Buat array 7 hari terakhir dengan nilai default 0
            $hari_labels = [];
            $hari_values = [];
            $penjualan_map = [];

            foreach ($penjualan_harian as $ph) {
                $penjualan_map[$ph['tgl']] = $ph['jumlah'];
            }

            $max_val = 1; // Minimal 1 agar tidak dibagi 0
            for ($i = 6; $i >= 0; $i--) {
                $tgl           = date('Y-m-d', strtotime("-$i days"));
                $label         = date('d/m', strtotime($tgl));
                $val           = $penjualan_map[$tgl] ?? 0;
                $hari_labels[] = $label;
                $hari_values[] = $val;
                if ($val > $max_val) $max_val = $val;
            }
            ?>
            <div class="d-flex align-items-end gap-2" style="height: 120px;">
                <?php foreach ($hari_values as $idx => $val):
                    $tinggi_pct = round(($val / $max_val) * 100);
                    $tinggi_px  = max(4, round($tinggi_pct * 1.1)); // Min 4px agar terlihat
                ?>
                    <div class="flex-fill d-flex flex-column align-items-center justify-content-end gap-1"
                         style="height: 100%;">
                        <!-- Nilai di atas bar -->
                        <span class="small fw-bold" style="font-size: 10px; color: var(--color-text);">
                            <?= $val > 0 ? $val : '' ?>
                        </span>
                        <!-- Bar -->
                        <div style="
                            width: 100%;
                            height: <?= $tinggi_px ?>px;
                            background: <?= $val > 0 ? 'var(--color-accent)' : '#e8e0d8' ?>;
                            border-radius: 4px 4px 0 0;
                            min-height: 4px;
                            transition: height .3s;
                        "></div>
                        <!-- Label tanggal -->
                        <span class="small text-muted" style="font-size: 9px; margin-top: 2px;">
                            <?= $hari_labels[$idx] ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ── Top 5 Event Terpopuler ── -->
    <div class="col-md-5">
        <div class="card card-elevate p-4">
            <h6 class="fw-bold mb-3" style="color: var(--color-accent);">
                <i class="bi bi-trophy-fill"></i> Top 5 Event Terpopuler
            </h6>
            <?php if (empty($top_events)): ?>
                <p class="text-muted small">Belum ada data.</p>
            <?php else: ?>
                <?php foreach ($top_events as $rank => $ev): ?>
                    <div class="d-flex align-items-center gap-2 mb-2 pb-2 <?= $rank < count($top_events) - 1 ? 'border-bottom' : '' ?>">
                        <!-- Nomor ranking -->
                        <div class="fw-bold rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                             style="width:28px;height:28px;font-size:12px;
                                    background: <?= $rank === 0 ? '#f59e0b' : ($rank === 1 ? '#9ca3af' : ($rank === 2 ? '#c4830a' : '#e8e0d8')) ?>;
                                    color: <?= $rank < 3 ? '#fff' : '#666' ?>;">
                            <?= $rank + 1 ?>
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                            <div class="fw-semibold text-truncate small"><?= e($ev['title']) ?></div>
                            <div class="small text-muted"><?= e($ev['organizer']) ?></div>
                        </div>
                        <div class="text-end flex-shrink-0">
                            <div class="fw-bold" style="color: var(--color-accent);"><?= $ev['total_terjual'] ?></div>
                            <div class="small text-muted" style="font-size:10px;">tiket</div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</div>

<div class="row g-4">

    <!-- ── User Terbaru ── -->
    <div class="col-md-7">
        <div class="card card-elevate p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0" style="color: var(--color-accent);">
                    <i class="bi bi-person-plus-fill"></i> User Terbaru
                </h6>
                <a href="<?= BASE_URL ?>/views/users/index.php"
                   class="btn btn-outline-cta btn-sm">Lihat Semua</a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-elevate">
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th class="text-center">Role</th>
                            <th>Bergabung</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($user_terbaru as $u): ?>
                            <tr>
                                <td class="fw-semibold"><?= e($u['username']) ?></td>
                                <td class="small text-muted"><?= e($u['email']) ?></td>
                                <td class="text-center">
                                    <span class="badge badge-<?= e($u['role']) ?>">
                                        <?= e(ucfirst($u['role'])) ?>
                                    </span>
                                </td>
                                <td class="small text-muted">
                                    <?= tanggal_id($u['created_at']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ── Quick Actions Admin ── -->
    <div class="col-md-5">
        <div class="card card-elevate p-4">
            <h6 class="fw-bold mb-3" style="color: var(--color-accent);">
                <i class="bi bi-lightning-fill"></i> Aksi Cepat
            </h6>
            <div class="d-grid gap-2">
                <a href="<?= BASE_URL ?>/views/users/index.php?aksi=tambah"
                   class="btn btn-outline-cta text-start">
                    <i class="bi bi-person-plus me-2"></i> Tambah User Baru
                </a>
                <a href="<?= BASE_URL ?>/views/events/manage.php?aksi=tambah"
                   class="btn btn-outline-cta text-start">
                    <i class="bi bi-calendar-plus me-2"></i> Buat Event Baru
                </a>
                <a href="<?= BASE_URL ?>/views/events/manage.php"
                   class="btn btn-outline-cta text-start">
                    <i class="bi bi-calendar3 me-2"></i> Kelola Semua Event
                </a>
                <a href="<?= BASE_URL ?>/views/users/index.php"
                   class="btn btn-outline-cta text-start">
                    <i class="bi bi-people me-2"></i> Manajemen User
                </a>
                <a href="<?= BASE_URL ?>/views/checkin/index.php"
                   class="btn btn-cta text-start fw-semibold">
                    <i class="bi bi-qr-code-scan me-2"></i> Buka Check-in Scanner
                </a>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
