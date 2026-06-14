<?php
/**
 * VIN Decoder
 * @package AKPP_Kurgan
 */

if (!defined('ABSPATH')) {
    exit;
}

class AKPP_VIN_Decoder {
    
    private $nhtsa_api = 'https://vpic.nhtsa.dot.gov/api/vehicles/DecodeVin/';
    
    public function __construct() {
        add_action('wp_ajax_akpp_decode_vin', array($this, 'decode_vin'));
        add_action('wp_ajax_akpp_decode_body_number', array($this, 'decode_body_number'));
        add_action('wp_ajax_akpp_get_models_by_brand', array($this, 'get_models_by_brand'));
        add_action('wp_ajax_akpp_get_transmission_by_vehicle', array($this, 'get_transmission_by_vehicle'));
    }
    
    public function decode_vin() {
        check_ajax_referer(AKPP_CRM_NONCE, 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Нет доступа'));
        }
        
        $vin = strtoupper(sanitize_text_field($_POST['vin'] ?? ''));
        
        if (strlen($vin) !== 17) {
            wp_send_json_error(array('message' => 'VIN должен содержать 17 символов'));
        }
        
        $response = wp_remote_get($this->nhtsa_api . $vin . '/json', array('timeout' => 15));
        
        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => 'Ошибка подключения к API'));
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (empty($data['Results'])) {
            wp_send_json_error(array('message' => 'VIN не найден в базе'));
        }
        
        $result = array();
        foreach ($data['Results'] as $item) {
            if (!empty($item['Value'])) {
                $result[$item['Variable']] = $item['Value'];
            }
        }
        
        $make = $result['Make'] ?? '';
        $model = $result['Model'] ?? '';
        $year = intval($result['Model Year'] ?? 0);
        
        $transmission = $this->find_transmission_by_vehicle($make, $model, $year);
        
        $decoded = array(
            'vin'                        => $vin,
            'make'                       => $make,
            'model'                      => $model,
            'year'                       => $year,
            'engine'                     => trim(($result['Engine Configuration'] ?? '') . ' ' . ($result['Displacement (L)'] ?? '') . 'L'),
            'cylinders'                  => $result['Cylinders'] ?? '',
            'drive_type'                 => $result['Drive Type'] ?? '',
            'transmission_style'         => $result['Transmission Style'] ?? '',
            'body_class'                 => $result['Body Class'] ?? '',
            'plant_country'              => $result['Plant Country'] ?? '',
            'suggested_transmission'     => $transmission['code'] ?? '',
            'suggested_transmission_name' => $transmission['name'] ?? '',
        );
        
        wp_send_json_success($decoded);
    }
    
    public function decode_body_number() {
        check_ajax_referer(AKPP_CRM_NONCE, 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Нет доступа'));
        }
        
        $body_number = strtoupper(sanitize_text_field($_POST['body_number'] ?? ''));
        
        if (empty($body_number)) {
            wp_send_json_error(array('message' => 'Введите номер кузова'));
        }
        
        if (preg_match('/^([A-Z]{3})(\d{2,3})[-]?(\d+)$/', $body_number, $matches)) {
            $model_code = $matches[1] . $matches[2];
            $serial = $matches[3];
            
            $brand = $this->get_brand_by_model_code($model_code);
            $model = $this->get_model_by_code($model_code);
            
            $transmission = $this->find_transmission_by_vehicle($brand, $model, date('Y'));
            
            wp_send_json_success(array(
                'body_number'              => $body_number,
                'model_code'               => $model_code,
                'serial'                   => $serial,
                'brand'                    => $brand,
                'model'                    => $model,
                'suggested_transmission'   => $transmission['code'] ?? '',
                'suggested_transmission_name' => $transmission['name'] ?? '',
            ));
        }
        
        wp_send_json_error(array('message' => 'Не удалось расшифровать номер кузова'));
    }
    
    public function get_models_by_brand() {
        check_ajax_referer(AKPP_CRM_NONCE, 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Нет доступа'));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'akpp_vehicles';
        
        $brand = sanitize_text_field($_POST['brand'] ?? '');
        
        if (empty($brand)) {
            wp_send_json_error(array('message' => 'Укажите марку'));
        }
        
        $models = $wpdb->get_col($wpdb->prepare("SELECT DISTINCT model FROM $table WHERE brand = %s ORDER BY model ASC", $brand));
        
        wp_send_json_success($models);
    }
    
    public function get_transmission_by_vehicle() {
        check_ajax_referer(AKPP_CRM_NONCE, 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Нет доступа'));
        }
        
        $brand = sanitize_text_field($_POST['brand'] ?? '');
        $model = sanitize_text_field($_POST['model'] ?? '');
        $year = intval($_POST['year'] ?? 0);
        
        $transmission = $this->find_transmission_by_vehicle($brand, $model, $year);
        
        wp_send_json_success($transmission);
    }
    
    private function find_transmission_by_vehicle($brand, $model, $year) {
    global $wpdb;
    $table = $wpdb->prefix . 'akpp_vehicles';
    
    // Проверка входных данных
    if (empty($brand) || empty($model) || empty($year)) {
        return array('code' => '', 'name' => '');
    }
    
    $vehicle = $wpdb->get_row($wpdb->prepare(
        "SELECT transmission_code FROM $table 
         WHERE brand = %s AND model = %s AND year_from <= %d 
         AND (year_to >= %d OR year_to IS NULL OR year_to = 0)
         ORDER BY year_from DESC LIMIT 1",
        $brand, $model, intval($year), intval($year)
    ));
    
    if (!$vehicle || empty($vehicle->transmission_code)) {
        return array('code' => '', 'name' => '');
    }
    
    // Получаем название АКПП
    $trans_table = $wpdb->prefix . 'akpp_transmissions';
    $transmission = $wpdb->get_row($wpdb->prepare(
        "SELECT code, name FROM $trans_table WHERE code = %s",
        $vehicle->transmission_code
    ));
    
    return array(
        'code' => $vehicle->transmission_code,
        'name' => $transmission ? $transmission->name : '',
    );
}
    
    private function get_brand_by_model_code($code) {
        $brands = array(
            'UZZ' => 'Lexus', 'JZZ' => 'Toyota', 'GZZ' => 'Toyota',
            'JZS' => 'Toyota', 'UZX' => 'Lexus', 'JZX' => 'Toyota',
            'GX' => 'Lexus', 'UZ' => 'Lexus', 'JZ' => 'Toyota',
            'GRX' => 'Toyota', 'ARS' => 'Toyota', 'GWS' => 'Toyota',
        );
        
        foreach ($brands as $prefix => $brand) {
            if (strpos($code, $prefix) === 0) {
                return $brand;
            }
        }
        
        return 'Toyota';
    }
    
    private function get_model_by_code($code) {
        $models = array(
            'UZZ30' => 'Soarer', 'UZZ31' => 'Soarer', 'UZZ32' => 'Soarer',
            'JZZ30' => 'Supra', 'JZZ31' => 'Supra',
            'JZX90' => 'Mark II', 'JZX100' => 'Mark II', 'JZX110' => 'Mark II',
            'GX71' => 'Mark II', 'GX81' => 'Mark II',
            'UZS161' => 'Crown', 'UZS186' => 'Crown',
            'GRX130' => 'Crown', 'ARS220' => 'Crown',
            'GWS224' => 'Crown',
        );
        
        return $models[$code] ?? 'Unknown';
    }
}

new AKPP_VIN_Decoder();