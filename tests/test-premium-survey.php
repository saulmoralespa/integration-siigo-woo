<?php
/**
 * Class Test_Premium_Survey
 *
 * @package Integration_Siigo_WC
 */

/**
 * Tests de encuesta premium (Premium Survey)
 *
 * Prueba la lógica de validación del payload de la encuesta premium para
 * Integration Siigo WooCommerce. Los métodos privados se acceden vía
 * ReflectionClass.
 *
 * @since 0.3.7
 */
class Test_Premium_Survey extends WP_UnitTestCase {

    /** @var Integration_Siigo_WC_Plugin */
    private Integration_Siigo_WC_Plugin $plugin;

    /** @var ReflectionClass */
    private ReflectionClass $reflection;

    /**
     * Configuración inicial para cada test.
     */
    public function setUp(): void {
        parent::setUp();

        delete_option( 'woocommerce_wc_siigo_integration_settings' );

        $user_id = get_current_user_id();
        if ( $user_id ) {
            delete_user_meta( $user_id, 'integration_siigo_premium_survey_notice_dismissed' );
        }

        $this->plugin     = integration_siigo_wc_smp();
        $this->reflection = new ReflectionClass( Integration_Siigo_WC_Plugin::class );
    }

    /**
     * Limpieza después de cada test.
     */
    public function tearDown(): void {
        parent::tearDown();

        delete_option( 'woocommerce_wc_siigo_integration_settings' );

        $user_id = get_current_user_id();
        if ( $user_id ) {
            delete_user_meta( $user_id, 'integration_siigo_premium_survey_notice_dismissed' );
        }
    }

    // -------------------------------------------------------------------------
    // Helper de reflexión
    // -------------------------------------------------------------------------

    /**
     * Invoca un método privado/protegido vía ReflectionClass.
     *
     * @param string $method Nombre del método.
     * @param array  $args   Argumentos a pasar al método.
     * @return mixed
     */
    private function call_private( string $method, array $args = [] ): mixed {
        $m = $this->reflection->getMethod( $method );
        $m->setAccessible( true );
        return $m->invokeArgs( $this->plugin, $args );
    }

    // -------------------------------------------------------------------------
    // Fixtures
    // -------------------------------------------------------------------------

    /**
     * Arma un payload válido completo.
     * Permite sobreescribir claves individuales para casos negativos.
     *
     * @param array $overrides Claves a sobreescribir.
     * @return array
     */
    private function valid_survey_payload( array $overrides = [] ): array {
        return array_merge( [
            'q1_score'        => 9,
            'q1_motivo'       => '',
            'q2_pain_point'   => 'facturacion',
            'q3_time_loss'    => '1_3h',
            'q4_top_features' => [ 'inventario_bidireccional', 'devoluciones_notas_credito', 'reportes_alertas' ],
            'q5_most_critical'=> '',
            'q6_billing_model'=> 'mensual',
            'q7_price_range'  => '50000_99000',
            'q8_open_feedback'=> '',
            'consent_yes_no'  => 'no',
        ], $overrides );
    }

    // =========================================================================
    // Grupo 1: rango de q1_score (1-10)
    // =========================================================================

    /**
     * Test 1: q1_score = 0 (por debajo del rango) → inválido.
     */
    public function test_validate_survey_payload_rejects_q1_zero() {
        $payload = $this->valid_survey_payload( [ 'q1_score' => 0 ] );

        $result = $this->call_private( 'validate_survey_payload', [ $payload ] );

        $this->assertFalse(
            $result['valid'],
            'q1_score = 0 debe ser rechazado'
        );
        $this->assertArrayHasKey( 'error', $result );
    }

    /**
     * Test 2: q1_score = 11 (por encima del rango) → inválido.
     */
    public function test_validate_survey_payload_rejects_q1_above_ten() {
        $payload = $this->valid_survey_payload( [ 'q1_score' => 11 ] );

        $result = $this->call_private( 'validate_survey_payload', [ $payload ] );

        $this->assertFalse(
            $result['valid'],
            'q1_score = 11 debe ser rechazado'
        );
    }

    /**
     * Test 3: q1_score = 1 (límite inferior) → válido con motivo diligenciado.
     */
    public function test_validate_survey_payload_accepts_q1_at_lower_bound() {
        $payload = $this->valid_survey_payload( [
            'q1_score'  => 1,
            'q1_motivo' => 'No funciona la sincronizacion de facturas',
        ] );

        $result = $this->call_private( 'validate_survey_payload', [ $payload ] );

        $this->assertTrue(
            $result['valid'],
            'q1_score = 1 con motivo debe ser válido'
        );
    }

