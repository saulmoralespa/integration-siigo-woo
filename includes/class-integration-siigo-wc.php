<?php

use Saulmoralespa\Siigo\Client;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields;

class Integration_Siigo_WC
{
    private static ?Client $siigo = null;

    private static $integration_settings = null;

    const SKU_SHIPPING = 'S-P-W';

    public static function test_token($username, $access_key): bool
    {
        try{
            $file_token = dirname(__FILE__) . '/token.json';
            if(file_exists($file_token)) unlink($file_token);
            $siigo = new Client($username, $access_key);
            $siigo->setTokenFilePath($file_token)->getAccessToken();
        }catch(Exception $exception){
            integration_siigo_wc_smp()->log($exception->getMessage());
            return false;
        }

        return true;
    }

    public static function get_instance(): ?Client
    {
        if(isset(self::$integration_settings) && isset(self::$siigo)) return self::$siigo;

        self::$integration_settings = get_option('woocommerce_wc_siigo_integration_settings', null);

        if(!isset(self::$integration_settings)) return null;

        self::$integration_settings = (object)self::$integration_settings;

        if(self::$integration_settings->enabled === 'no') return null;

        if(self::$integration_settings->environment){
            self::$integration_settings->username = self::$integration_settings->sandbox_username;
            self::$integration_settings->access_key = self::$integration_settings->sandbox_access_key;
        }

        self::$siigo = new Client(self::$integration_settings->username, self::$integration_settings->access_key);
        $file_token = dirname(__FILE__) . '/token.json';
        self::$siigo->setTokenFilePath($file_token);

        return self::$siigo;
    }

    public static function sync_products_siigo(): void
    {
        if (!self::get_instance()) return;

        try {

            $dateTime = new DateTime('now', new DateTimeZone('UTC'));

            $queries_created = [
                "created_start" => $dateTime->format('Y-m-d'),
                "created_end" => $dateTime->format('Y-m-d\TH:i:s.u\Z')
            ];

            $queries_updated = [
                "updated_start" => $dateTime->format('Y-m-d'),
                "updated_end" => $dateTime->format('Y-m-d\TH:i:s.u\Z')
            ];

            $response_created = self::get_instance()->getProducts($queries_created);
            $response_updated = self::get_instance()->getProducts($queries_updated);

            $products = array_merge($response_created['results'] ?? [], $response_updated['results'] ?? []);

            if(empty($products)) return;

            foreach ($products as $product){
                $name = $product['name'];
                $sku = $product['code'];
                $price = $product['prices'][0]['price_list'][0]['value'] ?? 0;
                $sale_price = $product['prices'][0]['price_list'][1]['value'] ?? '';
                $description = $product['description'] ?? '';
                $stock_control = $product['stock_control'] ?? false;
                $available_quantity = $product['available_quantity'] ?? 0;
                $product_id = wc_get_product_id_by_sku($sku);

                if($product_id){
                    $wc_product = wc_get_product($product_id);
                }else{
                    $wc_product = new WC_Product();
                }
                $wc_product->set_name($name);
                $wc_product->set_description($description);
                $wc_product->set_price($price);
                $wc_product->set_sale_price($sale_price);
                $wc_product->set_regular_price($price);
                $wc_product->set_sku($sku);
                $wc_product->set_manage_stock($stock_control);
                $wc_product->set_stock_quantity($available_quantity);
                $wc_product->save();
            }
        }catch (Exception $exception){
            integration_siigo_wc_smp()->log($exception->getMessage());
        }
    }

    public static function sync_products_woo(): void
    {
        if (!self::get_instance()) return;

        $products_ids = get_posts( array(
            'post_type'        => ['product','product_variation'],
            'numberposts'      => -1,
            'post_status'      => 'publish',
            'fields'           => 'ids',
            'meta_query'       => array(
                'relation' => 'AND',
                array(
                    'key'     => '_sku',
                    'value'   => '',
                    'compare' => '!='
                ),
                array(
                    'key'     => '_sync_siigo',
                    'compare' => 'NOT EXISTS',
                ),
            )
        ));

        self::sync_products_to_siigo($products_ids);
    }

