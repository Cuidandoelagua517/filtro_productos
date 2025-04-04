/**
 * SOLUCIÓN MEJORADA: Script optimizado para corregir problemas con la barra de búsqueda
 * y garantizar la correcta integración con la búsqueda de WooCommerce
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
    
    console.log('Search Bar Fix - Versión mejorada con búsqueda integrada');
    
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
    }
    
    /**
     * Verificar y reparar la barra de búsqueda si es necesario
     * VERSIÓN MEJORADA: Asegura que los eventos se conecten correctamente
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
                '<input type="text" id="productos-search-input" name="s" class="search-fix-input" placeholder="Buscar por nombre, referencia o características..." value="' + (typeof window.currentFilters !== 'undefined' && window.currentFilters.search ? window.currentFilters.search : '') + '" />' +
                '<button type="submit" class="productos-search-button search-fix-button" aria-label="Buscar">' +
                '<i class="fas fa-search" aria-hidden="true"></i>' +
                '</button>' +
                '</form>' +
                '</div>' +
                '</div>'
            );
            
            // MEJORADO: Conectar eventos de búsqueda inmediatamente
            connectSearchEvents();
            
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
                '<input type="text" id="productos-search-input" name="s" class="search-fix-input" placeholder="Buscar por nombre, referencia o características..." value="' + (typeof window.currentFilters !== 'undefined' && window.currentFilters.search ? window.currentFilters.search : '') + '" />' +
                '<button type="submit" class="productos-search-button search-fix-button" aria-label="Buscar">' +
                '<i class="fas fa-search" aria-hidden="true"></i>' +
                '</button>' +
                '</form>' +
                '</div>'
            );
            
            // MEJORADO: Conectar eventos de búsqueda inmediatamente
            connectSearchEvents();
        } else {
            // Asegurarse de que aún si la barra existe, tenga los eventos conectados
            connectSearchEvents();
        }
        
        // NUEVO: Verificar si ya hay un término de búsqueda almacenado y aplicarlo al campo
        if (typeof window.currentFilters !== 'undefined' && window.currentFilters.search) {
            $('.wc-productos-template .productos-search input').val(window.currentFilters.search);
        }
    }
    
    /**
     * NUEVA FUNCIÓN: Conectar eventos de búsqueda de manera consistente
     * Esta función centraliza la conexión de eventos para evitar duplicación
     */
    function connectSearchEvents() {
        // Primero desconectar posibles eventos duplicados
        $('.wc-productos-template .productos-search-form, .wc-productos-template .search-fix-form').off('submit');
        $('.wc-productos-template .productos-search-button, .wc-productos-template .search-fix-button').off('click');
        $('.wc-productos-template .productos-search input, .wc-productos-template .search-fix-input').off('keypress');
        
        // Conectar evento de envío de formulario
        $('.wc-productos-template .productos-search-form, .wc-productos-template .search-fix-form').on('submit', function(e) {
            e.preventDefault();
            console.log('Formulario de búsqueda enviado');
            
            // MEJORA: Asegurarse de que filterProducts existe
            if (typeof window.filterProducts === 'function') {
                // Capturar el término de búsqueda y guardarlo temporalmente en currentFilters
                if (typeof window.currentFilters !== 'undefined') {
                    window.currentFilters.search = $.trim($(this).find('input').val());
                    console.log('Término de búsqueda actualizado:', window.currentFilters.search);
                }
                window.filterProducts(1);
            } else {
                console.error('La función filterProducts no está disponible');
                // Si no existe, hacer un fallback a la búsqueda nativa de WooCommerce
                var searchTerm = $.trim($(this).find('input').val());
                window.location.href = '?s=' + encodeURIComponent(searchTerm) + '&post_type=product';
            }
            return false;
        });
        
        // Conectar evento de clic en botón
        $('.wc-productos-template .productos-search-button, .wc-productos-template .search-fix-button').on('click', function(e) {
            e.preventDefault();
            console.log('Botón de búsqueda clickeado');
            
            // Usar el formulario padre para obtener el valor de búsqueda
            var $form = $(this).closest('form');
            var searchTerm = $.trim($form.find('input').val());
            
            if (typeof window.filterProducts === 'function') {
                // Actualizar término de búsqueda en currentFilters
                if (typeof window.currentFilters !== 'undefined') {
                    window.currentFilters.search = searchTerm;
                    console.log('Término de búsqueda actualizado:', window.currentFilters.search);
                }
                window.filterProducts(1);
            } else {
                console.error('La función filterProducts no está disponible');
                // Fallback a la búsqueda nativa
                window.location.href = '?s=' + encodeURIComponent(searchTerm) + '&post_type=product';
            }
            return false;
        });
        
        // Conectar evento de tecla Enter en el campo de búsqueda
        $('.wc-productos-template .productos-search input, .wc-productos-template .search-fix-input').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                console.log('Tecla Enter presionada en búsqueda');
                
                var searchTerm = $.trim($(this).val());
                
                if (typeof window.filterProducts === 'function') {
                    // Actualizar término de búsqueda en currentFilters
                    if (typeof window.currentFilters !== 'undefined') {
                        window.currentFilters.search = searchTerm;
                        console.log('Término de búsqueda actualizado:', window.currentFilters.search);
                    }
                    window.filterProducts(1);
                } else {
                    console.error('La función filterProducts no está disponible');
                    // Fallback a la búsqueda nativa
                    window.location.href = '?s=' + encodeURIComponent(searchTerm) + '&post_type=product';
                }
                return false;
            }
        });

        // NUEVO: Botón de limpieza de búsqueda (para mayor usabilidad)
        $('.wc-productos-template .productos-search').append(
            '<span class="search-clear-button" style="display: none;">&times;</span>'
        );
        
        // Mostrar/ocultar botón de limpiar dependiendo si hay texto
        $('.wc-productos-template .productos-search input, .wc-productos-template .search-fix-input').on('input', function() {
            var $clearBtn = $(this).parent().find('.search-clear-button');
            if ($(this).val().length > 0) {
                $clearBtn.show();
            } else {
                $clearBtn.hide();
            }
        });
        
        // Iniciar con estado correcto del botón de limpiar
        $('.wc-productos-template .productos-search input, .wc-productos-template .search-fix-input').each(function() {
            var $clearBtn = $(this).parent().find('.search-clear-button');
            if ($(this).val().length > 0) {
                $clearBtn.show();
            } else {
                $clearBtn.hide();
            }
        });
        
        // Evento para limpiar la búsqueda
        $('.wc-productos-template .search-clear-button').on('click', function() {
            var $input = $(this).parent().find('input');
            $input.val('');
            $(this).hide();
            
            // Actualizar término de búsqueda y filtrar
            if (typeof window.currentFilters !== 'undefined') {
                window.currentFilters.search = '';
                console.log('Término de búsqueda limpiado');
            }
            
            if (typeof window.filterProducts === 'function') {
                window.filterProducts(1);
            }
        });
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
    
    /**
     * NUEVA FUNCIÓN: Verificar y corregir la integración con WooCommerce
     */
    function integrateWithWooCommerce() {
        // Verificar si hay un término de búsqueda en la URL
        var urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('s') && urlParams.has('post_type') && urlParams.get('post_type') === 'product') {
            var searchTerm = urlParams.get('s');
            
            // Asegurarse de que el término de búsqueda se aplique también a nuestra búsqueda personalizada
            $('.wc-productos-template .productos-search input, .wc-productos-template .search-fix-input').val(searchTerm);
            
            // Actualizar el objeto currentFilters si existe
            if (typeof window.currentFilters !== 'undefined') {
                window.currentFilters.search = searchTerm;
                console.log('Término de búsqueda de URL aplicado:', searchTerm);
                
                // Iniciar búsqueda con AJAX si existe la función
                if (typeof window.filterProducts === 'function') {
                    setTimeout(function() {
                        window.filterProducts(1);
                    }, 100);
                }
            }
        }
    }
    
    // Ejecutar las funciones seguras inmediatamente
    safeForceGrid();
    fixSearchBar();
    fixGridStructure();
    fixOrphanedElements();
    integrateWithWooCommerce();
    
    // Volver a aplicar después de una pequeña demora para asegurar que todos los elementos se hayan cargado
    setTimeout(function() {
        safeForceGrid();
        fixSearchBar();
        fixGridStructure();
        fixOrphanedElements();
        connectSearchEvents();  // Asegurar que los eventos estén conectados
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
        connectSearchEvents();  // Asegurar que los eventos estén conectados
    });
    
    // NUEVO: Detectar cambios en el DOM que podrían afectar a la barra de búsqueda
    // Esto es útil cuando hay scripts de temas que modifican el DOM después de cargarse
    var observeDOM = (function(){
        var MutationObserver = window.MutationObserver || window.WebKitMutationObserver;
        
        return function(obj, callback){
            if(!obj || obj.nodeType !== 1) return;
            
            if(MutationObserver){
                var mutationObserver = new MutationObserver(callback);
                mutationObserver.observe(obj, { childList: true, subtree: true });
                return mutationObserver;
            } else if(window.addEventListener){
                obj.addEventListener('DOMNodeInserted', callback, false);
                obj.addEventListener('DOMNodeRemoved', callback, false);
            }
        };
    })();
    
    // Observar cambios en el contenedor principal
    var $container = $('.wc-productos-template').get(0);
    if ($container) {
        observeDOM($container, function(mutations) {
            // Verificar si algún cambio afecta a la barra de búsqueda
            var needsFixing = false;
            
            if (mutations && mutations.length) {
                for (var i = 0; i < mutations.length; i++) {
                    if (mutations[i].type === 'childList') {
                        needsFixing = true;
                        break;
                    }
                }
            } else {
                needsFixing = true;
            }
            
            if (needsFixing) {
                setTimeout(function() {
                    fixSearchBar();
                    connectSearchEvents();
                }, 100);
            }
        });
    }
    
    // Exponer función modificada para uso global que no interfiera con la paginación
    window.forceGridLayout = safeForceGrid;
    
    // NUEVO: Exponer la función connectSearchEvents para uso global
    window.connectSearchEvents = connectSearchEvents;
});
