<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}


class Integration_Siigo_WC_Plugin
{
    /**
     * Absolute plugin path.
     *
     * @var string
     */
    public string $plugin_path;
    /**
     * Absolute plugin URL.
     *
     * @var string
     */
    public string $plugin_url;
    /**
     * assets plugin.
     *
     * @var string
     */
    public string $assets;
    /**
     * Absolute path to plugin includes dir.
     *
     * @var string
     */
    public string $includes_path;
    /**
     * Absolute path to plugin lib dir
     *
     * @var string
     */
    public string $lib_path;
    /**
     * @var bool
     */
    private bool $bootstrapped = false;

    /**
     * Endpoint namespace.
     *
     * @var string
     */
    public string $namespace = 'wcsiigointegration/v1';

    public WC_Logger $logger;

    public function __construct(
        protected $file,
        protected $version
    )
    {
        $this->plugin_path = trailingslashit(plugin_dir_path($this->file));
        $this->plugin_url = trailingslashit(plugin_dir_url($this->file));
        $this->assets = $this->plugin_url . trailingslashit('assets');
        $this->includes_path = $this->plugin_path . trailingslashit('includes');
        $this->lib_path = $this->plugin_path . trailingslashit('lib');
        $this->logger = new WC_Logger();
    }

    public function run_siigo(): void
    {
        try {
            if ($this->bootstrapped) {
                throw new Exception('Integration Siigo Woocommerce can only be called once');
            }
            $this->_run();
            $this->bootstrapped = true;
        } catch (Exception $e) {
            if (is_admin() && !defined('DOING_AJAX')) {
                add_action('admin_notices', function () use ($e) {
                    integration_siigo_wc_smp_notices($e->getMessage());
                });
            }
        }
    }

    private function _run(): void
    {
        if (!class_exists('\Saulmoralespa\Siigo\Client')){
            require_once($this->lib_path . 'vendor/autoload.php');
        }

        if (!class_exists('WC_Siigo_Integration')) {
            require_once($this->includes_path . 'class-siigo-integration-wc.php');
            add_filter('woocommerce_integrations', array($this, 'add_integration'));
        }

        if (!class_exists('Integration_Siigo_WC')) {
            require_once($this->includes_path . 'class-integration-siigo-wc.php');
        }

        require_once($this->includes_path . 'class-integration-siigo-wc-admin.php');
        (new Integration_Siigo_WC_Admin());

        require_once ($this->lib_path . 'plugin-update-checker/plugin-update-checker.php');

        $myUpdateChecker = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
            'https://github.com/saulmoralespa/integration-siigo-woo',
            $this->file
        );

        $myUpdateChecker->setBranch('main');
        $myUpdateChecker->getVcsApi()->enableReleaseAssets();

        add_filter('plugin_action_links_' . plugin_basename($this->file), array($this, 'plugin_action_links'));
        add_filter('bulk_actions-edit-product', array($this, 'sync_bulk_actions'), 20 );
        add_filter('handle_bulk_actions-edit-product', array($this, 'sync_bulk_action_edit_product'), 10, 3);
        add_filter('manage_woocommerce_page_wc-orders_columns', array($this, 'invoice_column'));
        add_filter('woocommerce_default_address_fields', array($this, 'document_woocommerce_fields'));
        add_action('woocommerce_checkout_update_order_meta', array($this, 'document_woocommerce_fields_update_order_meta'));