    public static function sync_products_to_siigo(array $ids): void
    {
        if (!self::get_instance()) return;

        foreach ( $ids as $post_id ) {
            $product = wc_get_product($post_id);
            if(!$product->get_sku() || $product->meta_exists('_sync_siigo') ) continue;

            try {

                if(!self::$integration_settings->account_group) throw new Exception('Clasificación de inventario no configurado');

                $dataProduct = [
                    "code" => $product->get_sku(),
                    "name" => $product->get_name(),
                    "account_group" => self::$integration_settings->account_group,
                    "type" => $product->is_virtual() || $product->is_downloadable() ? 'Service' : 'Product', //Product, Service, ConsumerGood
                    "description" => $product->get_description(),
                    "stock_control" => $product->managing_stock(),
                    "prices" => [
                        [
                            "currency_code" => get_woocommerce_currency(),
                            "price_list" => [
                                [
                                    "position" => 1,
                                    "value" => wc_format_decimal($product->get_price(), 0)
                                ]
                            ]
                        ]
                    ]
                ];

                self::get_instance()->createProduct($dataProduct);
                update_post_meta($post_id, '_sync_siigo', true);
            }catch (Exception $exception){
                integration_siigo_wc_smp()->log($exception->getMessage());
            }
        }
    }

    public static function create_shipping_product(): void
    {
        if (!self::get_instance()) return;

        try {
            if(!self::$integration_settings->account_group) throw new Exception('Clasificación de inventario no configurado');
            $dataProduct = [
                "code" => self::SKU_SHIPPING,
                "name" => 'Envío',
                "account_group" => self::$integration_settings->account_group
            ];
            self::get_instance()->createProduct($dataProduct);
        }catch (Exception $exception){
            integration_siigo_wc_smp()->log($exception->getMessage());
        }
    }

