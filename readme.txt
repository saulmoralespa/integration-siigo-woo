=== Integration Siigo Woocommerce ===
Contributors: saulmorales
Donate link: https://saulmoralespa.com/donation
Tags: siigo, woocommerce, facturacion, contabilidad, colombia, invoice, accounting, integration
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 8.1
Stable tag: 0.2.0
WC requires at least: 9.6
WC tested up to: 9.7
License: GNU General Public License v3.0
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Integración completa de Siigo (sistema contable y de facturación) con WooCommerce para Colombia.

== Description ==

Integration Siigo Woocommerce es un plugin que permite integrar tu tienda WooCommerce con Siigo, el sistema contable y de facturación líder en Colombia. Esta integración automatiza procesos de facturación, sincronización de productos e inventario entre tu tienda online y tu sistema contable.

= Características principales =

* **Sincronización automática de productos**: Sincroniza productos desde Siigo a WooCommerce con precios, stock y descripciones
* **Control de inventario**: Actualiza automáticamente el stock de productos basado en la disponibilidad en Siigo
* **Facturación automática**: Genera facturas en Siigo cuando se completa una orden en WooCommerce
* **Gestión de clientes**: Sincroniza información de clientes entre WooCommerce y Siigo
* **Campo DNI en checkout**: Añade campo de documento de identidad requerido para facturación
* **Integración con ciudades y departamentos de Colombia**: Compatible con el plugin de departamentos y ciudades
* **Soporte para múltiples tipos de documento**: Cédula, NIT, pasaporte, etc.
* **Soporte sandbox y producción**: Prueba la integración en ambiente de pruebas antes de ir a producción
* **Sincronización programada**: Ejecuta sincronización automática diaria de productos
* **Compatible con WooCommerce Blocks**: Funciona con el nuevo sistema de checkout basado en bloques
* **Registro de errores (logs)**: Sistema de depuración para rastrear problemas

= Requisitos =

* WordPress 6.0 o superior
* WooCommerce 9.6 o superior
* PHP 8.1 o superior
* Plugin: Departamentos y Ciudades de Colombia para WooCommerce
* Cuenta activa en Siigo (Colombia)
* Credenciales API de Siigo (Username y Access Key)

= Compatibilidad =

* Compatible con WooCommerce High-Performance Order Storage (HPOS)
* Compatible con WooCommerce Blocks
* Optimizado para tiendas en Colombia

== Installation ==

= Instalación manual =

1. Descarga el archivo ZIP del plugin
2. Ve a WordPress > Plugins > Añadir nuevo > Subir plugin
3. Selecciona el archivo ZIP y haz clic en "Instalar ahora"
4. Activa el plugin

= Configuración inicial =

1. Asegúrate de tener instalado y activado WooCommerce
2. Instala y activa el plugin "Departamentos y Ciudades de Colombia para WooCommerce"
3. Ve a WooCommerce > Ajustes > Integraciones > Integration Siigo Woocommerce
4. Habilita la integración
5. Selecciona el ambiente (Sandbox para pruebas o Producción)
6. Ingresa tus credenciales de Siigo:
   - Username
   - Access Key
7. Haz clic en "Probar conexión" para verificar las credenciales
8. Configura los ajustes adicionales:
   - Grupo de cuenta
   - Impuesto
   - Tipo de documento
   - Método de pago
   - Bodega (warehouse)
   - Stock mínimo
9. Guarda los cambios

= Configuración de sincronización =

La sincronización automática de productos se ejecuta diariamente a las 5:00 AM. Los productos se sincronizan desde Siigo hacia WooCommerce, actualizando:

* Nombres de productos
* SKUs
* Precios
* Precios de oferta
* Descripciones
* Disponibilidad de stock
* Cantidades disponibles

== Frequently Asked Questions ==

= ¿Necesito una cuenta en Siigo para usar este plugin? =

