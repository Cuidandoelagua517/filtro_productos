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

/**
 * Obtener CSS predeterminado
 */
function wc_productos_template_get_default_css() {
    return '
/**
 * CSS para WooCommerce Productos Template
 * Diseño moderno, minimalista y optimizado para UX
 */

/* ===== 1. RESETEO Y VARIABLES ===== */
:root {
    --primary-color: #0056b3;
    --primary-hover: #004494;
    --light-bg: #f8f9fa;
    --border-color: #e2e2e2;
    --text-primary: #333;
    --text-secondary: #666;
    --text-muted: #777;
    --shadow-sm: 0 2px 5px rgba(0,0,0,0.05);
    --shadow-md: 0 5px 15px rgba(0,0,0,0.08);
    --transition: all 0.3s ease;
    --radius: 6px;
    --spacing-xs: 5px;
    --spacing-sm: 10px;
    --spacing-md: 15px;
    --spacing-lg: 20px;
    --spacing-xl: 30px;
}

/* Importante: limitar a las áreas específicas de productos */
.productos-container *, 
.woocommerce-products-header *,
.woocommerce ul.products *, 
.wc-productos-template * {
    box-sizing: border-box;
}

/* Solo modificar el área específica de catálogo de productos */
.productos-container,
.wc-productos-template,
.woocommerce ul.products,
.woocommerce-products-header {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 var(--spacing-lg);
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
    color: var(--text-primary);
    line-height: 1.5;
}

/* ===== HEADER CON TÍTULO Y BUSCADOR ===== */
.productos-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--spacing-xl) 0;
    margin-bottom: var(--spacing-xl);
    border-bottom: 1px solid var(--border-color);
}

.productos-header h1 {
    font-size: 28px;
    font-weight: 600;
    margin: 0;
    color: var(--text-primary);
}

/* Barra de búsqueda */
.productos-search {
    position: relative;
    width: 300px;
}

.productos-search input {
    width: 100%;
    padding: var(--spacing-sm) 40px var(--spacing-sm) var(--spacing-md);
    border: 1px solid var(--border-color);
    border-radius: var(--radius);
    font-size: 14px;
    transition: var(--transition);
}

.productos-search input:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(0, 86, 179, 0.15);
}

.productos-search button {
    position: absolute;
    right: 0;
    top: 0;
    height: 100%;
    width: 40px;
    background-color: var(--primary-color);
    color: white;
    border: none;
    border-radius: 0 var(--radius) var(--radius) 0;
    cursor: pointer;
    transition: var(--transition);
}

.productos-search button:hover {
    background-color: var(--primary-hover);
}

/* ===== LAYOUT PRINCIPAL (DOS COLUMNAS) ===== */
.productos-layout {
    display: flex;
    gap: var(--spacing-xl);
    margin-bottom: var(--spacing-xl);
}

/* ===== SIDEBAR DE FILTROS ===== */
.productos-sidebar {
    flex: 0 0 260px;
    width: 260px;
    background-color: var(--light-bg);
    border-radius: var(--radius);
    padding: var(--spacing-lg);
    border: 1px solid var(--border-color);
    position: sticky;
    top: 20px;
    max-height: calc(100vh - 40px);
    overflow-y: auto;
}

.productos-sidebar h2 {
    font-size: 18px;
    margin: 0 0 var(--spacing-lg) 0;
    padding-bottom: var(--spacing-md);
    border-bottom: 1px solid var(--border-color);
    color: var(--text-primary);
    font-weight: 600;
}

.productos-sidebar h3 {
    font-size: 16px;
    font-weight: 600;
    margin: 0 0 var(--spacing-sm) 0;
    color: var(--text-secondary);
}

/* Grupos de filtros */
.filtro-grupo {
    margin-bottom: var(--spacing-xl);
}

.filtro-grupo:last-child {
    margin-bottom: 0;
}

