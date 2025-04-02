<?php
/**
 * Template para el archivo de productos (archive-product.php)
 * 
 * @package WC_Productos_Template
 */

// Eliminar hooks por defecto de WooCommerce que podrían interferir
remove_action('woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10);
remove_action('woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10);
remove_action('woocommerce_before_shop_loop', 'woocommerce_result_count', 20);
remove_action('woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30);

// Obtener la página actual
$current_page = max(1, get_query_var('paged'));
?>

<div class="productos-container wc-productos-template">
<!-- Header -->
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
    
    <!-- Layout de dos columnas -->
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
                        // Verificar si está activa esta categoría
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
            
            <!-- Filtro de grado (usando atributos de producto) -->
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
                        // Verificar si está activo este grado
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
                } else {
                    // Fallback para demostración
                    echo '<div class="filtro-lista">';
                    ?>
                    <div class="filtro-option">
                        <input type="checkbox" id="grade-analitico" class="filtro-grade" value="analitico" />
                        <label for="grade-analitico"><?php esc_html_e('Analítico', 'wc-productos-template'); ?></label>
                    </div>
                    <div class="filtro-option">
                        <input type="checkbox" id="grade-reactivo" class="filtro-grade" value="reactivo" />
                        <label for="grade-reactivo"><?php esc_html_e('Reactivo', 'wc-productos-template'); ?></label>
                    </div>
                    <?php
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
            
            <!-- IMPORTANTE: Eliminamos el div.productos-grid que estaba generando una estructura anidada y conflictiva -->
            <?php
            if (have_posts()) {
                // Inicio del loop con estilos forzados directamente
               echo '<ul class="products productos-grid wc-productos-template columns-' . esc_attr(wc_get_loop_prop('columns', 4)) . '">';
                
                while (have_posts()) {
                    the_post();
                    wc_get_template_part('content', 'product');
                }
                
                echo '</ul>';
            } else {
                /**
                 * Hook: woocommerce_no_products_found.
                 *
                 * @hooked wc_no_products_found - 10
                 */
                do_action('woocommerce_no_products_found');
            }
            ?>
            
            <!-- Paginación -->
            <?php
            // Usar la función del plugin para la paginación consistente
            if (class_exists('WC_Productos_Template')) {
                $plugin = new WC_Productos_Template();
                
                // Si existe el método render_pagination
                if (method_exists($plugin, 'render_pagination')) {
                    $plugin->render_pagination(
                        wc_get_loop_prop('total_pages'),
                        wc_get_loop_prop('current_page')
                    );
                } else {
                    // Fallback a la paginación estándar de WooCommerce
                    woocommerce_pagination();
                }
            } else {
                // Paginación manual si no está disponible la clase
                $total = wc_get_loop_prop('total');
                $per_page = wc_get_loop_prop('per_page');
                $current = wc_get_loop_prop('current_page');
                $total_pages = ceil($total / $per_page);
                ?>
                <div class="productos-pagination">
                    <div class="pagination-info">
                        <?php
                        printf(
                            esc_html__('Mostrando %1$d-%2$d de %3$d resultados', 'wc-productos-template'),
                            (($current - 1) * $per_page) + 1,
                            min($total, $current * $per_page),
                            $total
                        );
                        ?>
                    </div>
                    
                    <div class="pagination-links">
                        <?php
                        // Botón anterior
                        if ($current > 1) {
                            echo '<button class="page-number page-prev" data-page="' . 
                                 esc_attr($current - 1) . '">←</button>';
                        }
                        
                        // Páginas numéricas
                        for ($i = 1; $i <= min(4, $total_pages); $i++) {
                            $class = $i === $current ? 'active' : '';
                            printf(
                                '<button class="page-number %1$s" data-page="%2$d">%2$d</button>',
                                esc_attr($class),
                                esc_attr($i)
                            );
                        }
                        
                        // Botón siguiente
                        if ($current < $total_pages) {
                            echo '<button class="page-number page-next" data-page="' . 
                                 esc_attr($current + 1) . '">→</button>';
                        }
                        ?>
                    </div>
                </div>
                <?php
            }
            ?>
        </main>
    </div>
</div>

<?php
// Hook para permitir scripts adicionales
do_action('wc_productos_template_after_main_content');
?>
