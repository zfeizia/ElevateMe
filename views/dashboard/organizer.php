<?php

require_once __DIR__ . '/../../config/bootstrap.php';

$auth->requireRole(['admin', 'organizer']);

$event_model       = new EventModel();
$ticket_type_model = new TicketTypeModel();
$ticket_model      = new TicketModel();
$organizer_id      = $auth->getUserId();

$events_saya = ($auth->getRole() === 'admin')
    ? $event_model->getAllWithOrganizer(100, 0)
    : $event_model->getByOrganizer($organizer_id, 100, 0);

// Hitung statistik ringkasan
$total_events   = count($events_saya);
$total_tiket    = 0;
$total_revenue  = 0;
$total_checkin  = 0;

foreach ($events_saya as $ev) {
    $tipe_tikets = $ticket_type_model->getByEvent($ev['id']);
    foreach ($tipe_tikets as $tt) {
        $total_tiket   += $tt['sold'];
        $total_revenue += $tt['sold'] * $ticket_type_model->getCurrentPrice($tt);
    }
    $laporan = $ticket_type_model->getAttendanceReport($ev['id']);
    foreach ($laporan as $lap) {
        $total_checkin += $lap['total_checkin'];
    }
}

// Event yang dipilih untuk laporan detail (dari query string ?event_id=X)
$selected_event_id = (int) ($_GET['event_id'] ?? ($events_saya[0]['id'] ?? 0));
$laporan_kehadiran = [];
$revenue_breakdown = [];

if ($selected_event_id > 0) {
    $laporan_kehadiran = $ticket_type_model->getAttendanceReport($selected_event_id);
    $revenue_breakdown = $ticket_type_model->getRevenueBreakdown($selected_event_id);
}

$page_title = 'Dashboard Organizer';
?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<?php show_flash($auth); ?>

<!-- ── Header ── -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color: var(--color-accent);">
            <i class="bi bi-speedometer2"></i>
            <?= $auth->getRole() === 'admin' ? 'Dashboard Admin' : 'Dashboard Organizer' ?>
        </h4>
        <p class="text-muted small mb-0">Kelola event dan pantau performa tiket kamu.</p>
    </div>
    <a href="<?= BASE_URL ?>/views/events/manage.php?aksi=tambah"
       class="btn btn-cta fw-semibold">
        <i class="bi bi-plus-circle"></i> Buat Event Baru
    </a>
</div>

<!-- ── Statistik Ringkasan ── -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card card-elevate p-3 text-center">
            <div class="fs-2 fw-bold" style="color: var(--color-accent);"><?= $total_events ?></div>
            <div class="small text-muted">Total Event</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card card-elevate p-3 text-center">
            <div class="fs-2 fw-bold text-success"><?= $total_tiket ?></div>
            <div class="small text-muted">Tiket Terjual</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card card-elevate p-3 text-center">
            <div class="fs-2 fw-bold text-primary"><?= $total_checkin ?></div>
            <div class="small text-muted">Total Hadir</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card card-elevate p-3 text-center">
            <div class="fs-2 fw-bold" style="color: #c4830a;">
                <?= $total_tiket > 0 ? round($total_checkin / $total_tiket * 100) : 0 ?>%
            </div>
            <div class="small text-muted">Tingkat Kehadiran</div>
        </div>
    </div>
</div>

