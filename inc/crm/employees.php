<?php
/**
 * Шаблон: Сотрудники
 * @package AKPP_Kurgan
 */

if (!defined('ABSPATH')) exit;
?>

<div class="wrap akpp-crm-dashboard">
    <h1>👥 Сотрудники</h1>
    
    <div class="akpp-form-section">
        <button type="button" class="button button-primary" onclick="jQuery('#add-employee-form').toggle();">
            ➕ Добавить сотрудника
        </button>
        
        <div id="add-employee-form" style="display: none; margin-top: 20px;">
            <form id="employee-form" method="post">
                <div class="form-row">
                    <div class="form-group">
                        <label>ФИО *</label>
                        <input type="text" name="name" required placeholder="Иванов Иван Иванович">
                    </div>
                    <div class="form-group">
                        <label>Телефон</label>
                        <input type="text" name="phone" placeholder="+7 (999) 123-45-67">
                    </div>
                    <div class="form-group">
                        <label>Должность</label>
                        <input type="text" name="position" placeholder="Мастер">
                    </div>
                    <div class="form-group">
                        <label>Тип оплаты</label>
                        <select name="payment_type">
                            <option value="percent">Процент</option>
                            <option value="fixed">Фикс</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Ставка (%)</label>
                        <input type="number" name="percent" value="0" min="0" max="100" step="0.01">
                    </div>
                    <div class="form-group">
                        <label>Статус</label>
                        <select name="is_active">
                            <option value="1">Активен</option>
                            <option value="0">Неактивен</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="button button-primary">💾 Сохранить</button>
                    <span id="form-response"></span>
                </div>
            </form>
        </div>
    </div>
    
    <div class="akpp-form-section">
        <h3>📋 Список сотрудников</h3>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>ФИО</th>
                    <th>Должность</th>
                    <th>Телефон</th>
                    <th>Тип оплаты</th>
                    <th>Ставка</th>
                    <th>Статус</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($employees)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px;">
                            <span style="color: #6b7280;">📭 Сотрудников пока нет</span>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($employees as $emp): ?>
                        <tr>
                            <td data-label="ID">#<?php echo esc_html($emp->id); ?></td>
                            <td data-label="ФИО"><?php echo esc_html($emp->name); ?></td>
                            <td data-label="Должность"><?php echo esc_html($emp->position ?? '—'); ?></td>
                            <td data-label="Телефон"><?php echo esc_html($emp->phone ?? '—'); ?></td>
                            <td data-label="Тип оплаты">
                                <?php echo $emp->payment_type === 'percent' ? 'Процент' : 'Фикс'; ?>
                            </td>
                            <td data-label="Ставка">
                                <?php echo esc_html($emp->percent ?? '0'); ?>%
                            </td>
                            <td data-label="Статус">
                                <span class="status-badge <?php echo $emp->is_active ? 'status-completed' : 'status-rejected'; ?>">
                                    <?php echo $emp->is_active ? 'Активен' : 'Неактивен'; ?>
                                </span>
                            </td>
                            <td data-label="Действия">
                                <button type="button" class="button" onclick="editEmployee(<?php echo $emp->id; ?>)">✏️</button>
                                <button type="button" class="button" onclick="deleteEmployee(<?php echo $emp->id; ?>)">🗑️</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#employee-form').on('submit', function(e) {
        e.preventDefault();
        
        $.post(akppCrm.ajaxUrl, {
            action: 'akpp_save_employee',
            nonce: akppCrm.nonce,
            data: $(this).serialize()
        }, function(response) {
            if (response.success) {
                $('#form-response').css('color', '#10b981').text('✅ ' + response.data.message);
                setTimeout(function() { location.reload(); }, 1000);
            } else {
                $('#form-response').css('color', '#ef4444').text('❌ ' + response.data.message);
            }
        });
    });
});

function editEmployee(id) {
    alert('Редактирование сотрудника ' + id);
}

function deleteEmployee(id) {
    if (confirm('Удалить сотрудника?')) {
        $.post(akppCrm.ajaxUrl, {
            action: 'akpp_delete_employee',
            nonce: akppCrm.nonce,
            id: id
        }, function(response) {
            if (response.success) location.reload();
            else alert('Ошибка: ' + response.data.message);
        });
    }
}
</script>