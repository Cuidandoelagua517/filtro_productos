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
        add_action('wp_ajax_productos_search', array($this, 'ajax_search_products'));
        add_action('wp_ajax_nopriv_productos_search', array($this, 'ajax_search_products'));
        add_action('wp_ajax_productos_search', array($this, 'ajax_search_products'));
        add_action('wp_ajax_nopriv_productos_search', array($this, 'ajax_search_products'));
        
        // Endpoints para el popup de login
        add_action('wp_ajax_dpc_get_login_form', array($this, 'ajax_get_login_form'));
        add_action('wp_ajax_nopriv_dpc_get_login_form', array($this, 'ajax_get_login_form'));
        add_action('wp_ajax_nopriv_mam_ajax_login', array($this, 'ajax_process_login'));
    // Añade esta línea al constructor de la clase WC_Productos_Template, junto a los otros endpoints AJAX
add_action('wp_ajax_nopriv_mam_ajax_register', array($this, 'ajax_process_register'));

        
        // En el constructor:
        $this->integrate_with_woocommerce_search();
        
        // Cargar clases adicionales si existen
        $this->load_classes();
        
        // Agregar hook para configurar filtros cuando WordPress esté listo
        add_action('wp_loaded', array($this, 'setup_user_filters'));
    }
}

/**
 * Configura los filtros para usuarios no logueados
 * Este método se ejecuta después de que WordPress está completamente cargado
 */
public function setup_user_filters() {
    // Ahora podemos usar funciones de WordPress con seguridad
    if (!is_user_logged_in()) {
        add_filter('woocommerce_get_price_html', array($this, 'replace_price_with_login_button'), 10, 2);
        add_filter('woocommerce_loop_add_to_cart_link', array($this, 'replace_add_to_cart_button'), 10, 2);
    }
}
    /**
 * Reemplazar el botón "Añadir al carrito" con un botón "Ver Precio" para usuarios no logueados
 */
public function replace_add_to_cart_button($html, $product) {
    $product_id = $product->get_id();
    return '<a href="#" class="dpc-login-to-view button" data-product-id="' . esc_attr($product_id) . '">' . 
        __('Ver Precio', 'wc-productos-template') . '</a>';
}
        /**
 * Función para registrar y cargar el script de búsqueda directa
 * Añadir al método register_scripts() en la clase WC_Productos_Template
 */
public function register_search_script() {
    // Verificar si estamos en una página donde se necesita el script
    if (!$this->is_product_page()) {
        return;
    }
    
    // Ruta al script de búsqueda directa
    $search_script_file = WC_PRODUCTOS_TEMPLATE_ASSETS_DIR . 'js/producto-search-direct.js';
    
    // Si el archivo no existe, crear uno nuevo con el contenido de la solución
    if (!file_exists($search_script_file)) {
        $this->create_search_script_file($search_script_file);
    }
    
    // Registrar y encolar el script
    wp_enqueue_script(
        'wc-productos-search-direct',
        WC_PRODUCTOS_TEMPLATE_URL . 'assets/js/producto-search-direct.js',
        array('jquery', 'wc-productos-template-script'),
        WC_PRODUCTOS_TEMPLATE_VERSION . '.' . time(),
        true
    );
    
    // Localizar el script con parámetros necesarios
    wp_localize_script('wc-productos-search-direct', 'WCProductosSearch', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('productos_filter_nonce'),
        'action' => 'productos_filter', // O 'productos_search' si se usa el nuevo endpoint
        'loading_text' => __('Buscando productos...', 'wc-productos-template'),
        'error_text' => __('Error al buscar productos. Intente nuevamente.', 'wc-productos-template'),
        'no_results_text' => __('No se encontraron productos que coincidan con su búsqueda.', 'wc-productos-template'),
        'current_search' => get_search_query()
    ));
    
    // Añadir script inline para inicializar inmediatamente la búsqueda
    wp_add_inline_script('wc-productos-search-direct', '
        jQuery(document).ready(function($) {
            // Inicializar inmediatamente para conectar la búsqueda
            if (typeof window.connectProductSearch === "function") {
                window.connectProductSearch();
                console.log("Búsqueda directa inicializada");
            }
        });
    ');
}

/**
 * Función para crear el archivo de script de búsqueda directa
 */
