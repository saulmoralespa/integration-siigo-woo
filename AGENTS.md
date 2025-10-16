# AGENTS Documentation

## Overview
This document provides comprehensive documentation for the Integration Siigo WooCommerce plugin. It includes details about the project structure, coding standards, testing practices, and development workflow.

---

## Project Structure

```
integration-siigo-woo/
├── includes/               # Core plugin classes
│   ├── class-integration-siigo-wc-plugin.php
│   ├── class-integration-siigo-wc.php
│   ├── class-siigo-integration-wc.php
│   ├── class-integration-siigo-wc-admin.php
│   └── admin/             # Admin settings
├── assets/                # Frontend assets
│   ├── js/               # JavaScript files
│   └── build/            # Built assets
├── lib/                   # External libraries
│   └── vendor/           # Composer dependencies
├── tests/                 # PHPUnit tests
│   ├── bootstrap.php
│   ├── wp-config.php
│   ├── test-plugin.php
│   ├── test-integration-siigo-wc.php
│   ├── test-siigo-integration.php
│   ├── test-ajax-functions.php
│   ├── test-checkout-fields.php
│   ├── test-product-sync.php
│   └── test-webhook.php
└── integration-siigo-woo.php  # Main plugin file
```

---

## Coding Standards

The plugin strictly adheres to the following coding standards:

### 1. WordPress Coding Standards
- Follows [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)
- Uses WordPress naming conventions for files, classes, functions, and hooks
- Implements WordPress Security best practices (nonces, sanitization, escaping)

### 2. PHP Standards
- **PHP Version**: Requires PHP 8.1+
- **PSR-12 Compatible**: Follows PSR-12 where it doesn't conflict with WordPress standards
- **Type Declarations**: Uses strict typing with PHP 8.1+ features
  - Nullable types: `?string`, `?int`
  - Typed properties: `public string $property`
  - Union types where appropriate
  - Return type declarations

### 3. Naming Conventions
- **Classes**: `Class_Name_With_Underscores`
- **Functions**: `function_name_with_underscores()`
- **Variables**: `$variable_name_with_underscores`
- **Constants**: `CONSTANT_NAME_IN_CAPS`
- **Hooks**: `prefix_hook_name`

### 4. Documentation
- All classes must have DocBlocks
- All public/protected methods must have DocBlocks
- Use `@package`, `@since`, `@param`, `@return` tags
- Follow WordPress DocBlock standards

### 5. File Organization
- One class per file
- File names match class names with `class-` prefix
- Test files use `test-` prefix

---

## Testing

El plugin incluye una suite completa de pruebas unitarias siguiendo los estándares de WordPress.

### Estructura de Pruebas

```
tests/
├── bootstrap.php                    # Configuración inicial de pruebas
├── wp-config.php                   # Configuración de WordPress para pruebas
├── test-plugin.php                 # Pruebas de la clase principal
├── test-integration-siigo-wc.php   # Pruebas de integración con Siigo
├── test-siigo-integration.php      # Pruebas de WC_Integration
├── test-ajax-functions.php         # Pruebas de funciones AJAX
├── test-checkout-fields.php        # Pruebas de campos del checkout
├── test-product-sync.php           # Pruebas de sincronización de productos
└── test-webhook.php                # Pruebas de webhooks
```

### Convención de Nombres de Pruebas (WordPress Standard)

```php
<?php
/**
 * Tests for My_Class
 *
 * @package Integration_Siigo_WC
 */
class Test_My_Class extends WP_UnitTestCase {
    
    public function test_method_does_something() {
        // Arrange
        $expected = 'value';
        
        // Act
        $result = my_function();
        
        // Assert
        $this->assertEquals( $expected, $result );
    }
}
```

### Características de las Pruebas

- **Framework**: PHPUnit 9.6+
- **Base Class**: `WP_UnitTestCase`
- **Naming**: `Test_Class_Name` para clases, `test_method_name` para métodos
- **File Naming**: `test-feature-name.php`
- **No Namespaces**: Siguiendo el estándar de WordPress
- **Database**: Tests usan una base de datos temporal
- **Fixtures**: `setUp()` y `tearDown()` para preparar/limpiar datos

## Development Workflow

### 1. Setup

```bash
git clone https://github.com/saulmoralespa/integration-siigo-woo
cd integration-siigo-woo
composer install
npm install
```

### 2. Development

```bash
# Watch mode para desarrollo
npm run watch

# Ejecutar pruebas mientras desarrollas
make test-watch
```

### 3. Before Commit

```bash
# Verificar código
make lint

# Ejecutar pruebas
make test

# Fix automático de estándares
make fix
```

### 4. Commit Standards

```bash
# Formato de commits
git commit -m "tipo: descripción corta

Descripción detallada del cambio"

# Tipos: feat, fix, docs, style, refactor, test, chore
```

---

## Best Practices

### Security
- Siempre sanitizar inputs: `sanitize_text_field()`, `sanitize_email()`
- Siempre escapar outputs: `esc_html()`, `esc_attr()`, `esc_url()`
- Usar nonces para formularios: `wp_nonce_field()`, `wp_verify_nonce()`
- Validar permisos: `current_user_can()`

### Performance
- Usar transients para cachear datos de API
- Optimizar queries de base de datos
- Lazy load de assets
- Minimizar requests HTTP

### Compatibility
- Testear con múltiples versiones de PHP (8.1, 8.2, 8.3)
- Testear con múltiples versiones de WordPress
- Testear con múltiples versiones de WooCommerce
- Considerar compatibilidad con otros plugins

---

## Resources

- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
- [WooCommerce Documentation](https://woocommerce.com/documentation/)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- [Siigo API Documentation](https://siigoapi.docs.apiary.io/)

---

## Support

Para soporte técnico o preguntas:
- GitHub Issues: https://github.com/saulmoralespa/integration-siigo-woo/issues
- Email: info@saulmoralespa.com
- Documentation: https://github.com/saulmoralespa/integration-siigo-woo

---

## Contributors

Mantainer: Saúl Morales Pacheco

Contributions are welcome! Please read the contributing guidelines before submitting pull requests.

---

## License

GNU General Public License v3.0 - See LICENSE file for details.
