<?php

wc_enqueue_js( "
    jQuery(function($) {
    const selectors = {
        siigoIntegrationFields: '#woocommerce_wc_siigo_integration_username, #woocommerce_wc_siigo_integration_access_key, #woocommerce_wc_siigo_integration_webhook_button',
        siigoIntegrationSandboxFields: '#woocommerce_wc_siigo_integration_sandbox_username, #woocommerce_wc_siigo_integration_sandbox_access_key',
        webhookButton: '#woocommerce_wc_siigo_integration_webhook_button',
        syncButton: '#woocommerce_wc_siigo_integration_sync_button',
        webhookButtonHeader: 'h3#woocommerce_wc_siigo_integration_webhook_button',
        productsTitle: 'h3#woocommerce_wc_siigo_integration_products',
        environmentSelector: '#woocommerce_wc_siigo_integration_environment',
        accessKeyFieldTemplate: '#woocommerce_wc_siigo_integration_\$_access_key'
    };

    function toggleFields() {
        const {
            siigoIntegrationFields,
            siigoIntegrationSandboxFields,
            webhookButton,
            syncButton,
            webhookButtonHeader,
            productsTitle,
            environmentSelector,
            accessKeyFieldTemplate
        } = selectors;

        const isProduction = $(environmentSelector).val() === '0';
        const integrationFields = isProduction ? siigoIntegrationFields : siigoIntegrationSandboxFields;
        const accessKeyField = accessKeyFieldTemplate.replace('\$_', isProduction ? '' : 'sandbox_');
        
        $(siigoIntegrationSandboxFields + ',' + siigoIntegrationFields).closest('tr').hide();
        $(webhookButtonHeader).hide();

        $(integrationFields).closest('tr').show();
        $(webhookButtonHeader).toggle(isProduction);
        
        const accessKey = $(accessKeyField);
        const hasAccessKey = accessKey.length && accessKey.val().length > 0;
        $(productsTitle).toggle(hasAccessKey);
        $(webhookButton).closest('tr').toggle(isProduction && hasAccessKey);
        $(syncButton).closest('tr').toggle(hasAccessKey);
    }

    $(selectors.environmentSelector).change(toggleFields).change();
});
");

$docs = "<p><a target='_blank' href='https://siigonube.siigo.com/#/api-credentials/'>Credenciales Siigo API</a></p>";
$sync_products = "<p><a target='_blank' href='https://siigonube.siigo.com/#/api-products/'>Sincronizar productos</a></p>";

$groups = $this->get_data_options('wc_siigo_integration', 'Integration_Siigo_WC::get_groups', function($new_group, $group){
    if(!$group["active"]) return $new_group;
    $new_group[$group["id"]] = "{$group["name"]}";
    return $new_group;
});

$taxes = $this->get_data_options('wc_siigo_integration', 'Integration_Siigo_WC::get_taxes', function($new_tax, $tax){
    $new_tax[$tax["id"]] = "{$tax["name"]}";
    return $new_tax;
});

$document_types = $this->get_data_options('wc_siigo_integration', 'Integration_Siigo_WC::get_document_types', function($new_document_type, $document_type){
    $new_document_type[$document_type["id"]] = "{$document_type["type"]} - {$document_type["code"]} - {$document_type["description"]}";
    return $new_document_type;
});

$sellers = $this->get_data_options('wc_siigo_integration', 'Integration_Siigo_WC::get_sellers', function($new_seller, $seller){
    $new_seller[$seller["id"]] = "{$seller["username"]} - {$seller["first_name"]} {$seller["last_name"]}";
    return $new_seller;
});

$payments = $this->get_data_options('wc_siigo_integration', 'Integration_Siigo_WC::get_payments', function($new_payment, $payment){
    $new_payment[$payment["id"]] = "{$payment["name"]}";
    return $new_payment;
});

$settings = get_option('woocommerce_wc_siigo_integration_settings', []);


if(isset($settings['webhook']['company_key'])){
    $webhook = [
        'webhook_button'  => array(
            'title' => 'Sincronización de productos mediante webhook habibilitada',
            'type'  => 'title'
        )
    ];
}else{
    $webhook = [
        'webhook_button'  => array(
            'title' => 'Sincronización de productos mediante webhook',
            'type'  => 'button',
            'class' => 'button-secondary siigo-sync-webhook',
            'description' => "Habilita la sincronización automática de productos desde Siigo a WooCommerce.",
            'custom_attributes' => [
                'data-nonce' => wp_create_nonce( 'integration_siigo_sync_webhook' ),
            ],
            'text' => 'Habilitar Webhook',
        )
    ];
}

return apply_filters('wc_siigo_integration_settings', [
    ...[
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
        'catalogs' => array(
            'title' => __( 'Catálogos' ),
            'type'  => 'title'
        ),
        'account_group' => array(
            'title' => __( 'Clasificación de inventario' ),
            'type' => 'select',
            'class' => 'wc-enhanced-select',
            'options'  => $groups,
            'default' => '',
            'description' => __( 'Clasificación general de los productos o servicios' ),
            'desc_tip' => false
        ),
        'tax' => array(
            'title' => __( 'Identificador único del impuesto' ),
            'type' => 'select',
            'class' => 'wc-enhanced-select',
            'options'  => $taxes,
            'default' => '',
            'description' => __( 'El IVA que desea que se muestre en el detalle de la factura. Se recomienda incluir el IVA en los precios de los productos' ),
            'desc_tip' => false
        ),
        'document_type' => array(
            'title' => __( 'Identificador del comprobante' ),
            'type' => 'select',
            'class' => 'wc-enhanced-select',
            'options'  => $document_types,
            'default' => '',
            'description' => __( 'Es el tipo de comprobante utilizado para la generación de la factura' ),
            'desc_tip' => false
        ),
        'payment' => array(
            'title' => __( 'Medio de pago' ),
            'type' => 'select',
            'class'    => 'wc-enhanced-select',
            'options'  => $payments,
            'default' => '',
            'description' => __( 'Medio de pago asociado a la factura' ),
            'desc_tip' => false
        ),
        'products' => array(
            'title' => __( 'Productos' ),
            'type'  => 'title'
        ),
        'sync_button'  => array(
            'title' => 'Sincronizar productos manualmente',
            'type'  => 'button',
            'description' => "Sincroniza los productos de Siigo a WooCommerce. <br> <strong>Nota:</strong> Este proceso puede tardar varios minutos.",
            'text' => 'Sincronizar ahora',
        ),
        ...$webhook,
        'invoice' => array(
            'title' => __( 'Factura' ),
            'type'  => 'title'
        ),
        'order_status_generate_invoice' => array(
            'title' => __( 'Estado del pedido' ),
            'type' => 'select',
            'class' => 'wc-enhanced-select',
            'options'  => wc_get_order_statuses(),
            'default' => 'wc-processing',
            'description' => __( 'El estado del pedido en el que se genera la factura' ),
            'desc_tip' => false
        ),
        'seller_generate_invoice' => array(
            'title' => __( 'Vendedor' ),
            'type' => 'select',
            'class'    => 'wc-enhanced-select',
            'options'  => $sellers,
            'default' => '',
            'description' => __( 'Vendedor asociado a la factura' ),
            'desc_tip' => false
        )
    ]
]);