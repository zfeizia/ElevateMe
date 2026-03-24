<?php

require_once __DIR__ . '/../../config/bootstrap.php';

$event_id = (int) ($_GET['id'] ?? 0);
if ($event_id <= 0) { header('Location: ' . BASE_URL . '/index.php'); exit; }

$event_model       = new EventModel();
$ticket_type_model = new TicketTypeModel();
$speaker_model     = new SpeakerModel();
$waitlist_model    = new WaitlistModel();

$event       = $event_model->getByIdWithOrganizer($event_id);
$speakers    = $speaker_model->getByEvent($event_id);
$tipe_tikets = $ticket_type_model->getByEvent($event_id);

if (!$event) { header('Location: ' . BASE_URL . '/index.php'); exit; }

if ($event['status'] !== 'published' && !in_array($auth->getRole(), ['admin', 'organizer'])) {
    header('Location: ' . BASE_URL . '/index.php'); exit;
}

// Ambil event serupa (kategori sama, acak)
$related_events = $event_model->getRelated($event_id, $event['category'] ?? '', 3);

$page_title = $event['title'];
?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&display=swap');
@keyframes fadeUp { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:translateY(0)} }
.sec-heading { font-family:'Playfair Display',serif;font-size:1.4rem;font-weight:700;color:#2B2523;margin-bottom:4px; }
.sec-line    { width:32px;height:3px;background:#7A1B22;border-radius:2px;margin-bottom:24px; }
.ev-card     { border:1px solid #ece5dc;border-radius:14px;background:#fff;overflow:hidden;transition:transform .22s,box-shadow .22s;height:100%;display:flex;flex-direction:column; }
.ev-card:hover { transform:translateY(-5px);box-shadow:0 16px 40px rgba(122,27,34,.12); }
.ev-img      { height:164px;object-fit:cover;width:100%; }
.ev-ph       { height:164px;display:flex;align-items:center;justify-content:center;background:linear-gradient(145deg,#7A1B22,#c4394a); }
.ev-body     { padding:16px;flex:1;display:flex;flex-direction:column; }
.ev-title    { font-weight:700;font-size:.9rem;color:#2B2523;margin-bottom:5px;line-height:1.4; }
.ev-meta     { display:flex;align-items:center;gap:5px;font-size:.75rem;color:#8a7370;margin-bottom:3px; }
.ev-meta i   { color:#7A1B22;font-size:.73rem; }
.ev-foot     { display:flex;align-items:center;justify-content:space-between;padding-top:10px;border-top:1px solid #f3ede6;margin-top:auto; }
.ev-price    { font-weight:700;font-size:.95rem;color:#7A1B22; }
.ev-lbl      { font-size:.68rem;color:#a09490;display:block; }
.btn-ev      { background:linear-gradient(135deg,#7A1B22,#9B222B);color:#fff;border:none;border-radius:7px;padding:6px 12px;font-size:.76rem;font-weight:600;text-decoration:none;transition:opacity .18s; }
.btn-ev:hover { opacity:.88;color:#fff; }
</style>

<?php show_flash($auth); ?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb small">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php" style="color:#7A1B22;">Beranda</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/views/events/explore.php" style="color:#7A1B22;">Jelajah Event</a></li>
        <li class="breadcrumb-item active"><?= e($event['title']) ?></li>
    </ol>
</nav>

<div class="row g-4">

    <!-- ── Kolom Kiri: Info Event ── -->
    <div class="col-lg-8">

        <?php if (!empty($event['banner'])): ?>
            <img src="<?= BASE_URL ?>/public/uploads/banners/<?= e($event['banner']) ?>"
                 class="img-fluid rounded-3 w-100 mb-4"
                 style="max-height:380px;object-fit:cover;"
                 alt="<?= e($event['title']) ?>">
        <?php else: ?>
            <div class="rounded-3 d-flex align-items-center justify-content-center mb-4"
                 style="height:280px;background:linear-gradient(135deg,#7A1B22,#c4394a);">
                <i class="bi bi-calendar-event text-white" style="font-size:4rem;opacity:.6;"></i>
            </div>
        <?php endif; ?>

        <!-- Judul & badge -->
        <div class="d-flex align-items-start justify-content-between gap-2 mb-2 flex-wrap">
            <h1 class="fw-bold h2 mb-0" style="color:#2B2523;"><?= e($event['title']) ?></h1>
            <div class="d-flex gap-2 flex-wrap">
                <?php if (!empty($event['category'])): ?>
                    <span class="badge px-3 py-2" style="background:#fde8ea;color:#7A1B22;font-size:.75rem;">
                        <?= e($event['category']) ?>
                    </span>
                <?php endif; ?>
                <?php if (!empty($event['is_online'])): ?>
                    <span class="badge px-3 py-2" style="background:#e0f2fe;color:#0369a1;font-size:.75rem;">
                        <i class="bi bi-wifi"></i> Online
                    </span>
                <?php endif; ?>
                <span class="badge rounded-pill px-3 py-2 <?= $event['status']==='published' ? 'bg-success' : ($event['status']==='completed' ? 'bg-secondary' : 'bg-warning text-dark') ?>">
                    <?= e(ucfirst($event['status'])) ?>
                </span>
            </div>
        </div>

        <!-- Meta -->
        <div class="d-flex flex-wrap gap-3 mb-4 small text-muted">
            <span><i class="bi bi-person-badge" style="color:#7A1B22;"></i> <?= e($event['organizer_name']) ?></span>
            <span><i class="bi bi-calendar3"    style="color:#7A1B22;"></i> <?= tanggal_id($event['start_date']) ?></span>
            <span><i class="bi bi-clock"         style="color:#7A1B22;"></i> <?= date('H:i', strtotime($event['start_date'])) ?> – <?= date('H:i', strtotime($event['end_date'])) ?></span>
            <span>
                <?php if (!empty($event['is_online'])): ?>
                    <i class="bi bi-wifi" style="color:#7A1B22;"></i> Online
                <?php else: ?>
                    <i class="bi bi-geo-alt-fill" style="color:#7A1B22;"></i> <?= e($event['location']) ?>
                <?php endif; ?>
            </span>
        </div>

        <!-- Deskripsi -->
        <div class="card card-elevate p-4 mb-4">
            <h5 class="fw-bold mb-3" style="color:#7A1B22;"><i class="bi bi-info-circle"></i> Tentang Event</h5>
            <p class="mb-0" style="line-height:1.8;"><?= nl2br(e($event['description'])) ?></p>
        </div>

        <!-- Speaker -->
        <?php if (!empty($speakers)): ?>
        <div class="card card-elevate p-4 mb-4">
            <h5 class="fw-bold mb-3" style="color:#7A1B22;"><i class="bi bi-mic-fill"></i> Lineup Pembicara</h5>
            <div class="row g-3">
                <?php foreach ($speakers as $spk): ?>
                <div class="col-md-6">
                    <div class="d-flex gap-3 align-items-start p-3 rounded-3" style="background:#faf7f3;border:1px solid #ece5dc;">
                        <?php if (!empty($spk['photo'])): ?>
                            <img src="<?= BASE_URL ?>/public/uploads/speakers/<?= e($spk['photo']) ?>"
                                 class="rounded-circle" style="width:52px;height:52px;object-fit:cover;" alt="">
                        <?php else: ?>
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                 style="width:52px;height:52px;background:#7A1B22;color:#fff;font-size:1.3rem;">
                                <i class="bi bi-person-fill"></i>
                            </div>
                        <?php endif; ?>
                        <div>
                            <div class="fw-bold small"><?= e($spk['name']) ?></div>
                            <div class="small" style="color:#7A1B22;"><?= e($spk['topic']) ?></div>
                            <div class="small text-muted"><i class="bi bi-clock"></i> <?= date('H:i', strtotime($spk['session_time'])) ?>, <?= tanggal_id($spk['session_time']) ?></div>
                            <?php if (!empty($spk['bio'])): ?>
                                <div class="small text-muted mt-1"><?= e(mb_substr($spk['bio'], 0, 90)) ?>...</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- ── Kolom Kanan: Pilihan Tiket ── -->
    <div class="col-lg-4">
        <div class="card card-elevate p-4" style="position:sticky;top:80px;">
            <h5 class="fw-bold mb-3" style="color:#7A1B22;"><i class="bi bi-ticket-perforated"></i> Pilih Tiket</h5>

            <?php if (empty($tipe_tikets)): ?>
                <p class="text-muted small">Belum ada tipe tiket untuk event ini.</p>
            <?php else: ?>
                <?php foreach ($tipe_tikets as $tt):
                    $harga      = $ticket_type_model->getCurrentPrice($tt);
                    $is_eb      = $ticket_type_model->isEarlyBirdActive($tt);
                    $tersedia   = $ticket_type_model->isAvailable($tt['id']);
                    $sisa       = $tt['quota'] - $tt['sold'];
                    $is_wl      = false;
                    if ($auth->isLoggedIn()) $is_wl = $waitlist_model->isOnWaitlist($tt['id'], $auth->getUserId());
                ?>
                <div class="p-3 mb-3 rounded-3 border <?= !$tersedia ? 'opacity-75' : '' ?>" style="background:#faf7f3;border-color:#ece5dc !important;">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <span class="fw-bold small"><?= e($tt['name']) ?></span>
                        <?php if ($is_eb): ?><span class="badge" style="background:#f59e0b;font-size:10px;">⚡ Early Bird</span><?php endif; ?>
                    </div>
                    <?php if (!empty($tt['description'])): ?>
                        <p class="small text-muted mb-2"><?= e($tt['description']) ?></p>
                    <?php endif; ?>
                    <div class="mb-2">
                        <span class="fw-bold" style="color:#7A1B22;font-size:1.05rem;"><?= rupiah($harga) ?></span>
                        <?php if ($is_eb && $tt['early_bird_price'] != $tt['price']): ?>
                            <span class="text-decoration-line-through text-muted small ms-1"><?= rupiah($tt['price']) ?></span>
                            <div class="small text-muted">s.d. <?= tanggal_id($tt['early_bird_deadline']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="small text-muted mb-3">
                        <?php if ($tersedia): ?>
                            <i class="bi bi-check-circle-fill text-success"></i> Tersisa <strong><?= $sisa ?></strong> tiket
                        <?php else: ?>
                            <i class="bi bi-x-circle-fill text-danger"></i> <strong>Tiket Habis</strong>
                        <?php endif; ?>
                    </div>

                    <?php if ($event['status'] !== 'published'): ?>
                        <button class="btn btn-secondary btn-sm w-100" disabled>Event Tidak Aktif</button>
                    <?php elseif ($tersedia): ?>
                        <?php if ($auth->isLoggedIn()): ?>
                            <form method="POST" action="<?= BASE_URL ?>/views/tickets/beli.php">
                                <?= csrf_input($auth) ?>
                                <input type="hidden" name="ticket_type_id" value="<?= $tt['id'] ?>">
                                <input type="hidden" name="event_title"    value="<?= e($event['title']) ?>">
                                <input type="hidden" name="redirect_back"  value="<?= e(BASE_URL . '/views/events/detail.php?id=' . $event_id) ?>">
                                <button type="submit" class="btn btn-sm w-100 fw-semibold" style="background:linear-gradient(135deg,#7A1B22,#9B222B);color:#fff;border:none;">
                                    <i class="bi bi-cart-plus"></i> Beli Tiket
                                </button>
                            </form>
                        <?php else: ?>
                            <a href="<?= BASE_URL ?>/views/auth/login.php"
                               class="btn btn-sm w-100 fw-semibold" style="background:linear-gradient(135deg,#7A1B22,#9B222B);color:#fff;">
                                <i class="bi bi-box-arrow-in-right"></i> Login untuk Beli
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php if ($auth->isLoggedIn()): ?>
                            <?php if ($is_wl): ?>
                                <button class="btn btn-outline-secondary btn-sm w-100" disabled>
                                    <i class="bi bi-clock-history"></i> Sudah di Waitlist
                                </button>
                            <?php else: ?>
                                <form method="POST" action="<?= BASE_URL ?>/views/tickets/waitlist.php">
                                    <?= csrf_input($auth) ?>
                                    <input type="hidden" name="ticket_type_id" value="<?= $tt['id'] ?>">
                                    <input type="hidden" name="redirect_back"  value="<?= e(BASE_URL . '/views/events/detail.php?id=' . $event_id) ?>">
                                    <button type="submit" class="btn btn-sm w-100 fw-semibold" style="border:1.5px solid #7A1B22;color:#7A1B22;background:transparent;">
                                        <i class="bi bi-hourglass-split"></i> Daftar Waitlist
                                    </button>
                                </form>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="<?= BASE_URL ?>/views/auth/login.php" class="btn btn-outline-secondary btn-sm w-100">Login untuk Waitlist</a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php if (!empty($related_events)): ?>
<div class="mt-5 pt-4" style="border-top:2px solid #f0e8de;">
    <h2 class="sec-heading">Event Serupa Untuk Kamu</h2>
    <div class="sec-line"></div>

    <div class="row g-4">
        <?php foreach ($related_events as $idx => $rev):
            $tt_m = new TicketTypeModel();
            $tts  = $tt_m->getByEvent($rev['id']);
            $hmin = null;
            foreach ($tts as $tt) { $h = $tt_m->getCurrentPrice($tt); if ($hmin===null||$h<$hmin) $hmin=$h; }
        ?>
        <div class="col-md-4" style="animation:fadeUp .4s ease <?= $idx*.1 ?>s both">
            <div class="ev-card">
                <?php if (!empty($rev['banner'])): ?>
                    <img class="ev-img" src="<?= BASE_URL ?>/public/uploads/banners/<?= e($rev['banner']) ?>" alt="<?= e($rev['title']) ?>">
                <?php else: ?>
                    <div class="ev-ph"><i class="bi bi-calendar-event text-white" style="font-size:2rem;opacity:.5;"></i></div>
                <?php endif; ?>
                <div class="ev-body">
                    <div class="d-flex gap-1 flex-wrap mb-2">
                        <?php if (!empty($rev['category'])): ?><span style="background:#fde8ea;color:#7A1B22;font-size:.66rem;font-weight:700;padding:2px 8px;border-radius:100px;"><?= e($rev['category']) ?></span><?php endif; ?>
                        <?php if (!empty($rev['is_online'])): ?><span style="background:#e0f2fe;color:#0369a1;font-size:.66rem;font-weight:700;padding:2px 8px;border-radius:100px;">Online</span><?php endif; ?>
                    </div>
                    <div class="ev-title"><?= e($rev['title']) ?></div>
                    <div class="ev-meta"><i class="bi bi-calendar3"></i><?= tanggal_id($rev['start_date']) ?></div>
                    <div class="ev-meta">
                        <?php if (!empty($rev['is_online'])): ?><i class="bi bi-wifi"></i>Online<?php else: ?><i class="bi bi-geo-alt-fill"></i><?= e($rev['location']) ?><?php endif; ?>
                    </div>
                    <div class="ev-foot">
                        <div><span class="ev-lbl">Mulai dari</span><span class="ev-price"><?= $hmin!==null?rupiah($hmin):'Gratis' ?></span></div>
                        <a href="<?= BASE_URL ?>/views/events/detail.php?id=<?= $rev['id'] ?>" class="btn-ev">Detail <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<style>
@media print {
    nav, footer, .btn, .breadcrumb { display:none !important; }
    body { background:#fff !important; }
    .card { box-shadow:none !important; border:1px solid #ccc !important; }
}
</style>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
