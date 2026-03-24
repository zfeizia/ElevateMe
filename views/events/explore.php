<?php

require_once __DIR__ . '/../../config/bootstrap.php';

$event_model = new EventModel();

$per_page = 12;
$page     = max(1, (int) ($_GET['page'] ?? 1));
$offset   = ($page - 1) * $per_page;

$params = [
    'q'         => trim($_GET['q']         ?? ''),
    'is_online' => $_GET['is_online']      ?? '',
    'location'  => trim($_GET['location']  ?? ''),
    'category'  => trim($_GET['category']  ?? ''),
    'gratis'    => $_GET['gratis']         ?? '',
];

$events      = $event_model->filter($params, $per_page, $offset);
$total       = $event_model->countFilter($params);
$total_pages = ceil($total / $per_page);
$locations   = $event_model->getLocations();

$kategori_list = ['Bootcamp','Seminar','Workshop','Webinar','Pelatihan','Networking','Talkshow','Konferensi'];

$page_title  = 'Jelajah Event';

$qstring = http_build_query(array_filter($params, fn($v) => $v !== ''));
?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&display=swap');
@keyframes fadeUp { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:translateY(0)} }

.explore-wrap { display:flex;gap:28px;align-items:flex-start; }

/* ── Sidebar Filter ── */
.filter-sidebar {
    flex:0 0 260px;
    background:#fff;border:1px solid #ece5dc;border-radius:16px;
    padding:24px;position:sticky;top:80px;
}
.filter-title { font-family:'Playfair Display',serif;font-size:1.1rem;font-weight:700;color:#2B2523;margin-bottom:20px;display:flex;align-items:center;gap:8px; }
.filter-section { margin-bottom:20px; }
.filter-label   { font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;color:#8a7370;margin-bottom:10px; }
.filter-divider { border:none;border-top:1px solid #f0e8de;margin:18px 0; }

/* Toggle Online/Offline */
.toggle-group { display:flex;border:1.5px solid #ece5dc;border-radius:9px;overflow:hidden; }
.toggle-opt   { flex:1;text-align:center;padding:8px 6px;font-size:.8rem;font-weight:600;color:#8a7370;cursor:pointer;transition:background .2s,color .2s;border:none;background:transparent;font-family:inherit; }
.toggle-opt.on{ background:#7A1B22;color:#fff; }

/* Dropdown & Input */
.filter-select { width:100%;padding:9px 12px;border:1.5px solid #ece5dc;border-radius:9px;font-family:inherit;font-size:.82rem;color:#2B2523;background:#fff;outline:none;transition:border-color .18s; }
.filter-select:focus { border-color:#7A1B22; }
.filter-input  { width:100%;padding:9px 12px;border:1.5px solid #ece5dc;border-radius:9px;font-family:inherit;font-size:.82rem;color:#2B2523;background:#fff;outline:none;transition:border-color .18s; }
.filter-input:focus { border-color:#7A1B22; }
.filter-input::placeholder { color:#c4b4b0; }

/* Kategori chips */
.cat-chips { display:flex;flex-wrap:wrap;gap:7px; }
.cat-chip  { padding:5px 12px;border-radius:100px;border:1.5px solid #ece5dc;font-size:.75rem;font-weight:600;color:#8a7370;cursor:pointer;transition:all .18s;text-decoration:none;background:#fff;display:inline-block; }
.cat-chip.on  { background:#7A1B22;color:#fff;border-color:#7A1B22; }
.cat-chip:hover:not(.on) { border-color:#c4a882;color:#2B2523; }

/* Harga toggle */
.price-opts { display:flex;flex-direction:column;gap:8px; }
.price-opt  { display:flex;align-items:center;gap:8px;cursor:pointer;font-size:.82rem;color:#2B2523; }
.price-radio{ width:16px;height:16px;accent-color:#7A1B22;cursor:pointer; }

/* Tombol filter */
.btn-filter { width:100%;padding:10px;background:linear-gradient(135deg,#7A1B22,#9B222B);color:#fff;border:none;border-radius:9px;font-size:.85rem;font-weight:600;cursor:pointer;transition:opacity .18s;font-family:inherit; }
.btn-filter:hover { opacity:.88; }
.btn-reset  { width:100%;padding:8px;background:transparent;color:#8a7370;border:1.5px solid #ece5dc;border-radius:9px;font-size:.8rem;cursor:pointer;transition:border-color .18s;font-family:inherit;margin-top:8px;text-decoration:none;display:block;text-align:center; }
.btn-reset:hover { border-color:#c4a882;color:#2B2523; }

/* ── Kanan: Grid ── */
.explore-right { flex:1;min-width:0; }
.explore-header{ display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px; }
.explore-heading{ font-family:'Playfair Display',serif;font-size:1.35rem;font-weight:700;color:#2B2523; }
.explore-count { font-size:.82rem;color:#8a7370; }

/* Active filters chips */
.active-filters { display:flex;flex-wrap:wrap;gap:6px;margin-bottom:16px; }
.af-chip { display:inline-flex;align-items:center;gap:5px;padding:4px 10px;background:#fde8ea;color:#7A1B22;border-radius:100px;font-size:.74rem;font-weight:600; }
.af-chip a { color:#7A1B22;text-decoration:none;font-weight:700;margin-left:2px; }

/* Event cards */
.ev-card  { border:1px solid #ece5dc;border-radius:14px;background:#fff;overflow:hidden;transition:transform .22s,box-shadow .22s;height:100%;display:flex;flex-direction:column; }
.ev-card:hover { transform:translateY(-5px);box-shadow:0 16px 40px rgba(122,27,34,.12); }
.ev-img   { height:160px;object-fit:cover;width:100%; }
.ev-ph    { height:160px;display:flex;align-items:center;justify-content:center;background:linear-gradient(145deg,#7A1B22,#c4394a); }
.ev-body  { padding:16px;flex:1;display:flex;flex-direction:column; }
.ev-title { font-weight:700;font-size:.9rem;color:#2B2523;margin-bottom:5px;line-height:1.4; }
.ev-meta  { display:flex;align-items:center;gap:5px;font-size:.75rem;color:#8a7370;margin-bottom:3px; }
.ev-meta i{ color:#7A1B22;font-size:.73rem; }
.ev-desc  { font-size:.75rem;color:#a09490;line-height:1.6;flex:1;margin:7px 0;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden; }
.ev-foot  { display:flex;align-items:center;justify-content:space-between;padding-top:10px;border-top:1px solid #f3ede6;margin-top:auto; }
.ev-price { font-weight:700;font-size:.95rem;color:#7A1B22; }
.ev-lbl   { font-size:.68rem;color:#a09490;display:block; }
.btn-ev   { background:linear-gradient(135deg,#7A1B22,#9B222B);color:#fff;border:none;border-radius:7px;padding:6px 12px;font-size:.76rem;font-weight:600;text-decoration:none;transition:opacity .18s; }
.btn-ev:hover { opacity:.88;color:#fff; }

/* Empty state */
.empty-state { text-align:center;padding:60px 20px; }

/* Responsive */
@media (max-width:768px) {
    .explore-wrap { flex-direction:column; }
    .filter-sidebar { flex:none;position:static;width:100%; }
}
</style>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb small">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php" style="color:#7A1B22;">Beranda</a></li>
        <li class="breadcrumb-item active">Jelajah Event</li>
    </ol>
</nav>

<?php show_flash($auth); ?>

<div class="explore-wrap">

    <aside class="filter-sidebar">
        <div class="filter-title">
            <i class="bi bi-sliders" style="color:#7A1B22;"></i>
            Filter
        </div>

        <form method="GET" action="" id="filterForm">

            <!-- Pencarian -->
            <div class="filter-section">
                <div class="filter-label">Cari Event</div>
                <input type="text" name="q" class="filter-input"
                       placeholder="Nama event..."
                       value="<?= e($params['q']) ?>">
            </div>

            <hr class="filter-divider">

            <!-- Online / Offline -->
            <div class="filter-section">
                <div class="filter-label">Tipe Event</div>
                <div class="toggle-group">
                    <button type="button" class="toggle-opt <?= $params['is_online']==='' ? 'on' : '' ?>"
                            onclick="setOnline('')">Semua</button>
                    <button type="button" class="toggle-opt <?= $params['is_online']==='0' ? 'on' : '' ?>"
                            onclick="setOnline('0')">Offline</button>
                    <button type="button" class="toggle-opt <?= $params['is_online']==='1' ? 'on' : '' ?>"
                            onclick="setOnline('1')">Online</button>
                </div>
                <input type="hidden" name="is_online" id="is_online_val"
                       value="<?= e($params['is_online']) ?>">
            </div>

            <hr class="filter-divider">

            <!-- Lokasi -->
            <div class="filter-section">
                <div class="filter-label">Lokasi</div>
                <select name="location" class="filter-select">
                    <option value="">Semua Kota</option>
                    <?php foreach ($locations as $loc): ?>
                        <option value="<?= e($loc) ?>" <?= $params['location']===$loc ? 'selected' : '' ?>>
                            <?= e($loc) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <hr class="filter-divider">

            <!-- Kategori -->
            <div class="filter-section">
                <div class="filter-label">Kategori</div>
                <div class="cat-chips">
                    <?php foreach ($kategori_list as $kat): ?>
                        <a href="javascript:void(0)"
                           class="cat-chip <?= $params['category']===$kat ? 'on' : '' ?>"
                           onclick="setCategory('<?= $kat === $params['category'] ? '' : $kat ?>')">
                            <?= e($kat) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" name="category" id="cat_val" value="<?= e($params['category']) ?>">
            </div>

            <hr class="filter-divider">

            <!-- Harga -->
            <div class="filter-section">
                <div class="filter-label">Harga</div>
                <div class="price-opts">
                    <label class="price-opt">
                        <input type="radio" class="price-radio" name="gratis" value=""
                               <?= $params['gratis']==='' ? 'checked' : '' ?>>
                        Semua
                    </label>
                    <label class="price-opt">
                        <input type="radio" class="price-radio" name="gratis" value="0"
                               <?= $params['gratis']==='0' ? 'checked' : '' ?>>
                        Berbayar
                    </label>
                    <label class="price-opt">
                        <input type="radio" class="price-radio" name="gratis" value="1"
                               <?= $params['gratis']==='1' ? 'checked' : '' ?>>
                        Gratis
                    </label>
                </div>
            </div>

            <button type="submit" class="btn-filter">
                <i class="bi bi-search me-1"></i> Terapkan Filter
            </button>
            <a href="<?= BASE_URL ?>/views/events/explore.php" class="btn-reset">Reset Filter</a>

        </form>
    </aside>
    
    <div class="explore-right">

        <!-- Header hasil -->
        <div class="explore-header">
            <div>
                <div class="explore-heading">Jelajah Event</div>
                <div class="explore-count">
                    Menampilkan <strong><?= $total ?></strong> event
                </div>
            </div>
        </div>

        <!-- Active filter chips -->
        <?php
        $active_chips = [];
        if (!empty($params['q']))        $active_chips[] = ['label' => 'Kata kunci: ' . $params['q'],           'rm' => 'q'];
        if ($params['is_online']==='1')  $active_chips[] = ['label' => 'Online',                                'rm' => 'is_online'];
        if ($params['is_online']==='0')  $active_chips[] = ['label' => 'Offline',                               'rm' => 'is_online'];
        if (!empty($params['location'])) $active_chips[] = ['label' => 'Kota: ' . $params['location'],          'rm' => 'location'];
        if (!empty($params['category'])) $active_chips[] = ['label' => $params['category'],                     'rm' => 'category'];
        if ($params['gratis']==='1')     $active_chips[] = ['label' => 'Gratis',                                'rm' => 'gratis'];
        if ($params['gratis']==='0')     $active_chips[] = ['label' => 'Berbayar',                              'rm' => 'gratis'];
        ?>
        <?php if (!empty($active_chips)): ?>
        <div class="active-filters">
            <?php foreach ($active_chips as $chip):
                $rm_params = $params;
                $rm_params[$chip['rm']] = '';
                $rm_qs = http_build_query(array_filter($rm_params, fn($v) => $v !== ''));
            ?>
            <span class="af-chip">
                <?= e($chip['label']) ?>
                <a href="<?= BASE_URL ?>/views/events/explore.php<?= $rm_qs ? '?' . $rm_qs : '' ?>">×</a>
            </span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Grid event -->
        <?php if (empty($events)): ?>
            <div class="empty-state">
                <i class="bi bi-calendar-x" style="font-size:3.5rem;color:#d4c8c4;"></i>
                <p class="mt-3 mb-3" style="color:#a09490;">Tidak ada event yang cocok dengan filter kamu.</p>
                <a href="<?= BASE_URL ?>/views/events/explore.php"
                   style="color:#7A1B22;font-weight:600;font-size:.85rem;">
                    Reset semua filter
                </a>
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($events as $idx => $event):
                    $tt_m = new TicketTypeModel();
                    $tts  = $tt_m->getByEvent($event['id']);
                    $hmin = null; $is_eb = false;
                    foreach ($tts as $tt) {
                        $h = $tt_m->getCurrentPrice($tt);
                        if ($hmin===null||$h<$hmin) $hmin=$h;
                        if ($tt_m->isEarlyBirdActive($tt)) $is_eb=true;
                    }
                ?>
                <div class="col-sm-6 col-xl-4" style="animation:fadeUp .35s ease <?= $idx*.04 ?>s both">
                    <div class="ev-card">
                        <?php if (!empty($event['banner'])): ?>
                            <img class="ev-img" src="<?= BASE_URL ?>/public/uploads/banners/<?= e($event['banner']) ?>" alt="<?= e($event['title']) ?>">
                        <?php else: ?>
                            <div class="ev-ph"><i class="bi bi-calendar-event text-white" style="font-size:2rem;opacity:.5;"></i></div>
                        <?php endif; ?>
                        <div class="ev-body">
                            <div class="d-flex gap-1 flex-wrap mb-2">
                                <?php if ($is_eb): ?><span style="background:#fff4e0;color:#b45309;font-size:.66rem;font-weight:700;padding:2px 8px;border-radius:100px;">⚡ Early Bird</span><?php endif; ?>
                                <?php if (!empty($event['category'])): ?><span style="background:#fde8ea;color:#7A1B22;font-size:.66rem;font-weight:700;padding:2px 8px;border-radius:100px;"><?= e($event['category']) ?></span><?php endif; ?>
                                <?php if (!empty($event['is_online'])): ?><span style="background:#e0f2fe;color:#0369a1;font-size:.66rem;font-weight:700;padding:2px 8px;border-radius:100px;">Online</span><?php endif; ?>
                            </div>
                            <div class="ev-title"><?= e($event['title']) ?></div>
                            <div class="ev-meta"><i class="bi bi-calendar3"></i><?= tanggal_id($event['start_date']) ?></div>
                            <div class="ev-meta">
                                <?php if (!empty($event['is_online'])): ?><i class="bi bi-wifi"></i>Online<?php else: ?><i class="bi bi-geo-alt-fill"></i><?= e($event['location']) ?><?php endif; ?>
                            </div>
                            <p class="ev-desc"><?= e($event['description']) ?></p>
                            <div class="ev-foot">
                                <div><span class="ev-lbl">Mulai dari</span><span class="ev-price"><?= $hmin!==null?rupiah($hmin):'Gratis' ?></span></div>
                                <a href="<?= BASE_URL ?>/views/events/detail.php?id=<?= $event['id'] ?>" class="btn-ev">Detail <i class="bi bi-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <nav class="mt-4 d-flex justify-content-center">
                <ul class="pagination pagination-sm">
                    <li class="page-item <?= $page<=1?'disabled':'' ?>">
                        <a class="page-link" href="?page=<?= $page-1 ?>&<?= $qstring ?>"><i class="bi bi-chevron-left"></i></a>
                    </li>
                    <?php for ($i=1;$i<=$total_pages;$i++): ?>
                    <li class="page-item <?= $i===$page?'active':'' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&<?= $qstring ?>"><?= $i ?></a>
                    </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page>=$total_pages?'disabled':'' ?>">
                        <a class="page-link" href="?page=<?= $page+1 ?>&<?= $qstring ?>"><i class="bi bi-chevron-right"></i></a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
        <?php endif; ?>

    </div><!-- /explore-right -->

</div><!-- /explore-wrap -->

<script>
function setOnline(val) {
    document.getElementById('is_online_val').value = val;
    document.querySelectorAll('.toggle-opt').forEach(function(btn, i) {
        btn.classList.toggle('on', ['','0','1'][i] === val);
    });
}
function setCategory(val) {
    document.getElementById('cat_val').value = val;
    document.querySelectorAll('.cat-chip').forEach(function(chip) {
        chip.classList.toggle('on', chip.textContent.trim() === val);
    });
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
