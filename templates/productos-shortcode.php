<?php
/**
 * Template para mostrar productos mediante shortcode
 * Modificado para mostrar 3 filas de 3 tarjetas cada una
 *
 * @package WC_Productos_Template
 */

// Obtener la página actual desde la URL
$current_page = get_query_var('paged') ? get_query_var('paged') : 1;

// Eliminar posibles títulos duplicados
add_filter('woocommerce_show_page_title', '__return_false');
?>

<div class="productos-container wc-productos-template limited-grid">
    <!-- Header con título y barra de búsqueda -->
    <div class="productos-header">
        <h1>Productos Destacados</h1>
    </div>

    <!-- Layout de productos -->
    <div class="productos-layout">
        <!-- Contenido principal -->
        <main class="productos-main">
            <!-- Wrapper para la cuadrícula de productos -->
            <div class="productos-wrapper">
                <?php
                // Preparar los argumentos de la consulta
                $args = array(
                    'post_type' => 'product',
                    'posts_per_page' => 9, // Exactamente 9 productos (3x3)
                    'orderby' => 'rand', // Orden aleatorio para mostrar productos variados
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

                if ($products_query->have_posts()) {
                    // Abrir la cuadrícula con límite de 3 columnas
                    echo '<ul class="products productos-grid limited-grid">';
                    
                    $product_count = 0;
                    while ($products_query->have_posts()) {
                        $products_query->the_post();
                        
                        // Configurar la variable global $product
                        global $product;
                        $product = wc_get_product(get_the_ID());
                        
                        wc_get_template_part('content', 'product');
                        
                        $product_count++;
                        
                        // Salir después de 9 productos
                        if ($product_count >= 9) {
                            break;
                        }
                    }
                    
                    echo '</ul>';
                    
                    wp_reset_postdata();
                } else {
                    echo '<p class="no-products-found">' . esc_html__('No se encontraron productos.', 'wc-productos-template') . '</p>';
                }
                ?>
            </div>
        </main>
    </div>
</div>

<!-- Estilos inline para forzar 3x3 grid -->
<style>
.wc-productos-template .productos-grid.limited-grid,
.wc-productos-template .limited-grid.products {
    display: grid !important;
    grid-template-columns: repeat(3, 1fr) !important;
    gap: 20px !important;
    max-width: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
}

.wc-productos-template .limited-grid li.product {
    width: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
}

@media (max-width: 768px) {
    .wc-productos-template .productos-grid.limited-grid,
    .wc-productos-template .limited-grid.products {
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 15px !important;
    }
}

@media (max-width: 480px) {
    .wc-productos-template .productos-grid.limited-grid,
    .wc-productos-template .limited-grid.products {
        grid-template-columns: repeat(1, 1fr) !important;
        gap: 10px !important;
    }
}
