<?php
/**
 * TransAKPP Importer Class
 * 
 * Импорт данных о АКПП с различных источников
 * 
 * @package AKPP_CRM
 * @version 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class TransAKPP_Importer {
    
    /**
     * Настройки импортера
     */
    private $settings;
    
    /**
     * Логирование
     */
    private $log = array();
    
    /**
     * Статистика импорта
     */
    private $stats = array(
        'imported' => 0,
        'updated' => 0,
        'errors' => 0,
        'skipped' => 0
    );

    /**
     * Конструктор
     */
    public function __construct() {
        $this->settings = array(
            'timeout' => 30,
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'delay_between_requests' => 1,
            'max_retries' => 3
        );
        
        add_action('akpp_import_log', array($this, 'log_message'), 10, 2);
    }

    /**
     * Основной метод импорта
     */
    public function import($source = 'all', $params = array()) {
        $this->reset_stats();
        
        switch($source) {
            case 'gearboxlist':
                return $this->import_from_gearboxlist($params);
            
            case 'aisins':
                return $this->import_from_aisins($params);
            
            case 'transpartsonline':
                return $this->import_from_transpartsonline($params);
            
            case 'all':
            default:
                $results = array();
                $results['gearboxlist'] = $this->import_from_gearboxlist($params);
                sleep(2);
                $results['aisins'] = $this->import_from_aisins($params);
                sleep(2);
                $results['transpartsonline'] = $this->import_from_transpartsonline($params);
                return $results;
        }
    }

    /**
     * Импорт с GearboxList.com
     */
    private function import_from_gearboxlist($params = array()) {
        $this->log_message('Starting import from GearboxList.com', 'info');
        
        $base_url = 'https://gearboxlist.com';
        $transmissions = array();
        
        // Парсинг списка АКПП
        $html = $this->fetch_url($base_url . '/automatic-transmissions/');
        
        if (!$html) {
            $this->log_message('Failed to fetch GearboxList.com', 'error');
            return false;
        }
        
        // Извлечение данных о трансмиссиях
        preg_match_all('/<div class="transmission-item[^"]*">(.*?)<\/div>/s', $html, $matches);
        
        foreach ($matches[1] as $item) {
            $transmission = $this->parse_gearboxlist_item($item);
            
            if ($transmission && !empty($transmission['code'])) {
                $transmissions[] = $transmission;
                $this->save_transmission($transmission, 'gearboxlist');
            }
        }
        
        $this->log_message(sprintf('Imported %d transmissions from GearboxList', count($transmissions)), 'success');
        
        return array(
            'count' => count($transmissions),
            'transmissions' => $transmissions,
            'stats' => $this->stats
        );
    }

    /**
     * Парсинг элемента с GearboxList
     */
    private function parse_gearboxlist_item($html) {
        $data = array();
        
        // Извлечение кода АКПП
        preg_match('/<h3[^>]*>([^<]+)<\/h3>/', $html, $matches);
        $data['code'] = isset($matches[1]) ? trim($matches[1]) : '';
        
        // Извлечение названия
        preg_match('/<div class="name[^>]*>([^<]+)<\/div>/', $html, $matches);
        $data['name'] = isset($matches[1]) ? trim($matches[1]) : '';
        
        // Извлечение производителя
        preg_match('/<div class="manufacturer[^>]*>([^<]+)<\/div>/', $html, $matches);
        $data['manufacturer'] = isset($matches[1]) ? trim($matches[1]) : '';
        
        // Извлечение количества передач
        preg_match('/<div class="gears[^>]*>(\d+)[-\s]*(?:speed|ступ)/i', $html, $matches);
        $data['gears'] = isset($matches[1]) ? intval($matches[1]) : 0;
        
        // Извлечение типа
        preg_match('/<div class="type[^>]*>([^<]+)<\/div>/', $html, $matches);
        $data['type'] = isset($matches[1]) ? trim($matches[1]) : '';
        
        // Извлечение применяемости
        preg_match_all('/<li class="application[^>]*>([^<]+)<\/li>/', $html, $matches);
        $data['applications'] = isset($matches[1]) ? $matches[1] : array();
        
        // Дополнительные характеристики
        preg_match('/<div class="specs[^>]*>(.*?)<\/div>/s', $html, $matches);
        if (isset($matches[1])) {
            $data['specs'] = $this->parse_specs($matches[1]);
        }
        
        return $data;
    }

    /**
     * Импорт с Aisins.ru
     */
    private function import_from_aisins($params = array()) {
        $this->log_message('Starting import from Aisins.ru', 'info');
        
        $base_url = 'https://aisins.ru';
        $transmissions = array();
        
        // Получение каталога АКПП Aisin
        $html = $this->fetch_url($base_url . '/catalog/');
        
        if (!$html) {
            $this->log_message('Failed to fetch Aisins.ru', 'error');
            return false;
        }
        
        // Парсинг списка моделей
        preg_match_all('/<div class="aisin-model[^"]*">(.*?)<\/div>/s', $html, $matches);
        
        foreach ($matches[1] as $model) {
            $transmission = $this->parse_aisins_model($model);
            
            if ($transmission && !empty($transmission['code'])) {
                // Дополнительная информация со страницы модели
                if (!empty($transmission['url'])) {
                    $detail_html = $this->fetch_url($transmission['url']);
                    if ($detail_html) {
                        $transmission = array_merge($transmission, $this->parse_aisins_detail($detail_html));
                    }
                }
                
                $transmissions[] = $transmission;
                $this->save_transmission($transmission, 'aisins');
            }
        }
        
        $this->log_message(sprintf('Imported %d Aisin transmissions', count($transmissions)), 'success');
        
        return array(
            'count' => count($transmissions),
            'transmissions' => $transmissions,
            'stats' => $this->stats
        );
    }

    /**
     * Парсинг модели Aisin
     */
    private function parse_aisins_model($html) {
        $data = array(
            'manufacturer' => 'Aisin'
        );
        
        // Код модели
        preg_match('/<h3[^>]*>(AW|Aisin)?\s*([A-Z0-9\-]+)<\/h3>/i', $html, $matches);
        $data['code'] = isset($matches[2]) ? trim($matches[2]) : '';
        
        // Название
        preg_match('/<div class="model-name[^>]*>([^<]+)<\/div>/', $html, $matches);
        $data['name'] = isset($matches[1]) ? trim($matches[1]) : '';
        
        // URL страницы
        preg_match('/<a href="([^"]+)" class="model-link">/', $html, $matches);
        $data['url'] = isset($matches[1]) ? $matches[1] : '';
        
        // Количество передач
        preg_match('/(\d+)[-\s]*(?:ступ|speed|передача)/i', $html, $matches);
        $data['gears'] = isset($matches[1]) ? intval($matches[1]) : 0;
        
        // Тип
        preg_match('/(AT|CVT|AMT|DSG)/i', $html, $matches);
        $data['type'] = isset($matches[1]) ? strtoupper($matches[1]) : 'AT';
        
        return $data;
    }

    /**
     * Парсинг детальной страницы Aisin
     */
    private function parse_aisins_detail($html) {
        $data = array();
        
        // Технические характеристики
        preg_match('/<table class="specs-table">(.*?)<\/table>/s', $html, $matches);
        if (isset($matches[1])) {
            $data['specs'] = $this->parse_aisins_specs($matches[1]);
        }
        
        // Применяемость
        preg_match_all('/<div class="car-application[^>]*>([^<]+)<\/div>/', $html, $matches);
        $data['applications'] = isset($matches[1]) ? $matches[1] : array();
        
        // Описание
        preg_match('/<div class="description[^>]*>(.*?)<\/div>/s', $html, $matches);
        $data['description'] = isset($matches[1]) ? trim(strip_tags($matches[1])) : '';
        
        return $data;
    }

    /**
     * Парсинг спецификаций Aisin
     */
    private function parse_aisins_specs($html) {
        $specs = array();
        
        preg_match_all('/<tr[^>]*>.*?<td[^>]*>([^<]+)<\/td>.*?<td[^>]*>([^<]+)<\/td>.*?<\/tr>/s', $html, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $key = trim(strip_tags($match[1]));
            $value = trim(strip_tags($match[2]));
            $specs[sanitize_title($key)] = array(
                'label' => $key,
                'value' => $value
            );
        }
        
        return $specs;
    }

    /**
     * Импорт с TransPartsOnline.com
     */
    private function import_from_transpartsonline($params = array()) {
        $this->log_message('Starting import from TransPartsOnline.com', 'info');
        
        $base_url = 'https://transpartsonline.com';
        $transmissions = array();
        
        // Получение списка трансмиссий
        $html = $this->fetch_url($base_url . '/transmissions/');
        
        if (!$html) {
            $this->log_message('Failed to fetch TransPartsOnline.com', 'error');
            return false;
        }
        
        // Парсинг списка
        preg_match_all('/<div class="transmission-card[^"]*">(.*?)<\/div>/s', $html, $matches);
        
        foreach ($matches[1] as $card) {
            $transmission = $this->parse_tpo_card($card);
            
            if ($transmission && !empty($transmission['code'])) {
                $transmissions[] = $transmission;
                $this->save_transmission($transmission, 'transpartsonline');
            }
        }
        
        $this->log_message(sprintf('Imported %d transmissions from TransPartsOnline', count($transmissions)), 'success');
        
        return array(
            'count' => count($transmissions),
            'transmissions' => $transmissions,
            'stats' => $this->stats
        );
    }

    /**
     * Парсинг карточки TransPartsOnline
     */
    private function parse_tpo_card($html) {
        $data = array();
        
        // Код трансмиссии
        preg_match('/<h2[^>]*>([^<]+)<\/h2>/', $html, $matches);
        $data['code'] = isset($matches[1]) ? trim($matches[1]) : '';
        
        // Производитель
        preg_match('/<div class="manufacturer[^>]*>([^<]+)<\/div>/', $html, $matches);
        $data['manufacturer'] = isset($matches[1]) ? trim($matches[1]) : '';
        
        // Полное название
        preg_match('/<div class="full-name[^>]*>([^<]+)<\/div>/', $html, $matches);
        $data['name'] = isset($matches[1]) ? trim($matches[1]) : '';
        
        // Технические данные
        preg_match('/<div class="tech-specs[^>]*>(.*?)<\/div>/s', $html, $matches);
        if (isset($matches[1])) {
            $data['tech_specs'] = $this->parse_tpo_specs($matches[1]);
        }
        
        // Применяемость
        preg_match_all('/<span class="vehicle[^>]*>([^<]+)<\/span>/', $html, $matches);
        $data['vehicles'] = isset($matches[1]) ? $matches[1] : array();
        
        // Номер детали
        preg_match('/<div class="part-number[^>]*>([^<]+)<\/div>/', $html, $matches);
        $data['part_number'] = isset($matches[1]) ? trim($matches[1]) : '';
        
        return $data;
    }

    /**
     * Парсинг спецификаций TPO
     */
    private function parse_tpo_specs($html) {
        $specs = array();
        
        preg_match_all('/<span class="spec-label[^>]*>([^<]+)<\/span>\s*:\s*<span class="spec-value[^>]*>([^<]+)<\/span>/', $html, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $key = trim($match[1]);
            $value = trim($match[2]);
            $specs[sanitize_title($key)] = array(
                'label' => $key,
                'value' => $value
            );
        }
        
        return $specs;
    }

    /**
     * Получение URL с обработкой ошибок
     */
    private function fetch_url($url, $retry = 0) {
        $args = array(
            'timeout' => $this->settings['timeout'],
            'user-agent' => $this->settings['user_agent'],
            'sslverify' => false
        );
        
        $response = wp_remote_get($url, $args);
        
        if (is_wp_error($response)) {
            if ($retry < $this->settings['max_retries']) {
                sleep(2);
                return $this->fetch_url($url, $retry + 1);
            }
            $this->log_message('Error fetching ' . $url . ': ' . $response->get_error_message(), 'error');
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        
        // Задержка между запросами
        sleep($this->settings['delay_between_requests']);
        
        return $body;
    }

    /**
     * Сохранение трансмиссии в базу
     */
    private function save_transmission($data, $source) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'akpp_transmissions';
        
        // Проверка существования
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$table_name} WHERE code = %s AND source = %s",
            $data['code'],
            $source
        ));
        
        $record = array(
            'code' => $data['code'],
            'name' => isset($data['name']) ? $data['name'] : '',
            'manufacturer' => isset($data['manufacturer']) ? $data['manufacturer'] : '',
            'gears' => isset($data['gears']) ? $data['gears'] : 0,
            'type' => isset($data['type']) ? $data['type'] : '',
            'source' => $source,
            'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
            'updated_at' => current_time('mysql')
        );
        
        if ($existing) {
            $wpdb->update(
                $table_name,
                $record,
                array('id' => $existing->id)
            );
            $this->stats['updated']++;
        } else {
            $record['created_at'] = current_time('mysql');
            $wpdb->insert($table_name, $record);
            $this->stats['imported']++;
        }
    }

    /**
     * Парсинг общих спецификаций
     */
    private function parse_specs($html) {
        $specs = array();
        
        preg_match_all('/<span[^>]*class="spec"[^>]*>([^<]+)<\/span>/', $html, $matches);
        
        foreach ($matches[1] as $spec) {
            if (strpos($spec, ':') !== false) {
                list($key, $value) = explode(':', $spec, 2);
                $specs[sanitize_title(trim($key))] = trim($value);
            }
        }
        
        return $specs;
    }

    /**
     * Логирование
     */
    public function log_message($message, $level = 'info') {
        $this->log[] = array(
            'time' => current_time('mysql'),
            'level' => $level,
            'message' => $message
        );
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf('[AKPP Import] [%s] %s', strtoupper($level), $message));
        }
    }

    /**
     * Сброс статистики
     */
    private function reset_stats() {
        $this->stats = array(
            'imported' => 0,
            'updated' => 0,
            'errors' => 0,
            'skipped' => 0
        );
        $this->log = array();
    }

    /**
     * Получение статистики
     */
    public function get_stats() {
        return $this->stats;
    }

    /**
     * Получение логов
     */
    public function get_logs() {
        return $this->log;
    }

    /**
     * Очистка базы трансмиссий
     */
    public function clear_database() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'akpp_transmissions';
        $wpdb->query("TRUNCATE TABLE {$table_name}");
        
        $this->log_message('Database cleared', 'info');
    }

    /**
     * Экспорт данных
     */
    public function export_to_csv($filename = 'akpp_transmissions.csv') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'akpp_transmissions';
        $transmissions = $wpdb->get_results("SELECT * FROM {$table_name}", ARRAY_A);
        
        $output = fopen('php://output', 'w');
        
        // Заголовки
        fputcsv($output, array(
            'ID',
            'Code',
            'Name',
            'Manufacturer',
            'Gears',
            'Type',
            'Source',
            'Created At',
            'Updated At'
        ));
        
        // Данные
        foreach ($transmissions as $trans) {
            fputcsv($output, array(
                $trans['id'],
                $trans['code'],
                $trans['name'],
                $trans['manufacturer'],
                $trans['gears'],
                $trans['type'],
                $trans['source'],
                $trans['created_at'],
                $trans['updated_at']
            ));
        }
        
        fclose($output);
    }
}

// Инициализация
function akpp_get_importer() {
    static $importer = null;
    
    if ($importer === null) {
        $importer = new TransAKPP_Importer();
    }
    
    return $importer;
}