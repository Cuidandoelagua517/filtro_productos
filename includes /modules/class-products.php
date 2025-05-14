<?php
/**
 * Módulo Products - Manejo de productos y visualización
 * 
 * @package WC_Productos_Template
 */

class WC_Productos_Template_Products {
    
    /**
     * El cargador que es responsable de mantener y registrar todos los hooks del plugin.
     *
     * @var WC_Productos_Template_Loader $loader
     */
    private $loader;
    
    /**
     * Inicializar el módulo Products.
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
        // Modificar consulta de productos para priorizar stock
        $this->loader->add_action('woocommerce_product_query', $this, 'modify_product_query', 20, 2);
        $this->loader->add_filter('woocommerce_get_catalog_ordering_args', $this, 'catalog_ordering_args', 20);
        
        // Mostrar badge de stock
        $this->loader->add_action('woocommerce_before_shop_loop_item_title', $this, 'show_stock_badge', 5);
        
        // Añadir filtros por stock
        $this->loader->add_filter('woocommerce_layered_nav_product_query_type', $this, 'add_stock_filter_query_type', 10, 4);
    }
    
    /**
     * Modificar consulta de productos para priorizar stock
     *
     * @param WP_Query $q Query de productos.
     * @param WC_Query $wc_query Query de WooCommerce.
     */
    public function modify_product_query($q, $wc_query) {
        // No modificar en admin o si ya está establecido un ordenamiento específico
        if (is_admin() || isset($_GET['orderby'])) {
            return;
        }
        
        // Verificar si debemos priorizar productos en stock
        $prioritize_stock = apply_filters('wc_productos_template_prioritize_stock', true);
        
        if ($prioritize_stock) {
            // Añadir join con la tabla de postmeta
            add_filter('posts_join', array($this, 'stock_status_join'));
            
            // Modificar la cláusula ORDER BY para priorizar productos en stock
            add_filter('posts_orderby', array($this, 'stock_status_orderby'), 10, 2);
        }
    }
    
    /**
     * Añadir join con la tabla de postmeta para ordenar por stock
     *
     * @param string $join SQL JOIN clause.
     * @return string Modified JOIN clause.
     */
    public function stock_status_join($join) {
        global $wpdb;
        
        if (!is_woocommerce() && !is_product_category() && !is_product_tag() && !is_shop()) {
            return $join;
        }
        
        // Solo añadir el join si no está ya presente
        if (strpos($join, 'stock_status') === false) {
            $join .= " LEFT JOIN {$wpdb->postmeta} stock_status ON {$wpdb->posts}.ID = stock_status.post_id AND stock_status.meta_key = '_stock_status' ";
        }
        
        // También unir con _stock para poder ordenar por cantidad de stock
        if (strpos($join, 'stock_qty') === false) {
            $join .= " LEFT JOIN {$wpdb->postmeta} stock_qty ON {$wpdb->posts}.ID = stock_qty.post_id AND stock_qty.meta_key = '_stock' ";
        }
        
        return $join;
    }
    
    /**
     * Modificar la cláusula ORDER BY para priorizar productos en stock
     *
     * @param string $orderby ORDER BY clause.
     * @param WP_Query $query The WP_Query instance.
     * @return string Modified ORDER BY clause.
     */
    public function stock_status_orderby($orderby, $query) {
        if (!is_woocommerce() && !is_product_category() && !is_product_tag() && !is_shop()) {
            return $orderby;
        }
        
        // Ordenar primero por estado de stock (instock primero) y luego por cantidad (mayor a menor)
        // Después, aplicar el ordenamiento original
        $orderby = "CASE WHEN stock_status.meta_value = 'instock' THEN 0 ELSE 1 END ASC, 
                   CAST(stock_qty.meta_value AS SIGNED) DESC, " . $orderby;
        
        // Eliminar los filtros para evitar bucles
        remove_filter('posts_join', array($this, 'stock_status_join'));
        remove_filter('posts_orderby', array($this, 'stock_status_orderby'));
        
        return $orderby;
    }
    
