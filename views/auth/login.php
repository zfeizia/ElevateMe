<?php
require_once __DIR__ . '/../../config/bootstrap.php';

if ($auth->isLoggedIn()) {
    $role = $auth->getRole();
    if ($role === 'admin')     { header('Location: ' . BASE_URL . '/views/dashboard/admin.php');     exit; }
    if ($role === 'organizer') { header('Location: ' . BASE_URL . '/views/dashboard/organizer.php'); exit; }
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$login_error = '';
$reg_errors  = [];
$reg_old     = [];

// Baca tab dari URL (?tab=register) — untuk tombol "Daftar Gratis" di navbar
$active_tab = (($_GET['tab'] ?? '') === 'register') ? 'register' : 'login';

if (isset($_POST['login'])) {
    $auth->verifyCsrfToken($_POST['csrf_token'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password']      ?? '';
    if (empty($username) || empty($password)) {
        $login_error = 'Username dan password wajib diisi.';
    } elseif (!$auth->login($username, $password)) {
        $login_error = 'Username atau password salah.';
    } else {
        $redirect = $_SESSION['redirect_after_login'] ?? null;
        unset($_SESSION['redirect_after_login']);
        $role = $auth->getRole();
        if ($redirect)             { header('Location: ' . $redirect); exit; }
        if ($role === 'admin')     { header('Location: ' . BASE_URL . '/views/dashboard/admin.php');     exit; }
        if ($role === 'organizer') { header('Location: ' . BASE_URL . '/views/dashboard/organizer.php'); exit; }
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

if (isset($_POST['register'])) {
    $auth->verifyCsrfToken($_POST['csrf_token'] ?? '');
    $active_tab = 'register';
    $username   = trim($_POST['reg_username']         ?? '');
    $email      = trim($_POST['reg_email']             ?? '');
    $password   = $_POST['reg_password']               ?? '';
    $confirm    = $_POST['reg_password_confirm']       ?? '';
    $reg_role   = in_array($_POST['reg_role'] ?? '', ['attendee','organizer'])
                    ? $_POST['reg_role']
                    : 'attendee';
    $reg_old    = ['username' => $username, 'email' => $email, 'role' => $reg_role];
    if (empty($username))                            $reg_errors[] = 'Username wajib diisi.';
    if (strlen($username) < 3)                       $reg_errors[] = 'Username minimal 3 karakter.';
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) $reg_errors[] = 'Hanya huruf, angka, underscore.';
    if (empty($email))                               $reg_errors[] = 'Email wajib diisi.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))  $reg_errors[] = 'Format email tidak valid.';
    if (strlen($password) < 8)                       $reg_errors[] = 'Password minimal 8 karakter.';
    if ($password !== $confirm)                      $reg_errors[] = 'Konfirmasi password tidak cocok.';
    if (empty($reg_errors)) {
        if ($auth->register($username, $email, $password, $reg_role)) {
            $auth->setFlash('success', 'Akun berhasil dibuat! Silakan login.');
            header('Location: ' . BASE_URL . '/views/auth/login.php');
            exit;
        } else {
            $reg_errors[] = 'Username atau email sudah terdaftar.';
        }
    }
}

$flash = $auth->getFlash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Masuk / Daftar — ElevateMe</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;0,900;1,700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html, body { width: 100%; height: 100%; overflow: hidden; }
body {
    font-family: 'DM Sans', sans-serif;
    background:
        radial-gradient(ellipse 70% 60% at 15% 50%,  rgba(138,30,39,.85) 0%, transparent 65%),
        radial-gradient(ellipse 50% 70% at 85% 20%,  rgba(196,57,74,.18) 0%, transparent 55%),
        radial-gradient(ellipse 60% 50% at 75% 80%,  rgba(92,16,24,.45)  0%, transparent 60%),
        linear-gradient(140deg, #3d0b0e 0%, #1a0406 40%, #0d0203 100%);
    display: flex; align-items: center; justify-content: center;
}
body::before {
    content: ''; position: fixed; inset: 0; pointer-events: none;
    background-image: radial-gradient(circle, rgba(255,255,255,.03) 1px, transparent 1px);
    background-size: 30px 30px;
}

/* ── Animasi ── */
@keyframes fromLeft { from { opacity:0; transform:translateX(-20px); } to { opacity:1; transform:translateX(0); } }
@keyframes riseUp   { from { opacity:0; transform:translateY(28px) scale(.96); } to { opacity:1; transform:translateY(0) scale(1); } }
@keyframes glowOrb  { 0%,100% { opacity:.15; transform:scale(1); } 50% { opacity:.28; transform:scale(1.12); } }
@keyframes shimmer  { from { transform:scaleX(0); transform-origin:left; } to { transform:scaleX(1); transform-origin:left; } }
@keyframes stepIn   { 0% { opacity:0; transform:scale(.5) translateY(8px); } 70% { transform:scale(1.1) translateY(-2px); } 100% { opacity:1; transform:scale(1) translateY(0); } }
@keyframes lineGrow { from { transform:scaleY(0); transform-origin:top; opacity:0; } to { transform:scaleY(1); transform-origin:top; opacity:1; } }
@keyframes panelIn  { from { opacity:0; transform:translateY(6px); } to { opacity:1; transform:translateY(0); } }
@keyframes rotateSlow { from { transform:rotate(0deg); } to { transform:rotate(360deg); } }

/* ── Dekorasi cincin (fixed, global) ── */
.dr { position:fixed; border-radius:50%; pointer-events:none; border:1px solid rgba(255,255,255,.05); }
.dr1 { width:340px; height:340px; top:-80px; left:30%; animation:rotateSlow 70s linear infinite; }
.dr2 { width:200px; height:200px; bottom:-50px; left:10%; }
.dr3 { width:500px; height:500px; bottom:-200px; right:-80px; animation:rotateSlow 90s linear infinite reverse; }

/* ── Shell ── */
.shell {
    position:relative; z-index:1; width:100%; max-width:1020px; height:100vh;
    display:flex; align-items:center;
    padding:0 clamp(24px,5vw,80px); gap:clamp(32px,6vw,100px);
}

/* ── Panel Kiri ── */
.left { flex:1; display:flex; flex-direction:column; justify-content:space-between; height:clamp(420px,80vh,620px); }

.brand { display:flex; align-items:center; gap:13px; text-decoration:none; animation:fromLeft .55s ease both; }
.brand-icon {
    width:46px; height:46px; border-radius:13px; flex-shrink:0;
    background:linear-gradient(135deg,#9B222B,#c4394a);
    display:flex; align-items:center; justify-content:center; font-size:20px; color:#fff;
}
.brand-name { font-family:'Playfair Display',serif; font-size:clamp(1.3rem,1.8vw,1.7rem); font-weight:700; color:#fff; line-height:1; }
.brand-sub  { font-size:.58rem; letter-spacing:2.5px; text-transform:uppercase; color:rgba(255,255,255,.35); margin-top:3px; }

.left-mid { flex:1; display:flex; flex-direction:column; justify-content:center; padding:clamp(24px,3.5vh,44px) 0; }

.headline { font-family:'Playfair Display',serif; font-size:clamp(1.8rem,2.8vw,2.8rem); font-weight:900; color:#fff; line-height:1.18; margin-bottom:6px; animation:fromLeft .55s ease .08s both; }
.headline em { font-style:italic; color:#fca5a5; }

.divider { width:40px; height:2px; background:rgba(255,255,255,.22); border-radius:2px; margin:clamp(12px,1.8vh,20px) 0; animation:shimmer .65s ease .5s both; }

.tagline { font-size:.63rem; letter-spacing:3.5px; text-transform:uppercase; color:rgba(255,255,255,.36); font-weight:600; margin-bottom:clamp(20px,3vh,34px); animation:fromLeft .55s ease .16s both; }

/* Steps */
.steps { display:flex; flex-direction:column; animation:fromLeft .55s ease .28s both; }
.step-row { display:flex; align-items:flex-start; gap:13px; }
.step-num {
    width:26px; height:26px; border-radius:50%; flex-shrink:0; margin-top:1px;
    background:linear-gradient(135deg,#9B222B,#c4394a);
    display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:700; color:#fff;
}
.step-row:nth-child(1) .step-num { animation:stepIn .45s ease .62s both; }
.step-row:nth-child(3) .step-num { animation:stepIn .45s ease .78s both; }
.step-row:nth-child(5) .step-num { animation:stepIn .45s ease .94s both; }
.step-title { font-size:clamp(.82rem,1.05vw,.95rem); font-weight:600; color:rgba(255,255,255,.9); line-height:1.3; }
.step-desc  { font-size:clamp(.68rem,.85vw,.76rem); color:rgba(255,255,255,.38); margin-top:2px; line-height:1.45; }
.step-line  { width:1px; height:clamp(14px,2vh,22px); margin:5px 0 5px 12px; background:linear-gradient(to bottom,rgba(255,255,255,.18),rgba(255,255,255,.05)); }
.step-line:nth-of-type(2) { animation:lineGrow .3s ease .7s  both; }
.step-line:nth-of-type(4) { animation:lineGrow .3s ease .86s both; }

.left-desc { font-size:clamp(.74rem,.9vw,.82rem); color:rgba(255,255,255,.32); line-height:1.75; animation:fromLeft .55s ease .42s both; }

/* ── Panel Kanan ── */
.right { flex:0 0 auto; width:clamp(340px,38vw,420px); display:flex; flex-direction:column; align-items:center; }

.glow-orb {
    position:absolute; width:280px; height:280px; border-radius:50%; pointer-events:none;
    background:radial-gradient(circle,rgba(155,34,43,.28) 0%,transparent 70%);
    top:50%; left:50%; transform:translate(-50%,-50%);
    animation:glowOrb 4s ease-in-out infinite;
}

.glass {
    position:relative; z-index:2; width:100%;
    background:rgba(253,251,247,.95);
    backdrop-filter:blur(24px); -webkit-backdrop-filter:blur(24px);
    border-radius:22px; overflow:hidden;
    box-shadow:0 24px 60px rgba(0,0,0,.5), 0 0 0 1px rgba(255,255,255,.2), inset 0 1px 0 rgba(255,255,255,.9);
    animation:riseUp .65s cubic-bezier(.22,.68,0,1.15) .18s both;
}

/* Pill tab */
.tab-pill { display:flex; margin:clamp(16px,2.2vh,24px) clamp(18px,2vw,26px) clamp(12px,1.6vh,18px); background:rgba(43,37,35,.075); border-radius:11px; padding:4px; gap:3px; }
.tp { flex:1; text-align:center; padding:clamp(7px,1vh,10px) 0; font-family:'DM Sans',sans-serif; font-size:clamp(.82rem,1vw,.92rem); font-weight:600; border-radius:8px; color:#8a7370; cursor:pointer; border:none; background:transparent; transition:color .2s,background .22s,box-shadow .22s; }
.tp.on { background:#fff; color:#7A1B22; box-shadow:0 2px 8px rgba(43,37,35,.14); }

/* Form panels — height konsisten lewat visibility trick */
.fp { display:block; padding:0 clamp(18px,2vw,26px) clamp(18px,2.5vh,26px); visibility:hidden; height:0; overflow:hidden; }
.fp.on { visibility:visible; height:auto; overflow:visible; animation:panelIn .2s ease both; }

.f-heading { font-family:'Playfair Display',serif; font-size:clamp(1.2rem,1.6vw,1.45rem); font-weight:700; color:#2B2523; margin-bottom:3px; }
.f-sub { font-size:clamp(.74rem,.9vw,.82rem); color:#8a7370; margin-bottom:clamp(14px,2vh,20px); }

/* Alert */
.a-err { background:#fff2f2; border:1px solid #fca5a5; border-left:3px solid #9B222B; border-radius:8px; padding:9px 12px; font-size:clamp(.72rem,.85vw,.8rem); color:#991b1b; margin-bottom:13px; line-height:1.5; }
.a-err ul { padding-left:14px; margin:3px 0 0; }
.a-ok  { background:#ecfdf5; border:1px solid #6ee7b7; border-left:3px solid #10b981; border-radius:8px; padding:9px 12px; font-size:clamp(.72rem,.85vw,.8rem); color:#065f46; margin-bottom:13px; }

/* Field */
.field { margin-bottom:clamp(10px,1.4vh,15px); }
.field label { display:block; font-size:clamp(.74rem,.9vw,.82rem); font-weight:600; color:#2B2523; margin-bottom:5px; }
.field label .req { color:#9B222B; margin-left:1px; }
.iw { position:relative; }
.iw input { width:100%; padding:clamp(9px,1.2vh,12px) 13px; border:1.5px solid #ece5dc; border-radius:9px; font-family:'DM Sans',sans-serif; font-size:clamp(.8rem,.95vw,.88rem); color:#2B2523; background:#fff; outline:none; transition:border-color .18s,box-shadow .18s; }
.iw input::placeholder { color:#c4b4b0; }
.iw input:focus { border-color:#7A1B22; box-shadow:0 0 0 3px rgba(122,27,34,.09); }
.pw-btn { position:absolute; right:12px; top:50%; transform:translateY(-50%); background:none; border:none; color:#a09490; cursor:pointer; font-size:14px; padding:0; line-height:1; transition:color .15s; }
.pw-btn:hover { color:#7A1B22; }

/* Button */
.btn-go { width:100%; padding:clamp(10px,1.4vh,13px); background:linear-gradient(135deg,#7A1B22,#9B222B); color:#fff; border:none; border-radius:10px; font-family:'DM Sans',sans-serif; font-size:clamp(.84rem,1vw,.94rem); font-weight:600; cursor:pointer; box-shadow:0 4px 18px rgba(122,27,34,.32); transition:opacity .18s,transform .13s,box-shadow .18s; margin-top:clamp(4px,.6vh,8px); letter-spacing:.3px; }
.btn-go:hover { opacity:.88; transform:translateY(-1px); box-shadow:0 7px 24px rgba(122,27,34,.42); }
.btn-go:active { transform:translateY(0); }

/* ── Role picker ── */
.role-picker { display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:clamp(10px,1.4vh,15px); }
.role-option { position:relative; }
.role-option input[type=radio] { position:absolute; opacity:0; width:0; height:0; }
.role-label {
    display:flex; flex-direction:column; align-items:center; gap:6px;
    padding:12px 8px; border-radius:10px;
    border:1.5px solid #ece5dc; background:#fff;
    cursor:pointer; transition:border-color .18s, background .18s, box-shadow .18s;
    text-align:center;
}
.role-label:hover { border-color:#c4a882; }
.role-option input:checked + .role-label {
    border-color:#7A1B22;
    background:#fde8ea;
    box-shadow:0 0 0 3px rgba(122,27,34,.09);
}
.role-icon {
    width:36px; height:36px; border-radius:9px;
    display:flex; align-items:center; justify-content:center;
    font-size:1rem; color:#fff;
    transition:background .18s;
}
.role-option input:checked + .role-label .role-icon { background:#7A1B22 !important; }
.role-title { font-size:.78rem; font-weight:600; color:#2B2523; line-height:1.3; }
.role-desc  { font-size:.68rem; color:#8a7370; line-height:1.4; }

/* Back link */
.back-link { position:relative; z-index:2; margin-top:18px; font-size:clamp(.72rem,.88vw,.8rem); color:rgba(255,255,255,.45); text-decoration:none; display:inline-flex; align-items:center; gap:5px; transition:color .2s; animation:panelIn .5s ease .6s both; }
.back-link:hover { color:rgba(255,255,255,.75); }

/* Responsive */
@media (max-width:720px) {
    html,body { overflow:auto; height:auto; }
    body { align-items:flex-start; padding:0; }
    .shell { flex-direction:column; height:auto; padding:32px 20px 48px; gap:36px; }
    .left { height:auto; }
    .right { width:100%; }
}
</style>
</head>
<body>

<div class="dr dr1"></div>
<div class="dr dr2"></div>
<div class="dr dr3"></div>

<div class="shell">

    <!-- KIRI -->
    <div class="left">

        <a href="<?= BASE_URL ?>/index.php" class="brand">
            <div class="brand-icon"><i class="bi bi-lightning-fill"></i></div>
            <div>
                <div class="brand-name">ElevateMe</div>
                <div class="brand-sub">Your Growth, Elevated</div>
            </div>
        </a>

        <div class="left-mid">
            <h2 class="headline">
                Temukan Event<br>yang <em>Mengubah</em><br>Hidupmu.
            </h2>
            <div class="divider"></div>
            <p class="tagline">Your Growth, Elevated</p>

            <div class="steps">
                <div class="step-row">
                    <div class="step-num">1</div>
                    <div>
                        <div class="step-title">Daftar akun gratis</div>
                        <div class="step-desc">Butuh kurang dari 30 detik</div>
                    </div>
                </div>
                <div class="step-line"></div>
                <div class="step-row">
                    <div class="step-num">2</div>
                    <div>
                        <div class="step-title">Temukan event pilihanmu</div>
                        <div class="step-desc">Bootcamp, workshop & seminar terbaik</div>
                    </div>
                </div>
                <div class="step-line"></div>
                <div class="step-row">
                    <div class="step-num">3</div>
                    <div>
                        <div class="step-title">Hadir & berkembang</div>
                        <div class="step-desc">Bawa pulang ilmu & koneksi baru</div>
                    </div>
                </div>
            </div>
        </div>

        <p class="left-desc">
            Platform terpercaya untuk bootcamp, workshop,<br>
            dan seminar pengembangan diri terbaik di Indonesia.
        </p>

    </div>

    <!-- KANAN -->
    <div class="right" style="position:relative;">

        <div class="glow-orb"></div>

        <div class="glass">

            <div class="tab-pill">
                <button class="tp <?= $active_tab==='login'    ? 'on':'' ?>" id="tp-login"    onclick="sw('login')">Masuk</button>
                <button class="tp <?= $active_tab==='register' ? 'on':'' ?>" id="tp-register" onclick="sw('register')">Daftar</button>
            </div>

            <!-- TAB MASUK -->
            <div class="fp <?= $active_tab==='login' ? 'on':'' ?>" id="fp-login">
                <div class="f-heading">Selamat Datang 👋</div>
                <div class="f-sub">Masuk ke akun ElevateMe kamu</div>

                <?php if (!empty($flash['success'])): ?>
                    <div class="a-ok"><i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($flash['success'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <?php if (!empty($login_error)): ?>
                    <div class="a-err"><i class="bi bi-exclamation-circle-fill"></i> <?= htmlspecialchars($login_error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= $auth->generateCsrfToken() ?>">
                    <div class="field">
                        <label for="username">Username <span class="req">*</span></label>
                        <div class="iw">
                            <input type="text" id="username" name="username" placeholder="Masukkan username"
                                   value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                   autocomplete="username" autofocus required>
                        </div>
                    </div>
                    <div class="field">
                        <label for="password">Kata Sandi <span class="req">*</span></label>
                        <div class="iw">
                            <input type="password" id="password" name="password" placeholder="Masukkan kata sandi" autocomplete="current-password" required>
                            <button type="button" class="pw-btn" onclick="tpw('password',this)"><i class="bi bi-eye-slash"></i></button>
                        </div>
                    </div>
                    <button type="submit" name="login" class="btn-go">Masuk</button>
                </form>

                <!-- Spacer supaya tinggi card sama dengan tab Daftar -->
                <div style="height:clamp(160px,21vh,210px);"></div>
            </div>

            <!-- TAB DAFTAR -->
            <div class="fp <?= $active_tab==='register' ? 'on':'' ?>" id="fp-register">
                <div class="f-heading">Buat Akun Baru ✨</div>
                <div class="f-sub">Daftar gratis dan mulai jelajahi event</div>

                <?php if (!empty($reg_errors)): ?>
                    <div class="a-err">
                        <i class="bi bi-exclamation-circle-fill"></i> Perbaiki kesalahan berikut:
                        <ul><?php foreach ($reg_errors as $e): ?><li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach; ?></ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= $auth->generateCsrfToken() ?>">

                    <!-- ── Role Picker ── -->
                    <div class="field">
                        <label style="font-size:clamp(.72rem,.88vw,.8rem);font-weight:600;color:#2B2523;display:block;margin-bottom:7px;">
                            Saya ingin <span class="req">*</span>
                        </label>
                        <div class="role-picker">
                            <!-- Attendee -->
                            <div class="role-option">
                                <input type="radio" id="role_attendee" name="reg_role"
                                       value="attendee"
                                       <?= ($reg_old['role'] ?? 'attendee') === 'attendee' ? 'checked' : '' ?>>
                                <label class="role-label" for="role_attendee">
                                    <div class="role-icon" style="background:#e0e7ff;">
                                        <i class="bi bi-ticket-perforated-fill" style="color:#4f46e5;"></i>
                                    </div>
                                    <div class="role-title">Cari &amp; Beli Tiket</div>
                                    <div class="role-desc">Temukan &amp; ikuti event seru</div>
                                </label>
                            </div>
                            <!-- Organizer -->
                            <div class="role-option">
                                <input type="radio" id="role_organizer" name="reg_role"
                                       value="organizer"
                                       <?= ($reg_old['role'] ?? '') === 'organizer' ? 'checked' : '' ?>>
                                <label class="role-label" for="role_organizer">
                                    <div class="role-icon" style="background:#fde8ea;">
                                        <i class="bi bi-megaphone-fill" style="color:#9B222B;"></i>
                                    </div>
                                    <div class="role-title">Buat Event</div>
                                    <div class="role-desc">Jadi Event Creator</div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="field">
                        <label for="reg_username">Username <span class="req">*</span></label>
                        <div class="iw"><input type="text" id="reg_username" name="reg_username" placeholder="contoh: budi_santoso" value="<?= htmlspecialchars($reg_old['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>" autocomplete="username" required></div>
                    </div>
                    <div class="field">
                        <label for="reg_email">Email <span class="req">*</span></label>
                        <div class="iw"><input type="email" id="reg_email" name="reg_email" placeholder="contoh: budi@email.com" value="<?= htmlspecialchars($reg_old['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" autocomplete="email" required></div>
                    </div>
                    <div class="field">
                        <label for="reg_password">Kata Sandi <span class="req">*</span></label>
                        <div class="iw">
                            <input type="password" id="reg_password" name="reg_password" placeholder="Minimal 8 karakter" autocomplete="new-password" required>
                            <button type="button" class="pw-btn" onclick="tpw('reg_password',this)"><i class="bi bi-eye-slash"></i></button>
                        </div>
                    </div>
                    <div class="field">
                        <label for="reg_confirm">Konfirmasi Kata Sandi <span class="req">*</span></label>
                        <div class="iw">
                            <input type="password" id="reg_confirm" name="reg_password_confirm" placeholder="Ulangi kata sandi" autocomplete="new-password" required>
                            <button type="button" class="pw-btn" onclick="tpw('reg_confirm',this)"><i class="bi bi-eye-slash"></i></button>
                        </div>
                    </div>
                    <button type="submit" name="register" class="btn-go">Buat Akun</button>
                </form>
            </div>

        </div><!-- /glass -->

        <a href="<?= BASE_URL ?>/index.php" class="back-link">
            <i class="bi bi-arrow-left" style="font-size:11px;"></i> Kembali ke Katalog Event
        </a>

    </div>

</div><!-- /shell -->

<script>
function sw(tab) {
    ['login','register'].forEach(function(t) {
        document.getElementById('tp-'+t).classList.toggle('on', t===tab);
        document.getElementById('fp-'+t).classList.toggle('on', t===tab);
    });
}
function tpw(id, btn) {
    var inp = document.getElementById(id), icon = btn.querySelector('i');
    inp.type = inp.type==='password' ? 'text' : 'password';
    icon.className = inp.type==='password' ? 'bi bi-eye-slash' : 'bi bi-eye';
}

// Auto-select role organizer jika datang dari tombol "Mulai Jadi Event Creator"
(function() {
    var params = new URLSearchParams(window.location.search);
    if (params.get('role') === 'organizer') {
        var el = document.getElementById('role_organizer');
        if (el) el.checked = true;
    }
})();
</script>
</body>
</html>