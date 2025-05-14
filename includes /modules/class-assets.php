<?php
/**
 * Módulo Assets - Gestión de CSS y JavaScript
 * 
 * @package WC_Productos_Template
 */

class WC_Productos_Template_Assets {
    
    /**
     * El cargador que es responsable de mantener y registrar todos los hooks del plugin.
     *
     * @var WC_Productos_Template_Loader $loader
     */
    private $loader;
    
    /**
     * Inicializar el módulo Assets.
     *
     * @param WC_Productos_Template_Loader $loader Cargador de plugins.
     */
    public function __construct($loader) {
        $this->loader = $loader;
    }
    
    /**
     * Inicializar los hooks del módulo.
     */
    public function init() {
        // Registrar y cargar CSS y JS
        $this->loader->add_action('wp_enqueue_scripts', $this, 'register_styles', 20);
        $this->loader->add_action('wp_enqueue_scripts', $this, 'register_scripts', 20);
        
        // Cargar CSS y JS solo cuando sea necesario
        $this->loader->add_action('wp_enqueue_scripts', $this, 'maybe_load_assets', 99);
        
        // Añadir estilos admin si es necesario
        $this->loader->add_action('admin_enqueue_scripts', $this, 'admin_styles');
        
        // Añadir versión a los assets para evitar caché
        $this->loader->add_filter('style_loader_src', $this, 'add_version_to_assets', 10, 2);
        $this->loader->add_filter('script_loader_src', $this, 'add_version_to_assets', 10, 2);
    }
    
    /**
     * Registrar estilos del plugin
     */
    public function register_styles() {
        // Registrar CSS principal
        wp_register_style(
            'wc-productos-template-main',
            WC_PRODUCTOS_TEMPLATE_URL . 'assets/css/main.css',
            array(),
            WC_PRODUCTOS_TEMPLATE_VERSION
        );
        
        // Registrar componentes CSS individuales para cargar según sea necesario
        $css_components = array(
            'grid'          => 'components/grid.css',
            'product-card'  => 'components/product-card.css',
            'filters'       => 'components/filters.css',
            'search'        => 'components/search.css',
            'login'         => 'components/login.css'
        );
        
        foreach ($css_components as $handle => $path) {
            wp_register_style(
                'wc-productos-template-' . $handle,
                WC_PRODUCTOS_TEMPLATE_URL . 'assets/css/' . $path,
                array(),
                WC_PRODUCTOS_TEMPLATE_VERSION
            );
        }
        
        // Registrar Font Awesome para iconos
        wp_register_style(
            'font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css',
            array(),
            '5.15.4'
        );
        
        // Registrar jQuery UI para sliders
        wp_register_style(
            'jquery-ui-style',
            '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css',
            array(),
            '1.12.1'
        );
    }
    
    /**
     * Registrar scripts del plugin
     */
    public function register_scripts() {
        // Registrar JS principal
        wp_register_script(
            'wc-productos-template-main',
            WC_PRODUCTOS_TEMPLATE_URL . 'assets/js/main.js',
            array('jquery'),
            WC_PRODUCTOS_TEMPLATE_VERSION,
            true
        );
        
        // Registrar módulos JS individuales
        $js_modules = array(
            'grid'      => 'modules/grid.js',
            'filters'   => 'modules/filters.js',
            'search'    => 'modules/search.js',
            'login'     => 'modules/login.js'
        );
        
        foreach ($js_modules as $handle => $path) {
            $file_path = WC_PRODUCTOS_TEMPLATE_JS_DIR . $path;
            
            // Comprobar si el archivo existe antes de registrarlo
            if (file_exists($file_path)) {
                wp_register_script(
                    'wc-productos-template-' . $handle,
                    WC_PRODUCTOS_TEMPLATE_URL . 'assets/js/' . $path,
                    array('jquery', 'wc-productos-template-main'),
                    WC_PRODUCTOS_TEMPLATE_VERSION,
                    true
                );
            } else {
                // Crear archivo base si no existe
                $this->create_js_module_file($handle, $file_path);
            }
        }
        
        // Localizar script principal con parámetros para AJAX
        wp_localize_script('wc-productos-template-main', 'WCProductosParams', array(
            'ajaxurl'       => admin_url('admin-ajax.php'),
            'nonce'         => wp_create_nonce('wc_productos_template_nonce'),
            'current_page'  => max(1, get_query_var('paged')),
            'products_per_page' => get_option('posts_per_page'),
            'i18n'          => array(
                'loading'   => __('Cargando productos...', 'wc-productos-template'),
                'error'     => __('Error al cargar productos. Intente nuevamente.', 'wc-productos-template'),
                'no_results' => __('No se encontraron productos.', 'wc-productos-template'),
                'added'     => __('Producto añadido al carrito', 'wc-productos-template')
            ),
            'is_mobile'     => wp_is_mobile(),
            'is_logged_in'  => is_user_logged_in()
        ));
    }
    
