<?php
/**
 * Tests for Integration_Siigo_WC_Plugin class
 *
 * @package Integration_Siigo_WC
 */

class Test_Integration_Siigo_WC_Plugin extends WP_UnitTestCase {

    private $plugin;

    public function setUp(): void {
        parent::setUp();
        $this->plugin = new Integration_Siigo_WC_Plugin( __FILE__, INTEGRATION_SIIGO_WC_SMP_VERSION );
    }

    public function test_plugin_initialization() {
        $this->assertInstanceOf( Integration_Siigo_WC_Plugin::class, $this->plugin );
        $this->assertNotEmpty( $this->plugin->plugin_path );
        $this->assertNotEmpty( $this->plugin->plugin_url );
        $this->assertNotEmpty( $this->plugin->includes_path );
        $this->assertNotEmpty( $this->plugin->lib_path );
    }

    public function test_plugin_paths_are_correct() {
        $this->assertStringContainsString( 'integration-siigo-woo', $this->plugin->plugin_path );
        $this->assertStringContainsString( 'includes', $this->plugin->includes_path );
        $this->assertStringContainsString( 'lib', $this->plugin->lib_path );
        $this->assertStringContainsString( 'assets', $this->plugin->assets );
    }

    public function test_plugin_namespace() {
        $this->assertEquals( 'wcsiigointegration/v1', $this->plugin->namespace );
    }

    public function tearDown(): void {
        parent::tearDown();
    }
}

