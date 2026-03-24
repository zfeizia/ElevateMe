<?php
require_once __DIR__ . '/config/bootstrap.php';

$event_model  = new EventModel();
$per_page     = 9;
$page         = max(1, (int) ($_GET['page'] ?? 1));
$offset       = ($page - 1) * $per_page;
$keyword      = trim($_GET['q'] ?? '');

if (!empty($keyword)) {
    $events       = $event_model->search($keyword, $per_page, $offset);
    $total_events = count($event_model->search($keyword, 9999, 0));
} else {
    $events       = $event_model->getPublished($per_page, $offset);
    $total_events = $event_model->countPublished();
}

$total_pages = ceil($total_events / $per_page);
$top_events  = $event_model->getTopEvents(3);
$page_title  = 'Beranda';

$kategori_list = [
    ['nama'=>'Bootcamp',   'icon'=>'bi-laptop',            'warna'=>'#7A1B22'],
    ['nama'=>'Seminar',    'icon'=>'bi-mic-fill',          'warna'=>'#c4830a'],
    ['nama'=>'Workshop',   'icon'=>'bi-tools',             'warna'=>'#1d4ed8'],
    ['nama'=>'Webinar',    'icon'=>'bi-camera-video-fill', 'warna'=>'#0f766e'],
    ['nama'=>'Pelatihan',  'icon'=>'bi-mortarboard-fill',  'warna'=>'#7c3aed'],
    ['nama'=>'Networking', 'icon'=>'bi-people-fill',       'warna'=>'#b45309'],
    ['nama'=>'Talkshow',   'icon'=>'bi-chat-quote-fill',   'warna'=>'#be185d'],
    ['nama'=>'Konferensi', 'icon'=>'bi-building',          'warna'=>'#374151'],
];
?>
<?php require_once __DIR__ . '/views/layouts/header.php'; ?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;0,900;1,700&display=swap');
@keyframes fadeUp    { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }
@keyframes quoteIn   { 0%{opacity:0;transform:translateY(6px)} 12%{opacity:1;transform:translateY(0)} 82%{opacity:1;transform:translateY(0)} 100%{opacity:0;transform:translateY(-6px)} }
@keyframes dotPulse  { 0%,100%{transform:scale(1)} 50%{transform:scale(1.5);opacity:.6} }
@keyframes shimmer   { from{transform:scaleX(0);transform-origin:left} to{transform:scaleX(1)} }
@keyframes rotateSlow{ from{transform:rotate(0deg)} to{transform:rotate(360deg)} }