.filtro-lista {
    max-height: 200px;
    overflow-y: auto;
    padding-right: var(--spacing-sm);
}

.filtro-option {
    display: flex;
    align-items: center;
    margin-bottom: var(--spacing-sm);
}

.filtro-option input[type="checkbox"] {
    margin-right: var(--spacing-sm);
    cursor: pointer;
}

.filtro-option label {
    font-size: 14px;
    cursor: pointer;
    user-select: none;
    color: var(--text-secondary);
}

.filtro-option label:hover {
    color: var(--primary-color);
}

/* Slider de volumen */
.volumen-slider {
    margin-top: var(--spacing-md);
}

.volumen-range {
    margin-bottom: var(--spacing-md);
    height: 4px;
    background: #ddd;
    border-radius: 2px;
}

.ui-slider-range {
    background-color: var(--primary-color);
}

.ui-slider-handle {
    width: 16px !important;
    height: 16px !important;
    border-radius: 50% !important;
    background-color: var(--primary-color) !important;
    border: 2px solid #fff !important;
    cursor: pointer !important;
    box-shadow: var(--shadow-sm);
    top: -0.5em !important;
}

.ui-slider-handle:focus {
    outline: none;
}

.volumen-values {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    color: var(--text-muted);
}

/* ===== ÁREA PRINCIPAL DE PRODUCTOS ===== */
.productos-main {
    flex: 1;
    min-width: 0; /* Importante para flex */
}

.productos-breadcrumb {
    margin-bottom: var(--spacing-lg);
    font-size: 13px;
    color: var(--text-muted);
}

.productos-breadcrumb a {
    color: var(--primary-color);
    text-decoration: none;
}

.productos-breadcrumb a:hover {
    text-decoration: underline;
}

/* Cuadrícula de productos */
.productos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: var(--spacing-lg);
    margin-bottom: var(--spacing-xl);
    min-height: 200px; /* Para evitar saltos durante AJAX */
    position: relative;
}

/* Mensaje sin resultados */
.productos-no-results,
.productos-error {
    grid-column: 1 / -1;
    text-align: center;
    padding: var(--spacing-xl);
    color: var(--text-muted);
    font-style: italic;
    background: var(--light-bg);
    border-radius: var(--radius);
}

/* ===== TARJETA DE PRODUCTO ===== */
.woocommerce ul.products li.producto-card,
.productos-container .producto-card,
.producto-card {
    background-color: #fff;
    border: 1px solid var(--border-color);
    border-radius: var(--radius);
    padding: var(--spacing-lg);
    transition: var(--transition);
    display: flex;
    flex-direction: column;
    height: 100%;
    overflow: hidden;
    position: relative;
}

.producto-card:hover {
    box-shadow: var(--shadow-md);
    transform: translateY(-3px);
}

.producto-imagen {
    height: 180px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: var(--spacing-md);
    position: relative;
    overflow: hidden;
}

.producto-imagen img {
    max-height: 100%;
    max-width: 100%;
    object-fit: contain;
    transition: transform 0.3s ease;
}

.producto-card:hover .producto-imagen img {
    transform: scale(1.05);
}

