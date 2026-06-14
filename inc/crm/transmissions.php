<?php if (!defined('ABSPATH')) exit; ?>

<div class="wrap akpp-crm-dashboard">
    <h1 class="wp-heading-inline">⚙️ База АКПП</h1>
    <button type="button" id="add-transmission-btn" class="page-title-action">➕ Добавить АКПП</button>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Код</th>
                <th>Название</th>
                <th>Производитель</th>
                <th>Ступеней</th>
                <th>Цена ремонта</th>
                <th>Amayama</th>
                <th>TransAKPP</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($transmissions)) : ?>
                <?php foreach ($transmissions as $t) : ?>
                <tr>
                    <td><code><?php echo esc_html($t->code); ?></code></td>
                    <td><?php echo esc_html($t->name); ?></td>
                    <td><?php echo esc_html($t->manufacturer); ?></td>
                    <td><?php echo $t->gears; ?></td>
                    <td><strong><?php echo akpp_format_money($t->repair_price); ?></strong></td>
                    <td>
                        <?php if ($t->amayama_code) : ?>
                            <a href="https://www.amayama.com/ru/search?code=<?php echo urlencode($t->amayama_code); ?>" target="_blank" class="button button-small">🔍</a>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($t->transakpp_url) : ?>
                            <a href="<?php echo esc_url($t->transakpp_url); ?>" target="_blank" class="button button-small">📚</a>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button class="button button-small edit-transmission" 
                            data-id="<?php echo $t->id; ?>"
                            data-code="<?php echo esc_attr($t->code); ?>"
                            data-name="<?php echo esc_attr($t->name); ?>"
                            data-manufacturer="<?php echo esc_attr($t->manufacturer); ?>"
                            data-gears="<?php echo $t->gears; ?>"
                            data-price="<?php echo $t->repair_price; ?>"
                            data-amayama="<?php echo esc_attr($t->amayama_code); ?>"
                            data-transakpp="<?php echo esc_attr($t->transakpp_url); ?>">✏️</button>
                        <button class="button button-small delete-transmission" data-id="<?php echo $t->id; ?>">🗑️</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr><td colspan="8">АКПП нет</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <!-- Modal -->
    <div id="transmission-modal" class="akpp-modal" style="display: none;">
        <div class="modal-content">
            <h3 id="transmission-modal-title">Добавить АКПП</h3>
            <form id="transmission-form">
                <input type="hidden" name="id" id="trans-id">
                <div class="form-row">
                    <div class="form-group"><label>Код *</label><input type="text" name="code" id="trans-code" required></div>
                    <div class="form-group"><label>Название *</label><input type="text" name="name" id="trans-name" required></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Производитель</label><input type="text" name="manufacturer" id="trans-manufacturer"></div>
                    <div class="form-group"><label>Ступеней</label><input type="number" name="gears" id="trans-gears"></div>
                    <div class="form-group"><label>Цена ремонта</label><input type="number" step="0.01" name="repair_price" id="trans-price"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Amayama OEM</label><input type="text" name="amayama_code" id="trans-amayama"></div>
                    <div class="form-group"><label>TransAKPP URL</label><input type="url" name="transakpp_url" id="trans-transakpp"></div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="button button-primary">💾 Сохранить</button>
                    <button type="button" id="close-transmission-modal" class="button">Отмена</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    const modal = $('#transmission-modal');
    const form = $('#transmission-form');
    
    $('#add-transmission-btn').on('click', function() {
        $('#transmission-modal-title').text('Добавить АКПП');
        form[0].reset();
        $('#trans-id').val('');
        modal.show();
    });
    
    $('.edit-transmission').on('click', function() {
        $('#transmission-modal-title').text('Редактировать АКПП');
        $('#trans-id').val($(this).data('id'));
        $('#trans-code').val($(this).data('code'));
        $('#trans-name').val($(this).data('name'));
        $('#trans-manufacturer').val($(this).data('manufacturer'));
        $('#trans-gears').val($(this).data('gears'));
        $('#trans-price').val($(this).data('price'));
        $('#trans-amayama').val($(this).data('amayama'));
        $('#trans-transakpp').val($(this).data('transakpp'));
        modal.show();
    });
    
    $('#close-transmission-modal, .akpp-modal').on('click', function(e) {
        if (e.target === this) modal.hide();
    });
    
    form.on('submit', function(e) {
        e.preventDefault();
        const action = $('#trans-id').val() ? 'akpp_edit_transmission' : 'akpp_add_transmission';
        $.post(akppCrm.ajaxUrl, form.serialize() + '&action=' + action + '&nonce=' + akppCrm.nonce, function(res) {
            if (res.success) { alert(res.data.message); location.reload(); }
            else { alert(res.data.message); }
        });
    });
    
    $('.delete-transmission').on('click', function() {
        if (!confirm('Удалить запись?')) return;
        $.post(akppCrm.ajaxUrl, { action: 'akpp_delete_transmission', nonce: akppCrm.nonce, id: $(this).data('id') }, function(res) {
            if (res.success) location.reload();
            else alert(res.data.message);
        });
    });
});
</script>