/**
 * Script para corregir problemas con la barra de búsqueda y header
 * 
 * Este script garantiza que la barra de búsqueda siempre aparezca
 * en la UI, incluso cuando haya conflictos de CSS.
 *
 * @package WC_Productos_Template
 */

jQuery(document).ready(function($) {
    /**
     * Verificar y reparar la barra de búsqueda si es necesario
     */
    function fixSearchBar() {
        // Verificar si estamos en una página con el template de productos
        if (!$('.wc-productos-template').length) {
            return;
        }
        
        console.log('Verificando barra de búsqueda...');
        
        // Verificar si existe el header de productos
        var $header = $('.productos-header');
        if ($header.length === 0 || $header.css('display') === 'none' || $header.css('visibility') === 'hidden') {
            console.log('Header no encontrado o no visible, recreando...');
            
            // Recrear el header al inicio del contenedor
            $('.wc-productos-template').prepend(
                '<div class="productos-header" style="display:flex !important; width:100% !important; justify-content:space-between !important; align-items:center !important; margin-bottom:25px !important; visibility:visible !important; opacity:1 !important; z-index:10 !important;">' +
                '<h1>' + ($('.woocommerce-products-header__title').text() || 'Productos') + '</h1>' +
                '<div class="productos-search" style="position:relative !important; width:300px !important; display:block !important; visibility:visible !important; opacity:1 !important; margin:0 !important;">' +
                '<form role="search" method="get" class="productos-search-form" action="javascript:void(0);">' +
                '<input type="text" id="productos-search-input" name="s" placeholder="Buscar por nombre, referencia o características..." value="" style="width:100% !important; display:block !important; padding:10px 40px 10px 15px !important; border:1px solid #ddd !important; border-radius:4px !important; visibility:visible !important; opacity:1 !important;" />' +
                '<button type="submit" class="productos-search-button" aria-label="Buscar" style="position:absolute !important; right:0 !important; top:0 !important; height:100% !important; width:40px !important; background-color:#0056b3 !important; color:white !important; border:none !important; border-radius:0 4px 4px 0 !important; cursor:pointer !important; display:block !important; visibility:visible !important; opacity:1 !important;">' +
                '<i class="fas fa-search" aria-hidden="true"></i>' +
                '</button>' +
                '</form>' +
                '</div>' +
                '</div>'
            );
            return;
        }
        
        // Verificar si existe la barra de búsqueda
        var $searchBar = $('.productos-search');
        if ($searchBar.length === 0 || $searchBar.css('display') === 'none' || $searchBar.css('visibility') === 'hidden') {
            console.log('Barra de búsqueda no encontrada o no visible, recreando...');
            
            // Recrear la barra de búsqueda dentro del header
            $header.append(
                '<div class="productos-search" style="position:relative !important; width:300px !important; display:block !important; visibility:visible !important; opacity:1 !important; margin:0 !important;">' +
                '<form role="search" method="get" class="productos-search-form" action="javascript:void(0);">' +
                '<input type="text" id="productos-search-input" name="s" placeholder="Buscar por nombre, referencia o características..." value="" style="width:100% !important; display:block !important; padding:10px 40px 10px 15px !important; border:1px solid #ddd !important; border-radius:4px !important; visibility:visible !important; opacity:1 !important;" />' +
                '<button type="submit" class="productos-search-button" aria-label="Buscar" style="position:absolute !important; right:0 !important; top:0 !important; height:100% !important; width:40px !important; background-color:#0056b3 !important; color:white !important; border:none !important; border-radius:0 4px 4px 0 !important; cursor:pointer !important; display:block !important; visibility:visible !important; opacity:1 !important;">' +
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
                '<form role="search" method="get" class="productos-search-form" action="javascript:void(0);">' +
                '<input type="text" id="productos-search-input" name="s" placeholder="Buscar por nombre, referencia o características..." value="" style="width:100% !important; display:block !important; padding:10px 40px 10px 15px !important; border:1px solid #ddd !important; border-radius:4px !important; visibility:visible !important; opacity:1 !important;" />' +
                '<button type="submit" class="productos-search-button" aria-label="Buscar" style="position:absolute !important; right:0 !important; top:0 !important; height:100% !important; width:40px !important; background-color:#0056b3 !important; color:white !important; border:none !important; border-radius:0 4px 4px 0 !important; cursor:pointer !important; display:block !important; visibility:visible !important; opacity:1 !important;">' +
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
        
        // Forzar estilos críticos para la barra de búsqueda
        $header.css({
            'display': 'flex',
            'flex-wrap': 'wrap',
            'justify-content': 'space-between',
            'align-items': 'center',
            'margin-bottom': '25px',
            'width': '100%',
            'visibility': 'visible',
            'opacity': '1',
            'z-index': '50'
        });
        
        $searchBar.css({
            'position': 'relative',
            'width': '300px',
            'display': 'block',
            'visibility': 'visible',
            'opacity': '1',
            'margin': '0',
            'z-index': '1'
        });
        
        $form = $searchBar.find('form, .productos-search-form');
        $input = $searchBar.find('input[type="text"]');
        $button = $searchBar.find('button, .productos-search-button');
        
        $form.css({
            'display': 'flex',
            'width': '100%',
            'position': 'relative'
        });
        
        $input.css({
            'width': '100%',
            'display': 'block',
            'padding': '10px 40px 10px 15px',
            'border': '1px solid #ddd',
            'border-radius': '4px',
            'visibility': 'visible',
            'opacity': '1'
        });
        
        $button.css({
            'position': 'absolute',
            'right': '0',
            'top': '0',
            'height': '100%',
            'width': '40px',
            'background-color': '#0056b3',
            'color': 'white',
            'border': 'none',
            'border-radius': '0 4px 4px 0',
            'cursor': 'pointer',
            'display': 'block',
            'visibility': 'visible',
            'opacity': '1'
        });
        
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
     */
    function fixHeaderStructure() {
        // Verificar si hay headers duplicados
        if ($('.productos-header').length > 1) {
            // Eliminar todos excepto el primero
            $('.productos-header:gt(0)').remove();
        }
        
        // Eliminar headers mal posicionados
        $('.productos-grid > .productos-header, ul.products > .productos-header').each(function() {
            var $header = $(this);
            var $container = $('.productos-container, .wc-productos-template').first();
            
            // Mover al inicio del contenedor si está en lugar incorrecto
            if ($container.length > 0) {
                $header.prependTo($container);
            }
        });
        
        // Eliminar cualquier texto de "Productos" que esté fuera del header
        $('.productos-grid, ul.products').contents().each(function() {
            if (this.nodeType === 3 && this.nodeValue.trim() === 'Productos') {
                $(this).remove();
            }
        });
    }
    

    
    // Ejecutar todas las correcciones
    function runAllFixes() {
        fixHeaderStructure();
        fixSearchBar();
        fixGridStructure();
    }
    
    // Ejecutar los arreglos inmediatamente
    runAllFixes();
    
    // Ejecutar después de que la página esté completamente cargada
    $(window).on('load', runAllFixes);
    
    // Ejecutar después de cada solicitud AJAX completada
    $(document).ajaxComplete(function() {
        setTimeout(runAllFixes, 100);
    });
    
    // Ejecutar periódicamente durante los primeros segundos para mayor seguridad
    var fixInterval = setInterval(runAllFixes, 1000);
    setTimeout(function() {
        clearInterval(fixInterval);
    }, 5000);
    
    // También ejecutar cuando cambie el tamaño de la ventana
    $(window).on('resize', function() {
        setTimeout(runAllFixes, 100);
    });
});
