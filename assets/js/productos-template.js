// Reemplazar el manejador de eventos de paginación en productos-template.js
jQuery(document).ready(function($) {
    // Verificar que estamos en la página correcta
    if (!$('.wc-productos-template').length) {
        return;
    }
    
    // Determinar si estamos en una página con shortcode o en el archivo principal
    const isShortcodePage = $('.productos-container').closest('.entry-content, .page-content, article').length > 0;
    
    // Modificar el comportamiento de los botones de paginación solo si estamos en una página de shortcode
    if (isShortcodePage) {
        // Desactivar el comportamiento AJAX para la paginación en las páginas de shortcode
        $(document).off('click', '.page-number, .woocommerce-pagination a');
        
        // Convertir los botones de paginación en enlaces reales
        $('.wc-productos-template .pagination-links .page-number').each(function() {
            var $button = $(this);
            var page = $button.data('page');
            
            // Crear un elemento <a> para reemplazar el botón
            var currentUrl = new URL(window.location.href);
            var urlParams = new URLSearchParams(currentUrl.search);
            urlParams.set('paged', page);
            
            var $link = $('<a>', {
                href: '?' + urlParams.toString(),
                class: $button.attr('class'),
                text: $button.text()
            });
            
            // Reemplazar el botón con el enlace
            $button.replaceWith($link);
        });
        
        // Utilizar la función filterProducts solo para filtros, no para paginación
        window.originalFilterProducts = window.filterProducts;
        
      window.filterProducts = function(page) {
    // Asignar valor predeterminado de forma compatible
    page = page || 1;
            // Si estamos en una página de shortcode, redireccionar en lugar de usar AJAX
            if (isShortcodePage) {
                var currentUrl = new URL(window.location.href);
                var urlParams = new URLSearchParams(currentUrl.search);
                
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
                
                // Actualizar parámetros de URL
                if (categoryFilter.length) {
                    urlParams.set('category', categoryFilter.join(','));
                } else {
                    urlParams.delete('category');
                }
                
                if (gradeFilter.length) {
                    urlParams.set('grade', gradeFilter.join(','));
                } else {
                    urlParams.delete('grade');
                }
                
                if (searchQuery) {
                    urlParams.set('s', searchQuery);
                } else {
                    urlParams.delete('s');
                }
                
                if (page > 1) {
                    urlParams.set('paged', page);
                } else {
                    urlParams.delete('paged');
                }
                
                // Redireccionar a la nueva URL
                window.location.href = '?' + urlParams.toString();
                return;
            }
            
            // Si no estamos en una página de shortcode, usar el comportamiento original de AJAX
            window.originalFilterProducts(page);
        };
    }
    
    // Modificar los filtros para que también recarguen la página en lugar de usar AJAX en las páginas de shortcode
    if (isShortcodePage) {
        $('.wc-productos-template .filtro-option input[type="checkbox"]').off('change').on('change', function() {
            filterProducts(1); // Volver a la página 1 al aplicar un filtro
        });
        
        $('.wc-productos-template .productos-search button').off('click').on('click', function(e) {
            e.preventDefault();
            filterProducts(1);
        });
        
        $('.wc-productos-template .productos-search input').off('keyup').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                filterProducts(1);
            }
        });
    }
});
// Forzar disposición en cuadrícula cuando el DOM esté listo
jQuery(document).ready(function($) {
    // Forzar cuadrícula con JavaScript como respaldo al CSS
  function forceGridLayout() {
    $('.wc-productos-template ul.products, .productos-grid').css({
        'display': 'grid',
        'grid-template-columns': 'repeat(auto-fill, minmax(220px, 1fr))',
        'gap': '20px',
        'width': '100%',
        'margin': '0',
        'padding': '0',
        'list-style': 'none',
        'grid-auto-rows': '1fr' // Añadir esta línea
    });
    
    $('.wc-productos-template ul.products li.product, .productos-grid li.product').css({
        'width': '100%', 
        'margin': '0 0 20px 0',
        'float': 'none',
        'clear': 'none',
        'display': 'flex',
        'flex-direction': 'column',
        'height': '100%', // Añadir esta línea
        'visibility': 'visible' // Añadir esta línea
    });
    
    // Media query para móviles (aplicar después de un pequeño retraso)
    if (window.innerWidth <= 480) {
        $('.wc-productos-template ul.products, .productos-grid').css({
            'grid-template-columns': 'repeat(2, 1fr)'
        });
    }
}
    
    // Ejecutar inmediatamente
    forceGridLayout();
    
    // Ejecutar de nuevo después de que se carguen todas las imágenes
    $(window).on('load', forceGridLayout);
    
    // Ejecutar cuando cambie el tamaño de la ventana
    $(window).on('resize', forceGridLayout);
});
// Reemplazar la función limpiarCuartaFila en assets/js/productos-template.js
jQuery(document).ready(function($) {
    function limpiarCuartaFila() {
        // Esperar a que el DOM esté completamente cargado
        setTimeout(function() {
            // Calcular cuántos productos por fila hay actualmente
            var $grid = $('.productos-grid, ul.products');
            if ($grid.length === 0) return;
            
            var productosPorFila = 4; // Valor por defecto
            
            // Determinar por el ancho de la ventana
            if (window.innerWidth <= 480) {
                productosPorFila = 2; // Móviles
            } else if (window.innerWidth <= 768) {
                productosPorFila = 3; // Tablets
            } else if (window.innerWidth <= 991) {
                productosPorFila = 3; // Pantallas medianas
            } else {
                productosPorFila = 4; // Pantallas grandes
            }
            
            // Total de productos para 3 filas exactas
            var maxProductos = productosPorFila * 3;
            
            // Ocultar todos los productos después del número máximo
            $grid.find('li.product:nth-child(n+' + (maxProductos + 1) + ')').hide();
            
            // IMPORTANTE: Eliminar cualquier div.producto-interior huérfano
            // Buscar directamente en el contenedor de productos
            $grid.children('div.producto-interior').remove();
            
            // NUEVO: Buscar en toda la página para eliminar cualquier div.producto-interior huérfano
            // que pudiera estar en la cuarta fila o en cualquier parte del DOM
            $('.page-wrapper div.producto-interior').each(function() {
                var $div = $(this);
                // Si este div no está dentro de un li.product, eliminarlo
                if ($div.parents('li.product').length === 0) {
                    $div.remove();
                }
            });
            
            // NUEVO: Buscar en todo el documento para ser exhaustivos
            $('body div.producto-interior').each(function() {
                var $div = $(this);
                // Si este div no está dentro de un li.product o está después de la tercera fila, eliminarlo
                if ($div.parents('li.product').length === 0 || 
                    $div.parents('li.product').index() >= maxProductos) {
                    $div.remove();
                }
            });
            
            // También verificar después de cada actualización de AJAX
            $(document).ajaxComplete(function() {
                $grid.children('div.producto-interior').remove();
                $('body div.producto-interior').each(function() {
                    var $div = $(this);
                    if ($div.parents('li.product').length === 0 || 
                        $div.parents('li.product').index() >= maxProductos) {
                        $div.remove();
                    }
                });
            });
            
            // Agregar una clase específica al contenedor para limitar la altura
            $grid.addClass('rows-limited');
            
            // Usar grid-template-rows para limitar filas
            $grid.css({
                'grid-template-rows': 'repeat(3, auto)',
                'overflow': 'hidden',
                'max-height': '1200px', // Establecer una altura máxima segura
                'display': 'grid',
                'grid-template-columns': 'repeat(' + productosPorFila + ', 1fr)'
            });
            
            // NUEVO: Agregar un clearfix después de la tercera fila para asegurar que nada aparezca después
            if (!$grid.next().hasClass('grid-clearfix')) {
                $grid.after('<div class="grid-clearfix" style="clear:both; height:0; overflow:hidden;"></div>');
            }
        }, 100);
    }
    
    // Ejecutar al cargar la página
    limpiarCuartaFila();
    
    // Ejecutar después de cualquier petición AJAX
    $(document).ajaxComplete(limpiarCuartaFila);
    
    // Ejecutar cuando se carguen todas las imágenes
    $(window).on('load', limpiarCuartaFila);
    
    // Ejecutar cuando cambie el tamaño de la ventana
    $(window).on('resize', limpiarCuartaFila);
    
    // Ejecutar periódicamente para prevenir elementos añadidos dinámicamente
    setInterval(limpiarCuartaFila, 1000); // Reducido a 1 segundo para mayor frecuencia
});

