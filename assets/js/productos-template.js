/**
 * SOLUCIÓN: JavaScript mejorado para productos-template.js
 * Este archivo corrige los problemas de paginación y filtrado
 */

jQuery(document).ready(function($) {
    console.log('Productos Template Script - Versión optimizada');
    
    // Verificar si estamos en una página con el template de productos
    if (!$('.wc-productos-template').length) {
        return;
    }
    
    // Estado global para almacenar los filtros actuales
    var currentFilters = {
        page: 1,
        category: [],
        grade: [],
        min_volume: 100,
        max_volume: 5000,
        search: ''
    };
    
   /**
 * MODIFICACIÓN: Mejorar función filterProducts para manejar mejor la búsqueda
 */
function filterProducts(page) {
    // Asignar página actual
    page = parseInt(page) || 1;
    currentFilters.page = page;
    
    // Mostrar mensaje de carga
    var $mainContent = $('.wc-productos-template .productos-main');
    
    // Verificar si ya existe un loader
    if (!$mainContent.find('.loading').length) {
        $mainContent.append('<div class="loading">' + 
            (typeof WCProductosParams !== 'undefined' ? 
            WCProductosParams.i18n.loading : 'Cargando productos...') + 
            '</div>');
    }
    
    // Recopilar valores de filtros actuales
    updateCurrentFilters();
    
    // Debug para verificar qué estamos enviando
    console.log('Enviando filtros:', currentFilters);
    
    // MODIFICADO: Asegurar que el formulario de búsqueda muestre el término buscado
    $('.wc-productos-template .productos-search input').val(currentFilters.search);
    
    // Realizar petición AJAX
    $.ajax({
        url: typeof WCProductosParams !== 'undefined' ? WCProductosParams.ajaxurl : ajaxurl,
        type: 'POST',
        data: {
            action: 'productos_filter',
            nonce: typeof WCProductosParams !== 'undefined' ? WCProductosParams.nonce : '',
            page: currentFilters.page,
            category: currentFilters.category.join(','),
            grade: currentFilters.grade.join(','),
            min_volume: currentFilters.min_volume,
            max_volume: currentFilters.max_volume,
            search: currentFilters.search
        },
        success: function(response) {
            // Eliminar mensaje de carga
            $mainContent.find('.loading').remove();
            
            console.log('Respuesta recibida:', response);
            
            if (response.success) {
                // Actualizar productos y paginación
                updateProductGrid(response.data.products);
                updatePagination(response.data.pagination);
                
                // Actualizar el breadcrumb si está disponible
                if (response.data.breadcrumb) {
                    updateBreadcrumb(response.data.breadcrumb);
                } else {
                    // Alternativa: actualizar manualmente con la página actual
                    updateBreadcrumbForPagination(currentFilters.page);
                }
                
                // Desplazarse al inicio de los productos con animación suave
                $('html, body').animate({
                    scrollTop: $('.wc-productos-template .productos-main').offset().top - 100
                }, 500);
                
                // Actualizar estado de URL sin recargar página
                updateUrlState();
                
                // Forzar la cuadrícula y volver a enlazar eventos
                setTimeout(function() {
                    forceGridLayout();
                    bindPaginationEvents();
                }, 100);
            } else {
                // Mostrar mensaje de error
                showError(typeof WCProductosParams !== 'undefined' ? 
                         WCProductosParams.i18n.error : 
                         'Error al cargar productos. Intente nuevamente.');
            }
        },
        error: function(xhr, status, error) {
            // Eliminar mensaje de carga y mostrar error
            $mainContent.find('.loading').remove();
            console.error('Error AJAX:', status, error);
            showError(typeof WCProductosParams !== 'undefined' ? 
                     WCProductosParams.i18n.error : 
                     'Error al cargar productos. Intente nuevamente.');
        }
    });
}
/**
 * MODIFICACIÓN: Unificar eventos de búsqueda para evitar duplicación
 */
function bindSearchEvents() {
    // Eliminar eventos anteriores para evitar duplicados
    $('.wc-productos-template .productos-search form, .wc-productos-template .productos-search-form').off('submit');
    $('.wc-productos-template .productos-search input').off('keypress');
    $('.wc-productos-template .productos-search button, .wc-productos-template .productos-search-button').off('click');
    
    // Búsqueda - formulario
    $('.wc-productos-template .productos-search form, .wc-productos-template .productos-search-form').on('submit', function(e) {
        e.preventDefault();
        console.log('Búsqueda enviada por formulario');
        filterProducts(1); // Volver a página 1 al buscar
    });
    
    // Búsqueda - tecla Enter
    $('.wc-productos-template .productos-search input').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            console.log('Búsqueda enviada con Enter');
            filterProducts(1); // Volver a página 1 al buscar
        }
    });
    
    // Búsqueda - botón
    $('.wc-productos-template .productos-search button, .wc-productos-template .productos-search-button').on('click', function(e) {
        e.preventDefault();
        console.log('Búsqueda enviada con botón');
        filterProducts(1); // Volver a página 1 al buscar
    });
    
    // Debug para verificar que los eventos de búsqueda fueron enlazados
    console.log('Eventos de búsqueda enlazados correctamente');
}
    
   /**
 * MODIFICACIÓN: Corregir función updateCurrentFilters() para capturar correctamente el término de búsqueda
 */
