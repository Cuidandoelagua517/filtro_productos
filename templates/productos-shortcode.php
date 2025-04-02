<?php
/**
 * Template para mostrar productos mediante shortcode
 * Versión corregida para visualización en cuadrícula
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

            <?php
            // Obtener la página actual desde la URL
            $current_page = get_query_var('paged') ? get_query_var('paged') : 1;

            // Establecer un valor por defecto razonable para posts_per_page
            $posts_per_page = isset($atts['per_page']) ? intval($atts['per_page']) : get_option('posts_per_page', 12);

            // Preparar los argumentos de la consulta
            $args = array(
                'post_type' => 'product',
                'posts_per_page' => $posts_per_page,
                'paged' => $current_page,
                'post_status' => 'publish',
            );

            // Aplicar filtros de categoría si están presentes en la URL o en los atributos del shortcode
            if (!empty($atts['category'])) {
                $args['tax_query'][] = array(
                    'taxonomy' => 'product_cat',
                    'field' => 'slug',
                    'terms' => $atts['category'],
                );
            } elseif (isset($_GET['category']) && !empty($_GET['category'])) {
                $categories = explode(',', sanitize_text_field($_GET['category']));
                $args['tax_query'][] = array(
                    'taxonomy' => 'product_cat',
                    'field' => 'slug',
                    'terms' => $categories,
                    'operator' => 'IN',
                );
            }

            // Aplicar filtros de grado (atributo de producto)
            if (isset($_GET['grade']) && !empty($_GET['grade'])) {
                $grades = explode(',', sanitize_text_field($_GET['grade']));
                $args['tax_query'][] = array(
                    'taxonomy' => 'pa_grado',
                    'field' => 'slug',
                    'terms' => $grades,
                    'operator' => 'IN',
                );
            }

            // Aplicar filtro de búsqueda
            if (isset($_GET['s']) && !empty($_GET['s'])) {
                $args['s'] = sanitize_text_field($_GET['s']);
            }

            // Aplicar filtros de volumen si están presentes
            if (isset($_GET['min_volume']) && isset($_GET['max_volume'])) {
                $min_volume = intval($_GET['min_volume']);
                $max_volume = intval($_GET['max_volume']);
                
                if ($min_volume > 100 || $max_volume < 5000) {
                    $args['meta_query'][] = array(
                        'key' => '_volumen_ml',
                        'value' => array($min_volume, $max_volume),
                        'type' => 'NUMERIC',
                        'compare' => 'BETWEEN',
                    );
                }
            }

            // Configurar orden 
            if (isset($_GET['orderby'])) {
                $orderby = sanitize_text_field($_GET['orderby']);
                $args['orderby'] = $orderby;
            }

            if (isset($_GET['order'])) {
                $order = strtoupper(sanitize_text_field($_GET['order']));
                if (in_array($order, array('ASC', 'DESC'))) {
                    $args['order'] = $order;
                }
            }

            // Ejecutar la consulta
            $products_query = new WP_Query($args);

            // Configurar propiedades importantes para WooCommerce
            wc_set_loop_prop('current_page', $current_page);
            wc_set_loop_prop('is_paginated', true);
            wc_set_loop_prop('page_template', 'productos-template');
            wc_set_loop_prop('per_page', $posts_per_page);
            wc_set_loop_prop('total', $products_query->found_posts);
            wc_set_loop_prop('total_pages', $products_query->max_num_pages);
            wc_set_loop_prop('columns', 4); // Ajustar según el diseño
            
            // IMPORTANTE: Eliminamos el div.productos-grid sobrante que causa conflictos
            if ($products_query->have_posts()) {
                // Forzar formato de cuadrícula directamente con HTML y estilos inline
           echo '<ul class="products productos-grid wc-productos-template columns-' . esc_attr(wc_get_loop_prop('columns', 4)) . '">';
                
                while ($products_query->have_posts()) {
                    $products_query->the_post();
                    
                    // Configurar la variable global $product
                    global $product;
                    $product = wc_get_product(get_the_ID());
                    
                    wc_get_template_part('content', 'product');
                }
                
                echo '</ul>';
                wp_reset_postdata();
            } else {
                echo '<p class="no-products-found">' . esc_html__('No se encontraron productos que coincidan con los criterios de búsqueda.', 'wc-productos-template') . '</p>';
            }
            ?>
            
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
                    // Construir los enlaces de paginación con URLs reales
                    $base_url = add_query_arg(null, null); // URL actual con todos los parámetros
                    $base_url = remove_query_arg('paged', $base_url); // Remover el parámetro de página
                    
                    // Botón "Anterior" si no estamos en la primera página
                    if ($current_page > 1) {
                        $prev_url = add_query_arg('paged', $current_page - 1, $base_url);
                        printf(
                            '<a href="%s" class="page-number page-prev" aria-label="%s">←</a>',
                            esc_url($prev_url),
                            esc_attr__('Página anterior', 'wc-productos-template')
                        );
                    }
                    
                    // Mostrar números de página
                    $start_page = max(1, $current_page - 2);
                    $end_page = min($products_query->max_num_pages, $current_page + 2);
                    
                    if ($start_page > 1) {
                        $first_url = add_query_arg('paged', 1, $base_url);
                        printf(
                            '<a href="%s" class="page-number">1</a>',
                            esc_url($first_url)
                        );
                        
                        if ($start_page > 2) {
                            echo '<span class="page-dots">...</span>';
                        }
                    }
                    
                    for ($i = $start_page; $i <= $end_page; $i++) {
                        $page_url = add_query_arg('paged', $i, $base_url);
                        
                        if ($i === $current_page) {
                            printf(
                                '<span class="page-number active">%d</span>',
                                $i
                            );
                        } else {
                            printf(
                                '<a href="%s" class="page-number">%d</a>',
                                esc_url($page_url),
                                $i
                            );
                        }
                    }
                    
                    if ($end_page < $products_query->max_num_pages) {
                        if ($end_page < $products_query->max_num_pages - 1) {
                            echo '<span class="page-dots">...</span>';
                        }
                        
                        $last_url = add_query_arg('paged', $products_query->max_num_pages, $base_url);
                        printf(
                            '<a href="%s" class="page-number">%d</a>',
                            esc_url($last_url),
                            $products_query->max_num_pages
                        );
                    }
                    
                    // Botón "Siguiente" si no estamos en la última página
                    if ($current_page < $products_query->max_num_pages) {
                        $next_url = add_query_arg('paged', $current_page + 1, $base_url);
                        printf(
                            '<a href="%s" class="page-number page-next" aria-label="%s">→</a>',
                            esc_url($next_url),
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
