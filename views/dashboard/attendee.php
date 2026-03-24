<?php

require_once __DIR__ . '/../../config/bootstrap.php';

$auth->requireRole('attendee');

$ticket_model   = new TicketModel();
$waitlist_model = new WaitlistModel();

$buyer_id    = $auth->getUserId();
$tiket_saya  = $ticket_model->getByBuyer($buyer_id);
$waitlist    = $waitlist_model->getByUser($buyer_id);
$notif_count = $waitlist_model->getEligibleCount($buyer_id);

// Tab aktif: default 'tickets', bisa 'waitlist'
$active_tab = $_GET['tab'] ?? 'tickets';

$page_title = 'Dashboard — Tiket Saya';
?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<?php show_flash($auth); ?>

<!-- ── Header Dashboard ── -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color: var(--color-accent);">
            <i class="bi bi-person-circle"></i> Halo, <?= e($_SESSION['username']) ?>!
        </h4>
        <p class="text-muted small mb-0">Kelola tiket dan aktivitas eventmu di sini.</p>
    </div>
    <a href="<?= BASE_URL ?>/index.php" class="btn btn-cta btn-sm fw-semibold">
        <i class="bi bi-search"></i> Cari Event
    </a>
</div>

<!-- ── Statistik Cepat ── -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card card-elevate p-3 text-center">
            <div class="fs-2 fw-bold" style="color: var(--color-accent);">
                <?= count($tiket_saya) ?>
            </div>
            <div class="small text-muted">Total Tiket</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card card-elevate p-3 text-center">
            <div class="fs-2 fw-bold text-success">
                <?= count(array_filter($tiket_saya, fn($t) => $t['status'] === 'valid')) ?>
            </div>
            <div class="small text-muted">Tiket Valid</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card card-elevate p-3 text-center">
            <div class="fs-2 fw-bold text-primary">
                <?= count(array_filter($tiket_saya, fn($t) => $t['status'] === 'checked_in')) ?>
            </div>
            <div class="small text-muted">Sudah Hadir</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card card-elevate p-3 text-center">
            <div class="fs-2 fw-bold" style="color: #f59e0b;">
                <?= count(array_filter($waitlist, fn($w) => $w['status'] === 'waiting')) ?>
                <?php if ($notif_count > 0): ?>
                    <span class="badge bg-warning text-dark" style="font-size: 12px;"><?= $notif_count ?></span>
                <?php endif; ?>
            </div>
            <div class="small text-muted">Di Waitlist</div>
        </div>
    </div>
</div>

<!-- ── Tabs ── -->
<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link <?= $active_tab === 'tickets' ? 'active' : '' ?>"
           href="?tab=tickets" style="color: var(--color-accent);">
            <i class="bi bi-ticket-perforated"></i> Tiket Saya
            <span class="badge bg-secondary ms-1"><?= count($tiket_saya) ?></span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $active_tab === 'waitlist' ? 'active' : '' ?>"
           href="?tab=waitlist" style="color: var(--color-accent);">
            <i class="bi bi-hourglass-split"></i> Waitlist
            <?php if ($notif_count > 0): ?>
                <span class="badge bg-warning text-dark ms-1"><?= $notif_count ?> baru!</span>
            <?php endif; ?>
        </a>
    </li>
</ul>

