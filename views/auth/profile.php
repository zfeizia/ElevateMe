<?php

require_once __DIR__ . '/../../config/bootstrap.php';

$auth->requireLogin();

$user_model = new UserModel();
$user_id    = $auth->getUserId();
$errors     = [];
$success    = '';

$user = $user_model->getById($user_id);

if (isset($_POST['update_profil'])) {
    $auth->verifyCsrfToken($_POST['csrf_token'] ?? '');

    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email']    ?? '');

    // Validasi
    if (empty($username))           $errors[] = 'Username wajib diisi.';
    if (strlen($username) < 3)      $errors[] = 'Username minimal 3 karakter.';
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = 'Username hanya boleh huruf, angka, dan underscore.';
    }
    if (empty($email))              $errors[] = 'Email wajib diisi.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email tidak valid.';
    }

    // Cek duplikat (kecualikan diri sendiri)
    if (empty($errors) && $user_model->isUsernameTaken($username, $user_id)) {
        $errors[] = 'Username sudah digunakan oleh orang lain.';
    }
    if (empty($errors) && $user_model->isEmailTaken($email, $user_id)) {
        $errors[] = 'Email sudah digunakan oleh orang lain.';
    }

    if (empty($errors)) {
        // Update data (role tidak boleh diubah sendiri, hanya admin)
        $user_model->update($user_id, $username, $email, $auth->getRole());

        // Update data di session agar navbar langsung berubah
        $_SESSION['username'] = $username;
        $_SESSION['email']    = $email;

        $auth->setFlash('success', 'Profil berhasil diperbarui.');
        header('Location: ' . BASE_URL . '/views/auth/profile.php');
        exit;
    }

    // Jika ada error, isi ulang $user dengan data yang baru disubmit
    $user['username'] = $username;
    $user['email']    = $email;
}

if (isset($_POST['ganti_password'])) {
    $auth->verifyCsrfToken($_POST['csrf_token'] ?? '');

    $password_lama   = $_POST['password_lama']    ?? '';
    $password_baru   = $_POST['password_baru']    ?? '';
    $password_konfirm = $_POST['password_konfirm'] ?? '';

    // Verifikasi password lama
    if (!password_verify($password_lama, $user['password'])) {
        $errors[] = 'Password lama tidak sesuai.';
    }
    if (strlen($password_baru) < 8) {
        $errors[] = 'Password baru minimal 8 karakter.';
    }
    if ($password_baru !== $password_konfirm) {
        $errors[] = 'Konfirmasi password baru tidak cocok.';
    }

    if (empty($errors)) {
        $user_model->updatePassword($user_id, $password_baru);
        $auth->setFlash('success', 'Password berhasil diubah. Silakan login ulang untuk keamanan.');
        // Logout setelah ganti password
        $auth->logout();
    }
}

$page_title = 'Profil Saya';
?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<?php show_flash($auth); ?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">

        <h5 class="fw-bold mb-4" style="color: var(--color-accent);">
            <i class="bi bi-person-circle"></i> Profil Saya
        </h5>

        <!-- ── Error Messages ── -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-elevate-error mb-3 p-3 rounded small">
                <ul class="mb-0 ps-3">
                    <?php foreach ($errors as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- ── Info Akun ── -->
        <div class="card card-elevate p-4 mb-4">
            <div class="d-flex align-items-center gap-3 mb-4">
                <!-- Avatar inisial -->
                <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold"
                     style="width:64px;height:64px;background:var(--color-accent);color:#fff;font-size:1.6rem;flex-shrink:0;">
                    <?= strtoupper(substr($user['username'], 0, 1)) ?>
                </div>
                <div>
                    <div class="fw-bold fs-5"><?= e($user['username']) ?></div>
                    <span class="badge badge-<?= e($user['role']) ?> px-2">
                        <?= e(ucfirst($user['role'])) ?>
                    </span>
                    <div class="small text-muted mt-1">
                        Bergabung <?= tanggal_id($user['created_at']) ?>
                    </div>
                </div>
            </div>

            <!-- Form Edit Profil -->
            <h6 class="fw-semibold mb-3 border-bottom pb-2">Edit Profil</h6>
            <form method="POST" action="">
                <?= csrf_input($auth) ?>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Username</label>
                    <input type="text" name="username" class="form-control"
                           value="<?= e($user['username']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Email</label>
                    <input type="email" name="email" class="form-control"
                           value="<?= e($user['email']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Role</label>
                    <input type="text" class="form-control bg-light"
                           value="<?= e(ucfirst($user['role'])) ?>" readonly>
                    <div class="form-text">Role hanya bisa diubah oleh Admin.</div>
                </div>

                <button type="submit" name="update_profil" class="btn btn-cta fw-semibold">
                    <i class="bi bi-check-lg"></i> Simpan Perubahan
                </button>
            </form>
        </div>

        <!-- ── Ganti Password ── -->
        <div class="card card-elevate p-4">
            <h6 class="fw-semibold mb-3 border-bottom pb-2">
                <i class="bi bi-shield-lock"></i> Ganti Password
            </h6>
            <form method="POST" action="">
                <?= csrf_input($auth) ?>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Password Lama</label>
                    <input type="password" name="password_lama" class="form-control"
                           placeholder="Masukkan password saat ini" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Password Baru</label>
                    <input type="password" name="password_baru" class="form-control"
                           placeholder="Minimal 8 karakter" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Konfirmasi Password Baru</label>
                    <input type="password" name="password_konfirm" class="form-control"
                           placeholder="Ulangi password baru" required>
                </div>

                <div class="alert alert-elevate-error p-2 small mb-3" style="background:#fff8e1;border-color:#f59e0b;color:#7c5a00;">
                    <i class="bi bi-exclamation-triangle"></i>
                    Setelah ganti password, kamu akan otomatis logout dan perlu login ulang.
                </div>

                <button type="submit" name="ganti_password" class="btn btn-outline-danger fw-semibold">
                    <i class="bi bi-key"></i> Ganti Password
                </button>
            </form>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
