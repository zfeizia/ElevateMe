<?php
require_once __DIR__ . '/../../config/bootstrap.php';

$auth->requireRole('admin');

$user_model = new UserModel();
$aksi       = $_GET['aksi'] ?? 'list';
$errors     = [];
$old        = [];

if (isset($_POST['tambah_user'])) {
    $auth->verifyCsrfToken($_POST['csrf_token'] ?? '');

    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']             ?? '';
    $role     = $_POST['role']                 ?? 'attendee';
    $old      = compact('username', 'email', 'role');

    if (empty($username))     $errors[] = 'Username wajib diisi.';
    if (empty($email))        $errors[] = 'Email wajib diisi.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid.';
    if (strlen($password) < 8)  $errors[] = 'Password minimal 8 karakter.';
    if (!in_array($role, ['admin','organizer','attendee'])) $errors[] = 'Role tidak valid.';

    if (empty($errors) && $user_model->isUsernameTaken($username)) {
        $errors[] = 'Username sudah digunakan.';
    }
    if (empty($errors) && $user_model->isEmailTaken($email)) {
        $errors[] = 'Email sudah digunakan.';
    }

    if (empty($errors)) {
        $user_model->insert($username, $email, $password, $role);
        $auth->setFlash('success', "User '$username' berhasil ditambahkan.");
        header('Location: ' . BASE_URL . '/views/users/index.php');
        exit;
    }
    $aksi = 'tambah';
}

if (isset($_POST['edit_user'])) {
    $auth->verifyCsrfToken($_POST['csrf_token'] ?? '');

    $id       = (int) ($_POST['id']       ?? 0);
    $username = trim($_POST['username']   ?? '');
    $email    = trim($_POST['email']      ?? '');
    $role     = $_POST['role']            ?? 'attendee';
    $old      = compact('id','username','email','role');

    if (empty($username))   $errors[] = 'Username wajib diisi.';
    if (empty($email))      $errors[] = 'Email wajib diisi.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid.';
    if (!in_array($role, ['admin','organizer','attendee'])) $errors[] = 'Role tidak valid.';

    if (empty($errors) && $user_model->isUsernameTaken($username, $id)) {
        $errors[] = 'Username sudah digunakan oleh orang lain.';
    }
    if (empty($errors) && $user_model->isEmailTaken($email, $id)) {
        $errors[] = 'Email sudah digunakan oleh orang lain.';
    }

    // Ganti password jika diisi
    $new_password = $_POST['password'] ?? '';
    if (!empty($new_password) && strlen($new_password) < 8) {
        $errors[] = 'Password baru minimal 8 karakter.';
    }

    if (empty($errors)) {
        $user_model->update($id, $username, $email, $role);

        if (!empty($new_password)) {
            $user_model->updatePassword($id, $new_password);
        }

        $auth->setFlash('success', "User '$username' berhasil diperbarui.");
        header('Location: ' . BASE_URL . '/views/users/index.php');
        exit;
    }
    $aksi = 'edit';
}

if (isset($_POST['hapus_user'])) {
    $auth->verifyCsrfToken($_POST['csrf_token'] ?? '');

    $id = (int) ($_POST['id'] ?? 0);

    if ($id === $auth->getUserId()) {
        $auth->setFlash('error', 'Kamu tidak bisa menghapus akunmu sendiri.');
    } else {
        $user_model->delete($id);
        $auth->setFlash('success', 'User berhasil dihapus.');
    }

    header('Location: ' . BASE_URL . '/views/users/index.php');
    exit;
}

$edit_data = null;
if ($aksi === 'edit' && isset($_GET['id']) && empty($old)) {
    $edit_data = $user_model->getById((int) $_GET['id']);

    if (!$edit_data) {
        $auth->setFlash('error', 'User tidak ditemukan.');
        header('Location: ' . BASE_URL . '/views/users/index.php');
        exit;
    }

    $old = $edit_data;
}

$per_page    = 10;
$page        = max(1, (int) ($_GET['page'] ?? 1));
$offset      = ($page - 1) * $per_page;
$users       = $user_model->getAllWithStats($per_page, $offset);
$total       = $user_model->countAll();
$total_pages = ceil($total / $per_page);

$page_title = 'Manajemen User';
?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<?php show_flash($auth); ?>

