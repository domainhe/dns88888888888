CREATE TABLE IF NOT EXISTS `mod_dnsmanager_domains` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `userid` INT NOT NULL,
    `domain` VARCHAR(255) NOT NULL,
    `zone_id` VARCHAR(64) NOT NULL,
    `ns1` VARCHAR(64) NOT NULL DEFAULT '',
    `ns2` VARCHAR(64) NOT NULL DEFAULT '',
    `ns_status` VARCHAR(20) NOT NULL DEFAULT 'pending',
    `ns_checked_at` DATETIME NULL DEFAULT NULL,
    `locked` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `mod_dnsmanager_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `userid` INT NOT NULL,
    `action` VARCHAR(50) NOT NULL,
    `record_type` VARCHAR(10),
    `record_name` VARCHAR(255),
    `record_content` VARCHAR(255),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `mod_dnsmanager_cron_runs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ran_at` DATETIME NOT NULL,
    `job` VARCHAR(64) NOT NULL,
    `duration_ms` INT NOT NULL DEFAULT 0,
    `success_count` INT NOT NULL DEFAULT 0,
    `fail_count` INT NOT NULL DEFAULT 0,
    `queue_depth` INT NOT NULL DEFAULT 0,
    `message` VARCHAR(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `mod_dnsmanager_desired_records` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `domain_id` INT NOT NULL,
    `zone_id` VARCHAR(64) NOT NULL,
    `type` VARCHAR(10) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `content` TEXT NULL,
    `ttl` INT NOT NULL DEFAULT 1,
    `proxied` TINYINT(1) NOT NULL DEFAULT 0,
    `priority` INT NULL,
    `data` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_domain` (`domain_id`),
    INDEX `idx_zone` (`zone_id`),
    INDEX `idx_name_type` (`name`(191), `type`)
) ENGINE=InnoDB;
