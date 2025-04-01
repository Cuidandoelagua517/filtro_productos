
            jQuery(document).ready(function($) {
                // Inicializar slider de volumen
                if ($('.volumen-slider').length) {
                    $('.volumen-slider .volumen-range').slider({
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
                    $('.productos-list').append('<div class="loading">Cargando productos...</div>');
                    
                    // Obtener valores de filtros
                    var categoryFilter = [];
                    $('.filtro-category:checked').each(function() {
                        categoryFilter.push($(this).val());
                    });
                    
                    var gradeFilter = [];
                    $('.filtro-grade:checked').each(function() {
                        gradeFilter.push($(this).val());
                    });
                    
                    var minVolume = $('input[name="min_volume"]').val() || 100;
                    var maxVolume = $('input[name="max_volume"]').val() || 5000;
                    var searchQuery = $('.productos-search input').val();
                    
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
                                $('.productos-list').html(response.data.products);
                                
                                // Actualizar paginación
                                $('.productos-pagination').html(response.data.pagination);
                                
                                // Actualizar contador de resultados
                                $('.pagination-info').text('Mostrando 1-' + 
                                    Math.min(response.data.total, $('.producto-card').length) + 
                                    ' de ' + response.data.total + ' resultados');
                                
                                // Animar scroll hacia arriba
                                $('html, body').animate({
                                    scrollTop: $('.productos-list').offset().top - 100
                                }, 500);
                            } else {
                                console.error('Error al filtrar productos');
                            }
                            
                            // Marcar que AJAX ha terminado
                            ajaxRunning = false;
                        },
                        error: function() {
                            console.error('Error en la petición AJAX');
                            $('.loading').remove();
                            ajaxRunning = false;
                        }
                    });
                }
                
                // Event listeners para filtros
                $('.filtro-option input[type="checkbox"]').on('change', function() {
                    filterProducts();
                });
                
                // Evento para slider de volumen
                $('.volumen-slider .volumen-range').on('slidechange', function() {
                    clearTimeout(timer);
                    timer = setTimeout(function() {
                        filterProducts();
                    }, 500);
                });
                
                // Evento para búsqueda
                $('.productos-search input').on('keyup', function() {
                    clearTimeout(timer);
                    timer = setTimeout(function() {
                        filterProducts();
                    }, 500);
                });
                
                // Evento para búsqueda al hacer click en el botón
                $('.productos-search button').on('click', function(e) {
                    e.preventDefault();
                    filterProducts();
                });
                
                // Delegación de eventos para paginación
                $(document).on('click', '.page-number:not(.active)', function() {
                    var page = $(this).data('page') || 1;
                    filterProducts(page);
                });
                
                // Delegación de eventos para botón Agregar al carrito
                $(document).on('click', '.producto-boton', function(e) {
                    e.preventDefault();
                    var productId = $(this).data('product-id');
                    
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
                                $('body').append('<div class="wc-message-success">Producto añadido al carrito</div>');
                                setTimeout(function() {
                                    $('.wc-message-success').fadeOut().remove();
                                }, 3000);
                            }
                        }
                    });
                });
            });
        
