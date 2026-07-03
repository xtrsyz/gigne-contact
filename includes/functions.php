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
 * NORMALISASI (input -> bentuk kanonik)
 * ========================================================================= */

/** Normalisasi nomor telepon Indonesia -> kanonik 62xxxxxxxxxx */
function normalizePhone(string $raw): string
{
    $digits = preg_replace('/\\D+/', '', $raw);   // buang non-digit
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
        //     return preg_replace('/\\D+/', '', $accountId);

        default:
            return $accountId;
    }
}

/** Normalisasi teks umum: trim + rapikan spasi + lowercase */
function normalizeText(string $s): string
{
    $s = trim($s);
    $s = preg_replace('/\\s+/u', ' ', $s);
    return mb_strtolower($s, 'UTF-8');
}

/** Normalisasi URL: buang spasi, samain protokol ke https, buang trailing slash */
function normalizeUrl(string $url): string
{
    $url = trim($url);
    if ($url === '') return '';
    $url = preg_replace('/\\s+/', '', $url);
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

/** Ambil semua anggota network - Opsi B cukup 1 query */
function getNetwork(string $identifier): array
{
    $root = findRoot($identifier);
    $stmt = db()->prepare(
        "SELECT * FROM tag
         WHERE identifier = :root OR id_link = :root
         ORDER BY id ASC"
    );
    $stmt->execute([':root' => $root]);
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

    // insert; kalau nabrak UNIQUE(hashid) -> duplikat, di-skip (no-op)
    $stmt = db()->prepare(
        "INSERT INTO tag (hashid, identifier, id_link, name, tag, url, ip)
         VALUES (?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE id = id"
    );
    $stmt->execute([
        $hashid, $identifier, $linkTo,
        $input['name'] ?? null,
        $input['tag']  ?? null,
        $input['url']  ?? null,
        $_SERVER['REMOTE_ADDR'] ?? null,
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
 * SEARCH (dengan varian phone 08/628)
 * ========================================================================= */

function searchTags(string $keyword): array
{
    $pdo     = db();
    $keyword = trim($keyword);
    $like    = '%' . $keyword . '%';

    $conds  = ["identifier LIKE :kw", "name LIKE :kw", "tag LIKE :kw"];
    $params = [':kw' => $like];

    foreach (phoneSearchVariants($keyword) as $i => $variant) {
        $key          = ":pv$i";
        $conds[]      = "identifier LIKE $key";
        $params[$key] = '%' . $variant . '%';
    }

    $sql  = "SELECT identifier FROM tag WHERE " . implode(' OR ', $conds) . " ORDER BY id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return groupByNetwork($stmt->fetchAll(PDO::FETCH_COLUMN));
}

/** Varian angka buat pencarian phone: 628xxx / 8xxx / 08xxx */
function phoneSearchVariants(string $keyword): array
{
    $digits = preg_replace('/\\D+/', '', $keyword);
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

function listRecent(int $limit = 30): array
{
    $stmt = db()->prepare("SELECT identifier FROM tag ORDER BY id DESC LIMIT ?");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return groupByNetwork($stmt->fetchAll(PDO::FETCH_COLUMN));
}

function groupByNetwork(array $identifiers): array
{
    $networks  = [];
    $seenRoots = [];
    foreach ($identifiers as $ident) {
        $root = findRoot($ident);
        if (isset($seenRoots[$root])) continue;
        $seenRoots[$root] = true;
        $networks[] = getNetwork($ident);
    }
    return $networks;
}