function updateCurrentFilters() {
    // Categorías seleccionadas
    currentFilters.category = [];
    $('.wc-productos-template .filtro-category:checked').each(function() {
        currentFilters.category.push($(this).val());
    });
    
    // Grados seleccionados
    currentFilters.grade = [];
    $('.wc-productos-template .filtro-grade:checked').each(function() {
        currentFilters.grade.push($(this).val());
    });
    
    // Valores de volumen (asegurar que sean números)
    currentFilters.min_volume = parseInt($('.wc-productos-template input[name="min_volume"]').val()) || 100;
    currentFilters.max_volume = parseInt($('.wc-productos-template input[name="max_volume"]').val()) || 5000;
    
    // MODIFICADO: Término de búsqueda (selector más específico y robusto)
    var searchInputValue = '';
    var $searchInput = $('.wc-productos-template #productos-search-input, .wc-productos-template .productos-search input[type="text"], .wc-productos-template .productos-search-form input[name="s"]');
    
    if ($searchInput.length > 0) {
        searchInputValue = $searchInput.val() || '';
    }
    
    currentFilters.search = $.trim(searchInputValue);
    
    // Debug para verificar la captura del término de búsqueda
    console.log('Término de búsqueda capturado:', currentFilters.search);
}
    /**
     * Actualizar la cuadrícula de productos con el nuevo HTML - CORREGIDA
     */
    function updateProductGrid(productsHtml) {
        var $productsWrapper = $('.wc-productos-template .productos-wrapper');
        
        if ($productsWrapper.length) {
            // Eliminar cuadrícula anterior y mensaje de no productos
            $productsWrapper.find('ul.products, .productos-grid, .woocommerce-info, .no-products-found').remove();
            
            // Insertar nuevo HTML
            $productsWrapper.prepend(productsHtml);
        } else {
            // Si no existe el wrapper, crear uno
            var $main = $('.wc-productos-template .productos-main');
            if ($main.length) {
                $main.append('<div class="productos-wrapper">' + productsHtml + '</div>');
            } else {
                // Alternativa si no existe el main ni el wrapper
                var $container = $('.wc-productos-template');
                if ($container.length) {
                    var $productsGrid = $container.find('ul.products, .productos-grid');
                    if ($productsGrid.length) {
                        $productsGrid.replaceWith(productsHtml);
                    } else {
                        $container.append('<div class="productos-wrapper">' + productsHtml + '</div>');
                    }
                }
            }
        }
    }
    
    /**
     * Actualizar el breadcrumb - CORREGIDA
     */
    function updateBreadcrumb(breadcrumbHtml) {
        var $breadcrumb = $('.wc-productos-template .productos-breadcrumb');
        if ($breadcrumb.length) {
            $breadcrumb.html(breadcrumbHtml);
        }
    }
    
    /**
     * Función para actualizar el breadcrumb según la página actual - CORREGIDA
     */
    function updateBreadcrumbForPagination(currentPage) {
        // Obtener el breadcrumb actual
        var $breadcrumb = $('.wc-productos-template .productos-breadcrumb');
        if (!$breadcrumb.length) return;
        
        // Si estamos en la primera página, no es necesario modificar el breadcrumb
        if (currentPage <= 1) {
            // Eliminar página si existe en el breadcrumb
            var $breadcrumbNav = $breadcrumb.find('.woocommerce-breadcrumb');
            if ($breadcrumbNav.length) {
                var breadcrumbText = $breadcrumbNav.html();
                if (breadcrumbText && breadcrumbText.includes('Página')) {
                    breadcrumbText = breadcrumbText.replace(/\s*\/\s*Página\s+\d+/g, '');
                    $breadcrumbNav.html(breadcrumbText);
                }
            }
            return;
        }
        
        // Obtener el contenido actual del breadcrumb
        var $breadcrumbNav = $breadcrumb.find('.woocommerce-breadcrumb');
        if (!$breadcrumbNav.length) return;
        
        // Verificar si ya existe un elemento de página en el breadcrumb
        var breadcrumbText = $breadcrumbNav.html();
        
        // Si ya existe una referencia a la página, actualizarla
        if (breadcrumbText && breadcrumbText.includes('Página')) {
            breadcrumbText = breadcrumbText.replace(/Página\s+\d+/g, 'Página ' + currentPage);
            $breadcrumbNav.html(breadcrumbText);
        } else {
            // Si no existe, añadir la página al final
            breadcrumbText = breadcrumbText + ' / Página ' + currentPage;
            $breadcrumbNav.html(breadcrumbText);
        }
    }
    
    /**
     * Actualizar la paginación - CORREGIDA
     */
    function updatePagination(paginationHtml) {
        var $pagination = $('.wc-productos-template .productos-pagination');
        if ($pagination.length) {
            $pagination.replaceWith(paginationHtml);
        } else {
            $('.wc-productos-template .productos-wrapper').append(paginationHtml);
        }
    }
    
    /**
     * Mostrar mensaje de error - CORREGIDA
     */
    function showError(message) {
        var $productsWrapper = $('.wc-productos-template .productos-wrapper');
        // Asegurarse de que el wrapper exista
        if (!$productsWrapper.length) {
            $productsWrapper = $('<div class="productos-wrapper"></div>');
            $('.wc-productos-template .productos-main').append($productsWrapper);
        }
        
        // Eliminar cualquier mensaje de error anterior
        $productsWrapper.find('.woocommerce-info, .no-products-found').remove();
        
        // Mostrar el nuevo mensaje de error
        $productsWrapper.html('<div class="woocommerce-info">' + message + '</div>');
    }
    
    /**
     * Forzar disposición en cuadrícula con JavaScript - CORREGIDA
     */
    function forceGridLayout() {
        // Asegurarse de que la clase principal esté presente
        $('.wc-productos-template ul.products, .wc-productos-template .productos-grid').addClass('force-grid three-column-grid');
        
        // Establecer estilos explícitamente para asegurar la cuadrícula
        $('.wc-productos-template ul.products, .wc-productos-template .productos-grid').css({
            'display': 'grid',
            'grid-template-columns': 'repeat(3, 1fr)',
            'gap': '20px',
            'width': '100%',
            'max-width': '100%',
            'margin': '0 0 30px 0',
            'padding': '0',
            'list-style': 'none',
            'float': 'none',
            'clear': 'both'
        });
        
        // Estilo para los productos
        $('.wc-productos-template ul.products li.product, .wc-productos-template .productos-grid li.product').css({
            'width': '100%',
            'max-width': '100%',
            'margin': '0 0 20px 0',
            'padding': '0',
            'float': 'none',
            'clear': 'none',
            'box-sizing': 'border-box',
            'display': 'flex',
            'flex-direction': 'column',
            'height': '100%'
        });
        
        // Agregar clases responsive según el ancho de pantalla
        $('body').removeClass('screen-small screen-medium screen-large');
        
        if (window.innerWidth <= 480) {
            $('body').addClass('screen-small');
        } else if (window.innerWidth <= 768) {
            $('body').addClass('screen-medium');
        } else {
            $('body').addClass('screen-large');
        }
    }
    
  /**
 * MODIFICACIÓN: Mejorar función updateUrlState para incluir correctamente el término de búsqueda
 */
