<?php
/**
 * Plugin Name: WooCommerce Productos Template Personalizado
 * Plugin URI: https://example.com/
 * Description: Reorganiza el template de productos de WooCommerce con un diseño moderno y filtros con AJAX.
 * Version: 1.0.0
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
define('WC_PRODUCTOS_TEMPLATE_VERSION', '1.0.0');

if (!class_exists('WC_Productos_Template')) {

    class WC_Productos_Template {

        /**
         * Constructor
         */
        public function __construct() {
            // Verificar si WooCommerce está activo
            if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
                // Definir constantes
                define('WC_PRODUCTOS_TEMPLATE_URL', plugin_dir_url(__FILE__));
                define('WC_PRODUCTOS_TEMPLATE_PATH', plugin_dir_path(__FILE__));
                
                // Declarar compatibilidad con HPOS
                add_action('before_woocommerce_init', array($this, 'declare_hpos_compatibility'));
                
                // Inicializar el plugin
                add_action('init', array($this, 'init'));
                
                // Registrar scripts y estilos
                add_action('wp_enqueue_scripts', array($this, 'register_scripts'), 999);
                
                // Sobreescribir templates de WooCommerce
                add_filter('woocommerce_locate_template', array($this, 'override_woocommerce_templates'), 10, 3);
                add_action('wp_head', array($this, 'add_critical_styles'), 999);
                // Agregar AJAX handlers
                add_action('wp_ajax_productos_filter', array($this, 'ajax_filter_products'));
                add_action('wp_ajax_nopriv_productos_filter', array($this, 'ajax_filter_products'));
                // Método alternativo para cargar templates personalizados
                add_filter('template_include', array($this, 'template_loader'));
                
                // Agregar shortcodes
                add_shortcode('productos_personalizados', array($this, 'productos_shortcode'));
            }
        }
        
        /**
         * Declarar compatibilidad con HPOS (High-Performance Order Storage)
         */
        public function declare_hpos_compatibility() {
            if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
                \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
            }
        }

        /**
         * Inicializar el plugin
         */
        public function init() {
            // Crear directorio de templates
            $this->create_template_directory();
        }

        /**
         * Crear directorio de templates si no existe
         */
        private function create_template_directory() {
            $template_dir = WC_PRODUCTOS_TEMPLATE_PATH . 'templates';
            if (!file_exists($template_dir)) {
                mkdir($template_dir, 0755, true);
                
                // Copiar archivos de template predeterminados
                $this->copy_template_files();
            }
        }

        /**
         * Copiar archivos de template
         */
        private function copy_template_files() {
            // Aquí podrías añadir código para copiar templates predeterminados
            // de tu plugin a la carpeta de templates
        }
 
        /**
         * Registrar scripts y estilos
         */
        public function register_scripts() {
            // Sólo cargar en páginas de WooCommerce o con el shortcode
            if (is_shop() || is_product_category() || is_product_tag() || is_product() || 
                is_woocommerce() || 
                (is_a(get_post(), 'WP_Post') && has_shortcode(get_post()->post_content, 'productos_personalizados'))) {
                
                // Enqueue CSS con versión para evitar caché
                wp_enqueue_style(
                    'wc-productos-template-styles', 
                    WC_PRODUCTOS_TEMPLATE_URL . 'assets/css/productos-template.css', 
                    array(), 
                    WC_PRODUCTOS_TEMPLATE_VERSION
                );
                
                // Agregar soporte para la barra de rango
                wp_enqueue_script('jquery-ui-slider');
                wp_enqueue_style(
                    'jquery-ui-style', 
                    '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css',
                    array(),
                    '1.12.1'
                );
                
                // JavaScript con jQuery como dependencia
                wp_enqueue_script(
                    'wc-productos-template-script', 
                    WC_PRODUCTOS_TEMPLATE_URL . 'assets/js/productos-template.js', 
                    array('jquery', 'jquery-ui-slider'), 
                    WC_PRODUCTOS_TEMPLATE_VERSION, 
                    true
                );
                
                // Localizar script para AJAX
                wp_localize_script('wc-productos-template-script', 'WCProductosParams', array(
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('productos_filter_nonce'),
                    'i18n' => array(
                        'loading' => __('Cargando productos...', 'wc-productos-template'),
                        'error' => __('Error al cargar productos. Intente nuevamente.', 'wc-productos-template'),
                        'added' => __('Producto añadido al carrito', 'wc-productos-template')
                    )
                ));
            }
        }

        /**
         * Agregar estilos críticos inline
         */
        public function add_critical_styles() {
            if (is_shop() || is_product_category() || is_product_tag() || is_product() || is_woocommerce()) {
                echo '<style>
                /* Estilos críticos para tarjetas de productos */
                .producto-card, 
                .type-product {
                    background-color: #fff !important;
                    padding: 20px !important;
                    margin-bottom: 20px !important;
                    display: flex !important;
                    flex-direction: column !important;
                }
                
                .producto-imagen {
                    height: 180px !important;
                    display: flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                }
                
                .producto-titulo {
                    font-size: 16px !important;
                    font-weight: 600 !important;
                    color: #333 !important;
                }
                </style>';
            }
        }

        /**
         * Sobreescribir templates de WooCommerce
         */
        public function override_woocommerce_templates($template, $template_name, $template_path) {
            // Activar logging para depuración
            error_log('Template solicitado: ' . $template_name);
            
            $plugin_template = plugin_dir_path(__FILE__) . 'templates/' . $template_name;
            
            // Verifica si existe nuestra versión del template
            if (file_exists($plugin_template)) {
                error_log('Usando template personalizado: ' . $plugin_template);
                return $plugin_template;
            }
            
            return $template;
        }

        /**
         * Obtener categorías de productos
         */
        public function get_product_categories() {
            $args = array(
                'taxonomy' => 'product_cat',
                'orderby' => 'name',
                'hide_empty' => true
            );
            
            return get_terms($args);
        }

        /**
         * AJAX handler para filtrar productos
         */
        public function ajax_filter_products() {
            // Verificar nonce
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'productos_filter_nonce')) {
                wp_send_json_error('Nonce inválido');
                exit;
            }
            
            // Configurar argumentos de búsqueda
            $args = array(
                'post_type' => 'product',
                'posts_per_page' => get_option('posts_per_page'),
                'paged' => isset($_POST['page']) ? absint($_POST['page']) : 1,
                'tax_query' => array('relation' => 'AND'),
                'meta_query' => array('relation' => 'AND')
            );
            
            // Filtrar por categoría
            if (isset($_POST['category']) && !empty($_POST['category'])) {
                $args['tax_query'][] = array(
                    'taxonomy' => 'product_cat',
                    'field' => 'slug',
                    'terms' => sanitize_text_field($_POST['category'])
                );
            }
            
            // Filtrar por grado (atributo personalizado)
            if (isset($_POST['grade']) && !empty($_POST['grade'])) {
                $args['tax_query'][] = array(
                    'taxonomy' => 'pa_grado', // Asumiendo que tienes un atributo 'grado'
                    'field' => 'slug',
                    'terms' => sanitize_text_field($_POST['grade'])
                );
            }
            
            // Filtrar por volumen (rango)
            if (isset($_POST['min_volume']) && isset($_POST['max_volume'])) {
                $args['meta_query'][] = array(
                    'key' => '_volumen_ml', // Meta key personalizada
                    'value' => array(intval($_POST['min_volume']), intval($_POST['max_volume'])),
                    'type' => 'NUMERIC',
                    'compare' => 'BETWEEN'
                );
            }
            
            // Búsqueda
            if (isset($_POST['search']) && !empty($_POST['search'])) {
                $args['s'] = sanitize_text_field($_POST['search']);
            }
            
            // Obtener productos
            $products_query = new WP_Query($args);
            
            ob_start();
            
            if ($products_query->have_posts()) {
                while ($products_query->have_posts()) {
                    $products_query->the_post();
                    wc_get_template_part('content', 'product');
                }
            } else {
                echo '<p>' . esc_html__('No se encontraron productos.', 'wc-productos-template') . '</p>';
            }
            
            $products_html = ob_get_clean();
            
            // Crear paginación
            $pagination = '';
            if ($products_query->max_num_pages > 1) {
                ob_start();
                wc_get_template('loop/pagination.php', array(
                    'total' => $products_query->max_num_pages,
                    'current' => isset($_POST['page']) ? absint($_POST['page']) : 1
                ));
                $pagination = ob_get_clean();
            }
            
            // Resultado
            $response = array(
                'products' => $products_html,
                'pagination' => $pagination,
                'total' => $products_query->found_posts
            );
            
            wp_reset_postdata();
            wp_send_json_success($response);
            exit;
        }

        /**
         * Cargador de templates personalizados
         */
        public function template_loader($template) {
            if (is_product_category() || is_product_tag() || is_product() || is_shop()) {
                $file = 'archive-product.php';
                if (is_product()) {
                    $file = 'single-product.php';
                }
                
                $custom_template = plugin_dir_path(__FILE__) . 'templates/' . $file;
                if (file_exists($custom_template)) {
                    error_log('Cargando template personalizado: ' . $custom_template);
                    return $custom_template;
                }
            }
            return $template;
        }

        /**
         * Shortcode para mostrar productos con el nuevo template
         */
        public function productos_shortcode($atts) {
            $atts = shortcode_atts(array(
                'category' => '',
                'per_page' => get_option('posts_per_page')
            ), $atts, 'productos_personalizados');
            
            // Incluir template de página de productos
            ob_start();
            include(WC_PRODUCTOS_TEMPLATE_PATH . 'templates/productos-shortcode.php');
            return ob_get_clean();
        }
    }

    // Instanciar la clase
    new WC_Productos_Template();

    // Activación del plugin
    register_activation_hook(__FILE__, 'wc_productos_template_activate');
}

