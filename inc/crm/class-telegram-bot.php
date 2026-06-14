<?php
/**
 * Telegram Bot Integration
 * @package AKPP_Kurgan
 */

if (!defined('ABSPATH')) exit;

class AKPP_Telegram_Bot {
    
    private $webhook_url;
    
    public function __construct() {
        $this->webhook_url = admin_url('admin-ajax.php?action=akpp_telegram_webhook');
        
        add_action('wp_ajax_akpp_telegram_webhook', array($this, 'handle_webhook'));
        add_action('wp_ajax_nopriv_akpp_telegram_webhook', array($this, 'handle_webhook'));
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    public function register_settings() {
        register_setting('akpp_integrations', 'akpp_telegram_bot_token');
        register_setting('akpp_integrations', 'akpp_telegram_notify_chat');
    }
    
    public function handle_webhook() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['message'])) {
            wp_die();
        }
        
        $message = $input['message'];
        $chat_id = $message['chat']['id'];
        $text = trim($message['text'] ?? '');
        $user = $message['from'] ?? array();
        
        // Команды бота
        if ($text === '/start') {
            $this->send_message($chat_id, 
                "👋 Добро пожаловать в АКПП45!\n\n" .
                "Мы специализируемся на ремонте АКПП.\n\n" .
                "Чтобы оставить заявку, напишите:\n" .
                "📞 Ваш телефон\n" .
                "🚗 Марку и модель авто\n" .
                "⚙️ Код АКПП (если знаете)\n" .
                "💬 Описание проблемы"
            );
            wp_die();
        }
        
        if ($text === '/help') {
            $this->send_message($chat_id, 
                " *Доступные команды:*\n" .
                "/start - Начать\n" .
                "/help - Помощь\n" .
                "/status - Статус заявки\n\n" .
                "Или просто напишите ваш вопрос!"
            );
            wp_die();
        }
        
        // Создаем лид из сообщения
        global $wpdb;
        $table = $wpdb->prefix . 'akpp_leads';
        
        // Проверяем, есть ли уже лид от этого пользователя
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE source = 'telegram' AND source_id = %s AND status IN ('new','contacted','interested')",
            'tg_' . $chat_id
        ));
        
        if ($existing) {
            // Добавляем сообщение в историю
            $wpdb->insert($wpdb->prefix . 'akpp_lead_messages', array(
                'lead_id' => $existing,
                'direction' => 'in',
                'channel' => 'telegram',
                'message' => $text,
            ));
            
            $this->send_message($chat_id, "✅ Ваше сообщение получено. Менеджер скоро ответит.");
        } else {
            // Создаем новый лид
            $wpdb->insert($table, array(
                'source' => 'telegram',
                'source_id' => 'tg_' . $chat_id,
                'name' => ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''),
                'message' => $text,
                'status' => 'new',
                'created_at' => current_time('mysql'),
            ));
            
            $lead_id = $wpdb->insert_id;
            
            $this->send_message($chat_id, 
                "✅ Заявка принята! Номер: #{$lead_id}\n\n" .
                "Менеджер свяжется с вами в ближайшее время."
            );
            
            // Уведомление админу
            $this->notify_admin($lead_id, $text, $user);
        }
        
        wp_die();
    }
    
    private function send_message($chat_id, $text) {
        $token = get_option('akpp_telegram_bot_token', '');
        if (empty($token)) return;
        
        wp_remote_post("https://api.telegram.org/bot{$token}/sendMessage", array(
            'body' => array(
                'chat_id' => $chat_id,
                'text' => $text,
                'parse_mode' => 'Markdown',
            ),
            'timeout' => 10,
        ));
    }
    
    private function notify_admin($lead_id, $text, $user) {
        $chat = get_option('akpp_telegram_notify_chat', '');
        if (empty($chat)) return;
        
        $token = get_option('akpp_telegram_bot_token', '');
        $name = ($user['first_name'] ?? '') . ' ' . ($user['username'] ?? '');
        
        $this->send_message($chat, 
            "🔔 *Новое сообщение в боте!*\n\n" .
            "👤 {$name}\n" .
            "💬 {$text}\n\n" .
            "Лид #{$lead_id}"
        );
    }
    
    public function setup_webhook() {
        $token = get_option('akpp_telegram_bot_token', '');
        if (empty($token)) return false;
        
        $url = "https://api.telegram.org/bot{$token}/setWebhook";
        $response = wp_remote_post($url, array(
            'body' => array('url' => $this->webhook_url),
            'timeout' => 15,
        ));
        
        if (is_wp_error($response)) return false;
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        return $body['ok'] ?? false;
    }
}

new AKPP_Telegram_Bot();