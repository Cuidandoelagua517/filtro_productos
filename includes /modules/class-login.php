<?php
/**
 * Módulo Login - Manejo de inicio de sesión y registro
 * 
 * @package WC_Productos_Template
 */

class WC_Productos_Template_Login {
    
    /**
     * El cargador que es responsable de mantener y registrar todos los hooks del plugin.
     *
     * @var WC_Productos_Template_Loader $loader
     */
    private $loader;
    
    /**
     * Inicializar el módulo Login.
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
        // Modificar comportamiento para usuarios no logueados
        $this->loader->add_filter('woocommerce_get_price_html', $this, 'replace_price_with_login_button', 10, 2);
        $this->loader->add_filter('woocommerce_loop_add_to_cart_link', $this, 'replace_add_to_cart_button', 10, 2);
        
        // AJAX para manejar el popup de login
        $this->loader->add_action('wp_ajax_nopriv_wc_productos_get_login_form', $this, 'ajax_get_login_form');
        $this->loader->add_action('wp_ajax_nopriv_wc_productos_login', $this, 'ajax_process_login');
        $this->loader->add_action('wp_ajax_nopriv_wc_productos_register', $this, 'ajax_process_register');
        
        // Añadir scripts y estilos específicos para usuarios no logueados
        $this->loader->add_action('wp_enqueue_scripts', $this, 'enqueue_guest_scripts');
        
        // Personalizar formulario de login de WooCommerce
        $this->loader->add_action('woocommerce_login_form_start', $this, 'customize_login_form_start');
        $this->loader->add_action('woocommerce_login_form_end', $this, 'customize_login_form_end');
        
        // Personalizar página de mi cuenta para mostrar mensajes de bienvenida
        $this->loader->add_action('woocommerce_account_dashboard', $this, 'customize_account_dashboard');
        
        // Redireccionar después del login/registro
        $this->loader->add_filter('woocommerce_login_redirect', $this, 'login_redirect', 10, 2);
        $this->loader->add_filter('woocommerce_registration_redirect', $this, 'registration_redirect');
    }
    
    /**
     * Reemplazar el precio con un botón de login para usuarios no logueados
     * 
     * @param string $price_html HTML del precio.
     * @param WC_Product $product Objeto del producto.
     * @return string HTML modificado.
     */
    public function replace_price_with_login_button($price_html, $product) {
        // Solo aplicar si el usuario no está logueado
        if (is_user_logged_in() || is_admin()) {
            return $price_html;
        }
        
        // No modificar en páginas de checkout o carrito
        if (is_checkout() || is_cart()) {
            return $price_html;
        }
        
        $product_id = $product->get_id();
        
        // Reemplazar el precio con un botón para ver el precio
        return '<div class="wc-productos-price-login">
                <a href="#" class="wc-productos-login-to-view" data-product-id="' . esc_attr($product_id) . '">
                    <i class="fas fa-lock"></i> ' . esc_html__('Ver Precio', 'wc-productos-template') . '
                </a>
                </div>';
    }
    
    /**
     * Reemplazar el botón de añadir al carrito para usuarios no logueados
     * 
     * @param string $button HTML del botón.
     * @param WC_Product $product Objeto del producto.
     * @return string HTML modificado.
     */
    public function replace_add_to_cart_button($button, $product) {
        // Solo aplicar si el usuario no está logueado
        if (is_user_logged_in() || is_admin()) {
            return $button;
        }
        
        // No modificar en páginas de checkout o carrito
        if (is_checkout() || is_cart()) {
            return $button;
        }
        
        $product_id = $product->get_id();
        
        // Reemplazar el botón con un enlace para iniciar sesión
        return '<a href="#" class="button wc-productos-login-to-view" data-product-id="' . esc_attr($product_id) . '">
                <i class="fas fa-lock"></i> ' . esc_html__('Ver Detalles', 'wc-productos-template') . '
                </a>';
    }
    
