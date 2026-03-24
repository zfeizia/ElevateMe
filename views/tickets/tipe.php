<?php
require_once __DIR__ . '/../../config/bootstrap.php';

$auth->requireRole(['admin', 'organizer']);

$ticket_type_model = new TicketTypeModel();
$event_model       = new EventModel();
$organizer_id      = $auth->getUserId();

$event_id = (int) ($_GET['event_id'] ?? 0);
$aksi     = $_GET['aksi'] ?? 'list';
$errors   = [];
$old      = [];

// Validasi event ada & milik organizer ini
$event = $event_model->getById($event_id);
if (!$event || ($auth->getRole() !== 'admin' && $event['organizer_id'] != $organizer_id)) {
    $auth->setFlash('error', 'Event tidak ditemukan atau kamu tidak berhak mengaksesnya.');
    header('Location: ' . BASE_URL . '/views/events/manage.php');
    exit;
}

if (isset($_POST['tambah_tipe'])) {
    $auth->verifyCsrfToken($_POST['csrf_token'] ?? '');

    $name                = trim($_POST['name']                ?? '');
    $price               = (float) ($_POST['price']          ?? 0);
    $quota               = (int) ($_POST['quota']            ?? 0);
    $description         = trim($_POST['description']        ?? '');
    $early_bird_price    = !empty($_POST['early_bird_price']) ? (float) $_POST['early_bird_price'] : null;
    $early_bird_deadline = !empty($_POST['early_bird_deadline']) ? $_POST['early_bird_deadline'] : null;
    $old = compact('name','price','quota','description','early_bird_price','early_bird_deadline');

    if (empty($name))    $errors[] = 'Nama tipe tiket wajib diisi.';
    if ($price < 0)      $errors[] = 'Harga tidak boleh negatif.';
    if ($quota <= 0)     $errors[] = 'Kuota harus lebih dari 0.';
    if (!empty($early_bird_price) && $early_bird_price >= $price) {
        $errors[] = 'Harga early bird harus lebih kecil dari harga normal.';
    }

    if (empty($errors)) {
        $ticket_type_model->insert($event_id, $name, $price, $quota, $description, $early_bird_price, $early_bird_deadline);
        $auth->setFlash('success', 'Tipe tiket berhasil ditambahkan.');
        header('Location: ' . BASE_URL . '/views/tickets/tipe.php?event_id=' . $event_id);
        exit;
    }
    $aksi = 'tambah';
}

if (isset($_POST['edit_tipe'])) {
    $auth->verifyCsrfToken($_POST['csrf_token'] ?? '');

    $id                  = (int) ($_POST['id'] ?? 0);
    $name                = trim($_POST['name']           ?? '');
    $price               = (float) ($_POST['price']     ?? 0);
    $quota               = (int) ($_POST['quota']       ?? 0);
    $description         = trim($_POST['description']   ?? '');
    $early_bird_price    = !empty($_POST['early_bird_price']) ? (float) $_POST['early_bird_price'] : null;
    $early_bird_deadline = !empty($_POST['early_bird_deadline']) ? $_POST['early_bird_deadline'] : null;
    $old = compact('id','name','price','quota','description','early_bird_price','early_bird_deadline');

    if (empty($name)) $errors[] = 'Nama tipe tiket wajib diisi.';
    if ($price < 0)   $errors[] = 'Harga tidak boleh negatif.';
    if ($quota <= 0)  $errors[] = 'Kuota harus lebih dari 0.';

    if (empty($errors)) {
        $ticket_type_model->update($id, $name, $price, $quota, $description, $early_bird_price, $early_bird_deadline);
        $auth->setFlash('success', 'Tipe tiket berhasil diperbarui.');
        header('Location: ' . BASE_URL . '/views/tickets/tipe.php?event_id=' . $event_id);
        exit;
    }
    $aksi = 'edit';
}

if (isset($_POST['hapus_tipe'])) {
    $auth->verifyCsrfToken($_POST['csrf_token'] ?? '');
    $ticket_type_model->delete((int) ($_POST['id'] ?? 0));
    $auth->setFlash('success', 'Tipe tiket berhasil dihapus.');
    header('Location: ' . BASE_URL . '/views/tickets/tipe.php?event_id=' . $event_id);
    exit;
}

$edit_data = null;
if ($aksi === 'edit' && isset($_GET['id']) && empty($old)) {
    $edit_data = $ticket_type_model->getById((int) $_GET['id']);
    $old       = $edit_data ?? [];
}

$tipe_tikets = $ticket_type_model->getByEvent($event_id);

