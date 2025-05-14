<?php
/**
 * Módulo Filtering - Manejo de filtros de productos
 * 
 * @package WC_Productos_Template
 */

class WC_Productos_Template_Filtering {
    
    /**
     * El cargador que es responsable de mantener y registrar todos los hooks del plugin.
     *
     * @var WC_Productos_Template_Loader $loader
     */
    private $loader;
    
    /**
     * Inicializar el módulo Filtering.
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
        // Modificar consultas de productos
        $this->loader->add_action('pre_get_posts', $this, 'modify_product_query', 20);
        
        // Añadir opción de ordenamiento por stock
        $this->loader->add_filter('woocommerce_catalog_orderby', $this, 'add_stock_orderby_option');
        $this->loader->add_filter('woocommerce_get_catalog_ordering_args', $this, 'add_stock_ordering_args');
        
        // Añadir filtros a widgets
        $this->loader->add_action('woocommerce_before_shop_loop', $this, 'add_filter_widgets', 30);
        
        // Añadir filtro para categorías jerárquicas
        $this->loader->add_filter('woocommerce_product_categories_widget_args', $this, 'modify_category_widget_args');
        
        // Añadir soporte para filtro por disponibilidad
        $this->loader->add_filter('woocommerce_layered_nav_query_type', $this, 'add_availability_filter_query', 10, 5);
        
        // Ordenar productos en stock primero (alta prioridad para asegurar que siempre se aplique)
        $this->loader->add_filter('posts_clauses', $this, 'order_by_stock_status', 9999, 2);
    }
    
    /**
     * Modificar consulta de productos
     * 
     * @param WP_Query $query Objeto de consulta WP.
     */
    public function modify_product_query($query) {
        // No modificar en admin o si no es la consulta principal
        if (is_admin() || !$query->is_main_query()) {
            return;
        }
        
        // Solo modificar en páginas de producto WooCommerce
        if (!is_shop() && !is_product_category() && !is_product_tag() && !is_tax('product_taxonomy')) {
            return;
        }
        
        // Añadir meta query para filtrar por stock si está seleccionado
        if (isset($_GET['stock']) && $_GET['stock'] === 'instock') {
            $meta_query = $query->get('meta_query') ? $query->get('meta_query') : array();
            
            $meta_query[] = array(
                'key'     => '_stock_status',
                'value'   => 'instock',
                'compare' => '='
            );
            
            $query->set('meta_query', $meta_query);
        }
        
        // Añadir meta query para filtrar por rango de precios
        if (isset($_GET['min_price']) && isset($_GET['max_price'])) {
            $min_price = floatval($_GET['min_price']);
            $max_price = floatval($_GET['max_price']);
            
            if ($min_price >= 0 && $max_price > $min_price) {
                $meta_query = $query->get('meta_query') ? $query->get('meta_query') : array();
                
                $meta_query[] = array(
                    'key'     => '_price',
                    'value'   => array($min_price, $max_price),
                    'type'    => 'NUMERIC',
                    'compare' => 'BETWEEN'
                );
                
                $query->set('meta_query', $meta_query);
            }
        }
        
        // Ordenar por disponibilidad si no hay otro orden especificado por el usuario
        if (!isset($_GET['orderby'])) {
            $query->set('orderby', 'meta_value date');
            $query->set('meta_key', '_stock_status');
            $query->set('order', 'DESC');
        }
        
        // Modificar la cantidad de productos por página si está establecido
        if (isset($_GET['per_page']) && absint($_GET['per_page']) > 0) {
            $query->set('posts_per_page', absint($_GET['per_page']));
        }
    }
    
    /**
     * Añadir opción de ordenamiento por stock al dropdown
     * 
     * @param array $orderby Opciones actuales de ordenamiento.
     * @return array Opciones modificadas.
     */
    public function add_stock_orderby_option($orderby) {
        $orderby['stock'] = __('Disponibilidad', 'wc-productos-template');
        return $orderby;
    }
    
    /**
     * Añadir argumentos de ordenamiento para opción stock
     * 
     * @param array $args Argumentos de ordenamiento.
     * @return array Argumentos modificados.
     */
    public function add_stock_ordering_args($args) {
        if (isset($_GET['orderby'])) {
            if ($_GET['orderby'] === 'stock') {
                $args['orderby'] = 'meta_value';
                $args['order'] = 'DESC';
                $args['meta_key'] = '_stock_status';
            }
        }
        return $args;
    }
    