    public static function generate_invoice($order_id, $previous_status, $next_status): void
    {
        if (!self::get_instance() || wc_get_order_status_name($next_status) !== wc_get_order_status_name(self::$integration_settings->order_status_generate_invoice)) return;

        $order = wc_get_order($order_id);

        if(!apply_filters('wc_siigo_integration_verify_before_generate_invoice', true, $order)) return;

        if($order->meta_exists('_invoice_number_siigo')) return;

        $field_type_document = 'document/type_document';
        $field_dni = 'document/dni';
        $checkout_fields = Package::container()->get( CheckoutFields::class );
        $billing_type_document = $checkout_fields->get_field_from_object( $field_type_document, $order, 'billing' );
        $shipping_type_document = $checkout_fields->get_field_from_object( $field_type_document, $order, 'shipping' );


        $classic_type_document = get_post_meta( $order_id, '_billing_type_document', true ) ?: get_post_meta( $order_id, '_shipping_type_document', true );
        $classic_dni = get_post_meta( $order_id, self::$integration_settings->dni_field, true ) ?: get_post_meta( $order_id, '_billing_dni', true ) ?: get_post_meta( $order_id, '_shipping_dni', true );

        $billing_dni = $checkout_fields->get_field_from_object( $field_dni, $order, 'billing' );
        $shipping_dni = $checkout_fields->get_field_from_object( $field_dni, $order, 'shipping' );

        $type_document = $billing_type_document ?: $shipping_type_document ? : $classic_type_document;
        $dni = $billing_dni ?: $shipping_dni ?: $classic_dni;
        $dni = trim($dni);

        $country_code = $order->get_billing_country() ?: $order->get_shipping_country();
        $country_code = apply_filters('wc_siigo_integration_country_code', $country_code);
        $state = $order->get_billing_state() ?: $order->get_shipping_state();
        $city = $order->get_billing_city() ?: $order->get_shipping_city();
        $states_dane = include(dirname(__FILE__) . '/states-dane.php');
        $state_code = $states_dane[$state] ?? null;
        $state_code = apply_filters('wc_siigo_integration_state_code', $state_code);
        $city_code = self::get_code_city($state, $city);
        $city_code = apply_filters('wc_siigo_integration_city_code', $city_code);
        $address = $order->get_billing_address_1() ?: $order->get_shipping_address_1();
        $address = apply_filters('wc_siigo_integration_address', $address);

        if(!$country_code) throw new Exception('País no encontrado');
        if(!$state_code) throw new Exception('Departamento no encontrado');
        if(!$city_code) throw new Exception('Ciudad no encontrada');

        try {

            $dv_nit = 0;

            $name = [
                $order->get_billing_first_name() ?: $order->get_shipping_first_name(),
                $order->get_billing_last_name() ?: $order->get_shipping_last_name()
            ];

            $company = $order->get_billing_company() ?: $order->get_shipping_company();

            if ($type_document === 'NIT'){
                $dv_nit = self::calculateDv($dni);
                $name = $company ?: current($name);
                $name = [$name];
            }

            $dataClient = [
                "type" => "Customer", //Customer, Supplier, Other
                "person_type" => $type_document === 'NIT' ? 'Company' : "Person", // Person or Company
                "id_type" => $type_document === 'NIT' ? '31' : "13", // 13: Cédula de ciudadanía, 31: NIT
                "identification" => $dni,
                "name" => $name,
                "commercial_name" => $company,
                "branch_office" => 0, //Sucursal, valor por default 0
                "vat_responsible" => $type_document === 'NIT', // True si es responsable de IVA false si no
                "fiscal_responsibilities" => [
                    [
                        "code" => "R-99-PN"
                    ]
                ],
                "address" => [
                    "address" => $address,
                    "city" => [
                        "country_code" => $country_code,
                        "state_code" => $state_code,
                        "city_code" => $city_code
                    ],
                    "postal_code" => $order->get_billing_postcode() ?: $order->get_shipping_postcode(),
                ],
                "phones" => [
                    [
                        "indicative" => "57",
                        "number" => $order->get_billing_phone() ?: $order->get_shipping_phone(),
                        "extension" => ""
                    ]
                ],
                "contacts" => [
                    [
                        "first_name" => $order->get_billing_first_name() ?: $order->get_shipping_first_name(),
                        "last_name" => $order->get_billing_last_name() ?: $order->get_shipping_last_name(),
                        "email" => $order->get_billing_email(),
                        "phone" => [
                            "indicative" => "57",
                            "number" => $order->get_billing_phone() ?: $order->get_shipping_phone(),
                            "extension" => ""
                        ]
                    ]
                ]
            ];

            if($dv_nit){
                $dataClient["check_digit"] = "$dv_nit";
            }

            $queryClient = [
                "identification" => $dni
            ];

            $clients = self::$siigo->getClients($queryClient);

            if(empty($clients['results'])){
                self::$siigo->createClient($dataClient);
            }

            $queries = [
                "code" => self::SKU_SHIPPING,
            ];

            $products = self::$siigo->getProducts($queries);

            if(empty($products['results'])){
                self::create_shipping_product();
            }

            $items = [];
            $tax_rates = [];

            foreach ( $order->get_items('tax') as $item ) {
                $tax_rates[$item->get_rate_id()] = $item->get_rate_percent();
            }

            foreach ($order->get_items() as $item){
                /**
                 * @var WC_Product|bool $product
                 */
                $product = $item->get_product();
                $item_taxes   = $item->get_taxes();
                $tax_rate_id  = current( array_keys($item_taxes['subtotal']) );
                $tax_percent  = $tax_rates[$tax_rate_id];
                $tax_total    = $item_taxes['total'][$tax_rate_id];
                $items[] = [
                    "code" => $product->get_sku(),
                    "description" => apply_filters('wc_siigo_integration_description_item', $product->get_name()),
                    "quantity" => $item->get_quantity(),
                    "discount" => wc_format_decimal($item->get_subtotal() - $item->get_total(), 0),
                    "price" => wc_format_decimal($item->get_total() / $item->get_quantity(), 0)
                ];

                if($tax_percent && self::$integration_settings->tax){
                    $items[count($items) - 1]['taxes'] = [
                        [
                            "id" => self::$integration_settings->tax
                        ]
                    ];
                    $items[count($items) - 1]['taxed_price']  = wc_format_decimal($item->get_total() + $tax_total, 0);
                }
            }

            if($order->get_shipping_total()){
                $items[] = [
                    "code" => self::SKU_SHIPPING,
                    "description" => 'Envío',
                    "quantity" => 1,
                    "price" => wc_format_decimal($order->get_shipping_total(), 0)
                ];
            }

            if(!self::$integration_settings->document_type) throw new Exception('Tipo de documento no configurado');
            if(!self::$integration_settings->seller_generate_invoice) throw new Exception('Vendedor no configurado');
            if(!self::$integration_settings->payment) throw new Exception('Método de pago no configurado');

            $queries = [
                "type" => "FV"
            ];

            $dataInvoice = [
                "document" => [
                    "id" => (int)self::$integration_settings->document_type,
                ],
                "date" => wp_date('Y-m-d'),
                "customer" => [
                    "identification" => $dni,
                    "branch_office" => "0"
                ],
                "seller" => self::$integration_settings->seller_generate_invoice,
                "items" => $items,
                "stamp" => [
                    "send" => self::$integration_settings->send_dian === "yes"
                ],
                "mail" => [
                    "send" => true
                ],
                "payments" => [
                    [
                        "id" => self::$integration_settings->payment,
                        "value" => wc_format_decimal($order->get_total(), 0)
                    ]
                ]
            ];

            $documentsTypes = self::$siigo->getDocumentTypes($queries);
            array_filter($documentsTypes, function ($documentType) use (&$dataInvoice){
                if(!$documentType['automatic_number'] && $documentType['id'] === (int)self::$integration_settings->document_type){
                    $dataInvoice['number'] = $documentType['consecutive'];
                }
            });

            $invoice = self::$siigo->createInvoice($dataInvoice);
            $order->add_order_note( sprintf(__( 'Número de factura Siigo: %s.' ), $invoice['number']));
            $order->add_meta_data('_invoice_number_siigo', $invoice['number']);
            $order->save_meta_data();
        }catch (Exception $exception){
            integration_siigo_wc_smp()->log($exception->getMessage());
        }
    }

