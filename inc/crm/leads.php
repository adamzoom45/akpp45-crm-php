<?php
if (!defined('ABSPATH')) exit;
?>

<div class="wrap akpp-crm-dashboard">
    <h1>🎯 Лиды</h1>
    
    <div class="akpp-form-section">
        <h3>➕ Добавить лид</h3>
        <form id="add-lead-form">
            <div class="form-row">
                <div class="form-group">
                    <label>Источник</label>
                    <select name="source">
                        <option value="manual"> Вручную</option>
                        <option value="telegram">💬 Telegram</option>
                        <option value="avito">🛒 Avito</option>
                        <option value="2gis">️ 2GIS</option>
                        <option value="website">🌐 Сайт</option>
                        <option value="phone">📞 Телефон</option>
                        <option value="walk-in">🚶 С улицы</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Имя</label>
                    <input type="text" name="name" placeholder="Иван Иванов">
                </div>
                <div class="form-group">
                    <label>Телефон *</label>
                    <input type="tel" name="phone" placeholder="+7 (999) 123-45-67" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="email@example.com">
                </div>
                <div class="form-group">
                    <label>Авто</label>
                    <input type="text" name="vehicle_info" placeholder="Toyota Camry 2020">
                </div>
                <div class="form-group">
                    <label>Код АКПП</label>
                    <input type="text" name="transmission_code" placeholder="U660E">
                </div>
            </div>
            <div class="form-group">
                <label>Сообщение / Проблема</label>
                <textarea name="message" rows="3" placeholder="Опишите проблему..."></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="button button-primary">💾 Сохранить лид</button>
            </div>
        </form>
    </div>

    <div class="akpp-form-section">
        <h3>📋 Все лиды (<?php echo count($leads); ?>)</h3>
        
        <div style="margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
            <select id="filter-status" style="padding: 8px; border-radius: 6px; border: 1px solid rgba(0,255,136,0.3); background: #0a0f1c; color: #fff;">
                <option value="">Все статусы</option>
                <?php foreach ($this->statuses as $key => $label) : ?>
                    <option value="<?php echo $key; ?>" <?php echo $status_filter === $key ? 'selected' : ''; ?>>
                        <?php echo $label; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select id="filter-source" style="padding: 8px; border-radius: 6px; border: 1px solid rgba(0,255,136,0.3); background: #0a0f1c; color: #fff;">
                <option value="">Все источники</option>
                <option value="manual" <?php echo $source_filter === 'manual' ? 'selected' : ''; ?>> Вручную</option>
                <option value="telegram" <?php echo $source_filter === 'telegram' ? 'selected' : ''; ?>>💬 Telegram</option>
                <option value="avito" <?php echo $source_filter === 'avito' ? 'selected' : ''; ?>>🛒 Avito</option>
                <option value="2gis" <?php echo $source_filter === '2gis' ? 'selected' : ''; ?>>🗺️ 2GIS</option>
                <option value="website" <?php echo $source_filter === 'website' ? 'selected' : ''; ?>>🌐 Сайт</option>
            </select>
            <input type="text" id="search-leads" placeholder="Поиск..." style="padding: 8px; border-radius: 6px; border: 1px solid rgba(0,255,136,0.3); background: #0a0f1c; color: #fff; flex: 1; min-width: 200px;">
        </div>
        
        <table class="wp-list-table widefat striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Источник</th>
                    <th>Имя</th>
                    <th>Телефон</th>
                    <th>Авто</th>
                    <th>Статус</th>
                    <th>Дата</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($leads)) : ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px;">
                            <div style="color: #9ca3af;">
                                <div style="font-size: 48px; margin-bottom: 10px;">📭</div>
                                <div>Лидов пока нет</div>
                            </div>
                        </td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($leads as $lead) : 
                        $status_labels = array(
                            'new' => '🆕 Новый',
                            'contacted' => '📞 На связи',
                            'interested' => '💡 Интерес',
                            'negotiation' => '💬 Согласование',
                            'converted' => '✅ В сделку',
                            'rejected' => '❌ Отказ',
                            'cold' => '🧊 Холодный',
                        );
                        $source_icons = array(
                            'manual' => '📝',
                            'telegram' => '💬',
                            'avito' => '🛒',
                            '2gis' => '🗺️',
                            'website' => '🌐',
                            'phone' => '📞',
                            'walk-in' => '🚶',
                        );
                    ?>
                    <tr data-lead-id="<?php echo $lead->id; ?>">
                        <td>#<?php echo $lead->id; ?></td>
                        <td><?php echo $source_icons[$lead->source] ?? '📝'; ?> <?php echo esc_html($lead->source); ?></td>
                        <td><strong><?php echo esc_html($lead->name); ?></strong></td>
                        <td>
                            <?php if ($lead->phone) : ?>
                                <a href="tel:<?php echo esc_attr($lead->phone); ?>" style="color: #00ff88;">
                                    <?php echo esc_html($lead->phone); ?>
                                </a>
                            <?php else : ?>
                                <span style="color: #6b7280;">—</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html($lead->vehicle_info); ?></td>
                        <td>
                            <select class="lead-status-change" data-id="<?php echo $lead->id; ?>" style="padding: 6px 10px; border-radius: 6px; border: 1px solid rgba(0,255,136,0.3); background: #0a0f1c; color: #fff;">
                                <?php foreach ($status_labels as $k => $v) : ?>
                                    <option value="<?php echo $k; ?>" <?php echo $lead->status === $k ? 'selected' : ''; ?>>
                                        <?php echo $v; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td><?php echo date('d.m.Y H:i', strtotime($lead->created_at)); ?></td>
                        <td>
                            <div style="display: flex; gap: 5px;">
                                <?php if ($lead->deal_id) : ?>
                                    <a href="<?php echo admin_url('admin.php?page=akpp-new-deal&id=' . $lead->deal_id); ?>" class="button button-small" title="Перейти к сделке">
                                        🔗
                                    </a>
                                <?php endif; ?>
                                <button class="button button-small delete-lead" data-id="<?php echo $lead->id; ?>" title="Удалить">
                                    🗑️
                                </button>
                            </div>
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
    // Добавление лида
    $('#add-lead-form').on('submit', function(e) {
        e.preventDefault();
        
        const btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).text('⏳ Сохранение...');
        
        $.post(akppCrm.ajaxUrl, $(this).serialize() + '&action=akpp_save_lead&nonce=' + akppCrm.nonce, function(response) {
            btn.prop('disabled', false).text('💾 Сохранить лид');
            
            if (response.success) {
                alert('✅ ' + response.data.message);
                location.reload();
            } else {
                alert('❌ ' + response.data.message);
            }
        }).fail(function() {
            btn.prop('disabled', false).text('💾 Сохранить лид');
            alert('❌ Ошибка соединения');
        });
    });
    
    // Смена статуса
    $('.lead-status-change').on('change', function() {
        const id = $(this).data('id');
        const newStatus = $(this).val();
        const btn = $(this);
        
        btn.prop('disabled', true);
        
        $.post(akppCrm.ajaxUrl, {
            action: 'akpp_update_lead_status',
            nonce: akppCrm.nonce,
            lead_id: id,
            status: newStatus
        }, function(response) {
            btn.prop('disabled', false);
            
            if (response.success) {
                const msg = response.data.message || 'Статус обновлен';
                
                // Если создана сделка - предлагаем перейти
                if (response.data.deal_id) {
                    if (confirm('✅ ' + msg + '\n\nПерейти к сделке?')) {
                        window.location.href = '<?php echo admin_url("admin.php?page=akpp-new-deal"); ?>&id=' + response.data.deal_id;
                    } else {
                        location.reload();
                    }
                } else {
                    // Обычное обновление
                    const row = btn.closest('tr');
                    row.css('background', 'rgba(16, 185, 129, 0.2)');
                    setTimeout(function() { location.reload(); }, 800);
                }
            } else {
                alert('❌ ' + (response.data.message || 'Ошибка'));
                location.reload();
            }
        }).fail(function() {
            btn.prop('disabled', false);
            alert('❌ Ошибка соединения');
        });
    });
    
    // Удаление лида
    $('.delete-lead').on('click', function() {
        const id = $(this).data('id');
        
        if (!confirm('Удалить лид #' + id + '?')) return;
        
        const btn = $(this);
        btn.prop('disabled', true);
        
        $.post(akppCrm.ajaxUrl, {
            action: 'akpp_delete_lead',
            nonce: akppCrm.nonce,
            lead_id: id
        }, function(response) {
            if (response.success) {
                btn.closest('tr').fadeOut(300, function() {
                    location.reload();
                });
            } else {
                alert('❌ ' + response.data.message);
                btn.prop('disabled', false);
            }
        }).fail(function() {
            alert('❌ Ошибка соединения');
            btn.prop('disabled', false);
        });
    });
    
    // Фильтрация
    $('#filter-status, #filter-source').on('change', function() {
        const status = $('#filter-status').val();
        const source = $('#filter-source').val();
        
        let url = '<?php echo admin_url("admin.php?page=akpp-leads"); ?>';
        if (status) url += '&status=' + status;
        if (source) url += '&source=' + source;
        
        window.location.href = url;
    });
});
</script>