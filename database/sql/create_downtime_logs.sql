-- =============================================================
-- SQL setara migration: create_downtime_logs_table
-- Jalankan via phpMyAdmin / MySQL CLI kalau `php artisan migrate`
-- tidak bisa dipakai.
-- =============================================================

CREATE TABLE IF NOT EXISTS `downtime_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `vendor_id` BIGINT UNSIGNED NULL,
    `client_id` BIGINT UNSIGNED NULL,
    `client_name` VARCHAR(255) NULL,
    `down_at` DATETIME NOT NULL,
    `up_at` DATETIME NULL,
    `duration_seconds` INT UNSIGNED NULL,
    `status` VARCHAR(255) NOT NULL DEFAULT 'down',
    `reason` TEXT NOT NULL,
    `action` TEXT NULL,
    `created_by` BIGINT UNSIGNED NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    INDEX `downtime_logs_status_down_at_index` (`status`, `down_at`),
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Foreign keys (jika tabel partners, clients, users sudah ada)
ALTER TABLE `downtime_logs`
    ADD CONSTRAINT `downtime_logs_vendor_id_foreign`
        FOREIGN KEY (`vendor_id`) REFERENCES `partners` (`id`) ON DELETE SET NULL,
    ADD CONSTRAINT `downtime_logs_client_id_foreign`
        FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL,
    ADD CONSTRAINT `downtime_logs_created_by_foreign`
        FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;