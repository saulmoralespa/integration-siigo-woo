<?php

class Integration_Siigo_WC_Admin
{
    public function __construct()
    {
        add_action( 'init', array( $this, 'register_settings' ) );
        add_action('admin_menu', array($this, 'menu'));
    }

    public function register_settings(): void
    {
        $schema = array(
            'type'       => 'object',
            'properties' => array(
                'enabled' => array(
                    'type' => 'string',
                    'enum' => array('no', 'yes')
                ),
                'debug' => array(
                    'type' => 'string',
                    'enum' => array('no', 'yes')
                ),
                'environment' => array(
                    'type' => 'integer',
                    'enum' => array(0, 1),
                    'sanitize_callback' => 'intval'
                ),
                'username' => array(
                    'type' => 'string'
                ),
                'access_key' => array(
                    'type' => 'string'
                ),
                'sandbox_username' => array(
                    'type' => 'string'
                ),
                'sandbox_access_key' => array(
                    'type' => 'string'
                ),
                'account_group' => array(
                    'type' => 'string'
                ),
                'tax' => array(
                    'type' => 'string'
                ),
                'document_type' => array(
                    'type' => 'string'
                ),
                'payment' => array(
                    'type' => 'string'
                ),
                'min_stock_quantity' => array(
                    'type' => 'integer'
                ),
                'warehouse' => array(
                  'type' => 'string'
                ),
                'order_status_generate_invoice' => array(
                    'type' => 'string'
                ),
                'seller_generate_invoice' => array(
                    'type' => 'string'
                ),
                'send_dian' => array(
                    'type' => 'string',
                    'enum' => array('no', 'yes')
                ),
                'dni_field' => array(
                    'type' => 'string'
                )
            )
        );

        $id = INTEGRATION_SIIGO_WC_SMP_ID;

        register_setting(
            'options',
            "woocommerce_{$id}_settings",
            array(
                'type'         => 'object',
                'default'      => array(
                    'enabled' => 'no',
                    'debug' => 'no',
                    'environment' => 0,
                    'username' => '',
                    'access_key' => '',
                    'sandbox_username' => '',
                    'sandbox_access_key' => '',
                    'account_group' => '',
                    'tax' => '',
                    'document_type' => '',
                    'payment' => '',
                    'min_stock_quantity' => 0,
                    'warehouse' => '',
                    'order_status_generate_invoice' => 'wc-processing',
                    'seller_generate_invoice' => '',
                    'send_dian' => 'no',
                    'dni_field' => '',
                ),
                'show_in_rest' => array(
                    'schema' => $schema
                ),
            )
        );
    }

    public function menu(): void
    {
        add_submenu_page(
            'woocommerce',
            '',
            '',
            'manage_options',
            'wizard-siigo',
            array($this, 'wizard')
        );
    }

    public function wizard(): void
    {
        printf(
            '<div class="wrap" id="wizard-siigo">%s</div>',
            esc_html__( 'Cargando…')
        );
        ?>
        <?php
    }
}