/**
 * Función para activar el plugin
 */
function wc_productos_template_activate() {
    // Asegurarse de que WooCommerce está activo
    if (!function_exists('is_plugin_active')) {
        include_once(ABSPATH . 'wp-admin/includes/plugin.php');
    }
    
    if (!is_plugin_active('woocommerce/woocommerce.php') && current_user_can('activate_plugins')) {
        // Desactivar este plugin
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('Este plugin requiere que WooCommerce esté instalado y activado.');
    }
    
    // Crear CSS predeterminado si no existe
    $css_file = plugin_dir_path(__FILE__) . 'assets/css/productos-template.css';
    if (!file_exists($css_file)) {
        file_put_contents($css_file, wc_productos_template_get_default_css());
    }

    // Crear directorios necesarios
    $template_path = plugin_dir_path(__FILE__) . 'templates';
    $css_path = plugin_dir_path(__FILE__) . 'assets/css';
    $js_path = plugin_dir_path(__FILE__) . 'assets/js';
    
    if (!file_exists($template_path)) {
        mkdir($template_path, 0755, true);
    }
    
    if (!file_exists($css_path)) {
        mkdir($css_path, 0755, true);
    }
    
    if (!file_exists($js_path)) {
        mkdir($js_path, 0755, true);
    }
    
    // Crear archivos iniciales si no existen
    if (!file_exists($css_path . '/productos-template.css')) {
        file_put_contents($css_path . '/productos-template.css', wc_productos_template_get_default_css());
    }
    
    if (!file_exists($js_path . '/productos-template.js')) {
        file_put_contents($js_path . '/productos-template.js', wc_productos_template_get_default_js());
    }
}

/**
 * Mostrar diagnósticos en el admin
 */
function wc_productos_admin_diagnostics() {
    ?>
    <div class="notice notice-info is-dismissible">
        <p><strong>Diagnóstico de Filtro Productos:</strong></p>
        <ul style="list-style: disc; padding-left: 20px;">
            <li>Plugin URL: <?php echo esc_html(plugin_dir_url(__FILE__)); ?></li>
            <li>Plugin Path: <?php echo esc_html(plugin_dir_path(__FILE__)); ?></li>
            <li>CSS Path: <?php echo esc_html(plugin_dir_path(__FILE__) . 'assets/css/productos-template.css'); ?></li>
            <li>CSS URL: <?php echo esc_html(plugin_dir_url(__FILE__) . 'assets/css/productos-template.css'); ?></li>
            <li>CSS existe: <?php echo file_exists(plugin_dir_path(__FILE__) . 'assets/css/productos-template.css') ? 'Sí' : 'No'; ?></li>
        </ul>
        <p>Para forzar una recarga completa, añade <code>?nocache=<?php echo time(); ?></code> a la URL.</p>
    </div>
    <?php
}

// Añadir diagnósticos al admin
add_action('admin_notices', 'wc_productos_admin_diagnostics');

