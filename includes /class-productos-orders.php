<?php
/**
 * Clase para el manejo de pedidos compatible con HPOS
 *
 * Esta clase proporciona métodos para interactuar con pedidos de WooCommerce
 * de manera compatible con HPOS (High-Performance Order Storage).
 *
 * @package WC_Productos_Template
 */

if (!class_exists('WC_Productos_Template_Orders')) {

    class WC_Productos_Template_Orders {
        
        /**
         * Constructor
         */
        public function __construct() {
            // Registrar hooks para interactuar con pedidos
            add_action('woocommerce_checkout_create_order', array($this, 'add_custom_order_meta'), 10, 2);
            add_action('woocommerce_admin_order_data_after_order_details', array($this, 'display_custom_order_meta'), 10, 1);
        }
        
        /**
         * Agregar metadata personalizada al pedido
         * Compatible con HPOS
         *
         * @param WC_Order $order Objeto del pedido
         * @param array $data Datos del pedido del checkout
         */
        public function add_custom_order_meta($order, $data) {
            // Verificar que el carrito existe y está disponible
            if (!function_exists('WC') || !WC()->cart) {
                return;
            }
            
            // Guardar información sobre productos peligrosos en el pedido
            $cart = WC()->cart->get_cart();
            $has_dangerous_products = false;
            
            foreach ($cart as $cart_item) {
                $product_id = $cart_item['product_id'];
                $is_dangerous = get_post_meta($product_id, '_is_dangerous', true);
                
                if ($is_dangerous === 'yes') {
                    $has_dangerous_products = true;
                    break;
                }
            }
            
            // Guardar el metadata usando métodos compatibles con HPOS
            $order->update_meta_data('_has_dangerous_products', $has_dangerous_products ? 'yes' : 'no');
            $order->save();
        }
        
        /**
         * Mostrar metadata personalizada en la vista de admin
         * Compatible con HPOS
         *
         * @param WC_Order $order Objeto del pedido
         */
        public function display_custom_order_meta($order) {
            // Usar métodos compatibles con HPOS para obtener metadata
            $has_dangerous = $order->get_meta('_has_dangerous_products');
            
            if ($has_dangerous === 'yes') {
                echo '<div class="wc-productos-order-alert">';
                echo '<mark class="order-status tips dangerous" data-tip="' . 
                     esc_attr__('Este pedido contiene productos peligrosos', 'wc-productos-template') . '">';
                echo '<span class="dangerous-icon"></span> ' . 
                     esc_html__('¡Atención! Este pedido contiene productos peligrosos', 'wc-productos-template');
                echo '</mark>';
                echo '</div>';
            }
        }
        
        /**
         * Obtiene un pedido por ID de manera compatible con HPOS
         *
         * @param int $order_id ID del pedido
         * @return WC_Order|false Objeto del pedido o false si no existe
         */
        public static function get_order($order_id) {
            try {
                // Usar wc_get_order en lugar de get_post para compatibilidad con HPOS
                $order = wc_get_order($order_id);
                return $order;
            } catch (Exception $e) {
                error_log('Error al obtener pedido: ' . $e->getMessage());
                return false;
            }
        }
        
        /**
         * Actualiza el estado de un pedido de manera compatible con HPOS
         *
         * @param int $order_id ID del pedido
         * @param string $status Nuevo estado
         * @return bool Éxito o fracaso
         */
        public static function update_order_status($order_id, $status) {
            try {
                $order = self::get_order($order_id);
                
                if (!$order) {
                    return false;
                }
                
                // Usar métodos de la API de WC_Order en lugar de actualizar el post directamente
                $order->update_status($status);
                return true;
            } catch (Exception $e) {
                error_log('Error al actualizar estado de pedido: ' . $e->getMessage());
                return false;
            }
        }
        
        /**
         * Obtiene órdenes según criterios de manera compatible con HPOS
         *
         * @param array $args Argumentos para la consulta
         * @return array Lista de pedidos
         */
        public static function get_orders($args = array()) {
            // Valores predeterminados para los argumentos
            $default_args = array(
                'limit' => 10,
                'status' => array('wc-processing', 'wc-completed'),
                'orderby' => 'date',
                'order' => 'DESC',
            );
            
            $args = wp_parse_args($args, $default_args);
            
            try {
                // Usar wc_get_orders en lugar de WP_Query para compatibilidad con HPOS
                $orders = wc_get_orders($args);
                return $orders;
            } catch (Exception $e) {
                error_log('Error al obtener pedidos: ' . $e->getMessage());
                return array();
            }
        }
        
        /**
         * Obtiene el total de un campo específico para pedidos
         * 
         * @param string $field Campo a sumar (por ejemplo, total)
         * @param array $args Argumentos para filtrar pedidos
         * @return float Total calculado
         */
        public static function get_orders_total($field = 'total', $args = array()) {
            $orders = self::get_orders($args);
            $total = 0;
            
            foreach ($orders as $order) {
                // Usar los getters adecuados según el campo
                switch ($field) {
                    case 'total':
                        $total += floatval($order->get_total());
                        break;
                    case 'subtotal':
                        $total += floatval($order->get_subtotal());
                        break;
                    case 'tax':
                        $total += floatval($order->get_total_tax());
                        break;
                    case 'shipping':
                        $total += floatval($order->get_shipping_total());
                        break;
                    default:
                        $total += floatval($order->get_total());
                }
            }
            
            return $total;
        }
    }
    
    // Inicializar la clase
    new WC_Productos_Template_Orders();
}
