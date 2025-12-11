<?php
/**
 * Classe para renderizar e processar a aba Conexão
 */
if (!defined('ABSPATH')) exit;

class PPWOO_Admin_Connection_Tab {
    
    /**
     * Nome da option que armazena a URL do webhook
     */
    const OPTION_NAME = 'ppwoo_webhook_url';
    
    /**
     * Renderiza o formulário da aba Conexão
     */
    public static function render() {
        // Processa o formulário se foi submetido
        if (isset($_POST['ppwoo_save_webhook']) && check_admin_referer('ppwoo_save_webhook', 'ppwoo_webhook_nonce')) {
            self::save_webhook_url();
        }
        
        $webhook_url = get_option(self::OPTION_NAME, PPWOO_Config::get_webhook_url());
        
        ?>
        <form method="post" action="">
            <?php wp_nonce_field('ppwoo_save_webhook', 'ppwoo_webhook_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="webhook_url"><?php esc_html_e('URL do Webhook', 'painel-empacotamento'); ?></label>
                    </th>
                    <td>
                        <input type="url" 
                               id="webhook_url" 
                               name="ppwoo_webhook_url" 
                               value="<?php echo esc_url($webhook_url); ?>" 
                               class="regular-text" 
                               placeholder="https://exemplo.com/webhook" />
                        <p class="description"><?php esc_html_e('URL do webhook para receber eventos do painel', 'painel-empacotamento'); ?></p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(__('Salvar Webhook', 'painel-empacotamento')); ?>
        </form>
        
        <hr>
        
        <h2><?php esc_html_e('Testar Webhook', 'painel-empacotamento'); ?></h2>
        <p><?php esc_html_e('Clique no botão abaixo para testar a conexão com o webhook configurado.', 'painel-empacotamento'); ?></p>
        
        <button type="button" 
                id="ppwoo-test-webhook" 
                class="button button-secondary">
            <?php esc_html_e('Testar Webhook', 'painel-empacotamento'); ?>
        </button>
        
        <div id="ppwoo-webhook-test-result" style="margin-top: 15px; display: none;"></div>
        <?php
    }
    
    /**
     * Salva a URL do webhook
     */
    private static function save_webhook_url() {
        if (!isset($_POST['ppwoo_webhook_url'])) {
            return;
        }
        
        $url = esc_url_raw($_POST['ppwoo_webhook_url']);
        
        // Validação básica
        if (!empty($url) && !filter_var($url, FILTER_VALIDATE_URL)) {
            add_settings_error(
                'ppwoo_webhook',
                'ppwoo_webhook_invalid',
                __('URL do webhook inválida.', 'painel-empacotamento'),
                'error'
            );
            settings_errors('ppwoo_webhook');
            return;
        }
        
        $result = update_option(self::OPTION_NAME, $url);
        
        PPWOO_Debug::info('URL do webhook salva', ['url' => $url]);
        
        if ($result !== false) {
            add_settings_error(
                'ppwoo_webhook',
                'ppwoo_webhook_saved',
                __('URL do webhook salva com sucesso!', 'painel-empacotamento'),
                'success'
            );
        } else {
            add_settings_error(
                'ppwoo_webhook',
                'ppwoo_webhook_error',
                __('Erro ao salvar URL do webhook.', 'painel-empacotamento'),
                'error'
            );
        }
        
        settings_errors('ppwoo_webhook');
    }
    
    /**
     * Handler AJAX para testar o webhook
     */
    public static function ajax_test_webhook() {
        check_ajax_referer('ppwoo_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Sem permissão.', 'painel-empacotamento')]);
        }
        
        $webhook_url = get_option(self::OPTION_NAME, '');
        
        if (empty($webhook_url)) {
            wp_send_json_error(['message' => __('URL do webhook não configurada.', 'painel-empacotamento')]);
        }
        
        $start_time = microtime(true);
        
        PPWOO_Debug::info('Testando webhook', ['url' => $webhook_url]);
        
        $response = wp_remote_get($webhook_url, [
            'timeout' => 15,
            'sslverify' => apply_filters('packing_panel_webhook_sslverify', !PPWOO_Config::is_debug()),
        ]);
        
        $elapsed = (microtime(true) - $start_time) * 1000;
        
        if (is_wp_error($response)) {
            PPWOO_Debug::error('Erro ao testar webhook', ['error' => $response->get_error_message()]);
            
            wp_send_json_error([
                'message' => __('Erro na conexão: ', 'painel-empacotamento') . $response->get_error_message(),
                'elapsed' => number_format($elapsed, 2) . 'ms',
            ]);
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $body_preview = mb_substr($body, 0, 200);
        
        PPWOO_Debug::info('Resposta do webhook', [
            'status' => $status_code,
            'elapsed' => $elapsed . 'ms',
            'body_preview' => $body_preview,
        ]);
        
        if ($status_code >= 200 && $status_code < 300) {
            wp_send_json_success([
                'message' => __('Webhook respondendo corretamente!', 'painel-empacotamento'),
                'status' => $status_code,
                'elapsed' => number_format($elapsed, 2) . 'ms',
                'body_preview' => $body_preview,
            ]);
        } else {
            wp_send_json_error([
                'message' => sprintf(__('Webhook retornou status %d', 'painel-empacotamento'), $status_code),
                'status' => $status_code,
                'elapsed' => number_format($elapsed, 2) . 'ms',
                'body_preview' => $body_preview,
            ]);
        }
    }
}
