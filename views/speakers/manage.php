<?php
require_once __DIR__ . '/../../config/bootstrap.php';

$auth->requireRole(['admin', 'organizer']);

$speaker_model = new SpeakerModel();
$event_model   = new EventModel();
$organizer_id  = $auth->getUserId();

$event_id = (int) ($_GET['event_id'] ?? 0);
$aksi     = $_GET['aksi'] ?? 'list';
$errors   = [];
$old      = [];

$event = $event_model->getById($event_id);
if (!$event || ($auth->getRole() !== 'admin' && $event['organizer_id'] != $organizer_id)) {
    $auth->setFlash('error', 'Event tidak ditemukan atau kamu tidak berhak mengaksesnya.');
    header('Location: ' . BASE_URL . '/views/events/manage.php');
    exit;
}

if (isset($_POST['tambah_speaker'])) {
    $auth->verifyCsrfToken($_POST['csrf_token'] ?? '');

    $name         = trim($_POST['name']         ?? '');
    $topic        = trim($_POST['topic']        ?? '');
    $bio          = trim($_POST['bio']          ?? '');
    $session_time = trim($_POST['session_time'] ?? '');
    $old          = compact('name', 'topic', 'bio', 'session_time');

    // Validasi input
    if (empty($name))         $errors[] = 'Nama speaker wajib diisi.';
    if (empty($topic))        $errors[] = 'Topik wajib diisi.';
    if (empty($session_time)) $errors[] = 'Waktu sesi wajib diisi.';

    $photo_filename = null;
    if (!empty($_FILES['photo']['name'])) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
        $max_size      = 1 * 1024 * 1024; // 1MB

        if (!in_array($_FILES['photo']['type'], $allowed_types)) {
            $errors[] = 'Foto harus berformat JPG, PNG, atau WebP.';
        } elseif ($_FILES['photo']['size'] > $max_size) {
            $errors[] = 'Ukuran foto maksimal 1MB.';
        } else {
            $ext            = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $photo_filename = uniqid('spk_') . '.' . $ext;
            $upload_path    = BASE_PATH . '/public/uploads/speakers/' . $photo_filename;

            if (!move_uploaded_file($_FILES['photo']['tmp_name'], $upload_path)) {
                $errors[] = 'Gagal upload foto. Pastikan folder /public/uploads/speakers/ bisa ditulis.';
                $photo_filename = null;
            }
        }
    }

    if (empty($errors)) {
        $speaker_model->insert($event_id, $name, $topic, $bio, $photo_filename, $session_time);
        $auth->setFlash('success', "Speaker '$name' berhasil ditambahkan.");
        header('Location: ' . BASE_URL . '/views/speakers/manage.php?event_id=' . $event_id);
        exit;
    }

    $aksi = 'tambah';
}

if (isset($_POST['edit_speaker'])) {
    $auth->verifyCsrfToken($_POST['csrf_token'] ?? '');

    $id           = (int) ($_POST['id']           ?? 0);
    $name         = trim($_POST['name']         ?? '');
    $topic        = trim($_POST['topic']        ?? '');
    $bio          = trim($_POST['bio']          ?? '');
    $session_time = trim($_POST['session_time'] ?? '');
    $old          = compact('id', 'name', 'topic', 'bio', 'session_time');

    if (empty($name))         $errors[] = 'Nama speaker wajib diisi.';
    if (empty($topic))        $errors[] = 'Topik wajib diisi.';
    if (empty($session_time)) $errors[] = 'Waktu sesi wajib diisi.';

    $photo_filename = $_POST['photo_lama'] ?? null;
    if (!empty($_FILES['photo']['name'])) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
        if (in_array($_FILES['photo']['type'], $allowed_types) && $_FILES['photo']['size'] <= 1024 * 1024) {
            $ext            = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $photo_filename = uniqid('spk_') . '.' . $ext;
            move_uploaded_file(
                $_FILES['photo']['tmp_name'],
                BASE_PATH . '/public/uploads/speakers/' . $photo_filename
            );
        } else {
            $errors[] = 'Foto harus JPG/PNG/WebP dan maks 1MB.';
        }
    }

    if (empty($errors)) {
        $speaker_model->update($id, $name, $topic, $bio, $photo_filename, $session_time);
        $auth->setFlash('success', "Speaker '$name' berhasil diperbarui.");
        header('Location: ' . BASE_URL . '/views/speakers/manage.php?event_id=' . $event_id);
        exit;
    }

    $aksi = 'edit';
}

