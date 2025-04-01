?>
<div <?php wc_product_class('producto-card', $product); ?>>
    <?php
    global $product;
    
    // Imagen del producto
    echo '<div class="producto-imagen">';
    
    // Badge de estado
    if ($product->is_in_stock()) {
        echo '<span class="producto-badge badge-stock">' . esc_html__('En stock', 'wc-productos-template') . '</span>';
    }
    
    // Verificar si es un producto peligroso (por ejemplo, usando categorías o etiquetas)
    $is_dangerous = has_term('peligroso', 'product_tag', $product->get_id());
    if ($is_dangerous) {
        echo '<span class="producto-badge badge-danger">' . esc_html__('Peligroso', 'wc-productos-template') . '</span>';
    }
    
    // Mostrar imagen principal
    if (has_post_thumbnail()) {
        the_post_thumbnail('woocommerce_thumbnail');
    } else {
        echo wc_placeholder_img();
    }
    
    echo '</div>';
    
    // Información del producto
    echo '<h3 class="producto-titulo">' . get_the_title() . '</h3>';
    
    // Detalles del producto
    echo '<p class="producto-detalles">';
    
    // Volumen (asumiendo que hay un atributo o campo personalizado)
    $volumen = $product->get_attribute('pa_volumen') ?: get_post_meta($product->get_id(), '_volumen_ml', true);
    if ($volumen) {
        echo esc_html($volumen) . ' ml';
    }
    
    // Grado
    $grado = $product->get_attribute('pa_grado');
    if ($grado) {
        echo ' - ' . esc_html__('Grado', 'wc-productos-template') . ' ' . esc_html($grado);
    }
    
    echo '</p>';
    
    // Precio
    echo '<div class="producto-precio">' . $product->get_price_html() . '</div>';
    
    // Botón de añadir al carrito
    echo '<button class="producto-boton" data-product-id="' . esc_attr($product->get_id()) . '">' . 
        esc_html__('Agregar al carrito', 'wc-productos-template') . '</button>';
    ?>
</div>

<?php
