<?php
/**
 * Product Loop Start
 */
if (!defined('ABSPATH')) {
    exit;
}
?>
<ul class="products productos-grid wc-productos-template columns-<?php echo esc_attr(wc_get_loop_prop('columns')); ?>" style="display:grid !important; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)) !important; gap: 20px !important;">
