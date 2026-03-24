<?php

require_once __DIR__ . '/../../config/bootstrap.php';

$auth->requireRole(['admin', 'organizer']);

$event_model  = new EventModel();
$organizer_id = $auth->getUserId();
$aksi         = $_GET['aksi'] ?? 'list';
$errors       = [];
$old          = [];

if (isset($_POST['tambah_event'])) {
    $auth->verifyCsrfToken($_POST['csrf_token'] ?? '');

    $title       = trim($_POST['title']       ?? '');
    $description = trim($_POST['description'] ?? '');
    $location    = trim($_POST['location']    ?? '');
    $start_date  = trim($_POST['start_date']  ?? '');
    $end_date    = trim($_POST['end_date']    ?? '');
    $status      = $_POST['status']           ?? 'draft';
    $old         = compact('title','description','location','start_date','end_date','status');

    // Validasi
    if (empty($title))       $errors[] = 'Judul event wajib diisi.';
    if (empty($location))    $errors[] = 'Lokasi wajib diisi.';
    if (empty($start_date))  $errors[] = 'Tanggal mulai wajib diisi.';
    if (empty($end_date))    $errors[] = 'Tanggal selesai wajib diisi.';
    if (!empty($start_date) && !empty($end_date) && $end_date < $start_date) {
        $errors[] = 'Tanggal selesai tidak boleh sebelum tanggal mulai.';
    }
    if (!in_array($status, ['draft','published','cancelled','completed'])) {
        $errors[] = 'Status tidak valid.';
    }

    // Upload banner
    $banner_filename = null;
    if (!empty($_FILES['banner']['name'])) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
        $max_size      = 2 * 1024 * 1024; // 2MB

        if (!in_array($_FILES['banner']['type'], $allowed_types)) {
            $errors[] = 'Banner harus berupa file JPG, PNG, atau WebP.';
        } elseif ($_FILES['banner']['size'] > $max_size) {
            $errors[] = 'Ukuran banner maksimal 2MB.';
        } else {
            $ext             = pathinfo($_FILES['banner']['name'], PATHINFO_EXTENSION);
            $banner_filename = uniqid('banner_') . '.' . $ext;
            $upload_path     = BASE_PATH . '/public/uploads/banners/' . $banner_filename;

            if (!move_uploaded_file($_FILES['banner']['tmp_name'], $upload_path)) {
                $errors[] = 'Gagal mengupload banner. Pastikan folder /public/uploads/banners/ bisa ditulis.';
                $banner_filename = null;
            }
        }
    }

    if (empty($errors)) {
        $event_model->insert($organizer_id, $title, $description, $location, $start_date, $end_date, $banner_filename, $status);
        $auth->setFlash('success', 'Event berhasil dibuat!');
        header('Location: ' . BASE_URL . '/views/events/manage.php');
        exit;
    }

    $aksi = 'tambah'; // Tetap di form jika ada error
}

if (isset($_POST['edit_event'])) {
    $auth->verifyCsrfToken($_POST['csrf_token'] ?? '');

    $id          = (int) ($_POST['id'] ?? 0);
    $title       = trim($_POST['title']       ?? '');
    $description = trim($_POST['description'] ?? '');
    $location    = trim($_POST['location']    ?? '');
    $start_date  = trim($_POST['start_date']  ?? '');
    $end_date    = trim($_POST['end_date']    ?? '');
    $status      = $_POST['status']           ?? 'draft';
    $old         = compact('id','title','description','location','start_date','end_date','status');

    if ($auth->getRole() !== 'admin' && !$event_model->isOwnedBy($id, $organizer_id)) {
        $auth->setFlash('error', 'Kamu tidak berhak mengedit event ini.');
        header('Location: ' . BASE_URL . '/views/events/manage.php');
        exit;
    }

    if (empty($title))      $errors[] = 'Judul event wajib diisi.';
    if (empty($location))   $errors[] = 'Lokasi wajib diisi.';
    if (empty($start_date)) $errors[] = 'Tanggal mulai wajib diisi.';
    if (empty($end_date))   $errors[] = 'Tanggal selesai wajib diisi.';

    $banner_filename = $_POST['banner_lama'] ?? null;
    if (!empty($_FILES['banner']['name'])) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
        if (in_array($_FILES['banner']['type'], $allowed_types) && $_FILES['banner']['size'] <= 2 * 1024 * 1024) {
            $ext             = pathinfo($_FILES['banner']['name'], PATHINFO_EXTENSION);
            $banner_filename = uniqid('banner_') . '.' . $ext;
            move_uploaded_file($_FILES['banner']['tmp_name'], BASE_PATH . '/public/uploads/banners/' . $banner_filename);
        } else {
            $errors[] = 'Banner harus JPG/PNG/WebP, maks 2MB.';
        }
    }

    if (empty($errors)) {
        $event_model->update($id, $title, $description, $location, $start_date, $end_date, $banner_filename, $status);
        $auth->setFlash('success', 'Event berhasil diperbarui!');
        header('Location: ' . BASE_URL . '/views/events/manage.php');
        exit;
    }

    $aksi = 'edit';
}

