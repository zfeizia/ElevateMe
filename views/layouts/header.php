<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? e($page_title) . ' — ElevateMe' : 'ElevateMe' ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --color-bg:       #FDFBF7;
            --color-text:     #2B2523;
            --color-accent:   #7A1B22;
            --color-cta:      #9B222B;
            --color-cta-hover:#7A1B22;
        }
        body { background-color:var(--color-bg); color:var(--color-text); font-family:'Segoe UI',system-ui,sans-serif; }
        html { max-width: 100vw; overflow-x: hidden;}
        body { max-width: 100vw; overflow-x: hidden;}
   
        .navbar-elevate { background-color:var(--color-accent) !important; }
        .navbar-elevate .navbar-brand,
        .navbar-elevate .nav-link { color:#fff !important; }
        .navbar-elevate .nav-link:hover { color:#fde8ea !important; }
        .navbar-elevate .nav-link.active { color:#fde8ea !important; font-weight:600; border-bottom:2px solid #fde8ea; }

        .btn-cta { background-color:var(--color-cta); color:#fff; border:none; }
        .btn-cta:hover { background-color:var(--color-cta-hover); color:#fff; }
        .btn-outline-cta { border:1px solid var(--color-cta); color:var(--color-cta); background:transparent; }
        .btn-outline-cta:hover { background-color:var(--color-cta); color:#fff; }

        .card-elevate {
            border:1px solid #e8e0d8; border-radius:10px; background:#fff;
            box-shadow:0 2px 8px rgba(43,37,35,0.07);
            transition:transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
        }
        .card-elevate:hover { transform:translateY(-10px); box-shadow:0 15px 25px rgba(122,27,34,0.2); cursor:pointer; }

        .badge-admin     { background-color:#7A1B22; color:#fff; }
        .badge-organizer { background-color:#c4830a; color:#fff; }
        .badge-attendee  { background-color:#2563eb; color:#fff; }

        .qr-table { border-collapse:collapse; margin:0 auto; }
        .qr-table td { width:14px; height:14px; padding:0; }
        .qr-dark     { background-color:#2B2523; }
        .qr-light    { background-color:#FDFBF7; }
        .qr-border-h { background-color:#2B2523; height:6px; }
        .qr-border-v { background-color:#2B2523; width:6px; }

        .page-link { color:var(--color-accent); }
        .page-item.active .page-link { background-color:var(--color-accent); border-color:var(--color-accent); }

        .table-elevate thead { background-color:var(--color-accent); color:#fff; }

        .alert-elevate-success { background:#d1fae5; border-left:4px solid #10b981; color:#065f46; }
        .alert-elevate-error   { background:#fee2e2; border-left:4px solid #ef4444; color:#991b1b; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-elevate sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold fs-5 d-flex align-items-center gap-2" href="<?= BASE_URL ?>/index.php">
            <i class="bi bi-lightning-fill"></i> ElevateMe
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
            <span class="navbar-toggler-icon" style="filter:invert(1)"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>/views/events/explore.php">
                        <i class="bi bi-compass"></i> Jelajah Event
                    </a>
                </li>

                <?php if ($auth->isLoggedIn()): ?>

                    <?php if ($auth->getRole() === 'admin'): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                <i class="bi bi-shield-fill"></i> Admin
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/views/users/index.php">
                                    <i class="bi bi-people"></i> Manajemen User</a></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/views/events/manage.php">
                                    <i class="bi bi-calendar3"></i> Semua Event</a></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/views/dashboard/admin.php">
                                    <i class="bi bi-speedometer2"></i> Dashboard Admin</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>

                    <?php if (in_array($auth->getRole(), ['admin', 'organizer'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                <i class="bi bi-gear"></i> Kelola
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/views/dashboard/organizer.php">
                                    <i class="bi bi-speedometer2"></i> Dashboard Organizer</a></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/views/events/manage.php">
                                    <i class="bi bi-calendar-plus"></i> Event Saya</a></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/views/checkin/index.php">
                                    <i class="bi bi-qr-code-scan"></i> Check-in Peserta</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/views/dashboard/organizer.php">
                                    <i class="bi bi-bar-chart-line"></i> Laporan Kehadiran</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>

                    <?php if ($auth->getRole() === 'attendee'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= BASE_URL ?>/views/dashboard/attendee.php">
                                <i class="bi bi-ticket-perforated"></i> Tiket Saya
                            </a>
                        </li>
                    <?php endif; ?>

                <?php endif; ?>
            </ul>

            <ul class="navbar-nav">
                <?php if ($auth->isLoggedIn()): ?>

                    <?php
                    $waitlist_model = new WaitlistModel();
                    $notif_count    = $waitlist_model->getEligibleCount($auth->getUserId());
                    ?>
                    <li class="nav-item me-2 d-flex align-items-center">
                        <a href="<?= BASE_URL ?>/views/dashboard/attendee.php?tab=waitlist" class="text-white position-relative">
                            <i class="bi bi-bell-fill fs-5"></i>
                            <?php if ($notif_count > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark">
                                    <?= $notif_count ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i>
                            <?= e($_SESSION['username']) ?>
                            <span class="badge ms-1 badge-<?= e($auth->getRole()) ?> rounded-pill" style="font-size:10px">
                                <?= e($auth->getRole()) ?>
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>/views/auth/profile.php">
                                <i class="bi bi-person"></i> Profil Saya</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger"
                                   href="<?= BASE_URL ?>/views/auth/logout.php"
                                   onclick="return confirm('Yakin mau logout?')">
                                    <i class="bi bi-box-arrow-right"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </li>

                <?php else: ?>

                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>/views/auth/login.php">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-sm btn-light ms-2 fw-semibold"
                           href="<?= BASE_URL ?>/views/auth/login.php?tab=register">
                            Daftar Gratis
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-sm ms-2 fw-semibold"
                           href="<?= BASE_URL ?>/views/auth/login.php?tab=register&role=organizer"
                           style="background:transparent;border:1.5px solid rgba(255,255,255,.5);color:#fff;border-radius:8px;">
                            <i class="bi bi-megaphone-fill me-1"></i> Mulai Jadi Event Creator
                        </a>
                    </li>

                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="container my-4">