private function create_search_script_file($file_path) {
    // Contenido del script (usar el contenido del primer artefacto)
    $js_content = <<<'EOT'
/**
 * SOLUCIÓN DIRECTA: Conectar barra de búsqueda con AJAX
 */

jQuery(document).ready(function($) {
    console.log('Inicializando conexión directa de búsqueda con AJAX');
    
    // Función para realizar la búsqueda AJAX
    function executeSearch(searchTerm) {
        console.log('Ejecutando búsqueda AJAX para:', searchTerm);
        
        // Mostrar mensaje de carga
        var $mainContent = $('.wc-productos-template .productos-main');
        if (!$mainContent.find('.loading').length) {
            $mainContent.append('<div class="loading">' + 
                (typeof WCProductosSearch !== 'undefined' ? 
                WCProductosSearch.loading_text : 'Buscando productos...') + 
                '</div>');
        }
        
        // Realizar petición AJAX
        $.ajax({
            url: typeof WCProductosSearch !== 'undefined' ? WCProductosSearch.ajaxurl : ajaxurl,
            type: 'POST',
            data: {
                action: typeof WCProductosSearch !== 'undefined' ? WCProductosSearch.action : 'productos_filter',
                nonce: typeof WCProductosSearch !== 'undefined' ? WCProductosSearch.nonce : '',
                page: 1, // Siempre empezar en página 1 para búsquedas
                search: searchTerm
            },
            success: function(response) {
                console.log('Respuesta de búsqueda recibida:', response);
                
                // Eliminar mensaje de carga
                $mainContent.find('.loading').remove();
                
                if (response.success) {
                    // Actualizar productos
                    var $productsWrapper = $('.wc-productos-template .productos-wrapper');
                    
                    if ($productsWrapper.length) {
                        // Eliminar cuadrícula anterior y mensaje de no productos
                        $productsWrapper.find('ul.products, .productos-grid, .woocommerce-info, .no-products-found').remove();
                        
                        // Insertar nuevo HTML
                        $productsWrapper.prepend(response.data.products);
                        
                        // Actualizar paginación si existe
                        if (response.data.pagination) {
                            var $pagination = $('.wc-productos-template .productos-pagination');
                            if ($pagination.length) {
                                $pagination.replaceWith(response.data.pagination);
                            } else {
                                $productsWrapper.append(response.data.pagination);
                            }
                        }
                        
                        // Actualizar URL con el término de búsqueda
                        if (window.history && window.history.replaceState) {
                            var url = new URL(window.location.href);
                            url.searchParams.set('s', searchTerm);
                            window.history.replaceState({}, '', url.toString());
                        }
                        
                        // Forzar cuadrícula y enlazar eventos
                        setTimeout(function() {
                            if (typeof forceGridLayout === 'function') {
                                forceGridLayout();
                            }
                            bindSearchPaginationEvents();
                        }, 100);
                    } else {
                        console.error('No se encontró el contenedor de productos (.productos-wrapper)');
                    }
                } else {
                    console.error('Error en la respuesta AJAX:', response);
                    $mainContent.append('<div class="woocommerce-info">' + 
                        (typeof WCProductosSearch !== 'undefined' ? 
                        WCProductosSearch.error_text : 'Error al buscar productos. Intente nuevamente.') + 
                        '</div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error AJAX:', status, error);
                $mainContent.find('.loading').remove();
                $mainContent.append('<div class="woocommerce-info">' + 
                    (typeof WCProductosSearch !== 'undefined' ? 
                    WCProductosSearch.error_text : 'Error al buscar productos. Intente nuevamente.') + 
                    '</div>');
            }
        });
    }
    
    // Resto del código del primer artefacto...
    // [...]
    
    // Exponer funciones para uso global
    window.connectProductSearch = connectSearchBar;
    window.executeProductSearch = executeSearch;
});
EOT;

    // Guardar el archivo
    file_put_contents($file_path, $js_content);
    
    return true;
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
            // Crear el archivo de template de login si no existe
$login_template_path = WC_PRODUCTOS_TEMPLATE_TEMPLATES_DIR . 'login-form.php';
if (!file_exists($login_template_path) && file_exists(WC_PRODUCTOS_TEMPLATE_DIR . 'templates/login-form.php')) {
    copy(WC_PRODUCTOS_TEMPLATE_DIR . 'templates/login-form.php', $login_template_path);
}
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
// Modificar la línea donde se registra el CSS del popup
wp_register_style(
    'dpc-carousel-popup',
    WC_PRODUCTOS_TEMPLATE_URL . 'assets/css/carousel-popup.css',
    array(), // Quitar la dependencia para asegurar que se cargue
    WC_PRODUCTOS_TEMPLATE_VERSION . '.' . time()
);
    
    // Registrar JS para el popup de login/registro
    wp_register_script(
        'dpc-carousel-popup',
        WC_PRODUCTOS_TEMPLATE_URL . 'assets/js/carousel-popup.js',
        array('jquery'),
        WC_PRODUCTOS_TEMPLATE_VERSION . '.' . time(),
        true
    );
    // Localizar el script para el popup de login
wp_localize_script('dpc-carousel-popup', 'dpcConfig', array(
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'loginFormNonce' => wp_create_nonce('dpc_login_form_nonce'),
    'i18n' => array(
        'loginRequired' => __('Necesitas iniciar sesión para ver esta información', 'wc-productos-template'),
        'loginError' => __('Error al iniciar sesión', 'wc-productos-template'),
        'connectionError' => __('Error de conexión', 'wc-productos-template')
    ),
    'isLoggedIn' => is_user_logged_in()
));
    // Si el usuario no está logueado, cargar estos scripts automáticamente
    if (!is_user_logged_in()) {
        wp_enqueue_style('dpc-carousel-popup');
        wp_enqueue_script('dpc-carousel-popup');
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
 * Endpoint AJAX para obtener el formulario de login/registro
 */
public function ajax_get_login_form() {
    // Verificar nonce de seguridad
    check_ajax_referer('dpc_login_form_nonce', 'security');
    
    // Si el usuario ya está logueado, devolver mensaje
    if (is_user_logged_in()) {
        wp_send_json_success(array(
            'html' => '<div class="dpc-already-logged-in">' . __('Ya has iniciado sesión', 'dynamic-product-carousel') . '</div>',
            'redirect_url' => wp_get_referer() ? wp_get_referer() : home_url()
        ));
        return;
    }
    
    // Buffer de salida para capturar el template
    ob_start();
    
    // Comprobar si existe el template y luego incluirlo
    $template_path = WC_PRODUCTOS_TEMPLATE_DIR . 'templates/login-form.php';
    if (file_exists($template_path)) {
        include($template_path);
    } else {
        wp_send_json_error(array(
            'message' => __('Template del formulario de login no encontrado', 'dynamic-product-carousel')
        ));
        return;
    }
    
    // Obtener HTML generado
    $html = ob_get_clean();
    
    // Enviar respuesta exitosa con HTML
    wp_send_json_success(array(
        'html' => $html,
        'redirect_url' => wp_get_referer() ? wp_get_referer() : home_url()
    ));
}

/**
 * Endpoint AJAX para procesar el registro de usuarios - VERSIÓN CORREGIDA
 */
public function ajax_process_register() {
    // Verificar nonce de seguridad
    check_ajax_referer('mam-nonce', 'security');
    
    // Si el usuario ya está logueado, devolver mensaje
    if (is_user_logged_in()) {
        wp_send_json_error(array(
            'message' => __('Ya has iniciado sesión', 'wc-productos-template')
        ));
        return;
    }
    
    // Validar campos requeridos
    $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
    if (empty($email)) {
        wp_send_json_error(array(
            'message' => __('El correo electrónico es obligatorio', 'wc-productos-template')
        ));
        return;
    }
    
    // Extraer los datos del formulario
    $username = '';
    if (isset($_POST['username']) && 'no' === get_option('woocommerce_registration_generate_username')) {
        $username = sanitize_user($_POST['username']);
    }
    
    $password = '';
    $password_generated = false;
    if (isset($_POST['password']) && !empty($_POST['password']) && 'no' === get_option('woocommerce_registration_generate_password')) {
        $password = $_POST['password'];
    } else {
        $password = wp_generate_password();
        $password_generated = true;
    }

    // Datos de empresa y CUIT
    $company_name = isset($_POST['company_name']) ? sanitize_text_field($_POST['company_name']) : '';
    $cuit = isset($_POST['cuit']) ? sanitize_text_field($_POST['cuit']) : '';
    
    // Datos personales
    $first_name = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
    $last_name = isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '';
    $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
    
    // Verificar política de privacidad
    if (!isset($_POST['privacy_policy']) || empty($_POST['privacy_policy'])) {
        wp_send_json_error(array(
            'message' => __('Debes aceptar la política de privacidad', 'wc-productos-template')
        ));
        return;
    }
    
    // Verificar que el correo no esté ya registrado
    if (email_exists($email)) {
        wp_send_json_error(array(
            'message' => __('Este correo electrónico ya está registrado. Por favor, inicia sesión.', 'wc-productos-template')
        ));
        return;
    }
    
    // Si necesitamos generar nombre de usuario
    if (empty($username)) {
        $username = wc_create_new_customer_username($email);
    }
    
    // Crear el nuevo usuario - IMPORTANTE: No insertamos directamente, usamos la función de WooCommerce
    try {
        $new_customer_data = array(
            'user_login' => $username,
            'user_pass'  => $password,
            'user_email' => $email,
            'role'       => 'customer',
            'first_name' => $first_name,
            'last_name'  => $last_name,
        );
        
        // CORREGIDO: Usar la función de WooCommerce para crear clientes
        // Esta función se encarga de enviar el email correctamente
        $user_id = wc_create_new_customer(
            $email,
            $username, 
            $password
        );
        
        // Verificar si hubo error
        if (is_wp_error($user_id)) {
            wp_send_json_error(array(
                'message' => $user_id->get_error_message()
            ));
            return;
        }
        
        // Guardar campos personalizados (metadatos)
        if (!empty($first_name)) {
            update_user_meta($user_id, 'first_name', $first_name);
            update_user_meta($user_id, 'billing_first_name', $first_name);
        }
        
        if (!empty($last_name)) {
            update_user_meta($user_id, 'last_name', $last_name);
            update_user_meta($user_id, 'billing_last_name', $last_name);
        }
        
        if (!empty($company_name)) {
            update_user_meta($user_id, 'billing_company', $company_name);
            update_user_meta($user_id, 'company_name', $company_name);
        }
        
        if (!empty($cuit)) {
            update_user_meta($user_id, 'billing_cuit', $cuit);
            update_user_meta($user_id, 'cuit', $cuit);
        }
        
        if (!empty($phone)) {
            update_user_meta($user_id, 'billing_phone', $phone);
            update_user_meta($user_id, 'phone', $phone);
        }
        
        // Registrar fecha de aceptación de política de privacidad
        if (isset($_POST['privacy_policy']) && !empty($_POST['privacy_policy'])) {
            update_user_meta($user_id, 'privacy_policy_consent', 'yes');
            update_user_meta($user_id, 'privacy_policy_consent_date', current_time('mysql'));
        }
        
        // Enviar email de bienvenida con instrucciones para establecer contraseña
$email_sent = $this->send_new_user_welcome_email($user_id, $password_generated, $password);

if (!$email_sent) {
    // Log the failure but don't expose this to the user
    error_log('Failed to send welcome email to new user ID: ' . $user_id . ', Email: ' . $email);
}

// Even if email fails, we should still let the user know they registered successfully
wp_send_json_success(array(
    'message' => $password_generated ? 
        __('Registro exitoso. Se ha enviado un correo con la información de acceso.', 'wc-productos-template') : 
        __('Registro exitoso. Iniciando sesión...', 'wc-productos-template'),
    'redirect_url' => $redirect_url,
    'user_id' => $user_id
));

        
        // Iniciar sesión automáticamente si no se generó contraseña
        if (!$password_generated) {
            wc_set_customer_auth_cookie($user_id);
        }
        
        // Determinar URL de redirección
        if (!empty($_SERVER['HTTP_REFERER'])) {
            $redirect_url = $_SERVER['HTTP_REFERER'];
        } else {
            $redirect_url = home_url();
        }
        
        // Añadir parámetro para forzar recarga
        $redirect_url = add_query_arg('dpc_refresh', time(), $redirect_url);
        

        
    } catch (Exception $e) {
        wp_send_json_error(array(
            'message' => $e->getMessage()
        ));
    }
}
/**
 * Función mejorada para enviar emails de WooCommerce para nuevos usuarios
 * Reemplaza la función existente en la clase WC_Productos_Template
 */
private function send_new_user_welcome_email($user_id, $password_generated, $password = '') {
    // Validar que el usuario existe
    $user = get_userdata($user_id);
    if (!$user) {
        error_log('Error: No se puede enviar email de bienvenida - ID de usuario inválido: ' . $user_id);
        return false;
    }
    
    try {
        // Asegurarse de que WooCommerce está inicializado
        if (!did_action('woocommerce_init') && function_exists('WC')) {
            WC()->init();
        }
        
        // Asegurarse de que el mailer está cargado
        if (function_exists('WC') && !WC()->mailer()) {
            WC()->mailer();
        }
        
        // Verificar que el email WC_Email_Customer_New_Account existe
        if (function_exists('WC') && isset(WC()->mailer()->emails['WC_Email_Customer_New_Account'])) {
            // Obtener la instancia del email
            $customer_email = WC()->mailer()->emails['WC_Email_Customer_New_Account'];
            
            // Asegurarse de que el email está habilitado
            $customer_email->enabled = 'yes';
            
            // Establecer datos del usuario
            $customer_email->object = $user;
            $customer_email->user_login = $user->user_login;
            $customer_email->user_email = $user->user_email;
            $customer_email->user_pass = $password;
            $customer_email->password_generated = $password_generated;
            
            // Llamar a la función trigger con los parámetros adecuados
            if (method_exists($customer_email, 'trigger')) {
                $customer_email->trigger($user_id, $password, $password_generated);
                error_log('Email de nueva cuenta enviado correctamente para el usuario: ' . $user_id);
                return true;
            } else {
                error_log('Error: Método trigger no encontrado en el objeto WC_Email_Customer_New_Account');
            }
        } else {
            error_log('Error: WC_Email_Customer_New_Account no encontrado en el mailer de WooCommerce');
            
            // Intentar usar hooks de WooCommerce como alternativa
            do_action('woocommerce_created_customer', $user_id, array(
                'user_login' => $user->user_login,
                'user_pass'  => $password,
                'user_email' => $user->user_email
            ), $password_generated);
            
            do_action('woocommerce_new_customer', $user_id);
            error_log('Intentando enviar email mediante hooks woocommerce_created_customer y woocommerce_new_customer');
            return true;
        }
    } catch (Exception $e) {
        error_log('Excepción al enviar email de nueva cuenta: ' . $e->getMessage());
    }
    
    return false;
}
/**
 * Endpoint AJAX para procesar el login
 */
public function ajax_process_login() {
    // Verificar nonce de seguridad
    check_ajax_referer('mam-nonce', 'security');
    
    // Obtener credenciales
    $username = isset($_POST['username']) ? sanitize_user($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $remember = isset($_POST['rememberme']) ? (bool)$_POST['rememberme'] : false;
    $redirect = isset($_POST['redirect']) ? esc_url_raw($_POST['redirect']) : '';
    
    // Intentar login
    $user = wp_signon(array(
        'user_login' => $username,
        'user_password' => $password,
        'remember' => $remember
    ));
    
    if (is_wp_error($user)) {
        wp_send_json_error(array(
            'message' => $user->get_error_message()
        ));
        return;
    }
    
    // Login exitoso
    wp_set_current_user($user->ID);
    
    // Determinar URL de redirección
    if (!empty($redirect)) {
        $redirect_url = $redirect;
    } elseif (!empty($_SERVER['HTTP_REFERER'])) {
        $redirect_url = $_SERVER['HTTP_REFERER'];
    } else {
        $redirect_url = home_url();
    }
    
    // Añadir parámetro para forzar recarga
    $redirect_url = add_query_arg('dpc_refresh', time(), $redirect_url);
    
    wp_send_json_success(array(
        'message' => __('Login exitoso, redirigiendo...', 'dynamic-product-carousel'),
        'redirect_url' => $redirect_url,
        'user_id' => $user->ID
    ));
}

/**
 * Manejador AJAX simplificado y enfocado en la búsqueda
 */
public function ajax_search_products() {
    // Verificar nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'productos_filter_nonce')) {
        wp_send_json_error(array('message' => 'Error de seguridad, por favor recargue la página'));
        exit;
    }
    
    // Log para depuración
    error_log('Recibida solicitud AJAX para buscar productos');
    
    // Obtener parámetros
    $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
    $search_term = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    
    // Verificar que hay un término de búsqueda
    if (empty($search_term)) {
        wp_send_json_error(array('message' => 'El término de búsqueda no puede estar vacío'));
        exit;
    }
    
    error_log('Término de búsqueda: ' . $search_term);
    
    // Configurar argumentos de la consulta
    $args = array(
        'post_type'      => 'product',
        'posts_per_page' => get_option('posts_per_page'),
        'paged'          => $page,
        'post_status'    => 'publish',
    );
    
    // Crear meta_query para buscar en múltiples campos
    $meta_query = array('relation' => 'OR');
    
    // Buscar en SKU
    $meta_query[] = array(
        'key'     => '_sku',
        'value'   => $search_term,
        'compare' => 'LIKE'
    );
    
    // Buscar en campos de atributos comunes
    $attributes = array('pa_volumen', 'pa_grado', 'pa_caracteristicas', '_volumen_ml', '_grado');
    foreach ($attributes as $attr) {
        $meta_query[] = array(
            'key'     => $attr,
            'value'   => $search_term,
            'compare' => 'LIKE'
        );
    }
    
    // Añadir búsqueda estándar (título, contenido, extracto)
    $args['s'] = $search_term;
    
    // Añadir meta_query
    $args['meta_query'] = $meta_query;
    
    // Ejecutar la consulta
    $products_query = new WP_Query($args);
    
    error_log('Productos encontrados: ' . $products_query->found_posts);
    
    // Capturar la salida del contenido de productos
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
            
            // Usar template part para producto
            wc_get_template_part('content', 'product');
        }
        
        woocommerce_product_loop_end();
    } else {
        echo '<div class="woocommerce-info">' . 
            sprintf(esc_html__('No se encontraron productos que coincidan con "%s"', 'wc-productos-template'), 
                esc_html($search_term)) . 
            '</div>';
    }
    
    $products_html = ob_get_clean();
    
    // Restablecer postdata
    wp_reset_postdata();
    
    // Generar paginación
    ob_start();
    
    if ($products_query->max_num_pages > 1) {
        echo '<div class="productos-pagination">';
        
        echo '<div class="pagination-info">';
        $start = (($page - 1) * get_option('posts_per_page')) + 1;
        $end = min($products_query->found_posts, $page * get_option('posts_per_page'));
        
        printf(
            esc_html__('Mostrando %1$d-%2$d de %3$d resultados para "%4$s"', 'wc-productos-template'),
            $start,
            $end,
            $products_query->found_posts,
            esc_html($search_term)
        );
        
        echo '</div>';
        
        echo '<div class="pagination-links">';
        
        // Botón "Anterior"
        if ($page > 1) {
            echo '<a href="javascript:void(0);" class="page-number page-prev" data-page="' . ($page - 1) . '">←</a>';
        }
        
        // Números de página
        $start_page = max(1, $page - 2);
        $end_page = min($products_query->max_num_pages, $page + 2);
        
        if ($start_page > 1) {
            echo '<a href="javascript:void(0);" class="page-number" data-page="1">1</a>';
            
            if ($start_page > 2) {
                echo '<span class="page-dots">...</span>';
            }
        }
        
        for ($i = $start_page; $i <= $end_page; $i++) {
            $active_class = ($i === $page) ? ' active' : '';
            echo '<a href="javascript:void(0);" class="page-number' . $active_class . '" data-page="' . $i . '">' . $i . '</a>';
        }
        
        if ($end_page < $products_query->max_num_pages) {
            if ($end_page < $products_query->max_num_pages - 1) {
                echo '<span class="page-dots">...</span>';
            }
            
            echo '<a href="javascript:void(0);" class="page-number" data-page="' . $products_query->max_num_pages . '">' . $products_query->max_num_pages . '</a>';
        }
        
        // Botón "Siguiente"
        if ($page < $products_query->max_num_pages) {
            echo '<a href="javascript:void(0);" class="page-number page-next" data-page="' . ($page + 1) . '">→</a>';
        }
        
        echo '</div>';
        echo '</div>';
    }
    
    $pagination = ob_get_clean();
    
    // Preparar y enviar respuesta
    wp_send_json_success(array(
        'products'     => $products_html,
        'pagination'   => $pagination,
        'total'        => $products_query->found_posts,
        'current_page' => $page,
        'max_pages'    => $products_query->max_num_pages,
        'search_term'  => $search_term,
        'message'      => sprintf(
            __('Se encontraron %d productos para "%s"', 'wc-productos-template'),
            $products_query->found_posts,
            $search_term
        )
    ));
    
    exit;
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
 * AJAX handler para filtrar productos - VERSIÓN ACTUALIZADA PARA JERARQUÍA
 */
public function ajax_filter_products() {
    // Verificar nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'productos_filter_nonce')) {
        wp_send_json_error(array('message' => 'Nonce inválido'));
        exit;
    }
    // Añadir esta línea para verificar si el usuario está logueado
    $is_logged_in = is_user_logged_in();
    
    // Pasamos esta información a las propiedades del loop para acceder en los templates
   wc_set_loop_prop('is_user_logged_in', $is_logged_in);
    // Log para depuración
    error_log('Recibida solicitud AJAX para filtrar productos');
    
    // Obtener parámetros de la solicitud
    $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
    $search_term = isset($_POST['search']) ? trim(sanitize_text_field($_POST['search'])) : '';
    $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
    
    // Log de parámetros para depuración
    error_log('Parámetros: page=' . $page . ', search=' . $search_term . ', category=' . $category);
    
    // Configurar argumentos básicos de la consulta
    $args = array(
        'post_type'      => 'product',
        'posts_per_page' => get_option('posts_per_page'),
        'paged'          => $page,
        'post_status'    => 'publish',
    );
    
    // Añadir búsqueda si hay un término
    if (!empty($search_term)) {
        // SOLUCIÓN: Usar una consulta simplificada para búsqueda
        // No combinar 's' con meta_query compleja para evitar restricciones excesivas
        $args['s'] = $search_term;
        
        // Agregar parámetro para post_title (alta prioridad)
        add_filter('posts_search', function($search, $wp_query) use ($search_term) {
            global $wpdb;
            
            if (empty($search) || !is_search() || !isset($wp_query->query_vars['s'])) {
                return $search;
            }
            
            $like = '%' . $wpdb->esc_like($search_term) . '%';
            
            // Buscar en título, contenido o SKU (como meta)
            $search = " AND (
                ($wpdb->posts.post_title LIKE '$like') OR 
                ($wpdb->posts.post_content LIKE '$like') OR 
                ($wpdb->posts.post_excerpt LIKE '$like') OR
                EXISTS (
                    SELECT * FROM $wpdb->postmeta 
                    WHERE $wpdb->postmeta.post_id = $wpdb->posts.ID 
                    AND (
                        ($wpdb->postmeta.meta_key = '_sku' AND $wpdb->postmeta.meta_value LIKE '$like') OR
                        ($wpdb->postmeta.meta_key LIKE 'pa_%' AND $wpdb->postmeta.meta_value LIKE '$like')
                    )
                )
            )";
            
            return $search;
        }, 999, 2);
    }
    
    // Filtrar por categoría si está especificada
    if (!empty($category)) {
        $categories = explode(',', $category);
        if (!empty($categories)) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'product_cat',
                    'field'    => 'slug',
                    'terms'    => $categories,
                    'operator' => 'IN',
                    'include_children' => true
                )
            );
        }
    }
    
    // Log de la consulta final
    error_log('Argumentos de consulta: ' . json_encode($args));
    
    // Ejecutar la consulta
    $products_query = new WP_Query($args);
    
    // Log del resultado
    error_log('Productos encontrados: ' . $products_query->found_posts);
    
    // Configurar las propiedades del bucle de WooCommerce
    wc_set_loop_prop('current_page', $page);
    wc_set_loop_prop('is_paginated', true);
    wc_set_loop_prop('page_template', 'productos-template');
    wc_set_loop_prop('per_page', get_option('posts_per_page'));
    wc_set_loop_prop('total', $products_query->found_posts);
    wc_set_loop_prop('total_pages', $products_query->max_num_pages);
    wc_set_loop_prop('columns', 3);
    
  // Antes de iniciar el buffer para capturar la salida, añadir un filtro si el usuario no está logueado
    if (!$is_logged_in) {
        add_filter('woocommerce_get_price_html', array($this, 'replace_price_with_login_button'), 10, 2);
    }
    
    // Capturar la salida de la cuadrícula de productos como lo haces actualmente...
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

