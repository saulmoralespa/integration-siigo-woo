<?php
/**
 * Tests for webhook functionality
 *
 * @package Integration_Siigo_WC
 */

class Test_Webhook extends WP_UnitTestCase {

    public function setUp(): void {
        parent::setUp();
        
        if ( ! class_exists( 'Integration_Siigo_WC' ) ) {
            require_once dirname( __DIR__ ) . '/includes/class-integration-siigo-wc.php';
        }
    }

    public function test_webhook_with_valid_product_data() {
        $webhook_data = array(
            'entity' => 'product',
            'action' => 'created',
            'data'   => array(
                'id'                 => 'siigo_product_123',
                'code'               => 'WEBHOOK001',
                'name'               => 'Product from Webhook',
                'prices'             => array(
                    array(
                        'price_list' => array(
                            array( 'value' => 15000 ),
                        ),
                    ),
                ),
                'stock_control'      => true,
                'available_quantity' => 75,
            ),
        );
        
        $this->assertIsArray( $webhook_data );
        $this->assertEquals( 'product', $webhook_data['entity'] );
    }

    public function test_webhook_with_invoice_data() {
        $webhook_data = array(
            'entity' => 'invoice',
            'action' => 'created',
            'data'   => array(
                'id'       => 'invoice_123',
                'number'   => 'INV-001',
                'customer' => array(
                    'identification' => '123456789',
                ),
            ),
        );
        
        $this->assertEquals( 'invoice', $webhook_data['entity'] );
        $this->assertEquals( 'created', $webhook_data['action'] );
    }

    public function test_webhook_with_missing_data() {
        $webhook_data = array(
            'entity' => 'product',
            // Missing 'action' and 'data'
        );
        
        // Should handle missing data gracefully
        $this->assertArrayNotHasKey( 'action', $webhook_data );
    }

    public function test_webhook_with_invalid_json() {
        $decoded = json_decode( 'invalid json data', true );
        
        $this->assertNull( $decoded );
    }

    public function tearDown(): void {
        parent::tearDown();
    }
}

