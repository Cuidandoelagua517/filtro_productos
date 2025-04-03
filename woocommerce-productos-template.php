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
                
                // Cargar clases adicionales
                $this->load_classes();
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
         * Cargar clases adicionales
         */
        private function load_classes() {
            // Cargar archivo de la clase de metabox
            require_once WC_PRODUCTOS_TEMPLATE_PATH . 'includes/class-productos-metabox.php';
            
            // Cargar archivo de la clase de órdenes
            require_once WC_PRODUCTOS_TEMPLATE_PATH . 'includes/class-productos-orders.php';
        }

        /**
         * Inicializar el plugin
         */
        public function init() {
            // Crear directorio de templates si no existe
            $this->create_template_directory();
            
            // Forzar visualización en cuadrícula con alta prioridad
            add_action('wp_enqueue_scripts', array($this, 'force_grid_styles'), 99999);
        }

        /**
         * Crear directorio de templates si no existe
         */
        private function create_template_directory() {
            $template_dir = WC_PRODUCTOS_TEMPLATE_PATH . 'templates';
            if (!file_exists($template_dir)) {
                mkdir($template_dir, 0755, true);
            }
            
            // Crear subdirectorios necesarios
            $loop_dir = $template_dir . '/loop';
            if (!file_exists($loop_dir)) {
                mkdir($loop_dir, 0755, true);
            }
        }
 
        /**
         * Función para registrar scripts y estilos
         */
        public function register_scripts() {
            // Sólo cargar en páginas de WooCommerce o con el shortcode
            if (!$this->is_product_page()) {
                return;
            }
            
            // Obtener la página actual para la paginación
            $current_page = max(1, get_query_var('paged'));
            
            // Enqueue CSS principal con versión para evitar caché
            wp_enqueue_style(
                'wc-productos-template-styles', 
                WC_PRODUCTOS_TEMPLATE_URL . 'assets/css/productos-template.css', 
                array(), 
                WC_PRODUCTOS_TEMPLATE_VERSION . '.' . time()
            );
            
            // Establecer prioridad alta
            wp_style_add_data('wc-productos-template-styles', 'priority', 999);
            
            // Agregar soporte para la barra de rango
            wp_enqueue_script('jquery-ui-slider');
            wp_enqueue_style(
                'jquery-ui-style', 
                '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css',
                array(),
                '1.12.1'
            );
            
            // Asegurar que Font Awesome está cargado para los iconos
            wp_enqueue_style(
                'font-awesome',
                'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css',
                array(),
                '5.15.4'
            );
            
            // JavaScript con jQuery como dependencia
            wp_enqueue_script(
                'wc-productos-template-script', 
                WC_PRODUCTOS_TEMPLATE_URL . 'assets/js/productos-template.js', 
                array('jquery', 'jquery-ui-slider'), 
                WC_PRODUCTOS_TEMPLATE_VERSION . '.' . time(),
                true
            );
            
            // Script para corregir problemas de barra de búsqueda
            wp_enqueue_script(
                'wc-productos-search-bar-fix',
                WC_PRODUCTOS_TEMPLATE_URL . 'assets/js/search-bar-fix.js',
                array('jquery'),
                WC_PRODUCTOS_TEMPLATE_VERSION . '.' . time(),
                true
            );
            
            // Obtener datos para la paginación
            $products_per_page = get_option('posts_per_page');
            $total_products = wc_get_loop_prop('total', 0);
            if (!$total_products) {
                // Si no está definido, intentar obtener total desde query global
                global $wp_query;
                if (isset($wp_query) && is_object($wp_query)) {
                    $total_products = $wp_query->found_posts;
                }
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
        }
        
        /**
         * Verificar si estamos en una página de productos
         */
        private function is_product_page() {
            return is_shop() || 
                   is_product_category() || 
                   is_product_tag() || 
                   is_product() || 
                   is_woocommerce() || 
                   (is_a(get_post(), 'WP_Post') && has_shortcode(get_post()->post_content, 'productos_personalizados'));
        }
        
        /**
         * Estilos de cuadrícula forzados
         */
        public function force_grid_styles() {
            if (!$this->is_product_page()) {
                return;
            }
            
            // Cargar CSS específico para forzar cuadrícula
            wp_enqueue_style(
                'wc-force-grid',
                WC_PRODUCTOS_TEMPLATE_URL . 'assets/css/force-grid.css',
                array('wc-productos-template-styles'),
                WC_PRODUCTOS_TEMPLATE_VERSION . '.' . time()
            );
            
            wp_style_add_data('wc-force-grid', 'priority', 9999);
            
            // Cargar CSS para fix de barra de búsqueda
            wp_enqueue_style(
                'wc-productos-search-bar-fix',
                WC_PRODUCTOS_TEMPLATE_URL . 'assets/css/search-bar-fix.css',
                array(),
                WC_PRODUCTOS_TEMPLATE_VERSION . '.' . time()
            );
            
            wp_style_add_data('wc-productos-search-bar-fix', 'priority', 9999);
        }

        /**
         * Sobreescribir templates de WooCommerce selectivamente
         */
        public function override_woocommerce_templates($template, $template_name, $template_path) {
            // Lista de templates que queremos sobrescribir
            $override_templates = array(
                'content-product.php',           // Template de producto individual
                'loop/loop-start.php',           // Inicio del loop
                'loop/loop-end.php',             // Final del loop
                'loop/pagination.php',           // Paginación
                'loop/orderby.php',              // Selector de ordenamiento
                'loop/result-count.php',         // Contador de resultados
                'archive-product.php'            // Template de archivo de productos
            );
            
            // Solo sobrescribir los templates específicos
            if (in_array($template_name, $override_templates)) {
                $plugin_template = WC_PRODUCTOS_TEMPLATE_PATH . 'templates/' . $template_name;
                
                // Verificar si existe nuestra versión del template
                if (file_exists($plugin_template)) {
                    return $plugin_template;
                }
            }
            
            return $template;
        }

        /**
         * Cargador de templates personalizado
         */
        public function template_loader($template) {
            // Detectar si estamos mostrando el shortcode
            $using_shortcode = false;
            if (is_a(get_post(), 'WP_Post')) {
                $using_shortcode = has_shortcode(get_post()->post_content, 'productos_personalizados');
            }
            
            // Solo sobrescribir en páginas de archivo de productos cuando usamos el shortcode
            if ($using_shortcode && (is_product_category() || is_product_tag() || is_shop())) {
                $custom_template = WC_PRODUCTOS_TEMPLATE_PATH . 'templates/archive-product.php';
                if (file_exists($custom_template)) {
                    return $custom_template;
                }
            }
            
            return $template;
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
            
            // Obtener página actual
            $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
            
            // Configurar argumentos base de la consulta
            $args = array(
                'post_type'      => 'product',
                'posts_per_page' => get_option('posts_per_page'),
                'paged'          => $page,
                'post_status'    => 'publish',
            );
            
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
                woocommerce_product_loop_start();
                
                while ($products_query->have_posts()) {
                    $products_query->the_post();
                    wc_get_template_part('content', 'product');
                }
                
                woocommerce_product_loop_end();
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
                'max_pages'    => $products_query->max_num_pages
            ));
            
            exit;
        }
        
        /**
         * Renderiza la paginación de manera consistente
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
            
            // Mostrar números de página
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
         * Shortcode para mostrar productos con el nuevo template
         */
        public function productos_shortcode($atts) {
            $atts = shortcode_atts(array(
                'category' => '',
                'per_page' => get_option('posts_per_page')
            ), $atts, 'productos_personalizados');
            
            // Asegurarse de que se puedan obtener parámetros de paginación
            global $wp_query;
            if (isset($wp_query) && is_object($wp_query) && !isset($wp_query->query_vars['paged'])) {
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
    
    // Crear directorios necesarios
    $template_path = plugin_dir_path(__FILE__) . 'templates';
    $loop_path = $template_path . '/loop';
    $css_path = plugin_dir_path(__FILE__) . 'assets/css';
    $js_path = plugin_dir_path(__FILE__) . 'assets/js';
    $includes_path = plugin_dir_path(__FILE__) . 'includes';
    
    $directories = array($template_path, $loop_path, $css_path, $js_path, $includes_path);
    
    foreach ($directories as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}
