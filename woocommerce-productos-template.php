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
         * Función para registrar scripts y estilos
         */
        public function register_scripts() {
            // Obtener la página actual para la paginación
            $current_page = max(1, get_query_var('paged'));
            
            // Sólo cargar en páginas de WooCommerce o con el shortcode
            if (is_shop() || is_product_category() || is_product_tag() || is_product() || 
                is_woocommerce() || 
                (is_a(get_post(), 'WP_Post') && has_shortcode(get_post()->post_content, 'productos_personalizados'))) {
                
                // Enqueue CSS con versión para evitar caché
      wp_enqueue_style(
        'wc-productos-template-styles', 
        WC_PRODUCTOS_TEMPLATE_URL . 'assets/css/productos-template.css', 
        array(), 
        WC_PRODUCTOS_TEMPLATE_VERSION . '.' . time(),
        'all'  // Medio de estilo
    );
    
    // Establecer prioridad más alta (100 → 999)
    wp_style_add_data('wc-productos-template-styles', 'priority', 999);
                
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
                    WC_PRODUCTOS_TEMPLATE_VERSION . '.' . time(), // Agregar timestamp para forzar recarga
                    true
                );
                
                // Obtener datos para la paginación
                $products_per_page = get_option('posts_per_page');
                $total_products = wc_get_loop_prop('total', 0);
                if (!$total_products) {
                    // Si no está definido, intentar obtener total desde query global
                    global $wp_query;
                    $total_products = $wp_query->found_posts;
                }
                
                // Localizar script para AJAX
                wp_localize_script('wc-productos-template-script', 'WCProductosParams', array(
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('productos_filter_nonce'),
                    'current_page' => $current_page,
                    'products_per_page' => $products_per_page,
                    'total_products' => $total_products,
                    'total_pages' => ceil($total_products / $products_per_page),
                    'i18n' => array(
                        'loading' => __('Cargando productos...', 'wc-productos-template'),
                        'error' => __('Error al cargar productos. Intente nuevamente.', 'wc-productos-template'),
                        'added' => __('Producto añadido al carrito', 'wc-productos-template'),
                        'no_results' => __('No se encontraron productos.', 'wc-productos-template')
                    )
                ));
                
                // Añadir clase al body para namespace CSS
                add_filter('body_class', function($classes) {
                    $classes[] = 'wc-productos-template';
                    return $classes;
                });
                
                // Añadir estilos inline para corregir problemas específicos
                $inline_css = "
                    .woocommerce ul.products li.product {
                        background-color: #fff !important;
                        border: 1px solid #e2e2e2 !important;
                        border-radius: 8px !important;
                        overflow: hidden !important;
                        transition: all 0.3s ease !important;
                        margin-bottom: 20px !important;
                    }
                    .woocommerce ul.products li.product:hover {
                        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1) !important;
                        transform: translateY(-3px) !important;
                    }
                    .woocommerce ul.products li.product a img {
                        margin: 0 !important;
                    }
                    .woocommerce ul.products {
                        display: grid !important;
                        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)) !important;
                        gap: 20px !important;
                    }
                    /* Correcciones para paginación */
                    .page-number {
                        cursor: pointer;
                    }
                    .wc-productos-template .page-dots {
                        display: inline-block;
                        margin: 0 5px;
                        color: #666;
                    }
                ";
                wp_add_inline_style('wc-productos-template-styles', $inline_css);
                
                // Forzar visualización en cuadrícula
                $this->force_grid_layout();
            }
        }
        
        /**
         * Forzar visualización en cuadrícula para productos
         * Esta función aplica CSS con alta prioridad y fuerza la visualización en cuadrícula
         */
        public function force_grid_layout() {
            // CSS con mayor especificidad y !important para forzar la cuadrícula
            $force_grid_css = "
                body.woocommerce ul.products,
                body.woocommerce-page ul.products,
                body .woocommerce ul.products,
                body .wc-productos-template ul.products,
                body .wc-productos-template .productos-grid,
                body .productos-grid {
                    display: grid !important;
                    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)) !important;
                    gap: 20px !important;
                    width: 100% !important;
                    margin: 0 !important;
                    padding: 0 !important;
                    list-style: none !important;
                }
                
                body.woocommerce ul.products::before,
                body.woocommerce ul.products::after,
                body.woocommerce-page ul.products::before,
                body.woocommerce-page ul.products::after,
                body .woocommerce ul.products::before,
                body .woocommerce ul.products::after,
                body .productos-grid::before,
                body .productos-grid::after {
                    display: none !important;
                }
                
                body.woocommerce ul.products li.product,
                body.woocommerce-page ul.products li.product,
                body .woocommerce ul.products li.product,
                body .wc-productos-template ul.products li.product,
                body .productos-grid li.product {
                    float: none !important;
                    margin: 0 0 20px 0 !important;
                    padding: 0 !important;
                    width: 100% !important;
                    clear: none !important;
                }
                
                /* Asegurar compatibilidad con columnas específicas de WooCommerce */
                body.woocommerce ul.products.columns-1 li.product,
                body.woocommerce ul.products.columns-2 li.product,
                body.woocommerce ul.products.columns-3 li.product,
                body.woocommerce ul.products.columns-4 li.product,
                body.woocommerce ul.products.columns-5 li.product,
                body.woocommerce ul.products.columns-6 li.product {
                    width: 100% !important;
                    margin-right: 0 !important;
                }
                
                /* Responsive para dispositivos móviles */
                @media (max-width: 768px) {
                    body.woocommerce ul.products,
                    body.woocommerce-page ul.products,
                    body .woocommerce ul.products,
                    body .wc-productos-template ul.products,
                    body .wc-productos-template .productos-grid {
                        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)) !important;
                    }
                }
                
                @media (max-width: 480px) {
                    body.woocommerce ul.products,
                    body.woocommerce-page ul.products,
                    body .woocommerce ul.products,
                    body .wc-productos-template ul.products,
                    body .wc-productos-template .productos-grid {
                        grid-template-columns: repeat(2, 1fr)) !important;
                    }
                }
            ";
            
            // Agregar CSS con alta prioridad
            wp_add_inline_style('wc-productos-template-styles', $force_grid_css);
        }

        /**
         * Sobreescribir templates de WooCommerce de manera más selectiva
         */
