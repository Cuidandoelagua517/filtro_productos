/**
* SOLUCIÓN: JavaScript mejorado para productos-template.js
* Este archivo corrige los problemas de paginación y filtrado
*/

jQuery(document).ready(function($) {
   console.log('Productos Template Script - Versión optimizada');
   
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
    * MODIFICACIÓN: Mejorar función filterProducts para manejar mejor la búsqueda
    */
   var filterProducts = function(page) {
       // Asignar página actual
       page = parseInt(page) || 1;
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
       
       // Debug para verificar qué estamos enviando
       console.log('Enviando filtros:', currentFilters);
       
       // MODIFICADO: Asegurar que el formulario de búsqueda muestre el término buscado
       $('.wc-productos-template .productos-search input').val(currentFilters.search);
       
       // Realizar petición AJAX
       $.ajax({
           url: typeof WCProductosParams !== 'undefined' ? WCProductosParams.ajaxurl : ajaxurl,
           type: 'POST',
           data: {
               action: 'productos_filter',
               nonce: typeof WCProductosParams !== 'undefined' ? WCProductosParams.nonce : '',
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
               
               console.log('Respuesta recibida:', response);
               
               if (response.success) {
                   // Actualizar productos y paginación
                   updateProductGrid(response.data.products);
                   updatePagination(response.data.pagination);
                   
                   // Actualizar el breadcrumb si está disponible
                   if (response.data.breadcrumb) {
                       updateBreadcrumb(response.data.breadcrumb);
                   } else {
                       // Alternativa: actualizar manualmente con la página actual
                       updateBreadcrumbForPagination(currentFilters.page);
                   }
                   
                   // Desplazarse al inicio de los productos con animación suave
                   $('html, body').animate({
                       scrollTop: $('.wc-productos-template .productos-main').offset().top - 100
                   }, 500);
                   
                   // Actualizar estado de URL sin recargar página
                   updateUrlState();
                   
                   // Forzar la cuadrícula y volver a enlazar eventos
                   setTimeout(function() {
                       forceGridLayout();
                       bindPaginationEvents();
                   }, 100);
               } else {
                   // Mostrar mensaje de error
                   showError(typeof WCProductosParams !== 'undefined' ? 
                            WCProductosParams.i18n.error : 
                            'Error al cargar productos. Intente nuevamente.');
               }
           },
           error: function(xhr, status, error) {
               // Eliminar mensaje de carga y mostrar error
               $mainContent.find('.loading').remove();
               console.error('Error AJAX:', status, error);
               showError(typeof WCProductosParams !== 'undefined' ? 
                        WCProductosParams.i18n.error : 
                        'Error al cargar productos. Intente nuevamente.');
           }
       });
   };

   /**
    * MODIFICACIÓN: Unificar eventos de búsqueda para evitar duplicación
    */
   var bindSearchEvents = function() {
       // Eliminar eventos anteriores para evitar duplicados
       $('.wc-productos-template .productos-search form, .wc-productos-template .productos-search-form').off('submit');
       $('.wc-productos-template .productos-search input').off('keypress');
       $('.wc-productos-template .productos-search button, .wc-productos-template .productos-search-button').off('click');
       
       // Búsqueda - formulario
       $('.wc-productos-template .productos-search form, .wc-productos-template .productos-search-form').on('submit', function(e) {
           e.preventDefault();
           console.log('Búsqueda enviada por formulario');
           filterProducts(1); // Volver a página 1 al buscar
       });
       
       // Búsqueda - tecla Enter
       $('.wc-productos-template .productos-search input').on('keypress', function(e) {
           if (e.which === 13) {
               e.preventDefault();
               console.log('Búsqueda enviada con Enter');
               filterProducts(1); // Volver a página 1 al buscar
           }
       });
       
       // Búsqueda - botón
       $('.wc-productos-template .productos-search button, .wc-productos-template .productos-search-button').on('click', function(e) {
           e.preventDefault();
           console.log('Búsqueda enviada con botón');
           filterProducts(1); // Volver a página 1 al buscar
       });
       
       // Debug para verificar que los eventos de búsqueda fueron enlazados
       console.log('Eventos de búsqueda enlazados correctamente');
   };
       
   /**
    * MODIFICACIÓN: Corregir función updateCurrentFilters() para capturar correctamente el término de búsqueda
    */
   var updateCurrentFilters = function() {
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
       
       // Valores de volumen (asegurar que sean números)
       currentFilters.min_volume = parseInt($('.wc-productos-template input[name="min_volume"]').val()) || 100;
       currentFilters.max_volume = parseInt($('.wc-productos-template input[name="max_volume"]').val()) || 5000;
       
       // MODIFICADO: Término de búsqueda (selector más específico y robusto)
       var searchInputValue = '';
       var $searchInput = $('.wc-productos-template #productos-search-input, .wc-productos-template .productos-search input[type="text"], .wc-productos-template .productos-search-form input[name="s"]');
       
       if ($searchInput.length > 0) {
           searchInputValue = $searchInput.val() || '';
       }
       
       currentFilters.search = $.trim(searchInputValue);
       
       // Debug para verificar la captura del término de búsqueda
       console.log('Término de búsqueda capturado:', currentFilters.search);
   };

   /**
    * Actualizar la cuadrícula de productos con el nuevo HTML - CORREGIDA
    */
   var updateProductGrid = function(productsHtml) {
       var $productsWrapper = $('.wc-productos-template .productos-wrapper');
       
       if ($productsWrapper.length) {
           // Eliminar cuadrícula anterior y mensaje de no productos
           $productsWrapper.find('ul.products, .productos-grid, .woocommerce-info, .no-products-found').remove();
           
           // Insertar nuevo HTML
           $productsWrapper.prepend(productsHtml);
       } else {
           // Si no existe el wrapper, crear uno
           var $main = $('.wc-productos-template .productos-main');
           if ($main.length) {
               $main.append('<div class="productos-wrapper">' + productsHtml + '</div>');
           } else {
               // Alternativa si no existe el main ni el wrapper
               var $container = $('.wc-productos-template');
               if ($container.length) {
                   var $productsGrid = $container.find('ul.products, .productos-grid');
                   if ($productsGrid.length) {
                       $productsGrid.replaceWith(productsHtml);
                   } else {
                       $container.append('<div class="productos-wrapper">' + productsHtml + '</div>');
                   }
               }
           }
       }
   };
   
   /**
    * Actualizar el breadcrumb - CORREGIDA
    */
   var updateBreadcrumb = function(breadcrumbHtml) {
       var $breadcrumb = $('.wc-productos-template .productos-breadcrumb');
       if ($breadcrumb.length) {
           $breadcrumb.html(breadcrumbHtml);
       }
   };
   
   /**
 * Función para actualizar el breadcrumb según la página actual - CORREGIDA
 */
var updateBreadcrumbOnPageLoad = function() {
    // Obtener el contenido actual del breadcrumb
    var $navElement = $breadcrumb.find('.woocommerce-breadcrumb');
    if (!$navElement.length) return;
    
    // Verificar si ya existe un elemento de página en el breadcrumb
    var currentText = $navElement.html();
    
    // Si ya existe una referencia a la página, actualizarla
    if (currentText && currentText.includes('Página')) {
        currentText = currentText.replace(/Página\s+\d+/g, 'Página ' + currentPage);
        $navElement.html(currentText);
    } else {
        // Si no existe, añadir la página al final
        currentText = currentText + ' / Página ' + currentPage;
        $navElement.html(currentText);
    }
};
   
   /**
    * Actualizar la paginación - CORREGIDA
    */
   var updatePagination = function(paginationHtml) {
       var $pagination = $('.wc-productos-template .productos-pagination');
       if ($pagination.length) {
           $pagination.replaceWith(paginationHtml);
       } else {
           $('.wc-productos-template .productos-wrapper').append(paginationHtml);
       }
   };
   
   /**
    * Mostrar mensaje de error - CORREGIDA
    */
   var showError = function(message) {
       var $productsWrapper = $('.wc-productos-template .productos-wrapper');
       // Asegurarse de que el wrapper exista
       if (!$productsWrapper.length) {
           $productsWrapper = $('<div class="productos-wrapper"></div>');
           $('.wc-productos-template .productos-main').append($productsWrapper);
       }
       
       // Eliminar cualquier mensaje de error anterior
       $productsWrapper.find('.woocommerce-info, .no-products-found').remove();
       
       // Mostrar el nuevo mensaje de error
       $productsWrapper.html('<div class="woocommerce-info">' + message + '</div>');
   };
   
   /**
    * Forzar disposición en cuadrícula con JavaScript - CORREGIDA
    */
   var forceGridLayout = function() {
       // Asegurarse de que la clase principal esté presente
       $('.wc-productos-template ul.products, .wc-productos-template .productos-grid').addClass('force-grid three-column-grid');
       
       // Establecer estilos explícitamente para asegurar la cuadrícula
       $('.wc-productos-template ul.products, .wc-productos-template .productos-grid').css({
           'display': 'grid',
           'grid-template-columns': 'repeat(3, 1fr)',
           'gap': '20px',
           'width': '100%',
           'max-width': '100%',
           'margin': '0 0 30px 0',
           'padding': '0',
           'list-style': 'none',
           'float': 'none',
           'clear': 'both'
       });
       
       // Estilo para los productos
       $('.wc-productos-template ul.products li.product, .wc-productos-template .productos-grid li.product').css({
           'width': '100%',
           'max-width': '100%',
           'margin': '0 0 20px 0',
           'padding': '0',
           'float': 'none',
           'clear': 'none',
           'box-sizing': 'border-box',
           'display': 'flex',
           'flex-direction': 'column',
           'height': '100%'
       });
       
       // Agregar clases responsive según el ancho de pantalla
       $('body').removeClass('screen-small screen-medium screen-large');
       
       if (window.innerWidth <= 480) {
           $('body').addClass('screen-small');
       } else if (window.innerWidth <= 768) {
           $('body').addClass('screen-medium');
       } else {
           $('body').addClass('screen-large');
       }
   };
   
   /**
    * MODIFICACIÓN: Mejorar función updateUrlState para incluir correctamente el término de búsqueda
    */
   var updateUrlState = function() {
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
       
       // MODIFICADO: Usar 's' como parámetro que es el estándar de WordPress para búsqueda
       if (currentFilters.search) {
           params.set('s', currentFilters.search);
       }
       
       // Actualizar URL sin recargar (usando replaceState para no afectar el historial)
       var newUrl = url.pathname + (params.toString() ? '?' + params.toString() : '');
       window.history.replaceState({}, '', newUrl);
   };

   /**
    * MODIFICACIÓN: Extraer términos de búsqueda de la URL
    */
   var extractSearchFromUrl = function() {
       var urlParams = new URLSearchParams(window.location.search);
       if (urlParams.has('s')) {
           var searchTerm = urlParams.get('s');
           $('.wc-productos-template .productos-search input').val(searchTerm);
           currentFilters.search = searchTerm;
           
           console.log('Término de búsqueda extraído de URL:', searchTerm);
       }
   };
   
   /**
    * Inicializar el slider de volumen - CORREGIDA
    */
   var initVolumeSlider = function() {
       if ($.fn.slider && $('.volumen-range').length) {
           // Obtener valores iniciales
           var initialMin = parseInt($('input[name="min_volume"]').val() || 100);
           var initialMax = parseInt($('input[name="max_volume"]').val() || 5000);
           
           // Destruir el slider si ya está inicializado
           if ($('.volumen-range').hasClass('ui-slider')) {
               $('.volumen-range').slider('destroy');
           }
           
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
   };
   
   /**
    * MODIFICACIÓN: Actualizar la función bindFilterEvents para usar bindSearchEvents
    */
   var bindFilterEvents = function() {
       // Eliminar eventos anteriores para evitar duplicados
       $('.wc-productos-template .filtro-category').off('change');
       $('.wc-productos-template .filtro-grade').off('change');
       
       // Filtros de categoría
       $('.wc-productos-template .filtro-category').on('change', function() {
           console.log('Categoría cambiada');
           filterProducts(1); // Volver a página 1 al cambiar filtro
       });
       
       // Filtros de grado
       $('.wc-productos-template .filtro-grade').on('change', function() {
           console.log('Grado cambiado');
           filterProducts(1); // Volver a página 1 al cambiar filtro
       });
       
       // Enlazar eventos de búsqueda
       bindSearchEvents();
   };
   
   /**
    * Enlazar eventos de paginación - versión CORREGIDA
    */
   var bindPaginationEvents = function() {
       // En lugar de usar delegación general, vamos a ser específicos
       $('.wc-productos-template .productos-pagination .page-number').each(function() {
           var $this = $(this);
           
           // Eliminar eventos anteriores para evitar duplicación
           $this.off('click');
           
           // Agregar el nuevo evento
           $this.on('click', function(e) {
               e.preventDefault();
               var page = $(this).data('page');
               
               console.log('Clic en paginación:', page);
               
               if (page) {
                   // Actualizar inmediatamente el breadcrumb para mejor UX
                   updateBreadcrumbForPagination(page);
                   
                   // Luego filtrar productos
                   filterProducts(page);
               }
               return false;
           });
       });
       
       // Establecer estilo activo para la página actual
       var currentPage = currentFilters.page;
       $('.wc-productos-template .productos-pagination .page-number').removeClass('active');
       $('.wc-productos-template .productos-pagination .page-number[data-page="' + currentPage + '"]').addClass('active');
   };
   
   /**
    * Forzar cuadrícula de tres columnas - CORREGIDA
    */
   var forceThreeColumnGrid = function() {
       // Eliminar clases que puedan interferir
       $('.wc-productos-template ul.products, .productos-grid').removeClass('columns-1 columns-2 columns-4 columns-5 columns-6');
       
       // Aplicar clases para la cuadrícula de 3 columnas
       $('.wc-productos-template ul.products, .productos-grid').addClass('three-column-grid force-grid columns-3');
       
       // Establecer explícitamente grid-template-columns
       $('.wc-productos-template ul.products, .productos-grid').css('grid-template-columns', 'repeat(3, 1fr)');
   };

   /**
    * JavaScript para manejar la expansión/contracción de categorías jerárquicas
    */
 /**
 * JavaScript para manejar la expansión/contracción de categorías jerárquicas
 * VERSIÓN CORREGIDA para evitar selección automática de todas las categorías
 */
var initCategoryFilters = function() {
    console.log('Inicializando filtros de categorías jerárquicas - versión corregida');
    
    // Manejar clic en el icono de expansión
    $('.wc-productos-template .category-toggle').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation(); // Evitar que se propague al checkbox
        
        var categorySlug = $(this).data('category');
        var childrenList = $('#children-' + categorySlug);
        
        // Alternar expansión
        $(this).toggleClass('expanded');
        childrenList.toggleClass('expanded');
        
        // Rotar icono
        if ($(this).hasClass('expanded')) {
            $(this).find('i').removeClass('fa-chevron-down').addClass('fa-chevron-up');
        } else {
            $(this).find('i').removeClass('fa-chevron-up').addClass('fa-chevron-down');
        }
    });
    
    // MODIFICACIÓN: Cambio en el comportamiento de marcar/desmarcar hijos
    // Para evitar que se marquen TODAS las categorías al iniciar
    $('.wc-productos-template .filtro-parent-option .filtro-category').on('change', function() {
        var isChecked = $(this).prop('checked');
        var categorySlug = $(this).val();
        var childrenContainer = $('#children-' + categorySlug);
        
        // Si el padre está seleccionado, expandir automáticamente
        if (isChecked) {
            var toggle = $('.category-toggle[data-category="' + categorySlug + '"]');
            toggle.addClass('expanded');
            toggle.find('i').removeClass('fa-chevron-down').addClass('fa-chevron-up');
            childrenContainer.addClass('expanded');
            
            // IMPORTANTE: Solo marcar hijos si el usuario lo hace manualmente
            // NO marcar automáticamente los hijos cuando se carga la página
            if (window.userInitiatedAction === true) {
                childrenContainer.find('.filtro-child').prop('checked', isChecked);
            }
        } else {
            // Si desmarca el padre, permitir desmarcar los hijos
            if (window.userInitiatedAction === true) {
                childrenContainer.find('.filtro-child').prop('checked', false);
            }
        }
    });
    
    // NUEVA VARIABLE para determinar si el cambio lo inició el usuario
    window.userInitiatedAction = false;
    
    // Marcar cambios futuros como iniciados por el usuario
    $('.wc-productos-template .filtro-category').on('click', function() {
        window.userInitiatedAction = true;
        // Restaurar el valor después del evento
        setTimeout(function() {
            window.userInitiatedAction = false;
        }, 100);
    });
    
    // MODIFICADO: Al cargar, expandir solo las categorías realmente seleccionadas por URL
    // (en lugar de todas las categorías automáticamente)
    var urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('category')) {
        var selectedCategories = urlParams.get('category').split(',');
        
        selectedCategories.forEach(function(slug) {
            var parentCheckbox = $('.filtro-parent-option .filtro-category[value="' + slug + '"]');
            
            if (parentCheckbox.length) {
                // Es un padre, expandir sus hijos
                var toggle = $('.category-toggle[data-category="' + slug + '"]');
                toggle.addClass('expanded');
                toggle.find('i').removeClass('fa-chevron-down').addClass('fa-chevron-up');
                $('#children-' + slug).addClass('expanded');
            } else {
                // Es un hijo, buscar y expandir su padre
                var childCheckbox = $('.filtro-child-option .filtro-category[value="' + slug + '"]');
                if (childCheckbox.length) {
                    var parentContainer = childCheckbox.closest('.filtro-children-list');
                    if (parentContainer.length) {
                        var parentId = parentContainer.attr('id');
                        if (parentId && parentId.startsWith('children-')) {
                            var parentSlug = parentId.replace('children-', '');
                            var parentToggle = $('.category-toggle[data-category="' + parentSlug + '"]');
                            parentToggle.addClass('expanded');
                            parentToggle.find('i').removeClass('fa-chevron-down').addClass('fa-chevron-up');
                            parentContainer.addClass('expanded');
                        }
                    }
                }
            }
        });
    }
};

   /**
    * Inicializar todo - VERSIÓN CORREGIDA
    */
   var init = function() {
       console.log('Inicializando productos template con búsqueda mejorada');
       
       // Forzar cuadrícula desde el inicio
       forceGridLayout();
       forceThreeColumnGrid();
       
       // Inicializar slider de volumen
       initVolumeSlider();
       
       // Enlazar eventos
       bindFilterEvents();
       bindPaginationEvents();
       
       // Extraer filtros de la URL al cargar
       var urlParams = new URLSearchParams(window.location.search);
       
       // Procesar parámetros de la URL
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
       
       // MODIFICADO: Extraer término de búsqueda de la URL (parámetro 's')
       if (urlParams.has('s')) {
           var searchTerm = urlParams.get('s');
           $('.wc-productos-template .productos-search input').val(searchTerm);
           currentFilters.search = searchTerm;
       }
       
       if (urlParams.has('paged')) {
           currentFilters.page = parseInt(urlParams.get('paged'));
       }
       
       // Si hay paginación activa, actualizar el breadcrumb
       if (urlParams.has('paged')) {
           var pageNum = parseInt(urlParams.get('paged'));
           if (pageNum > 1) {
               setTimeout(function() {
                   updateBreadcrumbForPagination(pageNum);
               }, 300);
           }
       }
       
       // Si hay algún filtro o paginación activo, actualizar los productos
       if (urlParams.has('category') || urlParams.has('grade') || 
           urlParams.has('min_volume') || urlParams.has('max_volume') || 
           urlParams.has('s') || urlParams.has('paged')) {
           
           console.log('Hay filtros activos, actualizando productos...');
           
           // Usar timeout para asegurar que los elementos del DOM estén listos
           setTimeout(function() {
               filterProducts(currentFilters.page);
           }, 300);
       }
       
       // Volver a forzar la cuadrícula después de cargar imágenes
       $(window).on('load', function() {
           forceGridLayout();
           forceThreeColumnGrid();
           bindPaginationEvents(); // Reenlazar eventos después de carga completa
       });
       
       // Ajustar cuadrícula al cambiar tamaño de ventana
       $(window).on('resize', function() {
           forceGridLayout();
           forceThreeColumnGrid();
       });
   };
   
   // Iniciar todo
   init();
   
   // Exponer la función a nivel global para otros scripts
   window.filterProducts = filterProducts;
   
   // Asegurar que el script search-bar-fix no interfiera con la paginación
   if (window.forceGridLayout) {
       var originalForceGrid = window.forceGridLayout;
       window.forceGridLayout = function() {
           originalForceGrid();
           bindPaginationEvents(); // Volver a enlazar eventos después de forzar la cuadrícula
       };
   }
});