    public static function webhook(WP_REST_Request $request): WP_REST_Response
    {
        try {
            $product = json_decode($request->get_body(), true);
            $name = $product['name'];
            $sku = $product['code'];
            $price = $product['prices'][0]['price_list'][0]['value'] ?? 0;
            $sale_price = $product['prices'][0]['price_list'][1]['value'] ?? '';
            $description = $product['description'] ?? '';
            $stock_control = $product['stock_control'] ?? false;
            $available_quantity = $product['available_quantity'] ?? 0;
            $product_id = wc_get_product_id_by_sku($sku);

            if($product_id){
                $wc_product = wc_get_product($product_id);
            }else{
                $wc_product = new WC_Product();
            }

            $wc_product->set_name($name);
            $wc_product->set_description($description);
            $wc_product->set_price($price);
            $wc_product->set_sale_price($sale_price);
            $wc_product->set_regular_price($price);
            $wc_product->set_sku($sku);
            $wc_product->set_manage_stock($stock_control);
            $wc_product->set_stock_quantity($available_quantity);
            $wc_product->save();
        }catch (Exception $exception) {
            integration_siigo_wc_smp()->log($exception->getMessage());
        }

        return new WP_REST_Response();
    }

    public static function webhook_permissions_check(WP_REST_Request $request): bool
    {
        if (!self::get_instance()) return false;

        $body = json_decode($request->get_body(), true);

        return isset($body['username']) && $body['username'] == self::$integration_settings->username;
    }

    public static function subscribeWebhook(): bool
    {
        if (!self::get_instance()) return false;

        $namespace = integration_siigo_wc_smp()->namespace;
        $endpoint = get_rest_url(null, "/$namespace/webhook");

        try {
            $webhook_products_create = [
                "application_id" => "wordpress",
                "url" => $endpoint,
                "topic" => "public.siigoapi.products.create",
            ];

            $webhook_products_update = [
                "application_id" => "wordpress",
                "url" => $endpoint,
                "topic" => "public.siigoapi.products.update",
            ];
            $webhook_stock_update = [
                "application_id" => "wordpress",
                "url" => $endpoint,
                "topic" => "public.siigoapi.products.stock.update",
            ];

            $webhook_create = self::get_instance()->subscribeWebhook($webhook_products_create);
            self::get_instance()->subscribeWebhook($webhook_products_update);
            self::get_instance()->subscribeWebhook($webhook_stock_update);

            $settings = get_option('woocommerce_wc_siigo_integration_settings', []);
            $settings['webhook']['company_key'] = $webhook_create['company_key'];
            update_option('woocommerce_wc_siigo_integration_settings', $settings);
        }catch (Exception $exception){
            integration_siigo_wc_smp()->log($exception->getMessage());
            return false;
        }

        return true;
    }