public function override_woocommerce_templates($template, $template_name, $template_path) {
    // Forzar sobrescritura para archivos críticos para la cuadrícula
    if ($template_name == 'loop/loop-start.php' || $template_name == 'loop/loop-end.php' || $template_name == 'content-product.php') {
        $plugin_template = plugin_dir_path(__FILE__) . 'templates/' . $template_name;
        
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }
    }
    
    // Lista ampliada de templates que queremos sobrescribir
    $override_templates = array(
        'content-product.php',           // Template de producto individual
        'loop/loop-start.php',           // Inicio del loop (CRÍTICO para la cuadrícula)
        'loop/loop-end.php',             // Final del loop
        'loop/pagination.php',           // Paginación
        'loop/orderby.php',              // Selector de ordenamiento
        'loop/result-count.php',         // Contador de resultados
        'archive-product.php'            // Template de archivo de productos
    );
    
    // Solo sobrescribir los templates específicos
    if (in_array($template_name, $override_templates)) {
        $plugin_template = plugin_dir_path(__FILE__) . 'templates/' . $template_name;
        
        // Verificar si existe nuestra versión del template
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }
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
 * AJAX handler para filtrar productos - corregido para preservar la consulta principal
 */
public function ajax_filter_products() {
    // Verificar nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'productos_filter_nonce')) {
        wp_send_json_error('Nonce inválido');
        exit;
    }
    
    // Obtener página actual
    $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
    
    // Configurar argumentos base de la consulta
    $args = array(
        'post_type'      => 'product',
        'posts_per_page' => get_option('posts_per_page'),
        'paged'          => $page,
        'post_status'    => 'publish',
    );
        
    // Importante: Comprobar si hay filtros activados antes de añadirlos a la consulta
    $has_filters = false;
    
    // Inicializar arrays para taxonomías y meta
    $tax_query = array('relation' => 'AND');
    $meta_query = array('relation' => 'AND');
    
    // Filtrar por categoría
    if (isset($_POST['category']) && !empty($_POST['category'])) {
        $categories = explode(',', sanitize_text_field($_POST['category']));
        if (!empty($categories)) {
            $tax_query[] = array(
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => $categories,
                'operator' => 'IN'
            );
            $has_filters = true;
        }
    }
    
    // Filtrar por grado (atributo personalizado)
    if (isset($_POST['grade']) && !empty($_POST['grade'])) {
        $grades = explode(',', sanitize_text_field($_POST['grade']));
        if (!empty($grades)) {
            $tax_query[] = array(
                'taxonomy' => 'pa_grado',
                'field'    => 'slug',
                'terms'    => $grades,
                'operator' => 'IN'
            );
            $has_filters = true;
        }
    }
    
    // Filtrar por volumen (rango)
    if (isset($_POST['min_volume']) && isset($_POST['max_volume']) && 
        (intval($_POST['min_volume']) > 100 || intval($_POST['max_volume']) < 5000)) {
        $meta_query[] = array(
            'key'     => '_volumen_ml',
            'value'   => array(intval($_POST['min_volume']), intval($_POST['max_volume'])),
            'type'    => 'NUMERIC',
            'compare' => 'BETWEEN'
        );
        $has_filters = true;
    }
    
    // Búsqueda - Mejorado para buscar por SKU/REF y otros meta fields
    if (isset($_POST['search']) && !empty($_POST['search'])) {
        $search_term = sanitize_text_field($_POST['search']);
        
        // Crear un meta query para búsqueda en SKU
        $meta_query[] = array(
            'relation' => 'OR',
            array(
                'key'     => '_sku',
                'value'   => $search_term,
                'compare' => 'LIKE'
            ),
            array(
                'key'     => '_volumen_ml',
                'value'   => $search_term,
                'compare' => 'LIKE'
            )
        );
        
        // También buscar en el título y contenido del producto
        $args['s'] = $search_term;
        
        $has_filters = true;
    }
    
    // Añadir las consultas de taxonomía y meta solo si hay filtros activos
    if (count($tax_query) > 1) {
        $args['tax_query'] = $tax_query;
    }
    
    if (count($meta_query) > 1) {
        $args['meta_query'] = $meta_query;
    }
    
    // Aplicar filtros de WooCommerce
    $args = apply_filters('woocommerce_product_query_args', $args);
    
    // Ejecutar la consulta
    $products_query = new WP_Query($args);
    
    // Configurar las propiedades del bucle de WooCommerce
    wc_set_loop_prop('current_page', $page);
    wc_set_loop_prop('is_paginated', true);
    wc_set_loop_prop('page_template', 'productos-template');
    wc_set_loop_prop('per_page', get_option('posts_per_page'));
    wc_set_loop_prop('total', $products_query->found_posts);
    wc_set_loop_prop('total_pages', $products_query->max_num_pages);
    wc_set_loop_prop('columns', 4); // Ajusta según tu diseño
    
    ob_start();
    
    if ($products_query->have_posts()) {
        echo '<ul class="productos-grid products wc-productos-template columns-' . esc_attr(wc_get_loop_prop('columns', 4)) . '">';
        
        while ($products_query->have_posts()) {
            $products_query->the_post();
            wc_get_template_part('content', 'product');
        }
        
        echo '</ul>';
    } else {
        echo '<div class="woocommerce-info">' . 
            esc_html__('No se encontraron productos que coincidan con tu búsqueda.', 'wc-productos-template') . 
            '</div>';
    }
    
    $products_html = ob_get_clean();
    
    // Generar paginación
    ob_start();
    
    $this->render_pagination($products_query->max_num_pages, $page);
    
    $pagination = ob_get_clean();
    
    // Resetear datos de consulta
    wp_reset_postdata();
    
    // Enviar respuesta
    wp_send_json_success(array(
        'products'     => $products_html,
        'pagination'   => $pagination,
        'total'        => $products_query->found_posts,
        'current_page' => $page,
        'max_pages'    => $products_query->max_num_pages,
        'has_filters'  => $has_filters,
        'search_term'  => isset($_POST['search']) ? sanitize_text_field($_POST['search']) : ''
    ));
    
    exit;
}

        /**
         * Renderiza la paginación de manera consistente
         *
         * @param int $max_pages Número total de páginas
         * @param int $current_page Página actual
         */
        public function render_pagination($max_pages, $current_page = 1) {
            if ($max_pages <= 1) {
                return;
            }
            
            echo '<div class="productos-pagination">';
            echo '<div class="pagination-info">';
            
            $per_page = get_option('posts_per_page');
            $total = wc_get_loop_prop('total', 0);
            
            printf(
                esc_html__('Mostrando %1$d-%2$d de %3$d resultados', 'wc-productos-template'),
                (($current_page - 1) * $per_page) + 1,
                min($total, $current_page * $per_page),
                $total
            );
            
            echo '</div>';
            echo '<div class="pagination-links">';
            
            // Botón "Anterior" si no estamos en la primera página
            if ($current_page > 1) {
                printf(
                    '<button class="page-number page-prev" data-page="%d" aria-label="%s">←</button>',
                    $current_page - 1,
                    esc_attr__('Página anterior', 'wc-productos-template')
                );
            }
            
            // Mostrar números de página (con límite para evitar demasiados botones)
            $start = max(1, $current_page - 2);
            $end = min($max_pages, $current_page + 2);
            
            if ($start > 1) {
                echo '<button class="page-number" data-page="1">1</button>';
                if ($start > 2) {
                    echo '<span class="page-dots">...</span>';
                }
            }
            
            for ($i = $start; $i <= $end; $i++) {
                printf(
                    '<button class="page-number%s" data-page="%d">%d</button>',
                    $i === $current_page ? ' active' : '',
                    $i,
                    $i
                );
            }
            
            if ($end < $max_pages) {
                if ($end < $max_pages - 1) {
                    echo '<span class="page-dots">...</span>';
                }
                printf('<button class="page-number" data-page="%d">%d</button>', $max_pages, $max_pages);
            }
            
            // Botón "Siguiente" si no estamos en la última página
            if ($current_page < $max_pages) {
                printf(
                    '<button class="page-number page-next" data-page="%d" aria-label="%s">→</button>',
                    $current_page + 1,
                    esc_attr__('Página siguiente', 'wc-productos-template')
                );
            }
            
            echo '</div></div>';
        }

        /**
         * Cargador de templates personalizado más selectivo
         * Permite cargar el template completo para el shortcode
         */
        public function template_loader($template) {
            // Detectar si estamos mostrando el shortcode
            $using_shortcode = false;
            if (is_a(get_post(), 'WP_Post')) {
                $using_shortcode = has_shortcode(get_post()->post_content, 'productos_personalizados');
            }
            
            // Solo sobrescribir en páginas de archivo de productos cuando usamos el shortcode
            if ($using_shortcode && (is_product_category() || is_product_tag() || is_shop())) {
                $custom_template = plugin_dir_path(__FILE__) . 'templates/archive-product.php';
                if (file_exists($custom_template)) {
                    return $custom_template;
                }
            }
            
            return $template;
        }

        /**
         * Shortcode modificado para mostrar productos con el nuevo template y paginación adecuada
         */
        public function productos_shortcode($atts) {
            $atts = shortcode_atts(array(
                'category' => '',
                'per_page' => get_option('posts_per_page')
            ), $atts, 'productos_personalizados');
            
            // Asegurarse de que se puedan obtener parámetros de paginación
            global $wp_query;
            if (!isset($wp_query->query_vars['paged'])) {
                $wp_query->query_vars['paged'] = get_query_var('paged', 1);
            }
            
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
 * Función unificada para encolade estilos de cuadrícula
 * Reemplaza funciones dispersas como force_productos_grid_styles y wc_productos_fix_grid_display
 */
function wc_productos_unified_grid_styles() {
    // Solo cargar en páginas relevantes
    if (!is_woocommerce() && !is_shop() && !is_product_category() && !is_product_tag() && 
        !is_product() && !has_shortcode(get_post()->post_content, 'productos_personalizados')) {
        return;
    }
    
    // Encolar el archivo CSS principal con prioridad muy alta
    wp_enqueue_style(
        'wc-productos-template-styles',
        plugin_dir_url(__FILE__) . 'assets/css/productos-template.css',
        array(),
        defined('WC_PRODUCTOS_TEMPLATE_VERSION') ? WC_PRODUCTOS_TEMPLATE_VERSION . '.' . time() : time(),
        'all'
    );
    
    // Establecer prioridad extremadamente alta
    wp_style_add_data('wc-productos-template-styles', 'priority', 9999);
    
    // Encolar archivo force-grid.css específico para forzar cuadrícula
    wp_enqueue_style(
        'wc-force-grid',
        plugin_dir_url(__FILE__) . 'assets/css/force-grid.css',
        array('wc-productos-template-styles'),
        defined('WC_PRODUCTOS_TEMPLATE_VERSION') ? WC_PRODUCTOS_TEMPLATE_VERSION . '.' . time() : time(),
        'all'
    );
    
    // Darle prioridad aún mayor
    wp_style_add_data('wc-force-grid', 'priority', 99999);
    
    // CSS crítico inline con especificidad extrema
    $critical_css = "
    /* Estilos críticos de cuadrícula con especificidad extrema */
    html body ul.products,
    html body.woocommerce ul.products,
    html body.woocommerce-page ul.products,
    html body .woocommerce ul.products,
    html .wc-productos-template ul.products,
    html .productos-grid {
        display: grid !important;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)) !important;
        gap: 20px !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        list-style: none !important;
        float: none !important;
        clear: both !important;
    }
    
    html body ul.products li.product,
    html body.woocommerce ul.products li.product,
    html body.woocommerce-page ul.products li.product,
    html body .woocommerce ul.products li.product,
    html .wc-productos-template ul.products li.product,
    html .productos-grid li.product {
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 0 20px 0 !important;
        padding: 0 !important;
        float: none !important;
        clear: none !important;
        box-sizing: border-box !important;
    }
    
    /* Eliminar estilos que interfieren */
    html body ul.products::before,
    html body ul.products::after,
    html body.woocommerce ul.products::before,
    html body.woocommerce ul.products::after,
    html .woocommerce ul.products::before,
    html .woocommerce ul.products::after {
        display: none !important;
        content: none !important;
        clear: none !important;
    }";
    
    wp_add_inline_style('wc-force-grid', $critical_css);
    
    // JavaScript para aplicar estilos después de que el DOM esté cargado
    wp_add_inline_script('jquery', "
    jQuery(document).ready(function($) {
        function applyGridStyles() {
            $('ul.products, .productos-grid').css({
                'display': 'grid',
                'grid-template-columns': 'repeat(auto-fill, minmax(220px, 1fr))',
                'gap': '20px',
                'width': '100%',
                'margin': '0',
                'padding': '0',
                'list-style': 'none',
                'float': 'none'
            });
            
            $('ul.products li.product, .productos-grid li.product').css({
                'width': '100%',
                'margin': '0 0 20px 0',
                'float': 'none',
                'clear': 'none'
            });
        }
        
        // Aplicar inmediatamente
        applyGridStyles();
        
        // Aplicar después de cargar imágenes
        $(window).on('load', applyGridStyles);
        
        // Aplicar después de completar AJAX
        $(document).ajaxComplete(applyGridStyles);
    });");
}

