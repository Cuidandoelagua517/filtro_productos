// Añadir al archivo productos-template.js

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
