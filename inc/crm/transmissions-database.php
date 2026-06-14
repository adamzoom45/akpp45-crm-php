<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap akpp-crm-dashboard">
    <h1>⚙️ Каталог АКПП + ATPShop.ru</h1>
    <div class="akpp-crm-section">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Код АКПП</th>
                    <th>Название</th>
                    <th>Производитель</th>
                    <th>Тип</th>
                    <th>Ступеней</th>
                    <th>Цена ремонта</th>
                    <th>Каталоги</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transmissions as $t) : ?>
                <tr>
                    <td><strong style="color:#00ff88; font-size:16px;"><?php echo esc_html($t->code); ?></strong></td>
                    <td><?php echo esc_html($t->name); ?></td>
                    <td><strong><?php echo esc_html($t->manufacturer); ?></strong></td>
                    <td><?php echo esc_html($t->type); ?></td>
                    <td><?php echo $t->gears > 0 ? $t->gears . '-ст' : 'CVT'; ?></td>
                    <td><strong style="color:#00ff88;"><?php echo esc_html($t->repair_price); ?> ₽</strong></td>
                    <td>
                        <a href="https://www.atpshop.ru/search/?q=<?php echo urlencode($t->code); ?>" target="_blank" class="button button-small" style="background:#ff6b00; color:#fff; border:none; margin:2px;">🔍 ATPShop</a>
                        <?php if (!empty($t->amayama_code)) : ?>
                        <a href="https://www.amayama.com/ru/search?code=<?php echo urlencode($t->amayama_code); ?>" target="_blank" class="button button-small" style="background:#00ff88; color:#000; border:none; margin:2px;">🔍 Amayama</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>