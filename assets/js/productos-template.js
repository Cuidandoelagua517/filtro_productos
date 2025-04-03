/**
 * JavaScript principal para el template de productos de WooCommerce
 * Maneja filtros, búsqueda y paginación mediante AJAX
 *
 * @package WC_Productos_Template
 */

jQuery(document).ready(function($) {
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
     * Función principal para filtrar productos vía AJAX
     */
    function filterProducts(page) {
        // Asignar página actual
        page = page || 1;
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
        
        // Realizar petición AJAX
        $.ajax({
            url: WCProductosParams.ajaxurl,
            type: 'POST',
            data: {
                action: 'productos_filter',
                nonce: WCProductosParams.nonce,
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
                
                if (response.success) {
                    // Actualizar productos y paginación
                    updateProductGrid(response.data.products);
                    updatePagination(response.data.pagination);
                    
                    // Desplazarse al inicio de los productos
                    $('html, body').animate({
                        scrollTop: $('.wc-productos-template .productos-main').offset().top - 100
                    }, 500);
                    
                    // Actualizar estado de URL sin recargar página
                    updateUrlState();
                } else {
                    // Mostrar mensaje de error
                    showError(typeof WCProductosParams !== 'undefined' ? 
                             WCProductosParams.i18n.error : 
                             'Error al cargar productos. Intente nuevamente.');
                }
            },
            error: function() {
                // Eliminar mensaje de carga y mostrar error
                $mainContent.find('.loading').remove();
                showError(typeof WCProductosParams !== 'undefined' ? 
                         WCProductosParams.i18n.error : 
                         'Error al cargar productos. Intente nuevamente.');
            }
        });
    }
    
    /**
     * Recopilar valores actuales de los filtros
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
        
        // Valores de volumen
        currentFilters.min_volume = $('.wc-productos-template input[name="min_volume"]').val() || 100;
        currentFilters.max_volume = $('.wc-productos-template input[name="max_volume"]').val() || 5000;
        
        // Término de búsqueda
        currentFilters.search = $('.wc-productos-template .productos-search input').val() || '';
    }
    
    /**
     * Actualizar la cuadrícula de productos con el nuevo HTML
     */
    function updateProductGrid(productsHtml) {
        var $productsWrapper = $('.wc-productos-template .productos-wrapper');
        if ($productsWrapper.length) {
            // Eliminar cuadrícula anterior
            $productsWrapper.find('ul.products, .productos-grid').remove();
            
            // Insertar nuevo HTML
            $productsWrapper.prepend(productsHtml);
        } else {
            // Alternativa si no existe el wrapper
            var $productsGrid = $('.wc-productos-template ul.products, .wc-productos-template .productos-grid');
            if ($productsGrid.length) {
                $productsGrid.replaceWith(productsHtml);
            }
        }
        
        // Forzar cuadrícula de nuevo
        forceGridLayout();
    }
    
    /**
     * Actualizar la paginación
     */
    function updatePagination(paginationHtml) {
        var $pagination = $('.wc-productos-template .productos-pagination');
        if ($pagination.length) {
            $pagination.replaceWith(paginationHtml);
        } else {
            $('.wc-productos-template .productos-wrapper').append(paginationHtml);
        }
        
        // Reenlazar eventos de paginación
        bindPaginationEvents();
    }
    
    /**
     * Mostrar mensaje de error
     */
    function showError(message) {
        var $productsWrapper = $('.wc-productos-template .productos-wrapper');
        $productsWrapper.html('<div class="woocommerce-info">' + message + '</div>');
    }
    
    /**
     * Forzar disposición en cuadrícula con JavaScript
     */
    function forceGridLayout() {
        $('.wc-productos-template ul.products, .productos-grid').css({
            'display': 'grid',
            'grid-template-columns': 'repeat(auto-fill, minmax(220px, 1fr))',
            'gap': '20px',
            'width': '100%',
            'margin': '0',
            'padding': '0',
            'list-style': 'none',
            'float': 'none',
            'clear': 'both'
        });
        
        $('.wc-productos-template ul.products li.product, .productos-grid li.product').css({
            'width': '100%',
            'margin': '0 0 20px 0',
            'float': 'none',
            'clear': 'none',
            'display': 'flex',
            'flex-direction': 'column',
            'height': '100%'
        });
        
        // Media query para móviles
        if (window.innerWidth <= 480) {
            $('.wc-productos-template ul.products, .productos-grid').css({
                'grid-template-columns': 'repeat(2, 1fr)'
            });
        } else if (window.innerWidth <= 768) {
            $('.wc-productos-template ul.products, .productos-grid').css({
                'grid-template-columns': 'repeat(2, 1fr)'
            });
        } else if (window.innerWidth <= 991) {
            $('.wc-productos-template ul.products, .productos-grid').css({
                'grid-template-columns': 'repeat(3, 1fr)'
            });
        }
    }
    
    /**
     * Actualizar URL sin recargar la página (History API)
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
        
        if (currentFilters.search) {
            params.set('s', currentFilters.search);
        }
        
        // Actualizar URL sin recargar
        var newUrl = url.pathname + (params.toString() ? '?' + params.toString() : '');
        window.history.pushState({}, '', newUrl);
    }
    
    /**
     * Inicializar el slider de volumen
     */
    function initVolumeSlider() {
        if ($.fn.slider && $('.volumen-range').length) {
            // Obtener valores iniciales
            var initialMin = parseInt($('input[name="min_volume"]').val() || 100);
            var initialMax = parseInt($('input[name="max_volume"]').val() || 5000);
            
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
     * Enlazar eventos para filtros
     */
    function bindFilterEvents() {
        // Filtros de categoría
        $('.wc-productos-template .filtro-category').on('change', function() {
            filterProducts(1); // Volver a página 1 al cambiar filtro
        });
        
        // Filtros de grado
        $('.wc-productos-template .filtro-grade').on('change', function() {
            filterProducts(1); // Volver a página 1 al cambiar filtro
        });
        
        // Búsqueda
        $('.wc-productos-template .productos-search form, .wc-productos-template .productos-search-form').on('submit', function(e) {
            e.preventDefault();
            filterProducts(1); // Volver a página 1 al buscar
        });
        
        $('.wc-productos-template .productos-search input').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                filterProducts(1); // Volver a página 1 al buscar
            }
        });
        
        $('.wc-productos-template .productos-search button, .wc-productos-template .productos-search-button').on('click', function(e) {
            e.preventDefault();
            filterProducts(1); // Volver a página 1 al buscar
        });
    }
    
    /**
     * Enlazar eventos de paginación
     */
    function bindPaginationEvents() {
        $(document).on('click', '.wc-productos-template .page-number', function() {
            var page = $(this).data('page');
            if (page) {
                filterProducts(page);
            }
            return false;
        });
    }
    
    // Inicializar todo
    function init() {
        // Inicializar slider de volumen
        initVolumeSlider();
        
        // Enlazar eventos
        bindFilterEvents();
        bindPaginationEvents();
        
        // Forzar cuadrícula al inicio
        forceGridLayout();
        
        // Forzar cuadrícula después de cargar imágenes
        $(window).on('load', forceGridLayout);
        
        // Ajustar cuadrícula al cambiar tamaño de ventana
        $(window).on('resize', forceGridLayout);
        
        // Extraer filtros de la URL al cargar
        var urlParams = new URLSearchParams(window.location.search);
        
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
        
        if (urlParams.has('s')) {
            var searchTerm = urlParams.get('s');
            $('.wc-productos-template .productos-search input').val(searchTerm);
            currentFilters.search = searchTerm;
        }
        
        if (urlParams.has('paged')) {
            currentFilters.page = parseInt(urlParams.get('paged'));
        }
    }
    
    // Iniciar todo
    init();
    
    // Exponer la función a nivel global para otros scripts
    window.filterProducts = filterProducts;
});
