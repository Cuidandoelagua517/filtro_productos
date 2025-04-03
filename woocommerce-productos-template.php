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

// Comprueba si un directorio existe y lo crea si no
function wc_productos_create_directory_if_not_exists($path) {
    if (!file_exists($path)) {
        return mkdir($path, 0755, true);
    }
    return true;
}

// Definir constantes de directorios
define('WC_PRODUCTOS_TEMPLATE_DIR', plugin_dir_path(__FILE__));
define('WC_PRODUCTOS_TEMPLATE_URL', plugin_dir_url(__FILE__));
define('WC_PRODUCTOS_TEMPLATE_INCLUDES_DIR', WC_PRODUCTOS_TEMPLATE_DIR . 'includes/');
define('WC_PRODUCTOS_TEMPLATE_TEMPLATES_DIR', WC_PRODUCTOS_TEMPLATE_DIR . 'templates/');
define('WC_PRODUCTOS_TEMPLATE_ASSETS_DIR', WC_PRODUCTOS_TEMPLATE_DIR . 'assets/');

if (!class_exists('WC_Productos_Template')) {

    class WC_Productos_Template {

        /**
         * Constructor
         */
        public function __construct() {
            // Verificar si WooCommerce está activo
            if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
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
                
                // Cargar clases adicionales si existen
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
         * Cargar clases adicionales si existen
         */
        private function load_classes() {
            // Verificar y crear directorios de plugin
            $this->create_plugin_directories();
            
            // Definir archivos de clase
            $class_files = array(
                WC_PRODUCTOS_TEMPLATE_INCLUDES_DIR . 'class-productos-metabox.php',
                WC_PRODUCTOS_TEMPLATE_INCLUDES_DIR . 'class-productos-orders.php'
            );
            
            // Incluir archivos de clase si existen
            foreach ($class_files as $class_file) {
                if (file_exists($class_file)) {
                    require_once $class_file;
                } else {
                    // Mostrar advertencia en el admin
                    add_action('admin_notices', function() use ($class_file) {
                        ?>
                        <div class="notice notice-warning is-dismissible">
                            <p><?php printf(
                                esc_html__('Advertencia: El archivo %s no existe. Algunas funciones del plugin WC Productos Template podrían no estar disponibles.', 'wc-productos-template'),
                                '<code>' . esc_html(basename($class_file)) . '</code>'
                            ); ?></p>
                        </div>
                        <?php
                    });
                }
            }
        }

        /**
         * Inicializar el plugin
         */
        public function init() {
            // Crear directorio de templates si no existe
            $this->create_plugin_directories();
            
            // Forzar visualización en cuadrícula con alta prioridad
            add_action('wp_enqueue_scripts', array($this, 'force_grid_styles'), 99999);
        }
        
        /**
         * Crear directorios del plugin si no existen
         */
        private function create_plugin_directories() {
            // Crear directorio de includes
            wc_productos_create_directory_if_not_exists(WC_PRODUCTOS_TEMPLATE_INCLUDES_DIR);
            
            // Crear directorio de templates y subdirectorios
            wc_productos_create_directory_if_not_exists(WC_PRODUCTOS_TEMPLATE_TEMPLATES_DIR);
            wc_productos_create_directory_if_not_exists(WC_PRODUCTOS_TEMPLATE_TEMPLATES_DIR . 'loop');
            
            // Crear directorios para assets
            wc_productos_create_directory_if_not_exists(WC_PRODUCTOS_TEMPLATE_ASSETS_DIR);
            wc_productos_create_directory_if_not_exists(WC_PRODUCTOS_TEMPLATE_ASSETS_DIR . 'css');
            wc_productos_create_directory_if_not_exists(WC_PRODUCTOS_TEMPLATE_ASSETS_DIR . 'js');
            
            // Crear archivos de clase vacíos si no existen
            if (!file_exists(WC_PRODUCTOS_TEMPLATE_INCLUDES_DIR . 'class-productos-metabox.php')) {
                $this->create_empty_class_file('class-productos-metabox.php', 'WC_Productos_Template_Metabox');
            }
            
            if (!file_exists(WC_PRODUCTOS_TEMPLATE_INCLUDES_DIR . 'class-productos-orders.php')) {
                $this->create_empty_class_file('class-productos-orders.php', 'WC_Productos_Template_Orders');
            }
        }
        
        /**
         * Crear un archivo de clase vacío
         */
        private function create_empty_class_file($filename, $classname) {
            $content = "<?php\n/**\n * Clase {$classname} (Versión Mínima)\n */\n\nif (!class_exists('{$classname}')) {\n\n    class {$classname} {\n        \n        /**\n         * Constructor\n         */\n        public function __construct() {\n            // Funcionalidad mínima\n        }\n    }\n    \n    // Inicializar la clase\n    new {$classname}();\n}\n";
            file_put_contents(WC_PRODUCTOS_TEMPLATE_INCLUDES_DIR . $filename, $content);
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
            
            // Verificar y crear CSS principal si no existe
            $productos_css_file = WC_PRODUCTOS_TEMPLATE_ASSETS_DIR . 'css/productos-template.css';
            if (!file_exists($productos_css_file)) {
                $this->create_default_css_file($productos_css_file);
            }
            
            // Verificar y crear JS principal si no existe
            $productos_js_file = WC_PRODUCTOS_TEMPLATE_ASSETS_DIR . 'js/productos-template.js';
            if (!file_exists($productos_js_file)) {
                $this->create_default_js_file($productos_js_file);
            }
            
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
            
            // Script básico si no existe el archivo
           if (!file_exists($productos_js_file)) {
    wp_add_inline_script('wc-productos-template-script', "
        jQuery(document).ready(function($) {
            console.log('WC Productos Template inicializado');
            
            // Evento de búsqueda
            $('.productos-search form').on('submit', function(e) {
                e.preventDefault();
                alert('Funcionalidad de búsqueda no implementada completamente');
            });
        });
    ");
}
            
            // CSS básico si no existe el archivo
            if (!file_exists(WC_PRODUCTOS_TEMPLATE_ASSETS_DIR . 'css/force-grid.css')) {
                $this->create_default_grid_css_file();
            }
            
            // Localizar script para AJAX
            wp_localize_script('wc-productos-template-script', 'WCProductosParams', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('productos_filter_nonce'),
                'current_page' => $current_page,
                'products_per_page' => get_option('posts_per_page'),
                'total_products' => $this->get_total_products(),
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
         * Obtener el número total de productos
         */
        private function get_total_products() {
            $total = wc_get_loop_prop('total', 0);
            if (!$total) {
                // Si no está definido, intentar obtener total desde query global
                global $wp_query;
                if (isset($wp_query) && is_object($wp_query)) {
                    $total = $wp_query->found_posts;
                }
            }
            return $total ? $total : 0;
        }
        
        /**
         * Crear archivo CSS por defecto
         */
        private function create_default_css_file($file_path) {
            $css = "/**\n * Estilos básicos para productos\n */\n\n.productos-grid {\n  display: grid;\n  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));\n  gap: 20px;\n}\n\n.producto-card {\n  border: 1px solid #e2e2e2;\n  border-radius: 8px;\n  overflow: hidden;\n  transition: all 0.3s ease;\n}\n\n.producto-card:hover {\n  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);\n  transform: translateY(-3px);\n}\n";
            file_put_contents($file_path, $css);
        }
        
        /**
         * Crear archivo CSS para la cuadrícula por defecto
         */
        private function create_default_grid_css_file() {
            $file_path = WC_PRODUCTOS_TEMPLATE_ASSETS_DIR . 'css/force-grid.css';
            $css = "/**\n * Forzar cuadrícula\n */\n\nul.products, .productos-grid {\n  display: grid !important;\n  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)) !important;\n  gap: 20px !important;\n  width: 100% !important;\n  margin: 0 0 30px 0 !important;\n  padding: 0 !important;\n  list-style: none !important;\n}\n\nul.products li.product, .productos-grid li.product {\n  width: 100% !important;\n  margin: 0 0 20px 0 !important;\n  padding: 0 !important;\n  float: none !important;\n  clear: none !important;\n}";
            file_put_contents($file_path, $css);
        }
        
        /**
         * Crear archivo JS por defecto
         */
        private function create_default_js_file($file_path) {
            $js = "/**\n * Script básico para productos\n */\n\njQuery(document).ready(function($) {\n  // Forzar cuadrícula\n  $('.wc-productos-template ul.products, .productos-grid').css({\n    'display': 'grid',\n    'grid-template-columns': 'repeat(auto-fill, minmax(220px, 1fr))',\n    'gap': '20px'\n  });\n  \n  // Evento de búsqueda\n  $('.productos-search form').on('submit', function(e) {\n    e.preventDefault();\n    var searchTerm = $(this).find('input').val();\n    console.log('Buscando: ' + searchTerm);\n  });\n});\n";
            file_put_contents($file_path, $js);
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
            
            // Verificar si existe el archivo CSS
            $force_grid_css = WC_PRODUCTOS_TEMPLATE_ASSETS_DIR . 'css/force-grid.css';
            
            if (file_exists($force_grid_css)) {
                // Cargar CSS específico para forzar cuadrícula
                wp_enqueue_style(
                    'wc-force-grid',
                    WC_PRODUCTOS_TEMPLATE_URL . 'assets/css/force-grid.css',
                    array('wc-productos-template-styles'),
                    WC_PRODUCTOS_TEMPLATE_VERSION . '.' . time()
                );
                
                wp_style_add_data('wc-force-grid', 'priority', 9999);
            } else {
                // Si no existe, usar CSS inline
                $css = "ul.products, .productos-grid { display: grid !important; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)) !important; gap: 20px !important; }";
                wp_add_inline_style('wc-productos-template-styles', $css);
            }
            

        /**
         * Sobreescribir templates de WooCommerce selectivamente
         */
        public function override_woocommerce_templates($template, $template_name, $template_path) {
            // Lista de templates que queremos sobrescribir
            $override_templates = array(
                'content-product.php',
                'loop/loop-start.php',
                'loop/loop-end.php',
                'loop/pagination.php',
                'loop/orderby.php',
                'loop/result-count.php',
                'archive-product.php'
            );
            
            // Solo sobrescribir los templates específicos
            if (in_array($template_name, $override_templates)) {
                $plugin_template = WC_PRODUCTOS_TEMPLATE_TEMPLATES_DIR . $template_name;
                
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
                $custom_template = WC_PRODUCTOS_TEMPLATE_TEMPLATES_DIR . 'archive-product.php';
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
            
            // Búsqueda
            if (isset($_POST['search']) && !empty($_POST['search'])) {
                $search_term = sanitize_text_field($_POST['search']);
                
                // Incluir búsqueda en metadatos
                $meta_query[] = array(
                    'relation' => 'OR',
                    array(
                        'key'     => '_sku',
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
            wc_set_loop_prop('columns', 4);
            
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
            
            // Verificar si existe el template del shortcode
            $shortcode_template = WC_PRODUCTOS_TEMPLATE_TEMPLATES_DIR . 'productos-shortcode.php';
            
            if (file_exists($shortcode_template)) {
                // Incluir template de página de productos
                ob_start();
                include($shortcode_template);
                return ob_get_clean();
            } else {
                // Alternativa básica si no existe el template
                ob_start();
                
                echo '<div class="productos-container wc-productos-template">';
                echo '<h2>Productos</h2>';
                
                // Query básica de productos
                $args = array(
                    'post_type' => 'product',
                    'posts_per_page' => $atts['per_page'],
                    'paged' => get_query_var('paged', 1)
                );
                
                if (!empty($atts['category'])) {
                    $args['tax_query'] = array(
                        array(
                            'taxonomy' => 'product_cat',
                            'field' => 'slug',
                            'terms' => $atts['category']
                        )
                    );
                }
                
                $products_query = new WP_Query($args);
                
                if ($products_query->have_posts()) {
                    woocommerce_product_loop_start();
                    
                    while ($products_query->have_posts()) {
                        $products_query->the_post();
                        wc_get_template_part('content', 'product');
                    }
                    
                    woocommerce_product_loop_end();
                    
                    // Paginación simple
                    echo '<div class="productos-pagination">';
                    echo paginate_links(array(
                        'total' => $products_query->max_num_pages,
                        'current' => max(1, get_query_var('paged'))
                    ));
                    echo '</div>';
                    
                    wp_reset_postdata();
                } else {
                    echo '<p>' . esc_html__('No se encontraron productos.', 'wc-productos-template') . '</p>';
                }
                
                echo '</div>';
                
                return ob_get_clean();
            }
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
    $directories = array(
        plugin_dir_path(__FILE__) . 'templates',
        plugin_dir_path(__FILE__) . 'templates/loop',
        plugin_dir_path(__FILE__) . 'assets/css',
        plugin_dir_path(__FILE__) . 'assets/js',
        plugin_dir_path(__FILE__) . 'includes'
    );
    
    foreach ($directories as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}

/**
 * Función para cargar el corrector de transparencia
 * MOVIDA FUERA DE LA CLASE
 */
function wc_productos_transparency_fix() {
    // Solo cargar si el archivo existe
    $fix_css_file = plugin_dir_path(__FILE__) . 'assets/css/transparency-fix.css';
    
    if (file_exists($fix_css_file)) {
        wp_enqueue_style(
            'wc-transparency-fix',
            plugin_dir_url(__FILE__) . 'assets/css/transparency-fix.css',
            array(),
            time(), // Usar timestamp para evitar caché
            'all'
        );
        
        // Asignar prioridad extremadamente alta
        wp_style_add_data('wc-transparency-fix', 'priority', 99999);
    } else {
        // Si el archivo no existe, usar estilos inline
        $css_fix = "
        /* Fix de emergencia para problemas de transparencia */
        body, img, header, footer, .site-header, .site-footer, #masthead, #colophon, .logo, .brand-logo {
            opacity: 1 !important;
            visibility: visible !important;
            background-color: initial !important;
            filter: none !important;
        }
        ";
        wp_add_inline_style('wp-block-library', $css_fix);
    }
    
    // Script para corregir transparencia con JavaScript
    wp_add_inline_script('jquery', "
    jQuery(document).ready(function($) {
        // Corregir imágenes transparentes
        $('img').css({
            'opacity': '1',
            'visibility': 'visible'
        });
        
        // Corregir elementos de header y footer
        $('header, footer, #masthead, #colophon, .site-header, .site-footer').css({
            'opacity': '1',
            'visibility': 'visible',
            'background-color': ''
        });
        
        // Corregir logos de empresas
        $('.logo, .brand-logo, .company-logo, .partner-logo').css({
            'opacity': '1',
            'visibility': 'visible',
            'filter': 'none'
        });
    });
    ");
}

// Agregar la función al hook wp_enqueue_scripts con prioridad muy alta
add_action('wp_enqueue_scripts', 'wc_productos_transparency_fix', 99999);

/**
 * Función para corregir estilos en el admin
 * MOVIDA FUERA DE LA CLASE
 */
function wc_productos_admin_fix() {
    // Solo si estamos en pantallas de administración de WooCommerce
    $screen = get_current_screen();
    if (!$screen || !in_array($screen->id, array('woocommerce_page_wc-settings', 'edit-product'))) {
        return;
    }
    
    echo '<style>
    /* Corregir problemas de administración */
    body, img, header, footer, .wp-header-end, #wpcontent, #wpfooter {
        opacity: 1 !important;
        visibility: visible !important;
    }
    </style>';
}

// Agregar la función al hook admin_head
add_action('admin_head', 'wc_productos_admin_fix');
