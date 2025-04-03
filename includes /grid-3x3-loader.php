<?php
/**
 * Archivo para cargar las modificaciones de grid 3x3
 * Guardar como: includes/grid-3x3-loader.php
 */

if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente
}

/**
 * Clase para cargar las modificaciones de grid 3x3
 */
class WC_Productos_Grid_3x3 {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Cargar CSS con alta prioridad
        add_action('wp_enqueue_scripts', array($this, 'enqueue_grid_3x3_styles'), 99999);
        
        // Modificar la consulta de productos
        add_action('woocommerce_product_query', array($this, 'limit_products_per_page'), 20);
        
        // Añadir CSS inline para garantizar la visualización
        add_action('wp_head', array($this, 'add_inline_styles'), 999);
        
        // Modificar shortcode
        add_filter('shortcode_atts_productos_personalizados', array($this, 'modify_shortcode_atts'), 10, 3);
    }
    
    /**
     * Cargar estilos CSS específicos
     */
    public function enqueue_grid_3x3_styles() {
        // Solo en páginas relevantes
        if (!is_woocommerce() && !is_product_category() && !is_product_tag() && !is_shop() && 
            !is_page() && !has_shortcode(get_post()->post_content ?? '', 'productos_personalizados')) {
            return;
        }
        
        // Crear archivo grid-3x3.css si no existe
        $css_file = WC_PRODUCTOS_TEMPLATE_ASSETS_DIR . 'css/grid-3x3.css';
        if (!file_exists($css_file)) {
            $this->create_grid_3x3_css();
        }
        
        // Cargar CSS con alta prioridad
        wp_enqueue_style(
            'wc-productos-grid-3x3',
            WC_PRODUCTOS_TEMPLATE_URL . 'assets/css/grid-3x3.css',
            array('wc-productos-template-styles', 'wc-force-grid'),
            WC_PRODUCTOS_TEMPLATE_VERSION . '.' . time()
        );
        
        // Forzar la prioridad
        wp_style_add_data('wc-productos-grid-3x3', 'priority', 99999);
    }
    
    /**
     * Crear archivo CSS grid-3x3.css
     */
    private function create_grid_3x3_css() {
        $css_dir = WC_PRODUCTOS_TEMPLATE_ASSETS_DIR . 'css/';
        
        // Asegurarse de que el directorio existe
        if (!file_exists($css_dir)) {
            mkdir($css_dir, 0755, true);
        }
        
        // Contenido del CSS
        $css = <<<CSS
/**
 * GRID-3x3.CSS
 * Estilos forzados para mostrar exactamente 3 columnas y máximo 9 productos
 *
 * @package WC_Productos_Template
 */

/* Base del grid - forzar 3 columnas en todos los casos */
.wc-productos-template ul.products,
.productos-grid,
.woocommerce ul.products {
    display: grid !important;
    grid-template-columns: repeat(3, 1fr) !important;
    gap: 20px !important;
    width: 100% !important;
    max-width: 1200px !important;
    margin: 0 auto 30px auto !important;
    padding: 0 !important;
    list-style: none !important;
    float: none !important;
    clear: both !important;
}

/* Forzar estilos de producto */
.wc-productos-template ul.products li.product,
.productos-grid li.product,
.woocommerce ul.products li.product {
    width: 100% !important;
    margin: 0 0 20px 0 !important;
    padding: 0 !important;
    float: none !important;
    clear: none !important;
    box-sizing: border-box !important;
    display: flex !important;
    flex-direction: column !important;
    height: 100% !important;
}

/* Soporte responsive pero manteniendo el diseño 3x3 hasta tabletas */
@media (max-width: 768px) {
    .wc-productos-template ul.products,
    .productos-grid,
    .woocommerce ul.products {
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 15px !important;
    }
}

@media (max-width: 480px) {
    .wc-productos-template ul.products,
    .productos-grid,
    .woocommerce ul.products {
        grid-template-columns: repeat(1, 1fr) !important;
        gap: 10px !important;
    }
}

/* Ocultar productos excedentes en caso de que se muestren más de 9 */
.wc-productos-template ul.products li.product:nth-child(n+10),
.productos-grid li.product:nth-child(n+10),
.woocommerce ul.products li.product:nth-child(n+10) {
    display: none !important;
}

/* Aplicar al shortcode personalizado */
.productos-container.wc-productos-template .productos-grid {
    grid-template-columns: repeat(3, 1fr) !important;
}

/* Eliminar floats y clearfix que pueden romper la cuadrícula */
.wc-productos-template ul.products::before,
.wc-productos-template ul.products::after,
.productos-grid::before,
.productos-grid::after,
.woocommerce ul.products::before,
.woocommerce ul.products::after {
    display: none !important;
    content: none !important;
    clear: none !important;
}
CSS;
        
        // Guardar el archivo
        file_put_contents($css_dir . 'grid-3x3.css', $css);
    }
    
    /**
     * Limitar productos por página a 9
     */
    public function limit_products_per_page($query) {
        if (!is_admin() && $query->is_main_query() && 
            (is_shop() || is_product_category() || is_product_tag() || is_search())) {
            $query->set('posts_per_page', 9);
        }
    }
    
    /**
     * Añadir CSS inline para garantizar visualización
     */
    public function add_inline_styles() {
        // Solo en páginas relevantes
        if (!is_woocommerce() && !is_product_category() && !is_product_tag() && !is_shop() && 
            !is_page() && !has_shortcode(get_post()->post_content ?? '', 'productos_personalizados')) {
            return;
        }
        
        echo '<style id="wc-productos-grid-3x3-inline">
        /* Estilos inline para forzar grid 3x3 */
        .wc-productos-template ul.products,
        .productos-grid,
        .woocommerce ul.products {
            display: grid !important;
            grid-template-columns: repeat(3, 1fr) !important;
            gap: 20px !important;
            max-width: 1200px !important;
            margin: 0 auto 30px auto !important;
        }
        
        /* Ocultar productos excedentes */
        .wc-productos-template ul.products li.product:nth-child(n+10),
        .productos-grid li.product:nth-child(n+10),
        .woocommerce ul.products li.product:nth-child(n+10) {
            display: none !important;
        }
        
        @media (max-width: 768px) {
            .wc-productos-template ul.products,
            .productos-grid,
            .woocommerce ul.products {
                grid-template-columns: repeat(2, 1fr) !important;
            }
        }
        
        @media (max-width: 480px) {
            .wc-productos-template ul.products,
            .productos-grid,
            .woocommerce ul.products {
                grid-template-columns: repeat(1, 1fr) !important;
            }
        }
        </style>';
    }
    
    /**
     * Forzar valores del shortcode
     */
    public function modify_shortcode_atts($out, $pairs, $atts) {
        $out['per_page'] = 9; // Forzar 9 productos independientemente del valor pasado
        return $out;
    }
}

// Inicializar la clase
new WC_Productos_Grid_3x3();
