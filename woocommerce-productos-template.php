/**
 * Plugin Name: WooCommerce Productos Template Modular
 * Plugin URI: https://example.com/
 * Description: Reorganiza el template de productos de WooCommerce con un diseño moderno similar a Mercado Libre y filtros con AJAX.
 * Version: 2.0.0
 * Author: Tu Nombre
 * Author URI: https://example.com/
 * Text Domain: wc-productos-template
 * Domain Path: /languages
 * WC requires at least: 5.0.0
 * WC tested up to: 8.0.0
 * Requires PHP: 7.2
 * Requires at least: 5.6
 *
 * @package WC_Productos_Template
 */

// Si este archivo es llamado directamente, abortar.
if (!defined('WPINC')) {
    die;
}

// Definir constante de versión
define('WC_PRODUCTOS_TEMPLATE_VERSION', '2.0.0');
define('WC_PRODUCTOS_TEMPLATE_DIR', plugin_dir_path(__FILE__));
define('WC_PRODUCTOS_TEMPLATE_URL', plugin_dir_url(__FILE__));
define('WC_PRODUCTOS_TEMPLATE_INCLUDES_DIR', WC_PRODUCTOS_TEMPLATE_DIR . 'includes/');
define('WC_PRODUCTOS_TEMPLATE_MODULES_DIR', WC_PRODUCTOS_TEMPLATE_INCLUDES_DIR . 'modules/');
define('WC_PRODUCTOS_TEMPLATE_TEMPLATES_DIR', WC_PRODUCTOS_TEMPLATE_DIR . 'templates/');
define('WC_PRODUCTOS_TEMPLATE_ASSETS_DIR', WC_PRODUCTOS_TEMPLATE_DIR . 'assets/');
define('WC_PRODUCTOS_TEMPLATE_CSS_DIR', WC_PRODUCTOS_TEMPLATE_ASSETS_DIR . 'css/');
define('WC_PRODUCTOS_TEMPLATE_JS_DIR', WC_PRODUCTOS_TEMPLATE_ASSETS_DIR . 'js/');

/**
 * Comprueba si un directorio existe y lo crea si no
 */
function wc_productos_create_directory_if_not_exists($path) {
    if (!file_exists($path)) {
        return mkdir($path, 0755, true);
    }
    return true;
}

/**
 * Crea todos los directorios necesarios para el plugin
 */
function wc_productos_create_plugin_directories() {
    $directories = array(
        WC_PRODUCTOS_TEMPLATE_INCLUDES_DIR,
        WC_PRODUCTOS_TEMPLATE_MODULES_DIR,
        WC_PRODUCTOS_TEMPLATE_TEMPLATES_DIR,
        WC_PRODUCTOS_TEMPLATE_TEMPLATES_DIR . 'loop/',
        WC_PRODUCTOS_TEMPLATE_TEMPLATES_DIR . 'partials/',
        WC_PRODUCTOS_TEMPLATE_ASSETS_DIR,
        WC_PRODUCTOS_TEMPLATE_CSS_DIR,
        WC_PRODUCTOS_TEMPLATE_CSS_DIR . 'components/',
        WC_PRODUCTOS_TEMPLATE_JS_DIR,
        WC_PRODUCTOS_TEMPLATE_JS_DIR . 'modules/'
    );

    foreach ($directories as $dir) {
        wc_productos_create_directory_if_not_exists($dir);
    }
}

/**
 * El código que se ejecuta durante la activación del plugin.
 */
function activate_wc_productos_template() {
    require_once WC_PRODUCTOS_TEMPLATE_INCLUDES_DIR . 'class-activator.php';
    WC_Productos_Template_Activator::activate();
}

/**
 * El código que se ejecuta durante la desactivación del plugin.
 */
function deactivate_wc_productos_template() {
    require_once WC_PRODUCTOS_TEMPLATE_INCLUDES_DIR . 'class-deactivator.php';
    WC_Productos_Template_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_wc_productos_template');
register_deactivation_hook(__FILE__, 'deactivate_wc_productos_template');

/**
 * Comienza la ejecución del plugin.
 */
function run_wc_productos_template() {
    // Crear directorios
    wc_productos_create_plugin_directories();
    
    // Cargar archivos principales
    require_once WC_PRODUCTOS_TEMPLATE_INCLUDES_DIR . 'class-loader.php';
    require_once WC_PRODUCTOS_TEMPLATE_INCLUDES_DIR . 'class-plugin.php';
    
    // Iniciar el plugin
    $plugin = new WC_Productos_Template_Plugin();
    $plugin->run();
}

// Asegurarse de que WooCommerce está activo antes de iniciar el plugin
add_action('plugins_loaded', function() {
    if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
        run_wc_productos_template();
    } else {
        add_action('admin_notices', function() {
            ?>
            <div class="notice notice-error is-dismissible">
                <p><?php _e('WooCommerce Productos Template requiere que WooCommerce esté instalado y activado.', 'wc-productos-template'); ?></p>
            </div>
            <?php
        });
    }
});
