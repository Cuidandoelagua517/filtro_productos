/**
 * Script mejorado para corregir problemas con la barra de búsqueda
 * y forzar la visualización en cuadrícula
 *
 * @package WC_Productos_Template
 */

jQuery(document).ready(function($) {
    /**
     * Forzar la visualización en cuadrícula para listas de productos
     * Función principal que asegura que la cuadrícula se aplique correctamente
     */
    function forceGrid() {
        // Aplicar estilos directamente mediante jQuery para garantizar que se apliquen
        $('ul.products, .productos-grid').css({
            'display': 'grid',
            'grid-template-columns': 'repeat(auto-fill, minmax(220px, 1fr))',
            'gap': '20px',
            'width': '100%',
            'max-width': '100%',
            'margin': '0 0 30px 0',
            'padding': '0',
            'list-style': 'none',
            'float': 'none',
            'clear': 'both',
            'box-sizing': 'border-box'
        });
        
        // Eliminar flotadores que pueden romper la cuadrícula
        $('ul.products::before, ul.products::after, .productos-grid::before, .productos-grid::after').css({
            'display': 'none',
            'content': 'none',
            'clear': 'none',
            'visibility': 'hidden'
        });
        
        // Aplicar estilos a cada producto
        $('ul.products li.product, .productos-grid li.product').css({
            'width': '100%',
            'max-width': '100%',
            'margin': '0 0 20px 0',
            'padding': '0',
            'float': 'none',
            'clear': 'none',
            'box-sizing': 'border-box',
            'display': 'flex',
            'flex-direction': 'column',
            'height': '100%',
            'opacity': '1',
            'position': 'relative',
            'visibility': 'visible'
        });
        
        // Aplicar clases adicionales para asegurar la cuadrícula
        $('ul.products, .productos-grid').addClass('force-grid');
    }
    
    /**
     * Verificar y reparar la barra de búsqueda si es necesario
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
    }
    
    /**
     * Forzar estructura de cuadrícula para productos
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
    
    /**
     * Corrección de elementos huérfanos
     */
    function fixOrphanedElements() {
        // Eliminar elementos vacíos que pueden causar problemas
        $('.wc-productos-template *:empty').not('input, textarea, select, button, img, br, hr').remove();
        
        // Mover elementos que puedan haber quedado en lugar incorrecto
        $('.wc-productos-template .productos-grid > .productos-header, .wc-productos-template ul.products > .productos-header').each(function() {
            $(this).prependTo($('.wc-productos-template').first());
        });
    }
    
    // Ejecutar inmediatamente después de que el DOM esté listo
    forceGrid();
    
    // Si estamos en una página con el template personalizado
    if ($('.wc-productos-template').length) {
        fixSearchBar();
        fixGridStructure();
        fixOrphanedElements();
    }
    
    // Volver a aplicar después de una pequeña demora para asegurar que todos los elementos se hayan cargado
    setTimeout(function() {
        forceGrid();
        if ($('.wc-productos-template').length) {
            fixGridStructure();
            fixOrphanedElements();
        }
    }, 500);
    
    // Volver a aplicar al cambiar el tamaño de la ventana
    $(window).on('resize', function() {
        forceGrid();
        if ($('.wc-productos-template').length) {
            fixGridStructure();
        }
    });
    
    // Aplicar después de que las imágenes se hayan cargado
    $(window).on('load', function() {
        forceGrid();
        if ($('.wc-productos-template').length) {
            fixGridStructure();
            fixOrphanedElements();
        }
    });
    
    // Exponer función para uso global
    window.forceGridLayout = forceGrid;
});
