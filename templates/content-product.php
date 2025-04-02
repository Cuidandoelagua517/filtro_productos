<?php
/**
 * Template para cada producto individual
 * 
 * @package WC_Productos_Template
 */

global $product;

// Asegurarse de que estamos trabajando con un producto
if (!$product || !$product instanceof WC_Product) {
    return;
}
?>
<div class="producto-card" id="producto-<?php echo esc_attr($product->get_id()); ?>">
    <?php
    // Imagen del producto
    echo '<div class="producto-imagen">';
    
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
    
    // Mostrar imagen principal
    if (has_post_thumbnail()) {
        the_post_thumbnail('woocommerce_thumbnail');
    } else {
        echo wc_placeholder_img();
    }
    
    echo '</div>';
    
    // Información del producto
    echo '<h3 class="producto-titulo">';
    echo '<a href="' . esc_url(get_permalink()) . '">' . get_the_title() . '</a>';
    echo '</h3>';
    
    // Detalles del producto
    echo '<div class="producto-detalles">';
    
    // SKU o ID
    $sku = $product->get_sku();
    if ($sku) {
        echo '<span class="producto-sku">' . esc_html__('REF: ', 'wc-productos-template') . esc_html($sku) . '</span>';
    }
    
    // Volumen (desde atributo o meta)
    $volumen = $product->get_attribute('pa_volumen');
    if (!$volumen) {
        $volumen = get_post_meta($product->get_id(), '_volumen_ml', true);
        if ($volumen) {
            $volumen .= ' ml';
        }
    }
    
    if ($volumen) {
        echo '<span class="producto-volumen">' . esc_html($volumen) . '</span>';
    }
    
    // Grado (desde atributo)
    $grado = $product->get_attribute('pa_grado');
    if ($grado) {
        echo '<span class="producto-grado">' . 
             esc_html__('Grado: ', 'wc-productos-template') . esc_html($grado) . '</span>';
    }
    
    echo '</div>';
    
    // Precio
    echo '<div class="producto-precio">' . $product->get_price_html() . '</div>';
    
    // Botón de añadir al carrito
    echo '<button class="producto-boton" data-product-id="' . esc_attr($product->get_id()) . '">';
    
    if ($product->is_in_stock()) {
        echo esc_html__('Agregar al carrito', 'wc-productos-template');
    } else {
        echo esc_html__('Ver producto', 'wc-productos-template');
    }
    
    echo '</button>';
    ?>
</div>
