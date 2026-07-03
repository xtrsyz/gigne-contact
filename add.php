<?php
require_once __DIR__ . '/includes/functions.php';

$banks = loadBanks();
$success = $error = null;

// buat dropdown "hubungkan ke identitas yang sudah ada"
$existing = db()->query("SELECT identifier, name FROM tag ORDER BY id DESC LIMIT 200")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (empty($_POST['data_type']) || empty($_POST['account_id'])) {
            throw new RuntimeException('Type data & ID akun wajib diisi.');
        }

        $result = saveTag([
            'data_type'  => trim($_POST['data_type']),
            'account_id' => trim($_POST['account_id']),
            'name'       => trim($_POST['name'] ?? ''),
            'tag'        => trim($_POST['tag'] ?? ''),
            'url'        => trim($_POST['url'] ?? ''),
            'link_to'    => trim($_POST['link_to'] ?? ''),
        ]);

        $success = $result['is_duplicate']
            ? "Laporan identik sudah ada - tidak dibuat duplikat. (hashid: {$result['hashid']})"
            : "Data tersimpan! hashid: {$result['hashid']}";
    } catch (Throwable $ex) {
        $error = $ex->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Input Data Penipuan</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 720px; margin: 2rem auto; padding: 0 1rem; }
        label { display: block; margin: .75rem 0 .25rem; font-weight: 600; }
        input, select, textarea { width: 100%; padding: .5rem; box-sizing: border-box; }
        textarea { min-height: 80px; }
        button { margin-top: 1rem; padding: .6rem 1.2rem; cursor: pointer; }
        .alert { padding: .75rem; border-radius: 6px; margin: 1rem 0; }
        .ok { background: #e6f7e6; color: #1a7f1a; }
        .err { background: #fde8e8; color: #b91c1c; }
        nav a { margin-right: 1rem; }
        small { color: #666; font-weight: 400; }
    </style>
</head>
<body>
    <nav>
        <a href="index.php">Cari</a>
        <a href="add.php">Input Data</a>
    </nav>
    <h1>Input Data Penipuan</h1>

    <?php if ($success): ?><div class="alert ok"><?= e($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert err"><?= e($error) ?></div><?php endif; ?>

    <form method="post">
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

        <button type="submit">Simpan Data</button>
    </form>
</body>
</html>
