<?php
/**
 * Шаблон: Сделки
 * @package AKPP_Kurgan
 */

if (!defined('ABSPATH')) exit;
?>

<div class="wrap akpp-crm-dashboard">
    <h1>📋 Сделки</h1>
    
    <div class="akpp-quick-actions">
        <h2>⚡ Быстрые действия</h2>
        <div class="action-buttons">
            <a href="<?php echo admin_url('admin.php?page=akpp-new-deal'); ?>" class="button button-primary">
                ➕ Новая сделка
            </a>
            <button type="button" class="button" onclick="exportDeals()">📥 Экспорт</button>
        </div>
    </div>
    
    <div class="akpp-form-section">
        <h3>📊 Все сделки</h3>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Клиент</th>
                    <th>Автомобиль</th>
                    <th>Услуга</th>
                    <th>Сумма</th>
                    <th>Статус</th>
                    <th>Дата</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($deals)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px;">
                            <span style="color: #6b7280;">📭 Сделок пока нет</span>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($deals as $deal): ?>
                        <tr>
                            <td data-label="ID">#<?php echo esc_html($deal->id); ?></td>
                            <td data-label="Клиент">
                                <?php echo esc_html($deal->client_name ?? '—'); ?><br>
                                <small style="color: #9ca3af;"><?php echo esc_html($deal->client_phone ?? ''); ?></small>
                            </td>
                            <td data-label="Автомобиль">
                                <?php echo esc_html($deal->vehicle_info ?? '—'); ?>
                            </td>
                            <td data-label="Услуга">
                                <?php echo esc_html($deal->service_type ?? '—'); ?>
                            </td>
                            <td data-label="Сумма">
                                <strong style="color: #00ff88;">
                                    <?php echo number_format($deal->deal_price ?? 0, 0, '.', ' '); ?> ₽
                                </strong>
                            </td>
                            <td data-label="Статус">
                                <span class="status-badge status-<?php echo esc_attr($deal->status ?? 'new'); ?>">
                                    <?php
                                    $status_labels = array(
                                        'new' => 'Новая',
                                        'diagnostic' => 'Диагностика',
                                        'negotiation' => 'Согласование',
                                        'in_work' => 'В работе',
                                        'completed' => 'Завершена',
                                        'rejected' => 'Отменена'
                                    );
                                    echo $status_labels[$deal->status] ?? $deal->status;
                                    ?>
                                </span>
                            </td>
                            <td data-label="Дата">
                                <?php echo date('d.m.Y', strtotime($deal->created_at)); ?>
                            </td>
                            <td data-label="Действия">
                                <a href="<?php echo admin_url('admin.php?page=akpp-new-deal&id=' . $deal->id); ?>" 
                                   class="button">✏️</a>
                                <button type="button" class="button" onclick="deleteDeal(<?php echo $deal->id; ?>)">🗑️</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function exportDeals() {
    alert('Экспорт сделок в разработке');
}

function deleteDeal(id) {
    if (confirm('Удалить сделку?')) {
        $.post(akppCrm.ajaxUrl, {
            action: 'akpp_delete_deal',
            nonce: akppCrm.nonce,
            id: id
        }, function(response) {
            if (response.success) location.reload();
            else alert('Ошибка: ' + response.data.message);
        });
    }
}
</script>