if (isset($_POST['hapus_event'])) {
    $auth->verifyCsrfToken($_POST['csrf_token'] ?? '');

    $id = (int) ($_POST['id'] ?? 0);

    if ($auth->getRole() !== 'admin' && !$event_model->isOwnedBy($id, $organizer_id)) {
        $auth->setFlash('error', 'Kamu tidak berhak menghapus event ini.');
    } else {
        $event_model->delete($id);
        $auth->setFlash('success', 'Event berhasil dihapus.');
    }

    header('Location: ' . BASE_URL . '/views/events/manage.php');
    exit;
}

$edit_data = null;
if ($aksi === 'edit' && isset($_GET['id']) && empty($old)) {
    $id        = (int) $_GET['id'];
    $edit_data = $event_model->getById($id);

    if (!$edit_data || ($auth->getRole() !== 'admin' && $edit_data['organizer_id'] != $organizer_id)) {
        $auth->setFlash('error', 'Event tidak ditemukan atau kamu tidak berhak mengaksesnya.');
        header('Location: ' . BASE_URL . '/views/events/manage.php');
        exit;
    }

    $old = $edit_data;
}

$per_page = 10;
$page     = max(1, (int) ($_GET['page'] ?? 1));
$offset   = ($page - 1) * $per_page;

if ($auth->getRole() === 'admin') {
    $events      = $event_model->getAllWithOrganizer($per_page, $offset);
    $total       = $event_model->countAll();
} else {
    $events      = $event_model->getByOrganizer($organizer_id, $per_page, $offset);
    $total       = $event_model->countByOrganizer($organizer_id);
}

$total_pages = ceil($total / $per_page);

$page_title = 'Kelola Event';
?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<?php show_flash($auth); ?>

<?php if ($aksi === 'tambah'): ?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h5 class="fw-bold mb-0" style="color: var(--color-accent);">
        <i class="bi bi-plus-circle"></i> Buat Event Baru
    </h5>
    <a href="<?= BASE_URL ?>/views/events/manage.php" class="btn btn-outline-secondary btn-sm">
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

<div class="card card-elevate p-4">
    <form method="POST" action="" enctype="multipart/form-data">
        <?= csrf_input($auth) ?>

        <div class="row g-3">
            <div class="col-12">
                <label class="form-label fw-semibold">Judul Event <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control"
                       value="<?= e($old['title'] ?? '') ?>" required>
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold">Deskripsi</label>
                <textarea name="description" class="form-control" rows="4"><?= e($old['description'] ?? '') ?></textarea>
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold">Lokasi <span class="text-danger">*</span></label>
                <input type="text" name="location" class="form-control"
                       value="<?= e($old['location'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Tanggal & Jam Mulai <span class="text-danger">*</span></label>
                <input type="datetime-local" name="start_date" class="form-control"
                       value="<?= e($old['start_date'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Tanggal & Jam Selesai <span class="text-danger">*</span></label>
                <input type="datetime-local" name="end_date" class="form-control"
                       value="<?= e($old['end_date'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Status</label>
                <select name="status" class="form-select">
                    <?php foreach (['draft','published','cancelled','completed'] as $s): ?>
                        <option value="<?= $s ?>" <?= ($old['status'] ?? 'draft') === $s ? 'selected' : '' ?>>
                            <?= ucfirst($s) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Banner Event</label>
                <input type="file" name="banner" class="form-control" accept="image/jpeg,image/png,image/webp">
                <div class="form-text">JPG/PNG/WebP, maks 2MB</div>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button type="submit" name="tambah_event" class="btn btn-cta fw-semibold">
                <i class="bi bi-check-lg"></i> Buat Event
            </button>
            <a href="<?= BASE_URL ?>/views/events/manage.php" class="btn btn-outline-secondary">Batal</a>
        </div>
    </form>
</div>

<?php elseif ($aksi === 'edit'): ?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h5 class="fw-bold mb-0" style="color: var(--color-accent);">
        <i class="bi bi-pencil-square"></i> Edit Event
    </h5>
    <a href="<?= BASE_URL ?>/views/events/manage.php" class="btn btn-outline-secondary btn-sm">
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

