<?php
/**
 * CRM Integrations - Telegram, Avito, 2GIS
 * @package AKPP_Kurgan
 */

if (!defined('ABSPATH')) exit;

class AKPP_Integrations {
    
    public function __construct() {
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_akpp_test_telegram', array($this, 'ajax_test_telegram'));
        add_action('wp_ajax_akpp_avito_sync', array($this, 'ajax_avito_sync'));
        add_action('wp_ajax_akpp_2gis_sync', array($this, 'ajax_2gis_sync'));
    }
    
    public function register_settings() {
        register_setting('akpp_integrations', 'akpp_telegram_bot_token');
        register_setting('akpp_integrations', 'akpp_telegram_notify_chat');
        register_setting('akpp_integrations', 'akpp_avito_client_id');
        register_setting('akpp_integrations', 'akpp_avito_client_secret');
        register_setting('akpp_integrations', 'akpp_2gis_api_key');
        register_setting('akpp_integrations', 'akpp_2gis_firm_id');
    }
    
    /**
     * Тестовое сообщение Telegram
     */
    public function ajax_test_telegram() {
        check_ajax_referer('akpp_crm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Нет доступа'));
        }
        
        $token = sanitize_text_field($_POST['token'] ?? '');
        $chat = sanitize_text_field($_POST['chat'] ?? '');
        
        if (empty($token) || empty($chat)) {
            wp_send_json_error(array('message' => 'Token и Chat ID обязательны'));
        }
        
        $url = "https://api.telegram.org/bot{$token}/sendMessage";
        $response = wp_remote_post($url, array(
            'body' => array(
                'chat_id' => $chat,
                'text' => "✅ Тестовое сообщение от АКПП45 CRM\n\n" .
                          "📅 " . current_time('d.m.Y H:i') . "\n" .
                          "🌐 " . home_url(),
                'parse_mode' => 'HTML',
            ),
            'timeout' => 10,
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => 'Ошибка: ' . $response->get_error_message()));
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($body['ok']) {
            wp_send_json_success(array('message' => 'Сообщение отправлено!'));
        } else {
            wp_send_json_error(array('message' => $body['description'] ?? 'Неизвестная ошибка'));
        }
    }
    
    /**
     * Синхронизация Avito
     */
    public function ajax_avito_sync() {
        check_ajax_referer('akpp_crm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Нет доступа'));
        }
        
        $client_id = get_option('akpp_avito_client_id', '');
        $secret = get_option('akpp_avito_client_secret', '');
        
        if (empty($client_id) || empty($secret)) {
            wp_send_json_error(array('message' => 'Настройте Client ID и Secret'));
        }
        
        // Получаем токен
        $token_response = wp_remote_post('https://api.avito.ru/token', array(
            'body' => array(
                'grant_type' => 'client_credentials',
                'client_id' => $client_id,
                'client_secret' => $secret,
            ),
            'timeout' => 15,
        ));
        
        if (is_wp_error($token_response)) {
            wp_send_json_error(array('message' => 'Ошибка подключения к Avito'));
        }
        
        $token_data = json_decode(wp_remote_retrieve_body($token_response), true);
        
        if (empty($token_data['access_token'])) {
            wp_send_json_error(array('message' => 'Не удалось получить токен Avito'));
        }
        
        // Получаем сообщения
        $messages_response = wp_remote_get('https://api.avito.ru/core/v1/accounts/messages', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $token_data['access_token'],
            ),
            'timeout' => 20,
        ));
        
        if (is_wp_error($messages_response)) {
            wp_send_json_error(array('message' => 'Ошибка получения сообщений'));
        }
        
        $messages_data = json_decode(wp_remote_retrieve_body($messages_response), true);
        $count = $messages_data['total'] ?? 0;
        
        wp_send_json_success(array('message' => "Получено {$count} сообщений с Avito"));
    }
    
    /**
     * Синхронизация 2GIS
     */
    public function ajax_2gis_sync() {
        check_ajax_referer('akpp_crm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Нет доступа'));
        }
        
        $api_key = get_option('akpp_2gis_api_key', '');
        $firm_id = get_option('akpp_2gis_firm_id', '');
        
        if (empty($api_key) || empty($firm_id)) {
            wp_send_json_error(array('message' => 'Настройте API Key и Firm ID'));
        }
        
        // Получаем отзывы
        $url = "https://catalog.api.2gis.com/3.0/items?key={$api_key}&id={$firm_id}&fields=reviews&lang=ru";
        
        $response = wp_remote_get($url, array('timeout' => 20));
        
        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => 'Ошибка подключения к 2GIS'));
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        $reviews = $data['result']['items'][0]['reviews'] ?? array();
        $count = count($reviews);
        
        wp_send_json_success(array('message' => "Получено {$count} отзывов с 2GIS"));
    }
    
    /**
     * Отправка уведомления в Telegram
     */
    public static function send_notification($text) {
        $token = get_option('akpp_telegram_bot_token', '');
        $chat = get_option('akpp_telegram_notify_chat', '');
        
        if (empty($token) || empty($chat)) {
            return false;
        }
        
        $url = "https://api.telegram.org/bot{$token}/sendMessage";
        wp_remote_post($url, array(
            'body' => array(
                'chat_id' => $chat,
                'text' => $text,
                'parse_mode' => 'HTML',
            ),
            'timeout' => 10,
        ));
        
        return true;
    }
}

new AKPP_Integrations();