<?php
/**
 * CRM Main Class - Главный класс CRM
 */

if (!defined('ABSPATH')) {
    exit;
}

class AKPP_CRM_Main {
    
    public function __construct() {
        // Подключаем все классы
        $this->load_classes();
        
        // Регистрируем меню
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Подключаем стили и скрипты
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Регистрируем AJAX обработчики
        $this->register_ajax_handlers();
    }
    
    /**
     * Загрузка всех классов CRM
     */
    private function load_classes() {
        $classes = array(
            'class-crm-employees.php',
            'class-crm-deals.php',
            'class-crm-vehicles.php',
            'class-crm-transmissions.php',
            'class-crm-vin-decoder.php',
            'class-crm-amayama.php',
            'class-crm-transakpp.php',
        );
        
        foreach ($classes as $class) {
            $file = get_template_directory() . '/inc/crm/' . $class;
            if (file_exists($file)) {
                require_once $file;
            }
        }
    }
    
    /**
     * Регистрация AJAX обработчиков
     */
    private function register_ajax_handlers() {
        // Сделки
        add_action('wp_ajax_akpp_save_deal', array($this, 'ajax_save_deal'));
        add_action('wp_ajax_akpp_get_deal', array($this, 'ajax_get_deal'));
        add_action('wp_ajax_akpp_delete_deal', array($this, 'ajax_delete_deal'));
        
        // Сотрудники
        add_action('wp_ajax_akpp_save_employee', array($this, 'ajax_save_employee'));
        add_action('wp_ajax_akpp_delete_employee', array($this, 'ajax_delete_employee'));
        
        // Автомобили
        add_action('wp_ajax_akpp_save_vehicle', array($this, 'ajax_save_vehicle'));
        add_action('wp_ajax_akpp_delete_vehicle', array($this, 'ajax_delete_vehicle'));
        
        // АКПП
        add_action('wp_ajax_akpp_save_transmission', array($this, 'ajax_save_transmission'));
        add_action('wp_ajax_akpp_delete_transmission', array($this, 'ajax_delete_transmission'));
        
        // VIN декодер
        add_action('wp_ajax_akpp_decode_vin', array($this, 'ajax_decode_vin'));
        add_action('wp_ajax_akpp_decode_body', array($this, 'ajax_decode_body'));
        
        // Получение моделей по марке
        add_action('wp_ajax_akpp_get_models_by_brand', array($this, 'ajax_get_models_by_brand'));
        
        // Amayama
        add_action('wp_ajax_akpp_amayama_search', array($this, 'ajax_amayama_search'));
        add_action('wp_ajax_akpp_amayama_get_catalog', array($this, 'ajax_amayama_get_catalog'));
        
        // TransAKPP
        add_action('wp_ajax_akpp_transakpp_get_info', array($this, 'ajax_transakpp_get_info'));
        add_action('wp_ajax_akpp_transakpp_get_schemes', array($this, 'ajax_transakpp_get_schemes'));
    }
    