<div class="card card-elevate p-4">
    <form method="POST" action="" enctype="multipart/form-data">
        <?= csrf_input($auth) ?>
        <input type="hidden" name="id" value="<?= e($old['id'] ?? $edit_data['id'] ?? '') ?>">
        <input type="hidden" name="banner_lama" value="<?= e($old['banner'] ?? $edit_data['banner'] ?? '') ?>">

        <div class="row g-3">
            <div class="col-12">
                <label class="form-label fw-semibold">Judul Event <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control"
                       value="<?= e($old['title'] ?? '') ?>" required>
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold">Deskripsi</label>
                <textarea name="description" class="form-control" rows="4"><?= e($old['description'] ?? '') ?></textarea>
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold">Lokasi <span class="text-danger">*</span></label>
                <input type="text" name="location" class="form-control"
                       value="<?= e($old['location'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Tanggal & Jam Mulai</label>
                <input type="datetime-local" name="start_date" class="form-control"
                       value="<?= e(str_replace(' ', 'T', $old['start_date'] ?? '')) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Tanggal & Jam Selesai</label>
                <input type="datetime-local" name="end_date" class="form-control"
                       value="<?= e(str_replace(' ', 'T', $old['end_date'] ?? '')) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Status</label>
                <select name="status" class="form-select">
                    <?php foreach (['draft','published','cancelled','completed'] as $s): ?>
                        <option value="<?= $s ?>" <?= ($old['status'] ?? '') === $s ? 'selected' : '' ?>>
                            <?= ucfirst($s) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Ganti Banner (Opsional)</label>
                <?php if (!empty($old['banner'])): ?>
                    <div class="mb-2">
                        <img src="<?= BASE_URL ?>/public/uploads/banners/<?= e($old['banner']) ?>"
                             style="height:60px;border-radius:6px;object-fit:cover;" alt="Banner">
                        <span class="small text-muted ms-2">Banner saat ini</span>
                    </div>
                <?php endif; ?>
                <input type="file" name="banner" class="form-control" accept="image/jpeg,image/png,image/webp">
                <div class="form-text">Kosongkan jika tidak ingin ganti banner.</div>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button type="submit" name="edit_event" class="btn btn-cta fw-semibold">
                <i class="bi bi-check-lg"></i> Simpan Perubahan
            </button>
            <a href="<?= BASE_URL ?>/views/events/manage.php" class="btn btn-outline-secondary">Batal</a>
        </div>
    </form>
</div>

<?php else: ?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h5 class="fw-bold mb-0" style="color: var(--color-accent);">
        <i class="bi bi-calendar3"></i> Kelola Event
        <span class="badge bg-secondary ms-1" style="font-size: 12px;"><?= $total ?></span>
    </h5>
    <a href="?aksi=tambah" class="btn btn-cta fw-semibold">
        <i class="bi bi-plus-circle"></i> Buat Event Baru
    </a>
</div>

<div class="card card-elevate">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-elevate">
                <tr>
                    <th>Judul Event</th>
                    <?php if ($auth->getRole() === 'admin'): ?>
                        <th>Organizer</th>
                    <?php endif; ?>
                    <th>Tanggal</th>
                    <th class="text-center">Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($events)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">
                            Belum ada event. <a href="?aksi=tambah" style="color: var(--color-accent);">Buat sekarang</a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($events as $ev): ?>
                        <tr>
                            <td class="fw-semibold"><?= e($ev['title']) ?></td>
                            <?php if ($auth->getRole() === 'admin'): ?>
                                <td class="small text-muted"><?= e($ev['organizer_name']) ?></td>
                            <?php endif; ?>
                            <td class="small"><?= tanggal_id($ev['start_date']) ?></td>
                            <td class="text-center">
                                <span class="badge <?= $ev['status'] === 'published' ? 'bg-success' : ($ev['status'] === 'draft' ? 'bg-warning text-dark' : 'bg-secondary') ?>">
                                    <?= e(ucfirst($ev['status'])) ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?= BASE_URL ?>/views/events/detail.php?id=<?= $ev['id'] ?>"
                                   class="btn btn-outline-secondary btn-sm" title="Lihat">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="<?= BASE_URL ?>/views/tickets/tipe.php?event_id=<?= $ev['id'] ?>"
                                   class="btn btn-outline-secondary btn-sm" title="Tipe Tiket">
                                    <i class="bi bi-ticket-perforated"></i>
                                </a>
                                <a href="<?= BASE_URL ?>/views/speakers/manage.php?event_id=<?= $ev['id'] ?>"
                                   class="btn btn-outline-secondary btn-sm" title="Speaker">
                                    <i class="bi bi-mic"></i>
                                </a>
                                <a href="?aksi=edit&id=<?= $ev['id'] ?>"
                                   class="btn btn-outline-cta btn-sm" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <!-- Hapus via POST (bukan GET langsung) -->
                                <form method="POST" action="" class="d-inline"
                                      onsubmit="return confirm('Hapus event \'<?= e(addslashes($ev['title'])) ?>\'? Semua tiket dan speaker terkait juga akan dihapus.')">
                                    <?= csrf_input($auth) ?>
                                    <input type="hidden" name="id" value="<?= $ev['id'] ?>">
                                    <button type="submit" name="hapus_event"
                                            class="btn btn-outline-danger btn-sm" title="Hapus">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
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
