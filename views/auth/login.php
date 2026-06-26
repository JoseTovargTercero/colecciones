<!DOCTYPE html>
<html lang="es">

<head>
    <?php include __DIR__ . '/../partials/head.php'; ?>
</head>

<body class="authentication-bg pb-0" data-layout-config='{"darkMode":false}'>

    <div class="auth-fluid">
        <div class="auth-fluid-form-box">
            <div class="align-items-center d-flex h-100">
                <div class="card-body">

                    <div class="auth-brand text-center text-lg-start mb-4">
                        <a href="index.html" class="logo-dark">
                            <span><img src="public/assets/images/logo-dark.png" alt="" height="18"></span>
                        </a>
                        <a href="index.html" class="logo-light">
                            <span><img src="public/assets/images/logo.png" alt="" height="18"></span>
                        </a>
                    </div>

                    <h4 class="mt-4">Ingresar</h4>
                    <p class="text-muted mb-4">
                        Ingresa tu correo y contraseña para acceder a tu cuenta.
                    </p>

                    <form id="formLogin" method="POST">
                        <div class="mb-3">
                            <label for="emailaddress" class="form-label">
                                Correo electrónico
                            </label>
                            <input class="form-control" type="email" name="email" id="emailaddress" required
                                   placeholder="Ingresa tu correo">
                        </div>

                        <div class="mb-3">
                            <a href="javascript:void(0);" class="text-muted float-end"
                               id="link-forgot-password">
                                <small>¿Olvidaste tu contraseña?</small>
                            </a>
                            <label for="password" class="form-label">
                                Contraseña
                            </label>
                            <input class="form-control" type="password" required name="password" id="password"
                                   placeholder="Ingresa tu contraseña">
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="checkbox-signin">
                                <label class="form-check-label" for="checkbox-signin">
                                    Recordarme
                                </label>
                            </div>
                        </div>

                        <div class="d-grid mb-0 text-center">
                            <button class="btn btn-primary" type="submit">
                                <i class="mdi mdi-login"></i> Ingresar
                            </button>
                        </div>
                    </form>
                    </div> </div> </div>
        <div class="auth-fluid-right text-center" style="display: none;">
            <div class="auth-user-testimonial">
                <h2 class="mb-3"></h2>
                <p class="lead">
                    <i class="mdi mdi-format-quote-open"></i>
                    It's an elegant template. I love it very much!
                    <i class="mdi mdi-format-quote-close"></i>
                </p>
                <p>- Hyper Admin User</p>
            </div> </div>
        </div>
    <div class="modal fade" id="recoverPasswordModal" tabindex="-1" aria-labelledby="recoverPasswordModalLabel"
         aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="recoverPasswordForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="recoverPasswordModalLabel">Recuperar contraseña</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted">
                            Ingresa el correo asociado a tu cuenta y te enviaremos un enlace para restablecer tu contraseña.
                        </p>
                        <div class="mb-3">
                            <label for="recovery-email" class="form-label">Correo electrónico</label>
                            <input type="email" class="form-control" id="recovery-email" name="email"
                                   placeholder="Ingresa tu correo" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
                        <button type="submit" class="btn btn-primary">Enviar enlace</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel"
         aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="changePasswordForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="changePasswordModalLabel">Cambiar contraseña</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted">
                            Ingresa tu nueva contraseña para completar el proceso de recuperación.
                        </p>
                        <div class="mb-3">
                            <label for="new_password_reset" class="form-label">Nueva contraseña</label>
                            <input type="password" class="form-control" id="new_password_reset" name="new_password"
                                   placeholder="Nueva contraseña" required minlength="6">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
                        <button type="submit" class="btn btn-primary">Actualizar contraseña</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="public/assets/js/vendor.min.js"></script>
    <script src="public/assets/js/app.min.js"></script>
    
    <script>
        // Definir variables globales para que los módulos JS las usen
        window.baseUrl = "<?php echo BASE_URL; ?>";

        window.getQueryParam = function(param) {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get(param);
        }
    </script>

    <script type="module" src="public/assets/js/modules/login.js"></script>
</body>

</html>