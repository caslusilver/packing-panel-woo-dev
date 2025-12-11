<?php
/**
 * Classe client para integração com webhooks externos
 * 
 * Faz requisições GET JSON para buscar pedidos externos
 */
if (!defined('ABSPATH')) exit;

class PPWOO_WebhookClient {
    
    /**
     * Obtém a URL do webhook configurada
     * 
     * @return string
     */
    private static function get_webhook_url() {
        $url = get_option('ppwoo_webhook_url', '');
        
        if (empty($url)) {
            $url = PPWOO_Config::get_webhook_url();
        }
        
        return apply_filters('ppwoo_webhook_client_url', $url);
    }
    
    /**
     * Faz requisição GET para o webhook e retorna JSON
     * 
     * @param array $params Parâmetros adicionais para a requisição
     * @return array|WP_Error Dados retornados ou erro
     */
    public static function fetch($params = array()) {
        $webhook_url = self::get_webhook_url();
        
        if (empty($webhook_url) || strpos($webhook_url, 'YOUR_') !== false) {
            PPWOO_Debug::warn('URL do webhook não configurada');
            return new WP_Error('no_webhook_url', __('URL do webhook não configurada.', 'painel-empacotamento'));
        }
        
        // Adiciona parâmetros à URL se necessário
        if (!empty($params)) {
            $webhook_url = add_query_arg($params, $webhook_url);
        }
        
        $start_time = microtime(true);
        
        PPWOO_Debug::info('Fazendo requisição GET para webhook', ['url' => $webhook_url]);
        
        $response = wp_remote_get($webhook_url, [
            'timeout' => 15,
            'sslverify' => apply_filters('packing_panel_webhook_sslverify', !PPWOO_Config::is_debug()),
        ]);
        
        $elapsed = microtime(true) - $start_time;
        PPWOO_Debug::timed('Webhook GET request', $start_time);
        
        if (is_wp_error($response)) {
            PPWOO_Debug::error('Erro na requisição GET do webhook', [
                'error' => $response->get_error_message(),
                'elapsed' => $elapsed . 's',
            ]);
            return $response;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        PPWOO_Debug::info('Resposta do webhook recebida', [
            'status' => $status_code,
            'body_length' => strlen($body),
            'elapsed' => $elapsed . 's',
        ]);
        
        if ($status_code < 200 || $status_code >= 300) {
            PPWOO_Debug::warn('Webhook retornou status não sucesso', [
                'status' => $status_code,
                'body_preview' => mb_substr($body, 0, 200),
            ]);
            return new WP_Error('webhook_error', sprintf(__('Webhook retornou status %d', 'painel-empacotamento'), $status_code), $status_code);
        }
        
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            PPWOO_Debug::error('Erro ao decodificar JSON do webhook', [
                'json_error' => json_last_error_msg(),
                'body_preview' => mb_substr($body, 0, 200),
            ]);
            return new WP_Error('json_decode_error', __('Erro ao decodificar resposta JSON.', 'painel-empacotamento'), json_last_error());
        }
        
        PPWOO_Debug::info('Dados do webhook decodificados com sucesso', [
            'data_keys' => is_array($data) ? array_keys($data) : 'not_array',
        ]);
        
        return $data;
    }
    
    /**
     * Busca pedidos externos do webhook
     * 
     * @return array|WP_Error Array de pedidos ou erro
     */
    public static function fetch_external_orders() {
        $data = self::fetch(['action' => 'get_orders']);
        
        if (is_wp_error($data)) {
            return $data;
        }
        
        // Normaliza os dados para o formato esperado
        if (isset($data['orders']) && is_array($data['orders'])) {
            return $data['orders'];
        }
        
        if (is_array($data) && isset($data[0])) {
            return $data;
        }
        
        PPWOO_Debug::warn('Formato de dados do webhook não reconhecido', ['data_structure' => array_keys($data)]);
        
        return array();
    }
}
