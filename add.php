<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/footer.php';
require_once __DIR__ . '/includes/auth.php';

startSession();
requireLogin(); // wajib login

$user  = currentUser();
$error = null;

// prefill "Akun Terhubung" dari detail.php (?link_to=phone:628...)
$preLink = trim($_GET['link_to'] ?? '');
[$idty, $idn] = $preLink !== '' ? parseIdentifier($preLink) : ['', ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verifyCsrf();

        // disclaimer wajib
        if (empty($_POST['agree'])) {
            throw new RuntimeException('Anda harus menyetujui pernyataan tanggung jawab sebelum mengirim data.');
        }

        $dataType  = trim($_POST['data_type'] ?? '');
        $accountId = trim($_POST['account_id'] ?? '');
        if ($dataType === '' || $accountId === '') {
            throw new RuntimeException('Type dan ID/Number wajib diisi.');
        }

        $res = saveTag([
            'data_type'  => $dataType,                 // form field "type"
            'account_id' => $accountId,                // form field "identifier" (ID/Number)
            'name'       => trim($_POST['name'] ?? '') ?: null,
            'tag'        => trim($_POST['tag']  ?? '') ?: null,
            'url'        => trim($_POST['url']  ?? '') ?: null,
            'link_to'    => trim($_POST['link_to'] ?? '') ?: null, // form field "id_link"
            'user_id'    => (int)$user['id'],          // ownership
        ]);

        // sukses -> redirect ke detail (clean URL)
        $flag = $res['is_duplicate'] ? 'duplicate' : 'added';
        header('Location: /' . detailUrl($res['identifier']) . '?msg=' . $flag);
        exit;
    } catch (Throwable $ex) {
        $error = $ex->getMessage();
    }
}

$banks   = loadBanks(); // lokal data/banks.json
$ketemu  = false;
$csrf    = csrfToken();

renderHeader('Tambah Data');
?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<div class="container">
  <div class="title">Tambah Data</div>

  <?php if ($error): ?>
    <div class="alert err"><?= e($error) ?></div>
  <?php endif; ?>

  <form action="/add" method="post">
    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">

<?php if ($idty && $idn): ?>
    <div class="row">
      <div class="col-25">
        <label for="link_to">Akun Terhubung</label>
      </div>
      <div class="col-75">
        <input readonly type="text" id="link_to" name="link_to"
               value="<?= e("$idty:$idn") ?>">
      </div>
    </div>
<?php endif; ?>

    <div class="row">
      <div class="col-25">
        <label for="type">Type</label>
      </div>
      <div class="col-75">
        <select id="type" name="data_type" style="width:100%;">
          <option value="">-- pilih bank / e-wallet --</option>
<?php foreach ($banks as $key => $val):
        $selected = ($key == $idty) ? 'selected' : '';
        if ($key == $idty) $ketemu = true; ?>
          <option <?= $selected ?> value="<?= e($key) ?>"><?= e($val) ?></option>
<?php endforeach; ?>
<?php if ($idty && !$ketemu): ?>
          <option selected value="<?= e($idty) ?>"><?= e(ucfirst($idty)) ?></option>
<?php endif; ?>
        </select>
      </div>
    </div>

    <div class="row">
      <div class="col-25">
        <label for="identifier">ID/Number</label>
      </div>
      <div class="col-75">
        <input type="text" id="identifier" name="account_id"
               placeholder="ID Account" value="<?= e($idn) ?>">
      </div>
    </div>

    <div class="row">
      <div class="col-25">
        <label for="name">Name</label>
      </div>
      <div class="col-75">
        <input type="text" id="name" name="name" placeholder="Account Name">
      </div>
    </div>

    <div class="row">
      <div class="col-25">
        <label for="tag">Tag</label>
      </div>
      <div class="col-75">
        <input type="text" id="tag" name="tag" placeholder="Tag Name">
      </div>
    </div>

    <div class="row">
      <div class="col-25">
        <label for="url">Link (Optional)</label>
      </div>
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
      <input type="submit" value="Submit">
    </div>
  </form>
</div>

<script>
$(function () {
  $('#type').select2({
    placeholder: '-- pilih bank / e-wallet --',
    width: '100%'
  });
});
</script>
<?php renderFooter(); ?>