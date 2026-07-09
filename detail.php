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


$profile = fetchProfile($t, $v);

// untuk tampilan publik: hanya status='active'
$network = $identifier !== '' ? getNetwork($identifier, true) : [];

if (empty($network)) {
    $user = currentUser();

    if (!$user) {
        // ── PUBLIK: 404 + ajakan login ──
        http_response_code(404);
        renderHeader('Data tidak ditemukan');
        echo '<div class="container">';
        echo '<h1>Data tidak ditemukan</h1>';
        echo '<p class="meta">Belum ada data untuk <code>' . e($identifier) . '</code>.</p>';
        echo '<p>Punya informasi tentang ini? <a href="/add">Login</a> untuk menambahkan data.</p>';
        echo '<p><a href="/">← Kembali</a></p>';
        echo '</div>';
        renderFooter();
        exit;
    }

    // ── USER LOGIN: 200 + form (type & account_id TERKUNCI dari URL) ──
    $csrf = csrfToken();
    renderHeader('Tambah Data - ' . $identifier);
    ?>
    <?php if ($profile && !empty($profile['avatar'])): ?>
        <div style="display:flex;align-items:center;gap:1rem;margin-bottom:.5rem">
            <img src="<?= e($profile['avatar']) ?>" alt="avatar"
                 style="width:64px;height:64px;border-radius:50%;object-fit:cover">
            <div>
                <h1 style="margin:0"><?= e($profile['name'] ?: ($name ?: 'Tanpa nama')) ?>
                    <?php if ($tagLabel): ?><span class="tag-label"><?= e($tagLabel) ?></span><?php endif; ?>
                </h1>
                <p class="meta" style="margin:.25rem 0 0">
                    <?= e($profile['username']) ?> <?= e($profile['created']) ?>
                    <?php if (!empty($profile['profileurl'])): ?>
                        · <a href="<?= e($profile['profileurl']) ?>" target="_blank" rel="noopener">buka profil</a>
                    <?php endif; ?>
                </p>
            </div>
        </div>
    <?php else: ?>
        <h1><?= e(($profile['name'] ?? '') ?: ($name ?: 'Tanpa nama')) ?>
            <?php if ($tagLabel): ?><span class="tag-label"><?= e($tagLabel) ?></span><?php endif; ?>
        </h1>
    <?php endif; ?>
    <div class="container">
      <div class="alert info">
        Data untuk <code><?= e($identifier) ?></code> belum ada.
        Silakan lengkapi di bawah — Anda akan tercatat sebagai kontributor.
      </div>
      <div class="title">Tambah Data Baru</div>

      <form action="/add" method="post">
        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">

        <!-- Type: TERKUNCI dari URL -->
        <div class="row">
          <div class="col-25"><label>Type</label></div>
          <div class="col-75">
            <input type="text" readonly value="<?= e(bankName($t)) ?>"
                   style="background:#f0f0f0;cursor:not-allowed">
            <input type="hidden" name="data_type" value="<?= e($t) ?>">
          </div>
        </div>

        <!-- ID/Number: TERKUNCI dari URL -->
        <div class="row">
          <div class="col-25"><label>ID/Number</label></div>
          <div class="col-75">
            <input type="text" name="account_id" value="<?= e($v) ?>"
                   readonly style="background:#f0f0f0;cursor:not-allowed">
          </div>
        </div>

        <!-- Name, Tag, URL: bebas diisi -->
        <div class="row">
          <div class="col-25"><label for="name">Name</label></div>
          <div class="col-75"><input type="text" id="name" name="name" placeholder="Account Name"></div>
        </div>

        <div class="row">
          <div class="col-25"><label for="tag">Tag</label></div>
          <div class="col-75"><input type="text" id="tag" name="tag" placeholder="Tag Name"></div>
        </div>

        <div class="row">
          <div class="col-25"><label for="url">Link (Optional)</label></div>
          <div class="col-75">
            <input type="text" pattern="https?://.+" id="url" name="url"
                   placeholder="https:// link bukti atau kronologi">
          </div>
        </div>

        <div class="row">
          <div class="col-25"></div>
          <div class="col-75">
            <label style="font-weight:normal;display:flex;gap:.5rem;align-items:flex-start">
              <input type="checkbox" name="agree" value="1" required style="width:auto;margin-top:.25rem">
              <span>Saya menyatakan data ini benar dan saya bertanggung jawab penuh secara hukum
                    atas informasi yang saya input.</span>
            </label>
          </div>
        </div>

        <div class="row">
          <div class="col-25"></div>
          <div class="col-75"><button type="submit" class="btn btn-primary">Submit</button></div>
        </div>
      </form>
    </div>
    <?php
    renderFooter();
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
$msg     = $_GET['msg'] ?? '';

renderHeader('Detail - ' . ($name ?: $identifier));
?>
    <?php if ($profile && !empty($profile['avatar'])): ?>
        <div style="display:flex;align-items:center;gap:1rem;margin-bottom:.5rem">
            <img src="<?= e($profile['avatar']) ?>" alt="avatar"
                 style="width:64px;height:64px;border-radius:50%;object-fit:cover">
            <div>
                <h1 style="margin:0"><?= e($profile['name'] ?: ($name ?: 'Tanpa nama')) ?>
                    <?php if ($tagLabel): ?><span class="tag-label"><?= e($tagLabel) ?></span><?php endif; ?>
                </h1>
                <p class="meta" style="margin:.25rem 0 0">
                    <?= e($profile['username']) ?> <?= e($profile['created']) ?>
                    <?php if (!empty($profile['profileurl'])): ?>
                        · <a href="<?= e($profile['profileurl']) ?>" target="_blank" rel="noopener">buka profil</a>
                    <?php endif; ?>
                </p>
            </div>
        </div>
    <?php else: ?>
        <div>
			<h1 style="margin:0"><?= e($profile['name'] ?: ($name ?: 'Tanpa nama')) ?>
				<?php if ($tagLabel): ?><span class="tag-label"><?= e($tagLabel) ?></span><?php endif; ?>
			</h1>
			<p class="meta" style="margin:.25rem 0 0">
				<?= e($profile['username']) ?> <?= e($profile['created']) ?>
				<?php if (!empty($profile['profileurl'])): ?>
					· <a href="<?= e($profile['profileurl']) ?>" target="_blank" rel="noopener">buka profil</a>
				<?php endif; ?>
			</p>
		</div>
    <?php endif; ?>
    <p class="meta"><?= count($network) ?> identitas terhubung - root: <code><?= e($root) ?></code></p>

    <?php if ($msg === 'added'): ?>
        <div class="alert ok">Data berhasil ditambahkan dan terhubung ke jaringan ini.</div>
    <?php elseif ($msg === 'duplicate'): ?>
        <div class="alert info">Data sudah pernah ada (duplikat), tapi tetap terhubung ke jaringan ini.</div>
    <?php elseif ($msg === 'deleted'): ?>
        <div class="alert ok">Data berhasil dihapus.</div>
    <?php elseif ($msg === 'forbidden'): ?>
        <div class="alert err">Tidak diizinkan menghapus data tersebut.</div>
    <?php endif; ?>

    <p>
        <a class="btn" href="/sanggah?identifier=<?= urlencode($identifier) ?>">
            📝 Ajukan Sanggah untuk Data Ini
        </a>
    </p>

    <div class="section">
        <h2>Semua Identitas Terhubung</h2>
        <table>
            <tr>
                <th>Type Data</th><th>ID Akun</th><th>Nama</th>
                <th>Waktu</th><th>Aksi</th>
            </tr>
            <?php foreach ($network as $n):
                [$type, $acc] = parseIdentifier($n['identifier']);
                $isRoot       = ($n['identifier'] === $root);
                $canDelete    = $user && ($isAdmin || (int)$n['user_id'] === (int)$user['id']);
            ?>
                <tr>
                    <td><?= e(bankName($type)) ?></td>
                    <td>
                        <a href="/detail/<?= e($type) ?>/<?= e($acc) ?>" rel="noopener noreferrer"><strong><?= e($acc) ?></strong></a>
                        <?php if ($isRoot): ?><span class="root"> ★ root</span><?php endif; ?>
                    </td>
                    <td><?= e($n['name']) ?></td>
                    <td class="meta"><?= e($n['created_at']) ?></td>
                    <td>
                        <?php if ($canDelete): ?>
                            <form method="post" action="/delete"
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
            if (!empty($n['url'])) $links[$n['url']] = $n['tag']?$n['tag']:$n['identifier'];
        }
        ?>
        <?php if (empty($links)): ?>
            <p class="meta">Belum ada link bukti.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($links as $url => $fromIdent): ?>
                    <li>
                        <a href="<?= e($url) ?>" target="_blank" rel="noopener noreferrer"><span class="meta"><?= e($fromIdent) ?></span></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <?php /* ── INLINE FORM: Tambah Akun Terhubung (hanya user login) ── */ ?>
    <?php if ($user): ?>
    <div class="section">
        <h2>➕ Tambah Akun Terhubung</h2>
        <p class="meta">Data yang Anda tambahkan di sini otomatis terhubung ke jaringan ini
            (root: <code><?= e($root) ?></code>).</p>

        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

        <form action="/add" method="post">
            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
            <input type="hidden" name="link_to" value="<?= e($identifier) ?>"><!-- auto-nyambung -->

            <div class="row">
              <div class="col-25"><label for="type">Type</label></div>
              <div class="col-75">
                <select id="type" name="data_type" style="width:100%;">
                  <option value="">-- pilih bank / e-wallet --</option>
                  <?php foreach (loadBanks() as $key => $val): ?>
                      <option value="<?= e($key) ?>"><?= e($val) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <div class="row">
              <div class="col-25"><label for="identifier">ID/Number</label></div>
              <div class="col-75">
                <input type="text" id="identifier" name="account_id" placeholder="ID Account">
              </div>
            </div>

            <div class="row">
              <div class="col-25"><label for="name">Name</label></div>
              <div class="col-75">
                <input type="text" id="name" name="name" placeholder="Account Name">
              </div>
            </div>

            <div class="row">
              <div class="col-25"><label for="tag">Tag</label></div>
              <div class="col-75">
                <input type="text" id="tag" name="tag" placeholder="Tag Name">
              </div>
            </div>

            <div class="row">
              <div class="col-25"><label for="url">Link (Optional)</label></div>
              <div class="col-75">
                <input type="text" pattern="https?://.+" id="url" name="url"
                       placeholder="https:// link bukti atau kronologi">
              </div>
            </div>

            <div class="row">
              <div class="col-25"></div>
              <div class="col-75">
                <label style="font-weight:normal;display:flex;gap:.5rem;align-items:flex-start">
                  <input type="checkbox" name="agree" value="1" required style="width:auto;margin-top:.25rem">
                  <span>Saya menyatakan data ini benar dan saya bertanggung jawab penuh secara hukum
                        atas informasi yang saya input.</span>
                </label>
              </div>
            </div>

            <div class="row">
              <div class="col-25"></div>
              <div class="col-75">
                <button type="submit" class="btn btn-primary">Tambah &amp; Hubungkan</button>
              </div>
            </div>
        </form>

        <script>
        $(function () {
          $('#type').select2({
            placeholder: '-- pilih bank / e-wallet --',
            width: '100%'
          });
        });
        </script>
    </div>
    <?php endif; ?>
<?php renderFooter(); ?>