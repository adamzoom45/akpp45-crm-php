<?php
/**
 * CRM Configuration
 * @package AKPP_Kurgan
 */

if (!defined('ABSPATH')) {
    exit;
}

// Constants
define('AKPP_CRM_VERSION', '1.0.0');
define('AKPP_CRM_NONCE', 'akpp_crm_nonce');

// Statuses
define('AKPP_STATUS_NEW', 'new');
define('AKPP_STATUS_DIAGNOSTIC', 'diagnostic');
define('AKPP_STATUS_NEGOTIATION', 'negotiation');
define('AKPP_STATUS_IN_WORK', 'in_work');
define('AKPP_STATUS_COMPLETED', 'completed');
define('AKPP_STATUS_REJECTED', 'rejected');

// Salary Types
define('AKPP_SALARY_PERCENT', 'percent');
define('AKPP_SALARY_FIXED', 'fixed');

// Helper: Get status label
function akpp_get_status_label($status) {
    $labels = array(
        AKPP_STATUS_NEW        => '🆕 Новая',
        AKPP_STATUS_DIAGNOSTIC => '🔍 Диагностика',
        AKPP_STATUS_NEGOTIATION => '💬 Согласование',
        AKPP_STATUS_IN_WORK    => '🔧 В работе',
        AKPP_STATUS_COMPLETED  => '✅ Завершено',
        AKPP_STATUS_REJECTED   => '❌ Отказ',
    );
    return $labels[$status] ?? $status;
}

// Helper: Get status color
function akpp_get_status_color($status) {
    $colors = array(
        AKPP_STATUS_NEW        => '#3b82f6',
        AKPP_STATUS_DIAGNOSTIC => '#f59e0b',
        AKPP_STATUS_NEGOTIATION => '#8b5cf6',
        AKPP_STATUS_IN_WORK    => '#f97316',
        AKPP_STATUS_COMPLETED  => '#10b981',
        AKPP_STATUS_REJECTED   => '#ef4444',
    );
    return $colors[$status] ?? '#6b7280';
}

// Helper: Format money
function akpp_format_money($amount) {
    return number_format((float)$amount, 0, ',', ' ') . ' ₽';
}

// Helper: Sanitize phone
function akpp_sanitize_phone($phone) {
    return preg_replace('/[^0-9+]/', '', $phone);
}