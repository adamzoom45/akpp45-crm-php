<?php
/**
 * Database Setup - Добавление таблиц лидов
 * @package AKPP_Kurgan
 */

if (!defined('ABSPATH')) exit;

class AKPP_Database {
    
    public function __construct() {
        add_action('admin_init', array($this, 'create_tables'));
    }
    
    public function create_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Таблица лидов
        $sql_leads = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}akpp_leads (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            source VARCHAR(50) NOT NULL DEFAULT 'manual',
            source_id VARCHAR(100) DEFAULT NULL,
            name VARCHAR(255) DEFAULT NULL,
            phone VARCHAR(50) DEFAULT NULL,
            email VARCHAR(100) DEFAULT NULL,
            message TEXT DEFAULT NULL,
            vehicle_info VARCHAR(255) DEFAULT NULL,
            transmission_code VARCHAR(50) DEFAULT NULL,
            status VARCHAR(50) DEFAULT 'new',
            deal_id BIGINT UNSIGNED DEFAULT NULL,
            assigned_to BIGINT UNSIGNED DEFAULT NULL,
            utm_source VARCHAR(100) DEFAULT NULL,
            utm_medium VARCHAR(100) DEFAULT NULL,
            utm_campaign VARCHAR(100) DEFAULT NULL,
            ip_address VARCHAR(45) DEFAULT NULL,
            user_agent TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_status (status),
            KEY idx_source (source),
            KEY idx_phone (phone),
            KEY idx_created (created_at)
        ) $charset;";
        
        // Таблица сообщений лидов
        $sql_messages = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}akpp_lead_messages (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            lead_id BIGINT UNSIGNED NOT NULL,
            direction ENUM('in','out') DEFAULT 'in',
            channel VARCHAR(50) DEFAULT NULL,
            message TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_lead (lead_id)
        ) $charset;";
        
        // Таблица настроек интеграций
        $sql_settings = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}akpp_integration_settings (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            setting_key VARCHAR(100) NOT NULL UNIQUE,
            setting_value TEXT DEFAULT NULL,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;";
        
        dbDelta($sql_leads);
        dbDelta($sql_messages);
        dbDelta($sql_settings);
        
        // Добавляем колонку email в employees если нет
        $table_emp = $wpdb->prefix . 'akpp_employees';
        $col_exists = $wpdb->get_results("SHOW COLUMNS FROM `$table_emp` LIKE 'email'");
        if (empty($col_exists)) {
            $wpdb->query("ALTER TABLE `$table_emp` ADD COLUMN `email` VARCHAR(100) NULL DEFAULT '' AFTER `phone`");
        }
    }
}

new AKPP_Database();