$page_title = 'Tipe Tiket — ' . $event['title'];
?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php show_flash($auth); ?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h5 class="fw-bold mb-0" style="color: var(--color-accent);">
            <i class="bi bi-ticket-perforated"></i> Tipe Tiket
        </h5>
        <span class="text-muted small"><?= e($event['title']) ?></span>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/views/events/manage.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Event
        </a>
        <?php if ($aksi === 'list'): ?>
            <a href="?event_id=<?= $event_id ?>&aksi=tambah" class="btn btn-cta btn-sm">
                <i class="bi bi-plus"></i> Tambah Tipe Tiket
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-elevate-error mb-3 p-3 rounded small">
        <ul class="mb-0 ps-3"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<?php if (in_array($aksi, ['tambah', 'edit'])): ?>

    <div class="card card-elevate p-4 mb-4" style="max-width: 600px;">
        <h6 class="fw-bold mb-3"><?= $aksi === 'tambah' ? 'Tambah Tipe Tiket Baru' : 'Edit Tipe Tiket' ?></h6>
        <form method="POST" action="">
            <?= csrf_input($auth) ?>
            <input type="hidden" name="event_id" value="<?= $event_id ?>">
            <?php if ($aksi === 'edit'): ?>
                <input type="hidden" name="id" value="<?= e($old['id'] ?? '') ?>">
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label fw-semibold">Nama Tipe Tiket *</label>
                    <input type="text" name="name" class="form-control" value="<?= e($old['name'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Harga Normal (Rp) *</label>
                    <input type="number" name="price" class="form-control" min="0"
                           value="<?= e($old['price'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Kuota *</label>
                    <input type="number" name="quota" class="form-control" min="1"
                           value="<?= e($old['quota'] ?? '') ?>" required>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Deskripsi</label>
                    <textarea name="description" class="form-control" rows="2"><?= e($old['description'] ?? '') ?></textarea>
                </div>
                <div class="col-12"><hr><p class="small fw-semibold mb-2" style="color: var(--color-accent);">
                    <i class="bi bi-lightning-fill"></i> Pengaturan Early Bird 
                </p></div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Harga Early Bird (Rp)</label>
                    <input type="number" name="early_bird_price" class="form-control" min="0"
                           value="<?= e($old['early_bird_price'] ?? '') ?>" placeholder="Kosongkan jika tidak ada">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Berlaku Hingga</label>
                    <input type="datetime-local" name="early_bird_deadline" class="form-control"
                           value="<?= e(str_replace(' ', 'T', $old['early_bird_deadline'] ?? '')) ?>">
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit"
                        name="<?= $aksi === 'tambah' ? 'tambah_tipe' : 'edit_tipe' ?>"
                        class="btn btn-cta fw-semibold">
                    <i class="bi bi-check-lg"></i> Simpan
                </button>
                <a href="?event_id=<?= $event_id ?>" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>

<?php endif; ?>

<!-- List Tipe Tiket -->
<div class="card card-elevate">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-elevate">
                <tr>
                    <th>Nama</th>
                    <th>Harga Normal</th>
                    <th>Early Bird</th>
                    <th class="text-center">Kuota</th>
                    <th class="text-center">Terjual</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tipe_tikets)): ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada tipe tiket.</td></tr>
                <?php else: ?>
                    <?php foreach ($tipe_tikets as $tt):
                        $is_eb = $ticket_type_model->isEarlyBirdActive($tt);
                    ?>
                        <tr>
                            <td class="fw-semibold">
                                <?= e($tt['name']) ?>
                                <?php if ($is_eb): ?>
                                    <span class="badge ms-1" style="background:#f59e0b;font-size:9px;">Early Bird Aktif</span>
                                <?php endif; ?>
                            </td>
                            <td><?= rupiah($tt['price']) ?></td>
                            <td>
                                <?php if (!empty($tt['early_bird_price'])): ?>
                                    <span class="<?= $is_eb ? 'text-success fw-bold' : 'text-muted' ?>">
                                        <?= rupiah($tt['early_bird_price']) ?>
                                    </span>
                                    <div class="small text-muted">s.d. <?= tanggal_id($tt['early_bird_deadline']) ?></div>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center"><?= $tt['quota'] ?></td>
                            <td class="text-center fw-bold"><?= $tt['sold'] ?></td>
                            <td>
                                <a href="?event_id=<?= $event_id ?>&aksi=edit&id=<?= $tt['id'] ?>"
                                   class="btn btn-outline-cta btn-sm">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="" class="d-inline"
                                      onsubmit="return confirm('Hapus tipe tiket ini?')">
                                    <?= csrf_input($auth) ?>
                                    <input type="hidden" name="id" value="<?= $tt['id'] ?>">
                                    <button type="submit" name="hapus_tipe"
                                            class="btn btn-outline-danger btn-sm">
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

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
