<?php

class Integration_Siigo_WC_Admin
{
    public function __construct()
    {
        add_action('admin_menu', array($this, 'menu'));
        //add_action( 'wp_ajax_shipping_coordinadora_wc_cswc',array($this,'shipping_coordinadora_wc_cswc_ajax'));
    }

    public function menu(): void
    {
        add_submenu_page(
            null,
            '',
            '',
            'manage_options',
            'wc-siigo-integration',
            array($this, 'wizard')
        );
    }

    public function wizard()
    {
        ?>
        <div class="wrap about-wrap">
            <h3><?php _e( 'Actualicemos y estaremos listos para iniciar :)' ); ?></h3>
        </div>
        <?php
    }
}