    /**
     * Подключение стилей и скриптов для админки
     */
    public function enqueue_admin_assets($hook) {
        // Проверяем что мы на странице CRM
        if (strpos($hook, 'akpp-') === false) {
            return;
        }
        
        // Стили
        wp_enqueue_style(
            'akpp-crm-admin',
            get_template_directory_uri() . '/inc/crm/assets/css/crm-admin.css',
            array(),
            '1.0.0'
        );
        
        // Скрипты
        wp_enqueue_script(
            'akpp-crm-admin',
            get_template_directory_uri() . '/inc/crm/assets/js/crm-admin.js',
            array('jquery'),
            '1.0.0',
            true
        );
        
        // Передаем переменные в JavaScript
        wp_localize_script('akpp-crm-admin', 'akppCrm', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('akpp_crm_nonce'),
        ));
    }
    
    /**
     * Добавление меню админки
     */
    public function add_admin_menu() {
        add_menu_page(
            'CRM АКПП',
            'CRM АКПП',
            'manage_options',
            'akpp-crm',
            array($this, 'render_dashboard'),
            'dashicons-dashboard',
            3
        );
        
        add_submenu_page(
            'akpp-crm',
            'Панель управления',
            '📊 Панель',
            'manage_options',
            'akpp-crm',
            array($this, 'render_dashboard')
        );
        
        add_submenu_page(
            'akpp-crm',
            'Сделки',
            '📋 Сделки',
            'manage_options',
            'akpp-deals',
            array($this, 'render_deals')
        );
        
        add_submenu_page(
            'akpp-crm',
            'Новая сделка',
            '➕ Новая сделка',
            'manage_options',
            'akpp-new-deal',
            array($this, 'render_new_deal')
        );
        
        add_submenu_page(
            'akpp-crm',
            'Сотрудники',
            '👥 Сотрудники',
            'manage_options',
            'akpp-employees',
            array($this, 'render_employees')
        );
        
        add_submenu_page(
            'akpp-crm',
            'Автомобили',
            '🚗 Авто',
            'manage_options',
            'akpp-vehicles',
            array($this, 'render_vehicles')
        );
        
        add_submenu_page(
            'akpp-crm',
            'АКПП',
            '⚙️ АКПП',
            'manage_options',
            'akpp-transmissions',
            array($this, 'render_transmissions')
        );
    }
    
    /**
     * Рендер страницы панели управления
     */
    public function render_dashboard() {
        include get_template_directory() . '/inc/crm/templates/dashboard.php';
    }
    
    /**
     * Рендер страницы сделок
     */
    public function render_deals() {
        include get_template_directory() . '/inc/crm/templates/deals.php';
    }
    
    /**
     * Рендер страницы новой сделки
     */
    public function render_new_deal() {
        include get_template_directory() . '/inc/crm/templates/deal-form.php';
    }
    
    /**
     * Рендер страницы сотрудников
     */
    public function render_employees() {
        include get_template_directory() . '/inc/crm/templates/employees.php';
    }
    
    /**
     * Рендер страницы автомобилей
     */
    public function render_vehicles() {
        include get_template_directory() . '/inc/crm/templates/vehicles.php';
    }
    
    /**
     * Рендер страницы АКПП
     */
    public function render_transmissions() {
        include get_template_directory() . '/inc/crm/templates/transmissions.php';
    }
    
    // ============================================
    // AJAX ОБРАБОТЧИКИ
    // ============================================
    
    /**
     * Сохранение сделки
     */
    public function ajax_save_deal() {
        check_ajax_referer('akpp_crm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Недостаточно прав'));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'akpp_deals';
        
        $data = array(
            'client_name' => sanitize_text_field($_POST['client_name'] ?? ''),
            'client_phone' => sanitize_text_field($_POST['client_phone'] ?? ''),
            'client_vin' => sanitize_text_field($_POST['client_vin'] ?? ''),
            'client_body_number' => sanitize_text_field($_POST['client_body_number'] ?? ''),
            'client_plate' => sanitize_text_field($_POST['client_plate'] ?? ''),
            'car_brand' => sanitize_text_field($_POST['car_brand'] ?? ''),
            'car_model' => sanitize_text_field($_POST['car_model'] ?? ''),
            'car_year' => intval($_POST['car_year'] ?? 0),
            'transmission_code' => sanitize_text_field($_POST['transmission_code'] ?? ''),
            'employee_id' => intval($_POST['employee_id'] ?? 0),
            'problem_description' => sanitize_textarea_field($_POST['problem_description'] ?? ''),
            'parts_cost' => floatval($_POST['parts_cost'] ?? 0),
            'employee_payment' => floatval($_POST['employee_payment'] ?? 0),
            'deal_price' => floatval($_POST['deal_price'] ?? 0),
            'status' => sanitize_text_field($_POST['status'] ?? 'new'),
            'created_at' => current_time('mysql'),
        );
        
        $deal_id = intval($_POST['deal_id'] ?? 0);
        
        if ($deal_id > 0) {
            // Обновление
            $result = $wpdb->update($table, $data, array('id' => $deal_id));
            if ($result !== false) {
                wp_send_json_success(array('message' => 'Сделка обновлена', 'deal_id' => $deal_id));
            } else {
                wp_send_json_error(array('message' => 'Ошибка обновления'));
            }
        } else {
            // Создание
            $result = $wpdb->insert($table, $data);
            if ($result) {
                wp_send_json_success(array('message' => 'Сделка создана', 'deal_id' => $wpdb->insert_id));
            } else {
                wp_send_json_error(array('message' => 'Ошибка создания'));
            }
        }
    }
    
    /**
     * Получение сделки
     */
    public function ajax_get_deal() {
        check_ajax_referer('akpp_crm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Недостаточно прав'));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'akpp_deals';
        $deal_id = intval($_POST['deal_id'] ?? 0);
        
        $deal = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $deal_id
        ), ARRAY_A);
        
        if ($deal) {
            wp_send_json_success($deal);
        } else {
            wp_send_json_error(array('message' => 'Сделка не найдена'));
        }
    }
    
    /**
     * Удаление сделки
     */
    public function ajax_delete_deal() {
        check_ajax_referer('akpp_crm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Недостаточно прав'));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'akpp_deals';
        $deal_id = intval($_POST['deal_id'] ?? 0);
        
        $result = $wpdb->delete($table, array('id' => $deal_id));
        
        if ($result) {
            wp_send_json_success(array('message' => 'Сделка удалена'));
        } else {
            wp_send_json_error(array('message' => 'Ошибка удаления'));
        }
    }
    
    /**
     * Сохранение сотрудника
     */
    public function ajax_save_employee() {
        check_ajax_referer('akpp_crm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Недостаточно прав'));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'akpp_employees';
        
        $data = array(
            'name' => sanitize_text_field($_POST['name'] ?? ''),
            'position' => sanitize_text_field($_POST['position'] ?? ''),
            'phone' => sanitize_text_field($_POST['phone'] ?? ''),
            'salary_type' => sanitize_text_field($_POST['salary_type'] ?? 'percent'),
            'salary_percent' => floatval($_POST['salary_percent'] ?? 0),
            'fixed_salary' => floatval($_POST['fixed_salary'] ?? 0),
            'is_active' => intval($_POST['is_active'] ?? 1),
        );
        
        $employee_id = intval($_POST['employee_id'] ?? 0);
        
        if ($employee_id > 0) {
            $result = $wpdb->update($table, $data, array('id' => $employee_id));
            if ($result !== false) {
                wp_send_json_success(array('message' => 'Сотрудник обновлен'));
            } else {
                wp_send_json_error(array('message' => 'Ошибка обновления'));
            }
        } else {
            $result = $wpdb->insert($table, $data);
            if ($result) {
                wp_send_json_success(array('message' => 'Сотрудник добавлен', 'employee_id' => $wpdb->insert_id));
            } else {
                wp_send_json_error(array('message' => 'Ошибка добавления'));
            }
        }
    }
    
    /**
     * Удаление сотрудника
     */
    public function ajax_delete_employee() {
        check_ajax_referer('akpp_crm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Недостаточно прав'));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'akpp_employees';
        $employee_id = intval($_POST['employee_id'] ?? 0);
        
        $result = $wpdb->delete($table, array('id' => $employee_id));
        
        if ($result) {
            wp_send_json_success(array('message' => 'Сотрудник удален'));
        } else {
            wp_send_json_error(array('message' => 'Ошибка удаления'));
        }
    }
    
    /**
     * Сохранение автомобиля
     */
    public function ajax_save_vehicle() {
        check_ajax_referer('akpp_crm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Недостаточно прав'));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'akpp_vehicles';
        
        $data = array(
            'brand' => sanitize_text_field($_POST['brand'] ?? ''),
            'model' => sanitize_text_field($_POST['model'] ?? ''),
            'year_from' => intval($_POST['year_from'] ?? 0),
            'year_to' => intval($_POST['year_to'] ?? 0),
            'transmission_code' => sanitize_text_field($_POST['transmission_code'] ?? ''),
            'engine' => sanitize_text_field($_POST['engine'] ?? ''),
            'drive_type' => sanitize_text_field($_POST['drive_type'] ?? ''),
        );
        
        $vehicle_id = intval($_POST['vehicle_id'] ?? 0);
        
        if ($vehicle_id > 0) {
            $result = $wpdb->update($table, $data, array('id' => $vehicle_id));
            if ($result !== false) {
                wp_send_json_success(array('message' => 'Автомобиль обновлен'));
            } else {
                wp_send_json_error(array('message' => 'Ошибка обновления'));
            }
        } else {
            $result = $wpdb->insert($table, $data);
            if ($result) {
                wp_send_json_success(array('message' => 'Автомобиль добавлен', 'vehicle_id' => $wpdb->insert_id));
            } else {
                wp_send_json_error(array('message' => 'Ошибка добавления'));
            }
        }
    }
    
    /**
     * Удаление автомобиля
     */
    public function ajax_delete_vehicle() {
        check_ajax_referer('akpp_crm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Недостаточно прав'));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'akpp_vehicles';
        $vehicle_id = intval($_POST['vehicle_id'] ?? 0);
        
        $result = $wpdb->delete($table, array('id' => $vehicle_id));
        
        if ($result) {
            wp_send_json_success(array('message' => 'Автомобиль удален'));
        } else {
            wp_send_json_error(array('message' => 'Ошибка удаления'));
        }
    }
    
    /**
     * Сохранение АКПП
     */
    public function ajax_save_transmission() {
        check_ajax_referer('akpp_crm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Недостаточно прав'));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'akpp_transmissions';
        
        $data = array(
            'code' => sanitize_text_field($_POST['code'] ?? ''),
            'name' => sanitize_text_field($_POST['name'] ?? ''),
            'manufacturer' => sanitize_text_field($_POST['manufacturer'] ?? ''),
            'type' => sanitize_text_field($_POST['type'] ?? ''),
            'gears' => intval($_POST['gears'] ?? 0),
            'description' => sanitize_textarea_field($_POST['description'] ?? ''),
            'repair_price' => floatval($_POST['repair_price'] ?? 0),
        );
        
        $transmission_id = intval($_POST['transmission_id'] ?? 0);
        
        if ($transmission_id > 0) {
            $result = $wpdb->update($table, $data, array('id' => $transmission_id));
            if ($result !== false) {
                wp_send_json_success(array('message' => 'АКПП обновлена'));
            } else {
                wp_send_json_error(array('message' => 'Ошибка обновления'));
            }
        } else {
            $result = $wpdb->insert($table, $data);
            if ($result) {
                wp_send_json_success(array('message' => 'АКПП добавлена', 'transmission_id' => $wpdb->insert_id));
            } else {
                wp_send_json_error(array('message' => 'Ошибка добавления'));
            }
        }
    }
    
    /**
     * Удаление АКПП
     */
    public function ajax_delete_transmission() {
        check_ajax_referer('akpp_crm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Недостаточно прав'));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'akpp_transmissions';
        $transmission_id = intval($_POST['transmission_id'] ?? 0);
        
        $result = $wpdb->delete($table, array('id' => $transmission_id));
        
        if ($result) {
            wp_send_json_success(array('message' => 'АКПП удалена'));
        } else {
            wp_send_json_error(array('message' => 'Ошибка удаления'));
        }
    }
    
    /**
     * Расшифровка VIN
     */
    public function ajax_decode_vin() {
        check_ajax_referer('akpp_crm_nonce', 'nonce');
        
        $vin = strtoupper(sanitize_text_field($_POST['vin'] ?? ''));
        
        if (strlen($vin) !== 17) {
            wp_send_json_error(array('message' => 'VIN должен содержать 17 символов'));
        }
        
        // Используем NHTSA API
        $response = wp_remote_get("https://vpic.nhtsa.dot.gov/api/vehicles/DecodeVin/{$vin}?format=json");
        
        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => 'Ошибка подключения к API'));
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (empty($data['Results'])) {
            wp_send_json_error(array('message' => 'VIN не найден'));
        }
        
        $result = array();
        foreach ($data['Results'] as $item) {
            $result[$item['Variable']] = $item['Value'];
        }
        
        wp_send_json_success(array(
            'vin' => $vin,
            'make' => $result['Make'] ?? '',
            'model' => $result['Model'] ?? '',
            'year' => $result['Model Year'] ?? '',
            'engine' => ($result['Engine Configuration'] ?? '') . ' ' . ($result['Displacement (L)'] ?? '') . 'L',
            'drive_type' => $result['Drive Type'] ?? '',
            'transmission' => $result['Transmission Style'] ?? '',
        ));
    }
    
    /**
     * Расшифровка номера кузова
     */
    public function ajax_decode_body() {
        check_ajax_referer('akpp_crm_nonce', 'nonce');
        
        $body_number = strtoupper(sanitize_text_field($_POST['body_number'] ?? ''));
        
        if (empty($body_number)) {
            wp_send_json_error(array('message' => 'Введите номер кузова'));
        }
        
        // Парсинг номера кузова Toyota/Lexus
        if (preg_match('/^([A-Z]{3})(\d{2,3})[-]?(\d+)$/', $body_number, $matches)) {
            $model_code = $matches[1] . $matches[2];
            
            // Определяем марку по коду
            $brand = 'Toyota';
            if (strpos($model_code, 'UZZ') === 0 || strpos($model_code, 'UZX') === 0) {
                $brand = 'Lexus';
            }
            
            wp_send_json_success(array(
                'body_number' => $body_number,
                'model_code' => $model_code,
                'brand' => $brand,
                'model' => 'Unknown',
            ));
        }
        
        wp_send_json_error(array('message' => 'Не удалось расшифровать номер кузова'));
    }
    
    /**
     * Получение моделей по марке
     */
    public function ajax_get_models_by_brand() {
        check_ajax_referer('akpp_crm_nonce', 'nonce');
        
        global $wpdb;
        $table = $wpdb->prefix . 'akpp_vehicles';
        $brand = sanitize_text_field($_POST['brand'] ?? '');
        
        if (empty($brand)) {
            wp_send_json_error(array('message' => 'Укажите марку'));
        }
        
        $models = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT model FROM $table WHERE brand = %s ORDER BY model ASC",
            $brand
        ));
        
        wp_send_json_success($models);
    }
    
    /**
     * Поиск в Amayama
     */
    public function ajax_amayama_search() {
        check_ajax_referer('akpp_crm_nonce', 'nonce');
        
        $query = sanitize_text_field($_POST['query'] ?? '');
        
        if (empty($query)) {
            wp_send_json_error(array('message' => 'Введите поисковый запрос'));
        }
        
        $url = 'https://www.amayama.com/ru/search?code=' . urlencode($query);
        
        wp_send_json_success(array('url' => $url));
    }
    
    /**
     * Получение каталога Amayama
     */
    public function ajax_amayama_get_catalog() {
        check_ajax_referer('akpp_crm_nonce', 'nonce');
        
        $brand = sanitize_text_field($_POST['brand'] ?? '');
        $model = sanitize_text_field($_POST['model'] ?? '');
        
        if (empty($brand) || empty($model)) {
            wp_send_json_error(array('message' => 'Укажите марку и модель'));
        }
        
        $brand_slug = strtolower(str_replace(' ', '-', $brand));
        $model_slug = strtolower(str_replace(' ', '-', $model));
        
        $url = "https://www.amayama.com/ru/catalog/{$brand_slug}/{$model_slug}/";
        
        wp_send_json_success(array('url' => $url));
    }
    
    /**
     * Получение информации из TransAKPP
     */
    public function ajax_transakpp_get_info() {
        check_ajax_referer('akpp_crm_nonce', 'nonce');
        
        $code = sanitize_text_field($_POST['code'] ?? '');
        
        if (empty($code)) {
            wp_send_json_error(array('message' => 'Укажите код АКПП'));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'akpp_transmissions';
        
        $transmission = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE code = %s",
            $code
        ), ARRAY_A);
        
        if ($transmission) {
            wp_send_json_success($transmission);
        } else {
            wp_send_json_error(array('message' => 'АКПП не найдена'));
        }
    }
    
    /**
     * Получение схем из TransAKPP
     */
    public function ajax_transakpp_get_schemes() {
        check_ajax_referer('akpp_crm_nonce', 'nonce');
        
        $code = sanitize_text_field($_POST['code'] ?? '');
        
        if (empty($code)) {
            wp_send_json_error(array('message' => 'Укажите код АКПП'));
        }
        
        $schemes = array(
            'exploded' => "https://www.transakpp.ru/schemes/{$code}/exploded",
            'hydraulic' => "https://www.transakpp.ru/schemes/{$code}/hydraulic",
            'electrical' => "https://www.transakpp.ru/schemes/{$code}/electrical",
        );
        
        wp_send_json_success($schemes);
    }
}

// Инициализация
new AKPP_CRM_Main();