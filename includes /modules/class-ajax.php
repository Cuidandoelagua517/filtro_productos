<?php
/**
 * Módulo AJAX - Manejo de solicitudes AJAX
 * 
 * @package WC_Productos_Template
 */

class WC_Productos_Template_AJAX {
    
    /**
     * El cargador que es responsable de mantener y registrar todos los hooks del plugin.
     *
     * @var WC_Productos_Template_Loader $loader
     */
    private $loader;
    
    /**
     * Inicializar el módulo AJAX.
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
        // Registrar endpoints AJAX para usuarios logueados y no logueados
        $this->register_ajax_endpoints();
    }
    
    /**
     * Registrar endpoints AJAX
     */
    private function register_ajax_endpoints() {
        // Filtrar productos (para usuarios logueados y no logueados)
        $this->loader->add_action('wp_ajax_wc_productos_filter', $this, 'ajax_filter_products');
        $this->loader->add_action('wp_ajax_nopriv_wc_productos_filter', $this, 'ajax_filter_products');
        
        // Búsqueda de productos (para usuarios logueados y no logueados)
        $this->loader->add_action('wp_ajax_wc_productos_search', $this, 'ajax_search_products');
        $this->loader->add_action('wp_ajax_nopriv_wc_productos_search', $this, 'ajax_search_products');
        
        // Login/Registro (solo para usuarios no logueados)
        $this->loader->add_action('wp_ajax_nopriv_wc_productos_get_login_form', $this, 'ajax_get_login_form');
        $this->loader->add_action('wp_ajax_nopriv_wc_productos_ajax_login', $this, 'ajax_process_login');
        $this->loader->add_action('wp_ajax_nopriv_wc_productos_ajax_register', $this, 'ajax_process_register');
        
        // Cargar vista rápida de producto
        $this->loader->add_action('wp_ajax_wc_productos_quick_view', $this, 'ajax_load_quick_view');
        $this->loader->add_action('wp_ajax_nopriv_wc_productos_quick_view', $this, 'ajax_load_quick_view');
    }
    
    /**
     * AJAX para filtrar productos
     */
    public function ajax_filter_products() {
        // Verificar nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wc_productos_template_nonce')) {
            wp_send_json_error(array('message' => __('Error de seguridad, por favor recargue la página', 'wc-productos-template')));
            exit;
        }
        
        // Log para depuración
        $this->log_ajax_request('Filtrar productos');
        
        // Obtener parámetros
        $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
        $price_min = isset($_POST['price_min']) ? floatval($_POST['price_min']) : '';
        $price_max = isset($_POST['price_max']) ? floatval($_POST['price_max']) : '';
        $stock_status = isset($_POST['stock']) ? sanitize_text_field($_POST['stock']) : '';
        $search_term = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        
        // Configurar argumentos de la consulta
        $args = array(
            'post_type'      => 'product',
            'posts_per_page' => get_option('posts_per_page'),
            'paged'          => $page,
            'post_status'    => 'publish',
        );
        
        // Taxonomías y queries adicionales
        $tax_query = array();
        $meta_query = array();
        
        // Filtrar por categoría si está presente
        if (!empty($category)) {
            $categories = explode(',', $category);
            if (!empty($categories)) {
                $tax_query[] = array(
                    'taxonomy'  => 'product_cat',
                    'field'     => 'slug',
                    'terms'     => $categories,
                    'operator'  => 'IN',
                    'include_children' => true
                );
            }
        }
        
        // Filtrar por precio si está presente
        if (!empty($price_min) && !empty($price_max)) {
            $meta_query[] = array(
                'key'     => '_price',
                'value'   => array($price_min, $price_max),
                'type'    => 'NUMERIC',
                'compare' => 'BETWEEN'
            );
        } else if (!empty($price_min)) {
            $meta_query[] = array(
                'key'     => '_price',
                'value'   => $price_min,
                'type'    => 'NUMERIC',
                'compare' => '>='
            );
        } else if (!empty($price_max)) {
            $meta_query[] = array(
                'key'     => '_price',
                'value'   => $price_max,
                'type'    => 'NUMERIC',
                'compare' => '<='
            );
        }
        
