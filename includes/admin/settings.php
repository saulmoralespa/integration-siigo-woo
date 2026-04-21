<?php

$docs_url = '<a href="https://shop.saulmoralespa.com/integration-siigo-woocommerce/" target="_blank" style="text-decoration: none;">
            📘 Ver documentación completa del plugin
        </a>';
$docs = "<p><a target='_blank' href='https://siigonube.siigo.com/#/api-credentials/'>Credenciales Siigo API</a></p>";

return [
    'docs' => array(
        'title' => '',
        'type' => 'title',
        'description' => $docs_url
    ),
    'enabled' => array(
        'title' => __('Activar/Desactivar'),
        'type' => 'checkbox',
        'label' => __('Activar Siigo'),
        'default' => 'no'
    ),
    'debug' => array(
        'title'       => __( 'Depurador' ),
        'label'       => __( 'Habilitar el modo de desarrollador' ),
        'type'        => 'checkbox',
        'default'     => 'no',
        'description' => __( 'Enable debug mode to show debugging information in woocommerce - status' ),
        'desc_tip' => true
    ),
    'environment' => array(
        'title' => __('Entorno'),
        'type'        => 'select',
        'class'       => 'wc-enhanced-select',
        'description' => __('Entorno de pruebas o producción'),
        'desc_tip' => true,
        'default' => 1,
        'options'     => array(
            0 => __( 'Producción'),
            1 => __( 'Pruebas')
        ),
    ),
    'api'  => array(
        'title' => __( 'Credenciales API' ),
        'type'  => 'title',
        'description' => $docs
    ),
    'username' => array(
        'title' => __( 'username' ),
        'type'  => 'email',
        'description' => __( 'username para el entorno de producción' ),
        'desc_tip' => false
    ),
    'access_key' => array(
        'title' => __( 'access_key' ),
        'type'  => 'password',
        'description' => __( 'access_key para el entorno de producción' ),
        'desc_tip' => false
    ),
    'sandbox_username' => array(
        'title' => __( 'username' ),
        'type'  => 'email',
        'description' => __( 'username para el entorno de pruebas' ),
        'desc_tip' => false
    ),
    'sandbox_access_key' => array(
        'title' => __( 'access_key' ),
        'type'  => 'password',
        'description' => __( 'access_key para el entorno de pruebas' ),
        'desc_tip' => false
    )
];