.producto-badge {
    position: absolute;
    top: var(--spacing-sm);
    right: var(--spacing-sm);
    padding: 4px var(--spacing-sm);
    border-radius: 30px;
    font-size: 11px;
    font-weight: 600;
    z-index: 2;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badge-stock {
    background-color: #e6f4ea;
    color: #137333;
}

.badge-danger {
    background-color: #fce8e6;
    color: #c5221f;
}

.producto-titulo {
    font-size: 16px;
    font-weight: 600;
    margin: 0 0 var(--spacing-sm) 0;
    color: var(--text-primary);
    line-height: 1.4;
}

.producto-detalles {
    font-size: 13px;
    color: var(--text-muted);
    margin: 0 0 var(--spacing-md) 0;
    line-height: 1.4;
}

.producto-precio {
    font-size: 18px;
    font-weight: 700;
    color: var(--primary-color);
    margin: auto 0 var(--spacing-md) 0;
}

.producto-precio del {
    font-size: 14px;
    color: var(--text-muted);
    font-weight: normal;
    margin-right: var(--spacing-xs);
}

.producto-precio ins {
    text-decoration: none;
}

.producto-boton {
    background-color: var(--primary-color);
    color: white;
    border: none;
    border-radius: var(--radius);
    padding: var(--spacing-sm);
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
    width: 100%;
    text-align: center;
    display: block;
    text-decoration: none;
}

.producto-boton:hover {
    background-color: var(--primary-hover);
}

.producto-boton.loading {
    opacity: 0.8;
    cursor: wait;
    background-color: var(--text-secondary);
}

/* ===== PAGINACIÓN ===== */
.productos-pagination {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: var(--spacing-xl);
    padding-top: var(--spacing-lg);
    border-top: 1px solid var(--border-color);
}

.pagination-info {
    font-size: 14px;
    color: var(--text-muted);
}

.pagination-links {
    display: flex;
}

.page-number {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 3px;
    border-radius: var(--radius);
    font-size: 14px;
    cursor: pointer;
    transition: var(--transition);
    background: none;
    border: 1px solid var(--border-color);
}

.page-number.active {
    background-color: var(--primary-color);
    color: white;
    font-weight: 600;
    border-color: var(--primary-color);
}

.page-number:not(.active) {
    background-color: var(--light-bg);
    color: var(--text-secondary);
}

.page-number:not(.active):hover {
    background-color: #e9ecef;
}

.page-next {
    font-weight: bold;
}

/* ===== ANIMACIONES Y EFECTOS ===== */
.productos-loading {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.8);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    z-index: 10;
}

.loader-icon {
    width: 30px;
    height: 30px;
    border: 3px solid var(--light-bg);
    border-radius: 50%;
    border-top-color: var(--primary-color);
    animation: spin 1s linear infinite;
    margin-bottom: var(--spacing-sm);
}

.loader-text {
    color: var(--text-secondary);
    font-size: 14px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.productos-grid {
    animation: fadeIn 0.5s ease;
}

/* ===== MENSAJES DE NOTIFICACIÓN ===== */
.wc-message-success {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background-color: #e6f4ea;
    color: #137333;
    padding: 12px 20px;
    border-radius: var(--radius);
    box-shadow: var(--shadow-md);
    z-index: 9999;
    animation: fadeIn 0.3s, fadeOut 0.3s 2.7s forwards;
    font-size: 14px;
}

@keyframes fadeOut {
    from { opacity: 1; transform: translateY(0); }
    to { opacity: 0; transform: translateY(20px); }
}

/* ===== MEJORAS DE ACCESIBILIDAD ===== */
.filtro-option input[type="checkbox"]:focus + label {
    text-decoration: underline;
    color: var(--primary-color);
}

.productos-search input:focus,
.producto-boton:focus,
.page-number:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(0, 86, 179, 0.25);
}

/* ===== PERSONALIZACIÓN DE JQUERY UI ===== */
.ui-slider-horizontal .ui-slider-handle {
    margin-left: -8px;
}

/* ===== EFECTOS ADICIONALES ===== */
.productos-container.scrolled .productos-sidebar {
    box-shadow: var(--shadow-sm);
}

/* ===== RESPONSIVE ===== */
@media (max-width: 1024px) {
    .productos-grid {
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    }
}

@media (max-width: 868px) {
    .productos-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .productos-header h1 {
        margin-bottom: var(--spacing-md);
    }
    
    .productos-search {
        width: 100%;
        max-width: 100%;
    }
    
    .productos-layout {
        flex-direction: column;
    }
    
    .productos-sidebar {
        width: 100%;
        flex: 0 0 auto;
        margin-bottom: var(--spacing-lg);
        position: static;
        max-width: 100%;
        max-height: none;
    }
    
    .productos-grid {
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    }
}

