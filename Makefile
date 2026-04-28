# Variables de configuración
WP_TEST__DIR := ../../wp-tests
TEST_UNIT    := ${WP_TEST__DIR}/vendor/bin/phpunit
TESTS_DIR    := tests

# Colores para output
GREEN := \033[0;32m
YELLOW := \033[1;33m
NC := \033[0m # No Color

.PHONY: test test-plugin test-integration test-siigo-integration test-checkout test-product-sync test-webhook test-survey test-all help

# Ayuda
help:
	@echo "$(YELLOW)Comandos disponibles:$(NC)"
	@echo "  $(GREEN)make test$(NC)                 - Ejecutar todos los tests"
	@echo "  $(GREEN)make test-plugin$(NC)          - Tests de Integration_Siigo_WC_Plugin"
	@echo "  $(GREEN)make test-integration$(NC)     - Tests de Integration_Siigo_WC"
	@echo "  $(GREEN)make test-siigo-integration$(NC) - Tests de WC_Siigo_Integration"
	@echo "  $(GREEN)make test-checkout$(NC)        - Tests de campos del checkout"
	@echo "  $(GREEN)make test-product-sync$(NC)    - Tests de sincronización de productos"
	@echo "  $(GREEN)make test-webhook$(NC)         - Tests de webhook"
	@echo "  $(GREEN)make test-survey$(NC)          - Tests de Premium Survey"
	@echo "  $(GREEN)make test-all$(NC)             - Todos los tests con detalles"

# Ejecutar todos los tests
test:
	@echo "$(YELLOW)Ejecutando todos los tests...$(NC)"
	WP_TEST__DIR=${WP_TEST__DIR} ${TEST_UNIT} --testdox --colors=always

# Tests de Integration_Siigo_WC_Plugin
test-plugin:
	@echo "$(YELLOW)Ejecutando tests de Integration_Siigo_WC_Plugin...$(NC)"
	WP_TEST__DIR=${WP_TEST__DIR} ${TEST_UNIT} --filter Test_Integration_Siigo_WC_Plugin --testdox --colors=always

# Tests de Integration_Siigo_WC
test-integration:
	@echo "$(YELLOW)Ejecutando tests de Integration_Siigo_WC...$(NC)"
	WP_TEST__DIR=${WP_TEST__DIR} ${TEST_UNIT} --filter Test_Integration_Siigo_WC --testdox --colors=always

# Tests de WC_Siigo_Integration
test-siigo-integration:
	@echo "$(YELLOW)Ejecutando tests de WC_Siigo_Integration...$(NC)"
	WP_TEST__DIR=${WP_TEST__DIR} ${TEST_UNIT} --filter Test_WC_Siigo_Integration --testdox --colors=always

# Tests de campos del checkout
test-checkout:
	@echo "$(YELLOW)Ejecutando tests de campos del checkout...$(NC)"
	WP_TEST__DIR=${WP_TEST__DIR} ${TEST_UNIT} --filter Test_Checkout_Fields --testdox --colors=always

# Tests de sincronización de productos
test-product-sync:
	@echo "$(YELLOW)Ejecutando tests de sincronización de productos...$(NC)"
	WP_TEST__DIR=${WP_TEST__DIR} ${TEST_UNIT} --filter Test_Product_Sync --testdox --colors=always

# Tests de webhook
test-webhook:
	@echo "$(YELLOW)Ejecutando tests de webhook...$(NC)"
	WP_TEST__DIR=${WP_TEST__DIR} ${TEST_UNIT} --filter Test_Webhook --testdox --colors=always

# Tests de Premium Survey
test-survey:
	@echo "$(YELLOW)Ejecutando tests de Premium Survey...$(NC)"
	WP_TEST__DIR=${WP_TEST__DIR} ${TEST_UNIT} --filter Test_Premium_Survey --testdox --colors=always

# Todos los tests con detalles
test-all:
	@echo "$(YELLOW)Ejecutando todos los tests con detalles...$(NC)"
	WP_TEST__DIR=${WP_TEST__DIR} ${TEST_UNIT} --colors=always
