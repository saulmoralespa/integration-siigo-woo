<?php
/**
 * Tests for checkout fields
 *
 * @package Integration_Siigo_WC
 */

class Test_Checkout_Fields extends WP_UnitTestCase {

    private $plugin;

    public function setUp(): void {
        parent::setUp();
        $this->plugin = new Integration_Siigo_WC_Plugin( __FILE__, INTEGRATION_SIIGO_WC_SMP_VERSION );
    }

    public function test_document_woocommerce_fields_adds_custom_fields() {
        $fields = array();
        $result = $this->plugin->document_woocommerce_fields( $fields );

        $this->assertArrayHasKey( 'type_document', $result );
        $this->assertArrayHasKey( 'dni', $result );

        $this->assertIsArray( $result['type_document'] );
        $this->assertIsArray( $result['dni'] );
    }

    public function test_type_document_field_has_options() {
        $fields = array();
        $result = $this->plugin->document_woocommerce_fields( $fields );

        $this->assertArrayHasKey( 'options', $result['type_document'] );
        $this->assertIsArray( $result['type_document']['options'] );
        $this->assertNotEmpty( $result['type_document']['options'] );
    }

    public function test_document_update_order_meta() {
        if ( ! function_exists( 'wc_create_order' ) ) {
            $this->markTestSkipped( 'WooCommerce not available' );
            return;
        }

        $order    = wc_create_order();
        $order_id = $order->save();

        $_POST['billing_type_document'] = '13';
        $_POST['billing_dni']            = '123456789';

        $this->plugin->document_woocommerce_fields_update_order_meta( $order_id );

        $saved_type = get_post_meta( $order_id, '_billing_type_document', true );
        $saved_dni  = get_post_meta( $order_id, '_billing_dni', true );

        $this->assertEquals( '13', $saved_type );
        $this->assertEquals( '123456789', $saved_dni );

        wp_delete_post( $order_id, true );
    }

    public function tearDown(): void {
        unset( $_POST['billing_type_document'] );
        unset( $_POST['billing_dni'] );
        parent::tearDown();
    }
}

