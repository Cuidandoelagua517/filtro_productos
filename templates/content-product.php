<?php
/**
 * Template para cada producto individual
 * 
 * @package WC_Productos_Template
 */

global $product;

// Asegurarse de que estamos trabajando con un producto
if (!$product || !($product instanceof WC_Product)) {
    return;
}
?>
<li <?php wc_product_class('producto-card', $product); ?>>
    <div class="producto-interior">
        <?php
        // Imagen del producto con enlace
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
        echo '<div class="producto-info">';
        
        // Título con enlace
        echo '<h2 class="producto-titulo">';
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
        
        // SKU o ID
        $sku = $product->get_sku();
        if ($sku) {
            echo '<div class="producto-sku">';
            echo '<strong>' . esc_html__('REF:', 'wc-productos-template') . '</strong> ';
            echo esc_html($sku) . '</div>';
        }
        
        // Grado (desde atributo)
        $grado = $product->get_attribute('pa_grado');
        if ($grado) {
            echo '<div class="producto-grado">';
            echo '<strong>' . esc_html__('Grado:', 'wc-productos-template') . '</strong> ';
            echo esc_html($grado) . '</div>';
        }
        
        echo '</div>'; // Fin detalles
        
        // Precio
        if ($price_html = $product->get_price_html()) {
            echo '<div class="producto-precio">' . $price_html . '</div>';
        }
        
        // Botón de añadir al carrito
        echo '<div class="producto-accion">';
        
        woocommerce_template_loop_add_to_cart(array(
            'class' => 'producto-boton'
        ));
        
        echo '</div>'; // Fin acciones
        
        echo '</div>'; // Fin info
        ?>
    </div>
</li>
