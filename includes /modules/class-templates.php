<?php
/**
 * Módulo Templates - Gestión de plantillas
 * 
 * @package WC_Productos_Template
 */

class WC_Productos_Template_Templates {
    
    /**
     * El cargador que es responsable de mantener y registrar todos los hooks del plugin.
     *
     * @var WC_Productos_Template_Loader $loader
     */
    private $loader;
    
    /**
     * Inicializar el módulo Templates.
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
        // Sobreescribir templates de WooCommerce
        $this->loader->add_filter('woocommerce_locate_template', $this, 'override_woocommerce_templates', 999, 3);
        $this->loader->add_filter('wc_get_template_part', $this, 'override_template_parts', 999, 3);
        
        // Cargar templates personalizados
        $this->loader->add_filter('template_include', $this, 'template_loader');
        
        // Crear archivos de template si no existen
        $this->create_template_files();
    }
    
    /**
     * Sobreescribir templates de WooCommerce
     *
     * @param string $template Ruta al template.
     * @param string $template_name Nombre del template.
     * @param string $template_path Ruta del template.
     * @return string Ruta modificada al template.
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
     * Sobreescribir partes de templates
     *
     * @param string $template Ruta al template.
     * @param string $slug Slug del template.
     * @param string $name Nombre del template.
     * @return string Ruta modificada al template.
     */
    public function override_template_parts($template, $slug, $name) {
        if ($slug === 'content' && $name === 'product') {
            // Comprobar si tenemos una plantilla personalizada para el producto
            $plugin_template = WC_PRODUCTOS_TEMPLATE_TEMPLATES_DIR . 'content-product.php';
            
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        return $template;
    }
    
    /**
     * Cargador de templates personalizado
     *
     * @param string $template Ruta al template.
     * @return string Ruta modificada al template.
     */
    public function template_loader($template) {
        // Detectar si estamos usando el shortcode en esta página
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
     * Crear archivos de template si no existen
     */
    private function create_template_files() {
        // Lista de templates a crear
        $templates = array(
            'content-product.php' => $this->get_content_product_template(),
            'loop/loop-start.php' => $this->get_loop_start_template(),
            'loop/loop-end.php' => $this->get_loop_end_template(),
            'partials/search-bar.php' => $this->get_search_bar_template(),
            'partials/filters.php' => $this->get_filters_template(),
            'partials/login-form.php' => $this->get_login_form_template(),
            'archive-product.php' => $this->get_archive_product_template()
        );
        
        foreach ($templates as $path => $content) {
            $file_path = WC_PRODUCTOS_TEMPLATE_TEMPLATES_DIR . $path;
            
            // Crear directorio si no existe
            $dir = dirname($file_path);
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
            
            // Crear archivo si no existe
            if (!file_exists($file_path)) {
                file_put_contents($file_path, $content);
            }
        }
    }
    
    /**
     * Obtener contenido del template content-product.php
     *
     * @return string Contenido del template.
     */
    private function get_content_product_template() {
        return '<?php
/**
 * Template para cada producto en la cuadrícula
 * Diseño inspirado en Mercado Libre
 *
 * @package WC_Productos_Template
 */

// Verificar que tenemos un objeto de producto válido
global $product;
if (!$product || !is_a($product, "WC_Product")) {
    return;
}

// Determinar si el usuario está logueado
$is_logged_in = is_user_logged_in();
?>
<li <?php wc_product_class("wc-producto-card", $product); ?>>
    <div class="wc-producto-inner">
        <?php
        // Badges (En stock, Oferta, etc.)
        echo "<div class=\"wc-producto-badges\">";
        
        // Badge de stock
        if ($product->is_in_stock()) {
            echo "<span class=\"wc-producto-badge badge-stock\">" . 
                esc_html__("En stock", "wc-productos-template") . "</span>";
        } else {
            echo "<span class=\"wc-producto-badge badge-out-stock\">" . 
                esc_html__("Agotado", "wc-productos-template") . "</span>";
        }
        
        // Badge de oferta
        if ($product->is_on_sale()) {
            echo "<span class=\"wc-producto-badge badge-sale\">" . 
                esc_html__("Oferta", "wc-productos-template") . "</span>";
        }
        
        // Badge de envío
        if ($product->get_shipping_class()) {
            echo "<span class=\"wc-producto-badge badge-shipping\">" . 
                "<i class=\"fas fa-truck\"></i> " . 
                esc_html__("Envío gratis", "wc-productos-template") . "</span>";
        }
        
        echo "</div>"; // Fin de badges
        
        // Imagen del producto con enlace
        echo "<div class=\"wc-producto-image\">";
        echo "<a href=\"" . esc_url(get_permalink()) . "\" class=\"wc-producto-image-link\">";
        
        if (has_post_thumbnail()) {
            the_post_thumbnail("woocommerce_thumbnail");
        } else {
            echo wc_placeholder_img();
        }
        
        echo "</a>";
        echo "</div>"; // Fin imagen
        
        // Información del producto
        echo "<div class=\"wc-producto-info\">";
        
        // SKU o ID como referencia
        $sku = $product->get_sku();
        if ($sku) {
            echo "<div class=\"wc-producto-sku\">";
            echo "<span class=\"sku-label\">" . esc_html__("REF:", "wc-productos-template") . "</span> ";
            echo esc_html($sku);
            echo "</div>";
        }
        
        // Título con enlace
        echo "<h2 class=\"wc-producto-title\">";
        echo "<a href=\"" . esc_url(get_permalink()) . "\">" . get_the_title() . "</a>";
        echo "</h2>";
        
        // Precio
        if ($is_logged_in) {
            echo "<div class=\"wc-producto-price\">";
            echo $product->get_price_html();
            echo "</div>";
        } else {
            echo "<div class=\"wc-producto-price-login\">";
            echo "<a href=\"#\" class=\"dpc-login-to-view\" data-product-id=\"" . esc_attr($product->get_id()) . "\">";
            echo esc_html__("Ver Precio", "wc-productos-template");
            echo "</a>";
            echo "</div>";
        }
        
        // Detalles adicionales (opcional)
        $attributes = $product->get_attributes();
        if (!empty($attributes)) {
            echo "<div class=\"wc-producto-details\">";
            
            // Mostrar solo los primeros 2 atributos
            $count = 0;
            foreach ($attributes as $attribute) {
                if ($count >= 2) break;
                
                if ($attribute->get_visible()) {
                    echo "<div class=\"wc-producto-attribute\">";
                    echo "<span class=\"attribute-label\">" . wc_attribute_label($attribute->get_name()) . ":</span> ";
                    
                    // Obtener términos para atributos taxonomy
                    if ($attribute->is_taxonomy()) {
                        $values = wc_get_product_terms($product->get_id(), $attribute->get_name(), array("fields" => "names"));
                        echo esc_html(implode(", ", $values));
                    } else {
                        // Obtener valor para atributos personalizados
                        $values = $attribute->get_options();
                        echo esc_html(implode(", ", $values));
                    }
                    
                    echo "</div>";
                    $count++;
                }
            }
            
            echo "</div>"; // Fin detalles
        }
        
        // Botones de acción
        echo "<div class=\"wc-producto-actions\">";
        
        if ($is_logged_in) {
            // Botón de añadir al carrito
            echo "<a href=\"" . esc_url($product->add_to_cart_url()) . "\" 
                    class=\"wc-producto-add-to-cart button add_to_cart_button " . 
                    ($product->is_purchasable() && $product->is_in_stock() ? "ajax_add_to_cart" : "") . "\"
                    data-product_id=\"" . esc_attr($product->get_id()) . "\"
                    data-product_sku=\"" . esc_attr($product->get_sku()) . "\">";
            
            echo "<i class=\"fas fa-shopping-cart\"></i> ";
            echo esc_html($product->is_purchasable() && $product->is_in_stock() ? 
                __("Añadir al carrito", "wc-productos-template") : 
                __("Ver detalles", "wc-productos-template"));
            
            echo "</a>";
        } else {
            // Botón para invitados
            echo "<a href=\"#\" class=\"wc-producto-add-to-cart button dpc-login-to-view\" 
                    data-product_id=\"" . esc_attr($product->get_id()) . "\">";
            
            echo "<i class=\"fas fa-shopping-cart\"></i> ";
            echo esc_html__("Ver detalles", "wc-productos-template");
            
            echo "</a>";
        }
        
        echo "</div>"; // Fin acciones
        
        echo "</div>"; // Fin info
        ?>
    </div>
</li>';
    }
    
    /**
     * Obtener contenido del template loop-start.php
     *
     * @return string Contenido del template.
     */
    private function get_loop_start_template() {
        return '<?php
/**
 * Product Loop Start
 * Template personalizado al estilo Mercado Libre
 *
 * @package WC_Productos_Template
 */

if (!defined("ABSPATH")) {
    exit;
}
?>
<ul class="products wc-productos-grid columns-<?php echo esc_attr(wc_get_loop_prop("columns")); ?>">';
    }
    
    /**
     * Obtener contenido del template loop-end.php
     *
     * @return string Contenido del template.
     */
    private function get_loop_end_template() {
        return '<?php
/**
 * Product Loop End
 * Template personalizado al estilo Mercado Libre
 *
 * @package WC_Productos_Template
 */

if (!defined("ABSPATH")) {
    exit;
}
?>
</ul>';
    }
    
    /**
     * Obtener contenido del template search-bar.php
     *
     * @return string Contenido del template.
     */
    private function get_search_bar_template() {
        return '<?php
/**
 * Template para la barra de búsqueda
 * Diseño inspirado en Mercado Libre
 *
 * @package WC_Productos_Template
 */

if (!defined("ABSPATH")) {
    exit;
}
?>
<div class="wc-productos-search-bar">
    <div class="wc-productos-search-container">
        <form role="search" method="get" class="wc-productos-search-form" action="<?php echo esc_url(home_url("/")); ?>">
            <input type="hidden" name="post_type" value="product" />
            <div class="wc-productos-search-input-wrapper">
                <input type="text" 
                       id="wc-productos-search-input"
                       name="s" 
                       placeholder="<?php esc_attr_e("Buscar productos, marcas y más...", "wc-productos-template"); ?>" 
                       value="<?php echo esc_attr(get_search_query()); ?>" />
                <button type="submit" class="wc-productos-search-button">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </form>
    </div>
    
    <?php if (is_user_logged_in()): ?>
    <div class="wc-productos-user-actions">
        <a href="<?php echo esc_url(wc_get_account_endpoint_url("dashboard")); ?>" class="wc-productos-user-account">
            <i class="fas fa-user"></i>
            <span><?php esc_html_e("Mi cuenta", "wc-productos-template"); ?></span>
        </a>
        <a href="<?php echo esc_url(wc_get_cart_url()); ?>" class="wc-productos-cart">
            <i class="fas fa-shopping-cart"></i>
            <span class="wc-productos-cart-count"><?php echo WC()->cart->get_cart_contents_count(); ?></span>
        </a>
    </div>
    <?php else: ?>
    <div class="wc-productos-user-actions">
        <a href="<?php echo esc_url(get_permalink(get_option("woocommerce_myaccount_page_id"))); ?>" class="wc-productos-login">
            <i class="fas fa-sign-in-alt"></i>
            <span><?php esc_html_e("Ingresar", "wc-productos-template"); ?></span>
        </a>
        <a href="<?php echo esc_url(wc_get_cart_url()); ?>" class="wc-productos-cart">
            <i class="fas fa-shopping-cart"></i>
            <span class="wc-productos-cart-count"><?php echo WC()->cart->get_cart_contents_count(); ?></span>
        </a>
    </div>
    <?php endif; ?>
</div>

<div class="wc-productos-categories-nav">
    <ul class="wc-productos-categories-list">
        <?php
        // Mostrar categorías principales
        $categories = get_terms(array(
            "taxonomy" => "product_cat",
            "hide_empty" => true,
            "parent" => 0,
            "number" => 10
        ));
        
        if (!empty($categories) && !is_wp_error($categories)) {
            foreach ($categories as $category) {
                // No mostrar la categoría "Sin categorizar"
                if ($category->slug === "uncategorized") {
                    continue;
                }
                
                echo "<li class=\"wc-productos-category-item\">";
                echo "<a href=\"" . esc_url(get_term_link($category)) . "\">" . esc_html($category->name) . "</a>";
                echo "</li>";
            }
        }
        ?>
    </ul>
</div>';
    }
    
    /**
     * Obtener contenido del template filters.php
     *
     * @return string Contenido del template.
     */
    private function get_filters_template() {
        return '<?php
/**
 * Template para los filtros de la barra lateral
 * Diseño inspirado en Mercado Libre
 *
 * @package WC_Productos_Template
 */

if (!defined("ABSPATH")) {
    exit;
}
?>
<aside class="wc-productos-sidebar">
    <div class="wc-productos-filters">
        <h3 class="wc-productos-filters-title"><?php esc_html_e("Filtros", "wc-productos-template"); ?></h3>
        
        <!-- Filtro de categorías -->
        <div class="wc-productos-filter-section">
            <h4 class="wc-productos-filter-heading">
                <i class="fas fa-tags"></i>
                <?php esc_html_e("Categorías", "wc-productos-template"); ?>
            </h4>
            
            <div class="wc-productos-filter-content">
                <ul class="wc-productos-filter-list">
                    <?php
                    // Obtener categorías padre (parent = 0)
                    $parent_categories = get_terms(array(
                        "taxonomy" => "product_cat",
                        "hide_empty" => true,
                        "parent" => 0
                    ));
                    
                    if (!empty($parent_categories) && !is_wp_error($parent_categories)) {
                        foreach ($parent_categories as $parent) {
                            // Excluir la categoría "Sin categorizar"
                            if ($parent->slug === "uncategorized") {
                                continue;
                            }
                            
                            // Verificar si la categoría padre está activa
                            $parent_active = false;
                            if (isset($_GET["category"])) {
                                $active_cats = explode(",", $_GET["category"]);
                                $parent_active = in_array($parent->slug, $active_cats);
                            }
                            
                            // Obtener categorías hijas
                            $child_categories = get_terms(array(
                                "taxonomy" => "product_cat",
                                "hide_empty" => true,
                                "parent" => $parent->term_id
                            ));
                            
                            $has_children = !empty($child_categories) && !is_wp_error($child_categories);
                            
                            ?>
                            <li class="wc-productos-category-parent">
                                <div class="wc-productos-category-parent-header">
                                    <label class="wc-productos-category-checkbox">
                                        <input type="checkbox" 
                                               class="wc-productos-filter-category" 
                                               value="<?php echo esc_attr($parent->slug); ?>"
                                               <?php checked($parent_active, true); ?> />
                                        <span class="wc-productos-category-name"><?php echo esc_html($parent->name); ?></span>
                                    </label>
                                    
                                    <?php if ($has_children): ?>
                                    <button type="button" class="wc-productos-category-toggle" 
                                           data-category="<?php echo esc_attr($parent->slug); ?>">
                                        <i class="fas fa-chevron-down"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($has_children): ?>
                                <ul class="wc-productos-category-children" id="children-<?php echo esc_attr($parent->slug); ?>">
                                    <?php foreach ($child_categories as $child): 
                                        // Verificar si la categoría hija está activa
                                        $child_active = false;
                                        if (isset($_GET["category"])) {
                                            $active_cats = explode(",", $_GET["category"]);
                                            $child_active = in_array($child->slug, $active_cats);
                                        }
                                    ?>
                                        <li class="wc-productos-category-child">
                                            <label class="wc-productos-category-checkbox">
                                                <input type="checkbox" 
                                                       class="wc-productos-filter-category wc-productos-filter-child" 
                                                       value="<?php echo esc_attr($child->slug); ?>"
                                                       <?php checked($child_active, true); ?> />
                                                <span class="wc-productos-category-name"><?php echo esc_html($child->name); ?></span>
                                            </label>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                                <?php endif; ?>
                            </li>
                            <?php
                        }
                    }
                    ?>
                </ul>
            </div>
        </div>
        
        <!-- Filtro de disponibilidad -->
        <div class="wc-productos-filter-section">
            <h4 class="wc-productos-filter-heading">
                <i class="fas fa-box-open"></i>
                <?php esc_html_e("Disponibilidad", "wc-productos-template"); ?>
            </h4>
            
            <div class="wc-productos-filter-content">
                <ul class="wc-productos-filter-list">
                    <li>
                        <label class="wc-productos-filter-checkbox">
                            <input type="checkbox" class="wc-productos-filter-stock" value="instock" 
                                  <?php checked(isset($_GET["stock"]) && $_GET["stock"] === "instock", true); ?> />
                            <span><?php esc_html_e("En stock", "wc-productos-template"); ?></span>
                        </label>
                    </li>
                </ul>
            </div>
        </div>
        
        <!-- Filtro de precio -->
        <div class="wc-productos-filter-section">
            <h4 class="wc-productos-filter-heading">
                <i class="fas fa-dollar-sign"></i>
                <?php esc_html_e("Precio", "wc-productos-template"); ?>
            </h4>
            
            <div class="wc-productos-filter-content">
                <div class="wc-productos-price-slider">
                    <div class="wc-productos-price-range"></div>
                    <div class="wc-productos-price-inputs">
                        <div class="wc-productos-price-input">
                            <label for="wc-productos-min-price"><?php esc_html_e("Mín", "wc-productos-template"); ?></label>
                            <input type="number" id="wc-productos-min-price" class="wc-productos-min-price" min="0" />
                        </div>
                        <div class="wc-productos-price-input">
                            <label for="wc-productos-max-price"><?php esc_html_e("Máx", "wc-productos-template"); ?></label>
                            <input type="number" id="wc-productos-max-price" class="wc-productos-max-price" min="0" />
                        </div>
                    </div>
                    <button type="button" class="wc-productos-price-filter-button">
                        <?php esc_html_e("Aplicar", "wc-productos-template"); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Botón para aplicar filtros en móvil -->
        <button type="button" class="wc-productos-apply-filters-button">
            <?php esc_html_e("Aplicar filtros", "wc-productos-template"); ?>
        </button>
    </div>
</aside>';
    }
    
    /**
     * Obtener contenido del template login-form.php
     *
     * @return string Contenido del template.
     */
    private function get_login_form_template() {
        return '<?php
/**
 * Template para el formulario de login en popup
 * Diseño inspirado en Mercado Libre
 *
 * @package WC_Productos_Template
 */

if (!defined("ABSPATH")) {
    exit;
}
?>
<div class="wc-productos-login-container">
    <div class="wc-productos-login-tabs">
        <a href="#login" class="wc-productos-login-tab active">
            <i class="fas fa-sign-in-alt"></i>
            <?php esc_html_e("Iniciar Sesión", "wc-productos-template"); ?>
        </a>
        <a href="#register" class="wc-productos-register-tab">
            <i class="fas fa-user-plus"></i>
            <?php esc_html_e("Crear Cuenta", "wc-productos-template"); ?>
        </a>
    </div>
    
    <div class="wc-productos-login-forms">
        <!-- Formulario de Login -->
        <div class="wc-productos-login-form-wrapper">
            <form class="wc-productos-login-form" id="wc-productos-login-form" method="post">
                <?php wp_nonce_field("wc_productos_login", "wc_productos_login_nonce"); ?>
                <input type="hidden" name="action" value="wc_productos_ajax_login" />
                <input type="hidden" name="redirect" id="login-redirect-url" value="<?php echo esc_url(wc_get_page_permalink("shop")); ?>" />
                
                <div class="wc-productos-form-row">
                    <label for="wc-productos-username"><?php esc_html_e("Correo electrónico", "wc-productos-template"); ?></label>
                    <div class="wc-productos-input-icon">
                        <i class="fas fa-envelope"></i>
                        <input type="text" name="username" id="wc-productos-username" class="wc-productos-input" placeholder="<?php esc_attr_e("Tu email", "wc-productos-template"); ?>" required />
                    </div>
                </div>
                
                <div class="wc-productos-form-row">
                    <label for="wc-productos-password"><?php esc_html_e("Contraseña", "wc-productos-template"); ?></label>
                    <div class="wc-productos-input-icon wc-productos-password-field">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" id="wc-productos-password" class="wc-productos-input" placeholder="<?php esc_attr_e("Tu contraseña", "wc-productos-template"); ?>" required />
                        <span class="wc-productos-password-toggle" role="button">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                </div>
                
                <div class="wc-productos-form-row wc-productos-remember-row">
                    <label class="wc-productos-checkbox">
                        <input type="checkbox" name="rememberme" id="wc-productos-rememberme" value="forever" />
                        <span><?php esc_html_e("Recordarme", "wc-productos-template"); ?></span>
                    </label>
                    <a href="<?php echo esc_url(wp_lostpassword_url()); ?>" class="wc-productos-lost-password">
                        <?php esc_html_e("¿Olvidaste tu contraseña?", "wc-productos-template"); ?>
                    </a>
                </div>
                
                <div class="wc-productos-form-row">
                    <button type="submit" class="wc-productos-button wc-productos-login-button">
                        <i class="fas fa-sign-in-alt"></i>
                        <?php esc_html_e("Iniciar Sesión", "wc-productos-template"); ?>
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Formulario de Registro -->
        <div class="wc-productos-register-form-wrapper" style="display: none;">
            <form class="wc-productos-register-form" id="wc-productos-register-form" method="post">
                <?php wp_nonce_field("wc_productos_register", "wc_productos_register_nonce"); ?>
                <input type="hidden" name="action" value="wc_productos_ajax_register" />
                
                <div class="wc-productos-form-row">
                    <label for="wc-productos-reg-email"><?php esc_html_e("Correo electrónico", "wc-productos-template"); ?></label>
                    <div class="wc-productos-input-icon">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" id="wc-productos-reg-email" class="wc-productos-input" placeholder="<?php esc_attr_e("Tu email", "wc-productos-template"); ?>" required />
                    </div>
                </div>
                
                <div class="wc-productos-form-row wc-productos-form-row-half">
                    <label for="wc-productos-reg-first-name"><?php esc_html_e("Nombre", "wc-productos-template"); ?></label>
                    <div class="wc-productos-input-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" name="first_name" id="wc-productos-reg-first-name" class="wc-productos-input" placeholder="<?php esc_attr_e("Tu nombre", "wc-productos-template"); ?>" required />
                    </div>
                </div>
                
                <div class="wc-productos-form-row wc-productos-form-row-half">
                    <label for="wc-productos-reg-last-name"><?php esc_html_e("Apellidos", "wc-productos-template"); ?></label>
                    <div class="wc-productos-input-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" name="last_name" id="wc-productos-reg-last-name" class="wc-productos-input" placeholder="<?php esc_attr_e("Tus apellidos", "wc-productos-template"); ?>" required />
                    </div>
                </div>
                
                <div class="wc-productos-form-row wc-productos-form-row-half">
                    <label for="wc-productos-reg-password"><?php esc_html_e("Contraseña", "wc-productos-template"); ?></label>
                    <div class="wc-productos-input-icon wc-productos-password-field">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" id="wc-productos-reg-password" class="wc-productos-input" placeholder="<?php esc_attr_e("Tu contraseña", "wc-productos-template"); ?>" required />
                        <span class="wc-productos-password-toggle" role="button">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                </div>
                
                <div class="wc-productos-form-row wc-productos-form-row-half">
                    <label for="wc-productos-reg-password-confirm"><?php esc_html_e("Confirmar contraseña", "wc-productos-template"); ?></label>
                    <div class="wc-productos-input-icon wc-productos-password-field">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password_confirm" id="wc-productos-reg-password-confirm" class="wc-productos-input" placeholder="<?php esc_attr_e("Confirma tu contraseña", "wc-productos-template"); ?>" required />
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
                                esc_html__("He leído y acepto la %spolítica de privacidad%s", "wc-productos-template"),
                                "<a href=\"" . esc_url(get_privacy_policy_url()) . "\" target=\"_blank\">",
                                "</a>"
                            ); 
                        ?></span>
                    </label>
                </div>
                
                <div class="wc-productos-form-row">
                    <button type="submit" class="wc-productos-button wc-productos-register-button">
                        <i class="fas fa-user-plus"></i>
                        <?php esc_html_e("Crear Cuenta", "wc-productos-template"); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>';
    }
    
    /**
     * Obtener contenido del template archive-product.php
     *
     * @return string Contenido del template.
     */
    private function get_archive_product_template() {
        return '<?php
/**
 * Template para el archivo de productos
 * Diseño inspirado en Mercado Libre
 *
 * @package WC_Productos_Template
 */

// Ejecutar hooks de WooCommerce
do_action("woocommerce_before_main_content");
?>

<div class="wc-productos-template mercadolibre-style">
    <?php
    // Cargar barra de búsqueda
    $search_template = WC_PRODUCTOS_TEMPLATE_TEMPLATES_DIR . "partials/search-bar.php";
    if (file_exists($search_template)) {
        include $search_template;
    }
    ?>
    
    <div class="wc-productos-layout">
        <?php
        // Cargar sidebar de filtros
        $filters_template = WC_PRODUCTOS_TEMPLATE_TEMPLATES_DIR . "partials/filters.php";
        if (file_exists($filters_template)) {
            include $filters_template;
        }
        ?>
        
        <main class="wc-productos-main">
            <div class="wc-productos-header">
                <h1><?php woocommerce_page_title(); ?></h1>
                
                <?php 
                // Mostrar resultado de la búsqueda si es una página de búsqueda
                if (is_search()) {
                    echo "<div class=\"wc-productos-search-result\">";
                    echo sprintf(
                        esc_html__("Resultados para: %s", "wc-productos-template"),
                        "<span>\"" . esc_html(get_search_query()) . "\"</span>"
                    );
                    echo "</div>";
                }
                ?>
                
                <?php
                // Mostrar contador de resultados
                if (woocommerce_product_loop()) {
                    echo "<div class=\"wc-productos-result-count\">";
                    woocommerce_result_count();
                    echo "</div>";
                }
                ?>
            </div>
            
            <?php
            if (woocommerce_product_loop()) {
                // Ordenamiento
                echo "<div class=\"wc-productos-ordering\">";
                woocommerce_catalog_ordering();
                echo "</div>";
                
                // Iniciar loop de productos
                woocommerce_product_loop_start();
                
                if (wc_get_loop_prop("total")) {
                    while (have_posts()) {
                        the_post();
                        
                        // Cargar template part para cada producto
                        $content_template = WC_PRODUCTOS_TEMPLATE_TEMPLATES_DIR . "content-product.php";
                        if (file_exists($content_template)) {
                            include $content_template;
                        } else {
                            // Fallback al template estándar de WooCommerce
                            wc_get_template_part("content", "product");
                        }
                    }
                }
                
                woocommerce_product_loop_end();
                
                // Paginación
                echo "<div class=\"wc-productos-pagination\">";
                woocommerce_pagination();
                echo "</div>";
                
            } else {
                // No se encontraron productos
                echo "<div class=\"wc-productos-no-results\">";
                
                if (is_search()) {
                    echo "<p>" . 
                        sprintf(
                            esc_html__("No se encontraron productos que coincidan con tu búsqueda: \"%s\"", "wc-productos-template"),
                            esc_html(get_search_query())
                        ) . 
                        "</p>";
                } else {
                    echo "<p>" . esc_html__("No se encontraron productos.", "wc-productos-template") . "</p>";
                }
                
                echo "</div>";
            }
            ?>
        </main>
    </div>
</div>

<?php
// Ejecutar hooks de WooCommerce
do_action("woocommerce_after_main_content");
?>';
    }
}
