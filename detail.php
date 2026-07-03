<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/footer.php';

startSession();

$rawId = trim($_GET['id'] ?? '');

// normalisasi kalau yang dibuka berupa identifier phone
if ($rawId !== '') {
    [$t, $v]    = parseIdentifier($rawId);
    $identifier = makeIdentifier($t, $v);
} else {
    $identifier = '';
}

// untuk tampilan publik: hanya status='active'
$network = $identifier !== '' ? getNetwork($identifier, true) : [];

if (empty($network)) {
    http_response_code(404);
    echo '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><title>404</title></head><body>';
    echo '<p>Data tidak ditemukan.</p><a href="/index.php">Kembali</a>';
    echo '</body></html>';
    exit;
}

$root = findRoot($identifier);

// ambil nama & tag representatif
$name = $tagLabel = '';
foreach ($network as $n) {
    if (!$name && $n['name']) $name = $n['name'];
    if (!$tagLabel && $n['tag']) $tagLabel = $n['tag'];
}

$user    = currentUser();
$isAdmin = isAdmin();
$csrf    = csrfToken();

renderHeader('Detail - ' . ($name ?: $identifier));
?>
    <h1><?= e($name ?: 'Tanpa nama') ?>
        <?php if ($tagLabel): ?><span class="tag-label"><?= e($tagLabel) ?></span><?php endif; ?>
    </h1>
    <p class="meta"><?= count($network) ?> identitas terhubung - root: <code><?= e($root) ?></code></p>

    <p>
        <a class="btn" href="/sanggah.php?identifier=<?= urlencode($identifier) ?>">
            📝 Ajukan Sanggah untuk Data Ini
        </a>
    </p>

    <div class="section">
        <h2>Semua Identitas Terhubung</h2>
        <table>
            <tr>
                <th>Type Data</th><th>ID Akun</th><th>Nama</th>
                <th>Terhubung ke</th><th>Waktu</th><th>Aksi</th>
            </tr>
            <?php foreach ($network as $n):
                [$type, $acc] = parseIdentifier($n['identifier']);
                $isRoot       = ($n['identifier'] === $root);
                $canDelete    = $user && ($isAdmin || (int)$n['user_id'] === (int)$user['id']);
            ?>
                <tr>
                    <td><?= e(bankName($type)) ?></td>
                    <td>
                        <strong><?= e($acc) ?></strong>
                        <?php if ($isRoot): ?><span class="root"> ★ root</span><?php endif; ?>
                    </td>
                    <td><?= e($n['name']) ?></td>
                    <td><code><?= e($n['id_link'] ?: '-') ?></code></td>
                    <td class="meta"><?= e($n['created_at']) ?></td>
                    <td>
                        <?php if ($canDelete): ?>
                            <form method="post" action="/delete.php"
                                  onsubmit="return confirm('Hapus data ini?')">
                                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                                <input type="hidden" name="tag_id"
                                       value="<?= (int)$n['id'] ?>">
                                <input type="hidden" name="return_id"
                                       value="<?= e($identifier) ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="section">
        <h2>Link Bukti</h2>
        <?php
        $links = [];
        foreach ($network as $n) {
            if (!empty($n['url'])) $links[$n['url']] = $n['identifier'];
        }
        ?>
        <?php if (empty($links)): ?>
            <p class="meta">Belum ada link bukti.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($links as $url => $fromIdent): ?>
                    <li>
                        <a href="<?= e($url) ?>" target="_blank" rel="noopener noreferrer"><?= e($url) ?></a>
                        <span class="meta">(dari <?= e($fromIdent) ?>)</span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
<?php renderFooter(); ?>
