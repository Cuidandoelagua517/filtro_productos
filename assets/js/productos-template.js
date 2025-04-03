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
/**
 * Fix para la barra de búsqueda de WooCommerce Productos Template
 * 
 * Este script garantiza que la barra de búsqueda siempre aparezca
 * en la UI, incluso cuando haya conflictos de CSS.
 */

jQuery(document).ready(function($) {
    // Función para verificar y reparar la barra de búsqueda
    function fixSearchBar() {
        // Verificar si estamos en una página con el template de productos
        if (!$('.wc-productos-template').length) {
            return;
        }
        
        console.log('Verificando barra de búsqueda...');
        
        // Verificar si existe el header de productos
        var $header = $('.productos-header');
        if ($header.length === 0 || $header.css('display') === 'none') {
            console.log('Header no encontrado, recreando...');
            
            // Recrear el header al inicio del contenedor
            $('.wc-productos-template').prepend(
                '<div class="productos-header" style="display:flex !important; width:100% !important; justify-content:space-between !important; align-items:center !important; margin-bottom:25px !important; visibility:visible !important; opacity:1 !important;">' +
                '<h1>' + ($('.woocommerce-products-header__title').text() || 'Productos') + '</h1>' +
                '<div class="productos-search" style="position:relative !important; width:300px !important; display:block !important; visibility:visible !important; opacity:1 !important; margin:0 !important;">' +
                '<form role="search" method="get" id="productos-search-form" action="/">' +
                '<input type="text" id="productos-search-input" name="s" placeholder="Buscar por nombre, referencia o características..." value="" style="width:100% !important; display:block !important; padding:10px 40px 10px 15px !important; border:1px solid #ddd !important; border-radius:4px !important; visibility:visible !important; opacity:1 !important;" />' +
                '<input type="hidden" name="post_type" value="product" />' +
                '<button type="submit" aria-label="Buscar" style="position:absolute !important; right:0 !important; top:0 !important; height:100% !important; width:40px !important; background-color:#0056b3 !important; color:white !important; border:none !important; border-radius:0 4px 4px 0 !important; cursor:pointer !important; display:block !important; visibility:visible !important; opacity:1 !important;">' +
                '<i class="fas fa-search" aria-hidden="true"></i>' +
                '</button>' +
                '</form>' +
                '</div>' +
                '</div>'
            );
            return;
        }
        
        // Verificar si existe la barra de búsqueda
        var $searchBar = $('.productos-search');
        if ($searchBar.length === 0 || $searchBar.css('display') === 'none' || $searchBar.css('visibility') === 'hidden') {
            console.log('Barra de búsqueda no encontrada, recreando...');
            
            // Recrear la barra de búsqueda dentro del header
            $header.append(
                '<div class="productos-search" style="position:relative !important; width:300px !important; display:block !important; visibility:visible !important; opacity:1 !important; margin:0 !important;">' +
                '<form role="search" method="get" id="productos-search-form" action="/">' +
                '<input type="text" id="productos-search-input" name="s" placeholder="Buscar por nombre, referencia o características..." value="" style="width:100% !important; display:block !important; padding:10px 40px 10px 15px !important; border:1px solid #ddd !important; border-radius:4px !important; visibility:visible !important; opacity:1 !important;" />' +
                '<input type="hidden" name="post_type" value="product" />' +
                '<button type="submit" aria-label="Buscar" style="position:absolute !important; right:0 !important; top:0 !important; height:100% !important; width:40px !important; background-color:#0056b3 !important; color:white !important; border:none !important; border-radius:0 4px 4px 0 !important; cursor:pointer !important; display:block !important; visibility:visible !important; opacity:1 !important;">' +
                '<i class="fas fa-search" aria-hidden="true"></i>' +
                '</button>' +
                '</form>' +
                '</div>'
            );
            return;
        }
        
        // Asegurarse que los elementos internos de la barra de búsqueda estén visibles
        var $form = $searchBar.find('form');
        var $input = $searchBar.find('input[type="text"]');
        var $button = $searchBar.find('button');
        
        if ($form.length === 0 || $input.length === 0 || $button.length === 0 || 
            $form.css('display') === 'none' || $input.css('display') === 'none' || $button.css('display') === 'none') {
            console.log('Elementos de la barra de búsqueda no encontrados, recreando...');
            
            // Reemplazar el contenido completo de la barra de búsqueda
            $searchBar.html(
                '<form role="search" method="get" id="productos-search-form" action="/">' +
                '<input type="text" id="productos-search-input" name="s" placeholder="Buscar por nombre, referencia o características..." value="" style="width:100% !important; display:block !important; padding:10px 40px 10px 15px !important; border:1px solid #ddd !important; border-radius:4px !important; visibility:visible !important; opacity:1 !important;" />' +
                '<input type="hidden" name="post_type" value="product" />' +
                '<button type="submit" aria-label="Buscar" style="position:absolute !important; right:0 !important; top:0 !important; height:100% !important; width:40px !important; background-color:#0056b3 !important; color:white !important; border:none !important; border-radius:0 4px 4px 0 !important; cursor:pointer !important; display:block !important; visibility:visible !important; opacity:1 !important;">' +
                '<i class="fas fa-search" aria-hidden="true"></i>' +
                '</button>' +
                '</form>'
            );
        }
        
        // Asegurarse de que Font Awesome esté cargado para el icono de búsqueda
        if (typeof FontAwesome === 'undefined' && !$('link[href*="font-awesome"]').length) {
            console.log('Font Awesome no detectado, cargando...');
            $('head').append('<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">');
        }
        
        // Forzar estilos críticos para la barra de búsqueda
        $header.css({
            'display': 'flex',
            'flex-wrap': 'wrap',
            'justify-content': 'space-between',
            'align-items': 'center',
            'margin-bottom': '25px',
            'width': '100%',
            'visibility': 'visible',
            'opacity': '1',
            'z-index': '999'
        });
        
        $searchBar.css({
            'position': 'relative',
            'width': '300px',
            'display': 'block',
            'visibility': 'visible',
            'opacity': '1',
            'margin': '0',
            'z-index': '1000'
        });
        
        $form.css({
            'display': 'flex',
            'width': '100%'
        });
        
        $input.css({
            'width': '100%',
            'display': 'block',
            'padding': '10px 40px 10px 15px',
            'border': '1px solid #ddd',
            'border-radius': '4px',
            'visibility': 'visible',
            'opacity': '1'
        });
        
        $button.css({
            'position': 'absolute',
            'right': '0',
            'top': '0',
            'height': '100%',
            'width': '40px',
            'background-color': '#0056b3',
            'color': 'white',
            'border': 'none',
            'border-radius': '0 4px 4px 0',
            'cursor': 'pointer',
            'display': 'block',
            'visibility': 'visible',
            'opacity': '1'
        });
    }
    
    // Ejecutar el fix inmediatamente
    fixSearchBar();
    
    // Ejecutar el fix después de que se cargue la página completamente
    $(window).on('load', fixSearchBar);
    
    // Ejecutar el fix después de cada solicitud AJAX completada
    $(document).ajaxComplete(fixSearchBar);
    
    // Ejecutar el fix periódicamente durante los primeros 10 segundos para mayor seguridad
    var fixInterval = setInterval(function() {
        fixSearchBar();
    }, 1000);
    
    setTimeout(function() {
        clearInterval(fixInterval);
    }, 10000);
});

