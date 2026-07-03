-- Jalankan ini dulu di MySQL/MariaDB

CREATE DATABASE IF NOT EXISTS scam_tracker CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE scam_tracker;

-- ============================================================
-- Tabel users (untuk login Google OAuth)
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    google_sub  VARCHAR(64) NOT NULL,              -- Google unique subject ID
    email       VARCHAR(255) NOT NULL,
    name        VARCHAR(255) DEFAULT NULL,
    picture     TEXT DEFAULT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uq_google_sub (google_sub),
    UNIQUE KEY uq_email (email)
) ENGINE=InnoDB;

-- ============================================================
-- Tabel tag (DEFINISI LENGKAP untuk instalasi baru)
-- Sudah menyertakan kolom kepemilikan & soft-delete.
-- ============================================================
CREATE TABLE IF NOT EXISTS tag (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    hashid      CHAR(64) NOT NULL,                 -- sha256 fingerprint konten (64 hex)
    identifier  VARCHAR(255) NOT NULL,             -- "type:account_id" kanonik, mis. phone:6281267991717
    id_link     VARCHAR(255) DEFAULT NULL,         -- SELALU nunjuk root (Opsi B)
    name        VARCHAR(255) DEFAULT NULL,
    tag         VARCHAR(255) DEFAULT NULL,
    url         TEXT DEFAULT NULL,
    ip          VARCHAR(45) DEFAULT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Kolom kepemilikan & soft-delete (ditambahkan versi 2)
    user_id     INT NULL,                          -- siapa yang menginput (FK ke users.id)
    status      ENUM('active','hidden','removed') NOT NULL DEFAULT 'active',
    deleted_at  TIMESTAMP NULL DEFAULT NULL,
    deleted_by  INT NULL,                          -- siapa yang menghapus (FK ke users.id)

    UNIQUE KEY uq_hashid (hashid),                 -- penjaga duplikat (ketat): DB nolak laporan identik
    INDEX idx_identifier (identifier),
    INDEX idx_id_link (id_link),
    INDEX idx_name (name),
    INDEX idx_tag (tag),
    INDEX idx_status (status),
    INDEX idx_user_id (user_id),

    CONSTRAINT fk_tag_user    FOREIGN KEY (user_id)   REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_tag_deleter FOREIGN KEY (deleted_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- Tabel disputes (pengajuan sanggah/hapus data, tanpa login)
-- ============================================================
CREATE TABLE IF NOT EXISTS disputes (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    tag_id      INT NULL,                          -- FK ke tag.id (boleh NULL kalau tag sudah dihapus)
    identifier  VARCHAR(255) NULL,                 -- disimpan juga sebagai teks cadangan
    reason      TEXT NOT NULL,
    contact     VARCHAR(255) NULL,                 -- kontak pelapor (opsional)
    status      ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    handled_by  INT NULL,                          -- admin yang menangani (FK ke users.id)
    handled_at  TIMESTAMP NULL DEFAULT NULL,
    admin_note  TEXT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip          VARCHAR(45) NULL,

    INDEX idx_disputes_status (status),
    INDEX idx_disputes_tag_id (tag_id),

    CONSTRAINT fk_dispute_tag     FOREIGN KEY (tag_id)     REFERENCES tag(id) ON DELETE SET NULL,
    CONSTRAINT fk_dispute_handler FOREIGN KEY (handled_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- ALTER untuk database LAMA (yang sudah ada tabel tag)
-- Jalankan blok ini SEKALI SAJA pada instalasi yang sudah ada.
-- MySQL tidak punya ADD COLUMN IF NOT EXISTS, jadi jalankan manual
-- dan skip kalau kolom sudah ada (cek dulu via DESCRIBE tag).
-- ============================================================
-- ALTER TABLE tag ADD COLUMN user_id    INT NULL AFTER ip;
-- ALTER TABLE tag ADD COLUMN status     ENUM('active','hidden','removed') NOT NULL DEFAULT 'active' AFTER user_id;
-- ALTER TABLE tag ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL AFTER status;
-- ALTER TABLE tag ADD COLUMN deleted_by INT NULL AFTER deleted_at;
-- ALTER TABLE tag ADD INDEX idx_status  (status);
-- ALTER TABLE tag ADD INDEX idx_user_id (user_id);
-- ALTER TABLE tag ADD CONSTRAINT fk_tag_user    FOREIGN KEY (user_id)    REFERENCES users(id) ON DELETE SET NULL;
-- ALTER TABLE tag ADD CONSTRAINT fk_tag_deleter FOREIGN KEY (deleted_by) REFERENCES users(id) ON DELETE SET NULL;
