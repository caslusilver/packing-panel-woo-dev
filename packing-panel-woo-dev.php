<?php
/**
 * Plugin Name: Painel de Empacotamento Woo
 * Plugin URI: https://github.com/caslusilver/packing-panel-woo-dev
 * Description: Painel administrativo de empacotamento para pedidos via WooCommerce, com abas Motoboy e Correios, workflow e integração com webhooks externos.
 * Version: 0.2.1
 * Author: Lucas Andrade / AI
 * Author URI: https://github.com/caslusilver
 * License: GPL2
 * Text Domain: painel-empacotamento
 *
 * GitHub Plugin URI: caslusilver/packing-panel-woo-dev
 * Primary Branch: develop
 */

if (!defined('ABSPATH')) exit;

/**
 * TOGGLE DE DEBUG - Altere para true para ver erros e logs
 * ⚠️ IMPORTANTE: Mude para false em produção
 */
define('PPWOO_DEBUG_MODE', true);

// Exibe erros se debug estiver ativo
if (defined('PPWOO_DEBUG_MODE') && PPWOO_DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
}

/**
 * Obtém automaticamente a versão do plugin lendo o cabeçalho.
 */
function painel_empacotamento_get_version() {
    if (!function_exists('get_file_data')) {
        require_once ABSPATH . 'wp-includes/functions.php';
    }

    $plugin_data = get_file_data(__FILE__, [
        'Version' => 'Version',
    ]);

    return $plugin_data['Version'];
}

/**
 * Carrega CSS e JS genéricos do plugin (não o CSS/JS do painel).
 */
add_action('wp_enqueue_scripts', function () {

    $version = painel_empacotamento_get_version();

    wp_enqueue_style(
        'painel-empacotamento-style',
        plugin_dir_url(__FILE__) . 'assets/css/style.css',
        [],
        $version
    );

    wp_enqueue_script(
        'painel-empacotamento-script',
        plugin_dir_url(__FILE__) . 'assets/js/script.js',
        ['jquery'],
        $version,
        true
    );
});

/**
 * Carrega todos os arquivos internos do plugin.
 *
 * ⚠️ Aqui você NÃO coloca lógica — apenas imports.
 */

// Carrega classes auxiliares primeiro
require_once plugin_dir_path(__FILE__) . 'inc/core/class-config.php';
require_once plugin_dir_path(__FILE__) . 'inc/core/class-utils.php';
require_once plugin_dir_path(__FILE__) . 'inc/core/class-security.php';
require_once plugin_dir_path(__FILE__) . 'inc/core/class-webhook.php';
require_once plugin_dir_path(__FILE__) . 'inc/core/class-orders.php';

// Carrega classe principal
require_once plugin_dir_path(__FILE__) . 'inc/class-packing-panel.php';
require_once plugin_dir_path(__FILE__) . 'inc/admin-refresh-cache.php';