jQuery(document).ready(function($) {
    // Search functionality
    function handleProductSearch() {
        // Check if we're in a productos template page
        if (!$('.wc-productos-template').length) {
            return;
        }

        // Handle form submission via AJAX
        $('#productos-search-form').on('submit', function(e) {
            // Only intercept if we're using AJAX for filtering
            if (!isShortcodePage) {
                e.preventDefault();
                var searchQuery = $('#productos-search-input').val();
                filterProducts(1, searchQuery);
            }
        });

        // Handle button click
        $('.productos-search button').on('click', function(e) {
            // Only intercept if we're using AJAX for filtering
            if (!isShortcodePage) {
                e.preventDefault();
                var searchQuery = $('#productos-search-input').val();
                filterProducts(1, searchQuery);
            }
        });

        // Handle Enter key press
        $('#productos-search-input').on('keypress', function(e) {
            if (e.which === 13 && !isShortcodePage) {
                e.preventDefault();
                var searchQuery = $(this).val();
                filterProducts(1, searchQuery);
            }
        });
    }

    // Update the filterProducts function to accept a search query parameter
    window.filterProducts = function(page, searchQuery) {
        page = page || 1;
        
        // If we're in a shortcode page, handle via page reload
        if (typeof isShortcodePage !== 'undefined' && isShortcodePage) {
            var currentUrl = new URL(window.location.href);
            var urlParams = new URLSearchParams(currentUrl.search);
            
            // Get filter values
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
            
            // Use the search query parameter if provided, otherwise get from the input
            var searchValue = searchQuery || $('#productos-search-input').val();
            
            // Update URL parameters
            if (categoryFilter.length) {
                urlParams.set('category', categoryFilter.join(','));
            } else {
                urlParams.delete('category');
            }
            
            if (gradeFilter.length) {
                urlParams.set('grade', gradeFilter.join(','));
            } else {
                urlParams.delete('grade');
            }
            
            if (searchValue) {
                urlParams.set('s', searchValue);
            } else {
                urlParams.delete('s');
            }
            
            if (page > 1) {
                urlParams.set('paged', page);
            } else {
                urlParams.delete('paged');
            }
            
            // Redirect to the new URL
            window.location.href = '?' + urlParams.toString();
            return;
        }
        
        // Show loading message
        $('.wc-productos-template .productos-main').append('<div class="loading">' + WCProductosParams.i18n.loading + '</div>');
        
        // Get filter values
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
        
        // Use the search query parameter if provided, otherwise get from the input
        var searchValue = searchQuery || $('#productos-search-input').val();
        
        // AJAX request
        $.ajax({
            url: WCProductosParams.ajaxurl,
            type: 'POST',
            data: {
                action: 'productos_filter',
                nonce: WCProductosParams.nonce,
                page: page,
                category: categoryFilter.join(','),
                grade: gradeFilter.join(','),
                min_volume: minVolume,
                max_volume: maxVolume,
                search: searchValue
            },
            success: function(response) {
                if (response.success) {
                    // Remove loading message
                    $('.wc-productos-template .loading').remove();
                    
                    // Update products and pagination
                    $('.wc-productos-template .productos-grid, .wc-productos-template ul.products').remove();
                    $('.wc-productos-template .productos-pagination').remove();
                    
                    // Append new content
                    $('.wc-productos-template .productos-main').append(response.data.products);
                    $('.wc-productos-template .productos-main').append(response.data.pagination);
                    
                    // Force grid layout again
                    forceGridLayout();
                    
                    // Scroll to top of products
                    $('html, body').animate({
                        scrollTop: $('.wc-productos-template .productos-main').offset().top - 100
                    }, 500);
                    
                    // Update event handlers
                    bindProductEvents();
                }
            },
            error: function() {
                $('.wc-productos-template .loading').html(WCProductosParams.i18n.error);
            }
        });
    };

    // Call the search handler function
    handleProductSearch();
});