    public static function get_groups(): array
    {
        $groups = [];
        if (!self::get_instance()) return $groups;

        try {
            $groups = self::$siigo->getAccountGroups();
        }catch (Exception $exception){
            integration_siigo_wc_smp()->log($exception->getMessage());
        }

        return $groups;
    }

    public static function get_taxes(): array
    {
        $taxes = [];
        if (!self::get_instance()) return $taxes;

        try {
            $taxes = self::$siigo->getTaxes();
        }catch (Exception $exception){
            integration_siigo_wc_smp()->log($exception->getMessage());
        }

        return $taxes;
    }

    public static function get_document_types(): array
    {
        $document_types = [];
        if (!self::get_instance()) return $document_types;

        try {
            $queries = [
                "type" => "FV"
            ];
            $document_types = self::$siigo->getDocumentTypes($queries);
        }catch (Exception $exception){
            integration_siigo_wc_smp()->log($exception->getMessage());
        }

        return $document_types;
    }

    public static function get_sellers(): array
    {
        $sellers = [];
        if (!self::get_instance()) return $sellers;

        try {
            $sellers = self::$siigo->getUsers();
        }catch (Exception $exception){
            integration_siigo_wc_smp()->log($exception->getMessage());
        }

        return $sellers['results'] ?? [];
    }

    public static function get_payments(): array
    {
        $payments = [];
        if (!self::get_instance()) return $payments;

        try {
            $queries = [
                "document_type" => "FV"
            ];
            $payments = self::$siigo->getPaymentsMethods($queries);
        }catch (Exception $exception){
            integration_siigo_wc_smp()->log($exception->getMessage());
        }

        return $payments;
    }

    public static function get_code_city($state, $city, $country = 'CO'): bool|string
    {
        $name_state = self::name_destination($country, $state);
        $address = "$city - $name_state";
        $cities = include dirname(__FILE__) . '/cities.php';
        $address  = self::clean_string($address);
        $cities = self::clean_cities($cities);
        $destine = array_search($address, $cities);

        if ($destine && strlen($destine) === 4)
            $destine = '0' . $destine;

        return $destine;
    }

    public static function name_destination($country, $state_destination): string
    {
        $countries_obj = new WC_Countries();
        $country_states_array = $countries_obj->get_states();

        $name_state_destination = '';

        if (!isset($country_states_array[$country][$state_destination]))
            return $name_state_destination;

        $name_state_destination = $country_states_array[$country][$state_destination];
        return self::clean_string($name_state_destination);
    }

    public static function clean_string(string $string):string
    {
        $not_permitted = array("á", "é", "í", "ó", "ú", "Á", "É", "Í",
            "Ó", "Ú", "ñ");
        $permitted = array("a", "e", "i", "o", "u", "A", "E", "I", "O",
            "U", "n");
        $text = str_replace($not_permitted, $permitted, $string);
        return mb_strtolower($text);
    }

    public static function clean_cities($cities)
    {
        foreach ($cities as $key => $value) {
            $cities[$key] = self::clean_string($value);
        }

        return $cities;
    }

    public static function calculateDv($nit): int
    {
        $vpri = array(16);
        $z = strlen($nit);

        $vpri[1]  =  3 ;
        $vpri[2]  =  7 ;
        $vpri[3]  = 13 ;
        $vpri[4]  = 17 ;
        $vpri[5]  = 19 ;
        $vpri[6]  = 23 ;
        $vpri[7]  = 29 ;
        $vpri[8]  = 37 ;
        $vpri[9]  = 41 ;
        $vpri[10] = 43 ;
        $vpri[11] = 47 ;
        $vpri[12] = 53 ;
        $vpri[13] = 59 ;
        $vpri[14] = 67 ;
        $vpri[15] = 71 ;

        $x = 0 ;

        for ($i = 0; $i < $z; $i++) {
            $y = (int)substr($nit, $i, 1);
            $x += ($y * $vpri[$z - $i]);
        }

        $y = $x % 11;

        return ($y > 1) ? 11 - $y : $y;

    }
}