if (isset($_POST['hapus_speaker'])) {
    $auth->verifyCsrfToken($_POST['csrf_token'] ?? '');

    $id = (int) ($_POST['id'] ?? 0);

    if (!$speaker_model->isOwnedByOrganizer($id, $organizer_id) && $auth->getRole() !== 'admin') {
        $auth->setFlash('error', 'Kamu tidak berhak menghapus speaker ini.');
    } else {
        $speaker_model->delete($id);
        $auth->setFlash('success', 'Speaker berhasil dihapus.');
    }

    header('Location: ' . BASE_URL . '/views/speakers/manage.php?event_id=' . $event_id);
    exit;
}

$edit_data = null;
if ($aksi === 'edit' && isset($_GET['id']) && empty($old)) {
    $edit_data = $speaker_model->getById((int) $_GET['id']);

    if (!$edit_data) {
        $auth->setFlash('error', 'Speaker tidak ditemukan.');
        header('Location: ' . BASE_URL . '/views/speakers/manage.php?event_id=' . $event_id);
        exit;
    }

    $old = $edit_data;
}

$speakers   = $speaker_model->getByEvent($event_id);
$page_title = 'Kelola Speaker — ' . $event['title'];
?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<?php show_flash($auth); ?>

<!-- ── Header ── -->
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h5 class="fw-bold mb-0" style="color: var(--color-accent);">
            <i class="bi bi-mic-fill"></i> Kelola Speaker
        </h5>
        <span class="text-muted small">
            <i class="bi bi-calendar3"></i> <?= e($event['title']) ?>
        </span>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/views/events/detail.php?id=<?= $event_id ?>"
           class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-eye"></i> Lihat Event
        </a>
        <a href="<?= BASE_URL ?>/views/events/manage.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Event
        </a>
        <?php if ($aksi === 'list'): ?>
            <a href="?event_id=<?= $event_id ?>&aksi=tambah" class="btn btn-cta btn-sm fw-semibold">
                <i class="bi bi-plus-circle"></i> Tambah Speaker
            </a>
        <?php endif; ?>
    </div>
</div>

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

<?php if (in_array($aksi, ['tambah', 'edit'])): ?>

    <div class="card card-elevate p-4 mb-4" style="max-width: 640px;">
        <h6 class="fw-bold mb-3" style="color: var(--color-accent);">
            <?= $aksi === 'tambah' ? '➕ Tambah Speaker Baru' : '✏️ Edit Speaker' ?>
        </h6>

        <form method="POST" action="" enctype="multipart/form-data">
            <?= csrf_input($auth) ?>
            <input type="hidden" name="event_id" value="<?= $event_id ?>">
            <?php if ($aksi === 'edit'): ?>
                <input type="hidden" name="id"         value="<?= e($old['id']    ?? '') ?>">
                <input type="hidden" name="photo_lama" value="<?= e($old['photo'] ?? '') ?>">
            <?php endif; ?>

            <div class="row g-3">

                <!-- Nama Speaker -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">
                        Nama Speaker <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="name" class="form-control"
                           value="<?= e($old['name'] ?? '') ?>"
                           placeholder="contoh: Dr. Budi Santoso"
                           required>
                </div>

                <!-- Topik -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">
                        Topik Presentasi <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="topic" class="form-control"
                           value="<?= e($old['topic'] ?? '') ?>"
                           placeholder="contoh: Growth Mindset in Tech"
                           required>
                </div>

                <!-- Waktu Sesi -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">
                        Waktu Sesi <span class="text-danger">*</span>
                    </label>
                    <input type="datetime-local" name="session_time" class="form-control"
                           value="<?= e(str_replace(' ', 'T', $old['session_time'] ?? '')) ?>"
                           required>
                </div>

                <!-- Foto Speaker -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Foto Speaker (Opsional)</label>
                    <?php if (!empty($old['photo'])): ?>
                        <div class="mb-2 d-flex align-items-center gap-2">
                            <img src="<?= BASE_URL ?>/public/uploads/speakers/<?= e($old['photo']) ?>"
                                 class="rounded-circle"
                                 style="width:40px;height:40px;object-fit:cover;"
                                 alt="Foto saat ini">
                            <span class="small text-muted">Foto saat ini</span>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="photo" class="form-control"
                           accept="image/jpeg,image/png,image/webp">
                    <div class="form-text">JPG/PNG/WebP, maks 1MB. Kosongkan jika tidak ingin ganti.</div>
                </div>

                <!-- Bio -->
                <div class="col-12">
                    <label class="form-label fw-semibold">Bio / Profil Singkat</label>
                    <textarea name="bio" class="form-control" rows="3"
                              placeholder="Tuliskan latar belakang dan pengalaman speaker..."><?= e($old['bio'] ?? '') ?></textarea>
                </div>

            </div>

            <!-- Tombol Submit -->
            <div class="d-flex gap-2 mt-4">
                <button type="submit"
                        name="<?= $aksi === 'tambah' ? 'tambah_speaker' : 'edit_speaker' ?>"
                        class="btn btn-cta fw-semibold">
                    <i class="bi bi-check-lg"></i>
                    <?= $aksi === 'tambah' ? 'Tambah Speaker' : 'Simpan Perubahan' ?>
                </button>
                <a href="?event_id=<?= $event_id ?>" class="btn btn-outline-secondary">
                    Batal
                </a>
            </div>
        </form>
    </div>

