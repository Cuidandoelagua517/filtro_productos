<?php
/**
 * Se ejecuta durante la desactivación del plugin.
 *
 * @package WC_Productos_Template
 */

class WC_Productos_Template_Deactivator {

    /**
     * Desactivar el plugin.
     */
    public static function deactivate() {
        // Limpiar caché transients
        self::clear_transients();
        
        // Eliminar opciones de programación
        wp_clear_scheduled_hook('wc_productos_template_refresh_cache');
    }
    
    /**
     * Limpiar caché transients
     */
    private static function clear_transients() {
        global $wpdb;
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%wc_productos_template_cache%'");
    }
}
