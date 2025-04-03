<?php
/**
 * Plantilla para cada producto en la cuadrícula - VERSIÓN OPTIMIZADA
 * Con elementos mejorados para UX/UI
 *
 * @package WC_Productos_Template
 */

// Ensure $product is defined and is a valid WooCommerce product
global $product;
if (!$product || !is_a($product, 'WC_Product')) {
    return; // No renderizar nada si no hay producto válido
}
?>
<li <?php wc_product_class('producto-card', $product); ?>>
    <div class="producto-interior">
        <?php
        // Badges en contenedor separado para mejor posicionamiento
        echo '<div class="producto-badges">';
        
        // Badge de estado de stock
        if ($product->is_in_stock()) {
            echo '<span class="producto-badge badge-stock">' . 
                esc_html__('En stock', 'wc-productos-template') . '</span>';
        } else {
            echo '<span class="producto-badge badge-out-stock">' . 
                esc_html__('Agotado', 'wc-productos-template') . '</span>';
        }
        
        // Verificar si es un producto peligroso
        $is_dangerous = get_post_meta($product->get_id(), '_is_dangerous', true) === 'yes' || 
                    has_term('peligroso', 'product_tag', $product->get_id());
        
        if ($is_dangerous) {
            echo '<span class="producto-badge badge-danger">' . 
                esc_html__('Peligroso', 'wc-productos-template') . '</span>';
        }
        
        echo '</div>'; // Fin de badges
        
        // Imagen del producto con enlace y vista rápida
        echo '<div class="producto-imagen">';
        echo '<a href="' . esc_url(get_permalink()) . '" class="producto-imagen-link">';
        
        // Mostrar imagen principal
        if (has_post_thumbnail()) {
            the_post_thumbnail('woocommerce_thumbnail');
        } else {
            echo wc_placeholder_img();
        }
        
        echo '</a>';
        
        // Añadir vista rápida
        echo '<a href="' . esc_url(get_permalink()) . '" class="producto-vista-rapida">' . 
            esc_html__('Vista rápida', 'wc-productos-template') . '</a>';
        
        echo '</div>'; // Fin producto-imagen
        
        // Información del producto
        echo '<div class="producto-info">';
        
        // Meta información (SKU y categoría)
        echo '<div class="producto-meta">';
        
        // SKU o ID
        $sku = $product->get_sku();
        if ($sku) {
            echo '<div class="producto-sku">';
            echo '<strong>' . esc_html__('REF:', 'wc-productos-template') . '</strong> ';
            echo esc_html($sku) . '</div>';
        }
        
        // Categoría principal
        $categories = wc_get_product_category_list($product->get_id(), ', ', '<span class="producto-categoria">', '</span>');
        if ($categories) {
            echo $categories;
        }
        
        echo '</div>'; // Fin producto-meta
        
        // Título con enlace
        echo '<h2 class="producto-titulo woocommerce-loop-product__title">';
        echo '<a href="' . esc_url(get_permalink()) . '">' . get_the_title() . '</a>';
        echo '</h2>';
        
        // Detalles del producto
        echo '<div class="producto-detalles">';
        
        // Volumen (desde atributo o meta)
        $volumen = $product->get_attribute('pa_volumen');
        if (!$volumen) {
            $volumen = get_post_meta($product->get_id(), '_volumen_ml', true);
            if ($volumen) {
                echo '<div class="producto-volumen">';
                echo '<strong>' . esc_html__('Volumen:', 'wc-productos-template') . '</strong> ';
                echo esc_html($volumen) . ' ml</div>';
            }
        } else {
            echo '<div class="producto-volumen">';
            echo '<strong>' . esc_html__('Volumen:', 'wc-productos-template') . '</strong> ';
            echo esc_html($volumen) . '</div>';
        }
        
        // Grado (desde atributo)
        $grado = $product->get_attribute('pa_grado');
        if ($grado) {
            echo '<div class="producto-grado">';
            echo '<strong>' . esc_html__('Grado:', 'wc-productos-template') . '</strong> ';
            echo esc_html($grado) . '</div>';
        }
        
        echo '</div>'; // Fin detalles
        
        // Footer con precio y botón de acción
        echo '<div class="producto-footer">';
        
        // Precio
        if ($price_html = $product->get_price_html()) {
            echo '<div class="producto-precio price">' . $price_html . '</div>';
        }
        
        // Botón de añadir al carrito con icono
        echo '<div class="producto-accion">';
        
        echo '<a href="' . esc_url($product->add_to_cart_url()) . '" 
               class="producto-boton button add_to_cart_button ' . ($product->is_purchasable() && $product->is_in_stock() ? 'ajax_add_to_cart' : '') . '"
               data-product_id="' . esc_attr($product->get_id()) . '"
               data-product_sku="' . esc_attr($product->get_sku()) . '"
               aria-label="' . esc_attr__('Añadir al carrito', 'wc-productos-template') . '">';
               
        // Icono de carrito
        echo '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="9" cy="21" r="1"></circle>
                <circle cx="20" cy="21" r="1"></circle>
                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
              </svg>';
              
        echo esc_html($product->is_purchasable() && $product->is_in_stock() ? 
              __('Añadir al carrito', 'wc-productos-template') : 
              __('Leer más', 'wc-productos-template'));
        echo '</a>';
        
        echo '</div>'; // Fin acciones
        
        echo '</div>'; // Fin footer
        
        echo '</div>'; // Fin info
        ?>
    </div>
</li>
