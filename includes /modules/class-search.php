<?php
/**
 * Módulo Search - Mejoras en la búsqueda de productos
 * 
 * @package WC_Productos_Template
 */

class WC_Productos_Template_Search {
    
    /**
     * El cargador que es responsable de mantener y registrar todos los hooks del plugin.
     *
     * @var WC_Productos_Template_Loader $loader
     */
    private $loader;
    
    /**
     * Inicializar el módulo Search.
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
        // Mejorar la relevancia de la búsqueda
        $this->loader->add_filter('posts_search', $this, 'enhance_search_relevance', 10, 2);
        
        // Añadir soporte para búsqueda en campos personalizados y SKU
        $this->loader->add_action('pre_get_posts', $this, 'search_in_custom_fields', 10);
        
        // Modificar la consulta de búsqueda para incluir SKU
        $this->loader->add_filter('woocommerce_product_data_store_cpt_get_products_query', $this, 'handle_sku_search', 10, 2);
        
        // Añadir clase al body para estilos específicos de búsqueda
        $this->loader->add_filter('body_class', $this, 'add_search_body_classes');
        
        // Añadir soporte para búsqueda de términos exactos
        $this->loader->add_filter('posts_clauses', $this, 'search_exact_terms', 10, 2);
        
        // Añadir autocompletado de búsqueda
        $this->loader->add_action('wp_ajax_wc_productos_search_suggestions', $this, 'get_search_suggestions');
        $this->loader->add_action('wp_ajax_nopriv_wc_productos_search_suggestions', $this, 'get_search_suggestions');
    }
    
    /**
     * Mejorar la relevancia de los resultados de búsqueda
     *
     * @param string $search Cláusula SQL de búsqueda.
     * @param WP_Query $query Objeto de consulta.
     * @return string Cláusula SQL modificada.
     */
    public function enhance_search_relevance($search, $query) {
        global $wpdb;
        
        // Verificar si es una búsqueda válida que deberíamos modificar
        if (empty($search) || !$query->is_search() || !$query->is_main_query() || !$this->is_product_search($query)) {
            return $search;
        }
        
        $search_term = $query->get('s');
        if (empty($search_term)) {
            return $search;
        }
        
        // Limpiar y preparar el término de búsqueda
        $like = '%' . $wpdb->esc_like($search_term) . '%';
        
        // Búsqueda con relevancia personalizada
        // 1. Coincidencia en título (más relevante)
        // 2. Coincidencia en SKU
        // 3. Coincidencia en extracto
        // 4. Coincidencia en contenido (menos relevante)
        $search = " AND (
            ({$wpdb->posts}.post_title LIKE '{$like}') OR 
            EXISTS (
                SELECT * FROM {$wpdb->postmeta} 
                WHERE {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID 
                AND {$wpdb->postmeta}.meta_key = '_sku' 
                AND {$wpdb->postmeta}.meta_value LIKE '{$like}'
            ) OR 
            ({$wpdb->posts}.post_excerpt LIKE '{$like}') OR 
            ({$wpdb->posts}.post_content LIKE '{$like}')
        )";
        
        // Si es numérico, podría ser un SKU o ID
        if (is_numeric($search_term)) {
            $search_term_int = intval($search_term);
            $search = " AND (
                ({$wpdb->posts}.post_title LIKE '{$like}') OR 
                ({$wpdb->posts}.ID = {$search_term_int}) OR
                EXISTS (
                    SELECT * FROM {$wpdb->postmeta} 
                    WHERE {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID 
                    AND {$wpdb->postmeta}.meta_key = '_sku' 
                    AND {$wpdb->postmeta}.meta_value LIKE '{$like}'
                ) OR 
                ({$wpdb->posts}.post_excerpt LIKE '{$like}') OR 
                ({$wpdb->posts}.post_content LIKE '{$like}')
            )";
        }
        
        return $search;
    }
    