// Usar prioridad extremadamente alta
add_action('wp_enqueue_scripts', 'wc_productos_unified_grid_styles', 99999);

// Desactivar funciones antiguas si existen
if (function_exists('force_productos_grid_styles')) {
    remove_action('wp_enqueue_scripts', 'force_productos_grid_styles', 9999);
}

if (function_exists('wc_productos_fix_grid_display')) {
    remove_action('wp_enqueue_scripts', 'wc_productos_fix_grid_display', 99999);
}

/**
 * Calcular y ajustar el número de productos para mostrar exactamente 3 filas
 * basado en el ancho de la pantalla y el número de columnas
 */
function wc_productos_ajustar_productos_por_fila() {
    // Función para determinar productos por fila según el ancho de la ventana
    $script = "
    <script>
    jQuery(document).ready(function($) {
        function calcularProductosPorFila() {
            // Detectar cuántas columnas hay actualmente
            var $grid = $('.productos-grid, ul.products');
            if ($grid.length === 0) return;
            
            // Contar cuántos productos hay por fila
            var productosPorFila = 4; // Valor por defecto
            
            // Determinar por el ancho de la ventana
            if (window.innerWidth <= 480) {
                productosPorFila = 2; // Móviles: 2 columnas
            } else if (window.innerWidth <= 768) {
                productosPorFila = 3; // Tablets: 3 columnas
            } else if (window.innerWidth <= 991) {
                productosPorFila = 3; // Pantallas medianas: 3 columnas
            } else {
                productosPorFila = 4; // Pantallas grandes: 4 columnas
            }
            
            // Calcular el número total de productos para 3 filas exactas
            var productosTotal = productosPorFila * 3;
            
            // Guardar en una cookie para que PHP pueda acceder
            document.cookie = 'wc_productos_por_fila=' + productosPorFila + '; path=/';
            document.cookie = 'wc_productos_max=' + productosTotal + '; path=/';
            
            return productosTotal;
        }
        
        // Calcular al cargar
        calcularProductosPorFila();
        
        // Recalcular cuando cambie el tamaño de la ventana
        $(window).on('resize', function() {
            calcularProductosPorFila();
        });
    });
    </script>
    ";
    
    // Añadir el script al footer
    add_action('wp_footer', function() use ($script) {
        echo $script;
    });
    
    // Verificar que las funciones existen antes de usarlas
    if (function_exists('limitar_productos_a_tres_filas')) {
        // Modificar la consulta de productos para limitar a 3 filas
        add_action('woocommerce_product_query', 'limitar_productos_a_tres_filas');
    }
    
    if (function_exists('limitar_productos_a_tres_filas_array')) {
        // También modificar la consulta para el shortcode
        add_filter('woocommerce_shortcode_products_query', 'limitar_productos_a_tres_filas_array');
    }
}
 
