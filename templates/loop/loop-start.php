<?php
/**
 * Product Loop Start
 * Versión optimizada para forzar cuadrícula
 *
 * @package WC_Productos_Template
 */
if (!defined('ABSPATH')) {
    exit;
}
?>
<ul class="products productos-grid wc-productos-template columns-<?php echo esc_attr(wc_get_loop_prop('columns')); ?>">