    /**
     * Cargar scripts y estilos para usuarios no logueados
     */
    public function enqueue_guest_scripts() {
        // Solo para usuarios no logueados y en páginas relevantes
        if (is_user_logged_in() || is_admin() || is_checkout() || is_cart()) {
            return;
        }
        
        // Obtener el módulo Core para verificar si estamos en una página relevante
        $core_module = $this->loader->get_module('WC_Productos_Template_Core');
        
        // Si el módulo Core no está disponible o no es una página relevante, salir
        if (!$core_module || !method_exists($core_module, 'is_product_page') || !$core_module->is_product_page()) {
            return;
        }
        
        // Cargar CSS y JS para el popup de login
        wp_enqueue_style(
            'wc-productos-login-popup',
            WC_PRODUCTOS_TEMPLATE_URL . 'assets/css/components/login.css',
            array(),
            WC_PRODUCTOS_TEMPLATE_VERSION
        );
        
        wp_enqueue_script(
            'wc-productos-login',
            WC_PRODUCTOS_TEMPLATE_URL . 'assets/js/modules/login.js',
            array('jquery'),
            WC_PRODUCTOS_TEMPLATE_VERSION,
            true
        );
        
        // Localizar script con parámetros necesarios
        wp_localize_script('wc-productos-login', 'WCProductosLogin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wc_productos_template_nonce'),
            'i18n' => array(
                'login_required' => __('Necesitas iniciar sesión para ver esta información', 'wc-productos-template'),
                'login_error' => __('Error al iniciar sesión', 'wc-productos-template'),
                'register_error' => __('Error al registrarse', 'wc-productos-template'),
                'connection_error' => __('Error de conexión', 'wc-productos-template'),
                'loading' => __('Cargando...', 'wc-productos-template')
            ),
            'login_redirect' => apply_filters('wc_productos_login_redirect', wc_get_page_permalink('shop')),
            'is_logged_in' => is_user_logged_in()
        ));
    }
    
    /**
     * Obtener formulario de login vía AJAX
     */
    public function ajax_get_login_form() {
        // Verificar nonce
        check_ajax_referer('wc_productos_template_nonce', 'security');
        
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
            // Crear un formulario básico si no existe el template
            ?>
            <div class="wc-productos-login-container">
                <div class="wc-productos-login-tabs">
                    <a href="#login" class="wc-productos-login-tab active">
                        <i class="fas fa-sign-in-alt"></i>
                        <?php esc_html_e('Iniciar Sesión', 'wc-productos-template'); ?>
                    </a>
                    <a href="#register" class="wc-productos-register-tab">
                        <i class="fas fa-user-plus"></i>
                        <?php esc_html_e('Crear Cuenta', 'wc-productos-template'); ?>
                    </a>
                </div>
                
                <div class="wc-productos-login-forms">
                    <!-- Formulario de Login -->
                    <div class="wc-productos-login-form-wrapper">
                        <form class="wc-productos-login-form" id="wc-productos-login-form" method="post">
                            <?php wp_nonce_field('wc_productos_template_nonce', 'wc_productos_login_nonce'); ?>
                            <input type="hidden" name="action" value="wc_productos_login" />
                            <input type="hidden" name="redirect" id="login-redirect-url" value="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" />
                            
                            <div class="wc-productos-form-row">
                                <label for="wc-productos-username"><?php esc_html_e('Correo electrónico', 'wc-productos-template'); ?></label>
                                <div class="wc-productos-input-icon">
                                    <i class="fas fa-envelope"></i>
                                    <input type="text" name="username" id="wc-productos-username" class="wc-productos-input" placeholder="<?php esc_attr_e('Tu email', 'wc-productos-template'); ?>" required />
                                </div>
                            </div>
                            
                            <div class="wc-productos-form-row">
                                <label for="wc-productos-password"><?php esc_html_e('Contraseña', 'wc-productos-template'); ?></label>
                                <div class="wc-productos-input-icon wc-productos-password-field">
                                    <i class="fas fa-lock"></i>
                                    <input type="password" name="password" id="wc-productos-password" class="wc-productos-input" placeholder="<?php esc_attr_e('Tu contraseña', 'wc-productos-template'); ?>" required />
                                    <span class="wc-productos-password-toggle" role="button">
                                        <i class="fas fa-eye"></i>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="wc-productos-form-row wc-productos-remember-row">
                                <label class="wc-productos-checkbox">
                                    <input type="checkbox" name="rememberme" id="wc-productos-rememberme" value="forever" />
                                    <span><?php esc_html_e('Recordarme', 'wc-productos-template'); ?></span>
                                </label>
                                <a href="<?php echo esc_url(wp_lostpassword_url()); ?>" class="wc-productos-lost-password">
                                    <?php esc_html_e('¿Olvidaste tu contraseña?', 'wc-productos-template'); ?>
                                </a>
                            </div>
                            
                            <div class="wc-productos-form-row">
                                <button type="submit" class="wc-productos-button wc-productos-login-button">
                                    <i class="fas fa-sign-in-alt"></i>
                                    <?php esc_html_e('Iniciar Sesión', 'wc-productos-template'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Formulario de Registro -->
                    <div class="wc-productos-register-form-wrapper" style="display: none;">
                        <form class="wc-productos-register-form" id="wc-productos-register-form" method="post">
                            <?php wp_nonce_field('wc_productos_template_nonce', 'wc_productos_register_nonce'); ?>
                            <input type="hidden" name="action" value="wc_productos_register" />
                            
                            <div class="wc-productos-form-row">
                                <label for="wc-productos-reg-email"><?php esc_html_e('Correo electrónico', 'wc-productos-template'); ?></label>
                                <div class="wc-productos-input-icon">
                                    <i class="fas fa-envelope"></i>
                                    <input type="email" name="email" id="wc-productos-reg-email" class="wc-productos-input" placeholder="<?php esc_attr_e('Tu email', 'wc-productos-template'); ?>" required />
                                </div>
                            </div>
                            
                            <div class="wc-productos-form-row wc-productos-form-row-half">
                                <label for="wc-productos-reg-first-name"><?php esc_html_e('Nombre', 'wc-productos-template'); ?></label>
                                <div class="wc-productos-input-icon">
                                    <i class="fas fa-user"></i>
                                    <input type="text" name="first_name" id="wc-productos-reg-first-name" class="wc-productos-input" placeholder="<?php esc_attr_e('Tu nombre', 'wc-productos-template'); ?>" required />
                                </div>
                            </div>
                            
                            <div class="wc-productos-form-row wc-productos-form-row-half">
                                <label for="wc-productos-reg-last-name"><?php esc_html_e('Apellidos', 'wc-productos-template'); ?></label>
                                <div class="wc-productos-input-icon">
                                    <i class="fas fa-user"></i>
                                    <input type="text" name="last_name" id="wc-productos-reg-last-name" class="wc-productos-input" placeholder="<?php esc_attr_e('Tus apellidos', 'wc-productos-template'); ?>" required />
                                </div>
                            </div>
                            
                            <div class="wc-productos-form-row wc-productos-form-row-half">
                                <label for="wc-productos-reg-password"><?php esc_html_e('Contraseña', 'wc-productos-template'); ?></label>
                                <div class="wc-productos-input-icon wc-productos-password-field">
                                    <i class="fas fa-lock"></i>
                                    <input type="password" name="password" id="wc-productos-reg-password" class="wc-productos-input" placeholder="<?php esc_attr_e('Tu contraseña', 'wc-productos-template'); ?>" required />
                                    <span class="wc-productos-password-toggle" role="button">
                                        <i class="fas fa-eye"></i>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="wc-productos-form-row wc-productos-form-row-half">
                                <label for="wc-productos-reg-password-confirm"><?php esc_html_e('Confirmar contraseña', 'wc-productos-template'); ?></label>
                                <div class="wc-productos-input-icon wc-productos-password-field">
                                    <i class="fas fa-lock"></i>
                                    <input type="password" name="password_confirm" id="wc-productos-reg-password-confirm" class="wc-productos-input" placeholder="<?php esc_attr_e('Confirma tu contraseña', 'wc-productos-template'); ?>" required />
                                    <span class="wc-productos-password-toggle" role="button">
                                        <i class="fas fa-eye"></i>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="wc-productos-form-row">
                                <label class="wc-productos-checkbox">
                                    <input type="checkbox" name="privacy_policy" id="wc-productos-privacy-policy" value="1" required />
                                    <span><?php 
                                        printf(
                                            esc_html__('He leído y acepto la %spolítica de privacidad%s', 'wc-productos-template'),
                                            '<a href="' . esc_url(get_privacy_policy_url()) . '" target="_blank">',
                                            '</a>'
                                        ); 
                                    ?></span>
                                </label>
                            </div>
                            
                            <div class="wc-productos-form-row">
                                <button type="submit" class="wc-productos-button wc-productos-register-button">
                                    <i class="fas fa-user-plus"></i>
                                    <?php esc_html_e('Crear Cuenta', 'wc-productos-template'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php
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
     * Procesar login vía AJAX
     */
    public function ajax_process_login() {
        // Verificar nonce
        check_ajax_referer('wc_productos_template_nonce', 'wc_productos_login_nonce');
        
        // Obtener credenciales
        $username = isset($_POST['username']) ? sanitize_user($_POST['username']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $remember = isset($_POST['rememberme']) ? (bool)$_POST['rememberme'] : false;
        $redirect = isset($_POST['redirect']) ? esc_url_raw($_POST['redirect']) : '';
        
        // Verificar campos obligatorios
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
        
        // Añadir parámetro para forzar recarga de página
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
     * Procesar registro vía AJAX
     */
    public function ajax_process_register() {
        // Verificar nonce
        check_ajax_referer('wc_productos_template_nonce', 'wc_productos_register_nonce');
        
        // Obtener datos del formulario
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $password_confirm = isset($_POST['password_confirm']) ? $_POST['password_confirm'] : '';
        $first_name = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
        $last_name = isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '';
        
        // Verificar campos obligatorios
        if (empty($email) || !is_email($email)) {
            wp_send_json_error(array('message' => __('Por favor, ingrese un email válido', 'wc-productos-template')));
            exit;
        }
        
        // Verificar si el email ya está registrado
        if (email_exists($email)) {
            wp_send_json_error(array('message' => __('Este email ya está registrado. Por favor, utilice otro email o inicie sesión', 'wc-productos-template')));
            exit;
        }
        
        // Verificar contraseña
        if (empty($password)) {
            wp_send_json_error(array('message' => __('La contraseña es obligatoria', 'wc-productos-template')));
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
        
        // Crear nuevo cliente
        try {
            // Generar nombre de usuario a partir del email
            $username = sanitize_user(current(explode('@', $email)), true);
            
            // Si el nombre de usuario ya existe, añadir un sufijo aleatorio
            if (username_exists($username)) {
                $username .= '_' . substr(md5(time()), 0, 6);
            }
            
            // Crear el cliente
            $customer_id = wc_create_new_customer($email, $username, $password);
            
            // Verificar si hubo error
            if (is_wp_error($customer_id)) {
                throw new Exception($customer_id->get_error_message());
            }
            
            // Actualizar datos adicionales
            if (!empty($first_name)) {
                update_user_meta($customer_id, 'first_name', $first_name);
                update_user_meta($customer_id, 'billing_first_name', $first_name);
                update_user_meta($customer_id, 'shipping_first_name', $first_name);
            }
            
            if (!empty($last_name)) {
                update_user_meta($customer_id, 'last_name', $last_name);
                update_user_meta($customer_id, 'billing_last_name', $last_name);
                update_user_meta($customer_id, 'shipping_last_name', $last_name);
            }
            
            // Registrar aceptación de política de privacidad
            update_user_meta($customer_id, 'privacy_policy_consent', 'yes');
            update_user_meta($customer_id, 'privacy_policy_consent_date', current_time('mysql'));
            
            // Iniciar sesión automáticamente
            wp_set_current_user($customer_id);
            wp_set_auth_cookie($customer_id);
            
            // Obtener URL de redirección
            $redirect_url = apply_filters('wc_productos_registration_redirect', wc_get_page_permalink('myaccount'));
            
            // Añadir parámetro para forzar recarga de página
            $redirect_url = add_query_arg('wc_refresh', time(), $redirect_url);
            
            // Enviar respuesta exitosa
            wp_send_json_success(array(
                'message' => __('Registro exitoso, redirigiendo...', 'wc-productos-template'),
                'redirect_url' => $redirect_url,
                'user_id' => $customer_id
            ));
            exit;
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
            exit;
        }
    }
    
    /**
     * Personalizar inicio del formulario de login de WooCommerce
     */
    public function customize_login_form_start() {
        // Añadir clases y estilos personalizados
        echo '<div class="wc-productos-woocommerce-login">';
    }
    
    /**
     * Personalizar final del formulario de login de WooCommerce
     */
    public function customize_login_form_end() {
        echo '</div>';
    }
    
    /**
     * Personalizar el dashboard de la cuenta
     */
    public function customize_account_dashboard() {
        // Obtener datos del usuario actual
        $current_user = wp_get_current_user();
        $first_name = $current_user->first_name;
        
        // Mostrar mensaje de bienvenida personalizado
        echo '<div class="wc-productos-account-welcome">';
        
        if (!empty($first_name)) {
            echo '<h2>' . sprintf(__('¡Hola, %s!', 'wc-productos-template'), esc_html($first_name)) . '</h2>';
        } else {
            echo '<h2>' . __('¡Bienvenido a tu cuenta!', 'wc-productos-template') . '</h2>';
        }
        
        echo '<p>' . __('Desde aquí puedes gestionar tus compras, ver tus pedidos y actualizar tu información personal.', 'wc-productos-template') . '</p>';
        
        echo '</div>';
    }
    
    /**
     * Redireccionar después del login
     * 
     * @param string $redirect URL de redirección.
     * @param WP_User $user Usuario que inicia sesión.
     * @return string URL de redirección modificada.
     */
    public function login_redirect($redirect, $user) {
        // Si hay un parámetro redirect en la URL, usarlo
        if (isset($_GET['redirect']) && !empty($_GET['redirect'])) {
            $requested_redirect = esc_url_raw($_GET['redirect']);
            
            // Verificar que la URL pertenece al sitio
            if (strpos($requested_redirect, home_url()) === 0) {
                return $requested_redirect;
            }
        }
        
        // Si venimos de una página de producto, redirigir a esa página
        $referer = wp_get_referer();
        if ($referer && strpos($referer, home_url()) === 0) {
            // Verificar si es una página de producto
            if (strpos($referer, '/product/') !== false) {
                return $referer;
            }
        }
        
        // Por defecto, redirigir a la tienda
        return wc_get_page_permalink('shop');
    }
    
    /**
     * Redireccionar después del registro
     * 
     * @param string $redirect URL de redirección.
     * @return string URL de redirección modificada.
     */
    public function registration_redirect($redirect) {
        // Redirigir a la tienda
        return wc_get_page_permalink('shop');
    }
}
