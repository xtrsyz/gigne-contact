<?php
require_once __DIR__ . '/../config/database.php';

/* =========================================================================
 * LOADER TYPE DATA (banks.json)
 * ========================================================================= */

function loadBanks(): array
{
    static $banks = null;
    if ($banks === null) {
        $json  = file_get_contents(__DIR__ . '/../data/banks.json');
        $data  = json_decode($json, true);
        $banks = [];
        foreach ($data['bank'] as $b) {
            $banks[$b['id']] = $b['nama_bank'];
        }
    }
    return $banks;
}

function bankName(string $id): string
{
    return loadBanks()[$id] ?? $id;
}

function e(?string $s): string
{
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

/* =========================================================================
 * URL HELPERS
 * ========================================================================= */

/**
 * Bikin URL detail yang cantik (Pendekatan A): detail/<type>/<value>
 * Contoh: phone:6281267991717 -> detail/phone/6281267991717
 * Di-handle balik oleh .htaccess menjadi detail.php?id=<type>:<value>
 */
function detailUrl(string $identifier): string
{
    [$type, $value] = parseIdentifier($identifier);
    // rawurlencode tiap segmen (jaga-jaga kalau ada karakter aneh),
    // slash pemisah tetap literal
    return 'detail/' . rawurlencode($type) . '/' . rawurlencode($value);
}

/* =========================================================================
 * NORMALISASI (input -> bentuk kanonik)
 * ========================================================================= */

/** Normalisasi nomor telepon Indonesia -> kanonik 62xxxxxxxxxx */
function normalizePhone(string $raw): string
{
    $digits = preg_replace('/\D+/', '', $raw);   // buang non-digit
    if ($digits === '') return '';

    if (str_starts_with($digits, '0'))  return '62' . substr($digits, 1); // 08xx -> 628xx
    if (str_starts_with($digits, '62')) return $digits;                    // 628xx -> tetap
    if (str_starts_with($digits, '8'))  return '62' . $digits;             // 8xx  -> 628xx

    return $digits; // fallback (mis. nomor luar)
}

/** Normalisasi account_id sesuai type data-nya */
function normalizeAccountId(string $dataType, string $accountId): string
{
    $accountId = trim($accountId);

    switch ($dataType) {
        case 'phone':
            return normalizePhone($accountId);

        // kalau nanti mau rekening angka-only, tinggal buka ini:
        // case 'bri': case 'bca': case 'mandiri':
        //     return preg_replace('/\D+/', '', $accountId);

        default:
            return $accountId;
    }
}

/** Normalisasi teks umum: trim + rapikan spasi + lowercase */
function normalizeText(string $s): string
{
    $s = trim($s);
    $s = preg_replace('/\s+/u', ' ', $s);
    return mb_strtolower($s, 'UTF-8');
}

/** Normalisasi URL: buang spasi, samain protokol ke https, buang trailing slash */
function normalizeUrl(string $url): string
{
    $url = trim($url);
    if ($url === '') return '';
    $url = preg_replace('/\s+/', '', $url);
    $url = preg_replace('#^https?://#i', 'https://', $url); // http & https dianggap sama
    return rtrim($url, '/');
}

/* =========================================================================
 * IDENTIFIER HELPERS
 * ========================================================================= */

function makeIdentifier(string $dataType, string $accountId): string
{
    $dataType = trim($dataType);
    return $dataType . ':' . normalizeAccountId($dataType, $accountId);
}

function parseIdentifier(string $identifier): array
{
    $pos = strpos($identifier, ':');
    if ($pos === false) return [$identifier, ''];
    return [substr($identifier, 0, $pos), substr($identifier, $pos + 1)];
}

function findByIdentifier(string $identifier): ?array
{
    $stmt = db()->prepare("SELECT * FROM tag WHERE identifier = ? LIMIT 1");
    $stmt->execute([$identifier]);
    return $stmt->fetch() ?: null;
}

/* =========================================================================
 * CONTENT FINGERPRINT (dedup KETAT)
 * ------------------------------------------------------------------------
 * Duplikat = identifier + name + tag + url SEMUA sama (setelah dinormalisasi).
 * id_link TIDAK diikutkan (volatile - berubah pas merge).
 * ========================================================================= */

function contentFingerprint(array $value): string
{
    $parts = [
        normalizeText($value['identifier'] ?? ''),
        normalizeText($value['name'] ?? ''),
        normalizeText($value['tag'] ?? ''),
        normalizeUrl($value['url'] ?? ''),
    ];
    // \x1F = unit separator -> pemisah antar-field, anti collision
    return hash('sha256', implode("\x1F", $parts));
}

/* =========================================================================
 * CORE: ROOT & NETWORK (Opsi B - id_link selalu nunjuk root)
 * ========================================================================= */

/**
 * Telusurin rantai id_link sampai ROOT.
 * TIDAK difilter status supaya rantai jaringan tetap utuh.
 */
function findRoot(string $identifier, int $maxDepth = 50): string
{
    $seen    = [];
    $current = $identifier;

    for ($i = 0; $i < $maxDepth; $i++) {
        if (isset($seen[$current])) break;   // anti loop
        $seen[$current] = true;

        $row = findByIdentifier($current);
        if (!$row) return $current;          // dead-end = root

        $link = $row['id_link'];
        if (empty($link) || $link === $current) return $current; // nunjuk diri = root
        $current = $link;
    }
    return $current;
}

/**
 * Ambil semua anggota network - Opsi B cukup 1 query.
 *
 * @param bool $activeOnly true = hanya tampilkan status='active' (untuk publik)
 *
 * CATATAN: pakai DUA placeholder berbeda (:root1, :root2) untuk nilai yang sama,
 * karena PDO dengan EMULATE_PREPARES=false tidak mengizinkan satu named
 * placeholder dipakai lebih dari sekali (menyebabkan HY093).
 */
function getNetwork(string $identifier, bool $activeOnly = false): array
{
    $root = findRoot($identifier);
    $statusClause = $activeOnly ? " AND status = 'active'" : '';
    $stmt = db()->prepare(
        "SELECT * FROM tag
         WHERE (identifier = :root1 OR id_link = :root2){$statusClause}
         ORDER BY id ASC"
    );
    $stmt->execute([':root1' => $root, ':root2' => $root]);
    return $stmt->fetchAll();
}

/* =========================================================================
 * MERGE dua network
 * ========================================================================= */

function mergeNetworks(string $identA, string $identB): void
{
    $rootA = findRoot($identA);
    $rootB = findRoot($identB);
    if ($rootA === $rootB) return;

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $pdo->prepare(
            "UPDATE tag SET id_link = ? WHERE id_link = ? OR identifier = ?"
        )->execute([$rootA, $rootB, $rootB]);
        $pdo->commit();
    } catch (Throwable $ex) {
        $pdo->rollBack();
        throw $ex;
    }
}