function updateUrlState() {
    if (!window.history || !window.history.pushState) {
        return; // No soporta History API
    }
    
    var url = new URL(window.location.href);
    var params = url.searchParams;
    
    // Limpiar parámetros existentes
    params.delete('paged');
    params.delete('category');
    params.delete('grade');
    params.delete('min_volume');
    params.delete('max_volume');
    params.delete('s');
    
    // Añadir parámetros actuales
    if (currentFilters.page > 1) {
        params.set('paged', currentFilters.page);
    }
    
    if (currentFilters.category.length > 0) {
        params.set('category', currentFilters.category.join(','));
    }
    
    if (currentFilters.grade.length > 0) {
        params.set('grade', currentFilters.grade.join(','));
    }
    
    if (currentFilters.min_volume > 100 || currentFilters.max_volume < 5000) {
        params.set('min_volume', currentFilters.min_volume);
        params.set('max_volume', currentFilters.max_volume);
    }
    
    // MODIFICADO: Usar 's' como parámetro que es el estándar de WordPress para búsqueda
    if (currentFilters.search) {
        params.set('s', currentFilters.search);
    }
    
    // Actualizar URL sin recargar (usando replaceState para no afectar el historial)
    var newUrl = url.pathname + (params.toString() ? '?' + params.toString() : '');
    window.history.replaceState({}, '', newUrl);
}
/**
 * MODIFICACIÓN: Extraer términos de búsqueda de la URL
 */
