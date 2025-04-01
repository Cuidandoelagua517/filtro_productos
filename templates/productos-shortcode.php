<?php
/**
 * Template para productos-shortcode.php
 */
?>
<div class="productos-container">
    <!-- Header -->
    <div class="productos-header">
        <h1><?php echo esc_html__('Productos', 'wc-productos-template'); ?></h1>
        
        <!-- Barra de búsqueda -->
        <div class="productos-search">
            <input type="text" placeholder="Buscar por nombre, referencia o características..." />
            <button><i class="fas fa-search"></i></button>
        </div>
    </div>
    
    <!-- Nueva estructura con filtros y productos al mismo nivel -->
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
                                class="filtro-category" value="<?php echo esc_attr($category->slug); ?>" 
                                <?php checked($atts['category'] === $category->slug, true); ?> />
                            <label for="cat-<?php echo esc_attr($category->slug); ?>">
                                <?php echo esc_html($category->name); ?>
                            </label>
                        </div>
                        <?php
                    }
                }
                ?>
            </div>
            
            <!-- Filtro de grado -->
            <div class="filtro-grupo">
                <h4><?php esc_html_e('Grado', 'wc-productos-template'); ?></h4>
                <?php
                $grado_terms = get_terms(array(
                    'taxonomy' => 'pa_grado',
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
            
            <!-- Listado de productos en formato grid -->
            <div class="productos-grid">
                <?php
                $args = array(
                    'post_type' => 'product',
                    'posts_per_page' => $atts['per_page'],
                );
                
                if (!empty($atts['category'])) {
                    $args['tax_query'] = array(
                        array(
                            'taxonomy' => 'product_cat',
                            'field' => 'slug',
                            'terms' => $atts['category'],
                        ),
                    );
                }
                
                $products_query = new WP_Query($args);
                
                if ($products_query->have_posts()) {
                    while ($products_query->have_posts()) {
                        $products_query->the_post();
                        global $product;
                        ?>
                        <div class="producto-card">
                            <div class="producto-imagen">
                                <?php if ($product->is_in_stock()) : ?>
                                    <span class="producto-badge badge-stock"><?php esc_html_e('En stock', 'wc-productos-template'); ?></span>
                                <?php endif; ?>
                                
                                <?php if (has_post_thumbnail()) : ?>
                                    <?php the_post_thumbnail('woocommerce_thumbnail'); ?>
                                <?php else : ?>
                                    <?php echo wc_placeholder_img(); ?>
                                <?php endif; ?>
                            </div>
                            
                            <h3 class="producto-titulo"><?php the_title(); ?></h3>
                            
                            <p class="producto-detalles">
                                <?php
                                $volumen = $product->get_attribute('pa_volumen') ?: get_post_meta($product->get_id(), '_volumen_ml', true);
                                if ($volumen) {
                                    echo esc_html($volumen) . ' ml';
                                }
                                
                                $grado = $product->get_attribute('pa_grado');
                                if ($grado) {
                                    echo ' - ' . esc_html__('Grado', 'wc-productos-template') . ' ' . esc_html($grado);
                                }
                                ?>
                            </p>
                            
                            <div class="producto-precio"><?php echo $product->get_price_html(); ?></div>
                            
                            <button class="producto-boton" data-product-id="<?php echo esc_attr($product->get_id()); ?>">
                                <?php esc_html_e('Agregar al carrito', 'wc-productos-template'); ?>
                            </button>
                        </div>
                        <?php
                    }
                    
                    wp_reset_postdata();
                } else {
                    echo '<p>' . esc_html__('No se encontraron productos.', 'wc-productos-template') . '</p>';
                }
                ?>
            </div>
            
            <!-- Paginación -->
            <?php if ($products_query->max_num_pages > 1) : ?>
                <div class="productos-pagination">
                    <div class="pagination-info">
                        <?php
                        printf(
                            esc_html__('Mostrando 1-%1$d de %2$d resultados', 'wc-productos-template'),
                            min($products_query->found_posts, $atts['per_page']),
                            $products_query->found_posts
                        );
                        ?>
                    </div>
                    
                    <div class="pagination-links">
                        <?php
                        for ($i = 1; $i <= min(4, $products_query->max_num_pages); $i++) {
                            $class = $i === 1 ? 'active' : '';
                            printf(
                                '<div class="page-number %1$s" data-page="%2$d">%2$d</div>',
                                esc_attr($class),
                                esc_attr($i)
                            );
                        }
                        
                        if ($products_query->max_num_pages > 4) {
                            echo '<div class="page-number" data-page="2">→</div>';
                        }
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
