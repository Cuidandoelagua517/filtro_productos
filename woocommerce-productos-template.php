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
 *
 * Woo: 12345:a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6
 *
 * This plugin is compatible with WooCommerce HPOS (Custom Order Tables)
 *
 * WC tested up to: 8.0
 * Woo: 12345:a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6
 * WC requires at least: 5.0
 * WC requires PHP: 7.2
 */

// Si este archivo es llamado directamente, abortar.
if (!defined('WPINC')) {
    die;
}

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
                add_action('wp_enqueue_scripts', array($this, 'register_scripts'));
                
                // Sobreescribir templates de WooCommerce
                add_filter('woocommerce_locate_template', array($this, 'override_woocommerce_templates'), 10, 3);
                
                // Agregar AJAX handlers
                add_action('wp_ajax_productos_filter', array($this, 'ajax_filter_products'));
                add_action('wp_ajax_nopriv_productos_filter', array($this, 'ajax_filter_products'));
                
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
    // Modificar la condición para asegurar que los estilos se carguen
    if (is_shop() || is_product_category() || is_product_tag() || is_product() || 
        has_shortcode(get_post()->post_content ?? '', 'productos_personalizados') || 
        is_woocommerce()) {
        
        // CSS - Aumentar la prioridad para evitar sobreescrituras
      wp_enqueue_style('wc-productos-template-styles', 
        WC_PRODUCTOS_TEMPLATE_URL . 'assets/css/productos-template.css', 
        array(), 
        '1.0.3', // Bump version number
        'all'
    );
                
        // JavaScript con jQuery como dependencia
        wp_enqueue_script('wc-productos-template-script', 
            WC_PRODUCTOS_TEMPLATE_URL . 'assets/js/productos-template.js', 
            array('jquery'), 
            '1.0.1', 
            true
        );
                
        // Localizar script para AJAX
        wp_localize_script('wc-productos-template-script', 'WCProductosParams', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('productos_filter_nonce')
        ));
              // Add inline CSS for testing
    $custom_css = "
        .producto-card {
            border: 2px solid red !important;
            background-color: #f8f9fa !important;
            padding: 20px !important;
        }
    ";
    wp_add_inline_style('wc-productos-template-styles', $custom_css);
}      
        // Agregar soporte para la barra de rango
        wp_enqueue_script('jquery-ui-slider');
        wp_enqueue_style('jquery-ui-style', '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
    }

        /**
         * Sobreescribir templates de WooCommerce
         */
 public function override_woocommerce_templates($template, $template_name, $template_path) {
    // Verificar si estamos buscando un template específico
    $plugin_template_path = WC_PRODUCTOS_TEMPLATE_PATH . 'templates/';
    
    // Archivos de template a sobreescribir
    $templates_to_override = array(
        'archive-product.php',
        'content-product.php',
        'loop/loop-start.php',
        'loop/loop-end.php',
        'loop/pagination.php'
    );
    
    // Agregar logging para depuración (opcional)
    // error_log('Template solicitado: ' . $template_name);
    
    // Verificar si es un template que queremos sobreescribir
    if (in_array($template_name, $templates_to_override)) {
        $custom_template = $plugin_template_path . $template_name;
        
        // Si el archivo existe en nuestro plugin, usarlo
        if (file_exists($custom_template)) {
            // error_log('Usando template personalizado: ' . $custom_template);
            return $custom_template;
        }
    }
    
    // Si no, devolver el template original
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
    function wc_productos_template_activate() {
        // Asegurarse de que WooCommerce está activo
        if (!is_plugin_active('woocommerce/woocommerce.php') && current_user_can('activate_plugins')) {
            // Desactivar este plugin
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die('Este plugin requiere que WooCommerce esté instalado y activado.');
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

 function wc_productos_template_get_default_css() {
    return '
   /**

/* ===== 1. RESETEO Y BASE ===== */
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

/* ===== 2. CONTENEDOR PRINCIPAL ===== */
.productos-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 15px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
    color: #333;
}

/* ===== 3. HEADER CON TÍTULO Y BUSCADOR ===== */
.productos-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 0;
    margin-bottom: 25px;
    border-bottom: 1px solid #e2e2e2;
}

.productos-header h1 {
    font-size: 24px;
    font-weight: 600;
    margin: 0;
    color: #333;
}

/* Barra de búsqueda */
.productos-search {
    position: relative;
    width: 300px;
}

.productos-search input {
    width: 100%;
    padding: 10px 40px 10px 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.productos-search input:focus {
    outline: none;
    border-color: #0056b3;
    box-shadow: 0 0 0 3px rgba(0, 86, 179, 0.15);
}

.productos-search button {
    position: absolute;
    right: 0;
    top: 0;
    height: 100%;
    width: 40px;
    background-color: #0056b3;
    color: white;
    border: none;
    border-radius: 0 4px 4px 0;
    cursor: pointer;
    transition: background-color 0.2s;
}

.productos-search button:hover {
    background-color: #004494;
}

/* ===== 4. LAYOUT PRINCIPAL (DOS COLUMNAS) ===== */
.productos-layout {
    display: flex;
    gap: 30px;
    margin-bottom: 40px;
}

/* ===== 5. SIDEBAR DE FILTROS ===== */
.productos-sidebar {
    flex: 0 0 250px;
    width: 250px;
    background-color: #f8f9fa;
    border-radius: 6px;
    padding: 20px;
    border: 1px solid #e2e2e2;
    position: sticky;
    top: 20px;
}

.productos-sidebar h3 {
    font-size: 18px;
    margin: 0 0 20px 0;
    padding-bottom: 12px;
    border-bottom: 1px solid #e2e2e2;
    color: #333;
    font-weight: 600;
}

/* Grupos de filtros */
.filtro-grupo {
    margin-bottom: 25px;
}

.filtro-grupo:last-child {
    margin-bottom: 0;
}

.filtro-grupo h4 {
    font-size: 15px;
    margin: 0 0 12px 0;
    font-weight: 600;
    color: #555;
}

.filtro-option {
    display: flex;
    align-items: center;
    margin-bottom: 8px;
}

.filtro-option input[type="checkbox"] {
    margin-right: 10px;
    cursor: pointer;
}

.filtro-option label {
    font-size: 14px;
    cursor: pointer;
    user-select: none;
}

/* Slider de volumen */
.volumen-slider {
    margin-top: 15px;
}

.volumen-range {
    margin-bottom: 15px;
    height: 4px;
    background: #ddd;
    border-radius: 2px;
}

.ui-slider-range {
    background-color: #0056b3;
}

.ui-slider-handle {
    width: 16px !important;
    height: 16px !important;
    border-radius: 50% !important;
    background-color: #0056b3 !important;
    border: 2px solid #fff !important;
    cursor: pointer !important;
    box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.1);
    top: -0.5em !important;
}

.ui-slider-handle:focus {
    outline: none;
}

.volumen-values {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    color: #777;
}

/* ===== 6. ÁREA PRINCIPAL DE PRODUCTOS ===== */
.productos-main {
    flex: 1;
    min-width: 0; /* Importante para flex */
}

.productos-breadcrumb {
    margin-bottom: 20px;
    font-size: 13px;
    color: #777;
}

.productos-breadcrumb a {
    color: #0056b3;
    text-decoration: none;
}

.productos-breadcrumb a:hover {
    text-decoration: underline;
}

/* Cuadrícula de productos */
.productos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

/* ===== 7. TARJETA DE PRODUCTO ===== */
.producto-card {
    background-color: #fff;
    border: 1px solid #e2e2e2;
    border-radius: 6px;
    padding: 20px;
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    height: 100%;
    overflow: hidden;
}

.producto-card:hover {
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
    transform: translateY(-3px);
}

.producto-imagen {
    height: 180px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 15px;
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
    top: 10px;
    right: 10px;
    padding: 4px 10px;
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
    margin: 0 0 10px 0;
    color: #333;
    line-height: 1.4;
}

.producto-detalles {
    font-size: 13px;
    color: #777;
    margin: 0 0 15px 0;
    line-height: 1.4;
}

.producto-precio {
    font-size: 18px;
    font-weight: 700;
    color: #0056b3;
    margin: auto 0 15px 0;
}

.producto-precio del {
    font-size: 14px;
    color: #999;
    font-weight: normal;
    margin-right: 5px;
}

.producto-precio ins {
    text-decoration: none;
}

.producto-boton {
    background-color: #0056b3;
    color: white;
    border: none;
    border-radius: 4px;
    padding: 10px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.2s;
    width: 100%;
    text-align: center;
}

.producto-boton:hover {
    background-color: #004494;
}

/* ===== 8. PAGINACIÓN ===== */
.productos-pagination {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e2e2e2;
}

.pagination-info {
    font-size: 14px;
    color: #777;
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
    border-radius: 4px;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s;
}

.page-number.active {
    background-color: #0056b3;
    color: white;
    font-weight: 600;
}

.page-number:not(.active) {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    color: #555;
}

.page-number:not(.active):hover {
    background-color: #e9ecef;
}

/* ===== 9. RESPONSIVE ===== */
@media (max-width: 991px) {
    .productos-grid {
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    }
}

@media (max-width: 768px) {
    .productos-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .productos-header h1 {
        margin-bottom: 15px;
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
        margin-bottom: 20px;
        position: static;
        max-width: 100%;
    }
    
    .productos-grid {
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    }
    
    .productos-pagination {
        flex-direction: column;
        gap: 15px;
    }
}

@media (max-width: 480px) {
    .productos-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }
    
    .producto-card {
        padding: 15px;
    }
    
    .producto-imagen {
        height: 140px;
    }
    
    .producto-precio {
        font-size: 16px;
    }
    
    .producto-titulo {
        font-size: 14px;
    }
}

/* ===== 10. ANIMACIONES Y EFECTOS ===== */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.productos-grid {
    animation: fadeIn 0.5s ease;
}

/* Efecto de carga */
.loading {
    text-align: center;
    padding: 20px;
    color: #777;
    font-style: italic;
}

/* ===== 11. MENSAJES DE NOTIFICACIÓN ===== */
.wc-message-success {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background-color: #e6f4ea;
    color: #137333;
    padding: 12px 20px;
    border-radius: 4px;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
    z-index: 9999;
    animation: fadeIn 0.3s, fadeOut 0.3s 2.7s forwards;
    font-size: 14px;
}

@keyframes fadeOut {
    from { opacity: 1; transform: translateY(0); }
    to { opacity: 0; transform: translateY(20px); }
}

/* Mejoras para accesibilidad */
.filtro-option input[type="checkbox"]:focus + label {
    text-decoration: underline;
}

.productos-search input:focus,
.producto-boton:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(0, 86, 179, 0.25);
}

