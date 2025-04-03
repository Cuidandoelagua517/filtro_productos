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
<ul class="products productos-grid wc-productos-template columns-<?php echo esc_attr(wc_get_loop_prop('columns')); ?>" 
    style="display:grid !important; 
           grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)) !important; 
           gap: 20px !important;
           width: 100% !important;
           margin: 0 !important;
           padding: 0 !important;
           list-style: none !important;
           float: none !important;
           clear: both !important;">
