<?php
/**
 * Plantilla para cada producto en la cuadrícula
 * Versión corregida para evitar elementos huérfanos y títulos duplicados
 * Con padding adicional para mejor espaciado
 *
 * @package WC_Productos_Template
 */

// Ensure $product is defined and is a valid WooCommerce product
global $product;
$is_logged_in = is_user_logged_in();
if (!$product || !is_a($product, 'WC_Product')) {
    return; // No renderizar nada si no hay producto válido
}
?>
<li <?php wc_product_class('producto-card', $product); ?>>
    <div class="producto-interior" style="padding: 15px;">
        <?php
        // Imagen del producto con enlace
        echo '<div class="producto-imagen" style="margin-bottom: 20px;">';
        
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
        
        // Enlace a la imagen
        echo '<a href="' . esc_url(get_permalink()) . '" class="producto-imagen-link">';
        
        // Mostrar imagen principal
        if (has_post_thumbnail()) {
            the_post_thumbnail('woocommerce_thumbnail');
        } else {
            echo wc_placeholder_img();
        }
        
        echo '</a></div>';
        
        // Información del producto
        echo '<div class="producto-info" style="padding: 0 10px;">';
        
        // SKU o ID (movido arriba para destacar la referencia)
        $sku = $product->get_sku();
        if ($sku) {
            echo '<div class="producto-sku" style="margin-bottom: 10px;">';
            echo '<strong>' . esc_html__('REF:', 'wc-productos-template') . '</strong> ';
            echo esc_html($sku) . '</div>';
        }
        
        // Título con enlace
        echo '<h2 class="producto-titulo woocommerce-loop-product__title" style="margin-bottom: 15px;">';
        echo '<a href="' . esc_url(get_permalink()) . '">' . get_the_title() . '</a>';
        echo '</h2>';
        
        // Detalles del producto en formato de tabla para mejor organización
        echo '<div class="producto-detalles" style="margin-bottom: 20px;">';
        
        // Volumen (desde atributo o meta)
        $volumen = $product->get_attribute('pa_volumen');
        if (!$volumen) {
            $volumen = get_post_meta($product->get_id(), '_volumen_ml', true);
            if ($volumen) {
                echo '<div class="producto-volumen" style="margin-bottom: 8px;">';
                echo '<strong>' . esc_html__('Volumen:', 'wc-productos-template') . '</strong> ';
                echo esc_html($volumen) . ' ml</div>';
            }
        } else {
            echo '<div class="producto-volumen" style="margin-bottom: 8px;">';
            echo '<strong>' . esc_html__('Volumen:', 'wc-productos-template') . '</strong> ';
            echo esc_html($volumen) . '</div>';
        }
        
        // Grado (desde atributo)
        $grado = $product->get_attribute('pa_grado');
        if ($grado) {
            echo '<div class="producto-grado" style="margin-bottom: 8px;">';
            echo '<strong>' . esc_html__('Grado:', 'wc-productos-template') . '</strong> ';
            echo esc_html($grado) . '</div>';
        }
        
        echo '</div>'; // Fin detalles
        
        // Contenedor de precio y acción
        echo '<div class="producto-footer" style="padding-top: 15px; margin-top: 15px; border-top: 1px solid #f0f0f0;">';
        
    // Precio
if ($price_html = $product->get_price_html()) {
    if ($is_logged_in) {
        echo '<div class="producto-precio price" style="margin-bottom: 10px;">' . $price_html . '</div>';
    } else {
        echo '<div class="producto-precio price dpc-product-price" style="margin-bottom: 10px;">
              <a href="#" class="dpc-login-to-view" data-product-id="' . esc_attr($product->get_id()) . '">
                  ' . esc_html__('Ver Precio', 'wc-productos-template') . '
              </a>
              </div>';
    }
}
        
       // Botón de añadir al carrito (alrededor de la línea 100)
echo '<div class="producto-accion">';

if ($is_logged_in) {
    echo '<a href="' . esc_url($product->add_to_cart_url()) . '" 
           class="producto-boton button add_to_cart_button ' . ($product->is_purchasable() && $product->is_in_stock() ? 'ajax_add_to_cart' : '') . '"
           data-product_id="' . esc_attr($product->get_id()) . '"
           data-product_sku="' . esc_attr($product->get_sku()) . '"
           aria-label="' . esc_attr__('Añadir al carrito', 'wc-productos-template') . '"
           style="padding: 10px 18px; width: 100%; text-align: center;">';
    echo esc_html($product->is_purchasable() && $product->is_in_stock() ? 
          __('Añadir al carrito', 'wc-productos-template') : 
          __('Leer más', 'wc-productos-template'));
    echo '</a>';
} else {
    echo '<a href="#" class="producto-boton dpc-login-to-view" 
          data-product_id="' . esc_attr($product->get_id()) . '"
          style="padding: 10px 18px; width: 100%; text-align: center;">';
    echo esc_html__('Ver detalles', 'wc-productos-template');
    echo '</a>';
}

echo '</div>'; // Fin acciones
        
        echo '</div>'; // Fin footer
        
        echo '</div>'; // Fin info
        ?>
    </div>
</li>
