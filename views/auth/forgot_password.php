<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <title>Recuperar Contraseña - Control de Deudas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Recupera tu contraseña de acceso al sistema.">
    <link rel="shortcut icon" href="<?php echo BASE_URL; ?>public/assets/images/favicon.ico">

    <!-- MDI Icons -->
    <link href="<?php echo BASE_URL; ?>public/assets/css/icons.min.css" rel="stylesheet" type="text/css">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- SweetAlert2 -->
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

        .login-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
        }

        .login-wrapper::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image: url('<?php echo BASE_URL; ?>public/assets/images/bg-auth.webp');
            background-size: cover;
            background-position: center;
            filter: brightness(0.45) saturate(1.2);
            z-index: 0;
        }

        .login-wrapper::after {
            content: '';
            position: fixed;
            inset: 0;
            background: linear-gradient(135deg,
                    rgba(13, 14, 28, 0.70) 0%,
                    rgba(105, 108, 255, 0.18) 50%,
                    rgba(13, 14, 28, 0.75) 100%);
            z-index: 1;
        }

        .login-card {
            position: relative;
            z-index: 10;
            display: grid;
            grid-template-columns: 1fr 1fr;
            width: 100%;
            max-width: 880px;
            min-height: 520px;
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
            0%, 100% { opacity: 0.6; transform: scale(1); }
            50% { opacity: 1; transform: scale(1.08); }
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
            padding: 3.5rem 3rem;
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
            margin-bottom: 2.25rem;
            line-height: 1.5;
        }

        .field-group {
            margin-bottom: 1.25rem;
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
            padding: 0.78rem 0.9rem 0.78rem 2.6rem;
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
            to { transform: rotate(360deg); }
        }

        .back-link-wrap {
            text-align: center;
            margin-top: 1.4rem;
        }

        .back-link {
            font-size: 0.8125rem;
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s, text-decoration 0.2s;
            border-bottom: 1px solid transparent;
        }

        .back-link:hover {
            color: var(--primary-dark);
            border-bottom-color: var(--primary-dark);
        }

        .success-icon {
            text-align: center;
            padding: 1rem 0;
        }

        .success-icon .mdi {
            font-size: 3.5rem;
            color: #22c55e;
        }

        .success-text {
            font-size: 0.9375rem;
            color: var(--text-dark);
            margin-top: 0.5rem;
            line-height: 1.6;
        }

        #formForgotPassword.hidden,
        #successState.hidden {
            display: none;
        }

        @media (max-width: 680px) {
            .login-card {
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
                padding: 2.25rem 2rem;
            }
        }
    </style>
</head>

<body>
    <div class="login-wrapper">
        <div class="login-card">
            <div class="panel-brand">
                <div class="brand-logo-wrap">
                    <img src="<?php echo BASE_URL; ?>public/assets/images/iso.png" alt="Logo Control de Deudas">
                </div>
                <div class="brand-name">Control de Deudas</div>
                <div class="brand-sub">unidad de ventas</div>
                <div class="brand-divider"></div>
                <p class="brand-tagline">Recupera el acceso a tu cuenta</p>
            </div>

            <div class="panel-form">
                <div id="formContainer">
                    <h1 class="form-heading">Recuperar contraseña</h1>
                    <p class="form-subheading">Ingresa tu correo y te enviaremos un enlace para restablecer tu contraseña.</p>

                    <form id="formForgotPassword" method="POST" novalidate>
                        <div class="field-group">
                            <label for="email" class="field-label">Correo electronico</label>
                            <div class="input-wrap">
                                <i class="mdi mdi-email-outline input-icon"></i>
                                <input
                                    class="field-input"
                                    type="email"
                                    name="email"
                                    id="email"
                                    required
                                    placeholder="correo@ejemplo.com"
                                    autocomplete="email">
                            </div>
                        </div>

                        <button class="btn-submit" type="submit" id="btn-send">
                            <i class="mdi mdi-send"></i>
                            <span>Enviar enlace de recuperacion</span>
                        </button>
                    </form>

                    <div class="back-link-wrap">
                        <a href="<?php echo BASE_URL; ?>login" class="back-link">
                            <i class="mdi mdi-arrow-left"></i> Volver al inicio de sesion
                        </a>
                    </div>
                </div>

                <div id="successState" class="hidden">
                    <div class="success-icon">
                        <i class="mdi mdi-email-check-outline"></i>
                    </div>
                    <h1 class="form-heading" style="text-align:center;">Correo enviado</h1>
                    <p class="success-text">
                        Hemos enviado un enlace de recuperacion a tu correo electronico.
                        Revisa tu bandeja de entrada y sigue las instrucciones.
                    </p>
                    <div class="back-link-wrap" style="margin-top:2rem;">
                        <a href="<?php echo BASE_URL; ?>login" class="back-link">
                            <i class="mdi mdi-arrow-left"></i> Volver al inicio de sesion
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="<?php echo BASE_URL; ?>public/assets/js/vendor.min.js"></script>
    <script type="module" src="<?php echo BASE_URL; ?>public/assets/js/modules/forgot_password.js"></script>
</body>

</html>
