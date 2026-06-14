<?php
/**
 * Telegram CRM Bot with MTProto & v2ray Support
 * @package AKPP_Kurgan
 * @version 3.1.0
 */

if (!defined('ABSPATH')) exit;

class AKPP_Telegram_CRM {
    
    private $bot_token;
    private $chat_id;
    private $proxy_type;
    private $proxy_host;
    private $proxy_port;
    private $proxy_user;
    private $proxy_pass;
    private $use_mtproto;
    private $mtproto_api_id;
    private $mtproto_api_hash;
    private $mtproto_phone;
    
    public function __construct() {
        $this->load_settings();
        
        //add_action('admin_menu', array($this, 'add_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        
        // Webhook
        add_action('wp_ajax_akpp_tg_webhook', array($this, 'handle_webhook'));
        add_action('wp_ajax_nopriv_akpp_tg_webhook', array($this, 'handle_webhook'));
        
        // AJAX
        add_action('wp_ajax_akpp_tg_test_connection', array($this, 'ajax_test_connection'));
        add_action('wp_ajax_akpp_tg_save_settings', array($this, 'ajax_save_settings'));
    }
    
    /**
     * Загрузка настроек
     */
    private function load_settings() {
        $this->bot_token = trim(get_option('akpp_tg_bot_token', ''));
        $this->chat_id = trim(get_option('akpp_tg_chat_id', ''));
        $this->proxy_type = get_option('akpp_tg_proxy_type', 'none');
        $this->proxy_host = trim(get_option('akpp_tg_proxy_host', ''));
        $this->proxy_port = intval(get_option('akpp_tg_proxy_port', 0));
        $this->proxy_user = trim(get_option('akpp_tg_proxy_user', ''));
        $this->proxy_pass = trim(get_option('akpp_tg_proxy_pass', ''));
        
        // MTProto настройки (ОТДЕЛЬНО от бота!)
        $this->use_mtproto = (bool) get_option('akpp_tg_use_mtproto', false);
        $this->mtproto_api_id = trim(get_option('akpp_tg_mtproto_api_id', ''));
        $this->mtproto_api_hash = trim(get_option('akpp_tg_mtproto_api_hash', ''));
        $this->mtproto_phone = trim(get_option('akpp_tg_mtproto_phone', ''));
    }
    
    /**
     * Регистрация настроек
     */
    public function register_settings() {
        // Основные настройки бота
        register_setting('akpp_tg_settings', 'akpp_tg_bot_token');
        register_setting('akpp_tg_settings', 'akpp_tg_chat_id');
        
        // Прокси
        register_setting('akpp_tg_settings', 'akpp_tg_proxy_type');
        register_setting('akpp_tg_settings', 'akpp_tg_proxy_host');
        register_setting('akpp_tg_settings', 'akpp_tg_proxy_port');
        register_setting('akpp_tg_settings', 'akpp_tg_proxy_user');
        register_setting('akpp_tg_settings', 'akpp_tg_proxy_pass');
        
        // MTProto (ОТДЕЛЬНЫЕ настройки!)
        register_setting('akpp_tg_settings', 'akpp_tg_use_mtproto');
        register_setting('akpp_tg_settings', 'akpp_tg_mtproto_api_id');
        register_setting('akpp_tg_settings', 'akpp_tg_mtproto_api_hash');
        register_setting('akpp_tg_settings', 'akpp_tg_mtproto_phone');
    }
    
    /**
     * Меню
     */
public function add_menu() {
    add_submenu_page(
        'akpp-crm',
        'VPN / Прокси',
        '🔒 VPN',
        'manage_options',
        'akpp-telegram-vpn',
        array($this, 'render_page')
    );
}
    
