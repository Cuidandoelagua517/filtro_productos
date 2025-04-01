<?php
/**
 * Template para el archivo de productos (archive-product.php)
 */
?>
<div class="productos-container">
    <!-- Header -->
    <div class="productos-header">
        <h1><?php echo esc_html(woocommerce_page_title(false)); ?></h1>
        
        <!-- Barra de búsqueda -->
        <div class="productos-search">
            <input type="text" placeholder="Buscar por nombre, referencia o características..." />
            <button><i class="fas fa-search"></i></button>
        </div>
    </div>
    
    <div class="productos-content">
        <!-- Sidebar de filtros -->
        <div class="productos-sidebar">
            <h3><?php esc_html_e('Filtros', 'wc-productos-template'); ?></h3>
            
            <!-- Filtro de categorías -->
            <div class="filtro-grupo">
                <h4><?php esc_html_e('Categoría', 'wc-productos-template'); ?></h4>
                <?php
                $product_categories = get_terms(array(
                    'taxonomy' => 'product_cat',
                    'hide_empty' => true,
                ));
                
                if (!empty($product_categories) && !is_wp_error($product_categories)) {
                    foreach ($product_categories as $category) {
                        ?>
                        <div class="filtro-option">
                            <input type="checkbox" id="cat-<?php echo esc_attr($category->slug); ?>" 
                                class="filtro-category" value="<?php echo esc_attr($category->slug); ?>" />
                            <label for="cat-<?php echo esc_attr($category->slug); ?>">
                                <?php echo esc_html($category->name); ?>
                            </label>
                        </div>
                        <?php
                    }
                }
                ?>
            </div>
            
            <!-- Filtro de grado (usando atributos de producto) -->
            <div class="filtro-grupo">
                <h4><?php esc_html_e('Grado', 'wc-productos-template'); ?></h4>
                <?php
                $grado_terms = get_terms(array(
                    'taxonomy' => 'pa_grado', // Asegúrate de que este atributo existe
                    'hide_empty' => true,
                ));
                
                if (!empty($grado_terms) && !is_wp_error($grado_terms)) {
                    foreach ($grado_terms as $term) {
                        ?>
                        <div class="filtro-option">
                            <input type="checkbox" id="grade-<?php echo esc_attr($term->slug); ?>" 
                                class="filtro-grade" value="<?php echo esc_attr($term->slug); ?>" />
                            <label for="grade-<?php echo esc_attr($term->slug); ?>">
                                <?php echo esc_html($term->name); ?>
                            </label>
                        </div>
                        <?php
                    }
                } else {
                    // Fallback para demostración
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
                }
                ?>
            </div>
            
            <!-- Filtro de volumen -->
            <div class="filtro-grupo">
                <h4><?php esc_html_e('Volumen', 'wc-productos-template'); ?></h4>
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
        </div>
        
        <!-- Contenido principal -->
        <div class="productos-main">
            <!-- Breadcrumbs -->
            <div class="productos-breadcrumb">
                <?php woocommerce_breadcrumb(); ?>
            </div>
            
            <!-- Listado de productos -->
            <div class="productos-list">
                <?php
                if (have_posts()) {
                    while (have_posts()) {
                        the_post();
                        wc_get_template_part('content', 'product');
                    }
                } else {
                    echo '<p>' . esc_html__('No se encontraron productos.', 'wc-productos-template') . '</p>';
                }
                ?>
            </div>
            
            <!-- Paginación -->
            <div class="productos-pagination">
                <div class="pagination-info">
                    <?php
                    $total = wc_get_loop_prop('total');
                    $per_page = wc_get_loop_prop('per_page');
                    $current = wc_get_loop_prop('current_page');
                    $showing = min($total, $per_page * $current);
                    
                    printf(
                        esc_html__('Mostrando %1$d-%2$d de %3$d resultados', 'wc-productos-template'),
                        ($current - 1) * $per_page + 1,
                        $showing,
                        $total
                    );
                    ?>
                </div>
                
                <div class="pagination-links">
                    <?php
                    $total_pages = ceil($total / $per_page);
                    
                    for ($i = 1; $i <= min(4, $total_pages); $i++) {
                        $class = $i === $current ? 'active' : '';
                        printf(
                            '<div class="page-number %1$s" data-page="%2$d">%2$d</div>',
                            esc_attr($class),
                            esc_attr($i)
                        );
                    }
                    
                    if ($total_pages > 4) {
                        echo '<div class="page-number" data-page="' . esc_attr($current + 1) . '">→</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
