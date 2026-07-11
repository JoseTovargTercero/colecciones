<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <title>Crear cuenta - Control de Deudas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Registro de nuevos usuarios en el sistema Control de Deudas.">
    <link rel="shortcut icon" href="<?php echo BASE_URL; ?>public/assets/images/favicon.ico">

    <link href="<?php echo BASE_URL; ?>public/assets/css/icons.min.css" rel="stylesheet" type="text/css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <script src="<?php echo BASE_URL; ?>public/assets/js/sweetalert2.all.min.js"></script>

    <script>
        const BASE_URL = "<?php echo BASE_URL; ?>";
        window.baseUrl = "<?php echo BASE_URL; ?>";
    </script>

    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --primary: #696cff;
            --primary-dark: #5558e3;
            --primary-light: #8b8dff;
            --primary-glow: rgba(105, 108, 255, 0.35);
            --glass-bg: rgba(255, 255, 255, 0.10);
            --glass-border: rgba(255, 255, 255, 0.22);
            --glass-shadow: 0 8px 40px rgba(0, 0, 0, 0.30);
            --text-dark: #1a1f36;
            --text-muted: #6b7280;
            --input-bg: rgba(255, 255, 255, 0.92);
            --input-border: rgba(209, 213, 219, 0.80);
            --radius-card: 22px;
            --radius-input: 10px;
        }

        html,
        body {
            height: 100%;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: #0d0e1c;
        }

        .register-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
        }

        .register-wrapper::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image: url('<?php echo BASE_URL; ?>public/assets/images/bg-auth.webp');
            background-size: cover;
            background-position: center;
            filter: brightness(0.45) saturate(1.2);
            z-index: 0;
        }

        .register-wrapper::after {
            content: '';
            position: fixed;
            inset: 0;
            background: linear-gradient(135deg,
                    rgba(13, 14, 28, 0.70) 0%,
                    rgba(105, 108, 255, 0.18) 50%,
                    rgba(13, 14, 28, 0.75) 100%);
            z-index: 1;
        }

        .register-card {
            position: relative;
            z-index: 10;
            display: grid;
            grid-template-columns: 1fr 1fr;
            width: 100%;
            max-width: 880px;
            min-height: 560px;
            border-radius: var(--radius-card);
            overflow: hidden;
            backdrop-filter: blur(22px) saturate(1.5);
            -webkit-backdrop-filter: blur(22px) saturate(1.5);
            border: 1px solid var(--glass-border);
            box-shadow: var(--glass-shadow), 0 0 0 1px rgba(255, 255, 255, 0.05) inset;
            animation: cardEntrance 0.65s cubic-bezier(0.22, 1, 0.36, 1) both;
        }

        @keyframes cardEntrance {
            from {
                opacity: 0;
                transform: translateY(32px) scale(0.97);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .panel-brand {
            position: relative;
            background: rgba(255, 255, 255, 0.06);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem 2.5rem;
            border-right: 1px solid var(--glass-border);
            text-align: center;
            overflow: hidden;
        }

        .panel-brand::before {
            content: '';
            position: absolute;
            top: -60px;
            left: 50%;
            transform: translateX(-50%);
            width: 340px;
            height: 340px;
            background: radial-gradient(circle, rgba(105, 108, 255, 0.22) 0%, transparent 70%);
            pointer-events: none;
        }

        .brand-logo-wrap {
            position: relative;
            width: 96px;
            height: 96px;
            margin-bottom: 1.75rem;
            flex-shrink: 0;
        }

        .brand-logo-wrap::before {
            content: '';
            position: absolute;
            inset: -8px;
            border-radius: 50%;
            background: radial-gradient(circle, var(--primary-glow) 0%, transparent 70%);
            animation: logoPulse 3s ease-in-out infinite;
        }

        @keyframes logoPulse {

            0%,
            100% {
                opacity: 0.6;
                transform: scale(1);
            }

            50% {
                opacity: 1;
                transform: scale(1.08);
            }
        }

        .brand-logo-wrap img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            position: relative;
            z-index: 1;
            filter: drop-shadow(0 4px 20px rgba(105, 108, 255, 0.50));
        }

        .brand-name {
            font-size: 1.15rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #ffffff;
            line-height: 1.3;
            margin-bottom: 0.4rem;
        }

        .brand-sub {
            font-size: 0.875rem;
            font-weight: 400;
            color: rgba(255, 255, 255, 0.65);
            margin-bottom: 1.75rem;
        }

        .brand-divider {
            width: 40px;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--primary), transparent);
            border-radius: 2px;
            margin: 0 auto 1.5rem;
        }

        .brand-tagline {
            font-size: 0.8125rem;
            color: rgba(255, 255, 255, 0.45);
            line-height: 1.6;
            max-width: 220px;
            letter-spacing: 0.01em;
        }

        .panel-form {
            background: rgba(255, 255, 255, 1);
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 3rem 3rem;
        }

        .form-heading {
            font-size: 1.625rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.35rem;
            letter-spacing: -0.02em;
        }

        .form-subheading {
            font-size: 0.875rem;
            color: var(--text-muted);
            margin-bottom: 1.75rem;
            line-height: 1.5;
        }

        .field-group {
            margin-bottom: 1rem;
        }

        .field-label {
            display: block;
            font-size: 0.6875rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 0.45rem;
        }

        .input-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 0.9rem;
            font-size: 1.1rem;
            color: #9ca3af;
            pointer-events: none;
            transition: color 0.2s;
            z-index: 2;
        }

        .field-input {
            width: 100%;
            padding: 0.72rem 0.9rem 0.72rem 2.6rem;
            background: var(--input-bg);
            border: 1.5px solid var(--input-border);
            border-radius: var(--radius-input);
            font-family: inherit;
            font-size: 0.9375rem;
            color: var(--text-dark);
            outline: none;
            transition: border-color 0.22s, box-shadow 0.22s;
            appearance: none;
            -webkit-appearance: none;
        }

        .field-input::placeholder {
            color: #b0b7c3;
            font-weight: 400;
        }

        .field-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3.5px rgba(105, 108, 255, 0.14);
        }

        .field-input:focus+.input-icon,
        .input-wrap:focus-within .input-icon {
            color: var(--primary);
        }

        .btn-toggle-pass {
            position: absolute;
            right: 0.85rem;
            background: none;
            border: none;
            padding: 0.25rem;
            cursor: pointer;
            color: #9ca3af;
            font-size: 1.05rem;
            line-height: 1;
            transition: color 0.2s;
            z-index: 2;
        }

        .btn-toggle-pass:hover {
            color: var(--primary);
        }

        .field-input.has-toggle {
            padding-right: 2.8rem;
        }

        .btn-submit {
            width: 100%;
            padding: 0.85rem 1.5rem;
            margin-top: 0.5rem;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: #fff;
            border: none;
            border-radius: var(--radius-input);
            font-family: inherit;
            font-size: 0.9375rem;
            font-weight: 600;
            letter-spacing: 0.02em;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.55rem;
            transition: transform 0.18s, box-shadow 0.18s, filter 0.18s;
            box-shadow: 0 4px 18px rgba(105, 108, 255, 0.38);
            position: relative;
            overflow: hidden;
        }

        .btn-submit::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.12) 0%, transparent 60%);
            pointer-events: none;
        }

        .btn-submit:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(105, 108, 255, 0.48);
            filter: brightness(1.06);
        }

        .btn-submit:active:not(:disabled) {
            transform: translateY(0);
            box-shadow: 0 3px 12px rgba(105, 108, 255, 0.30);
        }

        .btn-submit:disabled {
            opacity: 0.72;
            cursor: not-allowed;
        }

        .btn-submit .mdi {
            font-size: 1.15rem;
        }

        .spinner-sm {
            width: 1rem;
            height: 1rem;
            border: 2px solid rgba(255, 255, 255, 0.4);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
            flex-shrink: 0;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .auth-link-wrap {
            text-align: center;
            margin-top: 1.2rem;
        }

        .auth-link {
            font-size: 0.8125rem;
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s, text-decoration 0.2s;
            border-bottom: 1px solid transparent;
        }

        .auth-link:hover {
            color: var(--primary-dark);
            border-bottom-color: var(--primary-dark);
        }

        .phone-prefix {
            position: absolute;
            left: 2.6rem;
            font-size: 0.9375rem;
            font-weight: 600;
            color: var(--text-dark);
            pointer-events: none;
            z-index: 2;
            user-select: none;
        }

        .field-input.phone-input {
            padding-left: 4.4rem;
        }

        @media (max-width: 680px) {
            .register-card {
                grid-template-columns: 1fr;
                max-width: 420px;
            }

            .panel-brand {
                padding: 2.25rem 2rem 2rem;
                border-right: none;
                border-bottom: 1px solid var(--glass-border);
            }

            .brand-logo-wrap {
                width: 72px;
                height: 72px;
                margin-bottom: 1.25rem;
            }

            .panel-form {
                padding: 2rem;
            }
        }
    </style>
</head>

<body>
    <div class="register-wrapper">
        <div class="register-card">

            <div class="panel-brand">
                <div class="brand-logo-wrap">
                    <img src="<?php echo BASE_URL; ?>public/assets/images/iso.png" alt="Logo Control de Deudas">
                </div>
                <div class="brand-name">Control de Deudas</div>
                <div class="brand-sub">unidad de ventas</div>
                <div class="brand-divider"></div>
                <p class="brand-tagline">Regístrate para gestionar tus colecciones.</p>
            </div>

            <div class="panel-form">
                <h1 class="form-heading">Crear cuenta</h1>
                <p class="form-subheading">Completa tus datos para registrarte.</p>

                <form id="formRegister" method="POST" novalidate>

                    <div class="field-group">
                        <label for="nombre" class="field-label">Nombre completo</label>
                        <div class="input-wrap">
                            <i class="mdi mdi-account-outline input-icon"></i>
                            <input class="field-input" type="text" name="nombre" id="nombre" required placeholder="Tu nombre" autocomplete="name">
                        </div>
                    </div>

                    <div class="field-group">
                        <label for="email" class="field-label">Correo electronico</label>
                        <div class="input-wrap">
                            <i class="mdi mdi-email-outline input-icon"></i>
                            <input class="field-input" type="email" name="email" id="email" required placeholder="correo@ejemplo.com" autocomplete="email">
                        </div>
                    </div>

                    <div class="field-group">
                        <label for="telefono" class="field-label">Telefono</label>
                        <div class="input-wrap">
                            <i class="mdi mdi-phone-outline input-icon"></i>
                            <span class="phone-prefix">+58</span>
                            <input class="field-input phone-input" type="tel" name="telefono" id="telefono" placeholder="4121234567" autocomplete="tel" maxlength="10" inputmode="numeric">
                        </div>
                    </div>

                    <div class="field-group">
                        <label for="tipo_usuario" class="field-label">Tipo de usuario</label>
                        <div class="input-wrap">
                            <i class="mdi mdi-account-switch input-icon"></i>
                            <select class="field-input" name="tipo_usuario" id="tipo_usuario" required style="padding-left:2.6rem;cursor:pointer;background:var(--input-bg);">
                                <option value="">Selecciona un tipo</option>
                                <option value="vendedor">Vendedor</option>
                                <option value="gerente">Gerente</option>
                            </select>
                        </div>
                        <div id="tipoDescripcion" style="font-size:0.75rem;color:var(--text-muted);margin-top:0.35rem;line-height:1.5;min-height:2.4rem;"></div>
                    </div>

                    <div class="field-group">
                        <label for="contrasena" class="field-label">Contrasena</label>
                        <div class="input-wrap">
                            <i class="mdi mdi-lock-outline input-icon"></i>
                            <input class="field-input has-toggle" type="password" name="contrasena" id="contrasena" required placeholder="Minimo 6 caracteres" autocomplete="new-password">
                            <button type="button" class="btn-toggle-pass" id="togglePassword" aria-label="Mostrar u ocultar contrasena">
                                <i class="mdi mdi-eye-outline" id="togglePasswordIcon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="field-group">
                        <label for="contrasena_confirm" class="field-label">Confirmar contrasena</label>
                        <div class="input-wrap">
                            <i class="mdi mdi-lock-outline input-icon"></i>
                            <input class="field-input has-toggle" type="password" name="contrasena_confirm" id="contrasena_confirm" required placeholder="Repite la contrasena" autocomplete="new-password">
                            <button type="button" class="btn-toggle-pass" id="togglePasswordConfirm" aria-label="Mostrar u ocultar contrasena">
                                <i class="mdi mdi-eye-outline" id="togglePasswordConfirmIcon"></i>
                            </button>
                        </div>
                    </div>

                    <button class="btn-submit" type="submit" id="btn-register">
                        <i class="mdi mdi-account-plus"></i>
                        <span>Crear cuenta</span>
                    </button>
                </form>

                <div class="auth-link-wrap">
                    <a href="<?php echo BASE_URL; ?>login" class="auth-link">¿Ya tienes cuenta? Inicia sesion</a>
                </div>
            </div>

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var tipoSelect = document.getElementById('tipo_usuario');
            var descDiv = document.getElementById('tipoDescripcion');
            var descripciones = {
                'vendedor': 'Trabajas con 5 o menos colecciones, el sistema se orienta a la venta unitaria de los productos, tus clientes son compradores finales.',
                'gerente': 'Trabajas con mas de 5 colecciones (Max 80), el sistema se orienta a la venta de colecciones completas, tus clientes son otros vendedores.'
            };

            function actualizarDesc() {
                var val = tipoSelect.value;
                descDiv.textContent = val && descripciones[val] ? descripciones[val] : '';
            }
            tipoSelect.addEventListener('change', actualizarDesc);
            actualizarDesc();
        });
    </script>

    <script src="<?php echo BASE_URL; ?>public/assets/js/vendor.min.js"></script>

    <script>
        (function() {
            const toggle = document.getElementById('togglePassword');
            const input = document.getElementById('contrasena');
            const icon = document.getElementById('togglePasswordIcon');
            if (toggle && input && icon) {
                toggle.addEventListener('click', function() {
                    const isPass = input.type === 'password';
                    input.type = isPass ? 'text' : 'password';
                    icon.className = isPass ? 'mdi mdi-eye-off-outline' : 'mdi mdi-eye-outline';
                });
            }
        }());
        (function() {
            const toggle = document.getElementById('togglePasswordConfirm');
            const input = document.getElementById('contrasena_confirm');
            const icon = document.getElementById('togglePasswordConfirmIcon');
            if (toggle && input && icon) {
                toggle.addEventListener('click', function() {
                    const isPass = input.type === 'password';
                    input.type = isPass ? 'text' : 'password';
                    icon.className = isPass ? 'mdi mdi-eye-off-outline' : 'mdi mdi-eye-outline';
                });
            }
        }());
    </script>

    <script type="module" src="<?php echo BASE_URL; ?>public/assets/js/modules/register.js"></script>
</body>

</html>