<?php
/**
 * CRM Leads Management
 * @package AKPP_Kurgan
 * @version 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class AKPP_Leads {
    
    private $statuses = array(
        'new' => '🆕 Новый',
        'contacted' => '📞 На связи',
        'interested' => '💡 Заинтересован',
        'negotiation' => '💬 Согласование',
        'converted' => '✅ В сделку',
        'rejected' => '❌ Отказ',
        'cold' => '🧊 Холодный',
    );
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu'));
        add_action('wp_ajax_akpp_save_lead', array($this, 'ajax_save_lead'));
        add_action('wp_ajax_akpp_get_leads', array($this, 'ajax_get_leads'));
        add_action('wp_ajax_akpp_update_lead_status', array($this, 'ajax_update_status'));
        add_action('wp_ajax_akpp_convert_lead_to_deal', array($this, 'ajax_convert_to_deal'));
        add_action('wp_ajax_akpp_get_lead_funnel_stats', array($this, 'ajax_funnel_stats'));
        add_action('wp_ajax_akpp_delete_lead', array($this, 'ajax_delete_lead'));
    }
    
    public function add_menu() {
        add_submenu_page(
            'akpp-crm',
            'Лиды',
            '🎯 Лиды',
            'manage_options',
            'akpp-leads',
            array($this, 'render_page')
        );
        add_submenu_page(
            'akpp-crm',
            'Воронка',
            '📊 Воронка',
            'manage_options',
            'akpp-funnel',
            array($this, 'render_funnel')
        );
    }
    
    public function render_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'akpp_leads';
        
        $status_filter = sanitize_text_field($_GET['status'] ?? '');
        $source_filter = sanitize_text_field($_GET['source'] ?? '');
        $search = sanitize_text_field($_GET['s'] ?? '');
        
        $where = array('1=1');
        $params = array();
        
        if ($status_filter) {
            $where[] = 'status = %s';
            $params[] = $status_filter;
        }
        if ($source_filter) {
            $where[] = 'source = %s';
            $params[] = $source_filter;
        }
        if ($search) {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $where[] = '(name LIKE %s OR phone LIKE %s OR message LIKE %s)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        
        $where_sql = implode(' AND ', $where);
        
        if (empty($params)) {
            $leads = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC LIMIT 100");
        } else {
            $leads = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table WHERE $where_sql ORDER BY created_at DESC LIMIT 100",
                $params
            ));
        }
        
        include get_template_directory() . '/inc/crm/templates/leads.php';
    }
    
    public function render_funnel() {
        include get_template_directory() . '/inc/crm/templates/funnel.php';
    }
    
    public function ajax_save_lead() {
        check_ajax_referer('akpp_crm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Недостаточно прав'));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'akpp_leads';
        
        $lead_id = intval($_POST['lead_id'] ?? 0);
        
        $data = array(
            'source' => sanitize_text_field($_POST['source'] ?? 'manual'),
            'source_id' => sanitize_text_field($_POST['source_id'] ?? ''),
            'name' => sanitize_text_field($_POST['name'] ?? ''),
            'phone' => sanitize_text_field($_POST['phone'] ?? ''),
            'email' => sanitize_email($_POST['email'] ?? ''),
            'message' => sanitize_textarea_field($_POST['message'] ?? ''),
            'vehicle_info' => sanitize_text_field($_POST['vehicle_info'] ?? ''),
            'transmission_code' => sanitize_text_field($_POST['transmission_code'] ?? ''),
            'status' => sanitize_text_field($_POST['status'] ?? 'new'),
            'assigned_to' => intval($_POST['assigned_to'] ?? 0),
            'utm_source' => sanitize_text_field($_POST['utm_source'] ?? ''),
            'utm_medium' => sanitize_text_field($_POST['utm_medium'] ?? ''),
            'utm_campaign' => sanitize_text_field($_POST['utm_campaign'] ?? ''),
        );
        
        if (empty($data['name']) && empty($data['phone'])) {
            wp_send_json_error(array('message' => 'Укажите имя или телефон'));
        }
        
        if ($lead_id > 0) {
            $data['updated_at'] = current_time('mysql');
            $result = $wpdb->update($table, $data, array('id' => $lead_id));
            
            if ($result !== false) {
                wp_send_json_success(array('message' => 'Лид обновлен', 'id' => $lead_id));
            } else {
                wp_send_json_error(array('message' => 'Ошибка обновления: ' . $wpdb->last_error));
            }
        } else {
            $data['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
            $data['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $data['created_at'] = current_time('mysql');
            $result = $wpdb->insert($table, $data);
            
            if ($result) {
                wp_send_json_success(array('message' => 'Лид создан', 'id' => $wpdb->insert_id));
            } else {
                wp_send_json_error(array('message' => 'Ошибка создания: ' . $wpdb->last_error));
            }
        }
    }
    
    public function ajax_get_leads() {
        check_ajax_referer('akpp_crm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Недостаточно прав'));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'akpp_leads';
        
        $status = sanitize_text_field($_POST['status'] ?? '');
        $page = intval($_POST['page'] ?? 1);
        $per_page = 50;
        
        $where = '1=1';
        $params = array();
        
        if ($status) {
            $where .= ' AND status = %s';
            $params[] = $status;
        }
        
        if (empty($params)) {
            $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table");
            $leads = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC LIMIT $per_page OFFSET " . (($page - 1) * $per_page));
        } else {
            $total = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE $where",
                $params
            ));
            $params[] = $per_page;
            $params[] = ($page - 1) * $per_page;
            $leads = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table WHERE $where ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $params
            ));
        }
        
        wp_send_json_success(array(
            'leads' => $leads ? $leads : array(),
            'total' => $total,
            'page' => $page,
            'pages' => ceil($total / $per_page)
        ));
    }
    
    public function ajax_update_status() {
        check_ajax_referer('akpp_crm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Недостаточно прав'));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'akpp_leads';
        
        $lead_id = intval($_POST['lead_id'] ?? 0);
        $new_status = sanitize_text_field($_POST['status'] ?? '');
        
        if (!array_key_exists($new_status, $this->statuses)) {
            wp_send_json_error(array('message' => 'Неверный статус'));
        }
        
        $lead = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $lead_id));
        if (!$lead) {
            wp_send_json_error(array('message' => 'Лид не найден'));
        }
        
        // Если статус меняется на "converted" - создаем сделку
        if ($new_status === 'converted' && $lead->status !== 'converted') {
            if (!$lead->deal_id) {
                $deal_id = $this->create_deal_from_lead($lead);
                
                if ($deal_id) {
                    $wpdb->update($table, 
                        array('status' => $new_status, 'deal_id' => $deal_id, 'updated_at' => current_time('mysql')),
                        array('id' => $lead_id)
                    );
                    wp_send_json_success(array('message' => 'Лид конвертирован в сделку #' . $deal_id, 'deal_id' => $deal_id));
                } else {
                    wp_send_json_error(array('message' => 'Ошибка создания сделки'));
                }
            } else {
                $wpdb->update($table, 
                    array('status' => $new_status, 'updated_at' => current_time('mysql')),
                    array('id' => $lead_id)
                );
                wp_send_json_success(array('message' => 'Статус обновлен (сделка #' . $lead->deal_id . ')'));
            }
        } else {
            $wpdb->update($table, 
                array('status' => $new_status, 'updated_at' => current_time('mysql')),
                array('id' => $lead_id)
            );
            wp_send_json_success(array('message' => 'Статус обновлен'));
        }
    }
    
    private function create_deal_from_lead($lead) {
        global $wpdb;
        $deals_table = $wpdb->prefix . 'akpp_deals';
        
        $vehicle_parts = explode(' ', $lead->vehicle_info);
        $brand = $vehicle_parts[0] ?? '';
        $model = $vehicle_parts[1] ?? '';
        
        $deal_data = array(
            'client_name' => $lead->name,
            'client_phone' => $lead->phone,
            'client_vin' => '',
            'client_body_number' => '',
            'client_plate' => '',
            'car_brand' => $brand,
            'car_model' => $model,
            'car_year' => 0,
            'transmission_code' => $lead->transmission_code,
            'employee_id' => 0,
            'problem_description' => $lead->message,
            'parts_cost' => 0,
            'employee_payment' => 0,
            'deal_price' => 0,
            'status' => 'new',
            'notes' => 'Создано из лида #' . $lead->id,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        );
        
        $result = $wpdb->insert($deals_table, $deal_data);
        
        return $result ? $wpdb->insert_id : false;
    }
    
    public function ajax_convert_to_deal() {
        check_ajax_referer('akpp_crm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Недостаточно прав'));
        }
        
        global $wpdb;
        $leads_table = $wpdb->prefix . 'akpp_leads';
        
        $lead_id = intval($_POST['lead_id'] ?? 0);
        $lead = $wpdb->get_row($wpdb->prepare("SELECT * FROM $leads_table WHERE id = %d", $lead_id));
        
        if (!$lead) {
            wp_send_json_error(array('message' => 'Лид не найден'));
        }
        
        if ($lead->deal_id) {
            wp_send_json_success(array('message' => 'Сделка уже создана #' . $lead->deal_id, 'deal_id' => $lead->deal_id));
            return;
        }
        
        $deal_id = $this->create_deal_from_lead($lead);
        
        if ($deal_id) {
            $wpdb->update($leads_table, 
                array('status' => 'converted', 'deal_id' => $deal_id, 'updated_at' => current_time('mysql')),
                array('id' => $lead_id)
            );
            wp_send_json_success(array('message' => 'Лид конвертирован в сделку #' . $deal_id, 'deal_id' => $deal_id));
        } else {
            wp_send_json_error(array('message' => 'Ошибка создания сделки'));
        }
    }
    
    public function ajax_delete_lead() {
        check_ajax_referer('akpp_crm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Недостаточно прав'));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'akpp_leads';
        
        $lead_id = intval($_POST['lead_id'] ?? 0);
        
        if ($lead_id <= 0) {
            wp_send_json_error(array('message' => 'Неверный ID'));
        }
        
        $result = $wpdb->delete($table, array('id' => $lead_id));
        
        if ($result) {
            wp_send_json_success(array('message' => 'Лид удален'));
        } else {
            wp_send_json_error(array('message' => 'Ошибка удаления'));
        }
    }
    
    public function ajax_funnel_stats() {
        check_ajax_referer('akpp_crm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Недостаточно прав'));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'akpp_leads';
        
        $stats = array();
        foreach (array_keys($this->statuses) as $status) {
            $stats[$status] = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE status = %s",
                $status
            ));
        }
        
        $stats['total'] = array_sum($stats);
        $stats['conversion_rate'] = $stats['total'] > 0 
            ? round(($stats['converted'] / $stats['total']) * 100, 1) 
            : 0;
        
        wp_send_json_success($stats);
    }
    
    public function get_statuses() {
        return $this->statuses;
    }
}

new AKPP_Leads();