/**
 * This function specifically targets and fixes the "Productos" heading and search bar position
 * Add this function to your productos-template.js file and make sure it runs on page load
 */
function fixProductosHeaderPosition() {
    console.log('Fixing productos header position...');
    var $ = jQuery;
    
    // 1. Check if we have a misplaced header inside the products grid
    var $misplacedHeader = $('.productos-grid .productos-header, ul.products .productos-header');
    
    if ($misplacedHeader.length > 0) {
        console.log('Found misplaced header inside product grid, moving it...');
        
        // 2. Find the proper container to move it to
        var $container = $('.productos-container, .wc-productos-template').first();
        
        if ($container.length > 0) {
            // 3. Move the header to be the first child of the container
            $misplacedHeader.prependTo($container);
            
            // 4. Apply styles to ensure proper display
            $misplacedHeader.css({
                'position': 'relative',
                'top': 'auto',
                'display': 'flex',
                'width': '100%',
                'margin-bottom': '30px',
                'border-bottom': '1px solid #e2e2e2',
                'padding-bottom': '15px'
            });
            
            // 5. Adjust container padding
            $container.css('padding-top', '0');
        }
    } else {
        console.log('No misplaced header found inside product grid');
        
        // 6. Check if header exists at all, if not we may need to create it
        var $header = $('.productos-header');
        
        if ($header.length === 0) {
            console.log('No header found, creating new one...');
            
            var $container = $('.productos-container, .wc-productos-template').first();
            
            if ($container.length > 0) {
                // Create a new header with search
                var headerHtml = '<div class="productos-header">' +
                    '<h1>Productos</h1>' +
                    '<div class="productos-search">' +
                    '<form role="search" method="get" id="productos-search-form" action="/">' +
                    '<input type="text" id="productos-search-input" name="s" placeholder="Buscar por nombre, referencia o características..." value="" />' +
                    '<input type="hidden" name="post_type" value="product" />' +
                    '<button type="submit" aria-label="Buscar"><i class="fas fa-search"></i></button>' +
                    '</form>' +
                    '</div>' +
                    '</div>';
                
                // Add to container
                $container.prepend(headerHtml);
            }
        }
    }
    
    // 7. Find any "Productos" text node directly inside the grid and remove it
    $('.productos-grid, ul.products').contents().each(function() {
        if (this.nodeType === 3 && this.nodeValue.trim() === 'Productos') {
            $(this).remove();
        }
    });
    
    // 8. Fix any other search bar issues
    var $searchBar = $('.productos-search');
    
    if ($searchBar.length > 0) {
        $searchBar.css({
            'position': 'relative',
            'width': $(window).width() <= 768 ? '100%' : '300px',
            'display': 'block',
            'visibility': 'visible',
            'opacity': '1'
        });
    }
}

// Run the fix when document is ready
jQuery(document).ready(function($) {
    // Wait a moment for all other scripts to initialize
    setTimeout(fixProductosHeaderPosition, 100);
    
    // Also run after AJAX calls
    $(document).ajaxComplete(function() {
        setTimeout(fixProductosHeaderPosition, 100);
    });
    
    // Run periodically for the first few seconds to catch any dynamic changes
    var fixInterval = setInterval(fixProductosHeaderPosition, 1000);
    setTimeout(function() {
        clearInterval(fixInterval);
    }, 5000);
});
