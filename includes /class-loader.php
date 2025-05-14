<?php
/**
 * Clase Loader del Plugin
 * 
 * Esta clase es responsable de registrar todos los hooks del plugin y cargar los módulos.
 * 
 * @package WC_Productos_Template
 */

class WC_Productos_Template_Loader {

    /**
     * Las acciones registradas con WordPress.
     *
     * @var array $actions
     */
    protected $actions;

    /**
     * Los filtros registrados con WordPress.
     *
     * @var array $filters
     */
    protected $filters;

    /**
     * Los shortcodes registrados con WordPress.
     *
     * @var array $shortcodes
     */
    protected $shortcodes;

    /**
     * Los módulos cargados del plugin.
     *
     * @var array $modules
     */
    protected $modules;

    /**
     * Inicializar la colección utilizada para mantener los hooks.
     */
    public function __construct() {
        $this->actions = array();
        $this->filters = array();
        $this->shortcodes = array();
        $this->modules = array();
    }

    /**
     * Añadir una nueva acción a la colección para ser registrada con WordPress.
     *
     * @param string $hook            El nombre del hook de WordPress al que estamos registrando.
     * @param object $component       La instancia del objeto en el que existe el método callback.
     * @param string $callback        El nombre del método de la clase $component.
     * @param int    $priority        Opcional. El priority. Por defecto es 10.
     * @param int    $accepted_args   Opcional. El número de args que acepta el callback. Por defecto es 1.
     */
    public function add_action($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->actions = $this->add($this->actions, $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * Añadir un nuevo filtro a la colección para ser registrado con WordPress.
     *
     * @param string $hook            El nombre del hook de WordPress al que estamos registrando.
     * @param object $component       La instancia del objeto en el que existe el método callback.
     * @param string $callback        El nombre del método de la clase $component.
     * @param int    $priority        Opcional. El priority. Por defecto es 10.
     * @param int    $accepted_args   Opcional. El número de args que acepta el callback. Por defecto es 1.
     */
    public function add_filter($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->filters = $this->add($this->filters, $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * Añadir un nuevo shortcode a la colección para ser registrado con WordPress.
     *
     * @param string $tag             El nombre del shortcode.
     * @param object $component       La instancia del objeto en el que existe el método callback.
     * @param string $callback        El nombre del método de la clase $component.
     */
    public function add_shortcode($tag, $component, $callback) {
        $this->shortcodes = $this->add($this->shortcodes, $tag, $component, $callback, 0, 0);
    }

    /**
     * Método utilidad que se utiliza para registrar los hooks en las colecciones.
     *
     * @param array  $hooks           Colección de hooks (actions/filters) a registrar con WordPress.
     * @param string $hook            El nombre del hook de WordPress al que estamos registrando.
     * @param object $component       La instancia del objeto en el que existe el método callback.
     * @param string $callback        El nombre del método de la clase $component.
     * @param int    $priority        El priority para el hook.
     * @param int    $accepted_args   El número de args que acepta el callback.
     * @return array                  Colección de hooks con el nuevo hook registrado.
     */
    private function add($hooks, $hook, $component, $callback, $priority, $accepted_args) {
        $hooks[] = array(
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args
        );

        return $hooks;
    }

    /**
     * Registrar los módulos con WordPress.
     *
     * @param string $module_name     El nombre de la clase del módulo.
     * @param string $file_path       La ruta al archivo del módulo.
     */
    public function register_module($module_name, $file_path) {
        if (file_exists($file_path)) {
            require_once $file_path;
            
            if (class_exists($module_name)) {
                $module = new $module_name($this);
                $this->modules[$module_name] = $module;
                
                // Inicializar el módulo si tiene un método init
                if (method_exists($module, 'init')) {
                    $module->init();
                }
                
                return $module;
            }
        }
        
        return false;
    }

    /**
     * Obtener un módulo registrado por su nombre de clase.
     *
     * @param string $module_name     El nombre de la clase del módulo.
     * @return object|false           La instancia del módulo o false si no existe.
     */
    public function get_module($module_name) {
        return isset($this->modules[$module_name]) ? $this->modules[$module_name] : false;
    }

    /**
     * Registrar los hooks con WordPress.
     */
    public function run() {
        // Registrar los filtros
        foreach ($this->filters as $hook) {
            add_filter(
                $hook['hook'],
                array($hook['component'], $hook['callback']),
                $hook['priority'],
                $hook['accepted_args']
            );
        }

        // Registrar las acciones
        foreach ($this->actions as $hook) {
            add_action(
                $hook['hook'],
                array($hook['component'], $hook['callback']),
                $hook['priority'],
                $hook['accepted_args']
            );
        }

        // Registrar los shortcodes
        foreach ($this->shortcodes as $hook) {
            add_shortcode(
                $hook['hook'],
                array($hook['component'], $hook['callback'])
            );
        }
    }
}
