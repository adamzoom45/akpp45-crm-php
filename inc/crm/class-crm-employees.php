<?php
/**
 * CRM Employees Management
 * @package AKPP_Kurgan
 */

if (!defined('ABSPATH')) {
    exit;
}

class AKPP_CRM_Employees {
    
    public function __construct() {
        add_action('wp_ajax_akpp_save_employee', array($this, 'save_employee'));
        add_action('wp_ajax_akpp_delete_employee', array($this, 'delete_employee'));
        add_action('wp_ajax_akpp_get_employees', array($this, 'get_employees'));
    }
    
    public function save_employee() {
        check_ajax_referer(AKPP_CRM_NONCE, 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Нет доступа'));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'akpp_employees';
        
        $employee_id = intval($_POST['employee_id'] ?? 0);
        
        $data = array(
            'name'           => sanitize_text_field($_POST['name'] ?? ''),
            'position'       => sanitize_text_field($_POST['position'] ?? ''),
            'phone'          => akpp_sanitize_phone($_POST['phone'] ?? ''),
            'email'          => sanitize_email($_POST['email'] ?? ''),
            'salary_type'    => sanitize_text_field($_POST['salary_type'] ?? AKPP_SALARY_PERCENT),
            'salary_percent' => floatval($_POST['salary_percent'] ?? 0),
            'fixed_salary'   => floatval($_POST['fixed_salary'] ?? 0),
            'is_active'      => intval($_POST['is_active'] ?? 1),
        );
        
        if (empty($data['name'])) {
            wp_send_json_error(array('message' => 'Укажите ФИО'));
        }
        
        if ($data['salary_type'] === AKPP_SALARY_PERCENT && ($data['salary_percent'] <= 0 || $data['salary_percent'] > 100)) {
            wp_send_json_error(array('message' => 'Процент должен быть от 0 до 100'));
        }
        
        if ($employee_id > 0) {
            $data['updated_at'] = current_time('mysql');
            $result = $wpdb->update($table, $data, array('id' => $employee_id));
            
            if ($result !== false) {
                wp_send_json_success(array('message' => 'Сотрудник обновлен'));
            } else {
                wp_send_json_error(array('message' => 'Ошибка обновления'));
            }
        } else {
            $data['created_at'] = current_time('mysql');
            $result = $wpdb->insert($table, $data);
            
            if ($result) {
                wp_send_json_success(array('message' => 'Сотрудник добавлен', 'employee_id' => $wpdb->insert_id));
            } else {
                wp_send_json_error(array('message' => 'Ошибка добавления'));
            }
        }
    }
    
    public function delete_employee() {
        check_ajax_referer(AKPP_CRM_NONCE, 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Нет доступа'));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'akpp_employees';
        
        $employee_id = intval($_POST['employee_id'] ?? 0);
        
        if ($employee_id <= 0) {
            wp_send_json_error(array('message' => 'Неверный ID'));
        }
        
        $result = $wpdb->delete($table, array('id' => $employee_id));
        
        if ($result) {
            wp_send_json_success(array('message' => 'Сотрудник удален'));
        } else {
            wp_send_json_error(array('message' => 'Ошибка удаления'));
        }
    }
    
    public function get_employees() {
        check_ajax_referer(AKPP_CRM_NONCE, 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Нет доступа'));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'akpp_employees';
        
        $active_only = isset($_POST['active_only']) && $_POST['active_only'] === '1';
        
        $where = $active_only ? 'WHERE is_active = 1' : '';
        
        $employees = $wpdb->get_results("SELECT * FROM $table $where ORDER BY name ASC");
        
        wp_send_json_success($employees);
    }
}

new AKPP_CRM_Employees();