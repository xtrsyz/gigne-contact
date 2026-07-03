# gigne-contact

Tracking Contact Database - sistem sederhana buat mencatat & melacak data kontak penipuan (no telp, rekening bank, akun Steam/Discord, dll) yang **saling terhubung**, sekaligus bisa dipakai buat pencarian.

## Konsep inti

Satu penipu biasanya punya banyak identitas (rekening BCA, Discord ID, no HP, Steam ID). Sistem ini menghubungkan semua identitas itu jadi satu **jaringan (network)** lewat kolom `id_link` yang selalu menunjuk ke **root** yang sama.

```
discord:542720825933955074   <- root (menunjuk diri sendiri)
phone:6281267991717          -> id_link = discord:542720825933955074
bri:537401023296535          -> id_link = discord:542720825933955074
```

Cari salah satu identitas -> semua identitas dalam jaringan yang sama ikut muncul.

## Fitur

- **Pencarian publik**: cari via identifier / nama / tag. Hasil dikelompokkan per-jaringan. Tidak butuh login.
- **Normalisasi nomor telepon**: `08xxx`, `628xxx`, `+628xxx`, `8xxx` -> semua dianggap identitas yang **sama** (disimpan kanonik `628xxx`).
- **Anti-duplikat (ketat)**: laporan dianggap duplikat kalau `identifier + name + tag + url` **persis sama** (fingerprint SHA-256, dijaga `UNIQUE(hashid)` di DB).
- **Merge network**: dua jaringan yang ternyata orang sama bisa digabung.
- **JSON API**: endpoint pencarian.
- **Login Google**: input data wajib login via Google OAuth 2.0.
- **Kepemilikan data**: setiap data tercatat siapa yang menginput; user hanya bisa hapus miliknya sendiri; admin bisa semua.
- **Soft-delete**: data yang dihapus tidak muncul di publik tapi tetap ada di DB.
- **Pengajuan sanggah**: siapa pun (tanpa login) bisa mengajukan sanggah/hapus data lewat `sanggah.php`.
- **Panel admin**: admin (email di `APP_ADMINS`) bisa approve/reject pengajuan sanggah.

## Struktur

```
gigne-contact/
|- config/
|  |- database.php           # koneksi DB (sesuaikan kredensial)
|  |- auth.php               # konfigurasi Google OAuth (JANGAN commit! ada di .gitignore)
|  |- auth.example.php       # template auth.php yang aman untuk di-commit
|- includes/
|  |- functions.php          # logika utama
|  |- auth.php               # helper autentikasi (session, CSRF, guards)
|  |- header.php             # partial header bersama (nav + banner)
|  |- footer.php             # partial footer bersama
|- auth/
|  |- login.php              # redirect ke Google consent screen
|  |- callback.php           # handler OAuth callback dari Google
|  |- logout.php             # hancurkan session
|- data/banks.json           # daftar type data / bank / e-wallet
|- schema.sql                # struktur tabel (users, tag, disputes)
|- index.php                 # halaman cari + daftar (publik)
|- add.php                   # form input data (wajib login)
|- detail.php                # detail 1 jaringan + bukti (publik)
|- delete.php                # handler soft-delete (wajib login)
|- mydata.php                # daftar data milik sendiri (wajib login)
|- sanggah.php               # form pengajuan sanggah/hapus data (publik)
|- admin.php                 # panel admin (wajib admin)
|- disclaimer.php            # halaman disclaimer lengkap (publik)
|- api.php                   # endpoint JSON pencarian (publik)
```

## Setup

### 1. Database

Buat database & tabel dengan menjalankan `schema.sql`:

```bash
mysql -u root -p < schema.sql
```

**Untuk database yang sudah ada** (sudah punya tabel `tag` versi lama): jalankan blok `ALTER TABLE` di bagian bawah `schema.sql`. Cek dulu dengan `DESCRIBE tag` apakah kolom `user_id`, `status`, `deleted_at`, `deleted_by` sudah ada, baru jalankan baris ALTER yang belum ada.

### 2. Konfigurasi database

Sesuaikan kredensial di `config/database.php`.

> ⚠️ `config/database.php` sudah terlanjur ada di repo. Untuk produksi, pastikan file ini tidak berisi kredensial asli di repo publik.

### 3. Login Google (OAuth 2.0)

#### Langkah membuat OAuth Client ID

1. Buka [Google Cloud Console](https://console.cloud.google.com/)
2. Buat project baru (atau gunakan yang sudah ada)
3. **APIs & Services → OAuth consent screen** → pilih *External*, isi info aplikasi (nama, email, dll)
4. **APIs & Services → Credentials → Create Credentials → OAuth client ID**
   - Application type: **Web application**
   - Authorized redirect URIs: tambahkan URI callback Anda, contoh:
     - Development: `http://localhost:8000/auth/callback.php`
     - Produksi: `https://yourdomain.com/auth/callback.php`
5. Salin **Client ID** dan **Client Secret**

#### Mengisi config/auth.php

```bash
cp config/auth.example.php config/auth.php
```

Edit `config/auth.php` dan isi:

```php
define('GOOGLE_CLIENT_ID',     'xxxx.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', 'GOCSPX-xxxxx');
define('GOOGLE_REDIRECT_URI',  'http://localhost:8000/auth/callback.php');
define('APP_ADMINS', [
    'email-admin-anda@gmail.com',
]);
```

> ⚠️ `config/auth.php` sudah masuk `.gitignore` — jangan commit file ini ke repo publik.

### 4. Jalankan server

```bash
php -S localhost:8000
```

Lalu buka:
- `index.php` → cari data (publik)
- `add.php` → input data (wajib login)
- `api.php?q=6281267991717` → API pencarian (publik)

## Alur penggunaan

### Publik (tanpa login)
- Buka `index.php` untuk mencari dan melihat data.
- Buka `detail.php?id=...` untuk melihat detail jaringan.
- Buka `sanggah.php` untuk mengajukan sanggah/hapus data.

### User login
- Login via `auth/login.php` menggunakan akun Google.
- Buka `add.php` untuk menginput data (wajib centang disclaimer tanggung jawab).
- Buka `mydata.php` untuk melihat dan menghapus data milik sendiri.

### Admin (email terdaftar di `APP_ADMINS`)
- Akses semua fitur user.
- Buka `admin.php` untuk meninjau pengajuan sanggah.
- Approve → data terkait otomatis di-soft-delete.
- Reject → pengajuan ditolak.

## Keamanan

- Semua form yang mengubah data menggunakan **CSRF token**.
- Semua query menggunakan **prepared statement** (tidak ada SQL injection).
- Semua output di-escape via `e()` (tidak ada XSS).
- OAuth menggunakan **state parameter** anti-CSRF.
- Email yang belum diverifikasi Google tidak bisa login.
- Secret tidak boleh ada di repo. Gunakan `config/auth.php` (di-gitignore).

## Catatan penting

- **Data yang di-soft-delete** (`status != 'active'`) tidak muncul di pencarian publik, list, detail, maupun `api.php`. Barisnya tetap ada di DB untuk keperluan audit.
- **`findRoot()`** internal tidak difilter status supaya rantai jaringan (id_link) tetap utuh meski ada anggota yang di-soft-delete.
- **Pencarian nomor HP**: `08xxx`, `628xxx`, `+628xxx`, `8xxx` → semua menemukan data yang sama.
