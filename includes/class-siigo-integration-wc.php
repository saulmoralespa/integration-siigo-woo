<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class WC_Siigo_Integration extends  WC_Integration
{
    public string $debug;

    public bool $isTest;

    public string $username;

    public string $access_key;

    public function __construct()
    {
        $this->id = INTEGRATION_SIIGO_WC_SMP_ID;
        $this->method_title = __( 'Integration Siigo Woocommerce');
        $this->method_description = __( 'Integration Siigo for Woocommerce');

        $this->debug = $this->get_option( 'debug' );
        $this->isTest = (bool)$this->get_option( 'environment' );

        if ($this->isTest){
            $this->username = $this->get_option('sandbox_username');
            $this->access_key = $this->get_option('sandbox_access_key');
        }else{
            $this->username = $this->get_option('username');
            $this->access_key = $this->get_option('access_key');
        }

        $this->init_form_fields();
        $this->init_settings();

        add_filter( 'woocommerce_settings_api_form_fields_' .  $this->id, array( $this, 'add_additional_settings' ) );
        add_action( 'woocommerce_update_options_integration_' .  $this->id, array( $this, 'process_admin_options' ) );
    }

    public function init_form_fields(): void
    {
        $this->form_fields = include(dirname(__FILE__) . '/admin/settings.php');
    }

    public function add_additional_settings( array $settings ): array
    {
        $additional_settings = [];

        if ( empty( $this->settings ) ) {
            return $settings;
        }

        $environment = $this->get_option('environment');
        $sandbox_access_key = $this->get_option('sandbox_access_key');
        $access_key = $this->get_option('access_key');

        if (($environment == 1 && $sandbox_access_key) ||
            ($environment == 0 && $access_key)) {
            $additional_settings = include(dirname(__FILE__) . '/admin/other_settings.php');
        }

        $settings = array_merge($settings, $additional_settings);

        return apply_filters('wc_siigo_integration_settings', $settings);
    }

    public function admin_options(): void
    {
        ?>
        <h3><?php echo $this->method_title; ?></h3>
        <p><?php echo $this->method_description; ?></p>
        <table class="form-table">
            <?php $this->generate_settings_html(); ?>
        </table>
        <?php
    }

    public function validate_password_field($key, $value) :string
    {
        $key_username =  $key === 'sandbox_access_key' ? 'sandbox_username' : 'username';
        $username = $_POST["woocommerce_{$this->id}_{$key_username}"] ?? null;
        $enabled = $_POST["woocommerce_{$this->id}_enabled"] ?? false;

        if($enabled && $username && $value){
            $status = Integration_Siigo_WC::test_token($username, $value);
            if(!$status){
                WC_Admin_Settings::add_error("Credenciales inválidas");
                $value = '';
            }
        }

        return $value;
    }

    public function generate_button_html( $key, $data ): string
    {
        $field    = $this->plugin_id . $this->id . '_' . $key;
        $defaults = array(
            'class'             => '',
            'css'               => '',
            'custom_attributes' => [
                'data-nonce' => wp_create_nonce( 'integration_siigo_sync_products' ),
            ],
            'desc_tip'          => false,
            'description'       => '',
            'title'             => '',
        );

        $data = wp_parse_args( $data, $defaults );

        if(!Integration_Siigo_WC::get_instance()) return '';

        ob_start();
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $field ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
                <?php echo $this->get_tooltip_html( $data ); ?>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
                    <button class="<?php echo esc_attr( $data['class'] ); ?>" type="button" name="<?php echo esc_attr( $field ); ?>" id="<?php echo esc_attr( $field ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" <?php echo $this->get_custom_attribute_html( $data ); ?>><?php echo wp_kses_post( $data['text'] ); ?></button>
                    <?php echo $this->get_description_html( $data ); ?>
                </fieldset>
            </td>
        </tr>
        <?php
        return ob_get_clean();
    }

    public function get_data_options(string $section, string $method, callable $callback)
    {
        $data = isset($_GET['section']) && $_GET['section'] === $section ? $method() : [];
        return array_reduce($data, $callback, []);
    }
}