        add_action('woocommerce_checkout_process', array('Integration_Siigo_WC', 'verify_nit_validation'));
        add_action('woocommerce_init', array($this, 'register_additional_checkout_fields'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts_admin'));
        add_action('admin_notices', array($this, 'premium_survey_admin_notice'));
        add_action('woocommerce_order_status_changed', array('Integration_Siigo_WC', 'generate_invoice'), 10, 3);
        add_action('integration_siigo_wc_smp_schedule', array('Integration_Siigo_WC', 'sync_products_siigo'));
        add_action('integration_siigo_wc_smp_schedule_sync_woo_siigo', array('Integration_Siigo_WC', 'sync_products_woo'));
        add_action('wp_ajax_integration_siigo_sync_products', array($this, 'ajax_integration_siigo_sync_products'));
        add_action('wp_ajax_integration_siigo_sync_woo_siigo', array($this, 'ajax_integration_siigo_sync_woo_siigo'));
        add_action('wp_ajax_integration_siigo_sync_webhook', array($this, 'ajax_integration_siigo_sync_webhook'));
        add_action('wp_ajax_integration_siigo_send_premium_survey', array($this, 'ajax_integration_siigo_send_premium_survey'));
        add_action('wp_ajax_integration_siigo_dismiss_premium_survey_notice', array($this, 'ajax_integration_siigo_dismiss_premium_survey_notice'));
        add_action('woocommerce_admin_order_data_after_order_details',  array($this, 'display_custom_editable_field_on_admin_orders'), 10);
        add_action('woocommerce_process_shop_order_meta', array($this, 'save_order_custom_field_meta'), 10);
        add_action('manage_woocommerce_page_wc-orders_custom_column', array($this, 'content_column_invoice'), 10, 2 );

        add_action('rest_api_init', function () {
            register_rest_route($this->namespace, '/webhook', array(
                'methods' => 'POST',
                'callback' => array('Integration_Siigo_WC', 'webhook'),
                'permission_callback' => array('Integration_Siigo_WC', 'webhook_permissions_check')
            ) );
        } );

        add_action(
            'woocommerce_set_additional_field_value',
            function ( $key, $value, $group, $wc_object ) {

                if ('document/dni' !== $key ) {
                    return;
                }

                $type_document_key = "_wc_$group/document/type_document";
                $dni_key = "_wc_$group/document/dni";
                $type_document = $wc_object->get_meta($type_document_key);
                $dni = $wc_object->get_meta($dni_key);

                if($type_document === 'NIT'){
                    $dv = Integration_Siigo_WC::calculateDv($dni);
                    $dni = "$dni-$dv";
                }

                $wc_object->update_meta_data($dni_key, $dni, true);
                $wc_object->save();
            },
            10,
            4
        );
    }

    public function plugin_action_links($links): array
    {
        $settings_url = admin_url('admin.php?page=wc-settings&tab=integration&section=' . INTEGRATION_SIIGO_WC_SMP_ID);
        $survey_url = add_query_arg('open_premium_survey', '1', $settings_url);

        $links[] = '<a href="' . esc_url($settings_url) . '">' . 'Configuraciones' . '</a>';
        $links[] = '<a href="' . esc_url($survey_url) . '">' . 'Encuesta Premium' . '</a>';
        $links[] = '<a target="_blank" href="https://shop.saulmoralespa.com/integration-siigo-woocommerce/">' . 'Documentación' . '</a>';
        return $links;
    }

    public function sync_bulk_actions(array $bulk_actions): array
    {
        $settings = get_option('woocommerce_wc_siigo_integration_settings');

        if((isset($settings['username']) &&
                $settings['access_key']) ||
            (isset($settings['sandbox_username']) &&
                $settings['sandbox_access_key']) &&
            $settings['enabled'] === 'yes'
        ){
            $bulk_actions['integration_siigo_sync'] = 'Sincronizar productos Siigo';
        }
        return $bulk_actions;
    }

    public function sync_bulk_action_edit_product($redirect_to, $action, array $post_ids) :string
    {
        if ($action !== 'integration_siigo_sync') return $redirect_to;

        Integration_Siigo_WC::sync_products_to_siigo($post_ids);

        return $redirect_to;
    }

    public function add_integration($integrations): array
    {
        $integrations[] = 'WC_Siigo_Integration';
        return $integrations;
    }

    /**
     * Log a message to the WooCommerce logger.
     *
     * @param mixed  $message The message to log (string, array, or object).
     * @param string $level   The log level (debug, info, notice, warning, error, critical, alert, emergency).
     * @return void
     */
    public function log(mixed $message, string $level = 'info' ): void
    {
        if ( is_array( $message ) || is_object( $message ) ) {
            $message = wp_json_encode( $message, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
        }

        $this->logger->log(
            $level,
            $message,
            array( 'source' => 'integration-siigo' )
        );
    }

    public function enqueue_scripts(): void
    {
        if ( is_checkout() ) {
            wp_enqueue_script( 'integration-siigo-field-dni', $this->plugin_url . 'assets/js/field-dni-checkout.js', array( 'jquery' ), $this->version, true );
        }
    }

    public function enqueue_scripts_admin($hook): void
    {
        if($hook === 'woocommerce_page_wizard-siigo' ){
            $asset_file = $this->plugin_path . 'assets/build/index.asset.php';

            if ( ! file_exists( $asset_file ) ) {
                return;
            }

            $asset = include $asset_file;

            wp_enqueue_style(
                    'integration-siigo-wizard',
                    $this->assets . 'build/index.css',
                    array(),
                    $asset['version']
            );

            wp_enqueue_script(
                'integration-siigo-wizard',
                $this->assets . 'build/index.js',
                $asset['dependencies'],
                $asset['version'],
                array(
                    'in_footer' => true
                )
            );
        }

        if($this->should_enqueue_premium_survey_assets_on_admin((string) $hook)){
            wp_enqueue_script( 'integration-siigo-sweet-alert', $this->assets. 'js/sweetalert2.min.js', array( 'jquery' ), $this->version, true );
            wp_enqueue_script( 'integration-siigo', $this->assets. 'js/integration-siigo.js', array( 'jquery' ), $this->version, true );
        }
    }

    private function is_siigo_integration_settings_page_request(string $hook): bool
    {
        if ($hook !== 'woocommerce_page_wc-settings') {
            return false;
        }

        $section = isset($_GET['section']) ? sanitize_key(wp_unslash($_GET['section'])) : '';

        return $section === INTEGRATION_SIIGO_WC_SMP_ID;
    }

    private function get_integration_settings(): array
    {
        $settings = get_option('woocommerce_wc_siigo_integration_settings', array());
        return is_array($settings) ? $settings : array();
    }

    private function has_survey_eligible_store(): bool
    {
        $settings = $this->get_integration_settings();

        $is_enabled = isset($settings['enabled']) && $settings['enabled'] === 'yes';
        $has_production_credentials = !empty($settings['username']) && !empty($settings['access_key']);
        $has_sandbox_credentials = !empty($settings['sandbox_username']) && !empty($settings['sandbox_access_key']);

        return $is_enabled && ($has_production_credentials || $has_sandbox_credentials);
    }

    private function is_woocommerce_admin_screen(): bool
    {
        if (!is_admin() || !function_exists('get_current_screen')) {
            return false;
        }

        $screen = get_current_screen();

        if (!$screen) {
            return false;
        }

        if (str_starts_with($screen->id, 'woocommerce_page_')) {
            return true;
        }

        if (!function_exists('wc_get_screen_ids')) {
            return false;
        }

        return in_array($screen->id, wc_get_screen_ids(), true);
    }

    private function is_siigo_integration_settings_screen(): bool
    {
        if (!function_exists('get_current_screen')) {
            return false;
        }

        $screen = get_current_screen();

        if (!$screen || $screen->id !== 'woocommerce_page_wc-settings') {
            return false;
        }

        $tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : '';
        $section = isset($_GET['section']) ? sanitize_key(wp_unslash($_GET['section'])) : '';

        return $tab === 'integration' && $section === INTEGRATION_SIIGO_WC_SMP_ID;
    }

    private function has_user_dismissed_premium_survey_notice(): bool
    {
        if (!is_user_logged_in()) {
            return false;
        }

        $dismissed = get_user_meta(get_current_user_id(), 'integration_siigo_premium_survey_notice_dismissed', true);

        return $dismissed === 'yes';
    }

    private function should_expose_survey_on_admin(): bool
    {
        if (!current_user_can('manage_woocommerce')) {
            return false;
        }

        if (!$this->has_survey_eligible_store()) {
            return false;
        }

        if ($this->has_user_dismissed_premium_survey_notice()) {
            return false;
        }

        return $this->is_woocommerce_admin_screen();
    }

    private function should_enqueue_premium_survey_assets_on_admin(string $hook): bool
    {
        if ($this->is_siigo_integration_settings_page_request($hook)) {
            return true;
        }

        return $this->should_expose_survey_on_admin();
    }

    public function premium_survey_admin_notice(): void
    {
        if (!$this->should_expose_survey_on_admin() || $this->is_siigo_integration_settings_screen()) {
            return;
        }

        $settings_url = admin_url('admin.php?page=wc-settings&tab=integration&section=' . INTEGRATION_SIIGO_WC_SMP_ID);
        $settings_with_survey_url = add_query_arg('open_premium_survey', '1', $settings_url);
        ?>
        <div class="notice notice-info is-dismissible siigo-premium-survey-notice" data-dismiss-nonce="<?php echo esc_attr(wp_create_nonce('integration_siigo_dismiss_premium_survey_notice')); ?>">
            <p>
                <strong>Integration Siigo Woocommerce: ayudanos a priorizar la version premium.</strong>
                Esta encuesta toma menos de 3 minutos y nos ayuda a mejorar este plugin para tu tienda.
            </p>
            <p>
                <button type="button" class="button button-primary siigo-send-premium-survey" data-nonce="<?php echo esc_attr(wp_create_nonce('integration_siigo_send_premium_survey')); ?>">
                    Responder encuesta premium
                </button>
                <a class="button button-secondary" href="<?php echo esc_url($settings_with_survey_url); ?>">Abrir desde configuraciones</a>
            </p>
        </div>
        <?php
    }

    public function ajax_integration_siigo_dismiss_premium_survey_notice(): void
    {
        $nonce = isset($_REQUEST['nonce']) ? sanitize_text_field(wp_unslash($_REQUEST['nonce'])) : '';

        if ( ! wp_verify_nonce($nonce, 'integration_siigo_dismiss_premium_survey_notice') ) {
            wp_send_json(array(
                'status' => false,
                'message' => __('No se pudo validar la solicitud.')
            ));
        }

        if (!is_user_logged_in() || !current_user_can('manage_woocommerce')) {
            wp_send_json(array(
                'status' => false,
                'message' => __('No tienes permisos para esta acción.')
            ));
        }

        update_user_meta(get_current_user_id(), 'integration_siigo_premium_survey_notice_dismissed', 'yes');

        wp_send_json(array('status' => true));
    }

    public function ajax_integration_siigo_sync_products(): void
    {
        if ( ! wp_verify_nonce(  $_REQUEST['nonce'], 'integration_siigo_sync_products' ) )
            return;

        wp_schedule_single_event(time() + 5, 'integration_siigo_wc_smp_schedule');
        wp_send_json(['status' => true]);
    }

    public function ajax_integration_siigo_sync_woo_siigo(): void
    {
        if ( ! wp_verify_nonce(  $_REQUEST['nonce'], 'integration_siigo_sync_woo_siigo' ) )
            return;

        wp_schedule_single_event(time() + 5, 'integration_siigo_wc_smp_schedule_sync_woo_siigo');
        wp_send_json(['status' => true]);
    }

    public function ajax_integration_siigo_sync_webhook(): void
    {
        if ( ! wp_verify_nonce(  $_REQUEST['nonce'], 'integration_siigo_sync_webhook' ) )
            return;

        $status = Integration_Siigo_WC::subscribeWebhook();
        wp_send_json(['status' => $status]);
    }

    public function ajax_integration_siigo_send_premium_survey(): void
    {
        $nonce = isset($_REQUEST['nonce']) ? sanitize_text_field(wp_unslash($_REQUEST['nonce'])) : '';

        if ( ! wp_verify_nonce($nonce, 'integration_siigo_send_premium_survey') ) {
            wp_send_json(array(
                'status' => false,
                'message' => __('No se pudo validar la solicitud. Recarga la pagina e intenta de nuevo.')
            ));
        }

        $q1_score = isset($_POST['q1_score']) ? (int) $_POST['q1_score'] : 0;
        $q2_pain_point = isset($_POST['q2_pain_point']) ? sanitize_text_field(wp_unslash($_POST['q2_pain_point'])) : '';
        $q3_time_loss = isset($_POST['q3_time_loss']) ? sanitize_text_field(wp_unslash($_POST['q3_time_loss'])) : '';
        $q5_most_critical = isset($_POST['q5_most_critical']) ? sanitize_text_field(wp_unslash($_POST['q5_most_critical'])) : '';
        $q6_billing_model = isset($_POST['q6_billing_model']) ? sanitize_text_field(wp_unslash($_POST['q6_billing_model'])) : '';
        $q7_price_range = isset($_POST['q7_price_range']) ? sanitize_text_field(wp_unslash($_POST['q7_price_range'])) : '';
        $q8_open_feedback = isset($_POST['q8_open_feedback']) ? sanitize_textarea_field(wp_unslash($_POST['q8_open_feedback'])) : '';
        $consent_yes_no = isset($_POST['consent_yes_no']) && sanitize_text_field(wp_unslash($_POST['consent_yes_no'])) === 'yes' ? 'yes' : 'no';

        $q4_top_features = isset($_POST['q4_top_features']) ? wp_unslash($_POST['q4_top_features']) : array();

        if ( is_string($q4_top_features) ) {
            $decoded_features = json_decode($q4_top_features, true);
            if ( JSON_ERROR_NONE === json_last_error() && is_array($decoded_features) ) {
                $q4_top_features = $decoded_features;
            } else {
                $q4_top_features = array_filter(array_map('trim', explode(',', $q4_top_features)));
            }
        }

        if ( ! is_array($q4_top_features) ) {
            $q4_top_features = array();
        }

        $q4_top_features = array_slice(
            array_values(
                array_unique(
                    array_filter(
                        array_map(
                            static fn($value): string => sanitize_text_field((string) $value),
                            $q4_top_features
                        )
                    )
                )
            ),
            0,
            3
        );

        if ($q1_score < 1 || $q1_score > 10 || empty($q4_top_features) || '' === $q7_price_range) {
            wp_send_json(array(
                'status' => false,
                'message' => __('Completa satisfaccion (1-10), Top 3 funcionalidades y rango de precio para enviar la encuesta.')
            ));
        }

        $response_id = wp_generate_uuid4();
        $date_time = wp_date('Y-m-d H:i:s');
        $date_time_iso = wp_date('c');

        $site_name = get_bloginfo('name');
        $site_url = home_url();

        $default_country = get_option('woocommerce_default_country', '');
        $country = is_string($default_country) ? explode(':', $default_country)[0] : '';
        $currency = function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : get_option('woocommerce_currency', 'N/A');

        $recipient = apply_filters('integration_siigo_premium_survey_email_recipient', 'moralespachecopablo@gmail.com');
        $recipient = is_array($recipient)
            ? array_filter(array_map('sanitize_email', $recipient))
            : sanitize_email((string) $recipient);

        if (empty($recipient)) {
            $this->log('No premium survey email recipient configured', 'error');
            wp_send_json(array(
                'status' => false,
                'message' => __('No hay correo de destino configurado para la encuesta premium.')
            ));
        }

        $admin_email = sanitize_email((string) get_option('admin_email'));
        $headers = array(
            'Content-Type: text/plain; charset=UTF-8'
        );

        if (!empty($admin_email)) {
            $headers[] = sprintf('Reply-To: %s', $admin_email);
        }

        $subject = sprintf(
            '[Siigo Woo] Respuesta encuesta premium | %s | %s',
            $site_url,
            $date_time
        );

        $message_lines = array(
            sprintf('Response ID: %s', $response_id),
            sprintf('Fecha envio: %s', $date_time_iso),
            sprintf('Sitio: %s', $site_name),
            sprintf('URL tienda: %s', $site_url),
            sprintf('Pais/moneda: %s / %s', $country ?: 'N/A', $currency ?: 'N/A'),
            sprintf('Version plugin: %s', (string) $this->version),
            sprintf('Version WP/WC: %s / %s', get_bloginfo('version'), defined('WC_VERSION') ? WC_VERSION : 'N/A'),
            sprintf('Satisfaccion actual (1-10): %d', $q1_score),
            sprintf('Dolor principal actual: %s', $q2_pain_point ?: 'N/A'),
            sprintf('Tiempo semanal perdido: %s', $q3_time_loss ?: 'N/A'),
            sprintf('Top 3 features premium: %s', implode(', ', $q4_top_features)),
            sprintf('Feature mas critica: %s', $q5_most_critical ?: 'N/A'),
            sprintf('Modelo de cobro preferido: %s', $q6_billing_model ?: 'N/A'),
            sprintf('Rango de precio mensual: %s', $q7_price_range),
            sprintf('Comentario abierto: %s', $q8_open_feedback ?: 'N/A'),
            sprintf('Consentimiento contacto: %s', $consent_yes_no),
        );

        $mail_sent = wp_mail($recipient, $subject, implode("\n", $message_lines), $headers);

        if (!$mail_sent) {
            $this->log(
                array(
                    'event' => 'premium_survey_send_failed',
                    'response_id' => $response_id,
                    'site_url' => $site_url,
                ),
                'error'
            );

            wp_send_json(array(
                'status' => false,
                'message' => __('No fue posible enviar tu respuesta por email. Intenta nuevamente.')
            ));
        }

        wp_send_json(array(
            'status' => true,
            'message' => __('Gracias. Tu respuesta fue enviada correctamente.')
        ));
    }

    public function invoice_column(array $columns): array
    {
        $columns['invoice_siigo'] = 'Factura Siigo';
        return $columns;
    }

    public function content_column_invoice(string $column, $order): void
    {
        if ($column !== 'invoice_siigo') return;

        $invoice_number_siigo = $order->get_meta('_invoice_number_siigo');

        if($invoice_number_siigo) {
            echo $invoice_number_siigo;
        }
    }

    public function register_additional_checkout_fields(): void
    {
        woocommerce_register_additional_checkout_field(
            array(
                'id'       => 'document/type_document',
                'label'    => 'Tipo de documento',
                'location' => 'address',
                'type'     => 'select',
                'required' => true,
                'options'  => [
                    [
                        'value' => 'CC',
                        'label' => 'Cédula de ciudadanía'
                    ],
                    [
                        'value' => 'NIT',
                        'label' => '(NIT) Número de indentificación tributaria'
                    ]
                ]
            )
        );
        woocommerce_register_additional_checkout_field(
            array(
                'id'            => 'document/dni',
                'label'         => 'Número de documento',
                'optionalLabel' => '1055666777',
                'location'      => 'address',
                'required'      => true,
                'attributes'    => array(
                    'autocomplete'     => 'billing_dni',
                    'aria-describedby' => 'some-element',
                    'aria-label'       => 'Número de documento',
                    'pattern'          => '[0-9]{5,12}'
                )
            ),
        );
    }

    public function document_woocommerce_fields(array $fields): array
    {
        $fields['type_document'] = array(
            'label'       => __('Tipo de documento'),
            'placeholder' => _x('', 'placeholder'),
            'required'    => true,
            'clear'       => true,
            'type'        => 'select',
            'default' => 'CC',
            'options'     => array(
                'CC' => __('Cédula de ciudadanía' ),
                'NIT' => __('(NIT) Número de indentificación tributaria')
            ),
            'class' => apply_filters('class_field_type_document', array())
        );

        $fields['dni'] = array(
            'label' => __('Número de documento'),
            'placeholder' => _x('', 'placeholder'),
            'required' => true,
            'clear' => true,
            'type' => 'number',
            'custom_attributes' => array(
                'minlength' => 5
            ),
            'class' => apply_filters('class_field_dni', array())
        );

        return $fields;
    }

    public function document_woocommerce_fields_update_order_meta($order_id): void
    {
        $this->updated_address('billing', $order_id);

        if(!empty($_POST['ship_to_different_address'])) {
            $this->updated_address('shipping', $order_id);
        }
    }

    private function updated_address(string $prefix, $order_id): void
    {
        if (!empty($_POST[ "{$prefix}_type_document" ])) {
            $type_document = sanitize_text_field($_POST[ "{$prefix}_type_document" ]);
            update_post_meta($order_id, "_{$prefix}_type_document", $type_document);
        }

        if (!empty($_POST[ "{$prefix}_dni" ])) {
            $dni = sanitize_text_field($_POST[ "{$prefix}_dni" ]);
            update_post_meta($order_id, "_{$prefix}_dni", $dni);
        }

        if(isset($dni) && isset($type_document) && $type_document === 'NIT'){
            $dv = Integration_Siigo_WC::calculateDv($dni);
            $dni = "$dni-$dv";
            update_post_meta($order_id, "_{$prefix}_dni", $dni);
        }
    }

    public function display_custom_editable_field_on_admin_orders(WC_Order $order ): void
    {

        ?>
        <br class="clear" />
        <div class="example_data_wrapper">
            <?php
            woocommerce_wp_select(array(
                'id' => '_billing_type_document',
                'value' => get_post_meta($order->get_id(), '_billing_type_document', true),
                'label' => __('Tipo de documento'),
                'options' => [
                    'CC' => 'Cédula de ciudadanía',
                    'NIT' => '(NIT) Número de identificación tributaria'
                ],
                'wrapper_class' => 'wc-enhanced-select'
            ));

            woocommerce_wp_text_input( array(
                'id' => '_billing_dni',
                'value' => get_post_meta($order->get_id(), '_billing_dni', true),
                'label' => __('Número de documento:'),
                'wrapper_class' => 'form-field-wide'
            ) );
            ?>
        </div>
        <?php
    }

    public function save_order_custom_field_meta( $order_id ): void
    {

        if ( isset($_POST['_billing_type_document']) ){
            update_post_meta($order_id, '_billing_type_document', sanitize_text_field($_POST['_billing_type_document']));
        }
        if ( isset($_POST['_billing_dni']) ){
            update_post_meta($order_id, '_billing_dni', sanitize_text_field($_POST['_billing_dni']));
        }
    }
}