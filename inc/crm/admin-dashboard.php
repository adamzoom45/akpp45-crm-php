<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap akpp-crm-dashboard">
    <h1>📊 Панель управления CRM</h1>
    
    <div class="akpp-crm-stats-grid">
        <div class="akpp-stat-card"><div class="stat-icon">📥</div><div class="stat-content"><h3>Всего заявок</h3><p class="stat-number"><?php echo $total_deals; ?></p></div></div>
        <div class="akpp-stat-card new"><div class="stat-icon">🆕</div><div class="stat-content"><h3>Новые</h3><p class="stat-number"><?php echo $new_deals; ?></p></div></div>
        <div class="akpp-stat-card in-work"><div class="stat-icon">🔧</div><div class="stat-content"><h3>В работе</h3><p class="stat-number"><?php echo $in_work; ?></p></div></div>
        <div class="akpp-stat-card completed"><div class="stat-icon">✅</div><div class="stat-content"><h3>Завершено</h3><p class="stat-number"><?php echo $completed; ?></p></div></div>
        <div class="akpp-stat-card rejected"><div class="stat-icon">❌</div><div class="stat-content"><h3>Отказы</h3><p class="stat-number"><?php echo $rejected; ?></p></div></div>
    </div>

    <div class="akpp-crm-section">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h2 style="margin:0;">🔥 Все сделки</h2>
            <a href="<?php echo admin_url('admin.php?page=akpp-crm-new-deal'); ?>" class="button button-primary">+ Новая сделка</a>
        </div>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Клиент</th>
                    <th>Телефон</th>
                    <th>VIN / Гос номер</th>
                    <th>Авто / АКПП</th>
                    <th>Статус</th>
                    <th>Сумма</th>
                    <th>Дата приема</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($deals)) : foreach ($deals as $deal) : ?>
                <tr>
                    <td><strong><?php echo esc_html($deal->client_name); ?></strong></td>
                    <td><a href="tel:<?php echo esc_attr($deal->client_phone); ?>"><?php echo esc_html($deal->client_phone); ?></a></td>
                    <td>
                        <strong style="color:#00ff88;"><?php echo esc_html(strtoupper($deal->client_plate)); ?></strong><br>
                        <small><?php echo esc_html($deal->client_vin); ?></small>
                    </td>
                    <td><?php echo esc_html($deal->car_brand . ' ' . $deal->car_model); ?><br><small style="color:#00ff88;"><?php echo esc_html($deal->transmission_code); ?></small></td>
                    <td>
                        <select class="lead-status-change" data-deal-id="<?php echo $deal->id; ?>">
                            <option value="new" <?php selected($deal->status, 'new'); ?>>🆕 Новая</option>
                            <option value="diagnostic" <?php selected($deal->status, 'diagnostic'); ?>>🔍 Диагностика</option>
                            <option value="negotiation" <?php selected($deal->status, 'negotiation'); ?>>💬 Согласование</option>
                            <option value="in_work" <?php selected($deal->status, 'in_work'); ?>>🔧 В работе</option>
                            <option value="completed" <?php selected($deal->status, 'completed'); ?>>✅ Завершено</option>
                            <option value="rejected" <?php selected($deal->status, 'rejected'); ?>>❌ Отказ</option>
                        </select>
                    </td>
                    <td><strong><?php echo esc_html($deal->deal_price); ?> ₽</strong></td>
                    <td><?php echo esc_html(date('d.m.Y H:i', strtotime($deal->received_date))); ?></td>
                    <td><a href="<?php echo admin_url('admin.php?page=akpp-crm-new-deal&id=' . $deal->id); ?>" class="button">Открыть</a></td>
                </tr>
                <?php endforeach; else : ?>
                <tr><td colspan="8">Сделок пока нет</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>