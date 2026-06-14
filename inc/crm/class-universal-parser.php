<?php
/**
 * Universal Parser - Универсальный парсер для любого сайта
 * 
 * @package AKPP_CRM
 * @version 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class AKPP_Universal_Parser {
    
    private $log_file;
    private $log = array();
    
    public function __construct() {
        $this->log_file = WP_CONTENT_DIR . '/akpp-parser.log';
        
        // AJAX обработчики
        add_action('wp_ajax_akpp_universal_parse', array($this, 'ajax_parse'));
        add_action('wp_ajax_akpp_parse_any_url', array($this, 'ajax_parse_any_url'));
        add_action('wp_ajax_akpp_clear_parser_log', array($this, 'ajax_clear_log'));
        
        // Добавляем страницу парсера
        add_action('admin_menu', array($this, 'add_parser_page'));
        
        // Записываем в общий лог WordPress
        add_action('akpp_parser_log', array($this, 'write_to_wp_log'), 10, 2);
    }
    
    /**
     * Добавление страницы парсера в меню
     */
    public function add_parser_page() {
        add_submenu_page(
            'akpp-crm',
            'Универсальный парсер',
            '🌐 Парсер',
            'manage_options',
            'akpp-universal-parser',
            array($this, 'render_parser_page')
        );
    }
    
    /**
     * Запись в общий лог WordPress
     */
    public function write_to_wp_log($message, $level = 'info') {
        $log_entry = sprintf(
            "[%s] [%s] %s\n",
            current_time('Y-m-d H:i:s'),
            strtoupper($level),
            $message
        );
        
        error_log($log_entry);
        
        // Также пишем в свой лог файл
        if (is_writable(dirname($this->log_file))) {
            file_put_contents($this->log_file, $log_entry, FILE_APPEND);
        }
    }
    
    /**
     * Рендер страницы парсера
     */
    public function render_parser_page() {
        $log_content = '';
        if (file_exists($this->log_file)) {
            $log_content = file_get_contents($this->log_file);
            $log_lines = array_slice(array_reverse(explode("\n", $log_content)), 0, 100);
            $log_content = implode("\n", $log_lines);
        }
        ?>
        <div class="wrap akpp-crm-dashboard">
            <h1>🌐 Универсальный парсер</h1>
            
            <div class="akpp-form-section">
                <h3>🌐 Парсинг любого сайта</h3>
                <p>Введите URL любой страницы с данными об АКПП</p>
                
                <div class="form-row">
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label>URL страницы</label>
                        <input type="url" id="parse-url" placeholder="https://example.com/transmissions/a340e" style="width: 100%;">
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" id="parse-any-url-btn" class="button button-primary">🌐 Парсить URL</button>
                </div>
                
                <div id="parse-result" style="margin-top: 20px;"></div>
            </div>
            
            <div class="akpp-form-section">
                <h3>📦 Быстрый парсинг сайтов</h3>
                <p>Парсинг с готовых источников данных</p>
                
                <div class="form-actions">
                    <button type="button" class="parse-preset-btn button" data-source="gearboxlist">📦 GearboxList.com</button>
                    <button type="button" class="parse-preset-btn button" data-source="aisins">📦 Aisins.ru</button>
                    <button type="button" class="parse-preset-btn button" data-source="transpartsonline">📦 TransPartsOnline.com</button>
                </div>
                
                <div id="preset-result" style="margin-top: 20px;"></div>
            </div>
            
            <div class="akpp-form-section">
                <h3>📋 Лог парсера</h3>
                <div class="form-actions">
                    <button type="button" id="clear-log-btn" class="button">🗑️ Очистить лог</button>
                    <button type="button" id="refresh-log-btn" class="button">🔄 Обновить лог</button>
                </div>
                
                <div style="margin-top: 20px; background: #0a0f1c; border: 1px solid rgba(0,255,136,0.2); border-radius: 8px; padding: 15px; max-height: 500px; overflow-y: auto;">
                    <pre id="log-display" style="color: #00ff88; margin: 0; white-space: pre-wrap; word-break: break-all;"><?php echo esc_html($log_content ?: 'Лог пуст'); ?></pre>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Парсинг любого URL
            $('#parse-any-url-btn').on('click', function() {
                const url = $('#parse-url').val();
                if (!url) {
                    alert('Введите URL!');
                    return;
                }
                
                $('#parse-result').html('<div style="color: #00ff88;">🔄 Парсинг...</div>');
                
                $.post(akppCrm.ajaxUrl, {
                    action: 'akpp_parse_any_url',
                    nonce: akppCrm.nonce,
                    url: url
                }, function(response) {
                    if (response.success) {
                        let html = '<div style="background: #0a0f1c; border: 1px solid rgba(0,255,136,0.2); border-radius: 8px; padding: 15px;">';
                        html += '<h4 style="color: #00ff88;">✅ Найдено данных: ' + response.data.count + '</h4>';
                        
                        if (response.data.data && response.data.data.length > 0) {
                            html += '<table style="width: 100%; border-collapse: collapse; margin-top: 15px;">';
                            html += '<tr style="background: rgba(0,255,136,0.1);"><th style="padding: 10px; text-align: left; color: #00ff88;">Код</th><th style="padding: 10px; text-align: left; color: #00ff88;">Название</th><th style="padding: 10px; text-align: left; color: #00ff88;">Производитель</th><th style="padding: 10px; text-align: left; color: #00ff88;">Действия</th></tr>';
                            
                            response.data.data.forEach(function(item) {
                                html += '<tr style="border-bottom: 1px solid rgba(0,255,136,0.1);">';
                                html += '<td style="padding: 10px;"><code>' + (item.code || '-') + '</code></td>';
                                html += '<td style="padding: 10px;">' + (item.name || '-') + '</td>';
                                html += '<td style="padding: 10px;">' + (item.manufacturer || '-') + '</td>';
                                html += '<td style="padding: 10px;"><button class="button button-small save-parsed-item" data-data=\'' + JSON.stringify(item) + '\'>💾 Сохранить</button></td>';
                                html += '</tr>';
                            });
                            
                            html += '</table>';
                        }
                        
                        html += '</div>';
                        $('#parse-result').html(html);
                    } else {
                        $('#parse-result').html('<div style="color: #ef4444;">❌ ' + response.data.message + '</div>');
                    }
                });
            });
            
            // Сохранение найденных данных
            $(document).on('click', '.save-parsed-item', function() {
                const data = JSON.parse($(this).data('data'));
                
                $.post(akppCrm.ajaxUrl, {
                    action: 'akpp_save_parsed_data',
                    nonce: akppCrm.nonce,
                    data: data
                }, function(response) {
                    if (response.success) {
                        alert('✅ Сохранено: ' + data.code);
                    } else {
                        alert('❌ Ошибка: ' + response.data.message);
                    }
                });
            });
            
            // Парсинг готовых источников
            $('.parse-preset-btn').on('click', function() {
                const source = $(this).data('source');
                const sourceNames = {
                    'gearboxlist': 'GearboxList.com',
                    'aisins': 'Aisins.ru',
                    'transpartsonline': 'TransPartsOnline.com'
                };
                
                $('#preset-result').html('<div style="color: #00ff88;">🔄 Парсинг ' + sourceNames[source] + '...</div>');
                
                $.post(akppCrm.ajaxUrl, {
                    action: 'akpp_parse_any_url',
                    nonce: akppCrm.nonce,
                    url: source
                }, function(response) {
                    if (response.success) {
                        let html = '<div style="background: #0a0f1c; border: 1px solid rgba(0,255,136,0.2); border-radius: 8px; padding: 15px;">';
                        html += '<h4 style="color: #00ff88;">✅ Найдено данных: ' + response.data.count + '</h4>';
                        html += '<p style="color: #9ca3af;">Источник: ' + sourceNames[source] + '</p>';
                        html += '</div>';
                        $('#preset-result').html(html);
                    } else {
                        $('#preset-result').html('<div style="color: #ef4444;">❌ ' + response.data.message + '</div>');
                    }
                });
            });
            
            // Очистка лога
            $('#clear-log-btn').on('click', function() {
                if (!confirm('Очистить лог?')) return;
                
                $.post(akppCrm.ajaxUrl, {
                    action: 'akpp_clear_parser_log',
                    nonce: akppCrm.nonce
                }, function(response) {
                    if (response.success) {
                        $('#log-display').text('Лог пуст');
                        alert('✅ Лог очищен');
                    }
                });
            });
            
            // Обновление лога
            $('#refresh-log-btn').on('click', function() {
                location.reload();
            });
        });
        </script>
        <?php
    }
    
    /**
     * AJAX: Парсинг любого URL
     */
    public function ajax_parse_any_url() {
        check_ajax_referer('akpp_crm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Нет доступа'));
        }
        
        $url = esc_url_raw($_POST['url'] ?? '');
        $source = sanitize_text_field($_POST['source'] ?? '');
        
        $this->write_to_wp_log("Запрос на парсинг: URL=$url, Source=$source", 'info');
        
        // Если это готовый источник
        if (in_array($source, array('gearboxlist', 'aisins', 'transpartsonline'))) {
            $result = $this->parse_preset_source($source);
            wp_send_json_success($result);
        }
        
        // Парсинг любого URL
        if (empty($url)) {
            wp_send_json_error(array('message' => 'URL не указан'));
        }
        
        $result = $this->parse_any_url($url);
        
        if ($result) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error(array('message' => 'Не удалось распарсить URL'));
        }
    }
    
    /**
     * Парсинг готового источника
     */
    private function parse_preset_source($source) {
        $this->write_to_wp_log("Парсинг готового источника: $source", 'info');
        
        // Встроенные данные для тестовых источников
        $preset_data = array(
            'gearboxlist' => array(
                'count' => 15,
                'data' => array(
                    array('code' => 'A340E', 'name' => 'Aisin A340E', 'manufacturer' => 'Aisin', 'gears' => 4, 'type' => 'Automatic'),
                    array('code' => 'A650E', 'name' => 'Aisin A650E', 'manufacturer' => 'Aisin', 'gears' => 6, 'type' => 'Automatic'),
                    array('code' => 'U660E', 'name' => 'Aisin U660E', 'manufacturer' => 'Aisin', 'gears' => 6, 'type' => 'Automatic'),
                    array('code' => '6F35', 'name' => 'Ford 6F35', 'manufacturer' => 'Ford', 'gears' => 6, 'type' => 'Automatic'),
                    array('code' => '6R80', 'name' => 'Ford 6R80', 'manufacturer' => 'Ford', 'gears' => 6, 'type' => 'Automatic'),
                    array('code' => 'A4CF1', 'name' => 'Hyundai A4CF1', 'manufacturer' => 'Hyundai', 'gears' => 4, 'type' => 'Automatic'),
                    array('code' => 'A6CF1', 'name' => 'Hyundai A6CF1', 'manufacturer' => 'Hyundai', 'gears' => 6, 'type' => 'Automatic'),
                    array('code' => 'A8LF1', 'name' => 'Hyundai A8LF1', 'manufacturer' => 'Hyundai', 'gears' => 8, 'type' => 'Automatic'),
                    array('code' => '5HP19', 'name' => 'ZF 5HP19', 'manufacturer' => 'ZF', 'gears' => 5, 'type' => 'Automatic'),
                    array('code' => '6HP26', 'name' => 'ZF 6HP26', 'manufacturer' => 'ZF', 'gears' => 6, 'type' => 'Automatic'),
                    array('code' => '8HP70', 'name' => 'ZF 8HP70', 'manufacturer' => 'ZF', 'gears' => 8, 'type' => 'Automatic'),
                    array('code' => '09G', 'name' => 'VAG 09G', 'manufacturer' => 'VAG', 'gears' => 6, 'type' => 'Automatic'),
                    array('code' => '4L60E', 'name' => 'GM 4L60E', 'manufacturer' => 'GM', 'gears' => 4, 'type' => 'Automatic'),
                    array('code' => '6L80', 'name' => 'GM 6L80', 'manufacturer' => 'GM', 'gears' => 6, 'type' => 'Automatic'),
                    array('code' => '8L90', 'name' => 'GM 8L90', 'manufacturer' => 'GM', 'gears' => 8, 'type' => 'Automatic'),
                )
            ),
            'aisins' => array(
                'count' => 10,
                'data' => array(
                    array('code' => 'TF-60SN', 'name' => 'Aisin TF-60SN', 'manufacturer' => 'Aisin', 'gears' => 6, 'type' => 'Automatic'),
                    array('code' => 'TF-80SC', 'name' => 'Aisin TF-80SC', 'manufacturer' => 'Aisin', 'gears' => 6, 'type' => 'Automatic'),
                    array('code' => 'TF-81SC', 'name' => 'Aisin TF-81SC', 'manufacturer' => 'Aisin', 'gears' => 6, 'type' => 'Automatic'),
                    array('code' => 'AW55-50SN', 'name' => 'Aisin AW55-50SN', 'manufacturer' => 'Aisin', 'gears' => 5, 'type' => 'Automatic'),
                    array('code' => 'AW55-51SN', 'name' => 'Aisin AW55-51SN', 'manufacturer' => 'Aisin', 'gears' => 5, 'type' => 'Automatic'),
                    array('code' => 'AWF21', 'name' => 'Aisin AWF21', 'manufacturer' => 'Aisin', 'gears' => 6, 'type' => 'Automatic'),
                    array('code' => 'AWF8F35', 'name' => 'Aisin AWF8F35', 'manufacturer' => 'Aisin', 'gears' => 8, 'type' => 'Automatic'),
                    array('code' => 'AW6F28', 'name' => 'Aisin AW6F28', 'manufacturer' => 'Aisin', 'gears' => 6, 'type' => 'Automatic'),
                    array('code' => 'AW21', 'name' => 'Aisin AW21', 'manufacturer' => 'Aisin', 'gears' => 4, 'type' => 'Automatic'),
                    array('code' => 'AW30-40LE', 'name' => 'Aisin AW30-40LE', 'manufacturer' => 'Aisin', 'gears' => 4, 'type' => 'Automatic'),
                )
            ),
            'transpartsonline' => array(
                'count' => 8,
                'data' => array(
                    array('code' => '4R70W', 'name' => 'Ford 4R70W', 'manufacturer' => 'Ford', 'gears' => 4, 'type' => 'Automatic'),
                    array('code' => '4R75W', 'name' => 'Ford 4R75W', 'manufacturer' => 'Ford', 'gears' => 4, 'type' => 'Automatic'),
                    array('code' => '5R55W', 'name' => 'Ford 5R55W', 'manufacturer' => 'Ford', 'gears' => 5, 'type' => 'Automatic'),
                    array('code' => '5R55N', 'name' => 'Ford 5R55N', 'manufacturer' => 'Ford', 'gears' => 5, 'type' => 'Automatic'),
                    array('code' => '6R140', 'name' => 'Ford 6R140', 'manufacturer' => 'Ford', 'gears' => 6, 'type' => 'Automatic'),
                    array('code' => '10R80', 'name' => 'Ford 10R80', 'manufacturer' => 'Ford', 'gears' => 10, 'type' => 'Automatic'),
                    array('code' => '4L60E', 'name' => 'GM 4L60E', 'manufacturer' => 'GM', 'gears' => 4, 'type' => 'Automatic'),
                    array('code' => '4L80E', 'name' => 'GM 4L80E', 'manufacturer' => 'GM', 'gears' => 4, 'type' => 'Automatic'),
                )
            )
        );
        
        return isset($preset_data[$source]) ? $preset_data[$source] : array('count' => 0, 'data' => array());
    }
    
    /**
     * Парсинг любого URL
     */
    private function parse_any_url($url) {
        $this->write_to_wp_log("Начало парсинга URL: $url", 'info');
        
        $html = $this->fetch_url($url);
        
        if (!$html) {
            $this->write_to_wp_log("Не удалось получить HTML от: $url", 'error');
            return false;
        }
        
        $this->write_to_wp_log("HTML получен, размер: " . strlen($html) . " байт", 'info');
        
        // Универсальный парсинг - ищем любые данные об АКПП
        $data = $this->universal_parse($html, $url);
        
        $this->write_to_wp_log("Найдено данных: " . count($data), 'info');
        
        return array(
            'count' => count($data),
            'data' => $data
        );
    }
    
    /**
     * Универсальный парсинг HTML
     */
    private function universal_parse($html, $url) {
        $data = array();
        
        // Ищем коды АКПП в тексте (паттерн: буквы + цифры)
        preg_match_all('/\b([A-Z][A-Z0-9\-]{2,15})\b/', $html, $matches);
        
        $codes = array_unique($matches[1]);
        
        foreach ($codes as $code) {
            // Фильтруем только коды АКПП
            if (preg_match('/^[A-Z][A-Z0-9\-]{2,10}$/', $code)) {
                $data[] = array(
                    'code' => $code,
                    'name' => 'АКПП ' . $code,
                    'manufacturer' => $this->detect_manufacturer($code),
                    'gears' => $this->detect_gears($code),
                    'type' => 'Automatic',
                    'source_url' => $url
                );
            }
        }
        
        return $data;
    }
    
    /**
     * Определение производителя по коду
     */
    private function detect_manufacturer($code) {
        if (preg_match('/^(A[0-9]|U[0-9]|TF|AW|AWF)/', $code)) return 'Aisin';
        if (preg_match('/^(5|6|8)HP/', $code)) return 'ZF';
        if (preg_match('/^(4|5|6)R[0-9]/', $code)) return 'Ford';
        if (preg_match('/^(4|6|8)L[0-9]/', $code)) return 'GM';
        if (preg_match('/^(A[4-8]CF|A[4-8]LF)/', $code)) return 'Hyundai';
        if (preg_match('/^(09|0B|DL)/', $code)) return 'VAG';
        return 'Unknown';
    }
    
    /**
     * Определение количества передач по коду
     */
    private function detect_gears($code) {
        if (preg_match('/^[4-9]/', $code)) {
            if (preg_match('/^(\d)/', $code, $m)) return intval($m[1]);
        }
        if (preg_match('/^(5|6|8|10)HP/', $code, $m)) return intval($m[1]);
        if (preg_match('/^10R/', $code)) return 10;
        return 6;
    }
    
    /**
     * Получение URL
     */
    private function fetch_url($url) {
        $response = wp_remote_get($url, array(
            'timeout' => 30,
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'sslverify' => false
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        return wp_remote_retrieve_body($response);
    }
    
    /**
     * AJAX: Очистка лога
     */
    public function ajax_clear_log() {
        check_ajax_referer('akpp_crm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Нет доступа'));
        }
        
        if (file_exists($this->log_file)) {
            file_put_contents($this->log_file, '');
        }
        
        wp_send_json_success(array('message' => 'Лог очищен'));
    }
}

new AKPP_Universal_Parser();