    /**
     * Рендер страницы
     */
    public function render_page() {
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'bot';
        ?>
        <div class="wrap akpp-crm-dashboard">
            <h1>📱 Telegram CRM</h1>
            
            <!-- Вкладки -->
            <div style="display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid rgba(0,255,136,0.2); padding-bottom: 10px;">
                <a href="?page=akpp-telegram&tab=bot" class="button <?php echo $active_tab === 'bot' ? 'button-primary' : ''; ?>">🤖 Бот</a>
                <a href="?page=akpp-telegram&tab=proxy" class="button <?php echo $active_tab === 'proxy' ? 'button-primary' : ''; ?>">🌐 Прокси/v2ray</a>
                <a href="?page=akpp-telegram&tab=mtproto" class="button <?php echo $active_tab === 'mtproto' ? 'button-primary' : ''; ?>">MTProto</a>
                <a href="?page=akpp-telegram&tab=test" class="button <?php echo $active_tab === 'test' ? 'button-primary' : ''; ?>">🔍 Тест</a>
            </div>
            
            <?php if ($active_tab === 'bot'): ?>
                <?php $this->render_bot_tab(); ?>
            <?php elseif ($active_tab === 'proxy'): ?>
                <?php $this->render_proxy_tab(); ?>
            <?php elseif ($active_tab === 'mtproto'): ?>
                <?php $this->render_mtproto_tab(); ?>
            <?php elseif ($active_tab === 'test'): ?>
                <?php $this->render_test_tab(); ?>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Вкладка: Бот
     */
    private function render_bot_tab() {
        ?>
        <div class="akpp-form-section">
            <h3> Настройки Telegram бота</h3>
            <form method="post" action="options.php">
                <?php settings_fields('akpp_tg_settings'); ?>
                
                <div class="form-row">
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label>Bot Token *</label>
                        <input type="text" name="akpp_tg_bot_token" 
                               value="<?php echo esc_attr($this->bot_token); ?>" 
                               placeholder="123456:ABC-DEF1234ghIkl-zyx57W2v1u129ew11"
                               style="width: 100%; font-family: monospace;">
                        <small style="color: #6b7280;">Получите токен через <a href="https://t.me/BotFather" target="_blank" style="color: #00ff88;">@BotFather</a></small>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Chat ID для уведомлений</label>
                        <input type="text" name="akpp_tg_chat_id" 
                               value="<?php echo esc_attr($this->chat_id); ?>" 
                               placeholder="-1001234567890">
                        <small style="color: #6b7280;">Узнайте ID через <a href="https://t.me/userinfobot" target="_blank" style="color: #00ff88;">@userinfobot</a></small>
                    </div>
                    <div class="form-group">
                        <label>Авторизованные пользователи (по одному ID на строку)</label>
                        <textarea name="akpp_tg_authorized_users" rows="4" 
                                  style="width: 100%; font-family: monospace;"><?php 
                            echo esc_textarea(get_option('akpp_tg_authorized_users', '')); 
                        ?></textarea>
                    </div>
                </div>
                
                <div class="form-actions">
                    <?php submit_button('💾 Сохранить'); ?>
                </div>
            </form>
        </div>
        <?php
    }
    
    /**
     * Вкладка: Прокси/v2ray
     */
    private function render_proxy_tab() {
        ?>
        <div class="akpp-form-section">
            <h3> Настройки прокси / v2ray</h3>
            <form method="post" action="options.php">
                <?php settings_fields('akpp_tg_settings'); ?>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Тип прокси</label>
                        <select name="akpp_tg_proxy_type" style="width: 100%;">
                            <option value="none" <?php selected($this->proxy_type, 'none'); ?>>Без прокси</option>
                            <option value="v2ray" <?php selected($this->proxy_type, 'v2ray'); ?>>v2ray (SOCKS5)</option>
                            <option value="socks5" <?php selected($this->proxy_type, 'socks5'); ?>>SOCKS5</option>
                            <option value="http" <?php selected($this->proxy_type, 'http'); ?>>HTTP/HTTPS</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Хост прокси</label>
                        <input type="text" name="akpp_tg_proxy_host" 
                               value="<?php echo esc_attr($this->proxy_host); ?>" 
                               placeholder="127.0.0.1 или IP сервера"
                               style="width: 100%;">
                    </div>
                    <div class="form-group">
                        <label>Порт</label>
                        <input type="number" name="akpp_tg_proxy_port" 
                               value="<?php echo esc_attr($this->proxy_port); ?>" 
                               placeholder="1080"
                               style="width: 100%;">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Логин (если требуется)</label>
                        <input type="text" name="akpp_tg_proxy_user" 
                               value="<?php echo esc_attr($this->proxy_user); ?>" 
                               style="width: 100%;">
                    </div>
                    <div class="form-group">
                        <label>Пароль (если требуется)</label>
                        <input type="password" name="akpp_tg_proxy_pass" 
                               value="<?php echo esc_attr($this->proxy_pass); ?>" 
                               style="width: 100%;">
                    </div>
                </div>
                
                <div class="form-actions">
                    <?php submit_button('💾 Сохранить'); ?>
                </div>
            </form>
        </div>
        <?php
    }
    
    /**
     * Вкладка: MTProto
     */
    private function render_mtproto_tab() {
        ?>
        <div class="akpp-form-section">
            <h3>⚡ MTProto (нативный протокол Telegram)</h3>
            <p style="color: #9ca3af; margin-bottom: 20px;">
                MTProto работает напрямую с серверами Telegram через TCP, минуя HTTP API. 
                <strong style="color: #fbbf24;">Внимание:</strong> MTProto использует отдельные credentials (API ID, API Hash), а не Bot Token!
            </p>
            
            <form method="post" action="options.php">
                <?php settings_fields('akpp_tg_settings'); ?>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="akpp_tg_use_mtproto" value="1" 
                                   <?php checked($this->use_mtproto, true); ?>>
                            Использовать MTProto вместо HTTP API
                        </label>
                        <small style="color: #6b7280; display: block; margin-top: 10px;">
                            При включении бот будет использовать MTProto для отправки сообщений
                        </small>
                    </div>
                </div>
                
                <div style="background: rgba(251, 191, 36, 0.1); border: 1px solid rgba(251, 191, 36, 0.3); border-radius: 8px; padding: 15px; margin: 20px 0;">
                    <h4 style="color: #fbbf24; margin: 0 0 10px 0;">⚠️ Настройки MTProto (отдельно от бота)</h4>
                    <p style="color: #9ca3af; margin: 0 0 15px 0; font-size: 13px;">
                        Получите API ID и API Hash на <a href="https://my.telegram.org/" target="_blank" style="color: #00ff88;">my.telegram.org</a>
                    </p>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>API ID *</label>
                            <input type="text" name="akpp_tg_mtproto_api_id" 
                                   value="<?php echo esc_attr($this->mtproto_api_id); ?>" 
                                   placeholder="12345678"
                                   style="width: 100%; font-family: monospace;">
                        </div>
                        <div class="form-group">
                            <label>API Hash *</label>
                            <input type="text" name="akpp_tg_mtproto_api_hash" 
                                   value="<?php echo esc_attr($this->mtproto_api_hash); ?>" 
                                   placeholder="abcdef1234567890abcdef1234567890"
                                   style="width: 100%; font-family: monospace;">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Телефон (для авторизации)</label>
                            <input type="text" name="akpp_tg_mtproto_phone" 
                                   value="<?php echo esc_attr($this->mtproto_phone); ?>" 
                                   placeholder="+79991234567"
                                   style="width: 100%;">
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <?php submit_button('💾 Сохранить MTProto настройки'); ?>
                </div>
            </form>
        </div>
        
        <div class="akpp-form-section">
            <h3>📦 Установка MadelineProto</h3>
            <div style="background: #0a0f1c; padding: 15px; border-radius: 8px; font-family: monospace; font-size: 13px;">
                <p style="color: #00ff88; margin-bottom: 10px;"># Перейдите в папку темы:</p>
                <code style="display: block; color: #e5e7eb; margin: 5px 0;">cd /home/akpp/htdocs/akpp45.ru/wp-content/themes/akpp-kurgan</code>
                
                <p style="color: #00ff88; margin: 15px 0 10px 0;"># Установите Composer (если нет):</p>
                <code style="display: block; color: #e5e7eb; margin: 5px 0;">curl -sS https://getcomposer.org/installer | php</code>
                
                <p style="color: #00ff88; margin: 15px 0 10px 0;"># Установите MadelineProto:</p>
                <code style="display: block; color: #e5e7eb; margin: 5px 0;">php composer.phar require danog/madelineproto</code>
                
                <p style="color: #fbbf24; margin-top: 15px;">⚠️ После установки заполните API ID, API Hash и включите чекбокс</p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Вкладка: Тест
     */
    private function render_test_tab() {
        ?>
        <div class="akpp-form-section">
            <h3>🔍 Тестирование подключения</h3>
            
            <div style="background: rgba(0,255,136,0.05); padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <strong>Текущая конфигурация:</strong><br>
                <span style="color: #9ca3af;">
                    Бот: <?php echo !empty($this->bot_token) ? '✅ Задан' : '❌ Не задан'; ?> | 
                    Прокси: <?php echo $this->proxy_type !== 'none' ? "{$this->proxy_type} ({$this->proxy_host}:{$this->proxy_port})" : '❌ Не используется'; ?> |
                    MTProto: <?php echo $this->use_mtproto ? '✅ Включен' : '❌ Выключен'; ?>
                </span>
            </div>
            
            <button type="button" id="test-connection-btn" class="button button-primary">🔍 Проверить подключение</button>
            <div id="test-result" style="margin-top: 20px; padding: 15px; background: #0a0f1c; border-radius: 8px; display: none;"></div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#test-connection-btn').on('click', function() {
                const btn = $(this);
                const result = $('#test-result');
                btn.prop('disabled', true).text('⏳ Проверка...');
                result.show().html('<div style="color: #fbbf24;">Проверяем подключение к Telegram...</div>');
                
                $.post(akppCrm.ajaxUrl, {
                    action: 'akpp_tg_test_connection',
                    nonce: akppCrm.nonce
                }, function(response) {
                    btn.prop('disabled', false).text('🔍 Проверить подключение');
                    if (response.success) {
                        result.html('<div style="color: #10b981;">✅ ' + response.data.message + '</div>');
                    } else {
                        result.html('<div style="color: #ef4444;">❌ ' + response.data.message + '</div>');
                    }
                }).fail(function() {
                    btn.prop('disabled', false).text('🔍 Проверить подключение');
                    result.html('<div style="color: #ef4444;">❌ Ошибка соединения с сервером</div>');
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * AJAX: Тест подключения
     */
    public function ajax_test_connection() {
        check_ajax_referer('akpp_crm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Нет доступа'));
        }
        
        // Проверяем Bot Token (он всегда нужен для webhook!)
        if (empty($this->bot_token)) {
            wp_send_json_error(array('message' => 'Bot Token не задан. Перейдите во вкладку "Бот" и укажите токен.'));
        }
        
        // Если включен MTProto - проверяем его настройки
        if ($this->use_mtproto) {
            if (empty($this->mtproto_api_id) || empty($this->mtproto_api_hash)) {
                wp_send_json_error(array('message' => 'MTProto включен, но не заполнены API ID или API Hash. Перейдите во вкладку "MTProto".'));
            }
            
            // Проверяем MadelineProto
            if (class_exists('\\danog\\MadelineProto\\API')) {
                wp_send_json_success(array('message' => 'MTProto настроен. Bot Token: ✅ | MTProto: ✅ (API ID: ' . $this->mtproto_api_id . ')'));
            } else {
                wp_send_json_error(array('message' => 'MTProto включен, но библиотека MadelineProto не установлена. Установите через Composer.'));
            }
        } else {
            // Обычный HTTP API
            $response = wp_remote_get("https://api.telegram.org/bot{$this->bot_token}/getMe", array(
                'timeout' => 15,
                'sslverify' => false,
            ));
            
            if (is_wp_error($response)) {
                $error = $response->get_error_message();
                if (strpos($error, 'cURL error 28') !== false) {
                    wp_send_json_error(array('message' => 'Таймаут. Telegram заблокирован. Настройте прокси во вкладке "Прокси/v2ray".'));
                }
                wp_send_json_error(array('message' => $error));
            }
            
            $body = json_decode(wp_remote_retrieve_body($response), true);
            if (isset($body['ok']) && $body['ok']) {
                $bot_name = $body['result']['username'] ?? 'unknown';
                wp_send_json_success(array('message' => "Бот @{$bot_name} работает через HTTP API"));
            } else {
                wp_send_json_error(array('message' => $body['description'] ?? 'Неизвестная ошибка'));
            }
        }
    }
    
    /**
     * Обработка webhook
     */
    public function handle_webhook() {
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input)) wp_die();
        
        if (isset($input['callback_query'])) {
            $this->handle_callback($input['callback_query']);
            wp_die();
        }
        
        if (isset($input['message'])) {
            $this->handle_message($input['message']);
        }
        
        wp_die();
    }
    
    /**
     * Отправка сообщения
     */
    public function send_message($chat_id, $text, $keyboard = array()) {
        $data = array(
            'chat_id' => $chat_id,
            'text' => $text,
            'parse_mode' => 'HTML',
        );
        
        if (!empty($keyboard)) {
            $data['reply_markup'] = json_encode(array('inline_keyboard' => $keyboard));
        }
        
        // Если MTProto включен и есть библиотека - используем её
        if ($this->use_mtproto && class_exists('\\danog\\MadelineProto\\API')) {
            return $this->send_via_mtproto($chat_id, $text);
        }
        
        // Иначе используем HTTP API
        $response = wp_remote_post("https://api.telegram.org/bot{$this->bot_token}/sendMessage", array(
            'body' => $data,
            'timeout' => 10,
            'sslverify' => false,
        ));
        
        return !is_wp_error($response);
    }
    
    /**
     * Отправка через MTProto
     */
    private function send_via_mtproto($chat_id, $text) {
        try {
            $madeline = new \danog\MadelineProto\API(array(
                'app_info' => array(
                    'api_id' => intval($this->mtproto_api_id),
                    'api_hash' => $this->mtproto_api_hash,
                ),
            ));
            $madeline->start();
            $madeline->messages->sendMessage(array(
                'peer' => $chat_id,
                'message' => $text,
            ));
            return true;
        } catch (Exception $e) {
            error_log('MTProto Error: ' . $e->getMessage());
            return false;
        }
    }
    
    // ... (остальные методы handle_message, handle_callback, cmd_* остаются без изменений)
}

new AKPP_Telegram_CRM();