.hero {
    position:relative;overflow:hidden;border-radius:20px;margin-bottom:64px;
    padding:80px 40px 68px;text-align:center;
    background:radial-gradient(ellipse 60% 55% at 100% 0%,rgba(196,57,74,.25) 0%,transparent 60%),
               radial-gradient(ellipse 50% 50% at 0% 100%,rgba(92,16,24,.4) 0%,transparent 60%),
               linear-gradient(145deg,#5C1018 0%,#7A1B22 45%,#9B222B 100%);
}
.hero::before { content:'';position:absolute;inset:0;background-image:radial-gradient(circle,rgba(255,255,255,.035) 1px,transparent 1px);background-size:30px 30px;pointer-events:none; }
.hero-ring    { position:absolute;border-radius:50%;border:1px solid rgba(255,255,255,.06);pointer-events:none; }
.hr1 { width:320px;height:320px;top:-90px;right:-90px;animation:rotateSlow 60s linear infinite; }
.hr2 { width:200px;height:200px;bottom:-80px;left:-60px;animation:rotateSlow 80s linear infinite reverse; }
.hero-inner   { position:relative;z-index:2; }
.hero-pill    { display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.18);border-radius:100px;padding:6px 18px;font-size:.72rem;font-weight:600;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,.85);margin-bottom:24px;animation:fadeUp .6s ease both; }
.hero-dot     { width:7px;height:7px;border-radius:50%;background:#fbbf24;animation:dotPulse 2s infinite; }
.hero-title   { font-family:'Playfair Display',serif;font-size:clamp(2rem,4.5vw,3.2rem);font-weight:900;color:#fff;line-height:1.15;margin-bottom:8px;animation:fadeUp .6s ease .1s both; }
.hero-title em{ font-style:italic;color:#fca5a5; }
.hero-divider { width:56px;height:2px;background:rgba(255,255,255,.3);border-radius:2px;margin:18px auto;animation:shimmer .7s ease .5s both; }
.hero-tagline { font-size:.72rem;letter-spacing:3px;text-transform:uppercase;color:rgba(255,255,255,.5);margin-bottom:30px;animation:fadeUp .6s ease .2s both; }
.quote-box    { min-height:54px;position:relative;margin-bottom:34px;animation:fadeUp .6s ease .3s both; }
.q-slide      { position:absolute;inset:0;opacity:0;display:flex;flex-direction:column;align-items:center;justify-content:center; }
.q-slide.on   { animation:quoteIn 5.5s ease forwards; }
.q-text       { font-size:1rem;font-style:italic;color:rgba(255,255,255,.8);max-width:540px;line-height:1.65; }
.q-author     { font-size:.7rem;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,.4);margin-top:6px; }
.hero-search  { display:flex;max-width:520px;margin:0 auto;background:rgba(255,255,255,.12);border:1.5px solid rgba(255,255,255,.2);border-radius:14px;overflow:hidden;backdrop-filter:blur(8px);transition:border-color .2s;animation:fadeUp .6s ease .5s both; }
.hero-search:focus-within { border-color:rgba(255,255,255,.5);background:rgba(255,255,255,.18); }
.hero-search input  { flex:1;background:none;border:none;outline:none;padding:14px 20px;font-size:.9rem;color:#fff;font-family:inherit; }
.hero-search input::placeholder { color:rgba(255,255,255,.5); }
.hero-search button { background:rgba(255,255,255,.18);border:none;border-left:1px solid rgba(255,255,255,.15);padding:0 22px;color:#fff;font-size:.88rem;font-weight:600;cursor:pointer;transition:background .2s;white-space:nowrap;font-family:inherit; }
.hero-search button:hover { background:rgba(255,255,255,.3); }
.hero-stats   { display:flex;justify-content:center;gap:48px;margin-top:40px;padding-top:28px;border-top:1px solid rgba(255,255,255,.1);animation:fadeUp .6s ease .7s both;flex-wrap:wrap; }
.hero-stat-n  { font-family:'Playfair Display',serif;font-size:1.6rem;font-weight:700;color:#fff;line-height:1; }
.hero-stat-l  { font-size:.68rem;color:rgba(255,255,255,.45);letter-spacing:2px;text-transform:uppercase;margin-top:4px; }

.sec-heading  { font-family:'Playfair Display',serif;font-size:1.5rem;font-weight:700;color:#2B2523;margin-bottom:4px; }
.sec-line     { width:36px;height:3px;background:#7A1B22;border-radius:2px;margin-bottom:28px; }

/* Top Events */
.top-card     { position:relative;border-radius:16px;overflow:hidden;height:280px;display:flex;flex-direction:column;justify-content:flex-end;text-decoration:none;transition:transform .25s,box-shadow .25s; }
.top-card:hover { transform:translateY(-6px);box-shadow:0 20px 50px rgba(122,27,34,.22); }
.top-rank     { position:absolute;top:16px;left:16px;z-index:3;font-family:'Playfair Display',serif;font-size:3.5rem;font-weight:900;color:rgba(255,255,255,.18);line-height:1;pointer-events:none; }
.top-img      { position:absolute;inset:0;width:100%;height:100%;object-fit:cover; }
.top-grad     { position:absolute;inset:0;background:linear-gradient(to top,rgba(13,2,3,.85) 0%,rgba(13,2,3,.2) 60%,transparent 100%); }
.top-body     { position:relative;z-index:2;padding:20px; }
.top-badge    { display:inline-block;font-size:.65rem;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;padding:3px 10px;border-radius:100px;background:rgba(155,34,43,.9);color:#fff;margin-bottom:8px; }
.top-title    { font-family:'Playfair Display',serif;font-size:1.05rem;font-weight:700;color:#fff;line-height:1.3;margin-bottom:4px; }
.top-meta     { font-size:.75rem;color:rgba(255,255,255,.65); }
.top-ph       { position:absolute;inset:0;background:linear-gradient(145deg,#7A1B22,#3d0b0e);display:flex;align-items:center;justify-content:center; }

/* Kategori */
.cat-grid { display:grid;grid-template-columns:repeat(auto-fill,minmax(110px,1fr));gap:14px; }
.cat-card { display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;padding:18px 12px;border-radius:14px;border:1.5px solid #ece5dc;background:#fff;text-decoration:none;transition:transform .2s,box-shadow .2s,border-color .2s; }
.cat-card:hover { transform:translateY(-4px);box-shadow:0 8px 24px rgba(122,27,34,.1);border-color:#c4a882; }
.cat-icon { width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;color:#fff; }
.cat-name { font-size:.78rem;font-weight:600;color:#2B2523;text-align:center;line-height:1.3; }

/* Event Cards */
.ev-card  { border:1px solid #ece5dc;border-radius:14px;background:#fff;overflow:hidden;transition:transform .22s,box-shadow .22s;height:100%;display:flex;flex-direction:column; }
.ev-card:hover { transform:translateY(-5px);box-shadow:0 16px 40px rgba(122,27,34,.12); }
.ev-img   { height:176px;object-fit:cover;width:100%; }
.ev-ph    { height:176px;display:flex;align-items:center;justify-content:center;background:linear-gradient(145deg,#7A1B22,#c4394a); }
.ev-body  { padding:18px;flex:1;display:flex;flex-direction:column; }
.ev-title { font-weight:700;font-size:.95rem;color:#2B2523;margin-bottom:6px;line-height:1.4; }
.ev-meta  { display:flex;align-items:center;gap:5px;font-size:.78rem;color:#8a7370;margin-bottom:4px; }
.ev-meta i{ color:#7A1B22;font-size:.76rem; }
.ev-desc  { font-size:.78rem;color:#a09490;line-height:1.6;flex:1;margin:8px 0;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden; }
.ev-foot  { display:flex;align-items:center;justify-content:space-between;padding-top:12px;border-top:1px solid #f3ede6;margin-top:auto; }
.ev-price { font-weight:700;font-size:1rem;color:#7A1B22; }
.ev-lbl   { font-size:.7rem;color:#a09490;display:block; }
.btn-ev   { background:linear-gradient(135deg,#7A1B22,#9B222B);color:#fff;border:none;border-radius:8px;padding:7px 14px;font-size:.8rem;font-weight:600;text-decoration:none;transition:opacity .18s,transform .13s; }
.btn-ev:hover { opacity:.88;transform:translateY(-1px);color:#fff; }
</style>

<?php show_flash($auth); ?>

<!-- HERO -->
<div class="hero">
    <div class="hero-ring hr1"></div>
    <div class="hero-ring hr2"></div>
    <div class="hero-inner">
        <div class="hero-pill"><span class="hero-dot"></span> Self-Development Platform</div>
        <h1 class="hero-title">Tingkatkan Dirimu<br>Bersama <em>ElevateMe</em></h1>
        <div class="hero-divider"></div>
        <p class="hero-tagline">Your Growth, Elevated</p>
        <div class="quote-box" id="qBox">
            <div class="q-slide"><p class="q-text">"The only way to do great work is to love what you do."</p><span class="q-author">— Steve Jobs</span></div>
            <div class="q-slide"><p class="q-text">"An investment in knowledge pays the best interest."</p><span class="q-author">— Benjamin Franklin</span></div>
            <div class="q-slide"><p class="q-text">"Growth is never by mere chance; it is the result of forces working together."</p><span class="q-author">— J.C. Penney</span></div>
            <div class="q-slide"><p class="q-text">"The mind is not a vessel to be filled, but a fire to be kindled."</p><span class="q-author">— Plutarch</span></div>
            <div class="q-slide"><p class="q-text">"Commit to lifelong learning. The most valuable asset you'll ever have is your mind."</p><span class="q-author">— Albert Einstein</span></div>
        </div>
        <div class="hero-search">
            <form method="GET" action="" style="display:contents">
                <input type="text" name="q" placeholder="Cari event, workshop, seminar..." value="<?= e($keyword) ?>">
                <button type="submit"><i class="bi bi-search me-1"></i>Cari</button>
            </form>
        </div>
        <?php if (!empty($keyword)): ?>
            <div class="mt-2"><a href="<?= BASE_URL ?>/index.php" style="color:rgba(255,255,255,.55);font-size:.8rem;text-decoration:none;"><i class="bi bi-x-circle"></i> Reset</a></div>
        <?php endif; ?>
        <div class="hero-stats">
            <div class="text-center"><div class="hero-stat-n"><?= $event_model->countPublished() ?>+</div><div class="hero-stat-l">Event Aktif</div></div>
            <div class="text-center"><div class="hero-stat-n">10+</div><div class="hero-stat-l">Speaker Expert</div></div>
            <div class="text-center"><div class="hero-stat-n">500+</div><div class="hero-stat-l">Peserta</div></div>
        </div>
    </div>
</div>

<!-- TOP EVENTS -->
<?php if (!empty($top_events) && empty($keyword)): ?>
<div class="mb-5">
    <div class="d-flex align-items-end justify-content-between mb-1">
        <h2 class="sec-heading mb-0">Top Events</h2>
        <a href="<?= BASE_URL ?>/views/events/explore.php" style="font-size:.82rem;color:#7A1B22;text-decoration:none;font-weight:600;">Lihat Semua <i class="bi bi-arrow-right"></i></a>
    </div>
    <div class="sec-line"></div>
    <div class="row g-3">
        <?php foreach ($top_events as $rank => $ev):
            $tt_m = new TicketTypeModel();
            $tts  = $tt_m->getByEvent($ev['id']);
            $hmin = null;
            foreach ($tts as $tt) { $h = $tt_m->getCurrentPrice($tt); if ($hmin===null||$h<$hmin) $hmin=$h; }
        ?>
        <div class="col-md-4">
            <a href="<?= BASE_URL ?>/views/events/detail.php?id=<?= $ev['id'] ?>" class="top-card d-block">
                <span class="top-rank"><?= $rank+1 ?></span>
                <?php if (!empty($ev['banner'])): ?>
                    <img class="top-img" src="<?= BASE_URL ?>/public/uploads/banners/<?= e($ev['banner']) ?>" alt="">
                <?php else: ?>
                    <div class="top-ph"><i class="bi bi-calendar-event text-white" style="font-size:2.5rem;opacity:.4;"></i></div>
                <?php endif; ?>
                <div class="top-grad"></div>
                <div class="top-body">
                    <span class="top-badge"><?= e($ev['category'] ?? 'Event') ?></span>
                    <div class="top-title"><?= e($ev['title']) ?></div>
                    <div class="top-meta">
                        <i class="bi bi-calendar3 me-1"></i><?= tanggal_id($ev['start_date']) ?>
                        &nbsp;·&nbsp;
                        <?php if (!empty($ev['is_online'])): ?><i class="bi bi-wifi me-1"></i>Online<?php else: ?><i class="bi bi-geo-alt me-1"></i><?= e($ev['location']) ?><?php endif; ?>
                        &nbsp;·&nbsp;<span style="color:#fca5a5;font-weight:600;"><?= $hmin!==null?rupiah($hmin):'Gratis' ?></span>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- KATEGORI -->
<?php if (empty($keyword)): ?>
<div class="mb-5">
    <h2 class="sec-heading">Kategori Event</h2>
    <div class="sec-line"></div>
    <div class="cat-grid">
        <?php foreach ($kategori_list as $kat): ?>
        <a href="<?= BASE_URL ?>/views/events/explore.php?category=<?= urlencode($kat['nama']) ?>" class="cat-card">
            <div class="cat-icon" style="background:<?= $kat['warna'] ?>;"><i class="bi <?= $kat['icon'] ?>"></i></div>
            <span class="cat-name"><?= e($kat['nama']) ?></span>
        </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- SEMUA EVENT -->
<?php if (!empty($keyword)): ?>
    <p class="text-muted small mb-4">Menampilkan <strong><?= $total_events ?></strong> hasil untuk "<strong><?= e($keyword) ?></strong>"</p>
<?php endif; ?>
<div class="mb-3">
    <h2 class="sec-heading"><?= !empty($keyword) ? 'Hasil Pencarian' : 'Semua Event' ?></h2>
    <div class="sec-line"></div>
</div>

<?php if (empty($events)): ?>
    <div class="text-center py-5">
        <i class="bi bi-calendar-x" style="font-size:3rem;color:#d4c8c4;"></i>
        <p class="mt-3" style="color:#a09490;"><?= !empty($keyword) ? 'Tidak ada event yang cocok.' : 'Belum ada event.' ?></p>
    </div>
<?php else: ?>
    <div class="row g-4">
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
        <div class="col-md-6 col-lg-4" style="animation:fadeUp .4s ease <?= $idx*.05 ?>s both">
            <div class="ev-card">
                <?php if (!empty($event['banner'])): ?>
                    <img class="ev-img" src="<?= BASE_URL ?>/public/uploads/banners/<?= e($event['banner']) ?>" alt="<?= e($event['title']) ?>">
                <?php else: ?>
                    <div class="ev-ph"><i class="bi bi-calendar-event text-white" style="font-size:2.2rem;opacity:.5;"></i></div>
                <?php endif; ?>
                <div class="ev-body">
                    <div class="d-flex gap-1 flex-wrap mb-2">
                        <?php if ($is_eb): ?><span style="background:#fff4e0;color:#b45309;font-size:.68rem;font-weight:700;padding:2px 9px;border-radius:100px;">⚡ EARLY BIRD</span><?php endif; ?>
                        <?php if (!empty($event['category'])): ?><span style="background:#fde8ea;color:#7A1B22;font-size:.68rem;font-weight:700;padding:2px 9px;border-radius:100px;"><?= e($event['category']) ?></span><?php endif; ?>
                    </div>
                    <div class="ev-title"><?= e($event['title']) ?></div>
                    <div class="ev-meta"><i class="bi bi-person-badge"></i><?= e($event['organizer_name']) ?></div>
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

    <?php if ($total_pages > 1): ?>
    <nav class="mt-5 d-flex justify-content-center">
        <ul class="pagination">
            <li class="page-item <?= $page<=1?'disabled':'' ?>"><a class="page-link" href="?page=<?= $page-1 ?>&q=<?= urlencode($keyword) ?>"><i class="bi bi-chevron-left"></i></a></li>
            <?php for ($i=1;$i<=$total_pages;$i++): ?><li class="page-item <?= $i===$page?'active':'' ?>"><a class="page-link" href="?page=<?= $i ?>&q=<?= urlencode($keyword) ?>"><?= $i ?></a></li><?php endfor; ?>
            <li class="page-item <?= $page>=$total_pages?'disabled':'' ?>"><a class="page-link" href="?page=<?= $page+1 ?>&q=<?= urlencode($keyword) ?>"><i class="bi bi-chevron-right"></i></a></li>
        </ul>
    </nav>
    <?php endif; ?>
<?php endif; ?>

<script>
(function(){
    var s=document.querySelectorAll('#qBox .q-slide'),c=0;
    function show(i){s.forEach(x=>x.classList.remove('on'));s[i].classList.add('on');}
    show(0);
    setInterval(function(){c=(c+1)%s.length;show(c);},5500);
})();
</script>
<?php require_once __DIR__ . '/views/layouts/footer.php'; ?>
