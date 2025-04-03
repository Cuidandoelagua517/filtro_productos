<?php
/**
 * Template para el archivo de productos (archive-product.php)
 * Versión corregida con estructura HTML limpia y ordenada
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
            <form role="search" method="get" id="productos-search-form" action="<?php echo esc_url(home_url('/')); ?>">
                <input type="text" 
                       id="productos-search-input"
                       name="s" 
                       placeholder="<?php esc_attr_e('Buscar por nombre, referencia o características...', 'wc-productos-template'); ?>" 
                       value="<?php echo get_search_query(); ?>" />
                <input type="hidden" name="post_type" value="product" />
                <button type="submit" aria-label="<?php esc_attr_e('Buscar', 'wc-productos-template'); ?>">
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
            
            <!-- Filtro de categorías -->
            <div class="filtro-grupo">
                <h3><?php esc_html_e('Categoría', 'wc-productos-template'); ?></h3>
                <?php
                $product_categories = get_terms(array(
                    'taxonomy' => 'product_cat',
                    'hide_empty' => true,
                ));
                
                if (!empty($product_categories) && !is_wp_error($product_categories)) {
                    echo '<div class="filtro-lista">';
                    foreach ($product_categories as $category) {
                        $active = false;
                        if (isset($_GET['category'])) {
                            $active_cats = explode(',', $_GET['category']);
                            $active = in_array($category->slug, $active_cats);
                        }
                        ?>
                        <div class="filtro-option">
                            <input type="checkbox" id="cat-<?php echo esc_attr($category->slug); ?>" 
                                class="filtro-category" value="<?php echo esc_attr($category->slug); ?>"
                                <?php checked($active, true); ?> />
                            <label for="cat-<?php echo esc_attr($category->slug); ?>">
                                <?php echo esc_html($category->name); ?>
                            </label>
                        </div>
                        <?php
                    }
                    echo '</div>';
                }
                ?>
            </div>
            
            <!-- Filtro de grado -->
            <div class="filtro-grupo">
                <h3><?php esc_html_e('Grado', 'wc-productos-template'); ?></h3>
                <?php
                $grado_terms = get_terms(array(
                    'taxonomy' => 'pa_grado',
                    'hide_empty' => true,
                ));
                
                if (!empty($grado_terms) && !is_wp_error($grado_terms)) {
                    echo '<div class="filtro-lista">';
                    foreach ($grado_terms as $term) {
                        $active = false;
                        if (isset($_GET['grade'])) {
                            $active_grades = explode(',', $_GET['grade']);
                            $active = in_array($term->slug, $active_grades);
                        }
                        ?>
                        <div class="filtro-option">
                            <input type="checkbox" id="grade-<?php echo esc_attr($term->slug); ?>" 
                                class="filtro-grade" value="<?php echo esc_attr($term->slug); ?>"
                                <?php checked($active, true); ?> />
                            <label for="grade-<?php echo esc_attr($term->slug); ?>">
                                <?php echo esc_html($term->name); ?>
                            </label>
                        </div>
                        <?php
                    }
                    echo '</div>';
                }
                ?>
            </div>
            
            <!-- Filtro de volumen -->
            <div class="filtro-grupo">
                <h3><?php esc_html_e('Volumen', 'wc-productos-template'); ?></h3>
                <div class="volumen-slider">
                    <div class="volumen-range"></div>
                    <div class="volumen-values">
                        <span id="volumen-min">100 ml</span>
                        <span id="volumen-max">5000 ml</span>
                    </div>
                    <input type="hidden" name="min_volume" value="100" />
                    <input type="hidden" name="max_volume" value="5000" />
                </div>
            </div>
        </aside>
        
        <!-- Contenido principal (columna derecha) -->
        <main class="productos-main">
            <!-- Breadcrumbs -->
            <div class="productos-breadcrumb">
                <?php woocommerce_breadcrumb(); ?>
            </div>
            
            <!-- 3. CUADRÍCULA DE PRODUCTOS (directamente dentro de main, NO dentro de otro div) -->
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
        </main>
    </div>
</div>

<?php
// Cerrar cualquier contenedor abierto por el tema
do_action('woocommerce_after_main_content');
?>
