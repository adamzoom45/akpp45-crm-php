<?php
if (!defined('ABSPATH')) exit;
?>

<div class="wrap akpp-crm-dashboard">
    <h1>🔌 Интеграции</h1>
    
    <div class="akpp-form-section">
        <h3>💬 Telegram Bot</h3>
        <p style="color: #9ca3af; margin-bottom: 15px;">
            Получайте уведомления о новых лидах и сделках прямо в Telegram.
            <a href="https://t.me/BotFather" target="_blank" style="color: #00ff88;">Создать бота →</a>
        </p>
        
        <form method="post" action="options.php">
            <?php settings_fields('akpp_integrations'); ?>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Bot Token</label>
                    <input type="text" name="akpp_telegram_bot_token" 
                           value="<?php echo esc_attr($telegram_token); ?>" 
                           placeholder="123456:ABC-DEF1234ghIkl-zyx57W2v1u129ew11"
                           style="width: 100%;">
                </div>
                <div class="form-group">
                    <label>Chat ID для уведомлений</label>
                    <input type="text" name="akpp_telegram_notify_chat" 
                           value="<?php echo esc_attr($telegram_chat); ?>" 
                           placeholder="-1001234567890">
                    <small style="color: #6b7280; display: block; margin-top: 5px;">
                        Напишите боту /start, затем узнайте ID через @userinfobot
                    </small>
                </div>
            </div>
            
            <div style="margin-top: 15px;">
                <button type="button" id="test-telegram" class="button">📤 Тестовое сообщение</button>
                <span id="telegram-test-result" style="margin-left: 15px; font-weight: bold;"></span>
            </div>
        </form>
    </div>
    
    <div class="akpp-form-section">
        <h3>🛒 Avito API</h3>
        <p style="color: #9ca3af; margin-bottom: 15px;">
            Автоматический импорт заявок с Avito.
            <a href="https://developers.avito.ru/" target="_blank" style="color: #00ff88;">Документация →</a>
        </p>
        
        <form method="post" action="options.php">
            <?php settings_fields('akpp_integrations'); ?>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Client ID</label>
                    <input type="text" name="akpp_avito_client_id" 
                           value="<?php echo esc_attr($avito_client_id); ?>" 
                           style="width: 100%;">
                </div>
                <div class="form-group">
                    <label>Client Secret</label>
                    <input type="password" name="akpp_avito_client_secret" 
                           value="<?php echo esc_attr($avito_secret); ?>" 
                           style="width: 100%;">
                </div>
            </div>
            
            <div style="margin-top: 15px;">
                <button type="button" id="avito-sync" class="button"> Синхронизировать сейчас</button>
                <span id="avito-result" style="margin-left: 15px; font-weight: bold;"></span>
            </div>
        </form>
    </div>
    
    <div class="akpp-form-section">
        <h3>️ 2GIS API</h3>
        <p style="color: #9ca3af; margin-bottom: 15px;">
            Импорт отзывов и заявок с 2GIS.
            <a href="https://dev.2gis.ru/" target="_blank" style="color: #00ff88;">Получить API ключ →</a>
        </p>
        
        <form method="post" action="options.php">
            <?php settings_fields('akpp_integrations'); ?>
            
            <div class="form-row">
                <div class="form-group">
                    <label>API Key</label>
                    <input type="text" name="akpp_2gis_api_key" 
                           value="<?php echo esc_attr($twogis_api); ?>" 
                           style="width: 100%;">
                </div>
                <div class="form-group">
                    <label>Firm ID</label>
                    <input type="text" name="akpp_2gis_firm_id" 
                           value="<?php echo esc_attr($twogis_firm); ?>" 
                           placeholder="70000001036620434">
                    <small style="color: #6b7280; display: block; margin-top: 5px;">
                        ID вашей компании в 2GIS (можно найти в URL карточки компании)
                    </small>
                </div>
            </div>
            
            <div style="margin-top: 15px;">
                <button type="button" id="2gis-sync" class="button">🔄 Загрузить отзывы</button>
                <span id="2gis-result" style="margin-left: 15px; font-weight: bold;"></span>
            </div>
        </form>
    </div>
    
    <div class="akpp-form-section">
        <h3>📊 Статистика интеграций</h3>
        <div class="akpp-stats-grid" style="grid-template-columns: repeat(3, 1fr);">
            <div class="akpp-stat-card">
                <div class="stat-content">
                    <h3>Telegram</h3>
                    <p class="stat-number" style="font-size: 24px;">
                        <?php echo !empty($telegram_token) ? '✅ Подключен' : '❌ Не настроен'; ?>
                    </p>
                </div>
            </div>
            <div class="akpp-stat-card">
                <div class="stat-content">
                    <h3>Avito</h3>
                    <p class="stat-number" style="font-size: 24px;">
                        <?php echo !empty($avito_client_id) ? '✅ Подключен' : '❌ Не настроен'; ?>
                    </p>
                </div>
            </div>
            <div class="akpp-stat-card">
                <div class="stat-content">
                    <h3>2GIS</h3>
                    <p class="stat-number" style="font-size: 24px;">
                        <?php echo !empty($twogis_api) ? '✅ Подключен' : '❌ Не настроен'; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <?php submit_button('💾 Сохранить настройки'); ?>
</div>

<script>
jQuery(document).ready(function($) {
    // Тест Telegram
    $('#test-telegram').on('click', function() {
        const token = $('input[name="akpp_telegram_bot_token"]').val();
        const chat = $('input[name="akpp_telegram_notify_chat"]').val();
        
        if (!token || !chat) {
            $('#telegram-test-result').css('color', '#ef4444').text('❌ Заполните Token и Chat ID');
            return;
        }
        
        $(this).prop('disabled', true).text(' Отправка...');
        
        $.post(akppCrm.ajaxUrl, {
            action: 'akpp_test_telegram',
            nonce: akppCrm.nonce,
            token: token,
            chat: chat
        }, function(response) {
            $('#test-telegram').prop('disabled', false).text('📤 Тестовое сообщение');
            
            if (response.success) {
                $('#telegram-test-result').css('color', '#10b981').text('✅ ' + response.data.message);
            } else {
                $('#telegram-test-result').css('color', '#ef4444').text('❌ ' + response.data.message);
            }
        });
    });
    
    // Синхронизация Avito
    $('#avito-sync').on('click', function() {
        $(this).prop('disabled', true).text('⏳ Синхронизация...');
        
        $.post(akppCrm.ajaxUrl, {
            action: 'akpp_avito_sync',
            nonce: akppCrm.nonce
        }, function(response) {
            $('#avito-sync').prop('disabled', false).text('🔄 Синхронизировать сейчас');
            
            if (response.success) {
                $('#avito-result').css('color', '#10b981').text('✅ ' + response.data.message);
            } else {
                $('#avito-result').css('color', '#ef4444').text('❌ ' + response.data.message);
            }
        });
    });
    
    // Синхронизация 2GIS
    $('#2gis-sync').on('click', function() {
        $(this).prop('disabled', true).text(' Загрузка...');
        
        $.post(akppCrm.ajaxUrl, {
            action: 'akpp_2gis_sync',
            nonce: akppCrm.nonce
        }, function(response) {
            $('#2gis-sync').prop('disabled', false).text('🔄 Загрузить отзывы');
            
            if (response.success) {
                $('#2gis-result').css('color', '#10b981').text('✅ ' + response.data.message);
            } else {
                $('#2gis-result').css('color', '#ef4444').text('❌ ' + response.data.message);
            }
        });
    });
});
</script>