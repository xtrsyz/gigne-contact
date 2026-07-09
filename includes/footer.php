<?php
/**
 * includes/footer.php
 * Partial footer bersama: link disclaimer, copyright.
 *
 * Cara pakai:
 *   renderFooter();
 */

function renderFooter(): void
{
    ?>
    <footer style="margin-top:3rem; padding-top:1rem; border-top:1px solid #ddd; font-size:.85rem; color:#666; text-align:center;">
        <p>
            <a href="/disclaimer">Disclaimer &amp; Kebijakan</a> &middot;
            <a href="/sanggah">Ajukan Sanggah / Hapus Data</a>
        </p>
        <p>Sistem ini disediakan untuk tujuan anti-penipuan. Data bersumber dari laporan komunitas.</p>
    </footer>
</body>
</html>
    <?php
}
