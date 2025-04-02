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
            WC_PRODUCTOS_TEMPLATE_VERSION . '.' . time() // Agregar timestamp para forzar recarga
        );
        
        // Establecer alta prioridad para nuestros estilos
        wp_style_add_data('wc-productos-template-styles', 'priority', 100);
        
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
    }
}

     /**
 * Sobreescribir templates de WooCommerce de manera más selectiva
 * Reemplaza la función existente
 */
public function override_woocommerce_templates($template, $template_name, $template_path) {
    // Lista de templates que queremos sobrescribir
    $override_templates = array(
        'content-product.php',           // Template de producto individual
        'loop/loop-start.php',           // Inicio del loop
        'loop/pagination.php',           // Paginación
        'archive-product.php'            // Solo si estamos usando el shortcode
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
    
    // Búsqueda
    if (isset($_POST['search']) && !empty($_POST['search'])) {
        $args['s'] = sanitize_text_field($_POST['search']);
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
        echo '<ul class="productos-grid products columns-' . esc_attr(wc_get_loop_prop('columns', 4)) . '">';
        
        while ($products_query->have_posts()) {
            $products_query->the_post();
            wc_get_template_part('content', 'product');
        }
        
        echo '</ul>';
    } else {
        echo '<div class="woocommerce-info">' . 
             esc_html__('No se encontraron productos que coincidan con tu selección.', 'wc-productos-template') . 
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
        'has_filters'  => $has_filters
    ));
    
    exit;
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
 * Reemplaza la función existente
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