// Remover el filtro después de obtener el HTML
    if (!$is_logged_in) {
        remove_filter('woocommerce_get_price_html', array($this, 'replace_price_with_login_button'), 10);
    }
    
    // Añadir scripts y estilos necesarios si el usuario no está logueado
    if (!$is_logged_in) {
        wp_enqueue_style('dpc-carousel-popup');
        wp_enqueue_script('dpc-carousel-popup');
    }
    
    // Restablecer datos del post después de la consulta
    wp_reset_postdata();
    
    // Eliminar el filtro de búsqueda personalizado
    if (!empty($search_term)) {
        remove_all_filters('posts_search', 999);
    }
    
    // Generar paginación
    ob_start();
    
    if ($products_query->max_num_pages > 1) {
        echo '<div class="productos-pagination">';
        
        echo '<div class="pagination-info">';
        $start = (($page - 1) * get_option('posts_per_page')) + 1;
        $end = min($products_query->found_posts, $page * get_option('posts_per_page'));
        
        printf(
            esc_html__('Mostrando %1$d-%2$d de %3$d resultados', 'wc-productos-template'),
            $start,
            $end,
            $products_query->found_posts
        );
        
        echo '</div>';
        
        echo '<div class="pagination-links">';
        
        // Botón "Anterior"
        if ($page > 1) {
            echo '<a href="javascript:void(0);" class="page-number page-prev" data-page="' . ($page - 1) . '">←</a>';
        }
        
        // Números de página
        $start_page = max(1, $page - 2);
        $end_page = min($products_query->max_num_pages, $page + 2);
        
        if ($start_page > 1) {
            echo '<a href="javascript:void(0);" class="page-number" data-page="1">1</a>';
            
            if ($start_page > 2) {
                echo '<span class="page-dots">...</span>';
            }
        }
        
        for ($i = $start_page; $i <= $end_page; $i++) {
            $active_class = ($i === $page) ? ' active' : '';
            echo '<a href="javascript:void(0);" class="page-number' . $active_class . '" data-page="' . $i . '">' . $i . '</a>';
        }
        
        if ($end_page < $products_query->max_num_pages) {
            if ($end_page < $products_query->max_num_pages - 1) {
                echo '<span class="page-dots">...</span>';
            }
            
            echo '<a href="javascript:void(0);" class="page-number" data-page="' . $products_query->max_num_pages . '">' . $products_query->max_num_pages . '</a>';
        }
        
        // Botón "Siguiente"
        if ($page < $products_query->max_num_pages) {
            echo '<a href="javascript:void(0);" class="page-number page-next" data-page="' . ($page + 1) . '">→</a>';
        }
        
        echo '</div>';
        echo '</div>';
    }
    
    $pagination = ob_get_clean();
    
    // Generar breadcrumb actualizado
    ob_start();
    
    if (function_exists('woocommerce_breadcrumb')) {
        if ($page > 1 || !empty($search_term)) {
            // Breadcrumb personalizado para búsqueda/paginación
            $breadcrumb_args = apply_filters('woocommerce_breadcrumb_defaults', array(
                'delimiter'   => '&nbsp;&#47;&nbsp;',
                'wrap_before' => '<nav class="woocommerce-breadcrumb">',
                'wrap_after'  => '</nav>',
                'before'      => '',
                'after'       => '',
                'home'        => _x('Inicio', 'breadcrumb', 'woocommerce'),
            ));
            
            echo $breadcrumb_args['wrap_before'];
            
            // Inicio
            echo $breadcrumb_args['before'];
            echo '<a href="' . esc_url(home_url()) . '">' . esc_html($breadcrumb_args['home']) . '</a>';
            echo $breadcrumb_args['after'] . $breadcrumb_args['delimiter'];
            
            // Tienda (si existe)
            $shop_page_id = wc_get_page_id('shop');
            if ($shop_page_id > 0 && $shop_page_id !== get_option('page_on_front')) {
                echo $breadcrumb_args['before'];
                echo '<a href="' . esc_url(get_permalink($shop_page_id)) . '">' . esc_html(get_the_title($shop_page_id)) . '</a>';
                echo $breadcrumb_args['after'];
                
                if (!empty($search_term)) {
                    echo $breadcrumb_args['delimiter'];
                    echo $breadcrumb_args['before'];
                    echo esc_html__('Búsqueda', 'wc-productos-template');
                    echo $breadcrumb_args['after'];
                }
                
                if ($page > 1) {
                    echo $breadcrumb_args['delimiter'];
                    echo $breadcrumb_args['before'];
                    echo sprintf(esc_html__('Página %d', 'wc-productos-template'), $page);
                    echo $breadcrumb_args['after'];
                }
            }
            
            echo $breadcrumb_args['wrap_after'];
        } else {
            // Breadcrumb estándar
            woocommerce_breadcrumb();
        }
    }
    
    $breadcrumb = ob_get_clean();
    
    // Incluir scripts de inicialización en la respuesta
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
        
        // Restaurar término de búsqueda si existe
        var searchTerm = ' . (isset($_POST['search']) ? json_encode(sanitize_text_field($_POST['search'])) : '""') . ';
        if (searchTerm) {
            $("#productos-search-input, .productos-search input[name=\'s\']").val(searchTerm);
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
        'search_term'  => isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '',
        'query_vars'   => $args // Para depuración
    ));
    
    exit;
}
        public function enhance_product_search($search, $wp_query) {
    global $wpdb;
    
    // Verificar que sea nuestra búsqueda
    if (empty($search) || !$wp_query->is_search || !$wp_query->is_main_query() || $wp_query->get('post_type') !== 'product') {
        return $search;
    }
    
    $search_term = $wp_query->get('s');
    if (empty($search_term)) {
        return $search;
    }
    
    // Limpiar y preparar el término de búsqueda
    $like = '%' . $wpdb->esc_like($search_term) . '%';
    
    // Nuestro reemplazo para búsqueda en título, contenido y extracto
    $search = " AND (
        ($wpdb->posts.post_title LIKE '$like') OR 
        ($wpdb->posts.post_content LIKE '$like') OR 
        ($wpdb->posts.post_excerpt LIKE '$like')
    )";
    
    // Si es numérico, podría ser un SKU o ID
    if (is_numeric($search_term)) {
        $search_term_int = intval($search_term);
        $search = " AND (
            ($wpdb->posts.post_title LIKE '$like') OR 
            ($wpdb->posts.post_content LIKE '$like') OR 
            ($wpdb->posts.post_excerpt LIKE '$like') OR
            ($wpdb->posts.ID = $search_term_int)
        )";
    }
    
    return $search;
}
/**
 * Reemplaza el precio con un botón de "Ver Precio" para usuarios no logueados
 */
