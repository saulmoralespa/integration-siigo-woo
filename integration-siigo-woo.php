<?php
/**
 * Plugin Name: Integration Siigo Woocommerce
 * Description: Integración del sistama contable y de facturación Siigo para Woocoommerce
 * Version: 0.0.19
 * Author: Saul Morales Pacheco
 * Author URI: https://saulmoralespa.com
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * WC tested up to: 8.9
 * WC requires at least: 8.9
 * Requires Plugins: woocommerce,departamentos-y-ciudades-de-colombia-para-woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if(!defined('INTEGRATION_SIIGO_WC_SMP_VERSION')){
    define('INTEGRATION_SIIGO_WC_SMP_VERSION', '0.0.19');
}

add_action( 'plugins_loaded', 'integration_siigo_wc_smp_init');

function integration_siigo_wc_smp_init(): void
{
    if(!integration_siigo_wc_sm_requirements()) return;

    integration_siigo_wc_smp()->run_siigo();
}

function integration_siigo_wc_smp_notices($notice): void
{
    ?>
    <div class="error notice">
        <p><?php echo esc_html( $notice ); ?></p>
    </div>
    <?php
}

function integration_siigo_wc_sm_requirements(): bool
{
    if ( !version_compare(PHP_VERSION, '8.1.0', '>=') ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            add_action(
                'admin_notices',
                function() {
                    integration_siigo_wc_smp_notices( 'Integration Siigo Woocommerce: Requiere la versión de php >= 8.1');
                }
            );
        }
        return false;
    }

    return true;
}

function integration_siigo_wc_smp(){
    static $plugin;
    if (!isset($plugin)){
        require_once('includes/class-integration-siigo-wc-plugin.php');
        $plugin = new Integration_Siigo_WC_Plugin(__FILE__, INTEGRATION_SIIGO_WC_SMP_VERSION);
    }
    return $plugin;
}

function activate_integration_siigo_wc_smp(): void
{
    if ( ! wp_next_scheduled( 'integration_siigo_wc_smp_schedule'  ) ) {
        $timestamp = strtotime('5am');
        wp_schedule_event($timestamp, 'daily', 'integration_siigo_wc_smp_schedule');
    }
}

function deactivation_integration_siigo_wc_smp(): void
{
    wp_clear_scheduled_hook( 'integration_siigo_wc_smp_schedule' );
}

register_activation_hook( __FILE__, 'activate_integration_siigo_wc_smp' );
register_deactivation_hook( __FILE__, 'deactivation_integration_siigo_wc_smp' );