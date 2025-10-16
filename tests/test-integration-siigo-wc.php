<?php
/**
 * Tests for Integration_Siigo_WC class
 *
 * @package Integration_Siigo_WC
 */

class Test_Integration_Siigo_WC extends WP_UnitTestCase {

    public function setUp(): void {
        parent::setUp();
        
        if ( ! class_exists( 'Integration_Siigo_WC' ) ) {
            require_once dirname( __DIR__ ) . '/includes/class-integration-siigo-wc.php';
        }
    }

    public function test_get_instance_returns_null_when_disabled() {
        update_option( 'woocommerce_wc_siigo_integration_settings', array( 'enabled' => 'no' ) );
        
        $result = Integration_Siigo_WC::get_instance();
        
        $this->assertNull( $result );
    }

    public function test_sku_shipping_constant() {
        $this->assertEquals( 'S-P-W', Integration_Siigo_WC::SKU_SHIPPING );
    }

    public function tearDown(): void {
        delete_option( 'woocommerce_wc_siigo_integration_settings' );
        parent::tearDown();
    }
}

