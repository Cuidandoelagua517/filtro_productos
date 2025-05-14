<?php
/**
 * Clase Principal del Plugin
 * 
 * Esta clase es responsable de inicializar todos los módulos del plugin.
 * 
 * @package WC_Productos_Template
 */

class WC_Productos_Template_Plugin {

    /**
     * El cargador que es responsable de mantener y registrar todos los hooks del plugin.
     *
     * @var WC_Productos_Template_Loader $loader
     */
    protected $loader;

    /**
     * Inicializar el plugin y cargar dependencias.
     */
    public function __construct() {
        $this->loader = new WC_Productos_Template_Loader();
        $this->load_modules();
    }

    /**
     * Cargar todos los módulos del plugin.
     */
    private function load_modules() {
        // Cargar los módulos del plugin
        $module_files = array(
            'WC_Productos_Template_Core'        => WC_PRODUCTOS_TEMPLATE_MODULES_DIR . 'class-core.php',
            'WC_Productos_Template_Templates'   => WC_PRODUCTOS_TEMPLATE_MODULES_DIR . 'class-templates.php',
            'WC_Productos_Template_Assets'      => WC_PRODUCTOS_TEMPLATE_MODULES_DIR . 'class-assets.php',
            'WC_Productos_Template_AJAX'        => WC_PRODUCTOS_TEMPLATE_MODULES_DIR . 'class-ajax.php',
            'WC_Productos_Template_Products'    => WC_PRODUCTOS_TEMPLATE_MODULES_DIR . 'class-products.php',
            'WC_Productos_Template_Filtering'   => WC_PRODUCTOS_TEMPLATE_MODULES_DIR . 'class-filtering.php',
            'WC_Productos_Template_Search'      => WC_PRODUCTOS_TEMPLATE_MODULES_DIR . 'class-search.php',
            'WC_Productos_Template_Login'       => WC_PRODUCTOS_TEMPLATE_MODULES_DIR . 'class-login.php',
        );
        
        foreach ($module_files as $class_name => $file_path) {
            $this->loader->register_module($class_name, $file_path);
        }
    }

    /**
     * Ejecutar el cargador para registrar todos los hooks con WordPress.
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * Obtener una referencia a la instancia del cargador.
     *
     * @return WC_Productos_Template_Loader
     */
    public function get_loader() {
        return $this->loader;
    }
}