    /**
     * Manejar búsqueda en campos personalizados
     *
     * @param WP_Query $query Objeto de consulta.
     */
    public function search_in_custom_fields($query) {
        // No modificar en admin o si no es la consulta principal
        if (is_admin() || !$query->is_main_query()) {
            return;
        }
        
        // Solo modificar en búsquedas
        if (!$query->is_search() || !$this->is_product_search($query)) {
            return;
        }
        
        $search_term = $query->get('s');
        if (empty($search_term)) {
            return;
        }
        
        // Meta query para buscar en campos personalizados
        $meta_query = $query->get('meta_query', array());
        
        // Añadir búsqueda en campos personalizados
        $meta_query[] = array(
            'relation' => 'OR',
            array(
                'key'     => '_sku',
                'value'   => $search_term,
                'compare' => 'LIKE'
            ),
            array(
                'key'     => '_volumen',
                'value'   => $search_term,
                'compare' => 'LIKE'
            ),
            array(
                'key'     => '_grado',
                'value'   => $search_term,
                'compare' => 'LIKE'
            ),
            array(
                'key'     => '_caracteristicas',
                'value'   => $search_term,
                'compare' => 'LIKE'
            )
        );
        
        $query->set('meta_query', $meta_query);
    }
    
    /**
     * Manejar búsqueda en SKU
     * 
     * @param array $query_args Argumentos de la consulta.
     * @param array $query_vars Variables de la consulta.
     * @return array Argumentos modificados.
     */
    public function handle_sku_search($query_args, $query_vars) {
        if (!empty($query_vars['s'])) {
            $search_term = esc_attr($query_vars['s']);
            
            // Verificar si ya hay una meta_query
            if (!isset($query_args['meta_query'])) {
                $query_args['meta_query'] = array();
            }
            
            // Añadir búsqueda en SKU
            $query_args['meta_query'][] = array(
                'relation' => 'OR',
                array(
                    'key'     => '_sku',
                    'value'   => $search_term,
                    'compare' => 'LIKE'
                )
            );
        }
        
        return $query_args;
    }
    
    /**
     * Añadir clases al body para estilos específicos de búsqueda
     * 
     * @param array $classes Clases actuales.
     * @return array Clases modificadas.
     */
    public function add_search_body_classes($classes) {
        if (is_search() || (isset($_GET['s']) && !empty($_GET['s']))) {
            $classes[] = 'wc-productos-search-results';
            $classes[] = 'wc-productos-search-active';
        }
        
        return $classes;
    }
    
    /**
     * Soporte para búsqueda de términos exactos
     * 
     * @param array $clauses Cláusulas de SQL.
     * @param WP_Query $query Objeto de consulta.
     * @return array Cláusulas modificadas.
     */
    public function search_exact_terms($clauses, $query) {
        global $wpdb;
        
        // Verificar si es una búsqueda válida que deberíamos modificar
        if (!$query->is_search() || !$this->is_product_search($query)) {
            return $clauses;
        }
        
        $search_term = $query->get('s');
        if (empty($search_term)) {
            return $clauses;
        }
        
        // Si el término está entre comillas, buscar coincidencia exacta
        if (preg_match('/^"(.*)"$/', $search_term, $matches)) {
            $exact_term = $matches[1];
            
            // Crear nueva cláusula WHERE para búsqueda exacta
            $exact_search = " AND (
                ({$wpdb->posts}.post_title = '{$exact_term}') OR 
                EXISTS (
                    SELECT * FROM {$wpdb->postmeta} 
                    WHERE {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID 
                    AND {$wpdb->postmeta}.meta_key = '_sku' 
                    AND {$wpdb->postmeta}.meta_value = '{$exact_term}'
                ) OR 
                ({$wpdb->posts}.post_excerpt = '{$exact_term}') OR 
                ({$wpdb->posts}.post_content LIKE '%{$exact_term}%')
            )";
            
            // Reemplazar la cláusula de búsqueda actual
            $clauses['where'] = str_replace(
                $wpdb->prepare("{$wpdb->posts}.post_title LIKE %s", '%' . $wpdb->esc_like($search_term) . '%'),
                "1=1",
                $clauses['where']
            );
            
            // Añadir nuestra cláusula exacta
            $clauses['where'] .= $exact_search;
        }
        
        return $clauses;
    }
    
