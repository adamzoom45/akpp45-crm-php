<?php
/**
 * TransAKPP Integration
 */
if (!defined('ABSPATH')) exit;

class AKPP_TransAKPP {
    
    public function __construct() {
        add_action('init', array($this, 'create_table'));
    }
    
    public function create_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}akpp_transmissions (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            code VARCHAR(50) NOT NULL,
            name VARCHAR(255),
            manufacturer VARCHAR(100),
            type VARCHAR(50),
            gears INT,
            description TEXT,
            repair_price DECIMAL(10,2) DEFAULT 0,
            amayama_code VARCHAR(100),
            transakpp_url VARCHAR(500),
            manual_url VARCHAR(500),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY idx_code (code)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

new AKPP_TransAKPP();