    /**
     * Modificar argumentos de ordenación del catálogo
     *
     * @param array $args Argumentos de ordenación.
     * @return array Argumentos modificados.
     */
    public function catalog_ordering_args($args) {
        // Añadir opción de ordenar por stock
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
     * Mostrar badge de stock en productos
     */
    public function show_stock_badge() {
        global $product;
        
        // No mostrar en páginas de admin
        if (is_admin()) {
            return;
        }
        
        // Verificar si el producto está disponible
        if ($product->is_in_stock()) {
            echo '<span class="wc-producto-badge badge-stock">' . esc_html__('En stock', 'wc-productos-template') . '</span>';
        } else {
            echo '<span class="wc-producto-badge badge-out-stock">' . esc_html__('Agotado', 'wc-productos-template') . '</span>';
        }
        
        // Si está en oferta, mostrar badge
        if ($product->is_on_sale()) {
            echo '<span class="wc-producto-badge badge-sale">' . esc_html__('Oferta', 'wc-productos-template') . '</span>';
        }
    }
    
    /**
     * Añadir tipo de consulta para filtro por stock
     *
     * @param array $filtered_posts Productos filtrados.
     * @param string $filter_name Nombre del filtro.
     * @param string $filter_value Valor del filtro.
     * @param WC_Query $wc_query Query de WooCommerce.
     * @return array Productos filtrados.
     */
    public function add_stock_filter_query_type($filtered_posts, $filter_name, $filter_value, $wc_query) {
        if ($filter_name === 'stock_status') {
            global $wpdb;
            
            // Filtrar productos por estado de stock
            $stock_status_query = "
                SELECT p.ID
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                WHERE p.post_type = 'product'
                AND p.post_status = 'publish'
                AND pm.meta_key = '_stock_status'
                AND pm.meta_value = %s
            ";
            
            $status_value = $filter_value === 'instock' ? 'instock' : 'outofstock';
            
            $ids = $wpdb->get_col($wpdb->prepare($stock_status_query, $status_value));
            
            if (!empty($ids)) {
                $filtered_posts = $ids;
            }
        }
        
        return $filtered_posts;
    }
    
    /**
     * Renderizar grid de productos
     *
     * @param array $args Argumentos para la consulta de productos.
     * @return string HTML del grid de productos.
     */
    public function render_products_grid($args = array()) {
        $default_args = array(
            'category'        => '',
            'per_page'        => get_option('posts_per_page'),
            'columns'         => 4,
            'orderby'         => 'menu_order title',
            'order'           => 'ASC',
            'prioritize_stock' => 'yes'
        );
        
        $args = wp_parse_args($args, $default_args);
        
        // Configurar argumentos para WP_Query
        $query_args = array(
            'post_type'           => 'product',
            'post_status'         => 'publish',
            'ignore_sticky_posts' => 1,
            'posts_per_page'      => $args['per_page'],
            'orderby'             => $args['orderby'],
            'order'               => $args['order'],
            'paged'               => max(1, get_query_var('paged'))
        );
        
        // Filtrar por categoría si está especificada
        if (!empty($args['category'])) {
            $query_args['tax_query'] = array(
                array(
                    'taxonomy'  => 'product_cat',
                    'field'     => 'slug',
                    'terms'     => explode(',', $args['category']),
                    'operator'  => 'IN'
                )
            );
        }
        
        // Modificar query para priorizar productos en stock si es necesario
        if ($args['prioritize_stock'] === 'yes') {
            add_filter('posts_join', array($this, 'stock_status_join'));
            add_filter('posts_orderby', array($this, 'stock_status_orderby'), 10, 2);
        }
        
        // Crear instancia de WP_Query
        $products = new WP_Query(apply_filters('wc_productos_template_products_query_args', $query_args));
        
        // Eliminar filtros después de la consulta
        if ($args['prioritize_stock'] === 'yes') {
            remove_filter('posts_join', array($this, 'stock_status_join'));
            remove_filter('posts_orderby', array($this, 'stock_status_orderby'));
        }
        
        // Iniciar buffer de salida
        ob_start();
        
        // Cargar template
        $this->load_products_template($products, $args);
        
        // Restaurar post data original
        wp_reset_postdata();
        
        // Devolver HTML generado
        return ob_get_clean();
    }
    
    /**
     * Cargar template de productos
     *
     * @param WP_Query $products Query de productos.
     * @param array $args Argumentos para el template.
     */
    private function load_products_template($products, $args) {
        // Asignar cantidad de columnas para el grid
        wc_set_loop_prop('columns', $args['columns']);
        
        // Abrir contenedor principal
        echo '<div class="wc-productos-template mercadolibre-style">';
        
        // Incluir barra de búsqueda y filtros
        $this->load_template_part('partials/search-bar');
        
        echo '<div class="wc-productos-layout">';
        
        // Cargar sidebar de filtros
        $this->load_template_part('partials/filters');
        
        // Contenedor principal de productos
        echo '<div class="wc-productos-main">';
        
        // Título y contador de resultados
        if ($products->have_posts()) {
            echo '<div class="wc-productos-header">';
            echo '<h1>' . $this->get_page_title() . '</h1>';
            
            // Contador de resultados
            echo '<div class="wc-productos-result-count">';
            echo sprintf(
                _n('Mostrando %1$d de %2$d resultado', 'Mostrando %1$d de %2$d resultados', $products->found_posts, 'wc-productos-template'),
                min($products->post_count, $products->found_posts),
                $products->found_posts
            );
            echo '</div>';
            echo '</div>';
            
            // Cargar productos
            woocommerce_product_loop_start();
            
            while ($products->have_posts()) {
                $products->the_post();
                global $product;
                
                // Cargar template part para producto
                $this->load_template_part('content', 'product');
            }
            
            woocommerce_product_loop_end();
            
            // Paginación
            echo '<div class="wc-productos-pagination">';
            echo paginate_links(array(
                'base'         => esc_url_raw(str_replace(999999999, '%#%', get_pagenum_link(999999999, false))),
                'format'       => '',
                'add_args'     => false,
                'current'      => max(1, get_query_var('paged')),
                'total'        => $products->max_num_pages,
                'prev_text'    => '&larr;',
                'next_text'    => '&rarr;',
                'type'         => 'list',
                'end_size'     => 3,
                'mid_size'     => 3
            ));
            echo '</div>';
        } else {
            // No se encontraron productos
            echo '<div class="wc-productos-no-results">';
            echo '<p>' . esc_html__('No se encontraron productos que coincidan con su búsqueda.', 'wc-productos-template') . '</p>';
            echo '</div>';
        }
        
        echo '</div>'; // Fin wc-productos-main
        echo '</div>'; // Fin wc-productos-layout
        echo '</div>'; // Fin wc-productos-template
    }
    
    /**
     * Cargar parte de template
     *
     * @param string $slug Slug del template.
     * @param string $name Nombre opcional del template.
     */
    private function load_template_part($slug, $name = '') {
        // Construir rutas de posibles templates
        $templates = array();
        $templates[] = $slug . (!empty($name) ? '-' . $name : '') . '.php';
        
        // Buscar template en el tema actual
        $template = locate_template($templates);
        
        // Si no se encuentra en el tema, buscar en el plugin
        if (!$template) {
            $plugin_template = WC_PRODUCTOS_TEMPLATE_TEMPLATES_DIR . $slug . (!empty($name) ? '-' . $name : '') . '.php';
            
            if (file_exists($plugin_template)) {
                $template = $plugin_template;
            }
        }
        
        // Incluir template si existe
        if ($template) {
            include $template;
        }
    }
    
    /**
     * Obtener título de la página actual
     *
     * @return string Título de la página.
     */
    private function get_page_title() {
        if (is_search()) {
            return sprintf(__('Resultados de búsqueda para: "%s"', 'wc-productos-template'), get_search_query());
        } else if (is_product_category()) {
            $term = get_queried_object();
            return $term->name;
        } else if (is_product_tag()) {
            $term = get_queried_object();
            return sprintf(__('Productos etiquetados como: "%s"', 'wc-productos-template'), $term->name);
        } else if (is_shop()) {
            return get_the_title(wc_get_page_id('shop'));
        } else {
            return get_the_title();
        }
    }
}