    /**
     * Obtener sugerencias de búsqueda para autocompletado
     */
    public function get_search_suggestions() {
        // Verificar nonce
        check_ajax_referer('wc_productos_template_nonce', 'nonce');
        
        // Obtener término de búsqueda
        $term = isset($_GET['term']) ? sanitize_text_field($_GET['term']) : '';
        
        if (empty($term) || strlen($term) < 3) {
            wp_send_json_error(array('message' => __('Término de búsqueda demasiado corto', 'wc-productos-template')));
            exit;
        }
        
        // Buscar productos que coincidan con el término
        $args = array(
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => 10,
            's'              => $term,
            'fields'         => 'ids'
        );
        
        $products = get_posts($args);
        
        // Buscar SKUs que coincidan con el término
        $sku_query = new WP_Query(array(
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => 5,
            'fields'         => 'ids',
            'meta_query'     => array(
                array(
                    'key'     => '_sku',
                    'value'   => $term,
                    'compare' => 'LIKE'
                )
            )
        ));
        
        // Combinar resultados y eliminar duplicados
        $product_ids = array_unique(array_merge($products, $sku_query->posts));
        
        // Preparar sugerencias
        $suggestions = array();
        
        foreach ($product_ids as $product_id) {
            $product = wc_get_product($product_id);
            
            if (!$product) {
                continue;
            }
            
            $suggestion = array(
                'id'    => $product_id,
                'label' => $product->get_name(),
                'value' => $product->get_name(),
                'url'   => get_permalink($product_id)
            );
            
            // Añadir SKU si existe
            $sku = $product->get_sku();
            if (!empty($sku)) {
                $suggestion['sku'] = $sku;
                $suggestion['label'] .= ' (' . $sku . ')';
            }
            
            // Añadir precio si el usuario está logueado
            if (is_user_logged_in()) {
                $suggestion['price'] = $product->get_price_html();
            }
            
            // Añadir imagen en miniatura si existe
            if (has_post_thumbnail($product_id)) {
                $suggestion['image'] = get_the_post_thumbnail_url($product_id, 'thumbnail');
            }
            
            $suggestions[] = $suggestion;
        }
        
        // Añadir sugerencias de términos de categorías
        $category_terms = get_terms(array(
            'taxonomy'   => 'product_cat',
            'hide_empty' => true,
            'search'     => $term,
            'number'     => 5
        ));
        
        if (!is_wp_error($category_terms) && !empty($category_terms)) {
            foreach ($category_terms as $term) {
                $suggestions[] = array(
                    'id'    => 'cat_' . $term->term_id,
                    'label' => sprintf(__('Categoría: %s', 'wc-productos-template'), $term->name),
                    'value' => $term->name,
                    'url'   => get_term_link($term),
                    'type'  => 'category'
                );
            }
        }
        
        // Si no hay sugerencias, añadir una para buscar el término completo
        if (empty($suggestions)) {
            $suggestions[] = array(
                'id'    => 'search_' . md5($term),
                'label' => sprintf(__('Buscar "%s" en todos los productos', 'wc-productos-template'), $term),
                'value' => $term,
                'url'   => add_query_arg(array('s' => $term, 'post_type' => 'product'), home_url('/'))
            );
        }
        
        wp_send_json_success($suggestions);
        exit;
    }
    
    /**
     * Verificar si la consulta es una búsqueda de productos
     * 
     * @param WP_Query $query Objeto de consulta.
     * @return bool True si es búsqueda de productos, false en caso contrario.
     */
    private function is_product_search($query) {
        // Verificar por post_type explícito
        if (isset($query->query_vars['post_type']) && $query->query_vars['post_type'] === 'product') {
            return true;
        }
        
        // Verificar por parámetro de URL
        if (isset($_GET['post_type']) && $_GET['post_type'] === 'product') {
            return true;
        }
        
        // Verificar si estamos en una página de WooCommerce
        if (function_exists('is_woocommerce') && is_woocommerce()) {
            return true;
        }
        
        return false;
    }
}