    /**
     * Modificar las cláusulas de consulta SQL para priorizar productos en stock
     * 
     * @param array $clauses Cláusulas de consulta.
     * @param WP_Query $query Objeto de consulta.
     * @return array Cláusulas modificadas.
     */
    public function order_by_stock_status($clauses, $query) {
        global $wpdb;
        
        // Verificar si estamos en una consulta de productos que debemos modificar
        if (!$this->should_modify_query($query)) {
            return $clauses;
        }
        
        // Verificar si ya existe un ordenamiento específico y respetarlo
        if (isset($_GET['orderby']) && $_GET['orderby'] !== 'stock') {
            return $clauses;
        }
        
        // Si ya hay un JOIN con la tabla stock_status, no duplicar
        if (strpos($clauses['join'], 'stock_status') === false) {
            $clauses['join'] .= " LEFT JOIN {$wpdb->postmeta} AS stock_status ON {$wpdb->posts}.ID = stock_status.post_id AND stock_status.meta_key = '_stock_status' ";
            
            // También unir con la tabla _stock para ordenar por cantidad de stock
            $clauses['join'] .= " LEFT JOIN {$wpdb->postmeta} AS stock_qty ON {$wpdb->posts}.ID = stock_qty.post_id AND stock_qty.meta_key = '_stock' ";
        }
        
        // Si no hay un orderby personalizado, usar stock_status primero, luego la cantidad
        if (!isset($_GET['orderby']) || $_GET['orderby'] === 'stock') {
            // CASE WHEN para ordenar instock primero, luego por cantidad de stock descendente
            $clauses['orderby'] = "CASE WHEN stock_status.meta_value = 'instock' THEN 0 ELSE 1 END ASC, 
                                  CAST(stock_qty.meta_value AS SIGNED) DESC, " . $clauses['orderby'];
        }
        
        return $clauses;
    }
    
    /**
     * Verificar si debemos modificar la consulta actual
     * 
     * @param WP_Query $query Objeto de consulta.
     * @return bool True si debemos modificar, false en caso contrario.
     */
    private function should_modify_query($query) {
        // No modificar consultas en admin o si no es la consulta principal
        if (is_admin() || !$query->is_main_query()) {
            return false;
        }
        
        // Verificar si es una consulta de productos de WooCommerce
        $is_product_query = (
            isset($query->query_vars['post_type']) && 
            $query->query_vars['post_type'] === 'product'
        );
        
        // También verificar si estamos en una página de WooCommerce
        $is_wc_page = is_shop() || is_product_category() || is_product_tag();
        
        // También comprobar si se está utilizando nuestro shortcode
        $using_shortcode = false;
        if (!$is_wc_page && is_singular()) {
            $post = get_post();
            $using_shortcode = $post && has_shortcode($post->post_content, 'productos_personalizados');
        }
        
        return $is_product_query || $is_wc_page || $using_shortcode;
    }
    
    /**
     * Añadir widgets de filtro personalizados
     */
    public function add_filter_widgets() {
        // Añadir filtro de stock
        $this->add_stock_filter_widget();
        
        // Añadir filtro de rango de precios
        $this->add_price_range_filter_widget();
    }
    
    /**
     * Añadir widget de filtro por stock
     */
    private function add_stock_filter_widget() {
        // Solo mostrar si estamos en shop o categoría y no es sidebar
        if (!is_active_sidebar('shop-filters') && (is_shop() || is_product_category())) {
            // Verificar el estado actual del filtro
            $is_filtered = isset($_GET['stock']) && $_GET['stock'] === 'instock';
            
            echo '<div class="wc-productos-widget wc-productos-stock-filter">';
            echo '<h4>' . __('Disponibilidad', 'wc-productos-template') . '</h4>';
            echo '<ul>';
            echo '<li>';
            echo '<label>';
            echo '<input type="checkbox" class="wc-productos-filter-stock" value="instock" ' . checked($is_filtered, true, false) . '>';
            echo __('En stock', 'wc-productos-template');
            echo '</label>';
            echo '</li>';
            echo '</ul>';
            echo '</div>';
        }
    }
    
