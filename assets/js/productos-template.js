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
 * Script para garantizar la correcta estructura del header y los productos
 * Agregar al archivo assets/js/productos-template.js
 */

jQuery(document).ready(function($) {
    // 1. Verificar que estamos en una página relevante
    if (!$('.wc-productos-template').length) {
        return;
    }

    /**
     * 2. Función principal para corregir el orden de los elementos
     * Esta función asegura que el header del sitio aparezca antes que el contenido de productos
     */
    function fixHeaderStructure() {
        console.log('Corrigiendo estructura del header...');
        
        // 2.1 Referencias a los elementos clave
        var $siteHeader = $('header.site-header, #masthead, .header-container, .main-header').first();
        var $productosContainer = $('.productos-container.wc-productos-template');
        var $siteContent = $('#content, .site-content, main.site-main, .content-area').first();
        
        // 2.2 Si no encontramos el contenedor de productos, no hacemos nada
        if ($productosContainer.length === 0) {
            console.log('No se encontró el contenedor de productos.');
            return;
        }
        
        // 2.3 Si encontramos un header, aseguramos que esté antes del contenedor de productos
        if ($siteHeader.length > 0) {
            console.log('Header encontrado, corrigiendo posición...');
            
            // 2.3.1 Si el header es un hermano del contenedor de productos, reordenar
            if ($siteHeader.parent().is($productosContainer.parent())) {
                if ($siteHeader.index() > $productosContainer.index()) {
                    $siteHeader.insertBefore($productosContainer);
                }
            }
            
            // 2.3.2 Si el header está dentro del contenedor de productos, sacarlo
            if ($siteHeader.parents('.productos-container').length > 0) {
                $siteHeader.insertBefore($productosContainer);
            }
            
            // 2.3.3 Aplicar estilos para garantizar visibilidad correcta
            $siteHeader.css({
                'order': '-2',
                'position': 'relative',
                'z-index': '100',
                'width': '100%'
            });
            
            $productosContainer.css({
                'order': '1',
                'position': 'relative',
                'z-index': '1',
                'width': '100%',
                'margin-top': '20px'
            });
        } else {
            console.log('No se encontró un header estándar. Verificando estructura alternativa...');
        }
        
        // 2.4 Si encontramos un contenedor de contenido principal, asegurar estructura
        if ($siteContent.length > 0) {
            $siteContent.css({
                'display': 'flex',
                'flex-direction': 'column',
                'width': '100%'
            });
        }
        
        // 2.5 Comprobar si el título "Productos" y la barra de búsqueda están correctamente posicionados
        var $productosHeader = $('.productos-header');
        var $productosGrid = $('.productos-grid, ul.products');
        
        if ($productosHeader.length > 0 && $productosGrid.length > 0) {
            // Asegurar que estén en el orden correcto dentro del contenedor
            if ($productosHeader.index() > $productosGrid.index()) {
                $productosHeader.insertBefore($productosGrid);
            }
        }
    }
    
    /**
     * 3. Función para verificar y corregir la estructura del título y barra de búsqueda
     */
    function fixSearchAndTitleSection() {
        // 3.1 Verificar si el título de productos y la barra de búsqueda existen
        var $productosHeader = $('.productos-header');
        var $productosSearch = $('.productos-search');
        
        // 3.2 Si no existe el header de productos, recrearlo
        if ($productosHeader.length === 0) {
            console.log('Recreando el header de productos...');
            $('.productos-container').prepend(
                '<div class="productos-header">' +
                '<h1>' + ($('.woocommerce-products-header__title').text() || 'Productos') + '</h1>' +
                '<div class="productos-search">' +
                '<form role="search" method="get" id="productos-search-form" action="/">' +
                '<input type="text" id="productos-search-input" name="s" placeholder="Buscar por nombre, referencia o características..." value="" />' +
                '<input type="hidden" name="post_type" value="product" />' +
                '<button type="submit" aria-label="Buscar"><i class="fas fa-search"></i></button>' +
                '</form>' +
                '</div>' +
                '</div>'
            );
        } 
        // 3.3 Si existe el header pero no la barra de búsqueda, recrearla
        else if ($productosSearch.length === 0) {
            console.log('Recreando la barra de búsqueda...');
            $productosHeader.append(
                '<div class="productos-search">' +
                '<form role="search" method="get" id="productos-search-form" action="/">' +
                '<input type="text" id="productos-search-input" name="s" placeholder="Buscar por nombre, referencia o características..." value="" />' +
                '<input type="hidden" name="post_type" value="product" />' +
                '<button type="submit" aria-label="Buscar"><i class="fas fa-search"></i></button>' +
                '</form>' +
                '</div>'
            );
        }
        
        // 3.4 Aplicar estilos para garantizar la correcta visualización
        $('.productos-header').css({
            'display': 'flex',
            'flex-wrap': 'wrap',
            'justify-content': 'space-between',
            'align-items': 'center',
            'width': '100%',
            'margin-bottom': '25px',
            'padding-bottom': '10px',
            'border-bottom': '1px solid #e2e2e2'
        });
        
        $('.productos-search').css({
            'position': 'relative',
            'width': '300px',
            'display': 'block',
            'visibility': 'visible',
            'opacity': '1'
        });
        
        // 3.5 Ajustes responsivos
        if (window.innerWidth <= 768) {
            $('.productos-header').css({
                'flex-direction': 'column',
                'align-items': 'flex-start'
            });
            
            $('.productos-header h1').css({
                'margin-bottom': '15px'
            });
            
            $('.productos-search').css({
                'width': '100%',
                'max-width': '100%'
            });
        }
    }
    
    // 4. Ejecutar las correcciones
    // Ejecución inmediata
    fixHeaderStructure();
    fixSearchAndTitleSection();
    
    // 5. Ejecutar después de cada carga de AJAX (puede ser necesario para filtros)
    $(document).ajaxComplete(function() {
        fixHeaderStructure();
        fixSearchAndTitleSection();
    });
    
    // 6. Ejecutar después de que todas las imágenes se hayan cargado
    $(window).on('load', function() {
        fixHeaderStructure();
        fixSearchAndTitleSection();
    });
    
    // 7. Ejecutar cuando cambie el tamaño de la ventana (para ajustes responsivos)
    $(window).on('resize', function() {
        fixSearchAndTitleSection();
    });
    
    // 8. Para mayor seguridad, ejecutar periódicamente durante los primeros segundos
    var safetyInterval = setInterval(function() {
        fixHeaderStructure();
        fixSearchAndTitleSection();
    }, 1000);
    
    // 9. Detener el intervalo después de 10 segundos
    setTimeout(function() {
        clearInterval(safetyInterval);
    }, 10000);
});
