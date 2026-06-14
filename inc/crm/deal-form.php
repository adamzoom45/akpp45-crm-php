<?php
if (!defined('ABSPATH')) exit;

if (!isset($deal)) $deal = null;
if (!isset($employees)) $employees = array();
if (!isset($brands)) $brands = array();
?>

<div class="wrap akpp-crm-dashboard">
    <h1 class="wp-heading-inline"><?php echo $deal ? '✏️ Редактирование сделки' : '➕ Новая сделка'; ?></h1>
    <a href="<?php echo admin_url('admin.php?page=akpp-deals'); ?>" class="page-title-action">← Назад к сделкам</a>

    <form id="akpp-deal-form">
        <input type="hidden" name="deal_id" id="deal-id" value="<?php echo $deal ? intval($deal->id) : 0; ?>">

        <!-- Клиент и Автомобиль -->
        <div class="akpp-form-section">
            <h3>👤 Клиент и Автомобиль</h3>

            <div class="form-row">
                <div class="form-group">
                    <label>VIN номер</label>
                    <div style="display:flex; gap:10px;">
                        <input type="text" name="client_vin" id="client-vin" 
                               value="<?php echo $deal ? esc_attr($deal->client_vin) : ''; ?>" 
                               maxlength="17" style="text-transform: uppercase; flex:1;">
                        <button type="button" id="decode-vin-btn" class="button">🔍 Расшифровать</button>
                    </div>
                </div>
                <div class="form-group">
                    <label>Номер кузова</label>
                    <div style="display:flex; gap:10px;">
                        <input type="text" name="client_body_number" id="client-body" 
                               value="<?php echo $deal ? esc_attr($deal->client_body_number) : ''; ?>" 
                               style="text-transform: uppercase; flex:1;">
                        <button type="button" id="decode-body-btn" class="button">🔍</button>
                    </div>
                </div>
            </div>

            <div id="vin-decode-result" style="display:none; margin-bottom:20px;"></div>

            <div class="form-row">
                <div class="form-group">
                    <label>ФИО клиента *</label>
                    <input type="text" name="client_name" 
                           value="<?php echo $deal ? esc_attr($deal->client_name) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label>Телефон *</label>
                    <input type="tel" name="client_phone" 
                           value="<?php echo $deal ? esc_attr($deal->client_phone) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label>Гос. номер</label>
                    <input type="text" name="client_plate" 
                           value="<?php echo $deal ? esc_attr($deal->client_plate) : ''; ?>" 
                           style="text-transform: uppercase;">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Марка</label>
                    <select name="car_brand" id="car-brand">
                        <option value="">-- Выберите марку --</option>
                        <?php if (!empty($brands)) : ?>
                            <?php foreach ($brands as $brand) : ?>
                                <option value="<?php echo esc_attr($brand); ?>" 
                                        <?php echo ($deal && $deal->car_brand === $brand) ? 'selected' : ''; ?>>
                                    <?php echo esc_html($brand); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <option value="__custom__">➕ Ввести марку вручную</option>
                    </select>
                    <input type="text" id="custom-brand-input" placeholder="Введите марку" 
                           style="display:none; margin-top:8px;">
                </div>
                <div class="form-group">
                    <label>Модель</label>
                    <select name="car_model" id="car-model">
                        <option value="">-- Сначала выберите марку --</option>
                    </select>
                    <input type="text" id="custom-model-input" placeholder="Введите модель" 
                           style="display:none; margin-top:8px;">
                </div>
                <div class="form-group">
                    <label>Год выпуска</label>
                    <input type="number" name="car_year" id="car-year" 
                           value="<?php echo $deal ? intval($deal->car_year) : ''; ?>" 
                           min="1990" max="2030">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Код АКПП</label>
                    <select name="transmission_code" id="transmission-code">
                        <option value="">-- Выберите код АКПП --</option>
                        <?php if (!empty($transmissions)) : ?>
                            <?php foreach ($transmissions as $trans) : ?>
                                <option value="<?php echo esc_attr($trans->code); ?>" 
                                        <?php echo ($deal && $deal->transmission_code === $trans->code) ? 'selected' : ''; ?>>
                                    <?php echo esc_html($trans->code); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <option value="__custom__">➕ Ввести код вручную</option>
                    </select>
                    <input type="text" id="custom-transmission-input" placeholder="Введите код АКПП" 
                           style="display:none; margin-top:8px;">
                </div>
                <div class="form-group">
                    <label>Сотрудник</label>
                    <select name="employee_id" id="employee-id">
                        <option value="">-- Выберите сотрудника --</option>
                        <?php if (!empty($employees)) : ?>
                            <?php foreach ($employees as $emp) : ?>
                                <option value="<?php echo intval($emp->id); ?>" 
                                        data-percent="<?php echo floatval($emp->salary_percent); ?>"
                                        <?php echo ($deal && $deal->employee_id == $emp->id) ? 'selected' : ''; ?>>
                                    <?php echo esc_html($emp->name . ' (' . $emp->salary_percent . '%)'); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Проблема / Симптомы</label>
                <textarea name="problem_description" rows="3"><?php echo $deal ? esc_textarea($deal->problem_description) : ''; ?></textarea>
            </div>
        </div>

        <!-- Финансы -->
        <div class="akpp-form-section">
            <h3>💰 Финансы</h3>
            <div class="form-row">
                <div class="form-group">
                    <label>Себестоимость запчастей (₽)</label>
                    <input type="number" name="parts_cost" id="parts-cost" step="0.01" 
                           value="<?php echo $deal ? floatval($deal->parts_cost) : 0; ?>">
                </div>
                <div class="form-group">
                    <label>Выплата сотруднику (₽)</label>
                    <input type="number" name="employee_payment" id="employee-payment" step="0.01" 
                           value="<?php echo $deal ? floatval($deal->employee_payment) : 0; ?>">
                </div>
                <div class="form-group">
                    <label>Итоговая цена (₽)</label>
                    <input type="number" name="deal_price" id="deal-price" step="0.01" 
                           value="<?php echo $deal ? floatval($deal->deal_price) : 0; ?>">
                </div>
            </div>
        </div>

        <!-- Статус и даты -->
        <div class="akpp-form-section">
            <h3>📅 Статус и даты</h3>
            <div class="form-row">
                <div class="form-group">
                    <label>Статус</label>
                    <select name="status">
                        <?php $current_status = $deal ? $deal->status : 'new'; ?>
                        <option value="new" <?php echo $current_status === 'new' ? 'selected' : ''; ?>>🆕 Новая</option>
                        <option value="diagnostic" <?php echo $current_status === 'diagnostic' ? 'selected' : ''; ?>>🔍 Диагностика</option>
                        <option value="negotiation" <?php echo $current_status === 'negotiation' ? 'selected' : ''; ?>>💬 Согласование</option>
                        <option value="in_work" <?php echo $current_status === 'in_work' ? 'selected' : ''; ?>>🔧 В работе</option>
                        <option value="completed" <?php echo $current_status === 'completed' ? 'selected' : ''; ?>>✅ Завершено</option>
                        <option value="rejected" <?php echo $current_status === 'rejected' ? 'selected' : ''; ?>>❌ Отказ</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Дата приема</label>
                    <input type="datetime-local" name="received_date" 
                           value="<?php echo $deal && $deal->received_date ? esc_attr(date('Y-m-d\TH:i', strtotime($deal->received_date))) : ''; ?>">
                </div>
                <div class="form-group">
                    <label>Дата выдачи</label>
                    <input type="datetime-local" name="completed_date" 
                           value="<?php echo $deal && $deal->completed_date ? esc_attr(date('Y-m-d\TH:i', strtotime($deal->completed_date))) : ''; ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Заметки</label>
                <textarea name="notes" rows="2"><?php echo $deal ? esc_textarea($deal->notes) : ''; ?></textarea>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="button button-primary button-hero">💾 Сохранить сделку</button>
            <a href="<?php echo admin_url('admin.php?page=akpp-deals'); ?>" class="button">Отмена</a>
            <div id="form-response" style="margin-left: 20px; font-weight: bold;"></div>
        </div>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    let isManualBrand = false;
    let isManualModel = false;
    let isManualTransmission = false;

    // Загрузка моделей при выборе марки
    function loadModels(brand) {
        if (!brand || brand === '__custom__') {
            $('#car-model').html('<option value="">-- Сначала выберите марку --</option>');
            return;
        }

        $('#car-model').html('<option value="">Загрузка...</option>');
        $('#custom-model-input').hide();
        isManualModel = false;

        $.post(akppCrm.ajaxUrl, {
            action: 'akpp_get_models_by_brand',
            nonce: akppCrm.nonce,
            brand: brand
        }, function(response) {
            if (response.success && response.data && response.data.length > 0) {
                let opts = '<option value="">-- Выберите модель --</option>';
                response.data.forEach(function(model) {
                    opts += '<option value="' + model + '">' + model + '</option>';
                });
                opts += '<option value="__custom__">➕ Ввести модель вручную</option>';
                $('#car-model').html(opts);
            } else {
                // Если моделей нет - показываем поле ввода
                $('#car-model').html('<option value="">-- Модель не найдена --</option>');
                $('#custom-model-input').show();
                isManualModel = true;
            }
        }).fail(function() {
            $('#car-model').html('<option value="">-- Ошибка загрузки --</option>');
            $('#custom-model-input').show();
            isManualModel = true;
        });
    }

    // Переключение на ручной ввод марки
    $('#car-brand').on('change', function() {
        if ($(this).val() === '__custom__') {
            isManualBrand = true;
            $('#custom-brand-input').show().focus();
            $('#car-model').html('<option value="">-- Введите марку вручную --</option>');
        } else {
            isManualBrand = false;
            $('#custom-brand-input').hide();
            loadModels($(this).val());
        }
    });

    // Переключение на ручной ввод модели
    $('#car-model').on('change', function() {
        if ($(this).val() === '__custom__') {
            isManualModel = true;
            $('#custom-model-input').show().focus();
        } else {
            isManualModel = false;
            $('#custom-model-input').hide();
        }
    });

    // Переключение на ручной ввод кода АКПП
    $('#transmission-code').on('change', function() {
        if ($(this).val() === '__custom__') {
            isManualTransmission = true;
            $('#custom-transmission-input').show().focus();
        } else {
            isManualTransmission = false;
            $('#custom-transmission-input').hide();
        }
    });

    // Авто-расчет выплаты сотруднику
    $('#employee-id, #deal-price').on('change input', function() {
        const percent = parseFloat($('#employee-id option:selected').data('percent')) || 0;
        const price = parseFloat($('#deal-price').val()) || 0;
        if (percent > 0 && price > 0) {
            $('#employee-payment').val((price * percent / 100).toFixed(2));
        }
    });

    // VIN декодер
    $('#decode-vin-btn').on('click', function() {
        const vin = $('#client-vin').val().trim();
        if (vin.length !== 17) {
            alert('VIN должен быть 17 символов');
            return;
        }

        $(this).prop('disabled', true).text('⏳ Проверяю...');

        $.post(akppCrm.ajaxUrl, {
            action: 'akpp_decode_vin',
            nonce: akppCrm.nonce,
            vin: vin
        }, function(response) {
            $('#decode-vin-btn').prop('disabled', false).text('🔍 Расшифровать');
            const div = $('#vin-decode-result');
            
            if (response.success) {
                const d = response.data;
                div.show().html(
                    '<div style="padding: 15px; background: rgba(16, 185, 129, 0.1); border: 1px solid #10b981; border-radius: 8px;">' +
                    '<strong style="color: #10b981;">✅ VIN расшифрован:</strong><br>' +
                    '<strong>' + (d.make || '') + ' ' + (d.model || '') + '</strong> (' + (d.year || '') + ')<br>' +
                    'ДВС: ' + (d.engine || 'н/д') + ' | Привод: ' + (d.drive_type || 'н/д') +
                    '</div>'
                );
                
                if (d.year) $('#car-year').val(d.year);
                if (d.make) {
                    $('#car-brand').val(d.make).trigger('change');
                    setTimeout(function() {
                        if (d.model) $('#car-model').val(d.model);
                    }, 500);
                }
            } else {
                div.show().html(
                    '<div style="padding: 15px; background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; border-radius: 8px;">' +
                    '<strong style="color: #ef4444;">❌ Не удалось распознать VIN</strong><br>' +
                    (response.data.message || 'Проверьте правильность ввода') +
                    '</div>'
                );
            }
        }).fail(function() {
            $('#decode-vin-btn').prop('disabled', false).text('🔍 Расшифровать');
            alert('Ошибка соединения с сервером');
        });
    });

    // Декодер номера кузова
    $('#decode-body-btn').on('click', function() {
        const body = $('#client-body').val().trim();
        if (!body) {
            alert('Введите номер кузова');
            return;
        }

        $(this).prop('disabled', true).text('⏳');

        $.post(akppCrm.ajaxUrl, {
            action: 'akpp_decode_body_number',
            nonce: akppCrm.nonce,
            body_number: body
        }, function(response) {
            $('#decode-body-btn').prop('disabled', false).text('🔍');
            const div = $('#vin-decode-result');
            
            if (response.success) {
                const d = response.data;
                div.show().html(
                    '<div style="padding: 15px; background: rgba(16, 185, 129, 0.1); border: 1px solid #10b981; border-radius: 8px;">' +
                    '<strong style="color: #10b981;">✅ Кузов распознан:</strong><br>' +
                    '<strong>' + (d.brand || '') + ' ' + (d.model || '') + '</strong> (Код: ' + (d.model_code || '') + ')' +
                    '</div>'
                );
                
                if (d.brand) {
                    $('#car-brand').val(d.brand).trigger('change');
                    setTimeout(function() {
                        if (d.model) $('#car-model').val(d.model);
                    }, 500);
                }
            } else {
                div.show().html(
                    '<div style="padding: 15px; background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; border-radius: 8px;">' +
                    '<strong style="color: #ef4444;">❌ Не удалось распознать номер кузова</strong>' +
                    '</div>'
                );
            }
        }).fail(function() {
            $('#decode-body-btn').prop('disabled', false).text('🔍');
            alert('Ошибка соединения');
        });
    });

    // Сохранение сделки
    $('#akpp-deal-form').on('submit', function(e) {
        e.preventDefault();
        
        let formData = $(this).serializeArray();
        
        // Обработка ручного ввода
        if (isManualBrand) {
            const customBrand = $('#custom-brand-input').val().trim();
            if (!customBrand) { alert('Введите марку автомобиля!'); return; }
            formData = formData.filter(f => f.name !== 'car_brand');
            formData.push({name: 'car_brand', value: customBrand});
        }
        
        if (isManualModel) {
            const customModel = $('#custom-model-input').val().trim();
            if (!customModel) { alert('Введите модель автомобиля!'); return; }
            formData = formData.filter(f => f.name !== 'car_model');
            formData.push({name: 'car_model', value: customModel});
        }
        
        if (isManualTransmission) {
            const customTrans = $('#custom-transmission-input').val().trim();
            formData = formData.filter(f => f.name !== 'transmission_code');
            formData.push({name: 'transmission_code', value: customTrans});
        }

        // Форматирование дат
        const receivedDate = $('input[name="received_date"]').val();
        const completedDate = $('input[name="completed_date"]').val();
        
        if (receivedDate) {
            const d = new Date(receivedDate);
            const formatted = d.getFullYear() + '-' + 
                String(d.getMonth() + 1).padStart(2, '0') + '-' + 
                String(d.getDate()).padStart(2, '0') + ' ' + 
                String(d.getHours()).padStart(2, '0') + ':' + 
                String(d.getMinutes()).padStart(2, '0') + ':00';
            formData = formData.filter(f => f.name !== 'received_date');
            formData.push({name: 'received_date', value: formatted});
        }
        
        if (completedDate) {
            const d = new Date(completedDate);
            const formatted = d.getFullYear() + '-' + 
                String(d.getMonth() + 1).padStart(2, '0') + '-' + 
                String(d.getDate()).padStart(2, '0') + ' ' + 
                String(d.getHours()).padStart(2, '0') + ':' + 
                String(d.getMinutes()).padStart(2, '0') + ':00';
            formData = formData.filter(f => f.name !== 'completed_date');
            formData.push({name: 'completed_date', value: formatted});
        }

        formData.push({name: 'action', value: 'akpp_save_deal'});
        formData.push({name: 'nonce', value: akppCrm.nonce});

        const submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true).text('⏳ Сохранение...');

        $.post(akppCrm.ajaxUrl, formData, function(response) {
            if (response.success) {
                $('#form-response').css('color', '#10b981').text('✅ ' + (response.data.message || 'Сделка сохранена'));
                setTimeout(function() { 
                    window.location.href = '<?php echo admin_url("admin.php?page=akpp-deals"); ?>'; 
                }, 1500);
            } else {
                $('#form-response').css('color', '#ef4444').text('❌ ' + (response.data.message || 'Ошибка сохранения'));
                submitBtn.prop('disabled', false).text('💾 Сохранить сделку');
                console.error('Ошибка сохранения:', response.data);
            }
        }).fail(function(xhr, status, error) {
            $('#form-response').css('color', '#ef4444').text('❌ Ошибка соединения');
            submitBtn.prop('disabled', false).text('💾 Сохранить сделку');
            console.error('AJAX Error:', status, error);
        });
    });
});
</script>