        // Filtrar por stock si está presente
        if (!empty($stock_status) && $stock_status === 'instock') {
            $meta_query[] = array(
                'key'     => '_stock_status',
                'value'   => 'instock',
                'compare' => '='
            );
        }
        
        // Añadir búsqueda si hay un término
        if (!empty($search_term)) {
            $args['s'] = $search_term;
            
            // Meta query para buscar en SKU y otros campos personalizados
            $search_meta_query = array('relation' => 'OR');
            
            // Buscar en SKU
            $search_meta_query[] = array(
                'key'     => '_sku',
                'value'   => $search_term,
                'compare' => 'LIKE'
            );
            
            // Buscar en otros campos personalizados
            $custom_fields = array('_volumen', '_grado', '_caracteristicas');
            foreach ($custom_fields as $field) {
                $search_meta_query[] = array(
                    'key'     => $field,
                    'value'   => $search_term,
                    'compare' => 'LIKE'
                );
            }
            
            // Añadir al meta_query principal
            $meta_query[] = $search_meta_query;
        }
        
        // Añadir tax_query a args si hay elementos
        if (!empty($tax_query)) {
            $args['tax_query'] = array_merge(
                array('relation' => 'AND'),
                $tax_query
            );
        }
        
        // Añadir meta_query a args si hay elementos
        if (!empty($meta_query)) {
            $args['meta_query'] = array_merge(
                array('relation' => 'AND'),
                $meta_query
            );
        }
        
        // Ordenar por stock primero (productos en stock aparecen primero)
        $args['orderby'] = array(
            'meta_value' => 'DESC',
            'date'       => 'DESC'
        );
        $args['meta_key'] = '_stock_status';
        
        // Ejecutar consulta de productos
        $products_query = new WP_Query(apply_filters('wc_productos_template_filter_query_args', $args));
        
        // Configurar propiedades del bucle de WooCommerce
        wc_set_loop_prop('current_page', $page);
        wc_set_loop_prop('total', $products_query->found_posts);
        wc_set_loop_prop('total_pages', $products_query->max_num_pages);
        wc_set_loop_prop('is_paginated', true);
        wc_set_loop_prop('per_page', get_option('posts_per_page'));
        
        // Cargar templates y generar HTML
        $data = $this->get_filtered_products_data($products_query);
        