    /**
     * Crear archivo de módulo JS base si no existe
     *
     * @param string $module Nombre del módulo.
     * @param string $file_path Ruta del archivo.
     */
    private function create_js_module_file($module, $file_path) {
        $js_content = "/**\n * Módulo {$module} para WC Productos Template\n */\n\njQuery(document).ready(function($) {\n    // Código del módulo {$module}\n    console.log('Módulo {$module} iniciado');\n});";
        
        file_put_contents($file_path, $js_content);
    }
    
    /**
     * Cargar estilos y scripts solo cuando sea necesario
     */
    public function maybe_load_assets() {
        // Obtener el módulo Core
        $core_module = $this->loader->get_module('WC_Productos_Template_Core');
        
        // Si no está disponible o no estamos en una página de productos, no cargar assets
        if (!$core_module || !$core_module->is_product_page()) {
            return;
        }
        
        // Cargar todos los estilos
        wp_enqueue_style('font-awesome');
        wp_enqueue_style('jquery-ui-style');
        wp_enqueue_style('wc-productos-template-main');
        
        // Cargar scripts
        wp_enqueue_script('jquery-ui-slider');
        wp_enqueue_script('wc-productos-template-main');
        wp_enqueue_script('wc-productos-template-grid');
        wp_enqueue_script('wc-productos-template-filters');
        wp_enqueue_script('wc-productos-template-search');
        
        // Cargar script de login solo para usuarios no logueados
        if (!is_user_logged_in()) {
            wp_enqueue_script('wc-productos-template-login');
        }
        
        // Agregar CSS inline para corregir posibles problemas con algunos temas
        $this->add_compatibility_css();
    }
    
    /**
     * Añadir estilos para el admin
     */
    public function admin_styles($hook) {
        // Cargar estilos solo en páginas de admin relevantes
        $admin_pages = array(
            'post.php',
            'post-new.php',
            'woocommerce_page_wc-settings'
        );
        
        if (in_array($hook, $admin_pages) && 
            (isset($_GET['post_type']) && $_GET['post_type'] === 'product')) {
            
            wp_enqueue_style(
                'wc-productos-template-admin',
                WC_PRODUCTOS_TEMPLATE_URL . 'assets/css/admin.css',
                array(),
                WC_PRODUCTOS_TEMPLATE_VERSION
            );
        }
    }
    
    /**
     * Añadir versión a los assets para evitar caché
     *
     * @param string $src URL del asset.
     * @param string $handle Handle del asset.
     * @return string URL con versión modificada.
     */
    public function add_version_to_assets($src, $handle) {
        // Solo modificar nuestros assets
        if (strpos($handle, 'wc-productos-template') === 0) {
            // Añadir timestamp para desarrollo
            if (defined('WP_DEBUG') && WP_DEBUG) {
                $src = add_query_arg('ver', time(), $src);
            }
        }
        
        return $src;
    }
    
    /**
     * Añadir CSS inline para corregir problemas con algunos temas
     */
    private function add_compatibility_css() {
        $css = "
        /* Correcciones para compatibilidad con temas */
        .wc-productos-template ul.products,
        .wc-productos-template .productos-grid {
            display: grid !important;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)) !important;
            gap: 20px !important;
            width: 100% !important;
            margin: 0 0 30px 0 !important;
            padding: 0 !important;
            list-style: none !important;
            float: none !important;
            clear: both !important;
        }
        
        .wc-productos-template ul.products::before,
        .wc-productos-template ul.products::after,
        .wc-productos-template .productos-grid::before,
        .wc-productos-template .productos-grid::after {
            display: none !important;
            content: none !important;
            clear: none !important;
        }
        
        .wc-productos-template ul.products li.product,
        .wc-productos-template .productos-grid li.product {
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 0 20px 0 !important;
            padding: 0 !important;
            float: none !important;
            clear: none !important;
            box-sizing: border-box !important;
            display: flex !important;
            flex-direction: column !important;
            height: 100% !important;
        }
        ";
        
        wp_add_inline_style('wc-productos-template-main', $css);
    }
}
