<?php 
if (!defined('ABSPATH')) exit; 
global $wpdb;

$deal_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$deal = $deal_id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}akpp_deals WHERE id = %d", $deal_id)) : null;

$vehicles = $wpdb->get_results("SELECT DISTINCT brand, model FROM {$wpdb->prefix}akpp_vehicles ORDER BY brand, model");
$transmissions = $wpdb->get_results("SELECT code FROM {$wpdb->prefix}akpp_transmissions ORDER BY code");
?>
<div class="wrap akpp-crm-dashboard">
    <h1><?php echo $deal ? '✏️ Редактировать сделку #' . $deal->id : '➕ Новая сделка'; ?></h1>
    
    <form id="akpp-deal-form" class="akpp-crm-section">
        <input type="hidden" name="deal_id" value="<?php echo $deal_id; ?>">
        
        <h3>👤 Клиент</h3>
        <div class="form-row">
            <div class="form-group"><label>ФИО Клиента</label><input type="text" name="client_name" value="<?php echo esc_attr($deal->client_name ?? ''); ?>" required></div>
            <div class="form-group"><label>Телефон</label><input type="tel" name="client_phone" value="<?php echo esc_attr($deal->client_phone ?? ''); ?>" required></div>
            <div class="form-group"><label>Гос номер</label><input type="text" name="client_plate" value="<?php echo esc_attr($deal->client_plate ?? ''); ?>" placeholder="А123БВ45" style="text-transform: uppercase;"></div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>VIN номер</label>
                <input type="text" name="client_vin" id="client-vin" value="<?php echo esc_attr($deal->client_vin ?? ''); ?>" maxlength="17" placeholder="Введите VIN для автозаполнения">
                <button type="button" id="decode-vin-btn" class="button" style="margin-top:5px;">🔍 Расшифровать VIN</button>
            </div>
            <div class="form-group">
                <label>Номер кузова</label>
                <input type="text" name="client_body_number" id="client-body" value="<?php echo esc_attr($deal->client_body_number ?? ''); ?>" placeholder="Например: UZZ30-1234567">
                <button type="button" id="decode-body-btn" class="button" style="margin-top:5px;">🔍 Расшифровать кузов</button>
            </div>
        </div>
        
        <div id="vin-decode-result" style="display:none; background:rgba(0,255,136,0.1); border:1px solid #00ff88; padding:15px; border-radius:8px; margin-top:15px;"></div>

        <h3>🚗 Автомобиль и АКПП</h3>
        <div class="form-row">
            <div class="form-group">
                <label>Марка</label>
                <select name="car_brand" id="car-brand">
                    <option value="">-- Выберите марку --</option>
                    <?php foreach ($vehicles as $v) : ?>
                        <option value="<?php echo esc_attr($v->brand); ?>" <?php selected($deal->car_brand ?? '', $v->brand); ?>><?php echo esc_html($v->brand); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Модель</label>
                <select name="car_model" id="car-model">
                    <option value="">-- Сначала выберите марку --</option>
                </select>
            </div>
            <div class="form-group"><label>Год выпуска</label><input type="number" name="car_year" id="car-year" value="<?php echo esc_attr($deal->car_year ?? ''); ?>"></div>
            <div class="form-group">
                <label>Код АКПП</label>
                <select name="transmission_code" id="transmission-code">
                    <option value="">-- Выберите АКПП --</option>
                    <?php foreach ($transmissions as $t) : ?>
                        <option value="<?php echo esc_attr($t->code); ?>" <?php selected($deal->transmission_code ?? '', $t->code); ?>><?php echo esc_html($t->code); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-group"><label>Проблема / Симптомы</label><textarea name="problem_description" rows="3"><?php echo esc_textarea($deal->problem_description ?? ''); ?></textarea></div>
        
        <div id="problems-list" style="margin-top:15px;"></div>

        <h3>💰 Финансы и Воронка</h3>
        <div class="form-row">
            <div class="form-group"><label>Себестоимость запчастей (₽)</label><input type="number" name="parts_cost" value="<?php echo esc_attr($deal->parts_cost ?? 0); ?>"></div>
            <div class="form-group"><label>Выплата сотруднику (₽)</label><input type="number" name="employee_payment" value="<?php echo esc_attr($deal->employee_payment ?? 0); ?>"></div>
            <div class="form-group"><label>Итоговая цена (₽)</label><input type="number" name="deal_price" value="<?php echo esc_attr($deal->deal_price ?? 0); ?>"></div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Статус</label>
                <select name="status">
                    <option value="new" <?php selected($deal->status ?? 'new', 'new'); ?>>🆕 Новая</option>
                    <option value="diagnostic" <?php selected($deal->status ?? '', 'diagnostic'); ?>>🔍 Диагностика</option>
                    <option value="negotiation" <?php selected($deal->status ?? '', 'negotiation'); ?>>💬 Согласование</option>
                    <option value="in_work" <?php selected($deal->status ?? '', 'in_work'); ?>>🔧 В работе</option>
                    <option value="completed" <?php selected($deal->status ?? '', 'completed'); ?>>✅ Завершено</option>
                    <option value="rejected" <?php selected($deal->status ?? '', 'rejected'); ?>>❌ Отказ</option>
                </select>
            </div>
            <div class="form-group"><label>Дата приема</label><input type="datetime-local" name="received_date" value="<?php echo esc_attr($deal->received_date ?? ''); ?>"></div>
            <div class="form-group"><label>Дата выдачи</label><input type="datetime-local" name="completed_date" value="<?php echo esc_attr($deal->completed_date ?? ''); ?>"></div>
        </div>
        
        <div class="form-group"><label>Заметки</label><textarea name="notes" rows="2"><?php echo esc_textarea($deal->notes ?? ''); ?></textarea></div>

        <button type="submit" class="button button-primary button-hero">💾 Сохранить сделку</button>
        <div id="form-response" style="margin-top:15px; font-weight:bold;"></div>
    </form>