<!-- ── TAB: Tiket Saya ── -->
<?php if ($active_tab === 'tickets'): ?>
    <?php if (empty($tiket_saya)): ?>
        <div class="text-center py-5">
            <i class="bi bi-ticket-perforated" style="font-size: 3rem; color: #ccc;"></i>
            <p class="mt-2 text-muted">Kamu belum punya tiket.</p>
            <a href="<?= BASE_URL ?>/index.php" class="btn btn-cta">
                <i class="bi bi-search"></i> Temukan Event
            </a>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($tiket_saya as $tiket): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card card-elevate h-100">

                        <!-- Status bar atas -->
                        <div class="p-2 text-center text-white small fw-semibold rounded-top"
                             style="background: <?= $tiket['status'] === 'valid' ? '#10b981' : ($tiket['status'] === 'checked_in' ? '#2563eb' : '#ef4444') ?>">
                            <?php if ($tiket['status'] === 'valid'): ?>
                                <i class="bi bi-check-circle"></i> Valid
                            <?php elseif ($tiket['status'] === 'checked_in'): ?>
                                <i class="bi bi-patch-check-fill"></i> Sudah Check-in
                            <?php else: ?>
                                <i class="bi bi-x-circle"></i> Dibatalkan
                            <?php endif; ?>
                        </div>

                        <div class="p-3">
                            <div class="fw-bold mb-1"><?= e($tiket['judul_event']) ?></div>
                            <div class="small text-muted mb-1">
                                <i class="bi bi-ticket" style="color: var(--color-accent);"></i>
                                <?= e($tiket['tipe_tiket']) ?>
                            </div>
                            <div class="small text-muted mb-1">
                                <i class="bi bi-calendar3" style="color: var(--color-accent);"></i>
                                <?= tanggal_id($tiket['start_date']) ?>
                            </div>
                            <div class="small text-muted mb-2">
                                <i class="bi bi-geo-alt" style="color: var(--color-accent);"></i>
                                <?= e($tiket['location']) ?>
                            </div>
                            <div class="font-monospace small p-2 rounded text-center mb-3"
                                 style="background: #f5f0eb; border: 1px dashed #c4a882; color: var(--color-accent); font-weight: 600; letter-spacing: 1px;">
                                <?= e($tiket['ticket_code']) ?>
                            </div>
                            <a href="<?= BASE_URL ?>/views/tickets/detail.php?kode=<?= urlencode($tiket['ticket_code']) ?>"
                               class="btn btn-cta btn-sm w-100">
                                <i class="bi bi-eye"></i> Lihat QR Code
                            </a>
                        </div>

                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

<!-- ── TAB: Waitlist ── -->
<?php elseif ($active_tab === 'waitlist'): ?>
    <?php if (empty($waitlist)): ?>
        <div class="text-center py-5">
            <i class="bi bi-hourglass" style="font-size: 3rem; color: #ccc;"></i>
            <p class="mt-2 text-muted">Kamu tidak sedang mengantri di waitlist manapun.</p>
        </div>
    <?php else: ?>
        <div class="card card-elevate">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-elevate">
                        <tr>
                            <th>Event</th>
                            <th>Tipe Tiket</th>
                            <th>Terdaftar</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($waitlist as $wl): ?>
                            <tr class="<?= $wl['status'] === 'eligible' ? 'table-warning' : '' ?>">
                                <td class="fw-semibold"><?= e($wl['judul_event']) ?></td>
                                <td><?= e($wl['tipe_tiket']) ?></td>
                                <td class="small text-muted">
                                    <?= tanggal_id($wl['registered_at']) ?>
                                </td>
                                <td>
                                    <?php if ($wl['status'] === 'eligible'): ?>
                                        <span class="badge bg-warning text-dark">
                                            <i class="bi bi-bell-fill"></i> Ada Slot!
                                        </span>
                                    <?php elseif ($wl['status'] === 'waiting'): ?>
                                        <span class="badge bg-secondary">Menunggu</span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border">Expired</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($wl['status'] === 'eligible'): ?>
                                        <!-- Tombol untuk acknowledge notifikasi -->
                                        <form method="POST"
                                              action="<?= BASE_URL ?>/views/tickets/waitlist_ack.php"
                                              class="d-inline">
                                            <?= csrf_input($auth) ?>
                                            <input type="hidden" name="waitlist_id" value="<?= $wl['id'] ?>">
                                            <button type="submit" class="btn btn-warning btn-sm fw-semibold">
                                                <i class="bi bi-check2"></i> Tandai Dibaca
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
