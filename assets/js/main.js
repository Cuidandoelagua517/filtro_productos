/**
 * JavaScript principal para WC Productos Template
 * Funcionalidad basada en paradigma de módulos
 * 
 * @package WC_Productos_Template
 */

(function($) {
    'use strict';
    
    // Objeto principal
    const WCProductos = {
        // Estado global
        state: {
            currentFilters: {
                page: 1,
                category: [],
                stock: '',
                min_price: '',
                max_price: '',
                orderby: '',
                search: ''
            },
            ajaxRunning: false,
            $container: null,
            $sidebar: null,
            $main: null,
            isMobile: window.innerWidth < 768
        },
        
        // Inicialización
        init: function() {
            // Verificar si estamos en una página con el template
            if (!$('.wc-productos-template').length) {
                return;
            }
            
            console.log('WC Productos Template - Inicializando');
            
            // Obtener referencias a elementos DOM
            this.state.$container = $('.wc-productos-template');
            this.state.$sidebar = $('.wc-productos-sidebar');
            this.state.$main = $('.wc-productos-main');
            
            // Inicializar submódulos
            this.Grid.init();
            this.Filter.init();
            this.Search.init();
            
            // Cargar parámetros iniciales desde URL
            this.loadStateFromUrl();
            
            // Inicializar eventos globales
            this.initEvents();
            
            console.log('WC Productos Template - Inicialización completa');
        },
        
        // Submódulo de cuadrícula
        Grid: {
            init: function() {
                this.forceGrid();
                this.initAddToCartButtons();
                this.initQuickView();
                
                // Reajustar cuadrícula al cambiar el tamaño de la ventana
                $(window).on('resize', this.handleResize.bind(this));
            },
            
            forceGrid: function() {
                // Forzar cuadrícula y corregir elementos flotantes
                $('.wc-productos-template ul.products, .wc-productos-grid').addClass('force-grid');
                
                // Asegurar que los elementos flotantes no rompan la cuadrícula
                $('.wc-productos-template ul.products::before, .wc-productos-template ul.products::after').css({
                    'display': 'none',
                    'content': 'none',
                    'clear': 'none'
                });
                
                // Asegurar que los elementos de la cuadrícula tengan altura completa
                $('.wc-productos-template ul.products li.product, .wc-productos-grid li.product').css({
                    'width': '100%',
                    'margin': '0',
                    'float': 'none',
                    'display': 'flex',
                    'flex-direction': 'column',
                    'height': '100%'
                });
                
                // Aplicar clases responsive según el ancho de pantalla
                this.applyResponsiveClasses();
            },
            
            applyResponsiveClasses: function() {
                const windowWidth = window.innerWidth;
                
                if (windowWidth >= 1200) {
                    // Escritorio grande - 4 columnas
                    $('.wc-productos-template ul.products, .wc-productos-grid').removeClass('one-column-grid two-column-grid three-column-grid').addClass('four-column-grid');
                } else if (windowWidth >= 992) {
                    // Escritorio - 3 columnas
                    $('.wc-productos-template ul.products, .wc-productos-grid').removeClass('one-column-grid two-column-grid four-column-grid').addClass('three-column-grid');
                } else if (windowWidth >= 576) {
                    // Tabletas - 2 columnas
                    $('.wc-productos-template ul.products, .wc-productos-grid').removeClass('one-column-grid three-column-grid four-column-grid').addClass('two-column-grid');
                } else {
                    // Móvil - 1 columna
                    $('.wc-productos-template ul.products, .wc-productos-grid').removeClass('two-column-grid three-column-grid four-column-grid').addClass('one-column-grid');
                }
                
                // Actualizar estado de móvil
                WCProductos.state.isMobile = windowWidth < 768;
            },
            
            handleResize: function() {
                this.applyResponsiveClasses();
                
                // Si cambió entre móvil y escritorio, reinicializar sidebar
                const wasMobile = WCProductos.state.isMobile;
                const isMobile = window.innerWidth < 768;
                
                if (wasMobile !== isMobile) {
                    WCProductos.state.isMobile = isMobile;
                    WCProductos.Filter.initSidebar();
                }
            },
            
            initAddToCartButtons: function() {
                // Manejar eventos de añadir al carrito
                $('body').on('click', '.wc-producto-add-to-cart.ajax_add_to_cart', function(e) {
                    e.preventDefault();
                    
                    const $button = $(this);
                    const productId = $button.data('product_id');
                    
                    // Si ya se está procesando, no hacer nada
                    if ($button.hasClass('loading')) {
                        return false;
                    }
                    
                    // Añadir clase de cargando
                    $button.addClass('loading');
                    
                    // Realizar solicitud AJAX
                    $.ajax({
                        type: 'POST',
                        url: WCProductosParams.ajaxurl,
                        data: {
                            action: 'woocommerce_add_to_cart',
                            product_id: productId,
                            quantity: 1
                        },
                        success: function(response) {
                            if (response.error) {
                                // Mostrar mensaje de error
                                if (response.product_url) {
                                    window.location = response.product_url;
                                    return;
                                }
                            } else {
                                // Actualizar fragmentos del carrito
                                if (response.fragments) {
                                    $.each(response.fragments, function(key, value) {
                                        $(key).replaceWith(value);
                                    });
                                }
                                
                                // Mostrar notificación de éxito
                                $button.removeClass('loading').addClass('added');
                                
                                // Disparar evento para otros scripts
                                $('body').trigger('added_to_cart', [response.fragments, response.cart_hash, $button]);
                            }
                        },
                        error: function() {
                            $button.removeClass('loading');
                            console.error('Error al añadir al carrito');
                        }
                    });
                    
                    return false;
                });
            },
            
            initQuickView: function() {
                // Manejar eventos de vista rápida
                $('body').on('click', '.wc-producto-quick-view-button', function(e) {
                    e.preventDefault();
                    
                    const $button = $(this);
                    const productId = $button.data('product_id');
                    
                    // Mostrar modal de vista rápida
                    WCProductos.loadQuickView(productId);
                    
                    return false;
                });
            }
        },
        
        // Submódulo de filtros
        Filter: {
            init: function() {
                this.initSidebar();
                this.initFilterHandlers();
                this.initPriceRange();
                this.initCategoryToggles();
                this.initSortingHandlers();
                this.initPaginationHandlers();
            },
            
            initSidebar: function() {
                // En móvil, hacer la barra lateral colapsable
                if (WCProductos.state.isMobile) {
                    // Si no existe el botón para mostrar/ocultar filtros, añadirlo
                    if (!$('.wc-productos-show-filters-button').length) {
                        WCProductos.state.$main.before(
                            '<button type="button" class="wc-productos-show-filters-button">' + 
                            '<i class="fas fa-filter"></i> ' + 
                            'Filtros</button>'
                        );
                        
                        // Eventos para mostrar/ocultar filtros
                        $('body').on('click', '.wc-productos-show-filters-button', function() {
                            WCProductos.state.$sidebar.toggleClass('active');
                            $('body').toggleClass('filters-active');
                        });
                        
                        // Botón para cerrar filtros
                        if (!WCProductos.state.$sidebar.find('.wc-productos-close-filters').length) {
                            WCProductos.state.$sidebar.prepend(
                                '<button type="button" class="wc-productos-close-filters">' + 
                                '<i class="fas fa-times"></i></button>'
                            );
                            
                            // Evento para cerrar filtros
                            $('body').on('click', '.wc-productos-close-filters', function() {
                                WCProductos.state.$sidebar.removeClass('active');
                                $('body').removeClass('filters-active');
                            });
                        }
                    }
                    
                    // Ocultar sidebar por defecto
                    WCProductos.state.$sidebar.removeClass('active');
                } else {
                    // En escritorio, eliminar elementos móviles
                    $('.wc-productos-show-filters-button, .wc-productos-close-filters').remove();
                    WCProductos.state.$sidebar.removeClass('active');
                    $('body').removeClass('filters-active');
                }
            },
            
            initFilterHandlers: function() {
                // Manejar filtros de categoría
                $('body').on('change', '.wc-productos-filter-category', function() {
                    // Actualizar array de categorías
                    WCProductos.state.currentFilters.category = [];
                    
                    $('.wc-productos-filter-category:checked').each(function() {
                        WCProductos.state.currentFilters.category.push($(this).val());
                    });
                    
                    // Resetear a página 1 y filtrar
                    WCProductos.state.currentFilters.page = 1;
                    WCProductos.applyFilters();
                });
                
                // Manejar filtro de stock
                $('body').on('change', '.wc-productos-filter-stock', function() {
                    if ($(this).is(':checked')) {
                        WCProductos.state.currentFilters.stock = 'instock';
                    } else {
                        WCProductos.state.currentFilters.stock = '';
                    }
                    
                    // Resetear a página 1 y filtrar
                    WCProductos.state.currentFilters.page = 1;
                    WCProductos.applyFilters();
                });
                
                // Manejar botón "Aplicar filtros" (principalmente en móvil)
                $('body').on('click', '.wc-productos-apply-filters-button', function() {
                    WCProductos.applyFilters();
                    
                    // En móvil, cerrar el panel de filtros
                    if (WCProductos.state.isMobile) {
                        WCProductos.state.$sidebar.removeClass('active');
                        $('body').removeClass('filters-active');
                    }
                });
            },
            
            initPriceRange: function() {
                // Inicializar slider de rango de precios si existe
                if ($.fn.slider && $('.wc-productos-price-slider-ui').length) {
                    // Obtener valores del elemento
                    const $slider = $('.wc-productos-price-slider');
                    const minPrice = parseFloat($slider.data('min')) || 0;
                    const maxPrice = parseFloat($slider.data('max')) || 1000;
                    const currentMin = parseFloat($slider.data('current-min')) || minPrice;
                    const currentMax = parseFloat($slider.data('current-max')) || maxPrice;
                    
                    // Inicializar slider
                    $('.wc-productos-price-slider-ui').slider({
                        range: true,
                        min: minPrice,
                        max: maxPrice,
                        values: [currentMin, currentMax],
                        slide: function(event, ui) {
                            // Actualizar valores en los inputs
                            $('.wc-productos-min-price').val(ui.values[0]);
                            $('.wc-productos-max-price').val(ui.values[1]);
                        }
                    });
                    
                    // Sincronizar inputs con slider
                    $('.wc-productos-min-price, .wc-productos-max-price').on('change', function() {
                        const minValue = parseFloat($('.wc-productos-min-price').val());
                        const maxValue = parseFloat($('.wc-productos-max-price').val());
                        
                        if (!isNaN(minValue) && !isNaN(maxValue) && minValue <= maxValue) {
                            $('.wc-productos-price-slider-ui').slider('values', [minValue, maxValue]);
                        }
                    });
                    
                    // Manejar botón de aplicar filtro de precio
                    $('body').on('click', '.wc-productos-price-filter-button', function() {
                        // Obtener valores actuales
                        const minValue = parseFloat($('.wc-productos-min-price').val());
                        const maxValue = parseFloat($('.wc-productos-max-price').val());
                        
                        if (!isNaN(minValue) && !isNaN(maxValue) && minValue <= maxValue) {
                            // Actualizar filtros y aplicar
                            WCProductos.state.currentFilters.min_price = minValue;
                            WCProductos.state.currentFilters.max_price = maxValue;
                            WCProductos.state.currentFilters.page = 1;
                            WCProductos.applyFilters();
                        }
                    });
                }
            },
            
            initCategoryToggles: function() {
                // Manejar expansión/contracción de categorías jerárquicas
                $('body').on('click', '.wc-productos-category-toggle', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const categorySlug = $(this).data('category');
                    const $childrenList = $('#children-' + categorySlug);
                    
                    // Alternar visibilidad
                    $(this).toggleClass('expanded');
                    $childrenList.toggleClass('expanded');
                    
                    // Cambiar icono
                    if ($(this).hasClass('expanded')) {
                        $(this).find('i').removeClass('fa-chevron-down').addClass('fa-chevron-up');
                    } else {
                        $(this).find('i').removeClass('fa-chevron-up').addClass('fa-chevron-down');
                    }
                });
                
                // Cuando se selecciona una categoría padre, expandir automáticamente
                $('body').on('change', '.wc-productos-category-parent-header .wc-productos-filter-category', function() {
                    if ($(this).is(':checked')) {
                        const categorySlug = $(this).val();
                        const $toggle = $('.wc-productos-category-toggle[data-category="' + categorySlug + '"]');
                        const $childrenList = $('#children-' + categorySlug);
                        
                        $toggle.addClass('expanded');
                        $toggle.find('i').removeClass('fa-chevron-down').addClass('fa-chevron-up');
                        $childrenList.addClass('expanded');
                    }
                });
            },
            
            initSortingHandlers: function() {
                // Manejar cambio de ordenamiento
                $('body').on('change', '.wc-productos-ordering select, .woocommerce-ordering select', function() {
                    WCProductos.state.currentFilters.orderby = $(this).val();
                    WCProductos.applyFilters();
                });
            },
            
            initPaginationHandlers: function() {
                // Manejar eventos de paginación
                $('body').on('click', '.wc-productos-pagination-links a', function(e) {
                    e.preventDefault();
                    
                    const page = $(this).data('page');
                    
                    if (page) {
                        WCProductos.state.currentFilters.page = page;
                        WCProductos.applyFilters();
                        
                        // Desplazarse al inicio de los productos
                        $('html, body').animate({
                            scrollTop: WCProductos.state.$main.offset().top - 100
                        }, 500);
                    }
                    
                    return false;
                });
            }
        },
        
        // Submódulo de búsqueda
        Search: {
            init: function() {
                this.initSearchForm();
                this.initAutocomplete();
            },
            
            initSearchForm: function() {
                // Manejar envío del formulario de búsqueda
                $('body').on('submit', '.wc-productos-search-form', function(e) {
                    e.preventDefault();
                    
                    // Obtener término de búsqueda
                    const searchTerm = $(this).find('input[name="s"]').val();
                    
                    // Actualizar filtros y aplicar
                    WCProductos.state.currentFilters.search = searchTerm;
                    WCProductos.state.currentFilters.page = 1;
                    WCProductos.applyFilters();
                    
                    return false;
                });
            },
            
            initAutocomplete: function() {
                // Inicializar autocompletado si jQuery UI está disponible
                if ($.fn.autocomplete && $('.wc-productos-search-form input[name="s"]').length) {
                    $('.wc-productos-search-form input[name="s"]').autocomplete({
                        minLength: 3,
                        source: function(request, response) {
                            // Realizar solicitud AJAX para obtener sugerencias
                            $.ajax({
                                url: WCProductosParams.ajaxurl,
                                data: {
                                    action: 'wc_productos_search_suggestions',
                                    term: request.term,
                                    nonce: WCProductosParams.nonce
                                },
                                dataType: 'json',
                                success: function(data) {
                                    if (data.success) {
                                        response(data.data);
                                    } else {
                                        response([]);
                                    }
                                },
                                error: function() {
                                    response([]);
                                }
                            });
                        },
                        select: function(event, ui) {
                            if (ui.item.url) {
                                window.location.href = ui.item.url;
                                return false;
                            }
                        }
                    }).autocomplete('instance')._renderItem = function(ul, item) {
                        // Personalizar renderizado de elementos
                        let html = '<div class="wc-productos-autocomplete-item">';
                        
                        // Añadir imagen si existe
                        if (item.image) {
                            html += '<div class="wc-productos-autocomplete-image"><img src="' + item.image + '" alt="' + item.label + '"></div>';
                        }
                        
                        html += '<div class="wc-productos-autocomplete-info">';
                        html += '<div class="wc-productos-autocomplete-title">' + item.label + '</div>';
                        
                        // Añadir precio si existe
                        if (item.price) {
                            html += '<div class="wc-productos-autocomplete-price">' + item.price + '</div>';
                        }
                        
                        html += '</div></div>';
                        
                        return $('<li>')
                            .append(html)
                            .appendTo(ul);
                    };
                }
            }
        },
        
        // Funciones globales
        
        // Cargar estado desde URL
        loadStateFromUrl: function() {
            const urlParams = new URLSearchParams(window.location.search);
            
            // Cargar página
            if (urlParams.has('paged')) {
                this.state.currentFilters.page = parseInt(urlParams.get('paged')) || 1;
            }
            
            // Cargar categorías
            if (urlParams.has('category')) {
                this.state.currentFilters.category = urlParams.get('category').split(',');
                
                // Marcar checkboxes correspondientes
                this.state.currentFilters.category.forEach(function(cat) {
                    $('.wc-productos-filter-category[value="' + cat + '"]').prop('checked', true);
                });
            }
            
            // Cargar filtro de stock
            if (urlParams.has('stock') && urlParams.get('stock') === 'instock') {
                this.state.currentFilters.stock = 'instock';
                $('.wc-productos-filter-stock').prop('checked', true);
            }
            
            // Cargar rango de precios
            if (urlParams.has('min_price') && urlParams.has('max_price')) {
                this.state.currentFilters.min_price = parseFloat(urlParams.get('min_price'));
                this.state.currentFilters.max_price = parseFloat(urlParams.get('max_price'));
                
                // Actualizar inputs
                $('.wc-productos-min-price').val(this.state.currentFilters.min_price);
                $('.wc-productos-max-price').val(this.state.currentFilters.max_price);
                
                // Actualizar slider si existe
                if ($.fn.slider && $('.wc-productos-price-slider-ui').length) {
                    $('.wc-productos-price-slider-ui').slider('values', [
                        this.state.currentFilters.min_price,
                        this.state.currentFilters.max_price
                    ]);
                }
            }
            
            // Cargar ordenamiento
            if (urlParams.has('orderby')) {
                this.state.currentFilters.orderby = urlParams.get('orderby');
                $('.wc-productos-ordering select, .woocommerce-ordering select').val(this.state.currentFilters.orderby);
            }
            
            // Cargar término de búsqueda
            if (urlParams.has('s')) {
                this.state.currentFilters.search = urlParams.get('s');
                $('.wc-productos-search-form input[name="s"]').val(this.state.currentFilters.search);
            }
        },
        
        // Inicializar eventos globales
        initEvents: function() {
            // Reajustar después de carga completa
            $(window).on('load', function() {
                WCProductos.Grid.forceGrid();
            });
            
            // Manejar clics en botones de login para invitados
            $('body').on('click', '.wc-productos-login-to-view', function(e) {
                e.preventDefault();
                
                // Si el módulo de login está disponible, usarlo
                if (typeof WCProductosLogin !== 'undefined') {
                    const productId = $(this).data('product_id');
                    WCProductosLogin.openLoginPopup(productId);
                } else {
                    // Fallback: redirigir a la página de login
                    window.location.href = WCProductosParams.login_url || '/my-account/';
                }
                
                return false;
            });
        },
        
        // Aplicar filtros actuales
        applyFilters: function() {
            // Si hay una solicitud AJAX en curso, no hacer nada
            if (this.state.ajaxRunning) {
                return;
            }
            
            // Actualizar estado
            this.state.ajaxRunning = true;
            
            // Mostrar indicador de carga
            if (!this.state.$main.find('.wc-productos-loading').length) {
                this.state.$main.append(
                    '<div class="wc-productos-loading">' + 
                    '<div class="wc-productos-loading-spinner"></div>' + 
                    '<div class="wc-productos-loading-text">' + WCProductosParams.i18n.loading + '</div>' + 
                    '</div>'
                );
            }
            
            // Actualizar URL con los filtros actuales
            this.updateUrlState();
            
            // Preparar datos para la solicitud AJAX
            const data = {
                action: 'wc_productos_filter',
                nonce: WCProductosParams.nonce,
                page: this.state.currentFilters.page,
                category: this.state.currentFilters.category.join(','),
                stock: this.state.currentFilters.stock,
                min_price: this.state.currentFilters.min_price,
                max_price: this.state.currentFilters.max_price,
                orderby: this.state.currentFilters.orderby,
                search: this.state.currentFilters.search
            };
            
            // Realizar solicitud AJAX
            $.ajax({
                url: WCProductosParams.ajaxurl,
                type: 'POST',
                data: data,
                success: this.handleFilterResponse.bind(this),
                error: this.handleFilterError.bind(this),
                complete: function() {
                    // Actualizar estado
                    WCProductos.state.ajaxRunning = false;
                    
                    // Eliminar indicador de carga
                    WCProductos.state.$main.find('.wc-productos-loading').remove();
                }
            });
        },
        
        // Manejar respuesta de filtrado
        handleFilterResponse: function(response) {
            if (!response.success) {
                this.handleFilterError();
                return;
            }
            
            // Actualizar productos
            const $productsWrapper = this.state.$main.find('.productos-wrapper, .wc-productos-wrapper');
            
            if ($productsWrapper.length) {
                // Eliminar cuadrícula anterior y mensajes
                $productsWrapper.find('ul.products, .wc-productos-grid, .woocommerce-info, .wc-productos-no-results').remove();
                
                // Insertar nuevo HTML de productos
                $productsWrapper.prepend(response.data.products);
            } else {
                // Si no existe el wrapper, insertar directamente en el contenedor principal
                this.state.$main.find('ul.products, .wc-productos-grid, .woocommerce-info, .wc-productos-no-results').remove();
                this.state.$main.prepend(response.data.products);
            }
            
            // Actualizar paginación
            const $pagination = this.state.$main.find('.wc-productos-pagination, .productos-pagination');
            
            if ($pagination.length) {
                $pagination.replaceWith(response.data.pagination);
            } else {
                this.state.$main.append(response.data.pagination);
            }
            
            // Forzar cuadrícula
            setTimeout(function() {
                WCProductos.Grid.forceGrid();
            }, 100);
        },
        
        // Manejar error de filtrado
        handleFilterError: function() {
            console.error('Error al filtrar productos');
            
            // Mostrar mensaje de error
            const $productsWrapper = this.state.$main.find('.productos-wrapper, .wc-productos-wrapper');
            
            if ($productsWrapper.length) {
                $productsWrapper.html(
                    '<div class="wc-productos-no-results">' + 
                    WCProductosParams.i18n.error + 
                    '</div>'
                );
            } else {
                this.state.$main.html(
                    '<div class="wc-productos-no-results">' + 
                    WCProductosParams.i18n.error + 
                    '</div>'
                );
            }
        },
        
        // Actualizar URL con los filtros actuales
        updateUrlState: function() {
            if (!window.history || !window.history.replaceState) {
                return;
            }
            
            const url = new URL(window.location.href);
            const params = url.searchParams;
            
            // Limpiar parámetros existentes
            params.delete('paged');
            params.delete('category');
            params.delete('stock');
            params.delete('min_price');
            params.delete('max_price');
            params.delete('orderby');
            params.delete('s');
            
            // Añadir parámetros actuales
            if (this.state.currentFilters.page > 1) {
                params.set('paged', this.state.currentFilters.page);
            }
            
            if (this.state.currentFilters.category.length > 0) {
                params.set('category', this.state.currentFilters.category.join(','));
            }
            
            if (this.state.currentFilters.stock) {
                params.set('stock', this.state.currentFilters.stock);
            }
            
            if (this.state.currentFilters.min_price && this.state.currentFilters.max_price) {
                params.set('min_price', this.state.currentFilters.min_price);
                params.set('max_price', this.state.currentFilters.max_price);
            }
            
            if (this.state.currentFilters.orderby) {
                params.set('orderby', this.state.currentFilters.orderby);
            }
            
            if (this.state.currentFilters.search) {
                params.set('s', this.state.currentFilters.search);
            }
            
            // Actualizar URL sin recargar
            const newUrl = url.pathname + (params.toString() ? '?' + params.toString() : '');
            window.history.replaceState({}, '', newUrl);
        },
        
        // Cargar vista rápida de producto
        loadQuickView: function(productId) {
            // Verificar si ya existe un modal
            if ($('.wc-productos-quick-view-modal').length) {
                $('.wc-productos-quick-view-modal').remove();
            }
            
            // Crear modal
            $('body').append(
                '<div class="wc-productos-quick-view-modal">' + 
                '<div class="wc-productos-quick-view-overlay"></div>' + 
                '<div class="wc-productos-quick-view-content">' + 
                '<button type="button" class="wc-productos-quick-view-close"><i class="fas fa-times"></i></button>' + 
                '<div class="wc-productos-quick-view-body">' + 
                '<div class="wc-productos-quick-view-loading">' + 
                '<div class="wc-productos-loading-spinner"></div>' + 
                '<div class="wc-productos-loading-text">' + WCProductosParams.i18n.loading + '</div>' + 
                '</div>' + 
                '</div>' + 
                '</div>' + 
                '</div>'
            );
            
            // Manejar cierre del modal
            $('.wc-productos-quick-view-overlay, .wc-productos-quick-view-close').on('click', function() {
                $('.wc-productos-quick-view-modal').remove();
            });
            
            // Realizar solicitud AJAX
            $.ajax({
                url: WCProductosParams.ajaxurl,
                type: 'POST',
                data: {
                    action: 'wc_productos_quick_view',
                    nonce: WCProductosParams.nonce,
                    product_id: productId
                },
                success: function(response) {
                    if (response.success) {
                        // Actualizar contenido
                        $('.wc-productos-quick-view-body').html(response.data.html);
                        
                        // Inicializar posibles sliders o tabs
                        if ($.fn.slick && $('.wc-productos-quick-view-images').length) {
                            $('.wc-productos-quick-view-images').slick({
                                dots: true,
                                arrows: true,
                                infinite: true,
                                speed: 300,
                                slidesToShow: 1
                            });
                        }
                        
                        // Inicializar tabs
                        $('.wc-productos-quick-view-tabs-nav a').on('click', function(e) {
                            e.preventDefault();
                            
                            const tab = $(this).data('tab');
                            
                            // Activar pestaña
                            $('.wc-productos-quick-view-tabs-nav a').removeClass('active');
                            $(this).addClass('active');
                            
                            // Mostrar contenido
                            $('.wc-productos-quick-view-tab-content').removeClass('active');
                            $('.wc-productos-quick-view-tab-content[data-tab="' + tab + '"]').addClass('active');
                            
                            return false;
                        });
                    } else {
                        // Mostrar mensaje de error
                        $('.wc-productos-quick-view-body').html(
                            '<div class="wc-productos-quick-view-error">' + 
                            response.data.message || WCProductosParams.i18n.error + 
                            '</div>'
                        );
                    }
                },
                error: function() {
                    // Mostrar mensaje de error
                    $('.wc-productos-quick-view-body').html(
                        '<div class="wc-productos-quick-view-error">' + 
                        WCProductosParams.i18n.error + 
                        '</div>'
                    );
                }
            });
        }
    };
    
    // Iniciar cuando el DOM esté listo
    $(document).ready(function() {
        WCProductos.init();
    });
    
    // Exponer globalmente
    window.WCProductos = WCProductos;
})(jQuery);