Sí, necesitas una cuenta activa en Siigo Colombia y tus credenciales API (Username y Access Key). Puedes obtenerlas desde tu panel de Siigo en la sección de integraciones.

= ¿El plugin funciona en modo sandbox? =

Sí, el plugin incluye soporte para ambiente sandbox (pruebas) y producción. Recomendamos probar primero en sandbox antes de activar en producción.

= ¿Cómo obtengo mis credenciales API de Siigo? =

Inicia sesión en tu cuenta de Siigo, ve a Configuración > Integraciones > API y genera tus credenciales (Username y Access Key).

= ¿Qué sucede si un producto no existe en WooCommerce? =

El plugin creará automáticamente el producto en WooCommerce usando la información de Siigo (nombre, SKU, precio, descripción, stock).

= ¿Puedo sincronizar productos manualmente? =

Sí, la sincronización se ejecuta automáticamente cada día, pero también puedes forzar una sincronización manual usando el hook de WordPress o esperando al próximo ciclo programado.

= ¿El plugin sincroniza órdenes hacia Siigo? =

Sí, cuando una orden se completa en WooCommerce, el plugin genera la factura correspondiente en Siigo con todos los detalles del cliente y productos.

= ¿Qué información del cliente se sincroniza? =

Se sincroniza: nombre completo, tipo de documento, número de documento, dirección, ciudad, departamento, teléfono y correo electrónico.

= ¿Es compatible con WooCommerce Blocks? =

Sí, el plugin es totalmente compatible con el nuevo sistema de checkout basado en bloques de WooCommerce.

= ¿Cómo puedo ver los errores o problemas? =

Activa el modo debug en la configuración del plugin. Los registros (logs) se guardarán en WooCommerce > Estado > Registros.

= ¿Puedo limitar el stock sincronizado? =

Sí, en la configuración puedes establecer una cantidad mínima de stock. Si el stock en Siigo es mayor a este valor, se usará el mínimo configurado.

= ¿Qué pasa si hay un error de conexión con Siigo? =

El plugin registrará el error en los logs de WooCommerce. Puedes revisar los logs para identificar el problema y solucionarlo.

== Screenshots ==

1. Configuración principal de la integración
2. Campos de credenciales API de Siigo
3. Configuración de facturación y documentos
4. Campo DNI en el checkout
5. Logs y depuración

== Changelog ==

= 0.2.0 =
* Compatibilidad con WooCommerce 9.7
* Compatibilidad con WordPress 6.8
* Soporte para WooCommerce High-Performance Order Storage (HPOS)
* Mejoras en la sincronización de productos
* Mejoras en el manejo de errores
* Optimizaciones de rendimiento

= 0.1.0 =
* Primera versión pública
* Sincronización de productos desde Siigo
* Generación de facturas automáticas
* Campo DNI en checkout
* Integración con ciudades y departamentos de Colombia
* Soporte sandbox y producción

== Upgrade Notice ==

= 0.2.0 =
Esta versión incluye compatibilidad con las últimas versiones de WordPress y WooCommerce, además de mejoras importantes en rendimiento y estabilidad.

== Additional Info ==

= Soporte =

Para soporte técnico, por favor visita:
* [Sitio web del desarrollador](https://saulmoralespa.com)
* [LinkedIn](https://www.linkedin.com/in/saulmoralespa/)

= Contribuir =

Si deseas contribuir al desarrollo del plugin, visita el repositorio en GitHub o contacta al autor.

= Donaciones =

Si este plugin te ha sido útil, considera hacer una donación en: https://saulmoralespa.com/donation

== Credits ==

* Desarrollado por: [Saúl Morales Pacheco](https://saulmoralespa.com)
* [LinkedIn](https://www.linkedin.com/in/saulmoralespa/)

== Privacy Policy ==

Este plugin no recopila ni almacena datos personales de los usuarios más allá de lo necesario para la integración con Siigo. Los datos se transmiten de forma segura mediante API a los servidores de Siigo siguiendo sus políticas de privacidad.
