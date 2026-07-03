<?php
/**
 * admin.php — Panel Admin: tinjauan pengajuan sanggah (disputes).
 * Wajib admin.
 */

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/footer.php';

startSession();
requireAdmin();

$user    = currentUser();
$success = $error = null;

// ── Handle approve/reject ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verifyCsrf();

        $disputeId = isset($_POST['dispute_id']) ? (int)$_POST['dispute_id'] : 0;
        $decision  = trim($_POST['decision'] ?? '');
        $adminNote = trim($_POST['admin_note'] ?? '') ?: null;

        if ($disputeId <= 0 || !in_array($decision, ['approved', 'rejected'], true)) {
            throw new RuntimeException('Parameter tidak valid.');
        }

        handleDispute($disputeId, (int)$user['id'], $decision, $adminNote);
        $success = $decision === 'approved'
            ? 'Pengajuan disetujui. Data terkait telah di-soft-delete.'
            : 'Pengajuan ditolak.';
    } catch (Throwable $ex) {
        $error = $ex->getMessage();
    }
}

// ── Filter status ─────────────────────────────────────────────────────────
$filterStatus = in_array($_GET['status'] ?? '', ['pending', 'approved', 'rejected'])
    ? $_GET['status']
    : 'pending';

$disputes = listDisputes($filterStatus);
$csrf     = csrfToken();

renderHeader('Panel Admin — Disputes');
?>
    <h1>Panel Admin</h1>

    <?php if ($success): ?><div class="alert ok"><?= e($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert err"><?= e($error) ?></div><?php endif; ?>

    <nav style="margin-bottom:1rem">
        Filter:
        <a href="?status=pending"   <?= $filterStatus === 'pending'   ? 'style="font-weight:bold"' : '' ?>>Pending</a> |
        <a href="?status=approved"  <?= $filterStatus === 'approved'  ? 'style="font-weight:bold"' : '' ?>>Approved</a> |
        <a href="?status=rejected"  <?= $filterStatus === 'rejected'  ? 'style="font-weight:bold"' : '' ?>>Rejected</a>
    </nav>

    <h2>Disputes — <?= e(ucfirst($filterStatus)) ?> (<?= count($disputes) ?>)</h2>

    <?php if (empty($disputes)): ?>
        <p class="meta">Tidak ada pengajuan berstatus <?= e($filterStatus) ?>.</p>
    <?php else: ?>
        <?php foreach ($disputes as $d): ?>
            <div class="card">
                <p>
                    <strong>#<?= (int)$d['id'] ?></strong>
                    &mdash; dikirim: <?= e($d['created_at']) ?>
                    | IP: <?= e($d['ip'] ?? '-') ?>
                </p>
                <p>
                    <strong>Data terkait:</strong>
                    <?php if ($d['tag_id']): ?>
                        <a href="/detail.php?id=<?= urlencode($d['tag_identifier'] ?? '') ?>">
                            <?= e($d['tag_identifier'] ?? "tag #" . $d['tag_id']) ?>
                            <?php if ($d['tag_name']): ?>(<?= e($d['tag_name']) ?>)<?php endif; ?>
                        </a>
                    <?php elseif ($d['identifier']): ?>
                        <?= e($d['identifier']) ?> <span class="meta">(tag sudah dihapus)</span>
                    <?php else: ?>
                        <span class="meta">—</span>
                    <?php endif; ?>
                </p>
                <p><strong>Alasan:</strong> <?= e($d['reason']) ?></p>
                <?php if ($d['contact']): ?>
                    <p><strong>Kontak pelapor:</strong> <?= e($d['contact']) ?></p>
                <?php endif; ?>
                <?php if ($d['status'] !== 'pending'): ?>
                    <p>
                        <strong>Status:</strong>
                        <span class="<?= $d['status'] === 'approved' ? 'status-active' : 'status-removed' ?>">
                            <?= e($d['status']) ?>
                        </span>
                        | Ditangani: <?= e($d['handled_at'] ?? '-') ?>
                        | Oleh: <?= e($d['handler_email'] ?? '-') ?>
                    </p>
                    <?php if ($d['admin_note']): ?>
                        <p><strong>Catatan admin:</strong> <?= e($d['admin_note']) ?></p>
                    <?php endif; ?>
                <?php else: ?>
                    <form method="post" style="margin-top:.5rem">
                        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                        <input type="hidden" name="dispute_id" value="<?= (int)$d['id'] ?>">
                        <label style="font-weight:normal">Catatan admin (opsional):</label>
                        <textarea name="admin_note" style="min-height:60px;margin-bottom:.5rem"
                                  placeholder="Catatan untuk pelapor atau internal..."></textarea>
                        <button type="submit" name="decision" value="approved"
                                class="btn btn-primary"
                                onclick="return confirm('Setujui &amp; hapus data terkait?')">
                            ✓ Approve
                        </button>
                        <button type="submit" name="decision" value="rejected"
                                class="btn btn-danger" style="margin-left:.5rem"
                                onclick="return confirm('Tolak pengajuan ini?')">
                            ✗ Reject
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
<?php renderFooter(); ?>