<?php endif; ?>

<?php if (empty($speakers)): ?>
    <div class="text-center py-5">
        <i class="bi bi-mic" style="font-size: 3rem; color: #ccc;"></i>
        <p class="mt-3 text-muted">Belum ada speaker untuk event ini.</p>
        <a href="?event_id=<?= $event_id ?>&aksi=tambah" class="btn btn-cta btn-sm">
            <i class="bi bi-plus-circle"></i> Tambah Speaker Pertama
        </a>
    </div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($speakers as $spk): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card card-elevate h-100">
                    <div class="card-body">

                        <!-- Foto + Nama -->
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <?php if (!empty($spk['photo'])): ?>
                                <img src="<?= BASE_URL ?>/public/uploads/speakers/<?= e($spk['photo']) ?>"
                                     class="rounded-circle flex-shrink-0"
                                     style="width:56px;height:56px;object-fit:cover;"
                                     alt="<?= e($spk['name']) ?>">
                            <?php else: ?>
                                <div class="rounded-circle flex-shrink-0 d-flex align-items-center justify-content-center"
                                     style="width:56px;height:56px;background:var(--color-accent);color:#fff;font-size:1.5rem;">
                                    <i class="bi bi-person-fill"></i>
                                </div>
                            <?php endif; ?>
                            <div>
                                <div class="fw-bold"><?= e($spk['name']) ?></div>
                                <div class="small" style="color: var(--color-accent);">
                                    <?= e($spk['topic']) ?>
                                </div>
                            </div>
                        </div>

                        <!-- Waktu Sesi -->
                        <div class="small text-muted mb-2">
                            <i class="bi bi-clock" style="color: var(--color-accent);"></i>
                            <?= tanggal_id($spk['session_time']) ?>,
                            <?= date('H:i', strtotime($spk['session_time'])) ?> WIB
                        </div>

                        <!-- Bio singkat -->
                        <?php if (!empty($spk['bio'])): ?>
                            <p class="small text-muted mb-3">
                                <?= e(mb_substr($spk['bio'], 0, 100)) ?>
                                <?= mb_strlen($spk['bio']) > 100 ? '...' : '' ?>
                            </p>
                        <?php endif; ?>

                        <!-- Tombol Aksi -->
                        <div class="d-flex gap-2 mt-auto">
                            <a href="?event_id=<?= $event_id ?>&aksi=edit&id=<?= $spk['id'] ?>"
                               class="btn btn-outline-cta btn-sm flex-fill">
                                <i class="bi bi-pencil"></i> Edit
                            </a>
                            <form method="POST" action="" class="flex-fill"
                                  onsubmit="return confirm('Hapus speaker \'<?= e(addslashes($spk['name'])) ?>\'?')">
                                <?= csrf_input($auth) ?>
                                <input type="hidden" name="id"       value="<?= $spk['id'] ?>">
                                <input type="hidden" name="event_id" value="<?= $event_id ?>">
                                <button type="submit" name="hapus_speaker"
                                        class="btn btn-outline-danger btn-sm w-100">
                                    <i class="bi bi-trash"></i> Hapus
                                </button>
                            </form>
                        </div>

                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