public function replace_price_with_login_button($price_html, $product) {
    $product_id = $product->get_id();
    return '<div class="dpc-product-price dpc-price-hidden">
            <a href="#" class="dpc-login-to-view" data-product-id="' . esc_attr($product_id) . '">
                ' . __('Ver Precio', 'wc-productos-template') . '
            </a>
        </div>';
}
/**
 * FUNCIÓN PARA AÑADIR AL PLUGIN: Filtrar las clases CSS del body para la página de búsqueda
 */
public function add_search_body_class($classes) {
    // Verificar si estamos en una búsqueda o hay un parámetro 's' en la URL
    if (is_search() || (isset($_GET['s']) && !empty($_GET['s']))) {
        $classes[] = 'search-results';
        $classes[] = 'wc-search-active';
    }
    return $classes;
}
        /**
 * Código para añadir al archivo woocommerce-productos-template.php
 * Este código debe ir dentro de la clase WC_Productos_Template
 */

/**
 * Integración con la búsqueda estándar de WooCommerce
 * Esta función se llama desde el constructor
 */
public function integrate_with_woocommerce_search() {
    // Mejorar la búsqueda nativa de WooCommerce para incluir metadatos
    add_filter('woocommerce_product_data_store_cpt_get_products_query', array($this, 'handle_custom_product_query_var'), 10, 2);
    
    // Incluir metadatos en los resultados de búsqueda
    add_filter('posts_search', array($this, 'enhance_product_search'), 10, 2);
    
    // Filtrar consultas pre_get_posts para búsqueda
    add_action('pre_get_posts', array($this, 'modify_product_search_query'));
    
    // Agregar clases CSS al body para páginas de búsqueda
    add_filter('body_class', array($this, 'add_search_body_class'));
    
    // Asegurarse de que el valor de búsqueda esté disponible para el JS
    add_action('wp_footer', array($this, 'inject_search_variables'));
}