<!-- ── Pilih Event untuk Laporan ── -->
<div class="card card-elevate p-4 mb-4">
    <h6 class="fw-bold mb-3" style="color: var(--color-accent);">
        <i class="bi bi-bar-chart-fill"></i> Laporan Kehadiran per Event
    </h6>

    <form method="GET" action="" class="d-flex gap-2 mb-4 flex-wrap">
        <select name="event_id" class="form-select" style="max-width: 350px;">
            <?php foreach ($events_saya as $ev): ?>
                <option value="<?= $ev['id'] ?>"
                    <?= $ev['id'] == $selected_event_id ? 'selected' : '' ?>>
                    <?= e($ev['title']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-outline-cta fw-semibold">
            <i class="bi bi-filter"></i> Tampilkan
        </button>
    </form>

    <?php if (!empty($laporan_kehadiran)): ?>
        <div class="table-responsive">
            <table class="table table-bordered table-hover mb-0">
                <thead class="table-elevate">
                    <tr>
                        <th>Tipe Tiket</th>
                        <th class="text-center">Kuota</th>
                        <th class="text-center">Terjual</th>
                        <th class="text-center">Check-in</th>
                        <th class="text-center">% Hadir</th>
                        <th>Progress</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($laporan_kehadiran as $lap): ?>
                        <?php
                        $persen = (float) $lap['persen_hadir'];
                        // Warna bar berdasarkan persentase kehadiran
                        $bar_color = $persen >= 80 ? '#10b981' : ($persen >= 50 ? '#f59e0b' : '#ef4444');
                        ?>
                        <tr>
                            <td class="fw-semibold"><?= e($lap['tipe_tiket']) ?></td>
                            <td class="text-center"><?= $lap['quota'] ?></td>
                            <td class="text-center"><?= $lap['total_terjual'] ?></td>
                            <td class="text-center fw-bold text-primary"><?= $lap['total_checkin'] ?></td>
                            <td class="text-center fw-bold" style="color: <?= $bar_color ?>">
                                <?= $persen ?>%
                            </td>
                            <td style="min-width: 120px;">
                                <div style="background: #e8e0d8; border-radius: 4px; height: 10px; overflow: hidden;">
                                    <div style="width: <?= $persen ?>%; background: <?= $bar_color ?>; height: 100%; border-radius: 4px; transition: width .4s;"></div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-muted small mb-0">Pilih event untuk melihat laporan kehadiran.</p>
    <?php endif; ?>
</div>

<?php if (!empty($revenue_breakdown)): ?>
    <?php
    $total_rev   = array_sum(array_column($revenue_breakdown, 'total_revenue'));
    $colors      = ['#7A1B22', '#9B222B', '#c4394a', '#e06070', '#f59e0b', '#10b981'];
    $conic_parts = [];
    $current_pct = 0;

    foreach ($revenue_breakdown as $i => $rb) {
        $pct           = $total_rev > 0 ? round($rb['total_revenue'] / $total_rev * 100, 1) : 0;
        $end_pct       = $current_pct + $pct;
        $color         = $colors[$i % count($colors)];
        $conic_parts[] = "$color {$current_pct}% {$end_pct}%";
        $current_pct   = $end_pct;
    }

    $conic_gradient = 'conic-gradient(' . implode(', ', $conic_parts) . ')';
    ?>

    <div class="card card-elevate p-4 mb-4">
        <h6 class="fw-bold mb-3" style="color: var(--color-accent);">
            <i class="bi bi-pie-chart-fill"></i> Revenue Breakdown per Tipe Tiket
        </h6>

        <div class="row align-items-center g-4">
            <!-- Pie Chart CSS -->
            <div class="col-md-4 text-center">
                <div style="
                    width: 180px;
                    height: 180px;
                    border-radius: 50%;
                    background: <?= $conic_gradient ?>;
                    margin: 0 auto;
                    box-shadow: 0 4px 16px rgba(122,27,34,0.15);
                "></div>
                <div class="mt-2 fw-bold" style="color: var(--color-accent);">
                    Total: <?= rupiah($total_rev) ?>
                </div>
            </div>

            <!-- Legenda -->
            <div class="col-md-8">
                <div class="row g-2">
                    <?php foreach ($revenue_breakdown as $i => $rb):
                        $pct   = $total_rev > 0 ? round($rb['total_revenue'] / $total_rev * 100, 1) : 0;
                        $color = $colors[$i % count($colors)];
                    ?>
                        <div class="col-12">
                            <div class="d-flex align-items-center gap-2">
                                <div style="width:14px;height:14px;border-radius:3px;background:<?= $color ?>;flex-shrink:0;"></div>
                                <div class="flex-grow-1 small">
                                    <span class="fw-semibold"><?= e($rb['tipe_tiket']) ?></span>
                                    <span class="text-muted ms-1">(<?= $rb['sold'] ?> tiket × <?= rupiah($rb['price']) ?>)</span>
                                </div>
                                <div class="fw-bold small" style="color: <?= $color ?>;">
                                    <?= $pct ?>%
                                </div>
                                <div class="small text-muted">
                                    <?= rupiah($rb['total_revenue']) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- ── Daftar Event ── -->
<div class="card card-elevate p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0" style="color: var(--color-accent);">
            <i class="bi bi-calendar3"></i> Event Saya
        </h6>
        <a href="<?= BASE_URL ?>/views/events/manage.php" class="btn btn-outline-cta btn-sm">
            Kelola Semua
        </a>
    </div>

    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-elevate">
                <tr>
                    <th>Judul Event</th>
                    <th>Tanggal</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Terjual</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (array_slice($events_saya, 0, 10) as $ev): ?>
                    <tr>
                        <td class="fw-semibold"><?= e($ev['title']) ?></td>
                        <td class="small text-muted"><?= tanggal_id($ev['start_date']) ?></td>
                        <td class="text-center">
                            <span class="badge <?= $ev['status'] === 'published' ? 'bg-success' : ($ev['status'] === 'draft' ? 'bg-warning text-dark' : 'bg-secondary') ?>">
                                <?= e(ucfirst($ev['status'])) ?>
                            </span>
                        </td>
                        <td class="text-center fw-bold">
                            <?php
                            $tipe_ev = $ticket_type_model->getByEvent($ev['id']);
                            echo array_sum(array_column($tipe_ev, 'sold'));
                            ?>
                        </td>
                        <td>
                            <a href="<?= BASE_URL ?>/views/events/detail.php?id=<?= $ev['id'] ?>"
                               class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="<?= BASE_URL ?>/views/events/manage.php?aksi=edit&id=<?= $ev['id'] ?>"
                               class="btn btn-outline-cta btn-sm">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="?event_id=<?= $ev['id'] ?>#laporan"
                               class="btn btn-sm"
                               style="background: var(--color-accent); color: #fff;">
                                <i class="bi bi-bar-chart"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
