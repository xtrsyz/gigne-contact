<?php
/**
 * mydata.php — halaman "Data Saya": daftar tag yang diinput user login.
 * Wajib login.
 */

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/footer.php';

startSession();
requireLogin();

$user = currentUser();
$tags = listUserTags((int)$user['id']);
$csrf = csrfToken();

$msg = $_GET['msg'] ?? '';

renderHeader('Data Saya');
?>
    <h1>Data Saya</h1>
    <p class="meta">Data yang Anda input ke sistem. Anda dapat menghapus data milik Anda sendiri.</p>

    <?php if ($msg === 'deleted'): ?>
        <div class="alert ok">Data berhasil dihapus.</div>
    <?php elseif ($msg === 'forbidden'): ?>
        <div class="alert err">Tidak diizinkan menghapus data tersebut.</div>
    <?php endif; ?>

    <?php if (empty($tags)): ?>
        <p>Anda belum pernah menginput data. <a href="/add.php">Input data sekarang</a>.</p>
    <?php else: ?>
        <table>
            <tr>
                <th>Identifier</th><th>Nama</th><th>Tag</th>
                <th>Waktu</th><th>Aksi</th>
            </tr>
            <?php foreach ($tags as $t):
                [$type, $acc] = parseIdentifier($t['identifier']);
            ?>
                <tr>
                    <td>
                        <a href="/<?= detailUrl($t['identifier']) ?>">
                            <strong><?= e($type) ?>:<?= e($acc) ?></strong>
                        </a>
                    </td>
                    <td><?= e($t['name']) ?></td>
                    <td><?= e($t['tag']) ?></td>
                    <td class="meta"><?= e($t['created_at']) ?></td>
                    <td>
                        <?php if ($t['status'] !== 'removed'): ?>
                            <form method="post" action="/delete.php"
                                  onsubmit="return confirm('Hapus data ini?')">
                                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                                <input type="hidden" name="tag_id" value="<?= (int)$t['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                            </form>
                        <?php else: ?>
                            <span class="meta">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

    <p style="margin-top:1.5rem"><a href="/add.php" class="btn btn-primary">+ Input Data Baru</a></p>
<?php renderFooter(); ?>
