<?php
/**
 * Añade un campo metabox para el volumen y opciones de peligrosidad
 */
if (!class_exists('WC_Productos_Template_Metabox')) {

    class WC_Productos_Template_Metabox {
        
        /**
         * Constructor
         */
        public function __construct() {
            add_action('add_meta_boxes', array($this, 'add_product_metabox'));
            add_action('save_post_product', array($this, 'save_product_metabox'));
            add_action('woocommerce_product_options_general_product_data', array($this, 'add_volume_field'));
            add_action('woocommerce_process_product_meta', array($this, 'save_volume_field'));
        }
        
        /**
         * Añade metabox para opciones adicionales
         */
        public function add_product_metabox() {
            add_meta_box(
                'wc_productos_options',
                __('Opciones de Producto', 'wc-productos-template'),
                array($this, 'render_product_metabox'),
                'product',
                'side',
                'default'
            );
        }
        
        /**
         * Renderiza el metabox
         */
        public function render_product_metabox($post) {
            // Nonce para seguridad
            wp_nonce_field('wc_productos_template_metabox', 'wc_productos_template_nonce');
            
            // Obtener valores guardados
            $is_dangerous = get_post_meta($post->ID, '_is_dangerous', true);
            
            ?>
            <p>
                <label for="wc_producto_is_dangerous">
                    <input type="checkbox" id="wc_producto_is_dangerous" name="wc_producto_is_dangerous" 
                        value="yes" <?php checked($is_dangerous, 'yes'); ?> />
                    <?php esc_html_e('Producto Peligroso', 'wc-productos-template'); ?>
                </label>
            </p>
            <p class="description">
                <?php esc_html_e('Marcar si el producto requiere advertencias especiales', 'wc-productos-template'); ?>
            </p>
            <?php
        }
        
        /**
         * Guarda los datos del metabox
         */
        public function save_product_metabox($post_id) {
            // Verificar nonce
            if (!isset($_POST['wc_productos_template_nonce']) || 
                !wp_verify_nonce($_POST['wc_productos_template_nonce'], 'wc_productos_template_metabox')) {
                return;
            }
            
            // Verificar autoguardado
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                return;
            }
            
            // Verificar permisos
            if (!current_user_can('edit_post', $post_id)) {
                return;
            }
            
            // Guardar campo de producto peligroso
            $is_dangerous = isset($_POST['wc_producto_is_dangerous']) ? 'yes' : 'no';
            update_post_meta($post_id, '_is_dangerous', $is_dangerous);
            
            // Si es peligroso, también añadir la etiqueta 'peligroso'
            if ($is_dangerous === 'yes') {
                wp_set_object_terms($post_id, 'peligroso', 'product_tag', true);
            } else {
                // Eliminar la etiqueta si ya no es peligroso
                $terms = wp_get_object_terms($post_id, 'product_tag', array('fields' => 'names'));
                if (is_array($terms) && !is_wp_error($terms)) {
                    $key = array_search('peligroso', $terms);
                    if ($key !== false) {
                        unset($terms[$key]);
                        wp_set_object_terms($post_id, $terms, 'product_tag');
                    }
                }
            }
        }
        
        /**
         * Añadir campo de volumen en la pestaña general de WooCommerce
         */
        public function add_volume_field() {
            global $post;
            
            echo '<div class="options_group">';
            
            // Campo de volumen
            woocommerce_wp_text_input(
                array(
                    'id' => '_volumen_ml',
                    'label' => __('Volumen (ml)', 'wc-productos-template'),
                    'desc_tip' => true,
                    'description' => __('Ingrese el volumen del producto en mililitros.', 'wc-productos-template'),
                    'type' => 'number',
                    'custom_attributes' => array(
                        'step' => '1',
                        'min' => '1'
                    )
                )
            );
            
            echo '</div>';
        }
        
        /**
         * Guardar campo de volumen
         */
        public function save_volume_field($post_id) {
            $volumen_ml = isset($_POST['_volumen_ml']) ? sanitize_text_field($_POST['_volumen_ml']) : '';
            update_post_meta($post_id, '_volumen_ml', $volumen_ml);
        }
    }
    
    // Inicializar la clase
    new WC_Productos_Template_Metabox();
}
