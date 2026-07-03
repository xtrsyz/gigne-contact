<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/footer.php';

startSession();
requireLogin();

$banks   = loadBanks();
$user    = currentUser();
$success = $error = null;

// buat dropdown "hubungkan ke identitas yang sudah ada" - hanya yang aktif
$existing = db()->prepare("SELECT identifier, name FROM tag WHERE status = 'active' ORDER BY id DESC LIMIT 200");
$existing->execute();
$existing = $existing->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verifyCsrf();

        if (empty($_POST['data_type']) || empty($_POST['account_id'])) {
            throw new RuntimeException('Type data & ID akun wajib diisi.');
        }

        if (empty($_POST['disclaimer_agree'])) {
            throw new RuntimeException('Anda harus menyetujui disclaimer tanggung jawab sebelum menyimpan data.');
        }

        $result = saveTag([
            'data_type'  => trim($_POST['data_type']),
            'account_id' => trim($_POST['account_id']),
            'name'       => trim($_POST['name'] ?? ''),
            'tag'        => trim($_POST['tag'] ?? ''),
            'url'        => trim($_POST['url'] ?? ''),
            'link_to'    => trim($_POST['link_to'] ?? ''),
            'user_id'    => $user['id'],
        ]);

        $success = $result['is_duplicate']
            ? "Laporan identik sudah ada - tidak dibuat duplikat."
            : "Data tersimpan!";
    } catch (Throwable $ex) {
        $error = $ex->getMessage();
    }
}

renderHeader('Input Data Penipuan');
?>
    <h1>Input Data Penipuan</h1>

    <?php if ($success): ?><div class="alert ok"><?= e($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert err"><?= e($error) ?></div><?php endif; ?>

    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

        <label>Type Data</label>
        <select name="data_type" required>
            <option value="">-- Pilih type data --</option>
            <?php foreach ($banks as $id => $nama): ?>
                <option value="<?= e($id) ?>"><?= e($nama) ?></option>
            <?php endforeach; ?>
        </select>

        <label>ID Akun <small>(no rekening / Discord ID / no HP / Steam ID / dll)</small></label>
        <input type="text" name="account_id" required>

        <label>Nama Pemilik</label>
        <input type="text" name="name">

        <label>Tag <small>(mis. "Tukang Scam FiveM")</small></label>
        <input type="text" name="tag" placeholder="Tukang Scam FiveM">

        <label>Link Bukti (URL)</label>
        <input type="text" name="url" placeholder="https://discord.com/channels/...">

        <label>Hubungkan ke identitas <small>(opsional - biar saling terhubung)</small></label>
        <select name="link_to">
            <option value="">-- Jadikan data baru (root) --</option>
            <?php foreach ($existing as $ex): ?>
                <option value="<?= e($ex['identifier']) ?>">
                    <?= e($ex['identifier']) ?><?= $ex['name'] ? ' - ' . e($ex['name']) : '' ?>
                </option>
            <?php endforeach; ?>
        </select>

        <div class="disclaimer-check">
            <label style="display:flex;gap:.5rem;align-items:flex-start;font-weight:normal">
                <input type="checkbox" name="disclaimer_agree" value="1" required style="width:auto;margin-top:.2rem">
                <span>
                    Saya bertanggung jawab atas data yang saya input.
                    Data yang saya masukkan adalah <strong>benar &amp; dapat dipertanggungjawabkan</strong>,
                    dan saya memahami konsekuensi hukum
                    (<strong>UU Perlindungan Data Pribadi / pencemaran nama baik</strong>)
                    bila memasukkan data palsu atau menyesatkan.
                </span>
            </label>
        </div>

        <button type="submit" class="btn btn-primary">Simpan Data</button>
    </form>
<?php renderFooter(); ?>