/* =========================================================================
 * SAVE (Opsi B + dedup ketat via UNIQUE(hashid))
 * ========================================================================= */

/**
 * Simpan data.
 * @param array $input Termasuk kunci opsional 'user_id' (int|null).
 */
function saveTag(array $input): array
{
    $dataType   = trim($input['data_type']);
    $identifier = makeIdentifier($dataType, $input['account_id']); // kanonik (phone 08->628)

    $hashid = contentFingerprint([
        'identifier' => $identifier,
        'name'       => $input['name'] ?? '',
        'tag'        => $input['tag'] ?? '',
        'url'        => $input['url'] ?? '',
    ]);

    // resolve target root buat linking (Opsi B)
    $targetRoot = null;
    if (!empty($input['link_to'])) {
        [$lt, $lv]  = parseIdentifier(trim($input['link_to']));
        $targetRoot = findRoot(makeIdentifier($lt, $lv));
    }
    $linkTo = $targetRoot ?? $identifier;

    $userId = isset($input['user_id']) ? (int)$input['user_id'] : null;

    // insert; kalau nabrak UNIQUE(hashid) -> duplikat, di-skip (no-op)
    $stmt = db()->prepare(
        "INSERT INTO tag (hashid, identifier, id_link, name, tag, url, ip, user_id, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')
         ON DUPLICATE KEY UPDATE id = id"
    );
    $stmt->execute([
        $hashid, $identifier, $linkTo,
        $input['name'] ?? null,
        $input['tag']  ?? null,
        $input['url']  ?? null,
        $_SERVER['REMOTE_ADDR'] ?? null,
        $userId,
    ]);

    $isDuplicate = ($stmt->rowCount() === 0);

    // tetap merge network kalau diminta (walaupun laporannya duplikat)
    if ($targetRoot !== null) {
        mergeNetworks($targetRoot, $identifier);
    }

    return [
        'hashid'       => $hashid,
        'is_duplicate' => $isDuplicate,
        'identifier'   => $identifier,
    ];
}