/**
 * Modificar consultas para búsqueda de productos
 */
public function modify_product_search_query($query) {
    // No modificar en el admin o si no es la consulta principal
    if (is_admin() || !$query->is_main_query()) {
        return;
    }
    
    // Si es una búsqueda o hay parámetro 's' en la URL
    if ($query->is_search() || (isset($_GET['s']) && !empty($_GET['s']))) {
        // Asegurarse de que incluya productos
        if (!isset($query->query_vars['post_type']) || $query->query_vars['post_type'] !== 'product') {
            if (isset($_GET['post_type']) && $_GET['post_type'] === 'product') {
                $query->set('post_type', 'product');
            }
        }
        
        // Establecer productos por página
        $posts_per_page = get_option('posts_per_page');
        $query->set('posts_per_page', $posts_per_page);
        
        // Optimizar meta_query para búsqueda avanzada
        $search_term = get_search_query();
        if (!empty($search_term)) {
            // Preparar meta query para buscar en SKU y otros campos
            $meta_query = array('relation' => 'OR');
            
            // Buscar en SKU
            $meta_query[] = array(
                'key'     => '_sku',
                'value'   => $search_term,
                'compare' => 'LIKE'
            );
            
            // Buscar en atributos comunes
            $attributes = array('pa_volumen', 'pa_grado', 'pa_caracteristicas');
            foreach ($attributes as $attribute) {
                $meta_query[] = array(
                    'key'     => $attribute,
                    'value'   => $search_term,
                    'compare' => 'LIKE'
                );
            }
            
            // Combinar con meta_query existente
            $existing_meta_query = $query->get('meta_query');
            if (!empty($existing_meta_query)) {
                $meta_query = array(
                    'relation' => 'AND',
                    $existing_meta_query,
                    array(
                        'relation' => 'OR',
                        $meta_query
                    )
                );
            }
            
            $query->set('meta_query', $meta_query);
        }
    }
}

