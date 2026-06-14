<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap akpp-crm-dashboard">
    <h1>🚗 База автомобилей</h1>
    <div class="akpp-crm-section">
        <table class="wp-list-table widefat fixed striped">
            <thead><tr><th>Марка</th><th>Модель</th><th>Годы</th><th>Двигатель</th><th>Привод</th><th>Код АКПП</th></tr></thead>
            <tbody>
                <?php foreach ($vehicles as $v) : ?>
                <tr>
                    <td><strong><?php echo esc_html($v->brand); ?></strong></td>
                    <td><?php echo esc_html($v->model); ?></td>
                    <td><?php echo esc_html($v->year_from . ' - ' . ($v->year_to ?: 'н.в.')); ?></td>
                    <td><?php echo esc_html($v->engine); ?></td>
                    <td><?php echo esc_html($v->drive_type); ?></td>
                    <td><code style="color:#00ff88;"><?php echo esc_html($v->transmission_code); ?></code></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>