/* =========================================================================
 * SINGLE TAG
 * ========================================================================= */

/** Ambil satu baris tag berdasarkan id */
function getTagById(int $id): ?array
{
    $stmt = db()->prepare("SELECT * FROM tag WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/* =========================================================================
 * SOFT-DELETE
 * ========================================================================= */

/**
 * Soft-delete sebuah tag.
 * Berhasil kalau: $isAdmin = true ATAU baris punya user_id == $byUserId.
 */
function softDeleteTag(int $tagId, int $byUserId, bool $isAdmin): bool
{
    $tag = getTagById($tagId);
    if (!$tag) return false;

    if (!$isAdmin && (int)$tag['user_id'] !== $byUserId) {
        return false; // tidak berhak
    }

    $stmt = db()->prepare(
        "UPDATE tag SET status = 'removed', deleted_at = NOW(), deleted_by = ?
         WHERE id = ?"
    );
    $stmt->execute([$byUserId, $tagId]);
    return $stmt->rowCount() > 0;
}

/* =========================================================================
 * USER TAGS
 * ========================================================================= */

/** Daftar tag milik user tertentu (semua status kecuali removed) */
function listUserTags(int $userId): array
{
    $stmt = db()->prepare(
        "SELECT * FROM tag
         WHERE user_id = ? AND status != 'removed'
         ORDER BY id DESC"
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

/* =========================================================================
 * SEARCH (dengan varian phone 08/628)
 * ========================================================================= */

/**
 * Cari di identifier, name, tag.
 * @param bool $activeOnly true = hanya tampilkan status='active' (untuk publik)
 *
 * CATATAN: pakai placeholder berbeda untuk keyword (:kw1, :kw2, :kw3) karena
 * PDO dengan EMULATE_PREPARES=false tidak mengizinkan satu named placeholder
 * dipakai lebih dari sekali (menyebabkan HY093).
 */
function searchTags(string $keyword, bool $activeOnly = false): array
{
    $pdo     = db();
    $keyword = trim($keyword);
    $like    = '%' . $keyword . '%';

    // :kw1/:kw2/:kw3 = nilai sama, placeholder beda (hindari HY093)
    $conds  = ["identifier LIKE :kw1", "name LIKE :kw2", "tag LIKE :kw3"];
    $params = [':kw1' => $like, ':kw2' => $like, ':kw3' => $like];

    // array_values() penting: pastikan index rapat 0,1,2,... supaya key
    // placeholder (:pv0, :pv1, ...) selalu cocok dengan yang di-bind.
    $variants = array_values(phoneSearchVariants($keyword));
    foreach ($variants as $i => $variant) {
        $key          = ":pv$i";
        $conds[]      = "identifier LIKE $key";
        $params[$key] = '%' . $variant . '%';
    }

    $statusClause = $activeOnly ? " AND status = 'active'" : '';
    $sql  = "SELECT identifier FROM tag WHERE (" . implode(' OR ', $conds) . "){$statusClause} ORDER BY id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return groupByNetwork($stmt->fetchAll(PDO::FETCH_COLUMN), $activeOnly);
}

/** Varian angka buat pencarian phone: 628xxx / 8xxx / 08xxx */
function phoneSearchVariants(string $keyword): array
{
    $digits = preg_replace('/\D+/', '', $keyword);
    if ($digits === '' || strlen($digits) < 4) return []; // kependekan -> skip

    $canonical = normalizePhone($digits);
    $variants  = [$canonical];

    if (str_starts_with($canonical, '62')) {
        $variants[] = substr($canonical, 2);       // 8xxx
        $variants[] = '0' . substr($canonical, 2); // 08xxx
    }
    return array_values(array_unique($variants));
}

/* =========================================================================
 * LIST & GROUPING
 * ========================================================================= */

/**
 * List record terbaru (homepage), dikelompokin per network.
 * @param bool $activeOnly true = hanya tampilkan status='active' (untuk publik)
 */
function listRecent(int $limit = 30, bool $activeOnly = false): array
{
    $statusClause = $activeOnly ? " WHERE status = 'active'" : '';
    $stmt = db()->prepare("SELECT identifier FROM tag{$statusClause} ORDER BY id DESC LIMIT ?");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return groupByNetwork($stmt->fetchAll(PDO::FETCH_COLUMN), $activeOnly);
}

/**
 * Dari daftar identifier -> kelompokin jadi array-of-network, tanpa duplikat network.
 * @param bool $activeOnly true = hanya ambil anggota status='active'
 */
function groupByNetwork(array $identifiers, bool $activeOnly = false): array
{
    $networks  = [];
    $seenRoots = [];
    foreach ($identifiers as $ident) {
        $root = findRoot($ident);
        if (isset($seenRoots[$root])) continue;
        $seenRoots[$root] = true;
        $net = getNetwork($ident, $activeOnly);
        if (!empty($net)) {
            $networks[] = $net;
        }
    }
    return $networks;
}

/* =========================================================================
 * DISPUTES (pengajuan sanggah/hapus data dari publik)
 * ========================================================================= */

/**
 * Simpan pengajuan sanggah baru (tanpa login).
 * @return int ID dispute yang baru dibuat
 */
function createDispute(array $input): int
{
    $tagId      = isset($input['tag_id']) && $input['tag_id'] !== '' ? (int)$input['tag_id'] : null;
    $identifier = isset($input['identifier']) ? trim($input['identifier']) : null;
    $reason     = trim($input['reason'] ?? '');
    $contact    = trim($input['contact'] ?? '') ?: null;
    $ip         = $_SERVER['REMOTE_ADDR'] ?? null;

    $stmt = db()->prepare(
        "INSERT INTO disputes (tag_id, identifier, reason, contact, ip)
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->execute([$tagId, $identifier, $reason, $contact, $ip]);
    return (int)db()->lastInsertId();
}

/** Daftar dispute berdasarkan status */
function listDisputes(string $status = 'pending'): array
{
    $stmt = db()->prepare(
        "SELECT d.*, t.identifier AS tag_identifier, t.name AS tag_name,
                u.email AS handler_email
         FROM disputes d
         LEFT JOIN tag t ON d.tag_id = t.id
         LEFT JOIN users u ON d.handled_by = u.id
         WHERE d.status = ?
         ORDER BY d.created_at DESC"
    );
    $stmt->execute([$status]);
    return $stmt->fetchAll();
}

/** Ambil satu dispute berdasarkan id */
function getDispute(int $id): ?array
{
    $stmt = db()->prepare("SELECT * FROM disputes WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/**
 * Tangani dispute (approve/reject) oleh admin.
 * Kalau approved dan dispute punya tag_id, otomatis soft-delete data terkait.
 */
function handleDispute(int $id, int $adminUserId, string $decision, ?string $adminNote): bool
{
    if (!in_array($decision, ['approved', 'rejected'], true)) return false;

    $dispute = getDispute($id);
    if (!$dispute) return false;

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $pdo->prepare(
            "UPDATE disputes
             SET status = ?, handled_by = ?, handled_at = NOW(), admin_note = ?
             WHERE id = ?"
        )->execute([$decision, $adminUserId, $adminNote, $id]);

        // kalau approved + ada tag_id -> soft-delete tag terkait
        if ($decision === 'approved' && !empty($dispute['tag_id'])) {
            softDeleteTag((int)$dispute['tag_id'], $adminUserId, true);
        }

        $pdo->commit();
        return true;
    } catch (Throwable $ex) {
        $pdo->rollBack();
        throw $ex;
    }
}
