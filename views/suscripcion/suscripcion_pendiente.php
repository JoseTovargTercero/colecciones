<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pago Pendiente — Colecciones</title>
    <meta name="description" content="Tu pago está en proceso de validación.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="<?= defined('BASE_URL') ? BASE_URL : '/' ?>public/assets/css/icons.min.css" rel="stylesheet" type="text/css">
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --bg: #f4f5f9;
            --surface: #ffffff;
            --card: #ffffff;
            --border: #e0e3e8;
            --accent: #696cff;
            --accent2: #8b8dff;
            --gold: #f59e0b;
            --text: #3a3b45;
            --muted: #858796;
            --green: #10b981;
            --red: #f87171;
            --radius: 18px;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .page-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .badge-expired {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            background: rgba(248, 113, 113, .1);
            color: var(--red);
            border: 1px solid rgba(248, 113, 113, .3);
            padding: .35rem 1rem;
            border-radius: 50px;
            font-size: .8rem;
            font-weight: 600;
            letter-spacing: .05em;
            margin-bottom: 1.2rem;
        }

        .icon-lock {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            animation: shake 3s ease-in-out infinite;
        }

        @keyframes shake {

            0%,
            100% {
                transform: rotate(0);
            }

            5% {
                transform: rotate(-8deg);
            }

            10% {
                transform: rotate(8deg);
            }

            15% {
                transform: rotate(0);
            }
        }

        h1 {
            font-size: clamp(1.8rem, 4vw, 2.4rem);
            font-weight: 800;
            margin-bottom: .6rem;
        }

        h1 span {
            background: linear-gradient(135deg, var(--red), #fb923c);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .subtitle {
            color: var(--muted);
            font-size: 1rem;
            max-width: 460px;
            margin: 0 auto;
        }

        /* Plans */
        .plans {
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
            justify-content: center;
            max-width: 780px;
            margin-bottom: 2rem;
        }

        .plan-card {
            background: var(--card);
            border: 2px solid var(--border);
            border-radius: var(--radius);
            padding: 2.2rem 2rem;
            width: 320px;
            cursor: pointer;
            transition: transform .25s, border-color .25s, box-shadow .25s;
            position: relative;
            overflow: hidden;
        }

        .plan-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at top right, rgba(108, 99, 255, .08), transparent 70%);
            pointer-events: none;
        }

        .plan-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 40px rgba(0, 0, 0, .1);
        }

        .plan-card.selected {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(105, 108, 255, .25), 0 16px 40px rgba(0, 0, 0, .1);
        }

        .plan-card.popular {
            border-color: var(--gold);
        }

        .plan-card.popular::before {
            background: radial-gradient(circle at top right, rgba(245, 158, 11, .08), transparent 70%);
        }

        .plan-card.popular.selected {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(245, 158, 11, .25), 0 16px 40px rgba(0, 0, 0, .1);
        }

        .badge-popular {
            position: absolute;
            top: 1.1rem;
            right: 1.1rem;
            background: linear-gradient(135deg, var(--gold), #fbbf24);
            color: #000;
            font-size: .7rem;
            font-weight: 700;
            padding: .25rem .7rem;
            border-radius: 50px;
            letter-spacing: .06em;
        }

        .plan-name {
            font-size: .8rem;
            font-weight: 600;
            color: var(--muted);
            letter-spacing: .08em;
            text-transform: uppercase;
            margin-bottom: .5rem;
        }

        .plan-price {
            font-size: 2.8rem;
            font-weight: 800;
            line-height: 1;
            margin-bottom: .3rem;
        }

        .plan-price sup {
            font-size: 1.2rem;
            font-weight: 600;
            vertical-align: super;
        }

        .plan-period {
            color: var(--muted);
            font-size: .85rem;
            margin-bottom: 1.4rem;
        }

        .plan-saving {
            color: var(--green);
            font-size: .82rem;
            font-weight: 600;
            margin-bottom: 1.4rem;
        }

        .plan-features {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: .55rem;
        }

        .plan-features li {
            display: flex;
            align-items: center;
            gap: .6rem;
            font-size: .9rem;
        }

        .plan-features li::before {
            content: '✓';
            color: var(--green);
            font-weight: 700;
            flex-shrink: 0;
        }

        .check-icon {
            position: absolute;
            top: 1rem;
            left: 1rem;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            border: 2px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all .2s;
        }

        .plan-card.selected .check-icon {
            background: var(--accent);
            border-color: var(--accent);
        }

        .plan-card.popular.selected .check-icon {
            background: var(--gold);
            border-color: var(--gold);
        }

        .check-icon svg {
            display: none;
        }

        .plan-card.selected .check-icon svg {
            display: block;
        }

        /* Gateway */
        .gateway {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 2rem 2.4rem;
            width: 100%;
            max-width: 660px;
            animation: fadeIn .4s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(12px);
            }

            to {
                opacity: 1;
                transform: none;
            }
        }

        .gateway h2 {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 1.4rem;
        }

        .payment-info {
            background: rgba(105, 108, 255, .05);
            border: 1px solid rgba(105, 108, 255, .15);
            border-radius: 12px;
            padding: 1.1rem 1.4rem;
            margin-bottom: 1.6rem;
            font-size: .88rem;
            line-height: 1.9;
        }

        .payment-info .label {
            color: var(--muted);
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            font-weight: 600;
            margin-bottom: .3rem;
        }

        .payment-info strong {
            color: var(--accent2);
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: .4rem;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        label {
            font-size: .78rem;
            font-weight: 600;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .05em;
        }

        input {
            background: var(--bg);
            border: 1.5px solid var(--border);
            border-radius: 10px;
            color: var(--text);
            font-size: .95rem;
            padding: .7rem 1rem;
            transition: border-color .2s, box-shadow .2s;
            outline: none;
            font-family: inherit;
        }

        input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(105, 108, 255, .15);
        }

        input[readonly] {
            opacity: .6;
            cursor: default;
        }

        .notice {
            background: rgba(248, 113, 113, .08);
            border: 1px solid rgba(248, 113, 113, .2);
            border-radius: 10px;
            padding: .85rem 1.2rem;
            font-size: .85rem;
            color: var(--red);
            margin-bottom: 1.6rem;
            text-align: center;
        }

        .btn-pagar {
            margin-top: 1.2rem;
            width: 100%;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: .85rem 1.5rem;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: transform .18s, box-shadow .18s, opacity .18s;
            letter-spacing: .02em;
        }

        .btn-pagar:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(105, 108, 255, .3);
        }

        .btn-pagar:disabled {
            opacity: .5;
            cursor: not-allowed;
        }

        #feedback {
            margin-top: 1rem;
            font-size: .9rem;
            text-align: center;
            min-height: 1.4rem;
        }

        .ok {
            color: var(--green);
        }

        .err {
            color: var(--red);
        }

        .logout-link {
            margin-top: 2rem;
            text-align: center;
        }

        .logout-link a {
            color: var(--muted);
            font-size: .82rem;
            text-decoration: none;
            border-bottom: 1px dashed var(--border);
        }

        .logout-link a:hover {
            color: var(--text);
        }

        @media (max-width: 640px) {
            .plans {
                flex-direction: column;
                align-items: center;
            }

            .plan-card {
                width: 100%;
                max-width: 360px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <?php
    $planRow   = isset($planes[0]) ? $planes[0] : ['precio_mensual' => 25, 'precio_anual' => 260];
    $pMensual  = number_format($planRow['precio_mensual'], 2);
    $pAnual    = number_format($planRow['precio_anual'],   2);
    $ahorro    = number_format(($planRow['precio_mensual'] * 12) - $planRow['precio_anual'], 2);
    ?>

    <div class="page-header">
        <div class="icon-lock" style="color:var(--accent); "><i class="mdi mdi-clock-outline"></i></div>
        <div class="badge-expired" style="color:var(--accent); background:rgba(105,108,255,0.1); border-color:rgba(105,108,255,0.3)">
            <i class="mdi mdi-information-outline"></i> &nbsp;Validando pago
        </div>
        <h1>Pago en <span>proceso</span></h1>
        <p class="subtitle">Tu pago está siendo verificado por nuestro equipo. Te notificaremos cuando tu acceso sea restablecido.</p>
        <p style="margin-top: 1rem; font-size: 0.9rem; color: var(--muted)">¿Te equivocaste o hubo un error al cargar el pago? <a href="#" onclick="document.getElementById('planCards').style.display='flex'; this.parentElement.style.display='none'; return false;" style="color:var(--accent); text-decoration:none; border-bottom:1px dashed var(--accent);">Cargar un nuevo pago</a>.</p>
    </div>

    <div class="plans" id="planCards" style="display:none">

        <div class="plan-card" id="card-mensual" onclick="seleccionarPlan('mensual')">
            <div class="check-icon">
                <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
                    <path d="M2 6l3 3 5-5" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </div>
            <p class="plan-name">Mensual</p>
            <div class="plan-price"><sup>$</sup><?= $pMensual ?></div>
            <p class="plan-period">por mes · renovación automática</p>
            <ul class="plan-features">
                <li>Acceso completo a todas las funciones</li>
                <li>Soporte incluido</li>
                <li>Sin compromiso de permanencia</li>
            </ul>
        </div>

        <div class="plan-card popular" id="card-anual" onclick="seleccionarPlan('anual')">
            <div class="check-icon">
                <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
                    <path d="M2 6l3 3 5-5" stroke="#000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </div>
            <span class="badge-popular"><i class="mdi mdi-star"></i> MÁS POPULAR</span>
            <p class="plan-name">Anual</p>
            <div class="plan-price" style="background:linear-gradient(135deg,#f59e0b,#fbbf24);-webkit-background-clip:text;-webkit-text-fill-color:transparent"><sup>$</sup><?= $pAnual ?></div>
            <p class="plan-period">por año · un solo pago</p>
            <p class="plan-saving"><i class="mdi mdi-cash-multiple"></i> Ahorra $<?= $ahorro ?> vs. mensual</p>
            <ul class="plan-features">
                <li>Acceso completo a todas las funciones</li>
                <li>Soporte incluido</li>
                <li>Precio fijo garantizado todo el año</li>
            </ul>
        </div>

    </div>

    <div class="gateway" id="gateway" style="display:none">
        <h2><i class="mdi mdi-credit-card"></i> Datos de pago — Pago Móvil</h2>

        <div class="notice">Tu acceso está suspendido hasta confirmar el pago.</div>

        <div class="payment-info">
            <div class="label">Realiza tu transferencia a:</div>
            <div><strong>Banco:</strong> Banesco (0134) &nbsp;|&nbsp; <strong>Teléfono:</strong> 04160679095 &nbsp;|&nbsp; <strong>Cédula:</strong> 27640176</div>
            <div><strong>Cuenta:</strong> 0134-0444-51-4441211410</div>
            <div style="margin-top:.4rem">Monto a pagar: <strong id="infoMonto">—</strong> USD</div>
        </div>

        <form id="formPago" onsubmit="enviarPago(event)">
            <input type="hidden" id="tipoPago" name="tipo_pago" value="">

            <div class="form-grid">
                <div class="form-group">
                    <label for="monto">Monto pagado (USD)</label>
                    <input type="text" id="monto" name="monto" step="0.01" min="1" readonly required>
                </div>
                <div class="form-group">
                    <label for="fecha_pago">Fecha de pago</label>
                    <input type="date" id="fecha_pago" name="fecha_pago" required>
                </div>
                <div class="form-group full">
                    <label for="referencia_pago">Número de operación / referencia</label>
                    <input type="text" id="referencia_pago" name="referencia_pago" placeholder="Ej: 123456789" maxlength="100" required>
                </div>
            </div>

            <button type="submit" class="btn-pagar" id="btnPagar">Confirmar pago y reactivar suscripción</button>
            <div id="feedback"></div>
        </form>
    </div>

    <div class="logout-link">
        <a href="<?= defined('BASE_URL') ? BASE_URL : '/' ?>logout">Cerrar sesión</a>
    </div>

    <script>
        let bcv = "<?php echo $_SESSION['bcv_valor'] ?>";
        bcv = parseFloat(bcv)

        const PRECIOS = {
            mensual: <?= $planRow['precio_mensual'] ?>,
            anual: <?= $planRow['precio_anual'] ?>
        };

        function seleccionarPlan(tipo) {
            document.querySelectorAll('.plan-card').forEach(c => c.classList.remove('selected'));
            document.getElementById('card-' + tipo).classList.add('selected');

            const monto = PRECIOS[tipo];
            let monto_pagar = monto.toFixed(2) * bcv;
            monto_pagar = Intl.NumberFormat('es-ES').format(monto_pagar);

            document.getElementById('monto').value = monto_pagar;
            document.getElementById('tipoPago').value = tipo;
            document.getElementById('infoMonto').textContent = monto.toFixed(2);

            const gw = document.getElementById('gateway');
            if (gw.style.display === 'none') {
                gw.style.display = 'block';
                gw.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }

        async function enviarPago(e) {
            e.preventDefault();
            const btn = document.getElementById('btnPagar');
            const fb = document.getElementById('feedback');
            btn.disabled = true;
            fb.textContent = 'Procesando…';
            fb.className = '';

            const form = new FormData(document.getElementById('formPago'));

            try {
                const resp = await fetch('<?= defined('BASE_URL') ? BASE_URL : '/' ?>suscripcion/pagar', {
                    method: 'POST',
                    body: form
                });
                const data = await resp.json();
                if (data.value) {
                    fb.textContent = data.message;
                    fb.className = 'ok';
                    setTimeout(() => location.reload(), 1800);
                } else {
                    fb.textContent = data.message;
                    fb.className = 'err';
                    btn.disabled = false;
                }
            } catch {
                fb.textContent = 'Error de conexión. Inténtalo de nuevo.';
                fb.className = 'err';
                btn.disabled = false;
            }
        }
    </script>
</body>

</html>