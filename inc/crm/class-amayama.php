<?php
/**
 * Amayama Integration
 * @package AKPP_Kurgan
 */

if (!defined('ABSPATH')) {
    exit;
}

class AKPP_Amayama {
    
    private $base_url = 'https://www.amayama.com';
    
    public function __construct() {
        add_action('wp_ajax_akpp_amayama_search', array($this, 'search'));
        add_action('wp_ajax_akpp_amayama_get_catalog', array($this, 'get_catalog'));
        add_action('wp_ajax_akpp_amayama_get_parts', array($this, 'get_parts'));
    }
    
    public function search() {
        check_ajax_referer(AKPP_CRM_NONCE, 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Нет доступа'));
        }
        
        $query = sanitize_text_field($_POST['query'] ?? '');
        
        if (empty($query)) {
            wp_send_json_error(array('message' => 'Введите поисковый запрос'));
        }
        
        $url = $this->base_url . '/ru/search?code=' . urlencode($query);
        
        wp_send_json_success(array('url' => $url, 'message' => 'Поиск в Amayama'));
    }
    
    public function get_catalog() {
        check_ajax_referer(AKPP_CRM_NONCE, 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Нет доступа'));
        }
        
        $brand = sanitize_text_field($_POST['brand'] ?? '');
        $model = sanitize_text_field($_POST['model'] ?? '');
        
        if (empty($brand) || empty($model)) {
            wp_send_json_error(array('message' => 'Укажите марку и модель'));
        }
        
        $brand_slug = strtolower(str_replace(' ', '-', $brand));
        $model_slug = strtolower(str_replace(' ', '-', $model));
        
        $url = $this->base_url . "/ru/catalog/{$brand_slug}/{$model_slug}/";
        
        wp_send_json_success(array('url' => $url, 'message' => 'Каталог открыт'));
    }
    
    public function get_parts() {
        check_ajax_referer(AKPP_CRM_NONCE, 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Нет доступа'));
        }
        
        $transmission_code = sanitize_text_field($_POST['transmission_code'] ?? '');
        
        if (empty($transmission_code)) {
            wp_send_json_error(array('message' => 'Укажите код АКПП'));
        }
        
        $oem_db = array(
            'A340E' => array('filter' => '35330-30060', 'pan_gasket' => '35168-30010', 'overhaul_kit' => '33000-30520'),
            'A340F' => array('filter' => '35330-30060', 'pan_gasket' => '35168-30010', 'overhaul_kit' => '33000-30530'),
            'A650E' => array('filter' => '35330-30120', 'pan_gasket' => '35168-30050', 'overhaul_kit' => '33000-30650'),
            'U660E' => array('filter' => '35330-30150', 'pan_gasket' => '35168-30080', 'overhaul_kit' => '33000-30820'),
            'U760E' => array('filter' => '35330-30160', 'pan_gasket' => '35168-30090', 'overhaul_kit' => '33000-30840'),
        );
        
        $oem = $oem_db[$transmission_code] ?? array();
        
        $parts = array(
            'filters' => array(
                'name' => 'Масляные фильтры',
                'items' => array(
                    array('name' => 'Фильтр основной', 'oem' => $oem['filter'] ?? '', 'amayama_url' => $this->base_url . '/ru/search?code=' . urlencode($oem['filter'] ?? '')),
                    array('name' => 'Фильтр дополнительный', 'oem' => ($oem['filter'] ?? '') . '-SUB', 'amayama_url' => $this->base_url . '/ru/search?code=' . urlencode(($oem['filter'] ?? '') . '-SUB')),
                ),
            ),
            'gaskets' => array(
                'name' => 'Прокладки и сальники',
                'items' => array(
                    array('name' => 'Прокладка поддона', 'oem' => $oem['pan_gasket'] ?? '', 'amayama_url' => $this->base_url . '/ru/search?code=' . urlencode($oem['pan_gasket'] ?? '')),
                    array('name' => 'Overhaul Kit', 'oem' => $oem['overhaul_kit'] ?? '', 'amayama_url' => $this->base_url . '/ru/search?code=' . urlencode($oem['overhaul_kit'] ?? '')),
                ),
            ),
            'frictions' => array(
                'name' => 'Фрикционы',
                'items' => array(
                    array('name' => 'Фрикцион C1', 'oem' => $transmission_code . '-C1'),
                    array('name' => 'Фрикцион C2', 'oem' => $transmission_code . '-C2'),
                    array('name' => 'Фрикцион B1', 'oem' => $transmission_code . '-B1'),
                    array('name' => 'Фрикцион B2', 'oem' => $transmission_code . '-B2'),
                ),
            ),
            'solenoids' => array(
                'name' => 'Соленоиды',
                'items' => array(
                    array('name' => 'Shift A', 'oem' => $transmission_code . '-SOL-A'),
                    array('name' => 'Shift B', 'oem' => $transmission_code . '-SOL-B'),
                    array('name' => 'Lock-up', 'oem' => $transmission_code . '-SOL-LU'),
                    array('name' => 'Line Pressure', 'oem' => $transmission_code . '-SOL-LP'),
                ),
            ),
            'torque_converter' => array(
                'name' => 'Гидротрансформатор',
                'items' => array(
                    array('name' => 'ГДТ в сборе', 'oem' => $transmission_code . '-TC'),
                    array('name' => 'Ремкомплект ГДТ', 'oem' => $transmission_code . '-TC-KIT'),
                ),
            ),
        );
        
        wp_send_json_success($parts);
    }
}

new AKPP_Amayama();