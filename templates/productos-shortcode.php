<?php
/**
 * Template para mostrar productos mediante shortcode
 * Versión corregida para la paginación
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

                // Usar la configuración de productos por página de WooCommerce
                $posts_per_page = !empty($atts['per_page']) ? intval($atts['per_page']) : get_option('posts_per_page');

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
                wc_set_loop_prop('columns', 3); // Establecer a 3 columnas
                
                if ($products_query->have_posts()) {
                    // Abrir la cuadrícula con estilos forzados para 3 columnas
                    echo '<ul class="products productos-grid wc-productos-template columns-3">';
                    
                    // Mostrar todos los productos de la página actual
                    while ($products_query->have_posts()) {
                        $products_query->the_post();
                        
                        // Configurar la variable global $product
                        global $product;
                        $product = wc_get_product(get_the_ID());
                        
                        // Renderizar el producto
                        wc_get_template_part('content', 'product');
                    }
                    
                    echo '</ul><!-- .productos-grid -->';
                    
                    wp_reset_postdata();
                    
                    // Agregar paginación personalizada
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
                        
                        // Botón "Anterior"
                        if ($current_page > 1) {
                            echo '<a href="javascript:void(0);" class="page-number page-prev" data-page="' . ($current_page - 1) . '">←</a>';
                        }
                        
                        // Números de página
                        $start_page = max(1, $current_page - 2);
                        $end_page = min($products_query->max_num_pages, $current_page + 2);
                        
                        if ($start_page > 1) {
                            echo '<a href="javascript:void(0);" class="page-number" data-page="1">1</a>';
                            
                            if ($start_page > 2) {
                                echo '<span class="page-dots">...</span>';
                            }
                        }
                        
                        for ($i = $start_page; $i <= $end_page; $i++) {
                            $active_class = ($i === $current_page) ? ' active' : '';
                            echo '<a href="javascript:void(0);" class="page-number' . $active_class . '" data-page="' . $i . '">' . $i . '</a>';
                        }
                        
                        if ($end_page < $products_query->max_num_pages) {
                            if ($end_page < $products_query->max_num_pages - 1) {
                                echo '<span class="page-dots">...</span>';
                            }
                            
                            echo '<a href="javascript:void(0);" class="page-number" data-page="' . $products_query->max_num_pages . '">' . $products_query->max_num_pages . '</a>';
                        }
                        
                        // Botón "Siguiente"
                        if ($current_page < $products_query->max_num_pages) {
                            echo '<a href="javascript:void(0);" class="page-number page-next" data-page="' . ($current_page + 1) . '">→</a>';
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

<script type="text/javascript">
// Script para asegurar que los eventos de paginación se vinculen correctamente
jQuery(document).ready(function($) {
    // Asegurarse de que los eventos de paginación se vinculen
    if (typeof window.filterProducts === 'function') {
        // Vincular eventos de paginación
        $('.productos-pagination .page-number').on('click', function(e) {
            e.preventDefault();
            var page = $(this).data('page');
            if (page) {
                window.filterProducts(page);
            }
            return false;
        });
    }
});
</script>