function extractSearchFromUrl() {
    var urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('s')) {
        var searchTerm = urlParams.get('s');
        $('.wc-productos-template .productos-search input').val(searchTerm);
        currentFilters.search = searchTerm;
        
        console.log('Término de búsqueda extraído de URL:', searchTerm);
    }
}
    
    /**
     * Inicializar el slider de volumen - CORREGIDA
     */
    function initVolumeSlider() {
        if ($.fn.slider && $('.volumen-range').length) {
            // Obtener valores iniciales
            var initialMin = parseInt($('input[name="min_volume"]').val() || 100);
            var initialMax = parseInt($('input[name="max_volume"]').val() || 5000);
            
            // Destruir el slider si ya está inicializado
            if ($('.volumen-range').hasClass('ui-slider')) {
                $('.volumen-range').slider('destroy');
            }
            
            $('.volumen-range').slider({
                range: true,
                min: 100,
                max: 5000,
                values: [initialMin, initialMax],
                slide: function(event, ui) {
                    $('#volumen-min').text(ui.values[0] + ' ml');
                    $('#volumen-max').text(ui.values[1] + ' ml');
                    $('input[name="min_volume"]').val(ui.values[0]);
                    $('input[name="max_volume"]').val(ui.values[1]);
                },
                change: function(event, ui) {
                    // Solo si el cambio fue por interacción del usuario
                    if (event.originalEvent) {
                        currentFilters.min_volume = ui.values[0];
                        currentFilters.max_volume = ui.values[1];
                        filterProducts(1); // Volver a página 1 al cambiar filtro
                    }
                }
            });
            
            // Establecer texto inicial
            $('#volumen-min').text(initialMin + ' ml');
            $('#volumen-max').text(initialMax + ' ml');
        }
    }
    
    /**
 * MODIFICACIÓN: Actualizar la función bindFilterEvents para usar bindSearchEvents
 */
function bindFilterEvents() {
    // Eliminar eventos anteriores para evitar duplicados
    $('.wc-productos-template .filtro-category').off('change');
    $('.wc-productos-template .filtro-grade').off('change');
    
    // Filtros de categoría
    $('.wc-productos-template .filtro-category').on('change', function() {
        console.log('Categoría cambiada');
        filterProducts(1); // Volver a página 1 al cambiar filtro
    });
    
    // Filtros de grado
    $('.wc-productos-template .filtro-grade').on('change', function() {
        console.log('Grado cambiado');
        filterProducts(1); // Volver a página 1 al cambiar filtro
    });
    
    // Enlazar eventos de búsqueda
    bindSearchEvents();
}

    
    /**
     * Enlazar eventos de paginación - versión CORREGIDA
     */
    function bindPaginationEvents() {
        // En lugar de usar delegación general, vamos a ser específicos
        $('.wc-productos-template .productos-pagination .page-number').each(function() {
            var $this = $(this);
            
            // Eliminar eventos anteriores para evitar duplicación
            $this.off('click');
            
            // Agregar el nuevo evento
            $this.on('click', function(e) {
                e.preventDefault();
                var page = $(this).data('page');
                
                console.log('Clic en paginación:', page);
                
                if (page) {
                    // Actualizar inmediatamente el breadcrumb para mejor UX
                    updateBreadcrumbForPagination(page);
                    
                    // Luego filtrar productos
                    filterProducts(page);
                }
                return false;
            });
        });
        
        // Establecer estilo activo para la página actual
        var currentPage = currentFilters.page;
        $('.wc-productos-template .productos-pagination .page-number').removeClass('active');
        $('.wc-productos-template .productos-pagination .page-number[data-page="' + currentPage + '"]').addClass('active');
    }
    
    /**
     * Forzar cuadrícula de tres columnas - CORREGIDA
     */
    function forceThreeColumnGrid() {
        // Eliminar clases que puedan interferir
        $('.wc-productos-template ul.products, .productos-grid').removeClass('columns-1 columns-2 columns-4 columns-5 columns-6');
        
        // Aplicar clases para la cuadrícula de 3 columnas
        $('.wc-productos-template ul.products, .productos-grid').addClass('three-column-grid force-grid columns-3');
        
        // Establecer explícitamente grid-template-columns
        $('.wc-productos-template ul.products, .productos-grid').css('grid-template-columns', 'repeat(3, 1fr)');
    }
    /**
 * JavaScript para manejar la expansión/contracción de categorías jerárquicas
 * Para agregar al archivo productos-template.js
 */

