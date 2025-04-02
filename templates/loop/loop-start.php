<?php
/**
 * Product Loop Start
 *
 * Este template reemplaza el original de WooCommerce para asegurar que nuestra cuadrícula de productos
 * funcione correctamente.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<ul class="products productos-grid columns-<?php echo esc_attr( wc_get_loop_prop( 'columns' ) ); ?>">
