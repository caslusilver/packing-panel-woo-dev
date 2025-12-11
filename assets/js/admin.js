/**
 * JavaScript para a página de administração do PackPanel
 */
(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Inicializa color pickers
        if ($.fn.wpColorPicker) {
            $('.ppwoo-color-picker').wpColorPicker();
        }
        
        // Handler para teste de webhook
        $('#ppwoo-test-webhook').on('click', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $result = $('#ppwoo-webhook-test-result');
            
            $button.prop('disabled', true).text('Testando...');
            $result.hide().empty();
            
            $.ajax({
                url: ppwooAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'ppwoo_test_webhook',
                    nonce: ppwooAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $result
                            .html('<div class="notice notice-success"><p><strong>' + 
                                  response.data.message + '</strong><br>' +
                                  'Status: ' + response.data.status + '<br>' +
                                  'Tempo: ' + response.data.elapsed + '<br>' +
                                  'Resposta: ' + response.data.body_preview + '</p></div>')
                            .show();
                    } else {
                        $result
                            .html('<div class="notice notice-error"><p><strong>' + 
                                  response.data.message + '</strong><br>' +
                                  (response.data.elapsed ? 'Tempo: ' + response.data.elapsed + '<br>' : '') +
                                  (response.data.body_preview ? 'Resposta: ' + response.data.body_preview : '') + '</p></div>')
                            .show();
                    }
                },
                error: function() {
                    $result
                        .html('<div class="notice notice-error"><p><strong>Erro ao testar webhook.</strong></p></div>')
                        .show();
                },
                complete: function() {
                    $button.prop('disabled', false).text('Testar Webhook');
                }
            });
        });
    });
})(jQuery);
