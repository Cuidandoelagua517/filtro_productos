<?php
/**
 * Template para mostrar productos mediante shortcode
 * Versión corregida para eliminar títulos duplicados y optimizada para AJAX
 *
 * @package WC_Productos_Template
 */

// Obtener la página actual desde la URL
$current_page = get_query_var('paged') ? get_query_var('paged') : 1;

// Eliminar posibles títulos duplicados
add_filter('woocommerce_show_page_title', '__return_false');
?>

<div class="productos-container wc-productos-template">
    <!-- Header con título y barra de búsqueda -->
    <div class="productos-header">
        <h1>Productos</h1>
        
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
    
    <!-- Layout de dos columnas -->
    <div class="productos-layout">
        <!-- Sidebar de filtros (columna izquierda) -->
        <aside class="productos-sidebar">
            <h2><?php esc_html_e('Filtros', 'wc-productos-template'); ?></h2>
            
            <!-- Filtro de categorías -->
            <div class="filtro-grupo" id="filtro-categorias">
                <h3><?php esc_html_e('Categoría', 'wc-productos-template'); ?></h3>
                <?php
                $product_categories = get_terms(array(
                    'taxonomy' => 'product_cat',
                    'hide_empty' => true,
                ));
                
                if (!empty($product_categories) && !is_wp_error($product_categories)) {
                    echo '<div class="filtro-lista">';
                    foreach ($product_categories as $category) {
                        // Excluir la categoría "Uncategorized" o su equivalente
                        if ($category->slug === 'uncategorized') {
                            continue;
                        }
                        
                        $active = false;
                        if (isset($atts['category']) && $atts['category'] === $category->slug) {
                            $active = true;
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
            <div class="filtro-grupo" id="filtro-grados">
                <h3><?php esc_html_e('Grado', 'wc-productos-template'); ?></h3>
                <?php
                $grado_terms = get_terms(array(
                    'taxonomy' => 'pa_grado',
                    'hide_empty' => true,
                ));
                
                if (!empty($grado_terms) && !is_wp_error($grado_terms)) {
                    echo '<div class="filtro-lista">';
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
                <?php 
                // Usar función de breadcrumb compatible
                if (function_exists('woocommerce_breadcrumb')) {
                    woocommerce_breadcrumb();
                }
                ?>
            </div>

            <!-- Wrapper para la cuadrícula de productos -->
            <div class="productos-wrapper">
                <?php
                // Obtener la página actual
                $current_page = get_query_var('paged') ? get_query_var('paged') : 1;

                // Establecer un valor por defecto para posts_per_page
                $posts_per_page = isset($atts['per_page']) ? intval($atts['per_page']) : get_option('posts_per_page', 12);

                // Preparar los argumentos de la consulta
                $args = array(
                    'post_type' => 'product',
                    'posts_per_page' => $posts_per_page,
                    'paged' => $current_page,
                    'post_status' => 'publish',
                );

                // Aplicar filtros de categoría si están presentes en los atributos del shortcode
                if (!empty($atts['category'])) {
                    $args['tax_query'][] = array(
                        'taxonomy' => 'product_cat',
                        'field' => 'slug',
                        'terms' => $atts['category'],
                    );
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
                
                if ($products_query->have_posts()) {
                    // Abrir la cuadrícula
                    woocommerce_product_loop_start();
                    
                    while ($products_query->have_posts()) {
                        $products_query->the_post();
                        
                        // Configurar la variable global $product
                        global $product;
                        $product = wc_get_product(get_the_ID());
                        
                        wc_get_template_part('content', 'product');
                    }
                    
                    woocommerce_product_loop_end();
                    
                    wp_reset_postdata();
                    
                    // Agregar paginación
                    if ($products_query->max_num_pages > 1) {
                        echo '<div class="productos-pagination">';
                        
                        echo '<div class="pagination-info">';
                        $start = (($current_page - 1) * $posts_per_page) + 1;
                        $end = min($products_query->found_posts, $current_page * $posts_per_page);
                        
                        printf(
                            esc_html__('Mostrando %1$d-%2$d de %3$d resultados', 'wc-productos-template'),
                            $start,
                            $end,
                            $products_query->found_posts
                        );
                        echo '</div>';
                        
                        echo '<div class="pagination-links">';
                        
                        // Botones de paginación
                        if ($current_page > 1) {
                            echo '<button class="page-number page-prev" data-page="' . ($current_page - 1) . '">←</button>';
                        }
                        
                        $start_page = max(1, $current_page - 2);
                        $end_page = min($products_query->max_num_pages, $current_page + 2);
                        
                        if ($start_page > 1) {
                            echo '<button class="page-number" data-page="1">1</button>';
                            
                            if ($start_page > 2) {
                                echo '<span class="page-dots">...</span>';
                            }
                        }
                        
                        for ($i = $start_page; $i <= $end_page; $i++) {
                            $active_class = ($i === $current_page) ? ' active' : '';
                            echo '<button class="page-number' . $active_class . '" data-page="' . $i . '">' . $i . '</button>';
                        }
                        
                        if ($end_page < $products_query->max_num_pages) {
                            if ($end_page < $products_query->max_num_pages - 1) {
                                echo '<span class="page-dots">...</span>';
                            }
                            
                            echo '<button class="page-number" data-page="' . $products_query->max_num_pages . '">' . $products_query->max_num_pages . '</button>';
                        }
                        
                        if ($current_page < $products_query->max_num_pages) {
                            echo '<button class="page-number page-next" data-page="' . ($current_page + 1) . '">→</button>';
                        }
                        
                        echo '</div>'; // fin .pagination-links
                        echo '</div>'; // fin .productos-pagination
                    }
                } else {
                    echo '<p class="no-products-found">' . esc_html__('No se encontraron productos.', 'wc-productos-template') . '</p>';
                }
                ?>
            </div>
        </main>
    </div>
</div>

<!-- Script para inicializar los controles de filtrado -->
<script type="text/javascript">
    jQuery(document).ready(function($) {
        if (typeof filterProducts === 'function') {
            // Los event listeners se manejan en productos-template.js
            console.log('Filtros inicializados');
        }
    });
</script>