// Función para inicializar comportamiento de categorías jerárquicas
function initCategoryFilters() {
    console.log('Inicializando filtros de categorías jerárquicas');
    
    // Manejar clic en el icono de expansión
    $('.wc-productos-template .category-toggle').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation(); // Evitar que se propague al checkbox
        
        var categorySlug = $(this).data('category');
        var childrenList = $('#children-' + categorySlug);
        
        // Alternar expansión
        $(this).toggleClass('expanded');
        childrenList.toggleClass('expanded');
        
        // Rotar icono
        if ($(this).hasClass('expanded')) {
            $(this).find('i').removeClass('fa-chevron-down').addClass('fa-chevron-up');
        } else {
            $(this).find('i').removeClass('fa-chevron-up').addClass('fa-chevron-down');
        }
    });
    
    // Marcar/desmarcar automáticamente categorías hijas cuando se selecciona la categoría padre
    $('.wc-productos-template .filtro-parent-option .filtro-category').on('change', function() {
        var isChecked = $(this).prop('checked');
        var categorySlug = $(this).val();
        var childrenContainer = $('#children-' + categorySlug);
        
        // Si el padre está seleccionado, expandir automáticamente
        if (isChecked) {
            var toggle = $('.category-toggle[data-category="' + categorySlug + '"]');
            toggle.addClass('expanded');
            toggle.find('i').removeClass('fa-chevron-down').addClass('fa-chevron-up');
            childrenContainer.addClass('expanded');
        }
        
        // Seleccionar o deseleccionar todas las categorías hijas
        childrenContainer.find('.filtro-child').prop('checked', isChecked);
    });
    
    // Al cargar, expandir automáticamente categorías que ya tienen selecciones
    $('.wc-productos-template .filtro-parent-option .filtro-category:checked').each(function() {
        var categorySlug = $(this).val();
        var toggle = $('.category-toggle[data-category="' + categorySlug + '"]');
        toggle.addClass('expanded');
        toggle.find('i').removeClass('fa-chevron-down').addClass('fa-chevron-up');
        $('#children-' + categorySlug).addClass('expanded');
    });
    
    // También expandir si alguna categoría hija está seleccionada
    $('.wc-productos-template .filtro-child-option .filtro-category:checked').each(function() {
        var parentContainer = $(this).closest('.filtro-children-list');
        var parentId = parentContainer.attr('id');
        if (parentId && parentId.startsWith('children-')) {
            var categorySlug = parentId.replace('children-', '');
            var toggle = $('.category-toggle[data-category="' + categorySlug + '"]');
            toggle.addClass('expanded');
            toggle.find('i').removeClass('fa-chevron-down').addClass('fa-chevron-up');
            parentContainer.addClass('expanded');
        }
    });
}

// Añadir la inicialización a la función existente
$(document).ready(function($) {
    // Verificar si estamos en una página con el template de productos
    if ($('.wc-productos-template').length) {
        // Inicializar filtros de categorías jerárquicas
        initCategoryFilters();
        
        // Volver a inicializar después de AJAX
        $(document).ajaxComplete(function(event, xhr, settings) {
            if (settings.url && settings.url.includes('productos_filter')) {
                setTimeout(function() {
                    initCategoryFilters();
                }, 200);
            }
        });
    }
});
    // Inicializar todo - VERSIÓN CORREGIDA
   function init() {
    console.log('Inicializando productos template con búsqueda mejorada');
    
    // Forzar cuadrícula desde el inicio
    forceGridLayout();
    forceThreeColumnGrid();
    
    // Inicializar slider de volumen
    initVolumeSlider();
    
    // Enlazar eventos
    bindFilterEvents();
    bindPaginationEvents();
    
    // Extraer filtros de la URL al cargar
    var urlParams = new URLSearchParams(window.location.search);
    
    // Procesar parámetros de la URL
    if (urlParams.has('category')) {
        var categories = urlParams.get('category').split(',');
        categories.forEach(function(cat) {
            $('#cat-' + cat).prop('checked', true);
        });
        currentFilters.category = categories;
    }
    
    if (urlParams.has('grade')) {
        var grades = urlParams.get('grade').split(',');
        grades.forEach(function(grade) {
            $('#grade-' + grade).prop('checked', true);
        });
        currentFilters.grade = grades;
    }
    
    if (urlParams.has('min_volume') && urlParams.has('max_volume')) {
        var minVol = parseInt(urlParams.get('min_volume'));
        var maxVol = parseInt(urlParams.get('max_volume'));
        
        if ($.fn.slider && $('.volumen-range').length) {
            $('.volumen-range').slider('values', [minVol, maxVol]);
            $('#volumen-min').text(minVol + ' ml');
            $('#volumen-max').text(maxVol + ' ml');
            $('input[name="min_volume"]').val(minVol);
            $('input[name="max_volume"]').val(maxVol);
        }
        
        currentFilters.min_volume = minVol;
        currentFilters.max_volume = maxVol;
    }
    
    // MODIFICADO: Extraer término de búsqueda de la URL (parámetro 's')
    if (urlParams.has('s')) {
        var searchTerm = urlParams.get('s');
        $('.wc-productos-template .productos-search input').val(searchTerm);
        currentFilters.search = searchTerm;
    }
    
    if (urlParams.has('paged')) {
        currentFilters.page = parseInt(urlParams.get('paged'));
    }
    
    // Si hay paginación activa, actualizar el breadcrumb
    if (urlParams.has('paged')) {
        var pageNum = parseInt(urlParams.get('paged'));
        if (pageNum > 1) {
            setTimeout(function() {
                updateBreadcrumbForPagination(pageNum);
            }, 300);
        }
    }
    
    // Si hay algún filtro o paginación activo, actualizar los productos
    if (urlParams.has('category') || urlParams.has('grade') || 
        urlParams.has('min_volume') || urlParams.has('max_volume') || 
        urlParams.has('s') || urlParams.has('paged')) {
        
        console.log('Hay filtros activos, actualizando productos...');
        
        // Usar timeout para asegurar que los elementos del DOM estén listos
        setTimeout(function() {
            filterProducts(currentFilters.page);
        }, 300);
    }
    
    // Volver a forzar la cuadrícula después de cargar imágenes
    $(window).on('load', function() {
        forceGridLayout();
        forceThreeColumnGrid();
        bindPaginationEvents(); // Reenlazar eventos después de carga completa
    });
    
    // Ajustar cuadrícula al cambiar tamaño de ventana
    $(window).on('resize', function() {
        forceGridLayout();
        forceThreeColumnGrid();
    });
}
    
    // Iniciar todo
    init();
    
    // Exponer la función a nivel global para otros scripts
    window.filterProducts = filterProducts;
    
    // Asegurar que el script search-bar-fix no interfiera con la paginación
    if (window.forceGridLayout) {
        var originalForceGrid = window.forceGridLayout;
        window.forceGridLayout = function() {
            originalForceGrid();
            bindPaginationEvents(); // Volver a enlazar eventos después de forzar la cuadrícula
        };
    }
});
/**
 * JavaScript mejorado para evitar congelamiento al aplicar filtros
 * Soluciona el problema de pantalla gris bloqueada
 */

