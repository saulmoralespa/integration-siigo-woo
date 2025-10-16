<?php
/**
 * Tests for WC_Siigo_Integration class
 *
 * @package Integration_Siigo_WC
 */

class Test_WC_Siigo_Integration extends WP_UnitTestCase {

    private $integration;

    public function setUp(): void {
        parent::setUp();
        
        if ( ! class_exists( 'WC_Siigo_Integration' ) ) {
            require_once dirname( __DIR__ ) . '/includes/class-siigo-integration-wc.php';
        }
        
        $this->integration = new WC_Siigo_Integration();
    }

    public function test_integration_id() {
        $this->assertEquals( INTEGRATION_SIIGO_WC_SMP_ID, $this->integration->id );
    }

    public function test_integration_has_form_fields() {
        $this->integration->init_form_fields();
        
        $this->assertIsArray( $this->integration->form_fields );
        $this->assertNotEmpty( $this->integration->form_fields );
    }

    public function test_sandbox_mode_uses_sandbox_credentials() {
        update_option(
            'woocommerce_wc_siigo_integration_settings',
            array(
                'environment'         => '1',
                'sandbox_username'    => 'test_sandbox_user',
                'sandbox_access_key'  => 'test_sandbox_key',
                'username'            => 'prod_user',
                'access_key'          => 'prod_key',
            )
        );
        
        $integration = new WC_Siigo_Integration();
        
        $this->assertTrue( $integration->isTest );
        $this->assertEquals( 'test_sandbox_user', $integration->username );
        $this->assertEquals( 'test_sandbox_key', $integration->access_key );
    }

    public function test_production_mode_uses_production_credentials() {
        update_option(
            'woocommerce_wc_siigo_integration_settings',
            array(
                'environment'         => '0',
                'sandbox_username'    => 'test_sandbox_user',
                'sandbox_access_key'  => 'test_sandbox_key',
                'username'            => 'prod_user',
                'access_key'          => 'prod_key',
            )
        );
        
        $integration = new WC_Siigo_Integration();
        
        $this->assertFalse( $integration->isTest );
        $this->assertEquals( 'prod_user', $integration->username );
        $this->assertEquals( 'prod_key', $integration->access_key );
    }

    public function tearDown(): void {
        delete_option( 'woocommerce_wc_siigo_integration_settings' );
        parent::tearDown();
    }
}

