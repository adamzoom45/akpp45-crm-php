<?php
/**
 * Transmissions Database
 * @package AKPP_Kurgan
 */

if (!defined('ABSPATH')) {
    exit;
}

class AKPP_Transmissions {
    
    public function __construct() {
        add_action('admin_init', array($this, 'populate_data'));
        add_action('wp_ajax_akpp_add_transmission', array($this, 'add_transmission'));
        add_action('wp_ajax_akpp_edit_transmission', array($this, 'edit_transmission'));
        add_action('wp_ajax_akpp_delete_transmission', array($this, 'delete_transmission'));
    }
    
    public function populate_data() {
        $check = get_option('akpp_transmissions_populated_v2', false);
        if ($check) {
            return;
        }
        
        $this->insert_transmissions();
        
        update_option('akpp_transmissions_populated_v2', true);
    }
    
    private function insert_transmissions() {
        global $wpdb;
        $table = $wpdb->prefix . 'akpp_transmissions';
        
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        if ($count > 0) {
            return;
        }
        
        $transmissions = array(
            array('A140E', 'Aisin A140E', 'Aisin Warner', 'Automatic', 4, '4-ст АКПП Toyota Camry', 50000, '33000-20010', 'https://www.transakpp.ru/toyota/a140e', ''),
            array('A241E', 'Aisin A241E', 'Aisin Warner', 'Automatic', 4, '4-ст АКПП Toyota Corolla', 50000, '33000-20020', 'https://www.transakpp.ru/toyota/a241e', ''),
            array('A340E', 'Aisin A340E', 'Aisin Warner', 'Automatic', 4, '4-ст АКПП Toyota RWD', 50000, '33000-30520', 'https://www.transakpp.ru/toyota/a340e', ''),
            array('A340F', 'Aisin A340F', 'Aisin Warner', 'Automatic', 4, '4-ст АКПП Toyota 4WD', 50000, '33000-30530', 'https://www.transakpp.ru/toyota/a340f', ''),
            array('A650E', 'Aisin A650E', 'Aisin Warner', 'Automatic', 6, '6-ст АКПП Toyota/Lexus', 50000, '33000-30650', 'https://www.transakpp.ru/toyota/a650e', ''),
            array('A750F', 'Aisin A750F', 'Aisin Warner', 'Automatic', 5, '5-ст АКПП Toyota 4WD', 55000, '33000-30670', 'https://www.transakpp.ru/toyota/a750f', ''),
            array('A760F', 'Aisin A760F', 'Aisin Warner', 'Automatic', 6, '6-ст АКПП Toyota 4WD', 60000, '33000-30690', 'https://www.transakpp.ru/toyota/a760f', ''),
            array('U660E', 'Aisin U660E', 'Aisin Warner', 'Automatic', 6, '6-ст АКПП Toyota FWD', 60000, '33000-30820', 'https://www.transakpp.ru/toyota/u660e', ''),
            array('U660F', 'Aisin U660F', 'Aisin Warner', 'Automatic', 6, '6-ст АКПП Toyota AWD', 60000, '33000-30830', 'https://www.transakpp.ru/toyota/u660f', ''),
            array('U880E', 'Aisin U880E', 'Aisin Warner', 'Automatic', 8, '8-ст АКПП Toyota', 70000, '33000-30860', 'https://www.transakpp.ru/toyota/u880e', ''),
            array('FS5A-EL', 'Mazda FS5A-EL', 'Mazda/Ford', 'Automatic', 5, '5-ст АКПП Mazda', 55000, '', 'https://www.transakpp.ru/mazda/fs5a-el', ''),
            array('6F35', 'Ford 6F35', 'Ford/GM', 'Automatic', 6, '6-ст АКПП Ford Focus/Escape', 60000, '', 'https://www.transakpp.ru/ford/6f35', ''),
            array('6R80', 'Ford 6R80', 'Ford', 'Automatic', 6, '6-ст АКПП Ford F-150/Mustang', 60000, '', 'https://www.transakpp.ru/ford/6r80', ''),
            array('10R80', 'Ford 10R80', 'Ford/GM', 'Automatic', 10, '10-ст АКПП Ford F-150/Mustang', 75000, '', 'https://www.transakpp.ru/ford/10r80', ''),
            array('A4CF1', 'Hyundai A4CF1', 'Hyundai Powertech', 'Automatic', 4, '4-ст АКПП Hyundai Solaris', 50000, '', 'https://www.transakpp.ru/hyundai/a4cf1', ''),
            array('A6CF1', 'Hyundai A6CF1', 'Hyundai Powertech', 'Automatic', 6, '6-ст АКПП Hyundai/Kia Rio', 60000, '', 'https://www.transakpp.ru/hyundai/a6cf1', ''),
            array('A8LF1', 'Hyundai A8LF1', 'Hyundai Powertech', 'Automatic', 8, '8-ст АКПП Hyundai/Kia', 70000, '', 'https://www.transakpp.ru/hyundai/a8lf1', ''),
            array('DP0', 'Renault DP0 (AL4)', 'Peugeot/Citroen', 'Automatic', 4, '4-ст АКПП Renault Logan/Duster', 50000, '', 'https://www.transakpp.ru/renault/dp0', ''),
            array('F4A22', 'Mitsubishi F4A22', 'Jatco', 'Automatic', 4, '4-ст АКПП Mitsubishi Lancer', 50000, '', 'https://www.transakpp.ru/mitsubishi/f4a22', ''),
            array('F6A42', 'Mitsubishi F6A42', 'Jatco', 'Automatic', 6, '6-ст АКПП Mitsubishi Outlander', 60000, '', 'https://www.transakpp.ru/mitsubishi/f6a42', ''),
            array('5HP19', 'ZF 5HP19', 'ZF', 'Automatic', 5, '5-ст АКПП BMW 3/5 Series', 55000, '', 'https://www.transakpp.ru/bmw/5hp19', ''),
            array('6HP26', 'ZF 6HP26', 'ZF', 'Automatic', 6, '6-ст АКПП BMW 7/X5/X6', 65000, '', 'https://www.transakpp.ru/bmw/6hp26', ''),
            array('8HP70', 'ZF 8HP70', 'ZF', 'Automatic', 8, '8-ст АКПП BMW 5/7/X5', 75000, '', 'https://www.transakpp.ru/bmw/8hp70', ''),
            array('09G', 'VAG 09G (TF-60SN)', 'Aisin', 'Automatic', 6, '6-ст АКПП VW/Audi', 60000, '', 'https://www.transakpp.ru/vag/09g', ''),
            array('09L', 'VAG 09L (ZF 6HP)', 'ZF', 'Automatic', 6, '6-ст АКПП Audi Q7/VW Touareg', 65000, '', 'https://www.transakpp.ru/vag/09l', ''),
            array('4L60E', 'GM 4L60E', 'General Motors', 'Automatic', 4, '4-ст АКПП GM/Cadillac', 50000, '', 'https://www.transakpp.ru/gm/4l60e', ''),
            array('6L80', 'GM 6L80 (6T80)', 'General Motors', 'Automatic', 6, '6-ст АКПП GM Trucks', 60000, '', 'https://www.transakpp.ru/gm/6l80', ''),
            array('8L90', 'GM 8L90 (8T90)', 'General Motors', 'Automatic', 8, '8-ст АКПП GM Corvette/Camaro', 75000, '', 'https://www.transakpp.ru/gm/8l90', ''),
        );
        
        foreach ($transmissions as $trans) {
            $wpdb->insert($table, array(
                'code'           => $trans[0],
                'name'           => $trans[1],
                'manufacturer'   => $trans[2],
                'type'           => $trans[3],
                'gears'          => $trans[4],
                'description'    => $trans[5],
                'repair_price'   => $trans[6],
                'amayama_code'   => $trans[7],
                'transakpp_url'  => $trans[8],
                'manual_url'     => $trans[9],
            ));
        }
    }
    
