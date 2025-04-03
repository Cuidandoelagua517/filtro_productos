<?php
/**
 * Template para el archivo de productos (archive-product.php)
 * Versión corregida con estructura HTML limpia y ordenada
 *
 * @package WC_Productos_Template
 */

// Solo ejecutar el hook para temas que lo necesiten
do_action('woocommerce_before_main_content');
?>

<div class="productos-container wc-productos-template">
    <!-- 1. HEADER (siempre primero y fuera del layout principal) -->
    <div class="productos-header">
        <h1><?php echo esc_html(woocommerce_page_title(false)); ?></h1>
        
        <!-- Barra de búsqueda -->
        <div class="productos-search">
            <form role="search" method="get" class="productos-search-form" action="javascript:void(0);">
                <input type="text" 
                       id="productos-search-input"
                       name="s" 
                       placeholder="<?php esc_attr_e('Buscar por nombre, referencia o características...', 'wc-productos-template'); ?>" 
                       value="<?php echo get_search_query(); ?>" />
                <button type="submit" class="productos-search-button" aria-label="<?php esc_attr_e('Buscar', 'wc-productos-template'); ?>">
                    <i class="fas fa-search" aria-hidden="true"></i>
                </button>
            </form>
        </div>
    </div>
    
    <!-- 2. LAYOUT DE DOS COLUMNAS (siempre después del header) -->
    <div class="productos-layout">
        <!-- Sidebar de filtros (columna izquierda) -->
<aside class="productos-sidebar">
    <h2><?php esc_html_e('Filtros', 'wc-productos-template'); ?></h2>
    
    <!-- Filtro de categorías jerárquico -->
    <div class="filtro-grupo" id="filtro-categorias">
        <h3><?php esc_html_e('Categorías', 'wc-productos-template'); ?></h3>
        <?php
        // Obtener solo categorías padre (parent = 0)
        $parent_categories = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => true,
            'parent' => 0
        ));
        
        if (!empty($parent_categories) && !is_wp_error($parent_categories)) {
            echo '<div class="filtro-lista">';
            foreach ($parent_categories as $parent) {
                // Excluir la categoría "Uncategorized" o su equivalente
                if ($parent->slug === 'uncategorized') {
                    continue;
                }
                
                // Verificar si la categoría padre está activa
                $parent_active = false;
                if (isset($_GET['category'])) {
                    $active_cats = explode(',', $_GET['category']);
                    $parent_active = in_array($parent->slug, $active_cats);
                }
                
                // Obtener categorías hijas
                $child_categories = get_terms(array(
                    'taxonomy' => 'product_cat',
                    'hide_empty' => true,
                    'parent' => $parent->term_id
                ));
                
                $has_children = !empty($child_categories) && !is_wp_error($child_categories);
                
                ?>
                <div class="filtro-category-parent">
                    <div class="filtro-parent-option">
                        <input type="checkbox" id="cat-<?php echo esc_attr($parent->slug); ?>" 
                            class="filtro-category" value="<?php echo esc_attr($parent->slug); ?>"
                            <?php checked($parent_active, true); ?> />
                        <label for="cat-<?php echo esc_attr($parent->slug); ?>">
                            <?php echo esc_html($parent->name); ?>
                            <?php if ($has_children): ?>
                                <span class="category-toggle" data-category="<?php echo esc_attr($parent->slug); ?>">
                                    <i class="fas fa-chevron-down"></i>
                                </span>
                            <?php endif; ?>
                        </label>
                    </div>
                    
                    <?php if ($has_children): ?>
                        <div class="filtro-children-list" id="children-<?php echo esc_attr($parent->slug); ?>">
                            <?php foreach ($child_categories as $child): 
                                // Verificar si la categoría hija está activa
                                $child_active = false;
                                if (isset($_GET['category'])) {
                                    $active_cats = explode(',', $_GET['category']);
                                    $child_active = in_array($child->slug, $active_cats);
                                }
                            ?>
                                <div class="filtro-child-option">
                                    <input type="checkbox" id="cat-<?php echo esc_attr($child->slug); ?>" 
                                        class="filtro-category filtro-child" value="<?php echo esc_attr($child->slug); ?>"
                                        <?php checked($child_active, true); ?> />
                                    <label for="cat-<?php echo esc_attr($child->slug); ?>">
                                        <?php echo esc_html($child->name); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php
            }
            echo '</div>';
        }
        ?>
    </div>
</aside>
        
        <!-- Contenido principal (columna derecha) -->
        <main class="productos-main">
            <!-- Breadcrumbs -->
            <div class="productos-breadcrumb">
                <?php woocommerce_breadcrumb(); ?>
            </div>
            
            <!-- 3. CUADRÍCULA DE PRODUCTOS -->
            <div class="productos-wrapper">
                <?php
                if (have_posts()) {
                    // Abrir directamente la cuadrícula de productos
                    woocommerce_product_loop_start();
                    
                    while (have_posts()) {
                        the_post();
                        wc_get_template_part('content', 'product');
                    }
                    
                    woocommerce_product_loop_end();
                    
                    // Paginación
                    echo '<div class="productos-pagination">';
                    woocommerce_pagination();
                    echo '</div>';
                } else {
                    echo '<p class="no-products-found">' . 
                         esc_html__('No se encontraron productos que coincidan con tu búsqueda.', 'wc-productos-template') . 
                         '</p>';
                }
                ?>
            </div>
        </main>
    </div>
</div>

<?php
// Cerrar cualquier contenedor abierto por el tema
do_action('woocommerce_after_main_content');
?>
