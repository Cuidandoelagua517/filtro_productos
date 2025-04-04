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
                add_filter('woocommerce_locate_template', array($this, 'override_woocommerce_templates'), 999, 3);
                add_filter('wc_get_template_part', array($this, 'override_template_parts'), 999, 3);
 
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
        // Añadir este nuevo método
public function override_template_parts($template, $slug, $name) {
    if ($slug === 'content' && $name === 'product') {
        $plugin_template = WC_PRODUCTOS_TEMPLATE_TEMPLATES_DIR . 'content-product.php';
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }
    }
    return $template;
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
    
    // Verificar y crear CSS para la cuadrícula si no existe
    $force_grid_css_file = WC_PRODUCTOS_TEMPLATE_ASSETS_DIR . 'css/force-grid.css';
    if (!file_exists($force_grid_css_file)) {
        $this->create_default_grid_css_file();
    }
    
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
    
    // Verificar y crear JS para corrección de búsqueda
    $search_fix_js_file = WC_PRODUCTOS_TEMPLATE_ASSETS_DIR . 'js/search-bar-fix.js';
    if (!file_exists($search_fix_js_file)) {
        // Crear un archivo básico si no existe
        file_put_contents($search_fix_js_file, '/* JS para corregir problemas de la barra de búsqueda */');
    }
    
    // CSS para forzar cuadrícula con prioridad muy alta
    wp_enqueue_style(
        'wc-force-grid', 
        WC_PRODUCTOS_TEMPLATE_URL . 'assets/css/force-grid.css', 
        array(), 
        WC_PRODUCTOS_TEMPLATE_VERSION . '.' . time()
    );
    wp_style_add_data('wc-force-grid', 'priority', 99999);
    
    // Enqueue CSS principal con versión para evitar caché
    wp_enqueue_style(
        'wc-productos-template-styles', 
        WC_PRODUCTOS_TEMPLATE_URL . 'assets/css/productos-template.css', 
        array('wc-force-grid'), 
        WC_PRODUCTOS_TEMPLATE_VERSION . '.' . time()
    );
    
    // Enqueue JS para corrección de búsqueda con alta prioridad
    wp_enqueue_script(
        'wc-search-bar-fix', 
        WC_PRODUCTOS_TEMPLATE_URL . 'assets/js/search-bar-fix.js', 
        array('jquery'), 
        WC_PRODUCTOS_TEMPLATE_VERSION . '.' . time(),
        true
    );
    
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
    
    // JavaScript principal
    wp_enqueue_script(
        'wc-productos-template-script', 
        WC_PRODUCTOS_TEMPLATE_URL . 'assets/js/productos-template.js', 
        array('jquery', 'jquery-ui-slider'), 
        WC_PRODUCTOS_TEMPLATE_VERSION . '.' . time(),
        true
    );
    
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
    
    // Agregar CSS inline para asegurar que la cuadrícula se aplique
    $inline_css = "
    /* CSS inline para forzar cuadrícula */
    ul.products, .productos-grid {
        display: grid !important;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)) !important;
        gap: 20px !important;
    }
    ul.products li.product, .productos-grid li.product {
        width: 100% !important;
        margin: 0 !important;
        float: none !important;
        display: flex !important;
        flex-direction: column !important;
    }
    ul.products::before, ul.products::after, .productos-grid::before, .productos-grid::after {
        display: none !important;
    }
    ";
    wp_add_inline_style('wc-force-grid', $inline_css);
    
    // Añadir clase al body para namespace CSS
    add_filter('body_class', function($classes) {
        $classes[] = 'wc-productos-template-page';
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
 * Función para eliminar estilos forzados en páginas no relevantes
 * Esta función asegura que nuestros estilos solo se cargan en páginas adecuadas
 */
public function maybe_remove_template_styles() {
    // Si no estamos en una página de productos, desencolar nuestros estilos
    if (!$this->is_product_page() && !is_admin()) {
        wp_dequeue_style('wc-productos-template-styles');
        wp_dequeue_style('wc-force-grid');
        wp_dequeue_script('wc-productos-template-script');
        wp_dequeue_script('wc-search-bar-fix');
    }
}
      /**
 * Método mejorado para verificar si estamos en una página de productos
 */
private function is_product_page() {
    // Verificar si estamos en una página de WooCommerce
    $is_wc_page = is_shop() || is_product_category() || is_product_tag() || is_product() || is_woocommerce();
    
    // Verificar si estamos en una página con el shortcode
    $has_shortcode = false;
    if (is_a(get_post(), 'WP_Post')) {
        $has_shortcode = has_shortcode(get_post()->post_content, 'productos_personalizados');
    }
    
    // Verificar si estamos en una URL con parámetros específicos del plugin
    $has_plugin_params = isset($_GET['category']) || isset($_GET['grade']) || isset($_GET['min_volume']) || isset($_GET['max_volume']);
    
    return $is_wc_page || $has_shortcode || $has_plugin_params;
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
 * AJAX handler para filtrar productos - VERSIÓN MEJORADA PARA BÚSQUEDA
 */
public function ajax_filter_products() {
    // Verificar nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'productos_filter_nonce')) {
        wp_send_json_error(array('message' => 'Nonce inválido'));
        exit;
    }
    
    // Log para depuración
    error_log('Recibida solicitud AJAX para filtrar productos');
    
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
    $meta_query = array();
    
    // Filtrar por categoría (ahora con soporte para jerarquía)
    if (isset($_POST['category']) && !empty($_POST['category'])) {
        $categories = explode(',', sanitize_text_field($_POST['category']));
        if (!empty($categories)) {
            // Creamos un array para agrupar las categorías
            $category_terms = array();
            
            foreach ($categories as $cat_slug) {
                $term = get_term_by('slug', $cat_slug, 'product_cat');
                if ($term) {
                    $category_terms[] = $term->term_id;
                    
                    // Si es una categoría padre, también obtener sus hijos si no están explícitamente incluidos
                    $child_terms = get_terms(array(
                        'taxonomy' => 'product_cat',
                        'hide_empty' => true,
                        'parent' => $term->term_id
                    ));
                    
                    if (!empty($child_terms) && !is_wp_error($child_terms)) {
                        foreach ($child_terms as $child) {
                            // Verificar si la categoría hija ya está en la lista
                            if (!in_array($child->slug, $categories)) {
                                $category_terms[] = $child->term_id;
                            }
                        }
                    }
                }
            }
            
            // Usar 'IN' para permitir cualquiera de las categorías seleccionadas
            if (!empty($category_terms)) {
                $tax_query[] = array(
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => $category_terms,
                    'operator' => 'IN',
                    'include_children' => true
                );
            }
        }
    }
    
    // MEJORA: Búsqueda ampliada - ahora busca en más campos
    if (isset($_POST['search']) && !empty($_POST['search'])) {
        $search_term = sanitize_text_field($_POST['search']);
        
        // Log del término de búsqueda para depuración
        error_log('Término de búsqueda: ' . $search_term);
        
        // MEJORA 1: Búsqueda en múltiples metadatos
        $meta_query['relation'] = 'OR';
        
        // Búsqueda en SKU
        $meta_query[] = array(
            'key'     => '_sku',
            'value'   => $search_term,
            'compare' => 'LIKE'
        );
        
        // Búsqueda en volumen
        $meta_query[] = array(
            'key'     => '_volumen_ml',
            'value'   => $search_term,
            'compare' => 'LIKE'
        );
        
        // MEJORA 2: Búsqueda en atributos
        $product_attributes = array('pa_volumen', 'pa_grado');
        foreach ($product_attributes as $attribute) {
            // Buscar términos de atributo que coincidan
            $matching_terms = get_terms(array(
                'taxonomy'   => $attribute,
                'name__like' => $search_term,
                'hide_empty' => false
            ));
            
            if (!empty($matching_terms) && !is_wp_error($matching_terms)) {
                $term_ids = wp_list_pluck($matching_terms, 'term_id');
                $tax_query[] = array(
                    'relation' => 'OR',
                    array(
                        'taxonomy' => $attribute,
                        'field'    => 'term_id',
                        'terms'    => $term_ids,
                        'operator' => 'IN'
                    )
                );
            }
        }
        
        // MEJORA 3: Modificar la búsqueda principal para mayor relevancia
        $args['s'] = $search_term;
        
        // Búsqueda por título específicamente con mayor relevancia
        add_filter('posts_search', function($search, $wp_query) use ($search_term) {
            global $wpdb;
            
            if (!empty($search) && !empty($wp_query->query_vars['search_terms'])) {
                $title_like = '%' . $wpdb->esc_like($search_term) . '%';
                $search = str_replace(
                    'AND (', 
                    "AND ( ($wpdb->posts.post_title LIKE '$title_like') OR ", 
                    $search
                );
            }
            
            return $search;
        }, 10, 2);
        
        // Asignar la consulta de metadatos
        $args['meta_query'] = $meta_query;
    }
    
    // Añadir las consultas de taxonomía solo si hay filtros activos
    if (count($tax_query) > 1) {
        $args['tax_query'] = $tax_query;
    }
    
    // MEJORA: Ordenar por relevancia de búsqueda cuando hay un término de búsqueda
    if (isset($_POST['search']) && !empty($_POST['search'])) {
        $args['orderby'] = 'relevance';
    }
    
    // Aplicar filtros de WooCommerce
    $args = apply_filters('woocommerce_product_query_args', $args);
    
    // Log de la consulta final
    error_log('Argumentos de consulta final: ' . print_r($args, true));
    
    // Ejecutar la consulta
    $products_query = new WP_Query($args);
    
    // Log para depuración
    error_log('Consulta WP_Query ejecutada, encontrados: ' . $products_query->found_posts . ' productos');
    
    // Configurar las propiedades del bucle de WooCommerce
    wc_set_loop_prop('current_page', $page);
    wc_set_loop_prop('is_paginated', true);
    wc_set_loop_prop('page_template', 'productos-template');
    wc_set_loop_prop('per_page', get_option('posts_per_page'));
    wc_set_loop_prop('total', $products_query->found_posts);
    wc_set_loop_prop('total_pages', $products_query->max_num_pages);
    wc_set_loop_prop('columns', 3);
    
    // Capturar la salida de la cuadrícula de productos
    ob_start();
    
    if ($products_query->have_posts()) {
        woocommerce_product_loop_start();
        
        while ($products_query->have_posts()) {
            $products_query->the_post();
            global $product;
            
            // Asegurarse de que $product esté configurado correctamente
            if (!is_a($product, 'WC_Product')) {
                $product = wc_get_product(get_the_ID());
            }
            
            // Usar el template part correcto
            wc_get_template_part('content', 'product');
        }
        
        woocommerce_product_loop_end();
    } else {
        echo '<div class="woocommerce-info">' . 
            esc_html__('No se encontraron productos que coincidan con tu búsqueda.', 'wc-productos-template') . 
            '</div>';
    }
    
    // Obtener el HTML de productos
    $products_html = ob_get_clean();
    
    // Restablecer datos del post después de la consulta
    wp_reset_postdata();
    
    // Generar paginación
    ob_start();
    $this->render_pagination($products_query->max_num_pages, $page);
    $pagination = ob_get_clean();
    
    // Generar breadcrumb actualizado
    ob_start();
    $this->render_breadcrumb($page);
    $breadcrumb = ob_get_clean();
    
    // Incluir scripts de inicialización en la respuesta con mejoras para la búsqueda
    $init_script = '<script type="text/javascript">
    jQuery(document).ready(function($) {
        // Forzar cuadrícula después de la carga AJAX
        $(".wc-productos-template ul.products, .productos-grid").addClass("force-grid three-column-grid");
        
        // Vincular eventos de paginación
        $(".productos-pagination .page-number").on("click", function(e) {
            e.preventDefault();
            var page = $(this).data("page");
            if (typeof window.filterProducts === "function" && page) {
                window.filterProducts(page);
            }
            return false;
        });
        
        // MEJORA: Restaurar el término de búsqueda en el campo
        if (typeof window.currentFilters !== "undefined" && window.currentFilters.search) {
            $(".wc-productos-template .productos-search input").val(window.currentFilters.search);
        }
        
        // MEJORA: Resaltar la búsqueda en los resultados
        if (typeof window.currentFilters !== "undefined" && window.currentFilters.search) {
            var searchTerm = window.currentFilters.search;
            $(".wc-productos-template .producto-titulo a").each(function() {
                var text = $(this).text();
                var regex = new RegExp("(" + searchTerm + ")", "gi");
                if (text.match(regex)) {
                    $(this).html(text.replace(regex, "<mark>$1</mark>"));
                }
            });
        }
    });
    </script>';
    
    // Enviar respuesta completa
    wp_send_json_success(array(
        'products'     => $products_html,
        'pagination'   => $pagination,
        'breadcrumb'   => $breadcrumb,
        'init_script'  => $init_script,
        'total'        => $products_query->found_posts,
        'current_page' => $page,
        'max_pages'    => $products_query->max_num_pages,
        'search_term'  => isset($_POST['search']) ? sanitize_text_field($_POST['search']) : ''
    ));
    
    exit;
}
/**
 * Renderiza el breadcrumb con soporte para paginación - VERSIÓN CORREGIDA
 */
public function render_breadcrumb($current_page = 1) {
    // Si estamos en la página 1, usar el breadcrumb normal
    if ($current_page <= 1) {
        woocommerce_breadcrumb();
        return;
    }
    
    // Personalizar el breadcrumb para incluir la página actual
    $breadcrumb_args = apply_filters('woocommerce_breadcrumb_defaults', array(
        'delimiter'   => '&nbsp;&#47;&nbsp;',
        'wrap_before' => '<nav class="woocommerce-breadcrumb">',
        'wrap_after'  => '</nav>',
        'before'      => '',
        'after'       => '',
        'home'        => _x('Inicio', 'breadcrumb', 'woocommerce'),
    ));
    
    // Obtener el breadcrumb estándar
    $breadcrumbs = array();
    
    // Inicio
    $breadcrumbs[] = array(
        'name' => $breadcrumb_args['home'],
        'link' => get_home_url(),
    );
    
    // Tienda (si existe)
    $shop_page_id = wc_get_page_id('shop');
    if ($shop_page_id > 0 && $shop_page_id !== get_option('page_on_front')) {
        $breadcrumbs[] = array(
            'name' => get_the_title($shop_page_id),
            'link' => get_permalink($shop_page_id),
        );
    }
    
    // Categoría actual (si aplica)
    if (is_product_category()) {
        $current_term = get_queried_object();
        if ($current_term && isset($current_term->term_id)) {
            $breadcrumbs[] = array(
                'name' => $current_term->name,
                'link' => get_term_link($current_term),
            );
        }
    }
    
    // Agregar la página actual al final
    $breadcrumbs[] = array(
        'name' => sprintf(__('Página %d', 'wc-productos-template'), $current_page),
        'link' => '',
    );
    
    // Renderizar el breadcrumb personalizado
    echo $breadcrumb_args['wrap_before'];
    
    foreach ($breadcrumbs as $key => $breadcrumb) {
        echo $breadcrumb_args['before'];
        
        if (!empty($breadcrumb['link']) && $key < count($breadcrumbs) - 1) {
            echo '<a href="' . esc_url($breadcrumb['link']) . '">' . esc_html($breadcrumb['name']) . '</a>';
        } else {
            echo esc_html($breadcrumb['name']);
        }
        
        echo $breadcrumb_args['after'];
        
        if ($key < count($breadcrumbs) - 1) {
            echo $breadcrumb_args['delimiter'];
        }
    }
    
    echo $breadcrumb_args['wrap_after'];
}

/**
 * Renderiza la paginación de manera consistente - VERSIÓN CORREGIDA
 */
public function render_pagination($max_pages, $current_page = 1) {
    if ($max_pages <= 1) {
        return;
    }
    
    $per_page = get_option('posts_per_page');
    $total = wc_get_loop_prop('total', 0);
    
    echo '<div class="productos-pagination">';
    echo '<div class="pagination-info">';
    
    // Calcular el rango de productos mostrados
    $start = (($current_page - 1) * $per_page) + 1;
    $end = min($total, $current_page * $per_page);
    
    printf(
        esc_html__('Mostrando %1$d-%2$d de %3$d resultados', 'wc-productos-template'),
        $start,
        $end,
        $total
    );
    
    echo '</div>';
    echo '<div class="pagination-links">';
    
    // Botón "Anterior" si no estamos en la primera página
    if ($current_page > 1) {
        printf(
            '<a href="javascript:void(0);" class="page-number page-prev" data-page="%d" aria-label="%s">←</a>',
            $current_page - 1,
            esc_attr__('Página anterior', 'wc-productos-template')
        );
    }
    
    // Mostrar números de página
    $start = max(1, $current_page - 2);
    $end = min($max_pages, $current_page + 2);
    
    if ($start > 1) {
        echo '<a href="javascript:void(0);" class="page-number" data-page="1">1</a>';
        if ($start > 2) {
            echo '<span class="page-dots">...</span>';
        }
    }
    
    for ($i = $start; $i <= $end; $i++) {
        printf(
            '<a href="javascript:void(0);" class="page-number%s" data-page="%d">%d</a>',
            $i === $current_page ? ' active' : '',
            $i,
            $i
        );
    }
    
    if ($end < $max_pages) {
        if ($end < $max_pages - 1) {
            echo '<span class="page-dots">...</span>';
        }
        printf('<a href="javascript:void(0);" class="page-number" data-page="%d">%d</a>', $max_pages, $max_pages);
    }
    
    // Botón "Siguiente" si no estamos en la última página
    if ($current_page < $max_pages) {
        printf(
            '<a href="javascript:void(0);" class="page-number page-next" data-page="%d" aria-label="%s">→</a>',
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
