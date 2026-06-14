jQuery(document).ready(function($) {
    $(document).on('change', '.lead-status-change', function() {
        var dealId = $(this).data('deal-id');
        var newStatus = $(this).val();
        
        $.post(akppCrm.ajaxUrl, {
            action: 'akpp_update_deal_status',
            deal_id: dealId,
            status: newStatus,
            nonce: akppCrm.nonce
        }, function(response) {
            if (response.success) {
                location.reload();
            }
        });
    });
});