@media (max-width: 576px) {
    .productos-pagination {
        flex-direction: column;
        gap: var(--spacing-md);
        align-items: flex-start;
    }
    
    .pagination-links {
        margin-top: var(--spacing-sm);
    }
    
    .productos-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: var(--spacing-md);
    }
    
    .producto-card {
        padding: var(--spacing-md);
    }
    
    .producto-imagen {
        height: 140px;
    }
}

@media (max-width: 400px) {
    .productos-grid {
        grid-template-columns: 1fr;
    }
}
';
}

/**
 * JavaScript por defecto
 */
function wc_productos_template_get_default_js() {
    return "
        jQuery(document).ready(function($) {
            // Inicializar slider de volumen
            if ($('.volumen-slider').length) {
                $('.volumen-slider .volumen-range').slider({
                    range: true,
                    min: 100,
                    max: 5000,
                    values: [100, 5000],
                    slide: function(event, ui) {
                        $('#volumen-min').text(ui.values[0] + ' ml');
                        $('#volumen-max').text(ui.values[1] + ' ml');
                        $('input[name=\"min_volume\"]').val(ui.values[0]);
                        $('input[name=\"max_volume\"]').val(ui.values[1]);
                    }
                });
            }
            
            // Variables para filtrado
            var timer;
            var ajaxRunning = false;
            
            // Función para mostrar indicador de carga
            function showLoader() {
                $('.productos-grid').append('<div class=\"productos-loading\"><span class=\"loader-icon\"></span><span class=\"loader-text\">Cargando productos...</span></div>');
            }
            
            // Función para ocultar el indicador de carga
            function hideLoader() {
                $('.productos-loading').fadeOut(300, function() {
                    $(this).remove();
                });
            }
            
            // Función para filtrar productos
            function filterProducts(page = 1) {
                if (ajaxRunning) return;
                
                // Mostrar indicador de carga
                showLoader();
                
                // Obtener valores de filtros
                var categoryFilter = [];
                $('.filtro-category:checked').each(function() {
                    categoryFilter.push($(this).val());
                });
                
                var gradeFilter = [];
                $('.filtro-grade:checked').each(function() {
                    gradeFilter.push($(this).val());
                });
                
                var minVolume = $('input[name=\"min_volume\"]').val() || 100;
                var maxVolume = $('input[name=\"max_volume\"]').val() || 5000;
                var searchQuery = $('.productos-search input').val();
                
                // Configurar datos para AJAX
                var data = {
                    action: 'productos_filter',
                    nonce: WCProductosParams.nonce,
                    category: categoryFilter.join(','),
                    grade: gradeFilter.join(','),
                    min_volume: minVolume,
                    max_volume: maxVolume,
                    search: searchQuery,
                    page: page
                };
                
                // Marcar que AJAX está en progreso
                ajaxRunning = true;
                
                // Realizar petición AJAX
                $.ajax({
                    url: WCProductosParams.ajaxurl,
                    type: 'POST',
                    data: data,
                    success: function(response) {
                        if (response.success) {
                            // Actualizar lista de productos con animación
                            $('.productos-grid').fadeOut(200, function() {
                                $(this).html(response.data.products).fadeIn(200);
                            });
                            
                            // Actualizar paginación
                            if (response.data.pagination) {
                                $('.productos-pagination').fadeOut(200, function() {
                                    $(this).html(response.data.pagination).fadeIn(200);
                                });
                            }
                            
                            // Actualizar contador de resultados
                            var itemsShown = Math.min(response.data.total, $('.producto-card').length);
                            $('.pagination-info').text('Mostrando 1-' + itemsShown + ' de ' + 
                                response.data.total + ' resultados');
                            
                            // Animar scroll hacia arriba suavemente si estamos en otra página
                            if (page > 1) {
                                $('html, body').animate({
                                    scrollTop: $('.productos-grid').offset().top - 80
                                }, 400);
                            }
                        } else {
                            // Mostrar mensaje de error
                            $('.productos-grid').html('<p class=\"productos-error\">' + 
                                WCProductosParams.i18n.error + '</p>');
                        }
                        
                        // Ocultar loader y marcar AJAX como terminado
                        hideLoader();
                        ajaxRunning = false;
                    },
                    error: function(xhr, status, error) {
                        console.error('Error en la petición AJAX: ' + error);
                        $('.productos-grid').html('<p class=\"productos-error\">' + 
                            WCProductosParams.i18n.error + '</p>');
                        hideLoader();
                        ajaxRunning = false;
                    }
                });
            }
            
            // Event listeners para filtros con debounce
            $('.filtro-option input[type=\"checkbox\"]').on('change', function() {
                filterProducts();
            });
            
            // Evento para slider de volumen
            $('.volumen-slider .volumen-range').on('slidechange', function() {
                clearTimeout(timer);
                timer = setTimeout(function() {
                    filterProducts();
                }, 500);
            });
            
            // Evento para búsqueda con debounce
            $('.productos-search input').on('keyup', function() {
                clearTimeout(timer);
                timer = setTimeout(function() {
                    filterProducts();
                }, 500);
            });
            
            // Evento para búsqueda al hacer click en el botón
            $('.productos-search button').on('click', function(e) {
                e.preventDefault();
                filterProducts();
            });
            
            // Delegación de eventos para paginación
            $(document).on('click', '.page-number:not(.active)', function(e) {
                e.preventDefault();
                var page = $(this).data('page') || 1;
                filterProducts(page);
                
                // Actualizar clases de paginación
                $('.page-number').removeClass('active');
                $(this).addClass('active');
            });
            
            // Delegación de eventos para botón Agregar al carrito
            $(document).on('click', '.producto-boton', function(e) {
                e.preventDefault();
                var productId = $(this).data('product-id');
                var $button = $(this);
                
                // Cambiar estado del botón
                $button.addClass('loading').text('Agregando...');
                
                // Añadir al carrito usando AJAX de WooCommerce
                $.ajax({
                    url: wc_add_to_cart_params.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'woocommerce_ajax_add_to_cart',
                        product_id: productId,
                        quantity: 1
                    },
                    success: function(response) {
                        if (response.fragments) {
                            // Actualizar mini-carrito
                            $.each(response.fragments, function(key, value) {
                                $(key).replaceWith(value);
                            });
                            
                            // Restaurar botón
                            $button.removeClass('loading').text('Agregado al carrito');
                            setTimeout(function() {
                                $button.text('Agregar al carrito');
                            }, 2000);
                            
                            // Mostrar mensaje de éxito
                            if ($('.wc-message-success').length) {
                                $('.wc-message-success').remove();
                            }
                            
                            $('body').append('<div class=\"wc-message-success\">' + WCProductosParams.i18n.added + '</div>');
                            setTimeout(function() {
                                $('.wc-message-success').fadeOut(300, function() {
                                    $(this).remove();
                                });
                            }, 3000);
                        } else {
                            // Restaurar botón en caso de error
                            $button.removeClass('loading').text('Agregar al carrito');
                        }
                    },
                    error: function() {
                        console.error('Error al agregar al carrito');
                        $button.removeClass('loading').text('Error al agregar');
                        setTimeout(function() {
                            $button.text('Agregar al carrito');
                        }, 2000);
                    }
                });
            });
            
            // Efecto de scroll para sidebar
            $(window).on('scroll', function() {
                if ($(window).width() > 768) {
                    if ($(window).scrollTop() > 100) {
                        $('.productos-container').addClass('scrolled');
                    } else {
                        $('.productos-container').removeClass('scrolled');
                    }
                }
            });
        });
    ";
}
