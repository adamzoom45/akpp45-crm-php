<?php
/**
 * Vehicle Database
 * @package AKPP_Kurgan
 */

if (!defined('ABSPATH')) {
    exit;
}

class AKPP_Vehicle_DB {
    
    public function __construct() {
        add_action('init', array($this, 'create_tables'));
        add_action('admin_init', array($this, 'populate_data'));
        add_action('wp_ajax_akpp_add_vehicle', array($this, 'add_vehicle'));
        add_action('wp_ajax_akpp_edit_vehicle', array($this, 'edit_vehicle'));
        add_action('wp_ajax_akpp_delete_vehicle', array($this, 'delete_vehicle'));
    }
    
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql_vehicles = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}akpp_vehicles (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            brand VARCHAR(50) NOT NULL,
            model VARCHAR(100) NOT NULL,
            year_from INT,
            year_to INT,
            transmission_code VARCHAR(50),
            engine VARCHAR(100),
            drive_type VARCHAR(20),
            amayama_code VARCHAR(100),
            body_number VARCHAR(50),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_brand (brand),
            KEY idx_model (model),
            KEY idx_transmission (transmission_code)
        ) $charset_collate;";
        
        $sql_deals = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}akpp_deals (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            client_name VARCHAR(255),
            client_phone VARCHAR(50),
            client_vin VARCHAR(17),
            client_body_number VARCHAR(50),
            client_plate VARCHAR(20),
            car_brand VARCHAR(50),
            car_model VARCHAR(100),
            car_year INT,
            transmission_code VARCHAR(50),
            employee_id INT,
            problem_description TEXT,
            parts_cost DECIMAL(10,2) DEFAULT 0,
            employee_payment DECIMAL(10,2) DEFAULT 0,
            deal_price DECIMAL(10,2) DEFAULT 0,
            status VARCHAR(50) DEFAULT 'new',
            received_date DATETIME,
            completed_date DATETIME,
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_status (status),
            KEY idx_client_phone (client_phone),
            KEY idx_client_vin (client_vin)
        ) $charset_collate;";
        
        $sql_employees = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}akpp_employees (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            position VARCHAR(100),
            phone VARCHAR(50),
            salary_type VARCHAR(20) DEFAULT 'percent',
            salary_percent DECIMAL(5,2) DEFAULT 10.00,
            fixed_salary DECIMAL(10,2) DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_is_active (is_active)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_vehicles);
        dbDelta($sql_deals);
        dbDelta($sql_employees);
    }
    
    public function populate_data() {
        $check = get_option('akpp_vehicles_populated_v2', false);
        if ($check) {
            return;
        }
        
        $this->insert_vehicles();
        
        update_option('akpp_vehicles_populated_v2', true);
    }
    
    private function insert_vehicles() {
        global $wpdb;
        $table = $wpdb->prefix . 'akpp_vehicles';
        
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        if ($count > 0) {
            return;
        }
        
        $vehicles = array(
            // TOYOTA
            array('Toyota', 'Camry', 1990, 1996, 'A140E', '2.2L 5S-FE', 'FWD'),
            array('Toyota', 'Camry', 1996, 2001, 'A540E', '2.2L 5S-FE', 'FWD'),
            array('Toyota', 'Camry', 2001, 2006, 'U241E', '2.4L 2AZ-FE', 'FWD'),
            array('Toyota', 'Camry', 2006, 2011, 'U250E', '2.4L 2AZ-FE', 'FWD'),
            array('Toyota', 'Camry', 2011, 2017, 'U660E', '2.5L 2AR-FE', 'FWD'),
            array('Toyota', 'Camry', 2017, 2026, 'U880E', '2.5L A25A-FKS', 'FWD'),
            array('Toyota', 'Corolla', 1990, 2000, 'A241E', '1.6L 4A-FE', 'FWD'),
            array('Toyota', 'Corolla', 2000, 2007, 'U340E', '1.8L 1ZZ-FE', 'FWD'),
            array('Toyota', 'Corolla', 2007, 2013, 'U341E', '1.8L 2ZR-FE', 'FWD'),
            array('Toyota', 'Corolla', 2013, 2019, 'U660E', '1.8L 2ZR-FE', 'FWD'),
            array('Toyota', 'Corolla', 2019, 2026, 'K120', '2.0L M20A-FKS', 'FWD'),
            array('Toyota', 'RAV4', 1994, 2000, 'A241E', '2.0L 3S-FE', 'AWD'),
            array('Toyota', 'RAV4', 2000, 2005, 'U140E', '2.0L 1AZ-FE', 'AWD'),
            array('Toyota', 'RAV4', 2005, 2012, 'U241E', '2.4L 2AZ-FE', 'AWD'),
            array('Toyota', 'RAV4', 2012, 2018, 'U660E', '2.5L 2AR-FE', 'AWD'),
            array('Toyota', 'RAV4', 2018, 2026, 'U880E', '2.5L A25A-FKS', 'AWD'),
            array('Toyota', 'Land Cruiser 80', 1990, 1997, 'A340F', '4.5L 1FZ-FE', '4WD'),
            array('Toyota', 'Land Cruiser 80', 1990, 1997, 'A442F', '4.2L 1HD-T', '4WD'),
            array('Toyota', 'Land Cruiser 100', 1998, 2007, 'A340F', '4.5L 1FZ-FE', '4WD'),
            array('Toyota', 'Land Cruiser 100', 1998, 2007, 'A650E', '4.7L 2UZ-FE', '4WD'),
            array('Toyota', 'Land Cruiser 200', 2007, 2015, 'A650E', '4.6L 1UR-FE', '4WD'),
            array('Toyota', 'Land Cruiser 200', 2007, 2015, 'A750F', '4.5L 1VD-FTV', '4WD'),
            array('Toyota', 'Land Cruiser 200', 2015, 2021, 'A760F', '4.5L 1VD-FTV', '4WD'),
            array('Toyota', 'Land Cruiser 300', 2021, 2026, 'A760F', '3.5L V35A-FTS', '4WD'),
            array('Toyota', 'Prado 90', 1996, 2002, 'A340F', '3.4L 5VZ-FE', '4WD'),
            array('Toyota', 'Prado 120', 2002, 2009, 'A650E', '4.0L 1GR-FE', '4WD'),
            array('Toyota', 'Prado 150', 2009, 2023, 'A760F', '2.8L 1GD-FTV', '4WD'),
            array('Toyota', 'Mark II', 1992, 2000, 'A340E', '2.5L 1JZ-GE', 'RWD'),
            array('Toyota', 'Mark II', 2000, 2004, 'A650E', '2.5L 1JZ-GTE', 'RWD'),
            array('Toyota', 'Chaser', 1992, 2001, 'A340E', '2.5L 1JZ-GE', 'RWD'),
            array('Toyota', 'Cresta', 1992, 2001, 'A340E', '2.5L 1JZ-GE', 'RWD'),
            array('Toyota', 'Crown', 1995, 2003, 'A340E', '2.5L 1JZ-GE', 'RWD'),
            array('Toyota', 'Crown', 2003, 2008, 'A650E', '3.0L 3GR-FSE', 'RWD'),
            array('Toyota', 'Crown', 2008, 2012, 'A760E', '3.5L 2GR-FSE', 'RWD'),
            array('Toyota', 'Crown', 2018, 2026, 'A960E', '3.5L V35A-FTS', 'RWD'),
            array('Toyota', 'Mark X', 2004, 2019, 'A650E', '2.5L 4GR-FSE', 'RWD'),
            array('Toyota', 'Highlander', 2000, 2007, 'U150F', '3.0L 1MZ-FE', 'AWD'),
            array('Toyota', 'Highlander', 2007, 2019, 'U660F', '3.5L 2GR-FE', 'AWD'),
            array('Toyota', 'Highlander', 2019, 2026, 'U880F', '2.5L A25A-FXS', 'AWD'),
            array('Toyota', 'Supra', 1993, 2002, 'A340E', '3.0L 2JZ-GTE', 'RWD'),
            array('Toyota', 'Supra', 2019, 2026, 'ZF8HP', '3.0L B58', 'RWD'),
            array('Toyota', '86', 2012, 2020, 'A960E', '2.0L FA20', 'RWD'),
            array('Toyota', 'GR86', 2021, 2026, 'A960E', '2.4L FA24', 'RWD'),
            
            // LEXUS
            array('Lexus', 'IS200', 1998, 2005, 'A650E', '2.0L 1G-FE', 'RWD'),
            array('Lexus', 'IS250', 2005, 2020, 'A650E', '2.5L 4GR-FSE', 'RWD'),
            array('Lexus', 'IS350', 2005, 2020, 'A650E', '3.5L 2GR-FSE', 'RWD'),
            array('Lexus', 'GS300', 1993, 2005, 'A340E', '3.0L 2JZ-GE', 'RWD'),
            array('Lexus', 'GS350', 2005, 2020, 'A760E', '3.5L 2GR-FSE', 'RWD'),
            array('Lexus', 'LS400', 1989, 2000, 'A340E', '4.0L 1UZ-FE', 'RWD'),
            array('Lexus', 'LS430', 2000, 2006, 'A650E', '4.3L 3UZ-FE', 'RWD'),
            array('Lexus', 'LS460', 2006, 2017, 'A760E', '4.6L 1UR-FE', 'RWD'),
            array('Lexus', 'LS500', 2017, 2026, 'A960E', '3.5L V35A-FTS', 'RWD'),
            array('Lexus', 'RX300', 1998, 2003, 'U140F', '3.0L 1MZ-FE', 'AWD'),
            array('Lexus', 'RX330', 2003, 2006, 'U150F', '3.3L 3MZ-FE', 'AWD'),
            array('Lexus', 'RX350', 2006, 2022, 'U660F', '3.5L 2GR-FE', 'AWD'),
            array('Lexus', 'RX450h', 2009, 2022, 'U660F', '3.5L 2GR-FXE', 'AWD'),
            array('Lexus', 'NX200t', 2014, 2021, 'U660F', '2.0L 8AR-FTS', 'AWD'),
            array('Lexus', 'NX350', 2021, 2026, 'U880F', '2.4L T24A-FTS', 'AWD'),
            array('Lexus', 'LX450', 1995, 1997, 'A340F', '4.5L 1FZ-FE', '4WD'),
            array('Lexus', 'LX470', 1998, 2007, 'A650E', '4.7L 2UZ-FE', '4WD'),
            array('Lexus', 'LX570', 2007, 2021, 'A750F', '5.7L 3UR-FE', '4WD'),
            array('Lexus', 'LX600', 2021, 2026, 'A760F', '3.5L V35A-FTS', '4WD'),
            
            // MAZDA
            array('Mazda', 'Mazda 3', 2003, 2013, 'FS5A-EL', '2.0L LF-VE', 'FWD'),
            array('Mazda', 'Mazda 3', 2013, 2026, 'SKYACTIV-Drive', '2.5L PY-VPS', 'FWD'),
            array('Mazda', 'Mazda 6', 2002, 2012, 'FS5A-EL', '2.5L L5-VE', 'FWD'),
            array('Mazda', 'Mazda 6', 2012, 2026, 'SKYACTIV-Drive', '2.5L PY-VPS', 'FWD'),
            array('Mazda', 'CX-5', 2012, 2026, 'SKYACTIV-Drive', '2.5L PY-VPS', 'AWD'),
            array('Mazda', 'CX-9', 2006, 2015, 'FNR5', '3.7L CY-DE', 'AWD'),
            array('Mazda', 'CX-9', 2016, 2026, 'SKYACTIV-Drive', '2.5T PY-VPTS', 'AWD'),
            
            // FORD
            array('Ford', 'Focus', 1998, 2011, 'FN4A-EL', '2.0L Duratec', 'FWD'),
            array('Ford', 'Focus', 2011, 2018, '6F35', '2.0L Duratec', 'FWD'),
            array('Ford', 'Mustang', 2005, 2014, '5R55S', '4.0L V6', 'RWD'),
            array('Ford', 'Mustang', 2014, 2026, '6R80', '5.0L V8', 'RWD'),
            array('Ford', 'F-150', 2004, 2008, '4R75E', '4.6L V8', '4WD'),
            array('Ford', 'F-150', 2008, 2014, '6R80', '5.0L V8', '4WD'),
            array('Ford', 'F-150', 2014, 2026, '10R80', '3.5L EcoBoost', '4WD'),
            
            // HYUNDAI
            array('Hyundai', 'Solaris', 2010, 2017, 'A4CF1', '1.6L G4FG', 'FWD'),
            array('Hyundai', 'Solaris', 2017, 2026, 'A6CF1', '1.6L G4FG', 'FWD'),
            array('Hyundai', 'Elantra', 1995, 2010, 'A4AF3', '2.0L G4GC', 'FWD'),
            array('Hyundai', 'Elantra', 2010, 2016, 'A6CF1', '1.8L G4NB', 'FWD'),
            array('Hyundai', 'Elantra', 2016, 2026, 'A6CF1', '2.0L G4FG', 'FWD'),
            array('Hyundai', 'Tucson', 2004, 2010, 'A4AF3', '2.0L G4GC', 'AWD'),
            array('Hyundai', 'Tucson', 2010, 2015, 'A6AF1', '2.0L G4KD', 'AWD'),
            array('Hyundai', 'Tucson', 2015, 2026, 'A8LF1', '2.5L G4KM', 'AWD'),
            
            // KIA
            array('Kia', 'Rio', 2000, 2011, 'A4AF3', '1.6L G4ED', 'FWD'),
            array('Kia', 'Rio', 2011, 2026, 'A6CF1', '1.6L G4FG', 'FWD'),
            array('Kia', 'Sportage', 2004, 2010, 'A4AF3', '2.0L G4GC', 'AWD'),
            array('Kia', 'Sportage', 2010, 2016, 'A6AF1', '2.0L G4KD', 'AWD'),
            array('Kia', 'Sportage', 2016, 2026, 'A8LF1', '2.5L G4KM', 'AWD'),
            
            // NISSAN
            array('Nissan', 'X-Trail', 2007, 2015, 'RE0F10A', '2.0L MR20DE', 'AWD'),
            array('Nissan', 'X-Trail', 2015, 2026, 'RE0F10D', '2.0L MR20DD', 'AWD'),
            array('Nissan', 'Qashqai', 2007, 2014, 'RE0F10A', '2.0L MR20DE', 'AWD'),
            array('Nissan', 'Qashqai', 2014, 2026, 'RE0F10D', '2.0L MR20DD', 'AWD'),
            array('Nissan', 'Pathfinder', 2005, 2012, 'RE5R05A', '4.0L VQ40DE', '4WD'),
            array('Nissan', 'Pathfinder', 2012, 2026, 'RE0F10D', '3.5L VQ35DE', 'AWD'),
            
            // MITSUBISHI
            array('Mitsubishi', 'Lancer', 2003, 2017, 'F4A22', '2.0L 4B11', 'FWD'),
            array('Mitsubishi', 'Outlander', 2003, 2006, 'F4A22', '2.4L 4G64', 'AWD'),
            array('Mitsubishi', 'Outlander', 2006, 2012, 'F6A42', '3.0L 6B31', 'AWD'),
            array('Mitsubishi', 'Outlander', 2012, 2026, 'F6A42', '2.4L 4J12', 'AWD'),
            
            // RENAULT
            array('Renault', 'Logan', 2004, 2026, 'DP0', '1.6L K7M', 'FWD'),
            array('Renault', 'Duster', 2010, 2026, 'DP0', '2.0L F4R', '4WD'),
            array('Renault', 'Kaptur', 2016, 2026, 'DP0', '2.0L F4R', '4WD'),
            
            // BMW
            array('BMW', 'X5', 2000, 2006, 'GM5L40E', '3.0L M54', 'AWD'),
            array('BMW', 'X5', 2006, 2013, 'GA6HP26Z', '3.0L N52', 'AWD'),
            array('BMW', 'X5', 2013, 2018, 'GA8HP70Z', '3.0L N55', 'AWD'),
            array('BMW', 'X5', 2018, 2026, 'GA8HP75Z', '3.0L B58', 'AWD'),
            array('BMW', '3 Series', 1998, 2005, 'GM5L40E', '2.5L M54', 'RWD'),
            array('BMW', '3 Series', 2005, 2012, 'GA6HP26Z', '3.0L N52', 'RWD'),
            array('BMW', '3 Series', 2012, 2019, 'GA8HP45Z', '2.0L N20', 'RWD'),
            array('BMW', '3 Series', 2019, 2026, 'GA8HP50Z', '2.0L B48', 'RWD'),
            
            // VAG
            array('Volkswagen', 'Tiguan', 2007, 2016, '09G', '2.0L TSI', 'AWD'),
            array('Volkswagen', 'Tiguan', 2016, 2026, 'DQ381', '2.0L TSI', 'AWD'),
            array('Volkswagen', 'Passat', 2005, 2010, '09G', '2.0L TSI', 'FWD'),
            array('Volkswagen', 'Passat', 2010, 2015, 'DQ250', '2.0L TSI', 'FWD'),
            array('Volkswagen', 'Passat', 2015, 2026, 'DQ381', '2.0L TSI', 'FWD'),
            array('Audi', 'Q7', 2006, 2015, '09D', '3.6L FSI', 'AWD'),
            array('Audi', 'Q7', 2015, 2026, '0C8', '3.0L TFSI', 'AWD'),
            array('Audi', 'Q5', 2008, 2017, '0B5', '2.0L TFSI', 'AWD'),
            array('Audi', 'Q5', 2017, 2026, 'DL382', '2.0L TFSI', 'AWD'),
        );
        
        foreach ($vehicles as $v) {
            $wpdb->insert($table, array(
                'brand'             => $v[0],
                'model'             => $v[1],
                'year_from'         => $v[2],
                'year_to'           => $v[3],
                'transmission_code' => $v[4],
                'engine'            => $v[5],
                'drive_type'        => $v[6],
                'amayama_code'      => '',
                'body_number'       => '',
            ));
        }
    }
    
    public function add_vehicle() {
        check_ajax_referer(AKPP_CRM_NONCE, 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Нет доступа'));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'akpp_vehicles';
        
        $data = array(
            'brand'             => sanitize_text_field($_POST['brand'] ?? ''),
            'model'             => sanitize_text_field($_POST['model'] ?? ''),
            'year_from'         => intval($_POST['year_from'] ?? 0),
            'year_to'           => intval($_POST['year_to'] ?? 0),
            'transmission_code' => sanitize_text_field($_POST['transmission_code'] ?? ''),
            'engine'            => sanitize_text_field($_POST['engine'] ?? ''),
            'drive_type'        => sanitize_text_field($_POST['drive_type'] ?? ''),
        );
        
        if (empty($data['brand']) || empty($data['model'])) {
            wp_send_json_error(array('message' => 'Заполните обязательные поля'));
        }
        
        $result = $wpdb->insert($table, $data);
        
        if ($result) {
            wp_send_json_success(array('message' => 'Автомобиль добавлен', 'id' => $wpdb->insert_id));
        } else {
            wp_send_json_error(array('message' => 'Ошибка добавления'));
        }
    }
    
    public function edit_vehicle() {
        check_ajax_referer(AKPP_CRM_NONCE, 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Нет доступа'));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'akpp_vehicles';
        
        $id = intval($_POST['id'] ?? 0);
        
        $data = array(
            'brand'             => sanitize_text_field($_POST['brand'] ?? ''),
            'model'             => sanitize_text_field($_POST['model'] ?? ''),
            'year_from'         => intval($_POST['year_from'] ?? 0),
            'year_to'           => intval($_POST['year_to'] ?? 0),
            'transmission_code' => sanitize_text_field($_POST['transmission_code'] ?? ''),
            'engine'            => sanitize_text_field($_POST['engine'] ?? ''),
            'drive_type'        => sanitize_text_field($_POST['drive_type'] ?? ''),
        );
        
        if ($id <= 0) {
            wp_send_json_error(array('message' => 'Неверный ID'));
        }
        
        $result = $wpdb->update($table, $data, array('id' => $id));
        
        if ($result !== false) {
            wp_send_json_success(array('message' => 'Автомобиль обновлен'));
        } else {
            wp_send_json_error(array('message' => 'Ошибка обновления'));
        }
    }
    
    public function delete_vehicle() {
        check_ajax_referer(AKPP_CRM_NONCE, 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Нет доступа'));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'akpp_vehicles';
        
        $id = intval($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            wp_send_json_error(array('message' => 'Неверный ID'));
        }
        
        $result = $wpdb->delete($table, array('id' => $id));
        
        if ($result) {
            wp_send_json_success(array('message' => 'Автомобиль удален'));
        } else {
            wp_send_json_error(array('message' => 'Ошибка удаления'));
        }
    }
}

new AKPP_Vehicle_DB();