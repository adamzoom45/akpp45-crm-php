<?php
/**
 * 2GIS API Integration
 * @package AKPP_Kurgan
 */

if (!defined('ABSPATH')) exit;

class AKPP_2GIS_Integration {
    
    private $api_url = 'https://catalog.api.2gis.com/3.0/items';
    
    public function __construct() {
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_akpp_2gis_sync', array($this, 'ajax_sync_reviews'));
    }
    
    public function register_settings() {
        register_setting('akpp_integrations', 'akpp_2gis_api_key');
        register_setting('akpp_integrations', 'akpp_2gis_firm_id');
    }
    
    public function get_reviews() {
        $api_key = get_option('akpp_2gis_api_key', '');
        $firm_id = get_option('akpp_2gis_firm_id', '');
        
        if (empty($api_key) || empty($firm_id)) return array('error' => 'Нет настроек');
        
        $response = wp_remote_get($this->api_url, array(
            'timeout' => 20,
            'body' => array(
                'key' => $api_key,
                'id' => $firm_id,
                'fields' => 'reviews',
                'lang' => 'ru',
            ),
        ));
        
        if (is_wp_error($response)) return array('error' => $response->get_error_message());
        
        return json_decode(wp_remote_retrieve_body($response), true);
    }
    
    public function ajax_sync_reviews() {
        check_ajax_referer('akpp_crm_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(array('message' => 'Нет доступа'));
        
        $data = $this->get_reviews();
        if (isset($data['error'])) wp_send_json_error(array('message' => $data['error']));
        
        global $wpdb;
        $table = $wpdb->prefix . 'akpp_leads';
        $count = 0;
        
        foreach ($data['result']['items'][0]['reviews'] ?? array() as $review) {
            $source_id = '2gis_' . ($review['id'] ?? '');
            
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table WHERE source = '2gis' AND source_id = %s",
                $source_id
            ));
            
            if ($exists) continue;
            
            // Отзывы с низкой оценкой = потенциальные лиды
            if (($review['score'] ?? 5) <= 3) {
                $wpdb->insert($table, array(
                    'source' => '2gis',
                    'source_id' => $source_id,
                    'name' => $review['author_name'] ?? 'Клиент 2GIS',
                    'message' => $review['text'] ?? '',
                    'status' => 'new',
                    'created_at' => $review['created_at'] ?? current_time('mysql'),
                ));
                $count++;
            }
        }
        
        wp_send_json_success(array('message' => "Импортировано отзывов: {$count}"));
    }
}

new AKPP_2GIS_Integration();