    /**
     * Test 4: q1_score = 10 (límite superior) → válido sin motivo.
     */
    public function test_validate_survey_payload_accepts_q1_at_upper_bound() {
        $payload = $this->valid_survey_payload( [ 'q1_score' => 10 ] );

        $result = $this->call_private( 'validate_survey_payload', [ $payload ] );

        $this->assertTrue(
            $result['valid'],
            'q1_score = 10 sin motivo debe ser válido'
        );
    }

    // =========================================================================
    // Grupo 2: regla < 8 requiere q1_motivo
    // =========================================================================

    /**
     * Test 5: q1_score < 8 y motivo vacío → inválido.
     */
    public function test_validate_survey_payload_requires_motivo_when_q1_less_than_8() {
        $payload = $this->valid_survey_payload( [
            'q1_score'  => 5,
            'q1_motivo' => '',
        ] );

        $result = $this->call_private( 'validate_survey_payload', [ $payload ] );

        $this->assertFalse(
            $result['valid'],
            'Con q1_score < 8 y motivo vacío el payload debe ser inválido'
        );
        $this->assertArrayHasKey( 'error', $result );
    }

    /**
     * Test 6: q1_score = 7 (frontera) y motivo vacío → inválido.
     */
    public function test_validate_survey_payload_requires_motivo_at_score_7() {
        $payload = $this->valid_survey_payload( [
            'q1_score'  => 7,
            'q1_motivo' => '',
        ] );

        $result = $this->call_private( 'validate_survey_payload', [ $payload ] );

        $this->assertFalse(
            $result['valid'],
            'q1_score = 7 con motivo vacío debe ser inválido'
        );
    }

    /**
     * Test 7: q1_score < 8 y motivo diligenciado → válido.
     */
    public function test_validate_survey_payload_accepts_motivo_when_q1_less_than_8() {
        $payload = $this->valid_survey_payload( [
            'q1_score'  => 6,
            'q1_motivo' => 'El inventario no se sincroniza con Siigo en tiempo real',
        ] );

        $result = $this->call_private( 'validate_survey_payload', [ $payload ] );

        $this->assertTrue(
            $result['valid'],
            'Con q1_score < 8 y motivo diligenciado el payload debe ser válido'
        );
        $this->assertSame(
            6,
            $result['data']['q1_score']
        );
        $this->assertNotEmpty( $result['data']['q1_motivo'] );
    }

    /**
     * Test 8: q1_score = 8 (frontera) y motivo vacío → válido (motivo no requerido).
     */
    public function test_validate_survey_payload_does_not_require_motivo_when_q1_equals_8() {
        $payload = $this->valid_survey_payload( [
            'q1_score'  => 8,
            'q1_motivo' => '',
        ] );

        $result = $this->call_private( 'validate_survey_payload', [ $payload ] );

        $this->assertTrue(
            $result['valid'],
            'q1_score = 8 no debe requerir motivo'
        );
    }

    /**
     * Test 9: q1_score > 8 y motivo vacío → válido.
     */
    public function test_validate_survey_payload_does_not_require_motivo_when_q1_above_8() {
        $payload = $this->valid_survey_payload( [
            'q1_score'  => 9,
            'q1_motivo' => '',
        ] );

        $result = $this->call_private( 'validate_survey_payload', [ $payload ] );

        $this->assertTrue(
            $result['valid'],
            'Con q1_score > 8 el motivo no debe ser requerido'
        );
    }

    // =========================================================================
    // Grupo 3: funcionalidades (q4_top_features)
    // =========================================================================

    /**
     * Test 10: Sin funcionalidades seleccionadas → inválido.
     */
    public function test_validate_survey_payload_requires_at_least_one_feature() {
        $payload = $this->valid_survey_payload( [ 'q4_top_features' => [] ] );

        $result = $this->call_private( 'validate_survey_payload', [ $payload ] );

        $this->assertFalse(
            $result['valid'],
            'Sin funcionalidades el payload debe ser inválido'
        );
    }

    /**
     * Test 11: Más de 3 funcionalidades → se truncan a 3 y el payload es válido.
     */
    public function test_validate_survey_payload_truncates_features_to_three() {
        $payload = $this->valid_survey_payload( [
            'q4_top_features' => [
                'inventario_bidireccional',
                'devoluciones_notas_credito',
                'reportes_alertas',
                'reglas_sync',
                'soporte_prioritario',
            ],
        ] );

        $result = $this->call_private( 'validate_survey_payload', [ $payload ] );

        $this->assertTrue(
            $result['valid'],
            'Con más de 3 funcionalidades el payload debe ser válido después de truncar'
        );
        $this->assertCount(
            3,
            $result['data']['q4_top_features'],
            'Deben quedar exactamente 3 funcionalidades'
        );
    }

