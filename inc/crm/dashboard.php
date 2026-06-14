<?php if (!defined('ABSPATH')) exit; ?>

<div class="wrap akpp-crm-dashboard">
    <h1 class="wp-heading-inline">📊 Панель управления CRM</h1>
    
    <div class="akpp-stats-grid">
        <div class="akpp-stat-card">
            <div class="stat-icon">📥</div>
            <div class="stat-content">
                <h3>Всего сделок</h3>
                <p class="stat-number"><?php echo intval($stats['total_deals']); ?></p>
            </div>
        </div>
        
        <div class="akpp-stat-card active">
            <div class="stat-icon">🔧</div>
            <div class="stat-content">
                <h3>В работе</h3>
                <p class="stat-number"><?php echo intval($stats['active_deals']); ?></p>
            </div>
        </div>
        
        <div class="akpp-stat-card completed">
            <div class="stat-icon">✅</div>
            <div class="stat-content">
                <h3>Завершено</h3>
                <p class="stat-number"><?php echo intval($stats['completed_deals']); ?></p>
            </div>
        </div>
        
        <div class="akpp-stat-card revenue">
            <div class="stat-icon">💰</div>
            <div class="stat-content">
                <h3>Выручка</h3>
                <p class="stat-number"><?php echo akpp_format_money($stats['total_revenue']); ?></p>
            </div>
        </div>
        
        <div class="akpp-stat-card expenses">
            <div class="stat-icon">💸</div>
            <div class="stat-content">
                <h3>Расходы</h3>
                <p class="stat-number"><?php echo akpp_format_money($stats['total_parts_cost'] + $stats['total_salary']); ?></p>
            </div>
        </div>
        
        <div class="akpp-stat-card balance <?php echo $stats['balance'] >= 0 ? 'positive' : 'negative'; ?>">
            <div class="stat-icon"><?php echo $stats['balance'] >= 0 ? '📈' : '📉'; ?></div>
            <div class="stat-content">
                <h3>Баланс</h3>
                <p class="stat-number"><?php echo akpp_format_money($stats['balance']); ?></p>
            </div>
        </div>
    </div>
    
    <div class="akpp-quick-actions">
        <h2>Быстрые действия</h2>
        <div class="action-buttons">
            <a href="<?php echo admin_url('admin.php?page=akpp-new-deal'); ?>" class="button button-primary button-hero"> Новая сделка</a>
            <a href="<?php echo admin_url('admin.php?page=akpp-deals'); ?>" class="button button-hero"> Все сделки</a>
            <a href="<?php echo admin_url('admin.php?page=akpp-employees'); ?>" class="button button-hero"> Сотрудники</a>
        </div>
    </div>
    
    <div class="akpp-recent-deals">
        <h2>Последние сделки</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Клиент</th>
                    <th>Телефон</th>
                    <th>Авто</th>
                    <th>АКПП</th>
                    <th>Статус</th>
                    <th>Сумма</th>
                    <th>Дата</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($recent_deals)) : ?>
                    <?php foreach ($recent_deals as $deal) : ?>
                    <tr>
                        <td>#<?php echo intval($deal->id); ?></td>
                        <td><strong><?php echo esc_html($deal->client_name); ?></strong></td>
                        <td><a href="tel:<?php echo esc_attr($deal->client_phone); ?>"><?php echo esc_html($deal->client_phone); ?></a></td>
                        <td><?php echo esc_html($deal->car_brand . ' ' . $deal->car_model); ?></td>
                        <td><code><?php echo esc_html($deal->transmission_code); ?></code></td>
                        <td>
                            <span class="status-badge" style="background: <?php echo akpp_get_status_color($deal->status); ?>20; color: <?php echo akpp_get_status_color($deal->status); ?>">
                                <?php echo akpp_get_status_label($deal->status); ?>
                            </span>
                        </td>
                        <td><strong><?php echo akpp_format_money($deal->deal_price); ?></strong></td>
                        <td><?php echo date('d.m.Y', strtotime($deal->created_at)); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="8">Сделок пока нет</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>