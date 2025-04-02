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
    
    // Trigger evento después del filtrado
    const originalFilterProducts = window.filterProducts || function() {};
    window.filterProducts = function(page = 1) {
        originalFilterProducts(page);
        $(document).trigger('productos_filtered');
    };
});

// Estilos adicionales para el feedback de añadir al carrito
jQuery(document).ready(function($) {
    // Añadir estos estilos al DOM para animaciones
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
});
    
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
    function filterProducts(page = 1) {
        if (ajaxRunning) return;
        
        // Mostrar indicador de carga
        $('.wc-productos-template .productos-list').append('<div class="loading">Cargando productos...</div>');
        
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
                    $('.wc-productos-template .productos-list').html(response.data.products);
                    
                    // Actualizar paginación
                    $('.wc-productos-template .productos-pagination').html(response.data.pagination);
                    
                    // Actualizar contador de resultados
                    $('.wc-productos-template .pagination-info').text('Mostrando 1-' + 
                        Math.min(response.data.total, $('.wc-productos-template .producto-card').length) + 
                        ' de ' + response.data.total + ' resultados');
                    
                    // Animar scroll hacia arriba si hay productos
                    if ($('.wc-productos-template .productos-list').length) {
                        $('html, body').animate({
                            scrollTop: $('.wc-productos-template .productos-list').offset().top - 100
                        }, 500);
                    }
                } else {
                    console.error('Error al filtrar productos');
                    $('.wc-productos-template .productos-list').html('<p>' + WCProductosParams.i18n.error + '</p>');
                }
                
                // Marcar que AJAX ha terminado
                ajaxRunning = false;
                $('.wc-productos-template .loading').remove();
            },
            error: function() {
                console.error('Error en la petición AJAX');
                $('.wc-productos-template .loading').remove();
                $('.wc-productos-template .productos-list').html('<p>' + WCProductosParams.i18n.error + '</p>');
                ajaxRunning = false;
            }
        });
    }
    
    // Event listeners para filtros (solo para elementos dentro de wc-productos-template)
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
    
    // Delegación de eventos para paginación (usando namespace)
    $(document).on('click', '.wc-productos-template .page-number:not(.active)', function() {
        var page = $(this).data('page') || 1;
        filterProducts(page);
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
});
