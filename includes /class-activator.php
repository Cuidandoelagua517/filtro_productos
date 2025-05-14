<?php
/**
 * Se ejecuta durante la activación del plugin.
 *
 * @package WC_Productos_Template
 */

class WC_Productos_Template_Activator {

    /**
     * Activar el plugin.
     */
    public static function activate() {
        // Asegurarse de que WooCommerce está activo
        if (!function_exists('is_plugin_active')) {
            include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        
        if (!is_plugin_active('woocommerce/woocommerce.php') && current_user_can('activate_plugins')) {
            // Desactivar este plugin
            deactivate_plugins(plugin_basename(WC_PRODUCTOS_TEMPLATE_DIR . 'woocommerce-productos-template.php'));
            wp_die('Este plugin requiere que WooCommerce esté instalado y activado.');
        }
        
        // Crear directorios necesarios
        self::create_plugin_directories();
        
        // Crear archivos base necesarios
        self::create_base_files();
        
        // Limpiar caché transients
        self::clear_transients();
    }
    
    /**
     * Crear directorios necesarios para el plugin
     */
    private static function create_plugin_directories() {
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
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }
    
    /**
     * Crear archivos base necesarios
     */
    private static function create_base_files() {
        // Array de archivos a crear si no existen
        $base_files = array(
            WC_PRODUCTOS_TEMPLATE_CSS_DIR . 'main.css' => "/**\n * Estilos principales del plugin WC Productos Template\n */\n\n@import 'components/grid.css';\n@import 'components/product-card.css';\n@import 'components/filters.css';\n@import 'components/search.css';\n@import 'components/login.css';\n",
            
            WC_PRODUCTOS_TEMPLATE_CSS_DIR . 'components/grid.css' => "/**\n * Estilos para la cuadrícula de productos\n */\n\n.wc-productos-grid {\n  display: grid;\n  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));\n  gap: 20px;\n  width: 100%;\n  margin-bottom: 30px;\n}\n",
            
            WC_PRODUCTOS_TEMPLATE_CSS_DIR . 'components/product-card.css' => "/**\n * Estilos para la tarjeta de producto\n */\n\n.wc-producto-card {\n  border: 1px solid #e2e2e2;\n  border-radius: 8px;\n  overflow: hidden;\n  transition: all 0.3s ease;\n  background-color: #fff;\n  box-shadow: 0 2px 5px rgba(0,0,0,0.05);\n}\n\n.wc-producto-card:hover {\n  box-shadow: 0 5px 15px rgba(0,0,0,0.1);\n  transform: translateY(-3px);\n}\n",
            
            WC_PRODUCTOS_TEMPLATE_JS_DIR . 'main.js' => "/**\n * JavaScript principal del plugin WC Productos Template\n */\n\njQuery(document).ready(function($) {\n  // Código principal\n  console.log('WC Productos Template iniciado');\n});\n"
        );
        
        foreach ($base_files as $file_path => $content) {
            if (!file_exists($file_path)) {
                file_put_contents($file_path, $content);
            }
        }
    }
    
    /**
     * Limpiar caché transients
     */
    private static function clear_transients() {
        // Limpiar los transients relacionados con WooCommerce
        delete_transient('wc_products_onsale');
        
        // Limpiar caché del plugin
        global $wpdb;
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%wc_productos_template%'");
    }
}