/**
 * Manejar variables de consulta personalizadas para productos
 */
public function handle_custom_product_query_var($query, $query_vars) {
    if (!empty($query_vars['sku_search']) && !is_array($query_vars['sku_search'])) {
        $query['meta_query'][] = array(
            'key'     => '_sku',
            'value'   => esc_attr($query_vars['sku_search']),
            'compare' => 'LIKE'
        );
    }
    
    if (!empty($query_vars['attribute_search']) && !is_array($query_vars['attribute_search'])) {
        $search_term = esc_attr($query_vars['attribute_search']);
        $attributes_meta_query = array('relation' => 'OR');
        
        // Buscar en atributos comunes
        $attributes = array('pa_volumen', 'pa_grado', 'pa_caracteristicas');
        foreach ($attributes as $attribute) {
            $attributes_meta_query[] = array(
                'key'     => $attribute,
                'value'   => $search_term,
                'compare' => 'LIKE'
            );
        }
        
        $query['meta_query'][] = $attributes_meta_query;
    }
    
    return $query;
}

/**
 * Inyectar variables de búsqueda para JavaScript
 */
public function inject_search_variables() {
    // Verificar si estamos en una página de productos
    if (!$this->is_product_page()) {
        return;
    }
    
    // Obtener término de búsqueda actual
    $search_term = get_search_query();
    if (empty($search_term) && isset($_GET['s'])) {
        $search_term = sanitize_text_field($_GET['s']);
    }
    
    // Inyectar variable para JavaScript
    if (!empty($search_term)) {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Establecer término de búsqueda en todos los campos de búsqueda
            $('.wc-productos-template .productos-search input, #productos-search-input').val(<?php echo json_encode($search_term); ?>);
            
            // Si tenemos un objeto currentFilters global, actualizar la propiedad search
            if (typeof window.currentFilters !== 'undefined') {
                window.currentFilters.search = <?php echo json_encode($search_term); ?>;
            }
        });
        </script>
        <?php
    }
}

/**
 * Agregar este código al constructor de la clase WC_Productos_Template:
 */

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
    // Solo cargar si estamos en una página relevante
    if (!function_exists('is_product_page') || !is_product_page()) {
        return;
    }
    
    // Solo aplicar dentro de nuestro contenedor
    $css_fix = "
    .wc-productos-template img,
    .wc-productos-template .site-header,
    .wc-productos-template .site-footer,
    .wc-productos-template .logo,
    .wc-productos-template .brand-logo {
        opacity: 1 !important;
        visibility: visible !important;
    }
    ";
    wp_add_inline_style('wc-productos-template-styles', $css_fix);
    
    // Aplicar script solo a nuestros elementos
    wp_add_inline_script('wc-productos-template-script', "
    jQuery(document).ready(function($) {
        $('.wc-productos-template img').css({
            'opacity': '1',
            'visibility': 'visible'
        });
        
        $('.wc-productos-template .logo, .wc-productos-template .brand-logo').css({
            'opacity': '1',
            'visibility': 'visible'
        });
    });
    ", 'after');
}
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
}
