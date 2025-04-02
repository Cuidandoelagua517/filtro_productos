<?php
/**
 * Template para mostrar productos mediante shortcode
 * Versión modificada para evitar conflictos
 * 
 * @package WC_Productos_Template
 */

// Obtener la página actual desde la URL
$current_page = get_query_var('paged') ? get_query_var('paged') : 1;
?>
<div class="productos-container wc-productos-template">
    <!-- Header -->
    <div class="productos-header">
        <h1><?php echo esc_html__('Productos', 'wc-productos-template'); ?></h1>
        
        <!-- Barra de búsqueda -->
        <div class="productos-search">
            <input type="text" placeholder="<?php esc_attr_e('Buscar por nombre, referencia o características...', 'wc-productos-template'); ?>" />
            <button type="button" aria-label="<?php esc_attr_e('Buscar', 'wc-productos-template'); ?>">
                <i class="fas fa-search" aria-hidden="true"></i>
            </button>
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
                    foreach ($product_categories as $category) {
                        ?>
                        <div class="filtro-option">
                            <input type="checkbox" id="cat-<?php echo esc_attr($category->slug); ?>" 
                                class="filtro-category" value="<?php echo esc_attr($category->slug); ?>" 
                                <?php if (isset($atts['category']) && $atts['category'] === $category->slug) echo 'checked'; ?> />
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
                <h3><?php esc_html_e('Grado', 'wc-productos-template'); ?></h3>
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
                <?php 
                // Usar función de breadcrumb compatible
                if (function_exists('woocommerce_breadcrumb')) {
                    woocommerce_breadcrumb();
                }
                ?>
            </div>
            
            <!-- Listado de productos -->
            <div class="productos-list">
                <?php
                // Establecer un valor por defecto razonable para posts_per_page
                $posts_per_page = isset($atts['per_page']) ? intval($atts['per_page']) : get_option('posts_per_page', 12);
                
                $args = array(
                    'post_type' => 'product',
                    'posts_per_page' => $posts_per_page,
                    'paged' => $current_page,
                    'post_status' => 'publish',
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
                
                // Configurar propiedades importantes para WooCommerce
                wc_set_loop_prop('current_page', $current_page);
                wc_set_loop_prop('is_paginated', true);
                wc_set_loop_prop('page_template', 'productos-template');
                wc_set_loop_prop('per_page', $posts_per_page);
                wc_set_loop_prop('total', $products_query->found_posts);
                wc_set_loop_prop('total_pages', $products_query->max_num_pages);
                wc_set_loop_prop('columns', 4); // Ajusta según tu diseño
                
                if ($products_query->have_posts()) {
                    echo '<ul class="productos-grid products columns-' . esc_attr(wc_get_loop_prop('columns', 4)) . '">';
                    while ($products_query->have_posts()) {
                        $products_query->the_post();
                        wc_get_template_part('content', 'product');
                    }
                    echo '</ul>';
                    
                    wp_reset_postdata();
                } else {
                    echo '<p class="productos-no-results">' . esc_html__('No se encontraron productos.', 'wc-productos-template') . '</p>';
                }
                ?>
            </div>
            
            <!-- Paginación -->
            <?php if ($products_query->max_num_pages > 1) : ?>
                <div class="productos-pagination">
                    <div class="pagination-info">
                        <?php
                        $start = (($current_page - 1) * $posts_per_page) + 1;
                        $end = min($products_query->found_posts, $current_page * $posts_per_page);
                        
                        printf(
                            esc_html__('Mostrando %1$d-%2$d de %3$d resultados', 'wc-productos-template'),
                            $start,
                            $end,
                            $products_query->found_posts
                        );
                        ?>
                    </div>
                    
                    <div class="pagination-links">
                        <?php
                        // Botón "Anterior" si no estamos en la primera página
                        if ($current_page > 1) {
                            printf(
                                '<button class="page-number page-prev" data-page="%d" aria-label="%s">←</button>',
                                $current_page - 1,
                                esc_attr__('Página anterior', 'wc-productos-template')
                            );
                        }
                        
                        // Mostrar números de página
                        $start_page = max(1, $current_page - 2);
                        $end_page = min($products_query->max_num_pages, $current_page + 2);
                        
                        if ($start_page > 1) {
                            echo '<button class="page-number" data-page="1">1</button>';
                            if ($start_page > 2) {
                                echo '<span class="page-dots">...</span>';
                            }
                        }
                        
                        for ($i = $start_page; $i <= $end_page; $i++) {
                            printf(
                                '<button class="page-number%s" data-page="%d">%d</button>',
                                $i === $current_page ? ' active' : '',
                                $i,
                                $i
                            );
                        }
                        
                        if ($end_page < $products_query->max_num_pages) {
                            if ($end_page < $products_query->max_num_pages - 1) {
                                echo '<span class="page-dots">...</span>';
                            }
                            printf(
                                '<button class="page-number" data-page="%d">%d</button>',
                                $products_query->max_num_pages,
                                $products_query->max_num_pages
                            );
                        }
                        
                        // Botón "Siguiente" si no estamos en la última página
                        if ($current_page < $products_query->max_num_pages) {
                            printf(
                                '<button class="page-number page-next" data-page="%d" aria-label="%s">→</button>',
                                $current_page + 1,
                                esc_attr__('Página siguiente', 'wc-productos-template')
                            );
                        }
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>
