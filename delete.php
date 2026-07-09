<?php
/**
 * delete.php — handler soft-delete tag.
 * Hanya menerima POST. User hanya bisa hapus miliknya; admin bisa semua.
 */

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

startSession();
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /');
    exit;
}

try {
    verifyCsrf();
} catch (RuntimeException $e) {
    http_response_code(403);
    echo '<!DOCTYPE html><html><body><p>' . e($e->getMessage()) . '</p>';
    echo '<a href="/">Kembali</a></body></html>';
    exit;
}

$tagId    = isset($_POST['tag_id']) ? (int)$_POST['tag_id'] : 0;
$returnId = trim($_POST['return_id'] ?? '');
$user     = currentUser();

if ($tagId <= 0) {
    header('Location: /?msg=invalid');
    exit;
}

$ok = softDeleteTag($tagId, (int)$user['id'], isAdmin());

if ($returnId !== '') {
    // $param = urlencode($returnId);
    $msg   = $ok ? 'deleted' : 'forbidden';
    header("Location: /detail/{$returnId}?msg={$msg}");
} else {
    $msg = $ok ? 'deleted' : 'forbidden';
    header("Location: /mydata?msg={$msg}");
}
exit;