/**
 * Limitar la consulta de productos a exactamente 3 filas completas
 */
function limitar_productos_a_tres_filas($query) {
    // Determinar cuántos productos por fila basado en la cookie o usar valor predeterminado
    $productos_por_fila = isset($_COOKIE['wc_productos_por_fila']) ? intval($_COOKIE['wc_productos_por_fila']) : 4;
    
    // Calcular para 3 filas exactas
    $productos_total = $productos_por_fila * 3;
    
    // Establecer posts_per_page
    $query->set('posts_per_page', $productos_total);
    
    return $query;
}

/**
 * Versión para array de argumentos (usado en shortcodes)
 */
function limitar_productos_a_tres_filas_array($args) {
    // Determinar cuántos productos por fila basado en la cookie o usar valor predeterminado
    $productos_por_fila = isset($_COOKIE['wc_productos_por_fila']) ? intval($_COOKIE['wc_productos_por_fila']) : 4;
    
    // Calcular para 3 filas exactas
    $productos_total = $productos_por_fila * 3;
    
    // Establecer posts_per_page
    $args['posts_per_page'] = $productos_total;
    
    return $args;
}

// Iniciar la función
add_action('init', 'wc_productos_ajustar_productos_por_fila');
/**
 * Función para solucionar problemas con las plantillas
 */