jQuery(document).ready(function($) {
    // Variables globales para controlar el estado
    var isFilteringInProgress = false;
    
    // Función para inicializar el botón de filtros móvil
    function initMobileFilterButton() {
        console.log('Inicializando botón de filtros móvil...');
        
        // Eliminar botón y contenedor existentes para evitar duplicados
        $('.mobile-filters-toggle, .mobile-filters-container').remove();
        
        // Verificar si estamos en móvil (ancho <= 768px)
        if (window.innerWidth <= 768) {
            // Capturar el contenido del sidebar
            var $sidebar = $('.wc-productos-template .productos-sidebar');
            var sidebarContent = '';
            
            if ($sidebar.length) {
                sidebarContent = $sidebar.html();
                
                // Crear el botón de filtro
                var $filterButton = $('<button class="mobile-filters-toggle" aria-label="Filtros"><i class="fas fa-filter"></i></button>');
                
                // Crear el contenedor del panel
                var $filterContainer = $('<div class="mobile-filters-container">' +
                    '<div class="mobile-filters-header">' +
                    '<h2>Filtros</h2>' +
                    '<button class="close-filters" aria-label="Cerrar">&times;</button>' +
                    '</div>' +
                    '<div class="mobile-filters-content">' + sidebarContent + '</div>' +
                    '<div class="mobile-filters-loader" style="display:none;">' +
                    '<div class="loader-spinner"></div>' +
                    '<div class="loader-text">Aplicando filtros...</div>' +
                    '</div>' +
                    '</div>');
                
                // Añadir al body
                $('body').append($filterButton);
                $('body').append($filterContainer);
                
                // Manejar interacciones
                $filterButton.on('click', function() {
                    // No permitir abrir si hay filtrado en progreso
                    if (isFilteringInProgress) return;
                    
                    $filterContainer.toggleClass('active');
                    $('body').toggleClass('filters-open');
                });
                
                $filterContainer.find('.close-filters').on('click', function() {
                    closeFilterPanel();
                });
                
                // Si se hace clic fuera del panel, cerrarlo (solo si no hay filtrado en progreso)
                $(document).on('click', function(e) {
                    if (!isFilteringInProgress && 
                        $filterContainer.hasClass('active') && 
                        !$(e.target).closest('.mobile-filters-container').length && 
                        !$(e.target).closest('.mobile-filters-toggle').length) {
                        closeFilterPanel();
                    }
                });
                
                // Asegurar que los eventos de filtros funcionen en la versión móvil
                initMobileFilterEvents($filterContainer);
                
                console.log('Botón de filtros móvil creado correctamente');
            } else {
                console.log('No se encontró el sidebar de filtros');
                return; // Salir si no hay sidebar
            }
        }
    }
    
    // Función para cerrar el panel de filtros
    function closeFilterPanel() {
        $('.mobile-filters-container').removeClass('active');
        $('body').removeClass('filters-open');
    }
    
    // Función para mostrar el loader
    function showFilterLoader() {
        isFilteringInProgress = true;
        $('.mobile-filters-content').css('opacity', '0.3');
        $('.mobile-filters-loader').show();
        $('.close-filters').prop('disabled', true).css('opacity', '0.5');
    }
    
    // Función para ocultar el loader
    function hideFilterLoader() {
        isFilteringInProgress = false;
        $('.mobile-filters-content').css('opacity', '1');
        $('.mobile-filters-loader').hide();
        $('.close-filters').prop('disabled', false).css('opacity', '1');
        
        // Cerrar el panel después de aplicar filtros
        closeFilterPanel();
    }
    
    // Función para inicializar eventos de los filtros en la versión móvil
    function initMobileFilterEvents($container) {
        if (!$container || !$container.length) return;
        
        // Manejar la expansión/contracción de categorías jerárquicas
        $container.find('.category-toggle').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // No permitir cambios si hay filtrado en progreso
            if (isFilteringInProgress) return;
            
            var categorySlug = $(this).data('category');
            var childrenList = $container.find('#children-' + categorySlug);
            
            // Alternar expansión
            $(this).toggleClass('expanded');
            childrenList.toggleClass('expanded');
            
            // Rotar icono
            if ($(this).hasClass('expanded')) {
                $(this).find('i').removeClass('fa-chevron-down').addClass('fa-chevron-up');
            } else {
                $(this).find('i').removeClass('fa-chevron-up').addClass('fa-chevron-down');
            }
        });
        
        // Manejar cambios en checkboxes de filtros
        $container.find('.filtro-category, .filtro-grade').on('change', function() {
            // No permitir cambios si hay filtrado en progreso
            if (isFilteringInProgress) {
                // Revertir cambio en el checkbox
                $(this).prop('checked', !$(this).prop('checked'));
                return;
            }
            
            var isParent = $(this).closest('.filtro-parent-option').length > 0;
            var categorySlug, childrenContainer;
            
            // Si es categoría padre, actualizar hijos
            if (isParent) {
                var isChecked = $(this).prop('checked');
                categorySlug = $(this).val();
                childrenContainer = $container.find('#children-' + categorySlug);
                
                // Si el padre está seleccionado, expandir automáticamente
                if (isChecked) {
                    var toggle = $container.find('.category-toggle[data-category="' + categorySlug + '"]');
                    toggle.addClass('expanded');
                    toggle.find('i').removeClass('fa-chevron-down').addClass('fa-chevron-up');
                    childrenContainer.addClass('expanded');
                }
                
                // Seleccionar o deseleccionar todas las categorías hijas
                childrenContainer.find('.filtro-child').prop('checked', isChecked);
            }
            
            // Mostrar el loader antes de aplicar filtros
            showFilterLoader();
            
            // Aplicar filtros con retraso para permitir que el loader se muestre
            setTimeout(function() {
                // Llamar a la función de filtrado si está disponible
                if (typeof window.filterProducts === 'function') {
                    try {
                        window.filterProducts(1); // Volver a página 1 al cambiar filtro
                        
                        // Establecer un tiempo máximo para ocultar el loader
                        setTimeout(function() {
                            if (isFilteringInProgress) {
                                hideFilterLoader();
                                console.log('Forzando cierre del panel por timeout');
                            }
                        }, 5000); // 5 segundos máximo de espera
                    } catch (error) {
                        console.error('Error al filtrar productos:', error);
                        hideFilterLoader();
                        alert('Hubo un error al aplicar los filtros. Por favor, inténtalo de nuevo.');
                    }
                } else {
                    console.error('Función filterProducts no disponible');
                    hideFilterLoader();
                }
            }, 100);
        });
    }
    
    // Sobrescribir la función de filtrado original para manejar el estado del loader
    if (typeof window.originalFilterProducts === 'undefined' && typeof window.filterProducts === 'function') {
        // Guardar referencia a la función original
        window.originalFilterProducts = window.filterProducts;
        
        // Reemplazar con nuestra versión mejorada
        window.filterProducts = function(page) {
            var result = window.originalFilterProducts(page);
            
            // Después de 500ms, verificar si la respuesta AJAX ya llegó
            setTimeout(function() {
                if (isFilteringInProgress) {
                    // Escuchar evento ajaxComplete para detectar cuando termina
                    $(document).one('ajaxComplete', function(event, xhr, settings) {
                        if (settings.url && (
                            settings.url.includes('productos_filter') || 
                            settings.url.includes('ajax_search')
                        )) {
                            hideFilterLoader();
                        }
                    });
                }
            }, 500);
            
            return result;
        };
    }
    
    // Ejecutar la inicialización al cargar la página
    initMobileFilterButton();
    
    // Volver a inicializar cuando cambie el tamaño de la ventana
    var resizeTimer;
    $(window).on('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            initMobileFilterButton();
        }, 250); // Debounce para evitar múltiples llamadas
    });
    
    // Manejar el ajaxComplete para reinicializar y actualizar estado
    $(document).ajaxComplete(function(event, xhr, settings) {
        if (settings.url && (
            settings.url.includes('productos_filter') || 
            settings.url.includes('ajax_search') || 
            settings.url.includes('admin-ajax.php')
        )) {
            // Ocultar el loader si todavía está visible
            hideFilterLoader();
            
            // Reinicializar después de un pequeño retraso
            setTimeout(function() {
                initMobileFilterButton();
            }, 300);
        }
    });
    
    // Exponer para uso global
    window.initMobileFilterButton = initMobileFilterButton;
    window.closeFilterPanel = closeFilterPanel;
});
// Añade este código en línea al final del archivo productos-template.js
// o como un script separado que se cargue al final

