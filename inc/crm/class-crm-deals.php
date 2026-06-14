<?php
/**
 * CRM Deals Management
 * @package AKPP_Kurgan
 * @version 2.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class AKPP_CRM_Deals {
    
    public function __construct() {
        add_action('wp_ajax_akpp_save_deal', array($this, 'save_deal'));
        add_action('wp_ajax_akpp_get_deal', array($this, 'get_deal'));
        add_action('wp_ajax_akpp_delete_deal', array($this, 'delete_deal'));
        add_action('wp_ajax_akpp_get_deals', array($this, 'get_deals'));
        add_action('wp_ajax_akpp_get_models_by_brand', array($this, 'get_models_by_brand'));
        add_action('wp_ajax_akpp_decode_vin', array($this, 'decode_vin'));
        add_action('wp_ajax_akpp_decode_body_number', array($this, 'decode_body_number'));
        add_action('wp_ajax_akpp_get_transmission_by_vehicle', array($this, 'get_transmission_by_vehicle'));
        add_action('wp_ajax_akpp_add_vehicle_to_catalog', array($this, 'add_vehicle_to_catalog'));
    }
    
    /**
     * Сохранение сделки (создание или обновление)
     */
    public function save_deal() {
        check_ajax_referer('akpp_crm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Недостаточно прав'));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'akpp_deals';
        
        $deal_id = intval($_POST['deal_id'] ?? 0);
        
        $data = array(
            'client_name'          => sanitize_text_field($_POST['client_name'] ?? ''),
            'client_phone'         => sanitize_text_field($_POST['client_phone'] ?? ''),
            'client_vin'           => strtoupper(sanitize_text_field($_POST['client_vin'] ?? '')),
            'client_body_number'   => strtoupper(sanitize_text_field($_POST['client_body_number'] ?? '')),
            'client_plate'         => strtoupper(sanitize_text_field($_POST['client_plate'] ?? '')),
            'car_brand'            => sanitize_text_field($_POST['car_brand'] ?? ''),
            'car_model'            => sanitize_text_field($_POST['car_model'] ?? ''),
            'car_year'             => intval($_POST['car_year'] ?? 0),
            'transmission_code'    => sanitize_text_field($_POST['transmission_code'] ?? ''),
            'employee_id'          => intval($_POST['employee_id'] ?? 0),
            'problem_description'  => sanitize_textarea_field($_POST['problem_description'] ?? ''),
            'parts_cost'           => floatval($_POST['parts_cost'] ?? 0),
            'employee_payment'     => floatval($_POST['employee_payment'] ?? 0),
            'deal_price'           => floatval($_POST['deal_price'] ?? 0),
            'status'               => sanitize_text_field($_POST['status'] ?? 'new'),
        );
        
        // Обработка дат
        if (!empty($_POST['received_date'])) {
            $data['received_date'] = sanitize_text_field($_POST['received_date']);
        }
        if (!empty($_POST['completed_date'])) {
            $data['completed_date'] = sanitize_text_field($_POST['completed_date']);
        }
        if (!empty($_POST['notes'])) {
            $data['notes'] = sanitize_textarea_field($_POST['notes']);
        }
        
        // Валидация
        if (empty($data['client_name'])) {
            wp_send_json_error(array('message' => 'Введите ФИО клиента'));
        }
        if (empty($data['client_phone'])) {
            wp_send_json_error(array('message' => 'Введите телефон клиента'));
        }
        
        if ($deal_id > 0) {
            $data['updated_at'] = current_time('mysql');
            $result = $wpdb->update($table, $data, array('id' => $deal_id));
            
            if ($result !== false) {
                wp_send_json_success(array('message' => 'Сделка обновлена', 'deal_id' => $deal_id));
            } else {
                wp_send_json_error(array('message' => 'Ошибка обновления: ' . $wpdb->last_error));
            }
        } else {
            $data['created_at'] = current_time('mysql');
            $result = $wpdb->insert($table, $data);
            
            if ($result) {
                wp_send_json_success(array('message' => 'Сделка создана', 'deal_id' => $wpdb->insert_id));
            } else {
                wp_send_json_error(array('message' => 'Ошибка создания: ' . $wpdb->last_error));
            }
        }
    }
    
    /**
     * Получение списка сделок — ИСПРАВЛЕНО: убран prepare() без плейсхолдеров
     */
    public function get_deals() {
        check_ajax_referer('akpp_crm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Недостаточно прав'));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'akpp_deals';
        
        $status = sanitize_text_field($_POST['status'] ?? '');
        $search = sanitize_text_field($_POST['search'] ?? '');
        $page = intval($_POST['page'] ?? 1);
        $per_page = 50;
        $offset = ($page - 1) * $per_page;
        
        // ИСПРАВЛЕНО: разделяем логику — с фильтрами и без
        $has_filters = !empty($status) || !empty($search);
        
        if (!$has_filters) {
            // БЕЗ ФИЛЬТРОВ — прямой запрос без prepare()
            $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table");
            $deals = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC LIMIT $per_page OFFSET $offset");
        } else {
            // С ФИЛЬТРАМИ — собираем плейсхолдеры
            $where = array('1=1');
            $params = array();
            
            if (!empty($status)) {
                $where[] = 'status = %s';
                $params[] = $status;
            }
            
            if (!empty($search)) {
                $where[] = '(client_name LIKE %s OR client_phone LIKE %s OR client_vin LIKE %s)';
                $search_like = '%' . $wpdb->esc_like($search) . '%';
                $params[] = $search_like;
                $params[] = $search_like;
                $params[] = $search_like;
            }
            
            $where_clause = implode(' AND ', $where);
            
            // COUNT с плейсхолдерами
            $total = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE $where_clause",
                $params
            ));
            
            // Данные с плейсхолдерами
            $data_params = array_merge($params, array($per_page, $offset));
            $deals = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table WHERE $where_clause ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $data_params
            ));
        }
        
        wp_send_json_success(array(
            'deals'    => $deals ? $deals : array(),
            'total'    => $total,
            'page'     => $page,
            'per_page' => $per_page,
            'pages'    => ceil($total / $per_page)
        ));
    }
    
    /**
     * Получение одной сделки по ID
     */
    public function get_deal() {
        check_ajax_referer('akpp_crm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Недостаточно прав'));
        }
        
        global $wpdb;
        $deal_id = intval($_POST['deal_id'] ?? 0);
        
        if ($deal_id <= 0) {
            wp_send_json_error(array('message' => 'Неверный ID'));
        }
        
        $deal = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}akpp_deals WHERE id = %d",
            $deal_id
        ));
        
        if ($deal) {
            wp_send_json_success($deal);
        } else {
            wp_send_json_error(array('message' => 'Сделка не найдена'));
        }
    }
    
    /**
     * Удаление сделки
     */
    public function delete_deal() {
        check_ajax_referer('akpp_crm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Недостаточно прав'));
        }
        
        global $wpdb;
        $deal_id = intval($_POST['deal_id'] ?? 0);
        
        if ($deal_id <= 0) {
            wp_send_json_error(array('message' => 'Неверный ID'));
        }
        
        $result = $wpdb->delete($wpdb->prefix . 'akpp_deals', array('id' => $deal_id));
        
        if ($result) {
            wp_send_json_success(array('message' => 'Сделка удалена'));
        } else {
            wp_send_json_error(array('message' => 'Ошибка удаления'));
        }
    }
    
    /**
     * Получение моделей по марке автомобиля
     */
    public function get_models_by_brand() {
        check_ajax_referer('akpp_crm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Недостаточно прав'));
        }
        
        global $wpdb;
        $brand = sanitize_text_field($_POST['brand'] ?? '');
        
        if (empty($brand)) {
            wp_send_json_error(array('message' => 'Не указана марка'));
        }
        
        $models = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT model FROM {$wpdb->prefix}akpp_vehicles WHERE brand = %s ORDER BY model ASC",
            $brand
        ));
        
        wp_send_json_success($models ? $models : array());
    }
    
    /**
     * Декодирование VIN номера через NHTSA API
     */
    public function decode_vin() {
        check_ajax_referer('akpp_crm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Недостаточно прав'));
        }
        
        $vin = strtoupper(sanitize_text_field($_POST['vin'] ?? ''));
        
        if (strlen($vin) !== 17) {
            wp_send_json_error(array('message' => 'VIN должен содержать ровно 17 символов'));
        }
        
        $api_url = "https://vpic.nhtsa.dot.gov/api/vehicles/DecodeVin/{$vin}?format=json";
        
        $response = wp_remote_get($api_url, array(
            'timeout'    => 15,
            'user-agent' => 'AKPP45-CRM/1.0'
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => 'Ошибка API: ' . $response->get_error_message()));
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (empty($data['Results'])) {
            wp_send_json_error(array('message' => 'VIN не распознан'));
        }
        
        // Извлекаем данные из ответа NHTSA
        $decoded = array();
        foreach ($data['Results'] as $item) {
            if (!empty($item['Value'])) {
                $decoded[$item['Variable']] = $item['Value'];
            }
        }
        
        $make  = $decoded['Make'] ?? '';
        $model = $decoded['Model'] ?? '';
        $year  = $decoded['Model Year'] ?? '';
        
        $result = array(
            'vin'          => $vin,
            'make'         => $make,
            'model'        => $model,
            'year'         => $year,
            'engine'       => trim(($decoded['Engine Configuration'] ?? '') . ' ' . ($decoded['Displacement (L)'] ?? '') . 'L'),
            'drive_type'   => $decoded['Drive Type'] ?? '',
            'transmission' => $decoded['Transmission Style'] ?? '',
        );
        
        // Ищем подходящую АКПП в базе
        if (!empty($make) && !empty($model) && !empty($year)) {
            $transmission = $this->find_transmission_for_vehicle($make, $model, intval($year));
            if ($transmission) {
                $result['suggested_transmission'] = $transmission;
            }
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * Декодирование номера кузова — ИСПРАВЛЕНО: логика определения марки
     */
    public function decode_body_number() {
        check_ajax_referer('akpp_crm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Недостаточно прав'));
        }
        
        $body_number = strtoupper(sanitize_text_field($_POST['body_number'] ?? ''));
        
        if (empty($body_number)) {
            wp_send_json_error(array('message' => 'Введите номер кузова'));
        }
        
        // Парсинг формата Toyota/Lexus: XXX###-######
        if (preg_match('/^([A-Z]{3})(\d{2,3})[-]?(\d+)$/i', $body_number, $matches)) {
            $prefix     = strtoupper($matches[1]);
            $model_code = strtoupper($matches[1] . $matches[2]);
            
            // ИСПРАВЛЕНО: правильная логика определения марки
            // Lexus использует UCF, UZS, GSF, GXF префиксы
            $lexus_prefixes = array('UCF', 'UZS', 'GSF', 'GXF');
            if (in_array($prefix, $lexus_prefixes)) {
                $brand = 'Lexus';
            } else {
                $brand = 'Toyota';
            }
            
            // Ищем модель в базе
            $model = $this->find_model_by_code($model_code);
            
            // Ищем АКПП
            $transmission = '';
            if ($model && !empty($_POST['year'])) {
                $transmission = $this->find_transmission_for_vehicle($brand, $model, intval($_POST['year']));
            }
            
            wp_send_json_success(array(
                'body_number'          => $body_number,
                'model_code'           => $model_code,
                'brand'                => $brand,
                'model'                => $model ?: 'Unknown',
                'suggested_transmission' => $transmission,
            ));
        }
        
        wp_send_json_error(array('message' => 'Не удалось распознать номер кузова. Формат: XXX##-######'));
    }
    
    /**
     * Поиск АКПП для автомобиля
     */
    private function find_transmission_for_vehicle($brand, $model, $year) {
        global $wpdb;
        $table = $wpdb->prefix . 'akpp_vehicles';
        
        $transmission = $wpdb->get_var($wpdb->prepare(
            "SELECT transmission_code FROM $table 
             WHERE brand = %s AND model = %s AND year_from <= %d 
             AND (year_to >= %d OR year_to IS NULL OR year_to = 0)
             ORDER BY year_from DESC LIMIT 1",
            $brand, $model, $year, $year
        ));
        
        return $transmission ?: '';
    }
    
    /**
     * Поиск модели по коду кузова
     */
    private function find_model_by_code($model_code) {
        global $wpdb;
        $table = $wpdb->prefix . 'akpp_vehicles';
        
        $model = $wpdb->get_var($wpdb->prepare(
            "SELECT model FROM $table WHERE model LIKE %s LIMIT 1",
            '%' . $wpdb->esc_like($model_code) . '%'
        ));
        
        return $model ?: '';
    }
    
    /**
     * Добавление автомобиля в каталог
     */
    public function add_vehicle_to_catalog() {
        check_ajax_referer('akpp_crm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Недостаточно прав'));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'akpp_vehicles';
        
        $brand           = sanitize_text_field($_POST['brand'] ?? '');
        $model           = sanitize_text_field($_POST['model'] ?? '');
        $year_from       = intval($_POST['year_from'] ?? 2000);
        $year_to         = intval($_POST['year_to'] ?? 2030);
        $transmission_code = sanitize_text_field($_POST['transmission_code'] ?? '');
        
        if (empty($brand) || empty($model)) {
            wp_send_json_error(array('message' => 'Марка и модель обязательны'));
        }
        
        // Проверяем дубликат
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE brand = %s AND model = %s AND year_from = %d",
            $brand, $model, $year_from
        ));
        
        if ($exists) {
            wp_send_json_success(array('message' => 'Модель уже есть в каталоге'));
        }
        
        $result = $wpdb->insert($table, array(
            'brand'           => $brand,
            'model'           => $model,
            'year_from'       => $year_from,
            'year_to'         => $year_to,
            'transmission_code' => $transmission_code,
            'engine'          => '',
            'drive_type'      => '',
        ));
        
        if ($result) {
            wp_send_json_success(array('message' => 'Модель "' . $model . '" добавлена в каталог'));
        } else {
            wp_send_json_error(array('message' => 'Ошибка добавления: ' . $wpdb->last_error));
        }
    }
    
    /**
     * Поиск АКПП по марке/модели/году (AJAX)
     */
    public function get_transmission_by_vehicle() {
        check_ajax_referer('akpp_crm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Недостаточно прав'));
        }
        
        $brand = sanitize_text_field($_POST['brand'] ?? '');
        $model = sanitize_text_field($_POST['model'] ?? '');
        $year  = intval($_POST['year'] ?? 0);
        
        if (empty($brand) || empty($model) || empty($year)) {
            wp_send_json_error(array('message' => 'Недостаточно данных'));
        }
        
        $transmission = $this->find_transmission_for_vehicle($brand, $model, $year);
        
        if ($transmission) {
            wp_send_json_success(array('code' => $transmission));
        } else {
            wp_send_json_error(array('message' => 'АКПП не найдена для этого авто'));
        }
    }
}

new AKPP_CRM_Deals();