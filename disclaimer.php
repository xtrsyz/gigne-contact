<?php
/**
 * disclaimer.php — halaman disclaimer lengkap.
 */

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/footer.php';

startSession();

renderHeader('Disclaimer & Kebijakan');
?>
    <h1>Disclaimer &amp; Kebijakan</h1>

    <div class="section">
        <h2>1. Tujuan Sistem</h2>
        <p>
            Sistem ini dibuat <strong>semata-mata untuk tujuan anti-penipuan</strong> — membantu komunitas
            mengenali pola dan identitas yang pernah dilaporkan sebagai pelaku penipuan online.
            Bukan untuk fitnah, doxxing, atau tujuan merugikan pihak lain secara sewenang-wenang.
        </p>
    </div>

    <div class="section">
        <h2>2. Data Belum Tentu Terverifikasi</h2>
        <p>
            Data yang tersedia di sini bersumber dari <strong>laporan komunitas</strong> dan
            <strong>belum tentu diverifikasi secara independen</strong>. Kami tidak menjamin
            keakuratan, kelengkapan, atau kebenaran setiap data.
            Gunakan sebagai <strong>referensi awal</strong>, bukan keputusan akhir.
        </p>
        <p>
            Pengelola sistem <strong>tidak bertanggung jawab</strong> atas kerugian yang timbul akibat
            penggunaan informasi dari sistem ini.
        </p>
    </div>

    <div class="section">
        <h2>3. Tanggung Jawab Kontributor (Penginput Data)</h2>
        <p>
            Setiap orang yang menginput data ke sistem ini <strong>bertanggung jawab penuh</strong>
            atas kebenaran data yang dimasukkan. Dengan menginput data, kontributor menyatakan bahwa:
        </p>
        <ul>
            <li>Data yang dimasukkan adalah benar dan dapat dipertanggungjawabkan.</li>
            <li>Kontributor memiliki dasar yang kuat (bukti) atas laporan tersebut.</li>
            <li>Kontributor memahami konsekuensi hukum bila memasukkan data palsu atau menyesatkan,
                termasuk namun tidak terbatas pada pasal pencemaran nama baik, fitnah, dan
                <strong>Undang-Undang No. 27 Tahun 2022 tentang Perlindungan Data Pribadi (UU PDP)</strong>.</li>
        </ul>
    </div>

    <div class="section">
        <h2>4. Data Pribadi &amp; UU PDP</h2>
        <p>
            Nomor telepon, nomor rekening, dan identitas digital lainnya yang tersimpan di sistem ini
            merupakan data pribadi yang dilindungi oleh Undang-Undang.
            Sistem ini hanya menyimpan data yang dilaporkan terkait kegiatan penipuan,
            sesuai kepentingan publik yang sah.
        </p>
        <p>
            Jika Anda merasa data Anda tercantum secara keliru, Anda berhak mengajukan
            koreksi atau penghapusan melalui mekanisme sanggah.
        </p>
    </div>

    <div class="section">
        <h2>5. Cara Mengajukan Sanggah / Hapus Data</h2>
        <p>
            Jika Anda merasa ada data yang tidak akurat, keliru, atau menyangkut identitas Anda
            dan ingin dihapus:
        </p>
        <ol>
            <li>Kunjungi halaman <a href="/sanggah.php"><strong>Ajukan Sanggah</strong></a>.</li>
            <li>Isi alasan sanggah dengan jelas (sertakan bukti jika ada).</li>
            <li>Pengajuan akan ditinjau oleh admin dalam waktu paling lama <strong>7 hari kerja</strong>.</li>
            <li>Jika disetujui, data akan dihapus dari tampilan publik.</li>
        </ol>
        <p>
            Pengajuan sanggah yang tidak berdasar atau bersifat fiktif dapat berakibat hukum.
        </p>
    </div>

    <div class="section">
        <h2>6. Login &amp; Kepemilikan Data</h2>
        <p>
            Untuk menginput data, pengguna wajib login menggunakan akun Google.
            Setiap data yang diinput tercatat atas nama pengguna yang bersangkutan.
            Pengguna hanya dapat menghapus data yang diinput oleh mereka sendiri.
            Admin dapat menghapus semua data.
        </p>
    </div>

    <p style="margin-top:1.5rem">
        <a href="/index.php">&larr; Kembali ke beranda</a>
        &nbsp;|&nbsp;
        <a href="/sanggah.php">Ajukan Sanggah</a>
    </p>
<?php renderFooter(); ?>
