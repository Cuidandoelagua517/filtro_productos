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
        
        window.filterProducts = function(page = 1) {
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
