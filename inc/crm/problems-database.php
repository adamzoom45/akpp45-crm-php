<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap akpp-crm-dashboard">
    <h1>⚠️ База болячек АКПП</h1>
    
    <div class="akpp-crm-section">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>АКПП</th>
                    <th>Марка</th>
                    <th>Модель</th>
                    <th>Проблема</th>
                    <th>Описание</th>
                    <th>Решение</th>
                    <th>Серьезность</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($problems as $p) : ?>
                <tr>
                    <td><strong style="color:#00ff88;"><?php echo esc_html($p->transmission_code); ?></strong></td>
                    <td><?php echo esc_html($p->brand); ?></td>
                    <td><?php echo esc_html($p->model); ?></td>
                    <td><strong><?php echo esc_html($p->title); ?></strong></td>
                    <td><?php echo esc_html($p->description); ?></td>
                    <td><?php echo esc_html($p->solution); ?></td>
                    <td>
                        <?php if ($p->severity === 'high') : ?>
                            <span style="color:#ff6b6b; font-weight:bold;">🔴 Высокая</span>
                        <?php elseif ($p->severity === 'medium') : ?>
                            <span style="color:#f59e0b; font-weight:bold;">🟡 Средняя</span>
                        <?php else : ?>
                            <span style="color:#10b981; font-weight:bold;">🟢 Низкая</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>