        // Enviar respuesta
        wp_send_json_success($data);
        exit;
    }
    
    /**
     * AJAX para búsqueda de productos
     */
    public function ajax_search_products() {
        // Verificar nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wc_productos_template_nonce')) {
            wp_send_json_error(array('message' => __('Error de seguridad, por favor recargue la página', 'wc-productos-template')));
            exit;
        }
        
        // Log para depuración
        $this->log_ajax_request('Búsqueda de productos');
        
        // Obtener parámetros
        $search_term = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
        
        // Verificar término de búsqueda
        if (empty($search_term)) {
            wp_send_json_error(array('message' => __('Por favor, ingrese un término de búsqueda', 'wc-productos-template')));
            exit;
        }
        
        // Configurar argumentos de búsqueda
        $args = array(
            'post_type'      => 'product',
            'posts_per_page' => get_option('posts_per_page'),
            'paged'          => $page,
            'post_status'    => 'publish',
            's'              => $search_term
        );
        
        // Meta query para buscar en campos adicionales
        $meta_query = array('relation' => 'OR');
        
        // Buscar en SKU
        $meta_query[] = array(
            'key'     => '_sku',
            'value'   => $search_term,
            'compare' => 'LIKE'
        );
        
        // Buscar en atributos comunes
        $attributes = array('pa_volumen', 'pa_grado', 'pa_caracteristicas', '_volumen', '_grado');
        foreach ($attributes as $attr) {
            $meta_query[] = array(
                'key'     => $attr,
                'value'   => $search_term,
                'compare' => 'LIKE'
            );
        }
        
        // Añadir meta_query a los argumentos
        $args['meta_query'] = $meta_query;
        
        // Ejecutar consulta de productos
        $products_query = new WP_Query(apply_filters('wc_productos_template_search_query_args', $args));
        
        // Configurar propiedades del bucle de WooCommerce
        wc_set_loop_prop('current_page', $page);
        wc_set_loop_prop('total', $products_query->found_posts);
        wc_set_loop_prop('total_pages', $products_query->max_num_pages);
        wc_set_loop_prop('is_paginated', true);
        wc_set_loop_prop('per_page', get_option('posts_per_page'));
        
        // Cargar templates y generar HTML
        $data = $this->get_filtered_products_data($products_query);
        
        // Añadir información adicional para búsqueda
        $data['search_term'] = $search_term;
        $data['search_message'] = sprintf(
            __('Se encontraron %d resultados para "%s"', 'wc-productos-template'),
            $products_query->found_posts,
            $search_term
        );
        
        // Enviar respuesta
        wp_send_json_success($data);
        exit;
    }
    
    /**
     * AJAX para obtener formulario de login
     */
    public function ajax_get_login_form() {
        // Verificar nonce
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'wc_productos_template_nonce')) {
            wp_send_json_error(array('message' => __('Error de seguridad, por favor recargue la página', 'wc-productos-template')));
            exit;
        }
        
        // Log para depuración
        $this->log_ajax_request('Obtener formulario de login');
        
        // Si el usuario ya está logueado, devolver mensaje
        if (is_user_logged_in()) {
            wp_send_json_success(array(
                'html' => '<div class="wc-productos-already-logged-in">' . 
                    __('Ya has iniciado sesión', 'wc-productos-template') . 
                    '</div>',
                'redirect_url' => wp_get_referer() ? wp_get_referer() : home_url()
            ));
            exit;
        }
        
        // Buffer de salida para capturar el template
        ob_start();
        
        // Comprobar si existe el template y luego incluirlo
        $template_path = WC_PRODUCTOS_TEMPLATE_TEMPLATES_DIR . 'partials/login-form.php';
        if (file_exists($template_path)) {
            include($template_path);
        } else {
            echo '<div class="wc-productos-error">' . 
                __('Error: No se encontró el formulario de login', 'wc-productos-template') . 
                '</div>';
        }
        
        // Obtener HTML generado
        $html = ob_get_clean();
        
        // Enviar respuesta exitosa con HTML
        wp_send_json_success(array(
            'html' => $html,
            'redirect_url' => wp_get_referer() ? wp_get_referer() : home_url()
        ));
        exit;
    }
    
    /**
     * AJAX para procesar login
     */
    public function ajax_process_login() {
        // Verificar nonce
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'wc_productos_template_nonce')) {
            wp_send_json_error(array('message' => __('Error de seguridad, por favor recargue la página', 'wc-productos-template')));
            exit;
        }
        
        // Log para depuración
        $this->log_ajax_request('Procesar login');
        
        // Obtener credenciales
        $username = isset($_POST['username']) ? sanitize_user($_POST['username']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $remember = isset($_POST['rememberme']) ? (bool)$_POST['rememberme'] : false;
        $redirect = isset($_POST['redirect']) ? esc_url_raw($_POST['redirect']) : '';
        
        // Verificar campos
        if (empty($username) || empty($password)) {
            wp_send_json_error(array('message' => __('Por favor, ingrese su nombre de usuario y contraseña', 'wc-productos-template')));
            exit;
        }
        
        // Intentar login
        $user = wp_signon(array(
            'user_login'    => $username,
            'user_password' => $password,
            'remember'      => $remember
        ));
        
        // Verificar si hay error
        if (is_wp_error($user)) {
            wp_send_json_error(array('message' => $user->get_error_message()));
            exit;
        }
        
        // Login exitoso
        wp_set_current_user($user->ID);
        
        // Determinar URL de redirección
        if (!empty($redirect)) {
            $redirect_url = $redirect;
        } else if (!empty($_SERVER['HTTP_REFERER'])) {
            $redirect_url = $_SERVER['HTTP_REFERER'];
        } else {
            $redirect_url = home_url();
        }
        
        // Añadir parámetro para forzar recarga
        $redirect_url = add_query_arg('wc_refresh', time(), $redirect_url);
        
        // Enviar respuesta exitosa
        wp_send_json_success(array(
            'message' => __('Login exitoso, redirigiendo...', 'wc-productos-template'),
            'redirect_url' => $redirect_url,
            'user_id' => $user->ID
        ));
        exit;
    }
    
    /**
     * AJAX para procesar registro
     */
    public function ajax_process_register() {
        // Verificar nonce
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'wc_productos_template_nonce')) {
            wp_send_json_error(array('message' => __('Error de seguridad, por favor recargue la página', 'wc-productos-template')));
            exit;
        }
        
        // Log para depuración
        $this->log_ajax_request('Procesar registro');
        
        // Si el usuario ya está logueado, devolver error
        if (is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Ya has iniciado sesión', 'wc-productos-template')));
            exit;
        }
        
        // Validar campos requeridos
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $password_confirm = isset($_POST['password_confirm']) ? $_POST['password_confirm'] : '';
        
        // Verificar email
        if (empty($email) || !is_email($email)) {
            wp_send_json_error(array('message' => __('Por favor, ingrese un email válido', 'wc-productos-template')));
            exit;
        }
        
        // Verificar que el email no esté ya registrado
        if (email_exists($email)) {
            wp_send_json_error(array('message' => __('Este email ya está registrado. Por favor, inicie sesión', 'wc-productos-template')));
            exit;
        }
        
        // Verificar contraseña
        if (empty($password)) {
            wp_send_json_error(array('message' => __('Por favor, ingrese una contraseña', 'wc-productos-template')));
            exit;
        }
        
        // Verificar coincidencia de contraseñas
        if ($password !== $password_confirm) {
            wp_send_json_error(array('message' => __('Las contraseñas no coinciden', 'wc-productos-template')));
            exit;
        }
        
        // Verificar política de privacidad
        if (!isset($_POST['privacy_policy']) || empty($_POST['privacy_policy'])) {
            wp_send_json_error(array('message' => __('Debe aceptar la política de privacidad', 'wc-productos-template')));
            exit;
        }
        
        // Obtener datos adicionales
        $first_name = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
        $last_name = isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '';
        
        // Generar nombre de usuario a partir del email
        $username = sanitize_user(current(explode('@', $email)), true);
        
        // Crear el usuario
        $user_id = wc_create_new_customer($email, $username, $password);
        
        // Verificar si hubo error
        if (is_wp_error($user_id)) {
            wp_send_json_error(array('message' => $user_id->get_error_message()));
            exit;
        }
        
        // Actualizar datos adicionales
        if (!empty($first_name)) {
            update_user_meta($user_id, 'first_name', $first_name);
            update_user_meta($user_id, 'billing_first_name', $first_name);
        }
        
        if (!empty($last_name)) {
            update_user_meta($user_id, 'last_name', $last_name);
            update_user_meta($user_id, 'billing_last_name', $last_name);
        }
        
        // Registrar aceptación de política de privacidad
        update_user_meta($user_id, 'privacy_policy_consent', 'yes');
        update_user_meta($user_id, 'privacy_policy_consent_date', current_time('mysql'));
        
        // Iniciar sesión automáticamente
        wc_set_customer_auth_cookie($user_id);
        
        // Determinar URL de redirección
        $redirect_url = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : home_url();
        $redirect_url = add_query_arg('wc_refresh', time(), $redirect_url);
        
        // Enviar respuesta exitosa
        wp_send_json_success(array(
            'message' => __('Registro exitoso, redirigiendo...', 'wc-productos-template'),
            'redirect_url' => $redirect_url,
            'user_id' => $user_id
        ));
        exit;
    }
    
    /**
     * AJAX para cargar vista rápida de producto
     */
    public function ajax_load_quick_view() {
        // Verificar nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wc_productos_template_nonce')) {
            wp_send_json_error(array('message' => __('Error de seguridad, por favor recargue la página', 'wc-productos-template')));
            exit;
        }
        
        // Log para depuración
        $this->log_ajax_request('Cargar vista rápida de producto');
        
        // Obtener ID del producto
        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        
        // Verificar ID
        if (empty($product_id)) {
            wp_send_json_error(array('message' => __('ID de producto no válido', 'wc-productos-template')));
            exit;
        }
        
        // Obtener objeto de producto
        $product = wc_get_product($product_id);
        
        // Verificar que el producto existe
        if (!$product) {
            wp_send_json_error(array('message' => __('El producto no existe', 'wc-productos-template')));
            exit;
        }
        
        // Configurar post global para que funcionen las plantillas
        global $post;
        $post = get_post($product_id);
        setup_postdata($post);
        
        // Buffer de salida para capturar el HTML
        ob_start();
        
        // Intentar cargar template personalizado
        $template_path = WC_PRODUCTOS_TEMPLATE_TEMPLATES_DIR . 'partials/quick-view.php';
        if (file_exists($template_path)) {
            include($template_path);
        } else {
            // Template básico si no existe el personalizado
            echo '<div class="wc-productos-quick-view">';
            echo '<div class="wc-productos-quick-view-images">';
            echo woocommerce_get_product_thumbnail('full');
            echo '</div>';
            echo '<div class="wc-productos-quick-view-summary">';
            echo '<h2>' . esc_html($product->get_name()) . '</h2>';
            echo '<div class="wc-productos-quick-view-price">' . $product->get_price_html() . '</div>';
            echo '<div class="wc-productos-quick-view-description">' . wp_kses_post($product->get_short_description()) . '</div>';
            woocommerce_template_single_add_to_cart();
            echo '</div>';
            echo '</div>';
        }
        
        // Restaurar post data
        wp_reset_postdata();
        
        // Obtener HTML generado
        $html = ob_get_clean();
        
        // Enviar respuesta exitosa
        wp_send_json_success(array(
            'html' => $html,
            'product_id' => $product_id,
            'product_name' => $product->get_name()
        ));
        exit;
    }
    
    /**
     * Obtener datos de productos filtrados
     *
     * @param WP_Query $products_query Query de productos.
     * @return array Datos de productos.
     */
    private function get_filtered_products_data($products_query) {
        // Iniciar buffer de salida
        ob_start();
        
        // Generar HTML de productos
        if ($products_query->have_posts()) {
            woocommerce_product_loop_start();
            
            while ($products_query->have_posts()) {
                $products_query->the_post();
                global $product;
                
                // Cargar template part para producto
                $template_path = WC_PRODUCTOS_TEMPLATE_TEMPLATES_DIR . 'content-product.php';
                if (file_exists($template_path)) {
                    include($template_path);
                } else {
                    // Fallback al template estándar de WooCommerce
                    wc_get_template_part('content', 'product');
                }
            }
            
            woocommerce_product_loop_end();
        } else {
            echo '<div class="wc-productos-no-results">';
            echo '<p>' . esc_html__('No se encontraron productos que coincidan con su búsqueda.', 'wc-productos-template') . '</p>';
            echo '</div>';
        }
        
        // Obtener HTML de productos
        $products_html = ob_get_clean();
        
        // Generar paginación
        ob_start();
        $this->render_pagination($products_query);
        $pagination_html = ob_get_clean();
        
        // Restaurar post data original
        wp_reset_postdata();
        
        // Preparar respuesta
        return array(
            'products'     => $products_html,
            'pagination'   => $pagination_html,
            'total'        => $products_query->found_posts,
            'current_page' => $products_query->get('paged'),
            'max_pages'    => $products_query->max_num_pages
        );
    }
    
    /**
     * Renderizar paginación
     *
     * @param WP_Query $products_query Query de productos.
     */
    private function render_pagination($products_query) {
        // Obtener variables
        $current_page = max(1, $products_query->get('paged'));
        $total_pages = $products_query->max_num_pages;
        
        // No mostrar paginación si solo hay una página
        if ($total_pages <= 1) {
            return;
        }
        
        echo '<div class="wc-productos-pagination">';
        
        // Información de paginación
        echo '<div class="wc-productos-pagination-info">';
        
        $per_page = $products_query->get('posts_per_page');
        $total = $products_query->found_posts;
        $first = (($current_page - 1) * $per_page) + 1;
        $last = min($total, $current_page * $per_page);
        
        if ($total <= 1) {
            echo sprintf(
                __('Mostrando el único resultado', 'wc-productos-template')
            );
        } else if ($first === $last) {
            echo sprintf(
                __('Mostrando el resultado %d de %d', 'wc-productos-template'),
                $first,
                $total
            );
        } else {
            echo sprintf(
                __('Mostrando %d-%d de %d resultados', 'wc-productos-template'),
                $first,
                $last,
                $total
            );
        }
        
        echo '</div>';
        
        // Enlaces de paginación
        echo '<div class="wc-productos-pagination-links">';
        
        // Botón anterior
        if ($current_page > 1) {
            echo '<a href="javascript:void(0);" class="wc-productos-page-prev" data-page="' . ($current_page - 1) . '">' .
                '<i class="fas fa-chevron-left"></i>' .
                '<span class="screen-reader-text">' . __('Página anterior', 'wc-productos-template') . '</span>' .
                '</a>';
        }
        
        // Números de página
        $start_page = max(1, $current_page - 2);
        $end_page = min($total_pages, $current_page + 2);
        
        if ($start_page > 1) {
            echo '<a href="javascript:void(0);" class="wc-productos-page-number" data-page="1">1</a>';
            
            if ($start_page > 2) {
                echo '<span class="wc-productos-page-dots">...</span>';
            }
        }
        
        for ($i = $start_page; $i <= $end_page; $i++) {
            $active_class = ($i === $current_page) ? ' wc-productos-page-current' : '';
            echo '<a href="javascript:void(0);" class="wc-productos-page-number' . $active_class . '" data-page="' . $i . '">' . $i . '</a>';
        }
        
        if ($end_page < $total_pages) {
            if ($end_page < $total_pages - 1) {
                echo '<span class="wc-productos-page-dots">...</span>';
            }
            
            echo '<a href="javascript:void(0);" class="wc-productos-page-number" data-page="' . $total_pages . '">' . $total_pages . '</a>';
        }
        
        // Botón siguiente
        if ($current_page < $total_pages) {
            echo '<a href="javascript:void(0);" class="wc-productos-page-next" data-page="' . ($current_page + 1) . '">' .
                '<i class="fas fa-chevron-right"></i>' .
                '<span class="screen-reader-text">' . __('Página siguiente', 'wc-productos-template') . '</span>' .
                '</a>';
        }
        
        echo '</div>';
        echo '</div>';
    }
    
    /**
     * Registrar mensajes de log para depuración
     *
     * @param string $message Mensaje a registrar.
     */
    private function log_ajax_request($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('WC Productos Template AJAX - ' . $message);
        }
    }
}
