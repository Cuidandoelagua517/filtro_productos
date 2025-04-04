/**
 * SOLUCIÓN: Script optimizado para corregir problemas con la barra de búsqueda
 * sin interferir con la funcionalidad principal de la paginación y filtros.
 *
 * @package WC_Productos_Template
 */

jQuery(document).ready(function($) {
    /**
     * Verificar si estamos en una página con el template de productos
     */
    if (!$('.wc-productos-template').length) {
        return;
    }
    
    console.log('Search Bar Fix - Versión corregida');
    
    /**
     * Forzar disposición en cuadrícula de manera segura
     * Esta versión no interfiere con la paginación
     */
    function safeForceGrid() {
        // Aplicar estilos mediante clase en lugar de CSS directo
        $('ul.products, .productos-grid').addClass('force-grid three-column-grid');
        
        // Eliminar flotadores que pueden romper la cuadrícula
        $('ul.products::before, ul.products::after, .productos-grid::before, .productos-grid::after').css({
            'display': 'none',
            'content': 'none',
            'clear': 'none',
            'visibility': 'hidden'
        });
        
        // NO manipular directamente los elementos de paginación
    }
    
    /**
     * Verificar y reparar la barra de búsqueda si es necesario
     */
     function fixSearchBar() {
        // Verificar si estamos en una página con el template de productos
        if (!$('.wc-productos-template').length) {
            return;
        }
        
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
            
            // MODIFICADO: No enlazar eventos aquí sino dejar que productos-template.js lo haga
            // para evitar duplicaciones de eventos
            setTimeout(function() {
                // Señalar que la barra se ha recreado para que otros scripts la puedan detectar
                $(document).trigger('searchbar.fixed');
            }, 100);
            
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
            
            // MODIFICADO: No enlazar eventos aquí sino dejar que productos-template.js lo haga
            // Señalar que la barra se ha recreado para que otros scripts la puedan detectar
            setTimeout(function() {
                $(document).trigger('searchbar.fixed');
            }, 100);
        }
        
        // NUEVO: Restaurar término de búsqueda si existe en URL o en estado global
        var restoreSearchTerm = '';
        
        // Intentar obtener de URL
        var urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('s')) {
            restoreSearchTerm = urlParams.get('s');
        }
        // O del estado global si está disponible
        else if (window.currentFilters && window.currentFilters.search) {
            restoreSearchTerm = window.currentFilters.search;
        }
        
        // Restaurar valor en el campo de búsqueda
        if (restoreSearchTerm) {
            $('#productos-search-input, .productos-search input[name="s"]').val(restoreSearchTerm);
        }
    }
    
    /**
     * Forzar estructura de cuadrícula para productos sin interferir con la paginación
     */
   function fixGridStructure() {
        // Aplicar clases en lugar de manipular directamente
        $('.wc-productos-template .productos-grid, .wc-productos-template ul.products').addClass('three-column-grid');
        
        // Adaptar a móviles pero manteniendo máximo 3 columnas
        if (window.innerWidth <= 480) {
            $('body').addClass('screen-small').removeClass('screen-medium screen-large');
        } else if (window.innerWidth <= 768) {
            $('body').addClass('screen-medium').removeClass('screen-small screen-large');
        } else {
            $('body').addClass('screen-large').removeClass('screen-small screen-medium');
        }
    }

    
    /**
     * Corrección de elementos huérfanos sin eliminar elementos importantes
     */
    function fixOrphanedElements() {
        // Eliminar solo elementos realmente vacíos que causan problemas
        $('.wc-productos-template *:empty').not('input, textarea, select, button, img, br, hr, i').each(function() {
            // No eliminar contenedores importantes
            if (!$(this).hasClass('productos-pagination') && 
                !$(this).hasClass('page-dots') && 
                !$(this).parents('.productos-pagination').length) {
                $(this).remove();
            }
        });
        
        // Mover elementos que puedan haber quedado en lugar incorrecto
        $('.wc-productos-template .productos-grid > .productos-header, .wc-productos-template ul.products > .productos-header').each(function() {
            $(this).prependTo($('.wc-productos-template').first());
        });
    }
    
    // Ejecutar las funciones seguras inmediatamente
    safeForceGrid();
    fixSearchBar();
    fixGridStructure();
    fixOrphanedElements();
    
    // Volver a aplicar después de una pequeña demora para asegurar que todos los elementos se hayan cargado
    setTimeout(function() {
        safeForceGrid();
        fixSearchBar();
        fixGridStructure();
        fixOrphanedElements();
    }, 1000);
    
    // Volver a aplicar al cambiar el tamaño de la ventana
    $(window).on('resize', function() {
        safeForceGrid();
        fixGridStructure();
    });
    
    // Aplicar después de que las imágenes se hayan cargado
    $(window).on('load', function() {
        safeForceGrid();
        fixSearchBar();
        fixGridStructure();
        fixOrphanedElements();
    });
    
    // NUEVO: Escuchar evento de AJAX completado para volver a arreglar la barra de búsqueda
    $(document).ajaxComplete(function(event, xhr, settings) {
        if (settings.url && settings.url.indexOf('productos_filter') > -1) {
            setTimeout(function() {
                fixSearchBar();
                fixGridStructure();
                fixOrphanedElements();
                
                // Señalar que la estructura ha sido actualizada
                $(document).trigger('grid.fixed');
            }, 300);
        }
    });
    
    // Exponer función modificada para uso global que no interfiera con la paginación
    window.forceGridLayout = safeForceGrid;
});
