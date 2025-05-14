<?php
/**
 * Módulo Core - Funcionalidad central del plugin
 * 
 * @package WC_Productos_Template
 */

class WC_Productos_Template_Core {
    
    /**
     * El cargador que es responsable de mantener y registrar todos los hooks del plugin.
     *
     * @var WC_Productos_Template_Loader $loader
     */
    private $loader;
    
    /**
     * Inicializar el módulo Core.
     *
     * @param WC_Productos_Template_Loader $loader Cargador de plugins.
     */
    public function __construct($loader) {
        $this->loader = $loader;
    }
    
    /**
     * Inicializar los hooks del módulo.
     */
    public function init() {
        // Declarar compatibilidad con HPOS (High-Performance Order Storage)
        $this->loader->add_action('before_woocommerce_init', $this, 'declare_hpos_compatibility');
        
        // Registrar shortcodes
        $this->loader->add_shortcode('productos_personalizados', $this, 'productos_shortcode');
        
        // Hook para comprobar si podemos cargar el plugin
        $this->loader->add_action('init', $this, 'init_plugin');
        
        // Añadir clases al body
        $this->loader->add_filter('body_class', $this, 'add_body_classes');
    }
    
    /**
     * Declarar compatibilidad con HPOS (High-Performance Order Storage)
     */
    public function declare_hpos_compatibility() {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', plugin_basename(WC_PRODUCTOS_TEMPLATE_DIR), true);
        }
    }
    
    /**
     * Inicializar el plugin
     */
    public function init_plugin() {
        // Registrar un post type personalizado si fuera necesario
        // $this->register_post_types();
        
        // Registrar taxonomías personalizadas si fuera necesario
        // $this->register_taxonomies();
        
        // Añadir soporte para campos personalizados en productos
        $this->add_product_fields_support();
    }
    
    /**
     * Añadir soporte para campos personalizados en productos
     */
    private function add_product_fields_support() {
        // Añadir meta boxes u otros campos si son necesarios
        // Por ejemplo, podríamos añadir un campo para marcar productos como destacados
    }
    
    /**
     * Shortcode para mostrar productos con el nuevo template
     * 
     * @param array $atts Atributos del shortcode.
     * @return string HTML del shortcode.
     */
    public function productos_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category'      => '',
            'per_page'      => get_option('posts_per_page'),
            'columns'       => 4,
            'orderby'       => 'menu_order title',
            'order'         => 'ASC',
            'prioritize_stock' => 'yes'
        ), $atts, 'productos_personalizados');
        
        // Obtener el módulo de productos para generar el output
        $products_module = $this->loader->get_module('WC_Productos_Template_Products');
        
        if ($products_module) {
            return $products_module->render_products_grid($atts);
        }
        
        // Fallback básico si el módulo de productos no está disponible
        ob_start();
        
        echo '<div class="wc-productos-template">';
        echo '<p>El módulo de productos no está disponible.</p>';
        echo '</div>';
        
        return ob_get_clean();
    }
    
    /**
     * Añadir clases al body
     *
     * @param array $classes Clases actuales del body.
     * @return array Clases modificadas.
     */
    public function add_body_classes($classes) {
        // Añadir clase del plugin
        $classes[] = 'wc-productos-template-active';
        
        // Añadir clase basada en la vista actual
        if ($this->is_product_page()) {
            $classes[] = 'wc-productos-page';
        }
        
        return $classes;
    }
    
    /**
     * Verificar si estamos en una página de productos
     *
     * @return bool True si estamos en una página de productos, false en caso contrario.
     */
    public function is_product_page() {
        // Verificar si estamos en una página de WooCommerce
        $is_wc_page = is_shop() || is_product_category() || is_product_tag() || is_product() || is_woocommerce();
        
        // Verificar si estamos en una página con el shortcode
        $has_shortcode = false;
        if (is_a(get_post(), 'WP_Post')) {
            $has_shortcode = has_shortcode(get_post()->post_content, 'productos_personalizados');
        }
        
        // Verificar si estamos en una URL con parámetros específicos del plugin
        $has_plugin_params = isset($_GET['category']) || isset($_GET['filter']) || isset($_GET['stock']);
        
        return $is_wc_page || $has_shortcode || $has_plugin_params;
    }
}