    public function add_transmission() {
        check_ajax_referer(AKPP_CRM_NONCE, 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Нет доступа'));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'akpp_transmissions';
        
        $data = array(
            'code'           => sanitize_text_field($_POST['code'] ?? ''),
            'name'           => sanitize_text_field($_POST['name'] ?? ''),
            'manufacturer'   => sanitize_text_field($_POST['manufacturer'] ?? ''),
            'type'           => sanitize_text_field($_POST['type'] ?? ''),
            'gears'          => intval($_POST['gears'] ?? 0),
            'description'    => sanitize_textarea_field($_POST['description'] ?? ''),
            'repair_price'   => floatval($_POST['repair_price'] ?? 0),
            'amayama_code'   => sanitize_text_field($_POST['amayama_code'] ?? ''),
            'transakpp_url'  => esc_url_raw($_POST['transakpp_url'] ?? ''),
            'manual_url'     => esc_url_raw($_POST['manual_url'] ?? ''),
        );
        
        if (empty($data['code']) || empty($data['name'])) {
            wp_send_json_error(array('message' => 'Заполните обязательные поля'));
        }
        
        $result = $wpdb->insert($table, $data);
        
        if ($result) {
            wp_send_json_success(array('message' => 'АКПП добавлена', 'id' => $wpdb->insert_id));
        } else {
            wp_send_json_error(array('message' => 'Ошибка добавления'));
        }
    }
    
    public function edit_transmission() {
        check_ajax_referer(AKPP_CRM_NONCE, 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Нет доступа'));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'akpp_transmissions';
        
        $id = intval($_POST['id'] ?? 0);
        
        $data = array(
            'code'           => sanitize_text_field($_POST['code'] ?? ''),
            'name'           => sanitize_text_field($_POST['name'] ?? ''),
            'manufacturer'   => sanitize_text_field($_POST['manufacturer'] ?? ''),
            'type'           => sanitize_text_field($_POST['type'] ?? ''),
            'gears'          => intval($_POST['gears'] ?? 0),
            'description'    => sanitize_textarea_field($_POST['description'] ?? ''),
            'repair_price'   => floatval($_POST['repair_price'] ?? 0),
            'amayama_code'   => sanitize_text_field($_POST['amayama_code'] ?? ''),
            'transakpp_url'  => esc_url_raw($_POST['transakpp_url'] ?? ''),
            'manual_url'     => esc_url_raw($_POST['manual_url'] ?? ''),
        );
        
        if ($id <= 0) {
            wp_send_json_error(array('message' => 'Неверный ID'));
        }
        
        $result = $wpdb->update($table, $data, array('id' => $id));
        
        if ($result !== false) {
            wp_send_json_success(array('message' => 'АКПП обновлена'));
        } else {
            wp_send_json_error(array('message' => 'Ошибка обновления'));
        }
    }
    
    public function delete_transmission() {
        check_ajax_referer(AKPP_CRM_NONCE, 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Нет доступа'));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'akpp_transmissions';
        
        $id = intval($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            wp_send_json_error(array('message' => 'Неверный ID'));
        }
        
        $result = $wpdb->delete($table, array('id' => $id));
        
        if ($result) {
            wp_send_json_success(array('message' => 'АКПП удалена'));
        } else {
            wp_send_json_error(array('message' => 'Ошибка удаления'));
        }
    }
}

new AKPP_Transmissions();