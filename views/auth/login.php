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

                    <div class="auth-brand text-center mb-4 d-flex flex-column align-items-start">
                        <b>CONTROL DE DEUDAS</b>
                        <small>Unidad de gerentes</small>
                    </div>

                    <h4 class="mt-4">Ingresar</h4>
                    <p class="text-muted mb-4">
                        Ingresa tu correo electrónico y contraseña.
                    </p>

                    <form id="formLogin" method="POST">
                        <div class="mb-3">
                            <label for="email" class="form-label">Correo electrónico</label>
                            <input class="form-control" type="email" name="email" id="email" required
                                placeholder="Correo electrónico">
                        </div>

                        <div class="mb-3">
                            <label for="contrasena" class="form-label">Contraseña</label>
                            <input class="form-control" type="password" required name="contrasena" id="contrasena"
                                placeholder="Ingresa tu contraseña">
                        </div>

                        <div class="d-grid mb-0 text-center">
                            <button class="btn btn-primary" type="submit" id="btn-login">
                                <i class="mdi mdi-login"></i> Ingresar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="auth-fluid-right text-center" style="display: none;">
            <div class="auth-user-testimonial">
                <h2 class="mb-3"></h2>
            </div>
        </div>
    </div>

    <script src="public/assets/js/vendor.min.js"></script>
    <script src="public/assets/js/app.min.js"></script>

    <script>
        window.baseUrl = "<?php echo BASE_URL; ?>";
    </script>

    <script type="module" src="public/assets/js/modules/login.js"></script>
</body>

</html>