/* Personalización de la barra de jQuery UI */
.ui-slider-horizontal .ui-slider-handle {
    margin-left: -8px;
}

/* Sombra para resaltar el sidebar cuando hay scroll */
@media (min-width: 769px) {
    .productos-container.scrolled .productos-sidebar {
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
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
            
            // Función para filtrar productos
            function filterProducts(page = 1) {
                if (ajaxRunning) return;
                
                // Mostrar indicador de carga
                $('.productos-list').append('<div class=\"loading\">Cargando productos...</div>');
                
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
                            // Actualizar lista de productos
                            $('.productos-list').html(response.data.products);
                            
                            // Actualizar paginación
                            $('.productos-pagination').html(response.data.pagination);
                            
                            // Actualizar contador de resultados
                            $('.pagination-info').text('Mostrando 1-' + 
                                Math.min(response.data.total, $('.producto-card').length) + 
                                ' de ' + response.data.total + ' resultados');
                            
                            // Animar scroll hacia arriba
                            $('html, body').animate({
                                scrollTop: $('.productos-list').offset().top - 100
                            }, 500);
                        } else {
                            console.error('Error al filtrar productos');
                        }
                        
                        // Marcar que AJAX ha terminado
                        ajaxRunning = false;
                    },
                    error: function() {
                        console.error('Error en la petición AJAX');
                        $('.loading').remove();
                        ajaxRunning = false;
                    }
                });
            }
            
            // Event listeners para filtros
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
            
            // Evento para búsqueda
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
            $(document).on('click', '.page-number:not(.active)', function() {
                var page = $(this).data('page') || 1;
                filterProducts(page);
            });
            
            // Delegación de eventos para botón Agregar al carrito
            $(document).on('click', '.producto-boton', function(e) {
                e.preventDefault();
                var productId = $(this).data('product-id');
                
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
                            
                            // Mostrar mensaje de éxito
                            $('body').append('<div class=\"wc-message-success\">Producto añadido al carrito</div>');
                            setTimeout(function() {
                                $('.wc-message-success').fadeOut().remove();
                            }, 3000);
                        }
                    }
                });
            });
        });
    ";
}
}
