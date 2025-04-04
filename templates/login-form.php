<?php
/**
 * Template para el popup de login/registro
 * 
 * Este template se carga mediante AJAX cuando un usuario no logueado intenta
 * ver el precio o los detalles de un producto.
 * 
 * @package DynamicProductCarousel
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="mam-login-register-container">
    <?php if ('yes' === get_option('woocommerce_enable_myaccount_registration')) : ?>

    <div class="mam-login-register-tabs">
        <a href="#login" class="mam-login-tab active">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
            </svg>
            <?php esc_html_e('Iniciar Sesión', 'dynamic-product-carousel'); ?>
        </a>
        <a href="#register" class="mam-register-tab">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
            </svg>
            <?php esc_html_e('Crear Cuenta', 'dynamic-product-carousel'); ?>
        </a>
    </div>
    <?php endif; ?>

    <div class="mam-login-register-forms">
        <!-- FORMULARIO DE LOGIN -->
        <div class="mam-login-form-wrapper">
            <form class="woocommerce-form woocommerce-form-login login mam-ajax-form" method="post" id="login-form">
                <?php wp_nonce_field('mam-nonce', 'security'); ?>
                <!-- Agregar campo oculto para action -->
                <input type="hidden" name="action" value="mam_ajax_login">
                <input type="hidden" name="redirect" id="login-redirect-url" value="<?php echo esc_url(wp_get_referer() ? wp_get_referer() : home_url()); ?>">

                <?php do_action('woocommerce_login_form_start'); ?>

                <div class="mam-form-row mam-form-row-wide">
                    <label for="username"><?php esc_html_e('Correo electrónico', 'dynamic-product-carousel'); ?> <span class="required">*</span></label>
                    <div class="mam-input-with-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        <input type="email" 
                            class="woocommerce-Input woocommerce-Input--text input-text" 
                            name="username" 
                            id="username" 
                            autocomplete="email" 
                            value="<?php echo (!empty($_POST['username'])) ? esc_attr(wp_unslash($_POST['username'])) : ''; ?>" 
                            placeholder="<?php esc_attr_e('Tu dirección de email', 'dynamic-product-carousel'); ?>"
                            required />
                    </div>
                </div>

                <div class="mam-form-row mam-form-row-wide">
                    <label for="password"><?php esc_html_e('Contraseña', 'dynamic-product-carousel'); ?> <span class="required">*</span></label>
                    <div class="mam-password-field mam-input-with-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        <input class="woocommerce-Input woocommerce-Input--text input-text" 
                            type="password" 
                            name="password" 
                            id="password" 
                            autocomplete="current-password" 
                            placeholder="<?php esc_attr_e('Tu contraseña', 'dynamic-product-carousel'); ?>"
                            required />
                        <span class="mam-password-toggle" role="button" tabindex="0" aria-label="<?php esc_attr_e('Mostrar/ocultar contraseña', 'dynamic-product-carousel'); ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="mam-eye-icon">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </span>
                    </div>
                </div>

                <?php do_action('woocommerce_login_form'); ?>

                <div class="mam-form-row mam-remember-row">
                    <label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme mam-checkbox">
                        <input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever" />
                        <span class="mam-checkbox-label"><?php esc_html_e('Recordarme', 'dynamic-product-carousel'); ?></span>
                    </label>
                    <p class="woocommerce-LostPassword lost_password">
                        <a href="<?php echo esc_url(wp_lostpassword_url()); ?>"><?php esc_html_e('¿Olvidaste tu contraseña?', 'dynamic-product-carousel'); ?></a>
                    </p>
                </div>

                <div class="mam-form-row">
                    <?php wp_nonce_field('woocommerce-login', 'woocommerce-login-nonce'); ?>
                    <button type="submit" class="woocommerce-button button woocommerce-form-login__submit mam-button mam-button-primary" name="login" value="<?php esc_attr_e('Iniciar Sesión', 'dynamic-product-carousel'); ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                        </svg>
                        <?php esc_html_e('Iniciar Sesión', 'dynamic-product-carousel'); ?>
                    </button>
                </div>

                <?php do_action('woocommerce_login_form_end'); ?>

            </form>

            <div class="mam-login-divider">
                <span><?php esc_html_e('O conéctate con', 'dynamic-product-carousel'); ?></span>
            </div>

            <div class="mam-social-login">
                <?php
                // Añadir botones de login social si hay algún plugin compatible
                if (function_exists('wc_social_login_buttons') || function_exists('woocommerce_social_login_buttons')) {
                    if (function_exists('wc_social_login_buttons')) {
                        wc_social_login_buttons();
                    } elseif (function_exists('woocommerce_social_login_buttons')) {
                        woocommerce_social_login_buttons();
                    }
                } else {
                    // Botones de ejemplo (solo visuales)
                    ?>
                    <a href="#" class="mam-social-button mam-google">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 48 48">
                            <path fill="#FFC107" d="M43.611,20.083H42V20H24v8h11.303c-1.649,4.657-6.08,8-11.303,8c-6.627,0-12-5.373-12-12c0-6.627,5.373-12,12-12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C12.955,4,4,12.955,4,24c0,11.045,8.955,20,20,20c11.045,0,20-8.955,20-20C44,22.659,43.862,21.35,43.611,20.083z"></path>
                            <path fill="#FF3D00" d="M6.306,14.691l6.571,4.819C14.655,15.108,18.961,12,24,12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C16.318,4,9.656,8.337,6.306,14.691z"></path>
                            <path fill="#4CAF50" d="M24,44c5.166,0,9.86-1.977,13.409-5.192l-6.19-5.238C29.211,35.091,26.715,36,24,36c-5.202,0-9.619-3.317-11.283-7.946l-6.522,5.025C9.505,39.556,16.227,44,24,44z"></path>
                            <path fill="#1976D2" d="M43.611,20.083H42V20H24v8h11.303c-0.792,2.237-2.231,4.166-4.087,5.571c0.001-0.001,0.002-0.001,0.003-0.002l6.19,5.238C36.971,39.205,44,34,44,24C44,22.659,43.862,21.35,43.611,20.083z"></path>
                        </svg>
                        <?php esc_html_e('Continuar con Google', 'dynamic-product-carousel'); ?>
                    </a>
                    <a href="#" class="mam-social-button mam-facebook">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 48 48">
                            <path fill="#3F51B5" d="M42,37c0,2.762-2.238,5-5,5H11c-2.761,0-5-2.238-5-5V11c0-2.762,2.239-5,5-5h26c2.762,0,5,2.238,5,5V37z"></path>
                            <path fill="#FFF" d="M34.368,25H31v13h-5V25h-3v-4h3v-2.41c0.002-3.508,1.459-5.59,5.592-5.59H35v4h-2.287C31.104,17,31,17.6,31,18.723V21h4L34.368,25z"></path>
                        </svg>
                        <?php esc_html_e('Continuar con Facebook', 'dynamic-product-carousel'); ?>
                    </a>
                    <?php
                }
                ?>
            </div>
        </div>

        <?php if ('yes' === get_option('woocommerce_enable_myaccount_registration')) : ?>

        <!-- FORMULARIO DE REGISTRO -->
        <div class="mam-register-form-wrapper hide">
            <form method="post" class="woocommerce-form woocommerce-form-register register mam-ajax-form" id="register-form">
                <?php wp_nonce_field('mam-nonce', 'security'); ?>
                <input type="hidden" name="action" value="mam_ajax_register">

                <?php do_action('woocommerce_register_form_start'); ?>

                <?php if ('no' === get_option('woocommerce_registration_generate_username')) : ?>
                    <div class="mam-form-row mam-form-row-wide">
                        <label for="reg_username"><?php esc_html_e('Nombre de usuario', 'dynamic-product-carousel'); ?> <span class="required">*</span></label>
                        <div class="mam-input-with-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="reg_username" autocomplete="username" value="<?php echo (!empty($_POST['username'])) ? esc_attr(wp_unslash($_POST['username'])) : ''; ?>" placeholder="<?php esc_attr_e('Usuario para iniciar sesión', 'dynamic-product-carousel'); ?>" />
                        </div>
                    </div>
                <?php endif; ?>

                <div class="mam-form-row mam-form-row-wide">
                    <label for="reg_email"><?php esc_html_e('Correo electrónico', 'dynamic-product-carousel'); ?> <span class="required">*</span></label>
                    <div class="mam-input-with-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        <input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="reg_email" autocomplete="email" value="<?php echo (!empty($_POST['email'])) ? esc_attr(wp_unslash($_POST['email'])) : ''; ?>" placeholder="<?php esc_attr_e('Tu dirección de email', 'dynamic-product-carousel'); ?>" required />
                    </div>
                </div>

                <div class="mam-form-row mam-form-row-wide">
                    <label for="reg_company_name"><?php esc_html_e('Nombre de Empresa', 'dynamic-product-carousel'); ?> <span class="required">*</span></label>
                    <div class="mam-input-with-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="company_name" id="reg_company_name" value="<?php echo (!empty($_POST['company_name'])) ? esc_attr(wp_unslash($_POST['company_name'])) : ''; ?>" placeholder="<?php esc_attr_e('Nombre de tu empresa', 'dynamic-product-carousel'); ?>" required />
                    </div>
                </div>
                
                <div class="mam-form-row mam-form-row-wide">
                    <label for="reg_cuit"><?php esc_html_e('CUIT', 'dynamic-product-carousel'); ?> <span class="required">*</span></label>
                    <div class="mam-input-with-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="cuit" id="reg_cuit" value="<?php echo (!empty($_POST['cuit'])) ? esc_attr(wp_unslash($_POST['cuit'])) : ''; ?>" placeholder="<?php esc_attr_e('Formato: xx-xxxxxxxx-x', 'dynamic-product-carousel'); ?>" required />
                    </div>
                    <span class="mam-input-help-text"><?php esc_html_e('Ingresa el CUIT sin guiones. El formato será aplicado automáticamente.', 'dynamic-product-carousel'); ?></span>
                </div>

                <div class="mam-form-row mam-form-row-first">
                    <label for="reg_first_name"><?php esc_html_e('Nombre', 'dynamic-product-carousel'); ?> <span class="required">*</span></label>
                    <div class="mam-input-with-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="first_name" id="reg_first_name" value="<?php echo (!empty($_POST['first_name'])) ? esc_attr(wp_unslash($_POST['first_name'])) : ''; ?>" placeholder="<?php esc_attr_e('Tu nombre', 'dynamic-product-carousel'); ?>" required />
                    </div>
                </div>

                <div class="mam-form-row mam-form-row-last">
                    <label for="reg_last_name"><?php esc_html_e('Apellidos', 'dynamic-product-carousel'); ?> <span class="required">*</span></label>
                    <div class="mam-input-with-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="last_name" id="reg_last_name" value="<?php echo (!empty($_POST['last_name'])) ? esc_attr(wp_unslash($_POST['last_name'])) : ''; ?>" placeholder="<?php esc_attr_e('Tus apellidos', 'dynamic-product-carousel'); ?>" required />
                    </div>
                </div>

                <div class="mam-form-row mam-form-row-wide">
                    <label for="reg_phone"><?php esc_html_e('Teléfono', 'dynamic-product-carousel'); ?></label>
                    <div class="mam-input-with-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                        <input type="tel" class="woocommerce-Input woocommerce-Input--text input-text" name="phone" id="reg_phone" value="<?php echo (!empty($_POST['phone'])) ? esc_attr(wp_unslash($_POST['phone'])) : ''; ?>" placeholder="<?php esc_attr_e('Tu número de teléfono', 'dynamic-product-carousel'); ?>" />
                    </div>
                </div>

                <?php if ('no' === get_option('woocommerce_registration_generate_password')) : ?>

                    <div class="mam-form-row mam-form-row-wide">
                        <label for="reg_password"><?php esc_html_e('Contraseña', 'dynamic-product-carousel'); ?> <span class="required">*</span></label>
                        <div class="mam-password-field mam-input-with-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            <input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password" id="reg_password" autocomplete="new-password" placeholder="<?php esc_attr_e('Crea una contraseña segura', 'dynamic-product-carousel'); ?>" required />
                            <span class="mam-password-toggle" role="button" tabindex="0" aria-label="<?php esc_attr_e('Mostrar/ocultar contraseña', 'dynamic-product-carousel'); ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="mam-eye-icon">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </span>
                        </div>
                        <div class="mam-password-strength-meter">
                            <div class="mam-password-meter">
                                <div class="mam-password-meter-bar" id="reg-password-strength-meter"></div>
                            </div>
                            <span class="mam-password-strength-text" id="reg-password-strength-text"></span>
                        </div>
                    </div>

                <?php else : ?>

                    <p class="mam-generated-password-notice"><?php esc_html_e('Se enviará una contraseña a tu correo electrónico.', 'dynamic-product-carousel'); ?></p>

                <?php endif; ?>

                <div class="mam-form-row mam-privacy-policy">
                    <label class="woocommerce-form__label woocommerce-form__label-for-checkbox mam-checkbox">
                        <input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox" name="privacy_policy" id="privacy_policy" value="1" required />
                        <span class="mam-checkbox-label"><?php printf(esc_html__('He leído y acepto la %spolitica de privacidad%s', 'dynamic-product-carousel'), '<a href="' . esc_url(get_privacy_policy_url()) . '" target="_blank">', '</a>'); ?></span>
                    </label>
                </div>

                <?php do_action('woocommerce_register_form'); ?>

                <div class="mam-form-row">
                    <?php wp_nonce_field('woocommerce-register', 'woocommerce-register-nonce'); ?>
                    <button type="submit" class="woocommerce-Button woocommerce-button button woocommerce-form-register__submit mam-button mam-button-primary" name="register" value="<?php esc_attr_e('Registrarse', 'dynamic-product-carousel'); ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                        </svg>
                        <?php esc_html_e('Crear Cuenta', 'dynamic-product-carousel'); ?>
                    </button>
                </div>

                <?php do_action('woocommerce_register_form_end'); ?>

            </form>

            <div class="mam-register-benefits">
                <h3><?php esc_html_e('Beneficios de crear una cuenta', 'dynamic-product-carousel'); ?></h3>
                <ul>
                    <li>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <?php esc_html_e('Seguimiento de pedidos en tiempo real', 'dynamic-product-carousel'); ?>
                    </li>
                    <li>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <?php esc_html_e('Proceso de compra más rápido', 'dynamic-product-carousel'); ?>
                    </li>
                    <li>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <?php esc_html_e('Acceso a ofertas exclusivas y descuentos', 'dynamic-product-carousel'); ?>
                    </li>
                    <li>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <?php esc_html_e('Guarda tus direcciones y métodos de pago', 'dynamic-product-carousel'); ?>
                    </li>
                </ul>
            </div>
        </div>

        <?php endif; ?>
    </div>
</div>
