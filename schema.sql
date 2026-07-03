-- Jalankan ini dulu di MySQL/MariaDB

CREATE DATABASE IF NOT EXISTS scam_tracker CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE scam_tracker;

CREATE TABLE tag (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    hashid      CHAR(64) NOT NULL,                 -- sha256 fingerprint konten (64 hex)
    identifier  VARCHAR(255) NOT NULL,             -- "type:account_id" kanonik, mis. phone:6281267991717
    id_link     VARCHAR(255) DEFAULT NULL,         -- SELALU nunjuk root (Opsi B)
    name        VARCHAR(255) DEFAULT NULL,
    tag         VARCHAR(255) DEFAULT NULL,
    url         TEXT DEFAULT NULL,
    ip          VARCHAR(45) DEFAULT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uq_hashid (hashid),                 -- penjaga duplikat (ketat): DB nolak laporan identik
    INDEX idx_identifier (identifier),
    INDEX idx_id_link (id_link),
    INDEX idx_name (name),
    INDEX idx_tag (tag)
) ENGINE=InnoDB;