// Función inmediatamente invocada para forzar vista lineal en móvil
(function($) {
    // Función para aplicar vista lineal forzada
    function forceLinearView() {
        if (window.innerWidth <= 768) {
            console.log('Forzando vista lineal para móvil');
            
            // Remover clases grid y añadir clase de vista lineal
            $('.wc-productos-template ul.products, .wc-productos-template .productos-grid')
                .removeClass('columns-3 columns-4 force-grid three-column-grid')
                .addClass('mobile-linear-view');
            
            // Asegurar que los li tengan la estructura correcta
            $('.wc-productos-template ul.products li.product, .wc-productos-template .productos-grid li.product').each(function() {
                var $product = $(this);
                
                // Si no tiene el contenedor interior, envolverlo
                if ($product.find('.producto-interior').length === 0) {
                    $product.wrapInner('<div class="producto-interior"></div>');
                }
                
                // Asegurar que la imagen esté en el formato correcto
                var $imagen = $product.find('.producto-imagen');
                var $info = $product.find('.producto-info');
                var $footer = $product.find('.producto-footer');
                
                // Si la estructura no es correcta, reorganizarla
                if (!$product.hasClass('mobile-formatted')) {
                    // Ajustar estructura
                    $product.addClass('mobile-formatted');
                    
                    // Forzar estilos inline críticos
                    $product.css({
                        'display': 'flex',
                        'flex-direction': 'row',
                        'align-items': 'flex-start',
                        'width': '100%'
                    });
                    
                    // Ajustar contenedor interior
                    $product.find('.producto-interior').css({
                        'display': 'flex',
                        'flex-direction': 'row',
                        'flex-wrap': 'wrap',
                        'width': '100%'
                    });
                    
                    // Ajustar imagen
                    $imagen.css({
                        'width': '80px',
                        'min-width': '80px',
                        'height': '80px',
                        'margin-right': '15px',
                        'margin-bottom': '0'
                    });
                    
                    // Ajustar info
                    $info.css({
                        'flex': '1',
                        'min-width': '0'
                    });
                    
                    // Ajustar footer
                    $footer.css({
                        'width': '100%',
                        'margin-top': '8px',
                        'padding-top': '8px'
                    });
                }
            });
            
            // Forzar visualización
            $('style.mobile-force-style').remove();
            $('head').append('<style class="mobile-force-style">' +
                '@media (max-width: 768px) {' +
                '  .wc-productos-template ul.products, .wc-productos-template .productos-grid { ' +
                '    display: flex !important;' +
                '    flex-direction: column !important;' +
                '    grid-template-columns: none !important;' +
                '  }' +
                '  .wc-productos-template ul.products li.product, .wc-productos-template .productos-grid li.product {' +
                '    display: flex !important;' +
                '    flex-direction: row !important;' +
                '    width: 100% !important;' +
                '    margin-bottom: 10px !important;' +
                '  }' +
                '}' +
                '</style>');
        } else {
            // En desktop restaurar la vista grid
            $('.wc-productos-template ul.products, .wc-productos-template .productos-grid')
                .removeClass('mobile-linear-view')
                .addClass('force-grid three-column-grid');
            
            // Eliminar estilos forzados en móvil
            $('style.mobile-force-style').remove();
        }
    }
    
    // Ejecutar al cargar el documento
    $(document).ready(function() {
        forceLinearView();
        
        // Ejecutar cuando cambie el tamaño de la ventana
        $(window).on('resize', forceLinearView);
        
        // Ejecutar después de AJAX
        $(document).ajaxComplete(function() {
            setTimeout(forceLinearView, 100);
        });
    });
    
    // Hacer disponible globalmente
    window.forceLinearView = forceLinearView;
})(jQuery);
