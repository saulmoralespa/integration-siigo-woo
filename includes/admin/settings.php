<?php

wc_enqueue_js( "
    jQuery( function( $ ) {
	
	let siigo_integration_fields = '#woocommerce_wc_siigo_integration_username, #woocommerce_wc_siigo_integration_access_key';	
	let siigo_integration_sandbox_fields = '#woocommerce_wc_siigo_integration_sandbox_username, #woocommerce_wc_siigo_integration_sandbox_access_key';

	$( '#woocommerce_wc_siigo_integration_environment' ).change(function(){

		$( siigo_integration_sandbox_fields + ',' + siigo_integration_fields ).closest( 'tr' ).hide();
	
		if ( '0' === $( this ).val() ) {
			$( siigo_integration_fields ).closest( 'tr' ).show();
			
		}else{
		   $( siigo_integration_sandbox_fields ).closest( 'tr' ).show();
		}
	}).change();
});	
");

$docs = "<p><a target='_blank' href='https://siigonube.siigo.com/#/api-credentials/'>Credenciales Siigo API</a></p>";
$sync_products = "<p><a target='_blank' href='https://siigonube.siigo.com/#/api-products/'>Sincronizar productos</a></p>";

function get_data($section, $method, $callback) {
    $data = isset($_GET['section']) && $_GET['section'] === $section ? $method() : [];
    return array_reduce($data, $callback, []);
}

$groups = get_data('wc_siigo_integration', 'Integration_Siigo_WC::get_groups', function($new_group, $group){
    if(!$group["active"]) return $new_group;
    $new_group[$group["id"]] = "{$group["name"]}";
    return $new_group;
});

$taxes = get_data('wc_siigo_integration', 'Integration_Siigo_WC::get_taxes', function($new_tax, $tax){
    $new_tax[$tax["id"]] = "{$tax["name"]}";
    return $new_tax;
});

$document_types = get_data('wc_siigo_integration', 'Integration_Siigo_WC::get_document_types', function($new_document_type, $document_type){
    $new_document_type[$document_type["id"]] = "{$document_type["type"]} - {$document_type["code"]} - {$document_type["description"]}";
    return $new_document_type;
});

$sellers = get_data('wc_siigo_integration', 'Integration_Siigo_WC::get_sellers', function($new_seller, $seller){
    $new_seller[$seller["id"]] = "{$seller["username"]} - {$seller["first_name"]} {$seller["last_name"]}";
    return $new_seller;
});

$payments = get_data('wc_siigo_integration', 'Integration_Siigo_WC::get_payments', function($new_payment, $payment){
    $new_payment[$payment["id"]] = "{$payment["name"]}";
    return $new_payment;
});

return apply_filters('wc_siigo_integration_settings', [
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
        'title' => __('Enntorno'),
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
        'desc_tip' => true
    ),
    'access_key' => array(
        'title' => __( 'access_key' ),
        'type'  => 'password',
        'description' => __( 'access_key para el entorno de producción' ),
        'desc_tip' => true
    ),
    'sandbox_username' => array(
        'title' => __( 'username' ),
        'type'  => 'email',
        'description' => __( 'username para el entorno de pruebas' ),
        'desc_tip' => true
    ),
    'sandbox_access_key' => array(
        'title' => __( 'access_key' ),
        'type'  => 'password',
        'description' => __( 'access_key para el entorno de pruebas' ),
        'desc_tip' => true
    ),
    'products' => array(
        'title' => __( 'Productos' ),
        'type'  => 'title'
    ),
    'account_group' => array(
        'title' => __( 'Clasificación de inventario' ),
        'type' => 'select',
        'options'  => $groups,
        'default' => '',
        'description' => __( 'Clasificación general de los productos o servicios' ),
        'desc_tip' => false
    ),
    'customize_button'  => array(
        'title' => 'Sincronizar productos',
        'type'  => 'button',
        'description' => 'Sincroniza productos de Siigo con WooCommerce'
    ),
    'invoice' => array(
        'title' => __( 'Factura' ),
        'type'  => 'title'
    ),
    'order_status_generate_invoice' => array(
        'title' => __( 'Estado del pedido' ),
        'type' => 'select',
        'options'  => wc_get_order_statuses(),
        'default' => 'wc-processing',
        'description' => __( 'El estado del pedido en el que se genera la factura' ),
        'desc_tip' => false
    ),
    'tax' => array(
        'title' => __( 'Identificador único del impuesto' ),
        'type' => 'select',
        'options'  => $taxes,
        'default' => '',
        'description' => __( 'El IVA que desea que se muestre en el detalle de la factura. Se recomienda incluir el IVA en los precios de los productos' ),
        'desc_tip' => false
    ),
    'document_type' => array(
        'title' => __( 'Identificador del comprobante' ),
        'type' => 'select',
        'options'  => $document_types,
        'default' => '',
        'description' => __( 'Es el tipo de comprobante utilizado para la generación de la factura' ),
        'desc_tip' => false
    ),
    'seller_generate_invoice' => array(
        'title' => __( 'Vendedor' ),
        'type' => 'select',
        'options'  => $sellers,
        'default' => '',
        'description' => __( 'Vendedor asociado a la factura' ),
        'desc_tip' => false
    ),
    'payment' => array(
        'title' => __( 'Medio de pago' ),
        'type' => 'select',
        'options'  => $payments,
        'default' => '',
        'description' => __( 'Medio de pago asociado a la factura' ),
        'desc_tip' => false
    )
]);