</div>

<script>
const vehicleModels = <?php 
    $models = [];
    foreach($vehicles as $v) { $models[$v->brand][] = $v->model; }
    echo json_encode($models); 
?>;

document.getElementById('car-brand').addEventListener('change', function() {
    const brand = this.value;
    const modelSelect = document.getElementById('car-model');
    modelSelect.innerHTML = '<option value="">-- Выберите модель --</option>';
    if (brand && vehicleModels[brand]) {
        vehicleModels[brand].forEach(model => {
            modelSelect.innerHTML += `<option value="${model}">${model}</option>`;
        });
    }
});
if(document.getElementById('car-brand').value) document.getElementById('car-brand').dispatchEvent(new Event('change'));

document.getElementById('decode-vin-btn').addEventListener('click', function() {
    const vin = document.getElementById('client-vin').value;
    if (vin.length !== 17) {
        alert('VIN должен содержать 17 символов');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'akpp_decode_vin');
    formData.append('vin', vin);
    formData.append('nonce', akppCrm.nonce);
    
    fetch(akppCrm.ajaxUrl, { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        const resultDiv = document.getElementById('vin-decode-result');
        if (data.success) {
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = `
                <h4>✅ VIN расшифрован:</h4>
                <p><strong>Марка:</strong> ${data.data.make}</p>
                <p><strong>Модель:</strong> ${data.data.model}</p>
                <p><strong>Год:</strong> ${data.data.year}</p>
                <p><strong>Двигатель:</strong> ${data.data.engine}</p>
                <p><strong>Привод:</strong> ${data.data.drive_type}</p>
                <p><strong>АКПП:</strong> ${data.data.transmission || 'Не определена'}</p>
            `;
            
            document.getElementById('car-year').value = data.data.year;
            if (data.data.transmission) {
                document.getElementById('transmission-code').value = data.data.transmission;
            }
            
            if (data.data.problems && data.data.problems.length > 0) {
                let problemsHtml = '<h4>⚠️ Типичные проблемы:</h4><ul>';
                data.data.problems.forEach(p => {
                    problemsHtml += `<li><strong>${p.title}</strong> - ${p.description}</li>`;
                });
                problemsHtml += '</ul>';
                document.getElementById('problems-list').innerHTML = problemsHtml;
            }
        } else {
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = '<p style="color:#ff6b6b;">❌ ' + data.data.message + '</p>';
        }
    });
});

document.getElementById('decode-body-btn').addEventListener('click', function() {
    const bodyNumber = document.getElementById('client-body').value;
    if (!bodyNumber) {
        alert('Введите номер кузова');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'akpp_decode_body_number');
    formData.append('body_number', bodyNumber);
    formData.append('nonce', akppCrm.nonce);
    
    fetch(akppCrm.ajaxUrl, { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        const resultDiv = document.getElementById('vin-decode-result');
        if (data.success) {
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = `
                <h4>✅ Кузов найден:</h4>
                <p><strong>Марка:</strong> ${data.data.brand}</p>
                <p><strong>Модель:</strong> ${data.data.model}</p>
                <p><strong>Годы:</strong> ${data.data.year_from} - ${data.data.year_to}</p>
                <p><strong>АКПП:</strong> ${data.data.transmission_code}</p>
                <p><strong>Двигатель:</strong> ${data.data.engine}</p>
            `;
        } else {
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = '<p style="color:#ff6b6b;">❌ ' + data.data.message + '</p>';
        }
    });
});

document.getElementById('akpp-deal-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('action', 'akpp_save_deal');
    formData.append('nonce', akppCrm.nonce);

    fetch(akppCrm.ajaxUrl, { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        const resp = document.getElementById('form-response');
        if (data.success) {
            resp.style.color = '#00ff88';
            resp.textContent = '✅ ' + data.data.message;
            if (!<?php echo $deal_id ? 'true' : 'false'; ?>) window.location.href = '<?php echo admin_url('admin.php?page=akpp-crm-new-deal&id='); ?>' + data.data.deal_id;
        } else {
            resp.style.color = '#ff6b6b';
            resp.textContent = '❌ Ошибка';
        }
    });
});
</script>