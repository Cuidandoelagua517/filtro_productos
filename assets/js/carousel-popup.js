/**
 * Dynamic Product Carousel - JavaScript para Popup de Login
 * 
 * Este archivo maneja los eventos de clic en productos para usuarios invitados,
 * mostrando el formulario de login cuando intentan ver detalles o precios.
 */

(function($) {
    'use strict';
    
    // Verificar si jQuery y dpcConfig están disponibles
    if (typeof $ === 'undefined') {
        console.error('DPC Login Error: jQuery no está disponible');
        return;
    }
    
    if (typeof dpcConfig === 'undefined') {
        console.error('DPC Login Error: dpcConfig no está definido');
        return;
    }
    
    console.log('DPC Login: Inicializando script de popup de login');
    
    // Objeto global DPCLoginPopup
    const DPCLoginPopup = {
        init: function() {
            console.log('DPC Login: Inicializando DPCLoginPopup');
            
            // Referencias a elementos DOM que se crearán
            this.$overlay = null;
            this.$container = null;
            this.$closeButton = null;
            this.$loginContent = null;
            
            // Crear estructura del popup inmediatamente para estar listos
            this.createPopupStructure();
            
            // Inicializar eventos
            this.initEvents();
            
            // Verificar si hay elementos que ya existen en el DOM que necesitan eventos
            this.checkExistingElements();
            
            console.log('DPC Login: Popup de login inicializado correctamente');
        },
        
        initEvents: function() {
            // Usar variable self para mantener referencia al objeto
            const self = this;
            
            console.log('DPC Login: Inicializando eventos del popup de login');
            
            // Remover cualquier event handler existente para evitar duplicados
            $(document).off('click.dpcLoginPopup', '.dpc-login-to-view, .dpc-guest-button');
            
            // Usar delegación de eventos para enlaces "Ver Precio" y "Ver Detalles"
            $(document).on('click.dpcLoginPopup', '.dpc-login-to-view, .dpc-guest-button', function(e) {
                // Prevenir comportamiento por defecto
                e.preventDefault();
                e.stopPropagation();
                
                console.log('DPC Login: Clic detectado en botón para invitados', this);
                
                const productId = $(this).data('product-id');
                
                if (!productId) {
                    console.error('DPC Login: Error - No se encontró data-product-id en el elemento');
                    return false;
                }
                
                console.log('DPC Login: Abriendo popup para producto ID:', productId);
                
                // Usar self para referencia correcta
                self.openLoginPopup(productId);
                
                // Retornar false para asegurar que no haya navegación
                return false;
            });
            
            console.log('DPC Login: Eventos del popup inicializados correctamente');
        },
        
        checkExistingElements: function() {
            // Verificar si ya hay elementos en el DOM que necesitan eventos
            const $loginButtons = $('.dpc-login-to-view, .dpc-guest-button');
            
            if ($loginButtons.length > 0) {
                console.log('DPC Login: Se encontraron botones existentes:', $loginButtons.length);
            }
        },
        
        createPopupStructure: function() {
            console.log('DPC Login: Creando estructura del popup...');
            
            // Si ya existe, obtener referencias en lugar de recrear
            if ($('.dpc-login-popup-overlay').length > 0) {
                console.log('DPC Login: Estructura del popup ya existe, obteniendo referencias');
                this.$overlay = $('.dpc-login-popup-overlay');
                this.$container = $('.dpc-login-popup-container');
                this.$closeButton = $('.dpc-login-popup-close');
                
                // Inicializar eventos del popup existente
                this.initPopupEvents();
                return;
            }
            
            // Crear estructura HTML del popup
            const popupHTML = `
            <div class="dpc-login-popup-overlay">
                <div class="dpc-login-popup-container">
                    <button class="dpc-login-popup-close" aria-label="Cerrar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                    <div class="dpc-login-popup-loader">
                        <div class="dpc-login-popup-spinner"></div>
                    </div>
                    <div class="dpc-login-popup-inner"></div>
                </div>
            </div>`;
            
            // Añadir al final del body para evitar problemas de z-index
            $('body').append(popupHTML);
            
            // Guardar referencias
            this.$overlay = $('.dpc-login-popup-overlay');
            this.$container = $('.dpc-login-popup-container');
            this.$closeButton = $('.dpc-login-popup-close');
            
            // Inicializar eventos del popup
            this.initPopupEvents();
            
            console.log('DPC Login: Estructura del popup creada exitosamente');
        },
        
        initPopupEvents: function() {
            const self = this;
            
            // Eliminar handlers existentes primero
            this.$closeButton.off('click.dpcLoginPopup');
            this.$overlay.off('click.dpcLoginPopup');
            
            // Cerrar al hacer clic en el botón X
            this.$closeButton.on('click.dpcLoginPopup', function(e) {
                e.preventDefault();
                self.closePopup();
                return false;
            });
            
            // Cerrar al hacer clic fuera del popup
            this.$overlay.on('click.dpcLoginPopup', function(e) {
                if ($(e.target).hasClass('dpc-login-popup-overlay')) {
                    self.closePopup();
                    return false;
                }
            });
            
            // Cerrar con tecla ESC
            $(document).on('keydown.dpcLoginPopup', function(e) {
                if (e.key === 'Escape' && self.$overlay && self.$overlay.hasClass('active')) {
                    self.closePopup();
                }
            });
            
            console.log('DPC Login: Eventos del popup inicializados');
        },
        
        openLoginPopup: function(productId) {
            console.log('DPC Login: Abriendo popup para producto ID:', productId);
            
            // Asegurarse de que tenemos la estructura del popup
            if (!this.$overlay) {
                this.createPopupStructure();
            }
            
            // Mostrar loader
            this.$container.addClass('loading');
            this.$overlay.addClass('active');
            $('body').addClass('dpc-popup-open');
            
            // Cargar formulario de login vía AJAX
            this.loadLoginForm(productId);
        },
        
        loadLoginForm: function(productId) {
            const self = this;
            
            console.log('DPC Login: Cargando formulario de login vía AJAX');
            
            // Verificar que dpcConfig existe y tiene las propiedades necesarias
            if (!dpcConfig || !dpcConfig.ajaxUrl || !dpcConfig.loginFormNonce) {
                console.error('DPC Login: Configuración incompleta', dpcConfig);
                self.showError('Error de configuración. Por favor, recargue la página.');
                return;
            }
            
            // Capturar la URL actual antes de hacer la solicitud AJAX
            const currentUrl = window.location.href;
            
            $.ajax({
                url: dpcConfig.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'dpc_get_login_form',
                    product_id: productId || 0,
                    security: dpcConfig.loginFormNonce,
                    current_url: currentUrl // Pasamos la URL actual al servidor
                },
                timeout: 15000,
                success: function(response) {
                    console.log('DPC Login: Respuesta AJAX recibida', response);
                    
                    if (response && response.success) {
                        // Actualizar contenido del popup
                        self.$container.find('.dpc-login-popup-inner').html(response.data.html);
                        
                        // Establecer URL de redirección inmediatamente
                        $('#login-redirect-url').val(currentUrl);
                        console.log('DPC Login: URL de redirección establecida:', currentUrl);
                        
                        // Inicializar funcionalidades del formulario
                        self.initLoginFormFunctionality();
                        
                        // Ocultar loader
                        self.$container.removeClass('loading');
                    } else {
                        // Mostrar mensaje de error
                        let errorMsg = 'Error al cargar el formulario de login';
                        if (response && response.data && response.data.message) {
                            errorMsg = response.data.message;
                        }
                        self.showError(errorMsg);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('DPC Login: Error AJAX', {
                        status: jqXHR.status,
                        textStatus: textStatus,
                        errorThrown: errorThrown
                    });
                    self.showError('Error de conexión: ' + textStatus);
                },
                complete: function() {
                    // Asegurar que el loader se oculte
                    self.$container.removeClass('loading');
                }
            });
        },
        
        initLoginFormFunctionality: function() {
            console.log('DPC Login: Inicializando funcionalidad del formulario');
            
            // Cambio entre tabs login/registro
            $('.mam-login-tab, .mam-register-tab').on('click', function(e) {
                e.preventDefault();
                
                const target = $(this).attr('href').substring(1); // Quitar #
                
                // Actualizar tabs activos
                $('.mam-login-tab, .mam-register-tab').removeClass('active');
                $(this).addClass('active');
                
                // Mostrar formulario correspondiente
                if (target === 'login') {
                    $('.mam-login-form-wrapper').removeClass('hide');
                    $('.mam-register-form-wrapper').addClass('hide');
                } else {
                    $('.mam-login-form-wrapper').addClass('hide');
                    $('.mam-register-form-wrapper').removeClass('hide');
                }
            });
            
            // Toggle para mostrar/ocultar contraseña
            $('.mam-password-toggle').on('click', function() {
                const $input = $(this).siblings('input');
                const type = $input.attr('type') === 'password' ? 'text' : 'password';
                $input.attr('type', type);
                
                // Cambiar ícono
                $(this).find('.mam-eye-icon').toggleClass('show');
            });
            
            // Formateo de CUIT
            $('#reg_cuit').on('input', function() {
                let value = $(this).val().replace(/[^\d]/g, '');
                if (value.length > 11) {
                    value = value.substr(0, 11);
                }
                if (value.length > 2) {
                    value = value.substr(0, 2) + '-' + value.substr(2);
                }
                if (value.length > 11) {
                    value = value.substr(0, 11) + '-' + value.substr(11);
                }
                $(this).val(value);
            });
            
            // Inicializar eventos de envío de formulario
            this.initLoginFormSubmit();
        },
        
        initLoginFormSubmit: function() {
            const self = this;
            
            // Remover handlers existentes para evitar duplicados
            $('#login-form').off('submit.dpcLoginPopup');
            
            // Manejar envío del formulario de login
            $('#login-form').on('submit.dpcLoginPopup', function(e) {
                e.preventDefault();
                
                const $form = $(this);
                self.$container.addClass('loading');
                
                $.ajax({
                    url: dpcConfig.ajaxUrl,
                    type: 'POST',
                    data: $form.serialize(),
                    success: function(response) {
                        if (response.success) {
                            // Mostrar mensaje de éxito
                            self.showMessage(response.data.message || 'Login exitoso, redirigiendo...', 'success');
                            
                            // Redirigir a la página actual con parámetro para forzar recarga
                            setTimeout(function() {
                                window.location.href = response.data.redirect_url || 
                                    window.location.href + (window.location.href.indexOf('?') > -1 ? '&' : '?') + 'dpc_redirect=1';
                            }, 1000);
                        } else {
                            self.showError(response.data.message || dpcConfig.i18n.loginError || 'Error al iniciar sesión');
                        }
                    },
                    error: function() {
                        self.showError(dpcConfig.i18n.connectionError || 'Error de conexión');
                    },
                    complete: function() {
                        self.$container.removeClass('loading');
                    }
                });
            });
            
            // Manejar envío del formulario de registro (si existe)
            if ($('#register-form').length) {
                $('#register-form').off('submit.dpcLoginPopup');
                
                $('#register-form').on('submit.dpcLoginPopup', function(e) {
                    e.preventDefault();
                    
                    const $form = $(this);
                    self.$container.addClass('loading');
                    
                    $.ajax({
                        url: dpcConfig.ajaxUrl,
                        type: 'POST',
                        data: $form.serialize(),
                        success: function(response) {
                            if (response.success) {
                                // Mostrar mensaje de éxito
                                self.showMessage(response.data.message || 'Registro exitoso, iniciando sesión...', 'success');
                                
                                // Redirigir a la página actual con parámetro para forzar recarga
                                setTimeout(function() {
                                    window.location.href = response.data.redirect_url || 
                                        window.location.href + (window.location.href.indexOf('?') > -1 ? '&' : '?') + 'dpc_redirect=1';
                                }, 1500);
                            } else {
                                self.showError(response.data.message || 'Error al registrarse');
                            }
                        },
                        error: function() {
                            self.showError(dpcConfig.i18n.connectionError || 'Error de conexión');
                        },
                        complete: function() {
                            self.$container.removeClass('loading');
                        }
                    });
                });
            }
        },
        
        showMessage: function(message, type) {
            // Eliminar mensajes previos
            this.$container.find('.dpc-login-popup-message').remove();
            
            // Crear nuevo mensaje
            const $message = $('<div class="dpc-login-popup-message ' + type + '">' + message + '</div>');
            this.$container.append($message);
            
            // Auto-ocultar después de 5 segundos para mensajes de éxito
            if (type === 'success') {
                setTimeout(function() {
                    $message.fadeOut(300, function() {
                        $(this).remove();
                    });
                }, 5000);
            }
        },
        
        showError: function(message) {
            console.error('DPC Login Error:', message);
            
            // Eliminar mensajes previos
            this.$container.find('.dpc-login-popup-message').remove();
            
            // Crear mensaje de error
            const $message = $('<div class="dpc-login-popup-message error">' + message + '</div>');
            this.$container.append($message);
            
            // Si el contenido interno está vacío, mostrar un mensaje de error con botón para reintentar
            if (this.$container.find('.dpc-login-popup-inner').is(':empty')) {
                this.$container.find('.dpc-login-popup-inner').html(
                    '<div style="padding: 30px; text-align: center;">' +
                    '<p style="color: #b91c1c;">' + message + '</p>' +
                    '<button class="dpc-login-popup-retry">Reintentar</button>' +
                    '</div>'
                );
                
                // Añadir evento al botón de reintentar
                const self = this;
                this.$container.find('.dpc-login-popup-retry').on('click', function() {
                    self.loadLoginForm();
                });
            }
            
            // Auto-ocultar mensaje después de 5 segundos
            setTimeout(function() {
                $message.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        },
        
        closePopup: function() {
            if (!this.$overlay) return;
            
            this.$overlay.removeClass('active');
            $('body').removeClass('dpc-popup-open');
            
            // Limpiar contenido después de cerrar con efecto de fade
            setTimeout(() => {
                this.$container.find('.dpc-login-popup-inner').empty();
            }, 300);
        }
    };
    
    // Inicializar en document.ready
    $(document).ready(function() {
        console.log('DPC Login: Document ready - inicializando popup de login');
        DPCLoginPopup.init();
    });

    // Comprobar también en window.load para asegurar inicialiación completa
    $(window).on('load', function() {
        console.log('DPC Login: Window load - verificando inicialización del popup');
        if (!DPCLoginPopup.$overlay) {
            console.log('DPC Login: Popup no inicializado, intentando nuevamente');
            DPCLoginPopup.init();
        } else {
            console.log('DPC Login: Popup ya inicializado, refrescando eventos');
            DPCLoginPopup.initEvents();
        }
    });
    
    // Inicializar cada vez que se cargue un nuevo contenido AJAX
    $(document).ajaxComplete(function(event, xhr, settings) {
        // Solo reinicializar para respuestas que puedan contener botones de producto
        if (settings.url && (settings.url.includes('productos_filter') || settings.url.includes('ajax_search'))) {
            setTimeout(function() {
                console.log('DPC Login: Contenido AJAX completado, refrescando eventos');
                DPCLoginPopup.initEvents();
                DPCLoginPopup.checkExistingElements();
            }, 300);
        }
    });
    
    // Exponer globalmente para debugging
    window.DPCLoginPopup = DPCLoginPopup;
    
})(jQuery);