function wc_productos_fix_template_loading() {
    // Verificar que las plantillas críticas existen
    $plugin_path = plugin_dir_path(__FILE__);
    $templates_to_check = [
        'loop/loop-start.php',
        'loop/loop-end.php',
        'content-product.php'
    ];
    
    foreach ($templates_to_check as $template) {
        $template_path = $plugin_path . 'templates/' . $template;
        
        // Si la plantilla no existe, mostrar un warning en el admin
        if (!file_exists($template_path)) {
            add_action('admin_notices', function() use ($template) {
                echo '<div class="notice notice-error is-dismissible">';
                echo '<p><strong>Error en WC Productos Template:</strong> ';
                echo sprintf('No se encuentra la plantilla crucial: %s. Esto puede afectar la visualización en cuadrícula.', esc_html($template));
                echo '</p></div>';
            });
        }
    }
    
    // Asegurar que las plantillas se carguen correctamente
    add_filter('wc_get_template_part', function($template, $slug, $name) use ($plugin_path) {
        // Priorizar las plantillas críticas
        if ($slug == 'content' && $name == 'product') {
            $custom_template = $plugin_path . 'templates/content-product.php';
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
        
        return $template;
    }, 20, 3);
    
    // Asegurar que las plantillas de loop se carguen correctamente
    add_filter('woocommerce_locate_template', function($template, $template_name, $template_path) use ($plugin_path) {
        // Priorizar las plantillas de loop
        if ($template_name == 'loop/loop-start.php' || $template_name == 'loop/loop-end.php') {
            $custom_template = $plugin_path . 'templates/' . $template_name;
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
        
        return $template;
    }, 20, 3);
}

// Ejecutar en la inicialización del plugin
add_action('init', 'wc_productos_fix_template_loading');
/**
 * Agregar script directo para forzar visualización en cuadrícula
 * Añadir al final del archivo woocommerce-productos-template.php
 */
function wc_productos_direct_grid_fix() {
    // Solo ejecutar en páginas de productos de WooCommerce
    if (!is_woocommerce() && !has_shortcode(get_post()->post_content, 'productos_personalizados')) {
        return;
    }
    
    ?>
    <style type="text/css">
    /* Estilos críticos aplicados directamente */
    body ul.products,
    body.woocommerce ul.products,
    body.woocommerce-page ul.products,
    body .woocommerce ul.products,
    .wc-productos-template ul.products,
    .productos-grid {
        display: grid !important;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)) !important;
        gap: 20px !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        list-style: none !important;
        float: none !important;
    }
    
    body ul.products li.product,
    body.woocommerce ul.products li.product,
    body.woocommerce-page ul.products li.product,
    body .woocommerce ul.products li.product,
    .wc-productos-template ul.products li.product,
    .productos-grid li.product {
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 0 20px 0 !important;
        padding: 0 !important;
        float: none !important;
        clear: none !important;
    }
    </style>
    
    <script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        // Forzar cuadrícula de productos
        var productLists = document.querySelectorAll('ul.products, .productos-grid');
        
        if (productLists.length > 0) {
            productLists.forEach(function(list) {
                list.style.display = 'grid';
                list.style.gridTemplateColumns = 'repeat(auto-fill, minmax(220px, 1fr))';
                list.style.gap = '20px';
                list.style.width = '100%';
                list.style.margin = '0';
                list.style.padding = '0';
                list.style.listStyle = 'none';
                list.style.float = 'none';
                
                // Aplicar estilos a los elementos li
                var products = list.querySelectorAll('li.product');
                products.forEach(function(product) {
                    product.style.width = '100%';
                    product.style.margin = '0 0 20px 0';
                    product.style.float = 'none';
                    product.style.clear = 'none';
                });
            });
        }
    });
    </script>
    <?php
}

// Agregar con prioridad extremadamente alta
add_action('wp_footer', 'wc_productos_direct_grid_fix', 99999);
