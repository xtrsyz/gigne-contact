<?php
/**
 * sanggah.php — form pengajuan sanggah/hapus data. Publik, TANPA login.
 */

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/footer.php';

startSession();

$success = $error = null;

// tag_id atau identifier bisa datang dari query string (dari detail.php)
$preTagId     = isset($_GET['tag_id']) && ctype_digit($_GET['tag_id']) ? (int)$_GET['tag_id'] : null;
$preIdentifier = trim($_GET['identifier'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verifyCsrf();

        // Honeypot anti-bot: kalau field website diisi, ini bot
        if (!empty($_POST['website'])) {
            throw new RuntimeException('Terdeteksi aktivitas tidak wajar. Coba lagi.');
        }

        $reason = trim($_POST['reason'] ?? '');
        if ($reason === '') {
            throw new RuntimeException('Alasan sanggah wajib diisi.');
        }
        if (mb_strlen($reason) < 10) {
            throw new RuntimeException('Alasan sanggah terlalu pendek (minimal 10 karakter).');
        }

        $tagId = isset($_POST['tag_id']) && ctype_digit($_POST['tag_id'])
            ? (int)$_POST['tag_id']
            : null;
        $identifier = trim($_POST['identifier'] ?? '') ?: null;
        $contact    = trim($_POST['contact'] ?? '') ?: null;

        createDispute([
            'tag_id'     => $tagId,
            'identifier' => $identifier,
            'reason'     => $reason,
            'contact'    => $contact,
        ]);

        $success = 'Pengajuan sanggah Anda telah diterima dan akan ditinjau oleh admin. Terima kasih.';
    } catch (Throwable $ex) {
        $error = $ex->getMessage();
    }
}

renderHeader('Ajukan Sanggah / Hapus Data');
?>
    <h1>Ajukan Sanggah / Hapus Data</h1>

    <div class="alert info">
        <strong>Tentang fitur ini:</strong>
        Jika Anda merasa ada data yang keliru, tidak akurat, atau menyangkut identitas Anda dan ingin dihapus,
        Anda dapat mengajukan sanggah di sini <strong>tanpa perlu login</strong>.
        Pengajuan akan ditinjau oleh admin sebelum diproses.
        Baca <a href="/disclaimer.php">disclaimer lengkap</a> untuk informasi lebih lanjut.
    </div>

    <?php if ($success): ?>
        <div class="alert ok"><?= e($success) ?></div>
    <?php else: ?>
        <?php if ($error): ?>
            <div class="alert err"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

            <?php if ($preTagId): ?>
                <input type="hidden" name="tag_id" value="<?= (int)$preTagId ?>">
            <?php endif; ?>
            <?php if ($preIdentifier !== ''): ?>
                <input type="hidden" name="identifier" value="<?= e($preIdentifier) ?>">
            <?php endif; ?>

            <?php if ($preIdentifier || $preTagId): ?>
                <div class="alert info">
                    Pengajuan ini terkait data:
                    <strong><?= e($preIdentifier ?: "ID #{$preTagId}") ?></strong>
                </div>
            <?php else: ?>
                <label>Identifier data yang disanggah
                    <small>(mis. phone:6281234567890 atau discord:123456789 — opsional jika sudah diketahui)</small>
                </label>
                <input type="text" name="identifier" placeholder="phone:628xxxx atau kosongkan">
            <?php endif; ?>

            <label>Alasan sanggah <span style="color:#b91c1c">*</span></label>
            <textarea name="reason" required placeholder="Jelaskan mengapa data ini perlu dikoreksi atau dihapus..."></textarea>

            <label>Kontak Anda <small>(opsional — email/WA, supaya admin bisa menghubungi Anda)</small></label>
            <input type="text" name="contact" placeholder="email atau no WhatsApp">

            <!-- Honeypot: disembunyikan dari manusia, bot sering mengisinya -->
            <div style="display:none" aria-hidden="true">
                <label>Website (biarkan kosong)</label>
                <input type="text" name="website" tabindex="-1" autocomplete="off">
            </div>

            <div class="disclaimer-check" style="margin-top:1rem">
                <p style="margin:0;font-size:.9rem">
                    ⚠️ Pengajuan sanggah yang tidak berdasar atau fiktif dapat berakibat hukum.
                    Admin akan meninjau dalam waktu paling lama 7 hari kerja.
                </p>
            </div>

            <button type="submit" class="btn btn-primary" style="margin-top:.75rem">Kirim Pengajuan</button>
        </form>
    <?php endif; ?>
<?php renderFooter(); ?>
