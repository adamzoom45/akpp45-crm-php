<?php
/**
 * Шаблон: База автомобилей
 * @package AKPP_Kurgan
 */

if (!defined('ABSPATH')) exit;
?>

<div class="wrap akpp-crm-dashboard">
    <h1>🚗 База автомобилей</h1>
    
    <div class="akpp-form-section">
        <button type="button" class="button button-primary" onclick="jQuery('#add-vehicle-form').toggle();">
            ➕ Добавить авто
        </button>
        
        <div id="add-vehicle-form" style="display: none; margin-top: 20px;">
            <form id="vehicle-form" method="post">
                <div class="form-row">
                    <div class="form-group">
                        <label>Марка *</label>
                        <input type="text" name="brand" required placeholder="Ford">
                    </div>
                    <div class="form-group">
                        <label>Модель *</label>
                        <input type="text" name="model" required placeholder="Bronco">
                    </div>
                    <div class="form-group">
                        <label>Год от</label>
                        <input type="number" name="year_from" placeholder="2021">
                    </div>
                    <div class="form-group">
                        <label>Год до</label>
                        <input type="number" name="year_to" placeholder="2023">
                    </div>
                    <div class="form-group">
                        <label>Двигатель</label>
                        <input type="text" name="engine" placeholder="2.7L EcoBoost">
                    </div>
                    <div class="form-group">
                        <label>Привод</label>
                        <select name="drive_type">
                            <option value="">—</option>
                            <option value="4WD">4WD</option>
                            <option value="AWD">AWD</option>
                            <option value="2WD">2WD</option>
                            <option value="FWD">FWD</option>
                            <option value="RWD">RWD</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Код АКПП</label>
                        <input type="text" name="transmission_code" placeholder="10R80">
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
        <h3>📋 Список автомобилей (<?php echo count($vehicles); ?>)</h3>
        
        <?php if (empty($vehicles)): ?>
            <div style="text-align: center; padding: 40px; color: #9ca3af;">
                <div style="font-size: 48px; margin-bottom: 10px;">🚗</div>
                <p>Автомобилей пока нет</p>
                <p style="font-size: 13px;">Нажмите "Добавить авто" чтобы создать первую запись</p>
            </div>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Марка</th>
                        <th>Модель</th>
                        <th>Годы</th>
                        <th>Двигатель</th>
                        <th>Привод</th>
                        <th>Код АКПП</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vehicles as $vehicle): ?>
                        <tr>
                            <td data-label="Марка"><strong><?php echo esc_html($vehicle->brand); ?></strong></td>
                            <td data-label="Модель"><?php echo esc_html($vehicle->model); ?></td>
                            <td data-label="Годы">
                                <?php 
                                echo esc_html($vehicle->year_from ?? ''); 
                                if (!empty($vehicle->year_to)) {
                                    echo ' - ' . esc_html($vehicle->year_to);
                                }
                                ?>
                            </td>
                            <td data-label="Двигатель"><?php echo esc_html($vehicle->engine ?? '—'); ?></td>
                            <td data-label="Привод">
                                <?php 
                                $drive = $vehicle->drive_type ?? '';
                                if ($drive === '4WD') echo '🔷 4WD';
                                elseif ($drive === 'AWD') echo ' AWD';
                                elseif ($drive === 'FWD') echo '🔹 FWD';
                                elseif ($drive === 'RWD') echo '🔸 RWD';
                                else echo '—';
                                ?>
                            </td>
                            <td data-label="Код АКПП">
                                <?php if (!empty($vehicle->transmission_code)): ?>
                                    <span class="status-badge" style="background: rgba(0,255,136,0.2); color: #00ff88; border: 1px solid rgba(0,255,136,0.4);">
                                        <?php echo esc_html($vehicle->transmission_code); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: #6b7280;">—</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Действия">
                                <button type="button" class="button" onclick="editVehicle(<?php echo intval($vehicle->id); ?>)" title="Редактировать">✏️</button>
                                <button type="button" class="button" onclick="deleteVehicle(<?php echo intval($vehicle->id); ?>)" title="Удалить">🗑️</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Сохранение автомобиля
    $('#vehicle-form').on('submit', function(e) {
        e.preventDefault();
        
        const btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).text('⏳ Сохранение...');
        
        $.post(akppCrm.ajaxUrl, {
            action: 'akpp_add_vehicle_to_catalog',
            nonce: akppCrm.nonce,
            brand: $('input[name="brand"]').val(),
            model: $('input[name="model"]').val(),
            year_from: $('input[name="year_from"]').val(),
            year_to: $('input[name="year_to"]').val(),
            engine: $('input[name="engine"]').val(),
            drive_type: $('select[name="drive_type"]').val(),
            transmission_code: $('input[name="transmission_code"]').val()
        }, function(response) {
            btn.prop('disabled', false).text('💾 Сохранить');
            
            if (response.success) {
                $('#form-response').css('color', '#10b981').text('✅ ' + response.data.message);
                setTimeout(function() { location.reload(); }, 1000);
            } else {
                $('#form-response').css('color', '#ef4444').text('❌ ' + response.data.message);
            }
        }).fail(function() {
            btn.prop('disabled', false).text('💾 Сохранить');
            $('#form-response').css('color', '#ef4444').text('❌ Ошибка соединения');
        });
    });
});

function editVehicle(id) {
    alert('Редактирование авто #' + id + '\n\nФункция в разработке');
}

function deleteVehicle(id) {
    if (!confirm('Удалить автомобиль #' + id + '?')) return;
    
    $.post(akppCrm.ajaxUrl, {
        action: 'akpp_delete_vehicle',
        nonce: akppCrm.nonce,
        id: id
    }, function(response) {
        if (response.success) {
            location.reload();
        } else {
            alert('❌ ' + response.data.message);
        }
    }).fail(function() {
        alert('❌ Ошибка соединения');
    });
}
</script>