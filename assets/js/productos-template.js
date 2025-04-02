jQuery(document).ready(function($) {
    // Verificar que estamos en la página correcta
    if (!$('.wc-productos-template').length) {
        return;
    }
    
    // Lazy loading de imágenes para mejorar rendimiento
    function lazyLoadProductImages() {
        $('.wc-productos-template .producto-imagen img').each(function() {
            const img = $(this);
            const src = img.attr('data-src') || img.attr('src');
            
            if (src) {
                img.attr('src', src);
                img.removeAttr('data-src');
                
                img.on('load', function() {
                    img.addClass('loaded');
                });
            }
        });
    }
    
    // Llamar a lazy load al inicio
    lazyLoadProductImages();
  // Modifica esta parte en assets/js/productos-template.js
$(document).off('click', '.page-number, .woocommerce-pagination a').on('click', '.page-number, .woocommerce-pagination a', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    // Obtener número de página
    var page = $(this).data('page') || $(this).text() || 1;
    // Si es un enlace con texto "2", etc.
    if (isNaN(page) && $(this).attr('href')) {
        // Extraer número de página de la URL
        var matches = $(this).attr('href').match(/page\/(\d+)/);
        if (matches) {
            page = matches[1];
        } else {
            matches = $(this).attr('href').match(/paged=(\d+)/);
            if (matches) {
                page = matches[1];
            }
        }
    }
    
    filterProducts(parseInt(page));
    return false;
});  
    // Llamar nuevamente después de filtrar productos
    $(document).on('productos_filtered', function() {
        lazyLoadProductImages();
    });
    
    // Efecto hover para imágenes de productos
    $('.wc-productos-template').on('mouseenter', '.producto-card', function() {
        $(this).find('.producto-imagen img').css('transform', 'scale(1.08)');
    }).on('mouseleave', '.producto-card', function() {
        $(this).find('.producto-imagen img').css('transform', 'scale(1)');
    });
    
    // Mejorar el feedback al añadir al carrito
    $('.wc-productos-template').on('click', '.producto-boton.ajax_add_to_cart', function(e) {
        const $button = $(this);
        const originalText = $button.text();
        
        // Añadir clase para efecto visual
        $button.addClass('adding').text('Añadiendo...');
        
        // Restaurar el botón después de un tiempo
        setTimeout(function() {
            $button.removeClass('adding').text(originalText);
        }, 1500);
    });
    
    // Mostrar descripciones en hover (opcional)
    $('.wc-productos-template').on('mouseenter', '.producto-card', function() {
        const $card = $(this);
        const $details = $card.find('.producto-detalles');
        
        if ($details.data('description')) {
            $details.data('original', $details.html());
            $details.html($details.data('description'));
        }
    }).on('mouseleave', '.producto-card', function() {
        const $card = $(this);
        const $details = $card.find('.producto-detalles');
        
        if ($details.data('original')) {
            $details.html($details.data('original'));
        }
    });
    
    // Estilos adicionales para el feedback de añadir al carrito
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            .wc-productos-template .producto-boton.adding {
                background-color: #388e3c;
                position: relative;
                overflow: hidden;
            }
            .wc-productos-template .producto-boton.adding:after {
                content: '';
                position: absolute;
                top: 0;
                left: -100%;
                width: 100%;
                height: 100%;
                background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
                animation: shine 1s infinite;
            }
            @keyframes shine {
                100% {
                    left: 100%;
                }
            }
        `)
        .appendTo('head');
    
    // Inicializar slider de volumen
    if ($('.wc-productos-template .volumen-slider').length) {
        $('.wc-productos-template .volumen-slider .volumen-range').slider({
            range: true,
            min: 100,
            max: 5000,
            values: [100, 5000],
            slide: function(event, ui) {
                $('#volumen-min').text(ui.values[0] + ' ml');
                $('#volumen-max').text(ui.values[1] + ' ml');
                $('input[name="min_volume"]').val(ui.values[0]);
                $('input[name="max_volume"]').val(ui.values[1]);
            }
        });
    }
    
    // Variables para filtrado
    var timer;
    var ajaxRunning = false;
    
    // Función para filtrar productos
    window.filterProducts = function(page = 1) {
        if (ajaxRunning) return;
        
        // Mostrar indicador de carga
        if ($('.wc-productos-template .productos-grid .loading').length === 0) {
            $('.wc-productos-template .productos-grid').append('<div class="loading">Cargando productos...</div>');
        }
        
        // Obtener valores de filtros
        var categoryFilter = [];
        $('.wc-productos-template .filtro-category:checked').each(function() {
            categoryFilter.push($(this).val());
        });
        
        var gradeFilter = [];
        $('.wc-productos-template .filtro-grade:checked').each(function() {
            gradeFilter.push($(this).val());
        });
        
        var minVolume = $('.wc-productos-template input[name="min_volume"]').val() || 100;
        var maxVolume = $('.wc-productos-template input[name="max_volume"]').val() || 5000;
        var searchQuery = $('.wc-productos-template .productos-search input').val();
        
        // Configurar datos para AJAX
        var data = {
            action: 'productos_filter',
            nonce: WCProductosParams.nonce,
            category: categoryFilter.join(','),
            grade: gradeFilter.join(','),
            min_volume: minVolume,
            max_volume: maxVolume,
            search: searchQuery,
            page: page
        };
        
        // Marcar que AJAX está en progreso
        ajaxRunning = true;
        
        // Realizar petición AJAX
        $.ajax({
            url: WCProductosParams.ajaxurl,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    // Actualizar lista de productos
                    $('.wc-productos-template .productos-grid').html(response.data.products);
                    
                    // Actualizar paginación
                    $('.wc-productos-template .productos-pagination').html(response.data.pagination);
                    
                    // Actualizar contador de resultados si existe
                    if ($('.wc-productos-template .pagination-info').length) {
                        var showing = Math.min(response.data.total, response.data.current_page * WCProductosParams.products_per_page);
                        var start = (response.data.current_page - 1) * WCProductosParams.products_per_page + 1;
                        
                        if (response.data.total > 0) {
                            $('.wc-productos-template .pagination-info').text(
                                'Mostrando ' + start + '-' + showing + ' de ' + response.data.total + ' resultados'
                            );
                        } else {
                            $('.wc-productos-template .pagination-info').text('0 resultados');
                        }
                    }
                    
                    // Modificar URL para permitir refrescar la página
                    if (history.pushState) {
                        var newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
                        var params = [];
                        
                        // Añadir página a la URL si es diferente de la página 1
                        if (page > 1) {
                            params.push('paged=' + page);
                        }
                        
                        // Añadir otros parámetros si hay filtros activos
                        if (categoryFilter.length) {
                            params.push('category=' + categoryFilter.join(','));
                        }
                        
                        if (gradeFilter.length) {
                            params.push('grade=' + gradeFilter.join(','));
                        }
                        
                        if (searchQuery) {
                            params.push('s=' + encodeURIComponent(searchQuery));
                        }
                        
                        // Construir URL con parámetros
                        if (params.length > 0) {
                            newUrl += '?' + params.join('&');
                        }
                        
                        window.history.pushState({path: newUrl}, '', newUrl);
                    }
                    
                    // Scroll suave hacia arriba si es necesario
                    if ($('.wc-productos-template .productos-grid').length) {
                        $('html, body').animate({
                            scrollTop: $('.wc-productos-template .productos-grid').offset().top - 100
                        }, 500);
                    }
                    
                    // Disparar evento personalizado para permitir otras acciones
                    $(document).trigger('productos_filtered', [response.data]);
                    
                } else {
                    console.error('Error al filtrar productos');
                    $('.wc-productos-template .productos-grid').html(
                        '<p class="woocommerce-info">' + WCProductosParams.i18n.error + '</p>'
                    );
                }
                
                // Marcar que AJAX ha terminado
                ajaxRunning = false;
                $('.wc-productos-template .loading').remove();
            },
            error: function(xhr, status, error) {
                console.error('Error en la petición AJAX:', error);
                $('.wc-productos-template .loading').remove();
                $('.wc-productos-template .productos-grid').html(
                    '<p class="woocommerce-info">' + WCProductosParams.i18n.error + '</p>'
                );
                ajaxRunning = false;
            }
        });
    };
    
    // Event listeners para filtros
    $('.wc-productos-template .filtro-option input[type="checkbox"]').on('change', function() {
        filterProducts();
    });
    
    // Evento para slider de volumen
    $('.wc-productos-template .volumen-slider .volumen-range').on('slidechange', function() {
        clearTimeout(timer);
        timer = setTimeout(function() {
            filterProducts();
        }, 500);
    });
    
    // Evento para búsqueda
    $('.wc-productos-template .productos-search input').on('keyup', function() {
        clearTimeout(timer);
        timer = setTimeout(function() {
            filterProducts();
        }, 500);
    });
    
    // Evento para búsqueda al hacer click en el botón
    $('.wc-productos-template .productos-search button').on('click', function(e) {
        e.preventDefault();
        filterProducts();
    });
    
    // Delegación de eventos para paginación con prevención de doble binding
    $(document).off('click', '.wc-productos-template .page-number').on('click', '.wc-productos-template .page-number', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        // No hacer nada si ya está en la página activa
        if ($(this).hasClass('active')) {
            return false;
        }
        
        var page = $(this).data('page') || 1;
        filterProducts(page);
        return false;
    });
    
    // Delegación de eventos para botón Agregar al carrito (compatibilidad)
    $(document).on('click', '.wc-productos-template .producto-boton', function(e) {
        var $this = $(this);
        
        // Si ya tiene comportamiento de WooCommerce, no interferir
        if ($this.hasClass('ajax_add_to_cart') || $this.hasClass('add_to_cart_button')) {
            return;
        }
        
        e.preventDefault();
        var productId = $this.data('product-id');
        
        // Verificar que tenemos la variable de WooCommerce
        if (typeof wc_add_to_cart_params === 'undefined') {
            window.location.href = $this.attr('href');
            return;
        }
        
        // Añadir al carrito usando AJAX de WooCommerce
        $.ajax({
            url: wc_add_to_cart_params.ajax_url,
            type: 'POST',
            data: {
                action: 'woocommerce_ajax_add_to_cart',
                product_id: productId,
                quantity: 1
            },
            success: function(response) {
                if (response.fragments) {
                    // Actualizar mini-carrito
                    $.each(response.fragments, function(key, value) {
                        $(key).replaceWith(value);
                    });
                    
                    // Mostrar mensaje de éxito
                    $('body').append('<div class="wc-message-success wc-productos-template">' + 
                                    WCProductosParams.i18n.added + '</div>');
                    setTimeout(function() {
                        $('.wc-message-success').fadeOut().remove();
                    }, 3000);
                } else {
                    window.location.href = $this.attr('href');
                }
            },
            error: function() {
                window.location.href = $this.attr('href');
            }
        });
    });
    
    // Función para manejar scroll del sidebar
    $(window).on('scroll', function() {
        var scrollTop = $(window).scrollTop();
        if (scrollTop > 100) {
            $('.wc-productos-template').addClass('scrolled');
        } else {
            $('.wc-productos-template').removeClass('scrolled');
        }
    });
    
    // Verificar si hay parámetros en la URL al cargar
    $(window).on('load', function() {
        var urlParams = new URLSearchParams(window.location.search);
        var page = urlParams.get('paged');
        
        // Recuperar filtros de la URL y aplicarlos a los elementos de formulario
        var categoryParam = urlParams.get('category');
        var gradeParam = urlParams.get('grade');
        var searchParam = urlParams.get('s');
        
        if (categoryParam) {
            var categories = categoryParam.split(',');
            $.each(categories, function(i, cat) {
                $('.filtro-category[value="' + cat + '"]').prop('checked', true);
            });
        }
        
        if (gradeParam) {
            var grades = gradeParam.split(',');
            $.each(grades, function(i, grade) {
                $('.filtro-grade[value="' + grade + '"]').prop('checked', true);
            });
        }
        
        if (searchParam) {
            $('.productos-search input').val(decodeURIComponent(searchParam));
        }
        
        // Si hay una página especificada, verificar si necesitamos cargar mediante AJAX
        if (page && page > 1) {
            // Solo cargar vía AJAX si no hay contenido o si la URL tiene el parámetro refresh
            if ($('.wc-productos-template .productos-grid').is(':empty') || urlParams.get('refresh')) {
                filterProducts(parseInt(page));
            }
        }
    });
});
