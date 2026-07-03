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

- **Input data**: type data, ID akun, nama, tag, link bukti, + hubungkan ke identitas lain.
- **Pencarian**: cari via identifier / nama / tag. Hasil dikelompokkan per-jaringan.
- **Normalisasi nomor telepon**: `08xxx`, `628xxx`, `+628xxx`, `8xxx` -> semua dianggap identitas yang **sama** (disimpan kanonik `628xxx`).
- **Anti-duplikat (ketat)**: laporan dianggap duplikat kalau `identifier + name + tag + url` **persis sama** (fingerprint SHA-256, dijaga `UNIQUE(hashid)` di DB).
- **Merge network**: dua jaringan yang ternyata orang sama bisa digabung.
- **JSON API**: endpoint pencarian.

## Struktur

```
gigne-contact/
|- config/database.php      # koneksi DB (sesuaikan kredensial)
|- includes/functions.php   # logika utama
|- data/banks.json          # daftar type data / bank / e-wallet
|- schema.sql               # struktur tabel
|- index.php                # halaman cari + daftar
|- add.php                  # form input data
|- detail.php               # detail 1 jaringan + bukti
|- api.php                  # endpoint JSON (api.php?q=...)
```

## Cara pakai

1. Buat database & tabel: jalankan `schema.sql` di MySQL/MariaDB.
2. Sesuaikan kredensial di `config/database.php`.
3. Pastikan `data/banks.json` ada (daftar type data).
4. Jalankan di server PHP (mis. `php -S localhost:8000`), lalu:
   - `index.php` -> cari data
   - `add.php` -> input data
   - `api.php?q=6281267991717` -> API pencarian

## Catatan penting sebelum go-live

- **Authentication**: data sensitif - tambahkan login sebelum dipublikasikan. Jangan biarkan siapa pun bisa CRUD bebas.
- **Verifikasi laporan**: sistem seperti ini rawan disalahgunakan untuk fitnah/doxxing. Perlu moderasi sebelum data dipublikasikan.
- **Legal (UU PDP)**: NIK, no rekening, no HP adalah data pribadi yang dilindungi UU. Pertimbangkan disclaimer + mekanisme sanggah/hapus data.

> Proyek ini masih barebone (belum ada auth). Gunakan dengan bertanggung jawab.