    /**
     * Test 12: q4_top_features como string JSON → se parsea correctamente.
     */
    public function test_validate_survey_payload_parses_features_from_json_string() {
        $payload = $this->valid_survey_payload( [
            'q4_top_features' => json_encode( [ 'inventario_bidireccional', 'soporte_prioritario' ] ),
        ] );

        $result = $this->call_private( 'validate_survey_payload', [ $payload ] );

        $this->assertTrue( $result['valid'], 'Debe parsear q4_top_features desde string JSON' );
        $this->assertCount( 2, $result['data']['q4_top_features'] );
    }

    /**
     * Test 13: q4_top_features como string CSV → se parsea correctamente.
     */
    public function test_validate_survey_payload_parses_features_from_csv_string() {
        $payload = $this->valid_survey_payload( [
            'q4_top_features' => 'inventario_bidireccional, soporte_prioritario',
        ] );

        $result = $this->call_private( 'validate_survey_payload', [ $payload ] );

        $this->assertTrue( $result['valid'], 'Debe parsear q4_top_features desde string CSV' );
        $this->assertCount( 2, $result['data']['q4_top_features'] );
    }

    // =========================================================================
    // Grupo 4: precio requerido (q7_price_range)
    // =========================================================================

    /**
     * Test 14: Sin rango de precio → inválido.
     */
    public function test_validate_survey_payload_requires_price_range() {
        $payload = $this->valid_survey_payload( [ 'q7_price_range' => '' ] );

        $result = $this->call_private( 'validate_survey_payload', [ $payload ] );

        $this->assertFalse(
            $result['valid'],
            'Sin rango de precio el payload debe ser inválido'
        );
    }

    // =========================================================================
    // Grupo 5: payload completo y sanitización
    // =========================================================================

    /**
     * Test 15: Payload completamente válido → retorna valid = true con datos sanitizados.
     */
    public function test_validate_survey_payload_returns_valid_for_complete_payload() {
        $payload = $this->valid_survey_payload();

        $result = $this->call_private( 'validate_survey_payload', [ $payload ] );

        $this->assertTrue(
            $result['valid'],
            'Un payload completo válido debe retornar valid = true'
        );
        $this->assertArrayHasKey( 'data', $result );
        $this->assertSame( 9, $result['data']['q1_score'] );
    }

    /**
     * Test 16: q1_motivo excesivamente largo se trunca a 500 chars.
     */
    public function test_validate_survey_payload_sanitizes_motivo_length() {
        $long_motivo = str_repeat( 'a', 700 );
        $payload     = $this->valid_survey_payload( [
            'q1_score'  => 3,
            'q1_motivo' => $long_motivo,
        ] );

        $result = $this->call_private( 'validate_survey_payload', [ $payload ] );

        $this->assertTrue( $result['valid'] );
        $this->assertLessThanOrEqual(
            500,
            strlen( $result['data']['q1_motivo'] ),
            'El motivo debe truncarse a máximo 500 caracteres'
        );
    }

    /**
     * Test 17: motivo con solo espacios en blanco se trata como vacío → inválido cuando q1 < 8.
     */
    public function test_validate_survey_payload_treats_whitespace_motivo_as_empty() {
        $payload = $this->valid_survey_payload( [
            'q1_score'  => 4,
            'q1_motivo' => '   ',
        ] );

        $result = $this->call_private( 'validate_survey_payload', [ $payload ] );

        $this->assertFalse(
            $result['valid'],
            'Un motivo con solo espacios debe tratarse como vacío cuando q1_score < 8'
        );
    }

    /**
     * Test 18: datos de retorno incluyen q1_motivo en el array data.
     */
    public function test_validate_survey_payload_data_contains_q1_motivo_key() {
        $payload = $this->valid_survey_payload( [
            'q1_score'  => 5,
            'q1_motivo' => 'La facturacion falla con clientes extranjeros',
        ] );

        $result = $this->call_private( 'validate_survey_payload', [ $payload ] );

        $this->assertTrue( $result['valid'] );
        $this->assertArrayHasKey( 'q1_motivo', $result['data'] );
        $this->assertSame(
            'La facturacion falla con clientes extranjeros',
            $result['data']['q1_motivo']
        );
    }
}
