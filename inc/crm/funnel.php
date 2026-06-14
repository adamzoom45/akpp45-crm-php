<?php if (!defined('ABSPATH')) exit; ?>

<div class="wrap akpp-crm-dashboard">
    <h1>📊 Воронка продаж</h1>
    
    <div class="akpp-stats-grid" id="funnel-stats">
        <div class="akpp-stat-card">
            <div class="stat-content"><h3>Всего лидов</h3><p class="stat-number" id="stat-total">0</p></div>
        </div>
        <div class="akpp-stat-card">
            <div class="stat-content"><h3>Конверсия</h3><p class="stat-number" id="stat-conversion">0%</p></div>
        </div>
        <div class="akpp-stat-card">
            <div class="stat-content"><h3> Новые</h3><p class="stat-number" id="stat-new">0</p></div>
        </div>
        <div class="akpp-stat-card">
            <div class="stat-content"><h3>📞 На связи</h3><p class="stat-number" id="stat-contacted">0</p></div>
        </div>
        <div class="akpp-stat-card">
            <div class="stat-content"><h3>💡 Интерес</h3><p class="stat-number" id="stat-interested">0</p></div>
        </div>
        <div class="akpp-stat-card">
            <div class="stat-content"><h3>💬 Согласование</h3><p class="stat-number" id="stat-negotiation">0</p></div>
        </div>
        <div class="akpp-stat-card">
            <div class="stat-content"><h3>✅ В сделку</h3><p class="stat-number" id="stat-converted">0</p></div>
        </div>
        <div class="akpp-stat-card">
            <div class="stat-content"><h3>❌ Отказы</h3><p class="stat-number" id="stat-rejected">0</p></div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $.post(akppCrm.ajaxUrl, {action:'akpp_get_lead_funnel_stats', nonce:akppCrm.nonce}, function(r) {
        if (r.success) {
            $('#stat-total').text(r.data.total);
            $('#stat-conversion').text(r.data.conversion_rate + '%');
            $('#stat-new').text(r.data.new);
            $('#stat-contacted').text(r.data.contacted);
            $('#stat-interested').text(r.data.interested);
            $('#stat-negotiation').text(r.data.negotiation);
            $('#stat-converted').text(r.data.converted);
            $('#stat-rejected').text(r.data.rejected);
        }
    });
});
</script>