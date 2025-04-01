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
            // Solo cargar en la página de tienda y categorías de productos
            if (is_shop() || is_product_category()) {
                // CSS
                wp_enqueue_style('wc-productos-template-styles', 
                    WC_PRODUCTOS_TEMPLATE_URL . 'assets/css/productos-template.css', 
                    array(), 
                    '1.0.0'
                );
                
                // JavaScript con jQuery como dependencia
                wp_enqueue_script('wc-productos-template-script', 
                    WC_PRODUCTOS_TEMPLATE_URL . 'assets/js/productos-template.js', 
                    array('jquery'), 
                    '1.0.0', 
                    true
                );
                
                // Localizar script para AJAX
                wp_localize_script('wc-productos-template-script', 'WCProductosParams', array(
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('productos_filter_nonce')
                ));
                
                // Agregar soporte para la barra de rango
                wp_enqueue_script('jquery-ui-slider');
                wp_enqueue_style('jquery-ui-style', '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
            }
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
            
            // Verificar si es un template que queremos sobreescribir
            if (in_array($template_name, $templates_to_override)) {
                $custom_template = $plugin_template_path . $template_name;
                
                // Si el archivo existe en nuestro plugin, usarlo
                if (file_exists($custom_template)) {
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

    /**
     * CSS por defecto
     */
    function wc_productos_template_get_default_css() {
        return '
            /* Estilos para el template personalizado de productos */
/* Reseteo y base */
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    padding: 0;
}

/* Contenedor principal */
.productos-container {
    max-width: 1200px;
    margin: 0 auto;
    font-family: Arial, sans-serif;
}

/* Header con título y buscador */
.productos-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    background-color: #ffffff;
    border-bottom: 1px solid #dee2e6;
    margin-bottom: 20px;
}

.productos-header h1 {
    font-size: 24px;
    margin: 0;
}

/* Barra de búsqueda */
.productos-search {
    position: relative;
    width: 300px;
}

.productos-search input {
    width: 100%;
    padding: 8px 35px 8px 12px;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    font-size: 14px;
}

.productos-search button {
    position: absolute;
    right: 0;
    top: 0;
    height: 100%;
    width: 35px;
    background-color: #0056b3;
    color: white;
    border: none;
    border-radius: 0 4px 4px 0;
    cursor: pointer;
}

/* Layout principal - IMPORTANTE: Esto establece la estructura de dos columnas */
.productos-layout {
    display: flex;
    gap: 20px;
}

/* Sidebar de filtros */
.productos-sidebar {
    width: 250px;
    flex-shrink: 0;
    background-color: #ffffff;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 15px;
    margin-bottom: 20px;
    height: fit-content;
}

.productos-sidebar h3 {
    font-size: 16px;
    margin-top: 0;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #dee2e6;
}

/* Grupos de filtros */
.filtro-grupo {
    margin-bottom: 20px;
}

.filtro-grupo h4 {
    font-size: 14px;
    margin-top: 0;
    margin-bottom: 10px;
}

.filtro-option {
    display: flex;
    align-items: center;
    margin-bottom: 8px;
}

.filtro-option input[type="checkbox"] {
    margin-right: 10px;
}

/* Slider de volumen */
.volumen-slider {
    margin-top: 10px;
}

.volumen-range {
    margin-bottom: 10px;
    height: 6px;
}

.volumen-values {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: #666;
}

/* Área principal de productos */
.productos-main {
    flex: 1;
    min-width: 0; /* Importante para evitar que el flex sobrepase el ancho del contenedor */
}

.productos-breadcrumb {
    margin-bottom: 15px;
    font-size: 13px;
    color: #666;
}

/* Cuadrícula de productos */
.productos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

/* Tarjeta de producto */
.producto-card {
    background-color: #ffffff;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 15px;
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
}

.producto-card:hover {
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.producto-imagen {
    height: 160px;
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
}

.producto-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    padding: 3px 8px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
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
    font-size: 15px;
    font-weight: bold;
    margin: 0 0 8px 0;
}

.producto-detalles {
    font-size: 13px;
    color: #666;
    margin: 0 0 10px 0;
}

.producto-precio {
    font-size: 18px;
    font-weight: bold;
    color: #0056b3;
    margin: 0 0 15px 0;
    margin-top: auto; /* Empuja el precio hacia abajo */
}

.producto-boton {
    background-color: #0056b3;
    color: white;
    border: none;
    border-radius: 4px;
    padding: 8px 0;
    font-size: 14px;
    cursor: pointer;
    transition: background-color 0.2s;
}

.producto-boton:hover {
    background-color: #003d82;
}

/* Paginación */
.productos-pagination {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 20px;
}

.pagination-info {
    font-size: 13px;
    color: #666;
}

.pagination-links {
    display: flex;
}

.page-number {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 3px;
    border-radius: 4px;
    font-size: 14px;
    cursor: pointer;
}

.page-number.active {
    background-color: #0056b3;
    color: white;
}

.page-number:not(.active) {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
}

/* Responsive */
@media (max-width: 768px) {
    .productos-layout {
        flex-direction: column;
    }
    
    .productos-sidebar {
        width: 100%;
        margin-bottom: 20px;
    }
    
    .productos-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .productos-header h1 {
        margin-bottom: 15px;
    }
    
    .productos-search {
        width: 100%;
    }
    
    .productos-grid {
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    }
}

@media (max-width: 480px) {
    .productos-grid {
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }
    
    .producto-card {
        padding: 10px;
    }
    
    .producto-imagen {
        height: 120px;
    }
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
