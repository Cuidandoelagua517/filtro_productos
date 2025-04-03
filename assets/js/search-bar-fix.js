/**
 * Script optimizado para corregir problemas con la barra de búsqueda
 * Modificado para solo afectar elementos dentro de .wc-productos-template
 * 
 * @package WC_Productos_Template
 */

jQuery(document).ready(function($) {
    /**
     * Verificar y reparar la barra de búsqueda si es necesario
     * pero solo dentro del scope de .wc-productos-template
     */
    function fixSearchBar() {
        // Verificar si estamos en una página con el template de productos
        if (!$('.wc-productos-template').length) {
            return;
        }
        
        console.log('Verificando barra de búsqueda dentro del template...');
        
        // Verificar si existe el header de productos dentro de nuestro contenedor
        var $header = $('.wc-productos-template .productos-header');
        if ($header.length === 0 || $header.css('display') === 'none' || $header.css('visibility') === 'hidden') {
            console.log('Header no encontrado o no visible, recreando...');
            
            // Recrear el header al inicio del contenedor
            $('.wc-productos-template').prepend(
                '<div class="productos-header search-fix-header">' +
                '<h1>' + ($('.woocommerce-products-header__title').text() || 'Productos') + '</h1>' +
                '<div class="productos-search search-fix-bar">' +
                '<form role="search" method="get" class="productos-search-form search-fix-form" action="javascript:void(0);">' +
                '<input type="text" id="productos-search-input" name="s" class="search-fix-input" placeholder="Buscar por nombre, referencia o características..." value="" />' +
                '<button type="submit" class="productos-search-button search-fix-button" aria-label="Buscar">' +
                '<i class="fas fa-search" aria-hidden="true"></i>' +
                '</button>' +
                '</form>' +
                '</div>' +
                '</div>'
            );
            return;
        }
        
        // Verificar si existe la barra de búsqueda dentro de nuestro header
        var $searchBar = $header.find('.productos-search');
        if ($searchBar.length === 0 || $searchBar.css('display') === 'none' || $searchBar.css('visibility') === 'hidden') {
            console.log('Barra de búsqueda no encontrada o no visible, recreando...');
            
            // Recrear la barra de búsqueda dentro del header
            $header.append(
                '<div class="productos-search search-fix-bar">' +
                '<form role="search" method="get" class="productos-search-form search-fix-form" action="javascript:void(0);">' +
                '<input type="text" id="productos-search-input" name="s" class="search-fix-input" placeholder="Buscar por nombre, referencia o características..." value="" />' +
                '<button type="submit" class="productos-search-button search-fix-button" aria-label="Buscar">' +
                '<i class="fas fa-search" aria-hidden="true"></i>' +
                '</button>' +
                '</form>' +
                '</div>'
            );
            return;
        }
        
        // Asegurarse que los elementos internos de la barra de búsqueda estén visibles
        var $form = $searchBar.find('form, .productos-search-form');
        var $input = $searchBar.find('input[type="text"]');
        var $button = $searchBar.find('button, .productos-search-button');
        
        if ($form.length === 0 || $input.length === 0 || $button.length === 0 || 
            $form.css('display') === 'none' || $input.css('display') === 'none' || $button.css('display') === 'none') {
            console.log('Elementos de la barra de búsqueda no encontrados o no visibles, recreando...');
            
            // Reemplazar el contenido completo de la barra de búsqueda
            $searchBar.html(
                '<form role="search" method="get" class="productos-search-form search-fix-form" action="javascript:void(0);">' +
                '<input type="text" id="productos-search-input" name="s" class="search-fix-input" placeholder="Buscar por nombre, referencia o características..." value="" />' +
                '<button type="submit" class="productos-search-button search-fix-button" aria-label="Buscar">' +
                '<i class="fas fa-search" aria-hidden="true"></i>' +
                '</button>' +
                '</form>'
            );
        }
        
        // Asegurarse de que Font Awesome esté cargado para el icono de búsqueda
        if (!$('link[href*="font-awesome"]').length) {
            console.log('Font Awesome no detectado, cargando...');
            $('head').append('<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">');
        }
        
        // Asegurarse que el botón tenga el evento de clic
        $button.off('click').on('click', function(e) {
            e.preventDefault();
            
            // Intentar usar la función global filterProducts primero
            if (typeof window.filterProducts === 'function') {
                window.filterProducts(1);
            }
        });
        
        // Asegurarse que el formulario tenga el evento submit
        $form.off('submit').on('submit', function(e) {
            e.preventDefault();
            
            // Intentar usar la función global filterProducts primero
            if (typeof window.filterProducts === 'function') {
                window.filterProducts(1);
            }
        });
    }
    
    /**
     * Corregir la estructura del header y eliminar elementos duplicados
     * pero solo dentro del scope de .wc-productos-template
     */
    function fixHeaderStructure() {
        // Verificar si hay headers duplicados dentro de nuestro contenedor
        if ($('.wc-productos-template .productos-header').length > 1) {
            // Eliminar todos excepto el primero
            $('.wc-productos-template .productos-header:gt(0)').remove();
        }
        
        // Eliminar headers mal posicionados
        $('.wc-productos-template .productos-grid > .productos-header, .wc-productos-template ul.products > .productos-header').each(function() {
            var $header = $(this);
            var $container = $('.wc-productos-template').first();
            
            // Mover al inicio del contenedor si está en lugar incorrecto
            if ($container.length > 0) {
                $header.prependTo($container);
            }
        });
        
        // Eliminar cualquier texto de "Productos" que esté fuera del header pero dentro de nuestro scope
        $('.wc-productos-template .productos-grid, .wc-productos-template ul.products').contents().each(function() {
            if (this.nodeType === 3 && this.nodeValue.trim() === 'Productos') {
                $(this).remove();
            }
        });
    }
    
    /**
     * Corregir la estructura general de la cuadrícula
     * pero solo dentro del scope de .wc-productos-template
     */
    function fixGridStructure() {
        // Verificar si hay cuadrículas duplicadas dentro de nuestro contenedor
        if ($('.wc-productos-template .productos-grid, .wc-productos-template ul.products').length > 1) {
            // Si hay múltiples cuadrículas, mantener solo la primera que tenga productos
            var $grids = $('.wc-productos-template .productos-grid, .wc-productos-template ul.products');
            var $validGrid = null;
            
            $grids.each(function() {
                if ($(this).find('li.product').length > 0 && !$validGrid) {
                    $validGrid = $(this);
                } else if ($(this) !== $validGrid) {
                    $(this).remove();
                }
            });
        }
        
        // Forzar estilos de cuadrícula a 3 columnas
        $('.wc-productos-template .productos-grid, .wc-productos-template ul.products').addClass('three-column-grid');
        
        // Ocultar productos después del noveno
        $('.wc-productos-template .productos-grid li.product:nth-child(n+10), .wc-productos-template ul.products li.product:nth-child(n+10)').addClass('hide-product');
        
        // Adaptar a móviles pero manteniendo máximo 3 columnas
        if (window.innerWidth <= 480) {
            $('body').addClass('screen-small').removeClass('screen-medium screen-large');
        } else if (window.innerWidth <= 768) {
            $('body').addClass('screen-medium').removeClass('screen-small screen-large');
        } else {
            $('body').addClass('screen-large').removeClass('screen-small screen-medium');
        }
    }
    
    // Ejecutar las funciones solo si estamos en una página con el template
    if ($('.wc-productos-template').length) {
        fixSearchBar();
        fixHeaderStructure();
        fixGridStructure();
        
        // Volver a ejecutar al cambiar el tamaño de la ventana
        $(window).on('resize', function() {
            fixGridStructure();
        });
    }
});
