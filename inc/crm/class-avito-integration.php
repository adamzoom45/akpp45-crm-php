<?php
/**
 * Avito API Integration
 * @package AKPP_Kurgan
 */

if (!defined('ABSPATH')) exit;

class AKPP_Avito_Integration {
    
    private $auth_url = 'https://api.avito.ru/token';
    private $api_url = 'https://api.avito.ru/core/v1/accounts';
    
    public function __construct() {
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_akpp_avito_sync', array($this, 'ajax_sync'));
        add_action('wp_cron_akpp_avito_sync', array($this, 'auto_sync'));
        
        if (!wp_next_scheduled('wp_cron_akpp_avito_sync')) {
            wp_schedule_event(time(), 'twicedaily', 'wp_cron_akpp_avito_sync');
        }
    }
    
    public function register_settings() {
        register_setting('akpp_integrations', 'akpp_avito_client_id');
        register_setting('akpp_integrations', 'akpp_avito_client_secret');
    }
    
    private function get_token() {
        $client_id = get_option('akpp_avito_client_id', '');
        $secret = get_option('akpp_avito_client_secret', '');
        
        if (empty($client_id) || empty($secret)) return false;
        
        $cached = get_transient('akpp_avito_token');
        if ($cached) return $cached;
        
        $response = wp_remote_post($this->auth_url, array(
            'body' => array(
                'grant_type' => 'client_credentials',
                'client_id' => $client_id,
                'client_secret' => $secret,
            ),
            'timeout' => 15,
        ));
        
        if (is_wp_error($response)) return false;
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($body['access_token'])) return false;
        
        set_transient('akpp_avito_token', $body['access_token'], $body['expires_in'] - 60);
        return $body['access_token'];
    }
    
    public function get_messages() {
        $token = $this->get_token();
        if (!$token) return array('error' => 'Нет токена');
        
        $response = wp_remote_get("{$this->api_url}/messages", array(
            'headers' => array(
                'Authorization' => "Bearer {$token}",
                'Content-Type' => 'application/json',
            ),
            'timeout' => 20,
        ));
        
        if (is_wp_error($response)) return array('error' => $response->get_error_message());
        
        return json_decode(wp_remote_retrieve_body($response), true);
    }
    
    public function auto_sync() {
        $messages = $this->get_messages();
        if (isset($messages['error'])) return;
        
        global $wpdb;
        $table = $wpdb->prefix . 'akpp_leads';
        
        foreach ($messages['messages'] ?? array() as $msg) {
            $avito_id = 'avito_' . ($msg['id'] ?? '');
            
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table WHERE source = 'avito' AND source_id = %s",
                $avito_id
            ));
            
            if ($exists) continue;
            
            $wpdb->insert($table, array(
                'source' => 'avito',
                'source_id' => $avito_id,
                'name' => $msg['author']['name'] ?? 'Клиент Avito',
                'message' => $msg['text'] ?? '',
                'vehicle_info' => $msg['item']['title'] ?? '',
                'status' => 'new',
                'created_at' => $msg['created_at'] ?? current_time('mysql'),
            ));
        }
    }
    
    public function ajax_sync() {
        check_ajax_referer('akpp_crm_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(array('message' => 'Нет доступа'));
        
        $this->auto_sync();
        wp_send_json_success(array('message' => 'Синхронизация завершена'));
    }
}

new AKPP_Avito_Integration();