<?php if (in_array($aksi, ['tambah', 'edit'])): ?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h5 class="fw-bold mb-0" style="color: var(--color-accent);">
        <i class="bi bi-person-<?= $aksi === 'tambah' ? 'plus' : 'gear' ?>"></i>
        <?= $aksi === 'tambah' ? 'Tambah User Baru' : 'Edit User' ?>
    </h5>
    <a href="<?= BASE_URL ?>/views/users/index.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-elevate-error mb-3 p-3 rounded small">
        <ul class="mb-0 ps-3">
            <?php foreach ($errors as $err): ?>
                <li><?= e($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card card-elevate p-4" style="max-width: 600px;">
    <form method="POST" action="">
        <?= csrf_input($auth) ?>
        <?php if ($aksi === 'edit'): ?>
            <input type="hidden" name="id" value="<?= e($old['id'] ?? '') ?>">
        <?php endif; ?>

        <div class="mb-3">
            <label class="form-label fw-semibold">Username <span class="text-danger">*</span></label>
            <input type="text" name="username" class="form-control"
                   value="<?= e($old['username'] ?? '') ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
            <input type="email" name="email" class="form-control"
                   value="<?= e($old['email'] ?? '') ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold">
                <?= $aksi === 'edit' ? 'Password Baru (Kosongkan jika tidak ingin ganti)' : 'Password *' ?>
            </label>
            <input type="password" name="password" class="form-control"
                   placeholder="Minimal 8 karakter"
                   <?= $aksi === 'tambah' ? 'required' : '' ?>>
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold">Role <span class="text-danger">*</span></label>
            <select name="role" class="form-select">
                <?php foreach (['attendee', 'organizer', 'admin'] as $r): ?>
                    <option value="<?= $r ?>" <?= ($old['role'] ?? 'attendee') === $r ? 'selected' : '' ?>>
                        <?= ucfirst($r) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button type="submit"
                    name="<?= $aksi === 'tambah' ? 'tambah_user' : 'edit_user' ?>"
                    class="btn btn-cta fw-semibold">
                <i class="bi bi-check-lg"></i>
                <?= $aksi === 'tambah' ? 'Tambah User' : 'Simpan Perubahan' ?>
            </button>
            <a href="<?= BASE_URL ?>/views/users/index.php" class="btn btn-outline-secondary">Batal</a>
        </div>
    </form>
</div>

<?php else: ?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h5 class="fw-bold mb-0" style="color: var(--color-accent);">
        <i class="bi bi-people"></i> Manajemen User
        <span class="badge bg-secondary ms-1" style="font-size: 12px;"><?= $total ?></span>
    </h5>
    <a href="?aksi=tambah" class="btn btn-cta fw-semibold">
        <i class="bi bi-person-plus"></i> Tambah User
    </a>
</div>

<div class="card card-elevate">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-elevate">
                <tr>
                    <th>#</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th class="text-center">Role</th>
                    <th class="text-center">Tiket</th>
                    <th>Bergabung</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">Tidak ada user.</td></tr>
                <?php else: ?>
                    <?php foreach ($users as $i => $u): ?>
                        <tr>
                            <td class="text-muted small"><?= $offset + $i + 1 ?></td>
                            <td class="fw-semibold">
                                <?= e($u['username']) ?>
                                <?php if ($u['id'] == $auth->getUserId()): ?>
                                    <span class="badge bg-light text-dark border ms-1" style="font-size:9px;">Kamu</span>
                                <?php endif; ?>
                            </td>
                            <td class="small text-muted"><?= e($u['email']) ?></td>
                            <td class="text-center">
                                <span class="badge badge-<?= e($u['role']) ?> px-2">
                                    <?= e(ucfirst($u['role'])) ?>
                                </span>
                            </td>
                            <td class="text-center"><?= $u['jumlah_tiket'] ?></td>
                            <td class="small text-muted"><?= tanggal_id($u['created_at']) ?></td>
                            <td>
                                <a href="?aksi=edit&id=<?= $u['id'] ?>"
                                   class="btn btn-outline-cta btn-sm" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php if ($u['id'] != $auth->getUserId()): ?>
                                    <form method="POST" action="" class="d-inline"
                                          onsubmit="return confirm('Hapus user \'<?= e(addslashes($u['username'])) ?>\'?')">
                                        <?= csrf_input($auth) ?>
                                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                        <button type="submit" name="hapus_user"
                                                class="btn btn-outline-danger btn-sm" title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($total_pages > 1): ?>
    <nav class="mt-3 d-flex justify-content-center">
        <ul class="pagination pagination-sm">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>

<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
