/**
 * Módulo de Login para WC Productos Template
 * Maneja el proceso de inicio de sesión y registro mediante popup
 *
 * @package WC_Productos_Template
 */

(function($) {
    'use strict';

    // Objeto principal
    const WCProductosLogin = {
        // Estado global
        state: {
            isLoggedIn: false,
            currentProductId: null,
            $modal: null,
            ajaxRunning: false
        },

        // Inicialización
        init: function() {
            // Verificar si las variables globales están disponibles
            if (typeof WCProductosLogin === 'undefined') {
                console.error('WCProductosLogin: Variables no disponibles');
                return;
            }

            // Establecer estado de sesión
            this.state.isLoggedIn = WCProductosParams.is_logged_in || false;

            // Inicializar eventos
            this.initEvents();

            console.log('WCProductosLogin: Inicializado');
        },

        // Inicializar eventos
        initEvents: function() {
            // Evento para abrir el popup de login
            $('body').on('click', '.wc-productos-login-to-view', function(e) {
                e.preventDefault();

                // Si ya está logueado, no hacer nada
                if (WCProductosLogin.state.isLoggedIn) {
                    return false;
                }

                // Obtener ID del producto
                const productId = $(this).data('product-id');
                WCProductosLogin.openLoginPopup(productId);

                return false;
            });

            // Eventos para el modal de login
            $('body').on('click', '.wc-productos-login-modal-close, .wc-productos-login-modal-overlay', function(e) {
                e.preventDefault();
                WCProductosLogin.closeLoginPopup();
                return false;
            });

            // Cambiar entre las pestañas de login y registro
            $('body').on('click', '.wc-productos-login-tab, .wc-productos-register-tab', function(e) {
                e.preventDefault();

                // Activar pestaña
                $('.wc-productos-login-tab, .wc-productos-register-tab').removeClass('active');
                $(this).addClass('active');

                // Mostrar formulario correspondiente
                if ($(this).hasClass('wc-productos-login-tab')) {
                    $('.wc-productos-login-form-wrapper').show();
                    $('.wc-productos-register-form-wrapper').hide();
                } else {
                    $('.wc-productos-login-form-wrapper').hide();
                    $('.wc-productos-register-form-wrapper').show();
                }

                return false;
            });

            // Manejar envío del formulario de login
            $('body').on('submit', '#wc-productos-login-form', function(e) {
                e.preventDefault();
                WCProductosLogin.processLogin($(this));
                return false;
            });

            // Manejar envío del formulario de registro
            $('body').on('submit', '#wc-productos-register-form', function(e) {
                e.preventDefault();
                WCProductosLogin.processRegister($(this));
                return false;
            });

            // Toggle de visibilidad de contraseña
            $('body').on('click', '.wc-productos-password-toggle', function() {
                const $input = $(this).siblings('input');
                if ($input.attr('type') === 'password') {
                    $input.attr('type', 'text');
                    $(this).find('i').removeClass('fa-eye').addClass('fa-eye-slash');
                } else {
                    $input.attr('type', 'password');
                    $(this).find('i').removeClass('fa-eye-slash').addClass('fa-eye');
                }
            });
        },

        // Abrir popup de login
        openLoginPopup: function(productId) {
            // Guardar ID del producto
            this.state.currentProductId = productId || null;

            // Si ya existe un modal, eliminarlo
            if ($('.wc-productos-login-modal').length) {
                $('.wc-productos-login-modal').remove();
            }

            // Crear estructura del modal
            $('body').append(
                '<div class="wc-productos-login-modal">' +
                '<div class="wc-productos-login-modal-overlay"></div>' +
                '<div class="wc-productos-login-modal-content">' +
                '<button type="button" class="wc-productos-login-modal-close"><i class="fas fa-times"></i></button>' +
                '<div class="wc-productos-login-modal-body">' +
                '<div class="wc-productos-login-loading">' +
                '<div class="wc-productos-loading-spinner"></div>' +
                '<div class="wc-productos-loading-text">' + WCProductosParams.i18n.loading + '</div>' +
                '</div>' +
                '</div>' +
                '</div>' +
                '</div>'
            );

            // Guardar referencia al modal
            this.state.$modal = $('.wc-productos-login-modal');

            // Añadir clase al body para evitar scroll
            $('body').addClass('wc-productos-modal-open');

            // Cargar formulario
            this.loadLoginForm();
        },

        // Cerrar popup de login
        closeLoginPopup: function() {
            // Eliminar modal
            if (this.state.$modal) {
                this.state.$modal.remove();
                this.state.$modal = null;
            }

            // Eliminar clase del body
            $('body').removeClass('wc-productos-modal-open');

            // Resetear estado
            this.state.currentProductId = null;
        },

        // Cargar formulario de login
        loadLoginForm: function() {
            // Si ya hay una solicitud en curso, salir
            if (this.state.ajaxRunning) {
                return;
            }

            // Actualizar estado
            this.state.ajaxRunning = true;

            // Preparar datos para la solicitud AJAX
            const data = {
                action: 'wc_productos_get_login_form',
                security: WCProductosParams.nonce
            };

            // Realizar solicitud AJAX
            $.ajax({
                url: WCProductosParams.ajaxurl,
                type: 'POST',
                data: data,
                success: this.handleLoginFormResponse.bind(this),
                error: this.handleLoginFormError.bind(this),
                complete: function() {
                    // Actualizar estado
                    WCProductosLogin.state.ajaxRunning = false;
                }
            });
        },

        // Manejar respuesta del formulario de login
        handleLoginFormResponse: function(response) {
            if (!response.success) {
                this.handleLoginFormError();
                return;
            }

            // Actualizar contenido del modal
            if (this.state.$modal) {
                this.state.$modal.find('.wc-productos-login-modal-body').html(response.data.html);

                // Si hay un ID de producto, añadirlo como campo oculto
                if (this.state.currentProductId) {
                    $('#login-redirect-url').val(response.data.redirect_url);
                }
            }
        },

        // Manejar error del formulario de login
        handleLoginFormError: function() {
            // Mostrar mensaje de error
            if (this.state.$modal) {
                this.state.$modal.find('.wc-productos-login-modal-body').html(
                    '<div class="wc-productos-login-error">' +
                    '<p>' + WCProductosParams.i18n.connection_error + '</p>' +
                    '<button type="button" class="wc-productos-button wc-productos-login-modal-close">' +
                    WCProductosParams.i18n.close +
                    '</button>' +
                    '</div>'
                );
            }
        },

        // Procesar login
        processLogin: function($form) {
            // Si ya hay una solicitud en curso, salir
            if (this.state.ajaxRunning) {
                return;
            }

            // Validar formulario
            if (!this.validateForm($form)) {
                return;
            }

            // Actualizar estado
            this.state.ajaxRunning = true;

            // Mostrar indicador de carga
            $form.find('button[type="submit"]').prop('disabled', true).addClass('loading');

            // Preparar datos para la solicitud AJAX
            const formData = $form.serialize();

            // Realizar solicitud AJAX
            $.ajax({
                url: WCProductosParams.ajaxurl,
                type: 'POST',
                data: formData,
                success: this.handleLoginResponse.bind(this),
                error: this.handleLoginError.bind(this, $form),
                complete: function() {
                    // Actualizar estado
                    WCProductosLogin.state.ajaxRunning = false;

                    // Habilitar botón
                    $form.find('button[type="submit"]').prop('disabled', false).removeClass('loading');
                }
            });
        },

        // Manejar respuesta de login
        handleLoginResponse: function(response) {
            if (!response.success) {
                // Mostrar mensaje de error
                const errorMessage = response.data.message || WCProductosParams.i18n.login_error;
                this.showFormMessage('#wc-productos-login-form', errorMessage, 'error');
                return;
            }

            // Mostrar mensaje de éxito
            this.showFormMessage('#wc-productos-login-form', response.data.message, 'success');

            // Redirigir después de un breve retraso
            setTimeout(function() {
                window.location.href = response.data.redirect_url;
            }, 1000);
        },

        // Manejar error de login
        handleLoginError: function($form) {
            // Mostrar mensaje de error
            this.showFormMessage('#wc-productos-login-form', WCProductosParams.i18n.connection_error, 'error');
        },

        // Procesar registro
        processRegister: function($form) {
            // Si ya hay una solicitud en curso, salir
            if (this.state.ajaxRunning) {
                return;
            }

            // Validar formulario
            if (!this.validateForm($form)) {
                return;
            }

            // Validar que las contraseñas coincidan
            const password = $form.find('#wc-productos-reg-password').val();
            const passwordConfirm = $form.find('#wc-productos-reg-password-confirm').val();

            if (password !== passwordConfirm) {
                this.showFormMessage('#wc-productos-register-form', WCProductosParams.i18n.password_mismatch, 'error');
                return;
            }

            // Actualizar estado
            this.state.ajaxRunning = true;

            // Mostrar indicador de carga
            $form.find('button[type="submit"]').prop('disabled', true).addClass('loading');

            // Preparar datos para la solicitud AJAX
            const formData = $form.serialize();

            // Realizar solicitud AJAX
            $.ajax({
                url: WCProductosParams.ajaxurl,
                type: 'POST',
                data: formData,
                success: this.handleRegisterResponse.bind(this),
                error: this.handleRegisterError.bind(this, $form),
                complete: function() {
                    // Actualizar estado
                    WCProductosLogin.state.ajaxRunning = false;

                    // Habilitar botón
                    $form.find('button[type="submit"]').prop('disabled', false).removeClass('loading');
                }
            });
        },

        // Manejar respuesta de registro
        handleRegisterResponse: function(response) {
            if (!response.success) {
                // Mostrar mensaje de error
                const errorMessage = response.data.message || WCProductosParams.i18n.register_error;
                this.showFormMessage('#wc-productos-register-form', errorMessage, 'error');
                return;
            }

            // Mostrar mensaje de éxito
            this.showFormMessage('#wc-productos-register-form', response.data.message, 'success');

            // Redirigir después de un breve retraso
            setTimeout(function() {
                window.location.href = response.data.redirect_url;
            }, 1000);
        },

        // Manejar error de registro
        handleRegisterError: function($form) {
            // Mostrar mensaje de error
            this.showFormMessage('#wc-productos-register-form', WCProductosParams.i18n.connection_error, 'error');
        },

        // Validar formulario
        validateForm: function($form) {
            let isValid = true;
            const requiredFields = $form.find('[required]');

            // Verificar campos obligatorios
            requiredFields.each(function() {
                if ($(this).val() === '') {
                    isValid = false;
                    $(this).addClass('wc-productos-input-error');
                } else {
                    $(this).removeClass('wc-productos-input-error');
                }
            });

            // Si hay un campo de email, validar formato
            const emailField = $form.find('input[type="email"]');
            if (emailField.length && emailField.val() !== '') {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(emailField.val())) {
                    isValid = false;
                    emailField.addClass('wc-productos-input-error');
                    this.showFormMessage($form.attr('id'), WCProductosParams.i18n.invalid_email, 'error');
                }
            }

            // Mostrar mensaje si hay campos inválidos
            if (!isValid) {
                this.showFormMessage($form.attr('id'), WCProductosParams.i18n.fill_required, 'error');
            }

            return isValid;
        },

        // Mostrar mensaje en el formulario
        showFormMessage: function(formSelector, message, type) {
            const $form = $(formSelector);
            
            // Eliminar mensajes anteriores
            $form.find('.wc-productos-form-message').remove();
            
            // Añadir nuevo mensaje
            $form.prepend(
                '<div class="wc-productos-form-message wc-productos-form-message-' + type + '">' +
                message +
                '</div>'
            );
            
            // Desplazarse al inicio del formulario
            if (this.state.$modal) {
                this.state.$modal.find('.wc-productos-login-modal-body').scrollTop(0);
            }
        }
    };

    // Iniciar cuando el DOM esté listo
    $(document).ready(function() {
        WCProductosLogin.init();
    });

    // Exponer globalmente
    window.WCProductosLogin = WCProductosLogin;
})(jQuery);