    /**
     * Añadir widget de filtro por rango de precios
     */
    private function add_price_range_filter_widget() {
        // Solo mostrar si estamos en shop o categoría y no es sidebar
        if (!is_active_sidebar('shop-filters') && (is_shop() || is_product_category())) {
            // Obtener valores actuales de filtro
            $min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : '';
            $max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : '';
            
            // Obtener rango general de precios de productos
            $price_range = $this->get_price_range();
            
            echo '<div class="wc-productos-widget wc-productos-price-filter">';
            echo '<h4>' . __('Rango de Precio', 'wc-productos-template') . '</h4>';
            echo '<div class="wc-productos-price-slider" 
                data-min="' . esc_attr($price_range['min']) . '" 
                data-max="' . esc_attr($price_range['max']) . '" 
                data-current-min="' . esc_attr($min_price) . '" 
                data-current-max="' . esc_attr($max_price) . '">';
            
            echo '<div class="wc-productos-price-slider-ui"></div>';
            
            echo '<div class="wc-productos-price-inputs">';
            echo '<div class="wc-productos-price-input">';
            echo '<label for="wc-min-price">' . __('Mín', 'wc-productos-template') . '</label>';
            echo '<input type="number" id="wc-min-price" class="wc-productos-min-price" min="' . esc_attr($price_range['min']) . '" max="' . esc_attr($price_range['max']) . '" value="' . esc_attr($min_price ?: $price_range['min']) . '">';
            echo '</div>';
            
            echo '<div class="wc-productos-price-input">';
            echo '<label for="wc-max-price">' . __('Máx', 'wc-productos-template') . '</label>';
            echo '<input type="number" id="wc-max-price" class="wc-productos-max-price" min="' . esc_attr($price_range['min']) . '" max="' . esc_attr($price_range['max']) . '" value="' . esc_attr($max_price ?: $price_range['max']) . '">';
            echo '</div>';
            echo '</div>';
            
            echo '<button type="button" class="wc-productos-price-filter-button">' . __('Aplicar', 'wc-productos-template') . '</button>';
            
            echo '</div>';
            echo '</div>';
        }
    }
    
    /**
     * Obtener rango de precios de productos
     * 
     * @return array Rango con valores min y max.
     */
    private function get_price_range() {
        global $wpdb;
        
        // Intentar obtener del caché
        $transient_name = 'wc_productos_price_range';
        $price_range = get_transient($transient_name);
        
        if ($price_range === false) {
            // Consultar precios de productos publicados
            $results = $wpdb->get_row("
                SELECT MIN(CAST(price_meta.meta_value AS DECIMAL)) AS min_price, 
                       MAX(CAST(price_meta.meta_value AS DECIMAL)) AS max_price
                FROM {$wpdb->posts} AS posts
                INNER JOIN {$wpdb->postmeta} AS price_meta ON posts.ID = price_meta.post_id
                WHERE posts.post_type = 'product'
                AND posts.post_status = 'publish'
                AND price_meta.meta_key = '_price'
                AND price_meta.meta_value > 0
            ");
            
            if ($results) {
                $price_range = array(
                    'min' => floor($results->min_price),
                    'max' => ceil($results->max_price)
                );
            } else {
                // Valores por defecto si no hay productos
                $price_range = array(
                    'min' => 0,
                    'max' => 1000
                );
            }
            
            // Guardar en caché por 6 horas
            set_transient($transient_name, $price_range, 6 * HOUR_IN_SECONDS);
        }
        
        return $price_range;
    }
    
    /**
     * Modificar argumentos del widget de categorías
     * 
     * @param array $args Argumentos actuales.
     * @return array Argumentos modificados.
     */
    public function modify_category_widget_args($args) {
        // Mostrar categorías de forma jerárquica
        $args['hierarchical'] = 1;
        
        // Mostrar contadores de productos
        $args['show_count'] = 1;
        
        // No mostrar categorías vacías
        $args['hide_empty'] = 1;
        
        return $args;
    }
    
    /**
     * Añadir soporte para filtro por disponibilidad
     * 
     * @param array $filtered_posts Posts filtrados.
     * @param string $filter_name Nombre del filtro.
     * @param string $filter_value Valor del filtro.
     * @param WC_Query $query Consulta de WooCommerce.
     * @param string $query_type Tipo de consulta.
     * @return array Posts filtrados modificados.
     */
    public function add_availability_filter_query($filtered_posts, $filter_name, $filter_value, $query, $query_type = 'or') {
        if ($filter_name === 'stock_status') {
            global $wpdb;
            
            // Filtrar productos por estado de stock
            $status_value = ($filter_value === 'instock') ? 'instock' : 'outofstock';
            
            $query = "
                SELECT posts.ID
                FROM {$wpdb->posts} posts
                INNER JOIN {$wpdb->postmeta} pm ON posts.ID = pm.post_id
                WHERE posts.post_type = 'product'
                AND posts.post_status = 'publish'
                AND pm.meta_key = '_stock_status'
                AND pm.meta_value = %s
            ";
            
            $ids = $wpdb->get_col($wpdb->prepare($query, $status_value));
            
            if (!empty($ids)) {
                return $ids;
            }
        }
        
        return $filtered_posts;
    }
}
