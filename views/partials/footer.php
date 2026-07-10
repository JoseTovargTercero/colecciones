<?php include 'footer_scripts.php'; ?>

<!-- Tutorial Floating Component -->
<div id="tutorialOverlay" style="display:none"></div>

<!-- Global POST Loader -->
<div id="globalLoader" style="display:none">
    <div id="glBackdrop"></div>
    <div id="glCard">
        <div id="glRing">
            <div id="glRingInner"></div>
        </div>
        <div id="glText">Guardando...</div>
        <div id="glDots"><span></span><span></span><span></span></div>
    </div>
</div>

<style>
    #globalLoader {
        position: fixed;
        inset: 0;
        z-index: 999999;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    #glBackdrop {
        position: absolute;
        inset: 0;
        background: rgba(10, 10, 30, .45);
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
        transition: opacity .3s;
    }

    #glCard {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 18px;
        padding: 44px 56px 40px;
        background: rgba(255, 255, 255, .92);
        border-radius: 28px;
        box-shadow: 0 24px 80px rgba(0, 0, 0, .16), 0 0 0 1px rgba(255, 255, 255, .08) inset;
        animation: glIn .38s cubic-bezier(.22, 1, .36, 1) both;
    }

    @keyframes glIn {
        from {
            opacity: 0;
            transform: translateY(18px) scale(.97);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    #glRing {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: conic-gradient(from 0deg, #696cff, #a594f9, #696cff);
        animation: glSpin .9s linear infinite;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    #glRingInner {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: #fff;
    }

    @keyframes glSpin {
        to {
            transform: rotate(360deg);
        }
    }

    #glText {
        font-size: .9375rem;
        font-weight: 600;
        color: #1a1f36;
        letter-spacing: .01em;
    }

    #glDots {
        display: flex;
        gap: 5px;
    }

    #glDots span {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #696cff;
        animation: glDot 1.1s ease-in-out infinite;
    }

    #glDots span:nth-child(2) {
        animation-delay: .2s;
    }

    #glDots span:nth-child(3) {
        animation-delay: .4s;
    }

    @keyframes glDot {

        0%,
        80%,
        100% {
            opacity: .2;
            transform: scale(.7);
        }

        40% {
            opacity: 1;
            transform: scale(1);
        }
    }
</style>

<script>
    // Global file size validation (<1MB)
    document.addEventListener('change', function(e) {
        var input = e.target;
        if (input && input.type === 'file' && input.accept && input.accept.indexOf('image') !== -1) {
            for (var i = 0; i < input.files.length; i++) {
                if (input.files[i].size > 1048576) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Foto muy pesada',
                        text: 'La imagen "' + input.files[i].name + '" pesa ' + (input.files[i].size / 1048576).toFixed(1) + ' MB. Debe ser menor a 1 MB.'
                    });
                    input.value = '';
                    break;
                }
            }
        }
    });

    (function() {
        let loaderCount = 0;
        const loader = document.getElementById('globalLoader');

        function showLoader() {
            loaderCount++;
            if (loaderCount > 1) return;
            loader.style.display = '';
        }

        function hideLoader() {
            loaderCount--;
            if (loaderCount > 0) return;
            loader.style.display = 'none';
        }

        const origFetch = window.fetch;
        window.fetch = function(u, opts) {
            opts = opts || {};
            const method = (opts.method || 'GET').toUpperCase();
            if (method !== 'GET') showLoader();
            return origFetch.call(this, u, opts).finally(function() {
                if (method !== 'GET') hideLoader();
            });
        };

        const origOpen = XMLHttpRequest.prototype.open;
        XMLHttpRequest.prototype.open = function(m, u) {
            this._glMethod = m.toUpperCase();
            return origOpen.apply(this, arguments);
        };
        const origSend = XMLHttpRequest.prototype.send;
        XMLHttpRequest.prototype.send = function() {
            if (this._glMethod && this._glMethod !== 'GET') showLoader();
            this.addEventListener('loadend', function() {
                if (this._glMethod && this._glMethod !== 'GET') hideLoader();
            });
            return origSend.apply(this, arguments);
        };
    })();
</script>

<script>
    // Carga diferida de lottie-player solo cuando el tutorial va a mostrarse
    function loadLottiePlayer(cb) {
        if (customElements.get('lottie-player')) {
            cb();
            return;
        }
        const s = document.createElement('script');
        s.src = 'https://unpkg.com/@lottiefiles/lottie-player@2.0.8/dist/lottie-player.js';
        s.onload = cb;
        document.head.appendChild(s);
    }

    document.addEventListener('DOMContentLoaded', function() {
        if (window.location.search.includes('tutorial')) {
            sessionStorage.setItem('tutorial_done', '1');
        }
        if (sessionStorage.getItem('tutorial_done')) return;

        const BASE = '<?= rtrim(BASE_URL, '/') . '/' ?>';
        const overlay = document.getElementById('tutorialOverlay');
        loadLottiePlayer(() => {});

        /* ── Estilos ───────────────────────────────────────────────── */
        const style = document.createElement('style');
        style.textContent = `
            #tutorialBackdrop {
                position: fixed; inset: 0;
                background: rgba(10, 10, 30, 0.52);
                backdrop-filter: blur(3px);
                -webkit-backdrop-filter: blur(3px);
                z-index: 99990;
                opacity: 0;
                transition: opacity 0.28s ease;
                display: none;
            }
            #tutorialBackdrop.t-visible { opacity: 1; }

            #tutorialPanel {
                position: fixed;
                z-index: 99999;
                width: 500px;
                max-width: calc(100vw - 32px);
                background: #fff;
                border-radius: 20px;
                box-shadow: 0 24px 64px rgba(0,0,0,0.18), 0 2px 8px rgba(105,108,255,0.10);
                border: 1px solid rgba(105,108,255,0.12);
                overflow: hidden;
                transition:
                    bottom    0.38s cubic-bezier(0.34,1.56,0.64,1),
                    left      0.38s cubic-bezier(0.34,1.56,0.64,1),
                    top       0.38s cubic-bezier(0.34,1.56,0.64,1),
                    transform 0.38s cubic-bezier(0.34,1.56,0.64,1);
            }

            /* Modal centrado — cuando la URL NO coincide con el paso actual */
            #tutorialPanel.t-centered {
                top: 50%; left: 50%;
                transform: translate(-50%, -50%);
                bottom: auto;
            }

            /* Widget anclado — cuando la URL SI coincide con el paso actual */
            #tutorialPanel.t-anchored {
                bottom: 24px; left: 24px;
                top: auto; transform: none;
                width: 360px;
            }

            /* ── Header ── */
            #tHeader {
                background: linear-gradient(135deg, #696cff 0%, #a594f9 100%);
                padding: 18px 22px;
                display: flex; align-items: center; justify-content: space-between;
            }
            #tHeaderLeft { display: flex; align-items: center; gap: 12px; }
            #tHeaderIcon {
                background: rgba(255,255,255,0.22);
                border-radius: 50%; width: 38px; height: 38px;
                display: flex; align-items: center; justify-content: center;
                font-size: 18px; color: #fff; flex-shrink: 0;
            }
            #tTitle    { color: #fff; font-weight: 700; font-size: 15px; letter-spacing: -0.01em; }
            #tProgress { color: rgba(255,255,255,0.75); font-size: 12px; margin-top: 2px; }
            #tHeaderActions { display: flex; gap: 4px; }
            .t-hbtn {
                background: rgba(255,255,255,0.15); border: none;
                color: rgba(255,255,255,0.9); width: 30px; height: 30px;
                border-radius: 8px; cursor: pointer; font-size: 16px;
                display: flex; align-items: center; justify-content: center;
                transition: background 0.15s;
            }
            .t-hbtn:hover { background: rgba(255,255,255,0.28); }

            /* ── Body ── */
            #tBody { padding: 28px 26px 20px; }

            /* Bienvenida */
            #tWelcome { text-align: center; padding: 8px 0 4px; }
            #tWelcome .tw-icon {
                width: 120px; height: 120px;
                display: flex; align-items: center; justify-content: center;
                margin: 0 auto 16px;
            }
            #tWelcome .tw-icon lottie-player {
                width: 120px; height: 120px;
            }
            #tWelcome h2 {
                font-size: 1.3rem; font-weight: 700; color: #1a1f36;
                margin: 0 0 12px; letter-spacing: -0.02em;
            }
            #tWelcome p {
                font-size: 0.9rem; color: #6b7280; line-height: 1.7;
                margin: 0 auto 24px; max-width: 360px;
            }
            #tWelcome .tw-hint {
                display: flex; align-items: flex-start; gap: 10px;
                background: #f4f3fe; border-radius: 12px;
                padding: 12px 16px; margin-bottom: 28px; text-align: left;
            }
            #tWelcome .tw-hint i  { font-size: 20px; color: #696cff; flex-shrink: 0; margin-top: 1px; }
            #tWelcome .tw-hint span { font-size: 0.8125rem; color: #555; line-height: 1.55; }

            /* Paso actual */
            #tStepIcon {
                width: 56px; height: 56px; border-radius: 14px;
                background: linear-gradient(135deg, rgba(105,108,255,0.10) 0%, rgba(165,148,249,0.10) 100%);
                display: flex; align-items: center; justify-content: center;
                margin-bottom: 16px; font-size: 26px; color: #696cff;
            }
            #tStepLabel {
                font-size: 0.6875rem; font-weight: 600; letter-spacing: 0.08em;
                text-transform: uppercase; color: #696cff; margin-bottom: 6px;
            }
            #tStepTitle {
                font-size: 1.05rem; font-weight: 700; color: #1a1f36;
                margin-bottom: 8px; letter-spacing: -0.01em;
            }
            #tStepDesc  { font-size: 0.875rem; color: #6b7280; line-height: 1.6; margin-bottom: 14px; }
            #tStepDetails {
                font-size: 0.8125rem; color: #696cff; line-height: 1.55;
                padding: 10px 14px; background: #f4f3fe;
                border-radius: 10px; border-left: 3px solid #696cff; margin-bottom: 14px;
            }

            /* Boton CTA */
            #tStepBtn {
                display: flex; align-items: center; justify-content: center; gap: 8px;
                width: 100%; padding: 12px 20px;
                background: linear-gradient(135deg, #696cff 0%, #5558e3 100%);
                color: #fff !important; border: none; border-radius: 10px;
                font-size: 0.9rem; font-weight: 600; letter-spacing: 0.01em;
                cursor: pointer; text-decoration: none;
                transition: transform 0.15s, box-shadow 0.15s, filter 0.15s;
                box-shadow: 0 4px 16px rgba(105,108,255,0.35);
                position: relative; overflow: hidden;
            }
            #tStepBtn::after {
                content: ''; position: absolute; inset: 0;
                background: linear-gradient(180deg, rgba(255,255,255,0.10) 0%, transparent 55%);
                pointer-events: none;
            }
            #tStepBtn:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(105,108,255,0.45); filter: brightness(1.06); }

            /* Boton "Continuar" — cuando ya esta en la pagina del paso */
            #tStepBtn.t-current {
                background: linear-gradient(135deg, #28a745 0%, #20883a 100%);
                box-shadow: 0 4px 16px rgba(40,167,69,0.35);
            }
            #tStepBtn.t-current:hover { box-shadow: 0 8px 24px rgba(40,167,69,0.45); }

            /* Boton de inicio (bienvenida) */
            #tStartBtn {
                display: inline-flex; align-items: center; gap: 8px;
                padding: 12px 36px;
                background: linear-gradient(135deg, #696cff 0%, #5558e3 100%);
                color: #fff; border: none; border-radius: 10px;
                font-size: 0.9rem; font-weight: 600; cursor: pointer;
                box-shadow: 0 4px 16px rgba(105,108,255,0.35);
                transition: transform 0.15s, box-shadow 0.15s;
                position: relative; overflow: hidden;
            }
            #tStartBtn::after {
                content: ''; position: absolute; inset: 0;
                background: linear-gradient(180deg, rgba(255,255,255,0.10) 0%, transparent 55%);
                pointer-events: none;
            }
            #tStartBtn:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(105,108,255,0.45); }

            /* Completado */
            #tCompleted { text-align: center; padding: 8px 0 4px; }
            #tCompleted .tc-icon {
                width: 76px; height: 76px; border-radius: 50%;
                background: linear-gradient(135deg, rgba(40,167,69,0.12), rgba(40,167,69,0.06));
                display: flex; align-items: center; justify-content: center;
                margin: 0 auto 16px; font-size: 34px; color: #28a745;
            }
            #tCompleted h3 { font-size: 1.15rem; font-weight: 700; color: #1a1f36; margin-bottom: 6px; }
            #tCompleted p  { font-size: 0.875rem; color: #6b7280; margin: 0; line-height: 1.6; }

            /* Dots */
            #tFooter { display: flex; align-items: center; justify-content: center; gap: 6px; padding: 0 26px 22px; }
            .t-dot {
                width: 8px; height: 8px; border-radius: 50%;
                background: #e2e3ff; transition: all 0.22s ease;
            }
            .t-dot.t-done   { background: #696cff; opacity: 0.4; }
            .t-dot.t-active { background: #696cff; width: 22px; border-radius: 4px; }
            .t-dot.t-success{ background: #28a745; opacity: 0.7; }

            /* Minimizado */
            #tutorialPanel.t-minimized #tBody,
            #tutorialPanel.t-minimized #tFooter { display: none !important; }
        `;
        document.head.appendChild(style);

        /* ── Backdrop ─────────────────────────────────────────────── */
        const backdrop = document.createElement('div');
        backdrop.id = 'tutorialBackdrop';
        document.body.appendChild(backdrop);

        /* ── HTML del panel ───────────────────────────────────────── */
        overlay.innerHTML = `
            <div id="tutorialPanel">
                <div id="tHeader">
                    <div id="tHeaderLeft">
                        <div id="tHeaderIcon"><i class="mdi mdi-lightbulb-outline"></i></div>
                        <div>
                            <div id="tTitle">Tutorial de inicio</div>
                            <div id="tProgress">Bienvenida</div>
                        </div>
                    </div>
                    <div id="tHeaderActions">
                        <button class="t-hbtn" id="tBtnMin" title="Minimizar">
                            <i class="mdi mdi-minus" id="tMinIcon"></i>
                        </button>
                        <button class="t-hbtn" id="tBtnClose" title="Cerrar">
                            <i class="mdi mdi-close"></i>
                        </button>
                    </div>
                </div>

                <div id="tBody">
                    <!-- Bienvenida -->
                    <div id="tWelcome">
                        <div class="tw-icon">
                            <lottie-player
                                id="tLottieWave"
                                src="https://lottie.host/39760b47-9c72-4e28-bec5-16de122db2d6/MKnAggVw9F.json"
                                background="transparent"
                                speed="0.85"
                                loop
                                autoplay
                            ></lottie-player>
                        </div>
                        <h2>Bienvenido al sistema</h2>
                        <p>Para ayudarte a comenzar, te guiaremos paso a paso por los modulos clave. Cada paso te indicara exactamente que registrar y donde hacerlo.</p>
                        <div class="tw-hint">
                            <i class="mdi mdi-map-marker-path"></i>
                            <span>Sigue los pasos en orden. El sistema detectara automaticamente cuando completes cada uno y avanzara al siguiente.</span>
                        </div>
                        <button id="tStartBtn">
                            Comenzar tutorial
                            <i class="mdi mdi-arrow-right"></i>
                        </button>
                    </div>

                    <!-- Paso actual -->
                    <div id="tStepWrap" style="display:none">
                        <div class="text-center">
                        <div id="tStepIcon" style="margin: auto;margin-bottom: 12px;"></div>
                        <div id="tStepLabel">Paso</div>
                        </div>
                        <div id="tStepTitle">Cargando...</div>
                        <div id="tStepDesc"></div>
                        <div id="tStepDetails" style="display:none"></div>
                        <a id="tStepBtn" href="#">Ir al modulo</a>
                    </div>

                    <!-- Completado -->
                    <div id="tCompleted" style="display:none">
                        <div class="tc-icon"><i class="mdi mdi-check-circle-outline"></i></div>
                        <h3>Tutorial completado</h3>
                        <p>Ya completaste todos los pasos iniciales.<br>Puedes explorar el sistema libremente.</p>
                    </div>
                </div>

                <div id="tFooter"></div>
            </div>
        `;

        /* ── Referencias ──────────────────────────────────────────── */
        const panel = document.getElementById('tutorialPanel');
        const tProgress = document.getElementById('tProgress');
        const tWelcome = document.getElementById('tWelcome');
        const tStepWrap = document.getElementById('tStepWrap');
        const tCompleted = document.getElementById('tCompleted');
        const tStepIcon = document.getElementById('tStepIcon');
        const tStepLabel = document.getElementById('tStepLabel');
        const tStepTitle = document.getElementById('tStepTitle');
        const tStepDesc = document.getElementById('tStepDesc');
        const tStepDetails = document.getElementById('tStepDetails');
        const tStepBtn = document.getElementById('tStepBtn');
        const tStartBtn = document.getElementById('tStartBtn');
        const tFooter = document.getElementById('tFooter');
        const tBtnMin = document.getElementById('tBtnMin');
        const tMinIcon = document.getElementById('tMinIcon');
        const tBtnClose = document.getElementById('tBtnClose');

        const ICONS = [
            '<i class="mdi mdi-domain"></i>',
            '<i class="mdi mdi-calendar-clock"></i>',
            '<i class="mdi mdi-package-variant-closed"></i>',
            '<i class="mdi mdi-account-group"></i>',
            '<i class="mdi mdi-handshake"></i>'
        ];

        let tutorialState = null;
        let isMinimized = false;

        /* ── Utilidades ───────────────────────────────────────────── */
        function currentPageMatches(url) {
            const path = window.location.pathname.replace(/\/+$/, '');
            const base = BASE.replace(/\/+$/, '');
            return path === base + '/' + url || path.endsWith('/' + url);
        }

        function showBackdrop() {
            backdrop.style.display = 'block';
            requestAnimationFrame(() => backdrop.classList.add('t-visible'));
        }

        function hideBackdrop() {
            backdrop.classList.remove('t-visible');
            setTimeout(() => {
                backdrop.style.display = 'none';
            }, 300);
        }

        function setPosition(onPage) {
            if (onPage) {
                panel.classList.remove('t-centered');
                panel.classList.add('t-anchored');
                hideBackdrop();
            } else {
                showBackdrop();
                panel.classList.remove('t-anchored');
                panel.classList.add('t-centered');
            }
        }

        function renderDots(d, allDone) {
            tFooter.innerHTML = d.steps.map((_, i) => {
                let cls = 't-dot';
                if (allDone) cls += ' t-success';
                else if (i < d.currentStep) cls += ' t-done';
                else if (i === d.currentStep) cls += ' t-active';
                return `<span class="${cls}"></span>`;
            }).join('');
        }

        /* ── Render paso ──────────────────────────────────────────── */
        function renderStep(d) {
            const step = d.steps[d.currentStep];
            if (!step) {
                overlay.style.display = 'none';
                return;
            }

            const onPage = currentPageMatches(step.url);

            tWelcome.style.display = 'none';
            tCompleted.style.display = 'none';
            tStepWrap.style.display = '';
            overlay.style.display = '';

            tProgress.textContent = `Paso ${d.currentStep + 1} de ${d.totalSteps}`;
            tStepIcon.innerHTML = ICONS[d.currentStep] || '<i class="mdi mdi-clipboard-list-outline"></i>';
            tStepLabel.textContent = `Paso ${d.currentStep + 1} de ${d.totalSteps}`;
            tStepTitle.textContent = step.label;
            tStepDesc.innerHTML = step.desc;

            if (step.details && onPage) {
                tStepDetails.innerHTML = step.details;
                tStepDetails.style.display = '';
            } else {
                tStepDetails.style.display = 'none';
            }

            if (onPage) {
                tStepBtn.classList.add('t-current');
                tStepBtn.innerHTML = '<i class="mdi mdi-check"></i> Continuar';
                tStepBtn.removeAttribute('href');
            } else {
                tStepBtn.classList.remove('t-current');
                tStepBtn.innerHTML = '<i class="mdi mdi-arrow-right"></i> Ir al modulo';
                tStepBtn.href = BASE + step.url;
            }

            setPosition(onPage);
            renderDots(d, false);

            if (isMinimized) panel.classList.add('t-minimized');
            else panel.classList.remove('t-minimized');
        }

        /* ── Carga y avance ───────────────────────────────────────── */
        function loadTutorial() {
            fetch(BASE + 'api/tutorial/state')
                .then(r => r.json())
                .then(json => {
                    if (!json.value) return;
                    const d = json.data;
                    tutorialState = d;

                    if (d.completed) {
                        sessionStorage.setItem('tutorial_done', '1');
                        overlay.style.display = 'none';
                        return;
                    }

                    overlay.style.display = '';

                    if (d.currentStep === 0 && localStorage.getItem('tutorial_welcomed')) {
                        renderStep(d);
                    } else if (d.currentStep === 0) {
                        // Pantalla de bienvenida
                        tWelcome.style.display = '';
                        tStepWrap.style.display = 'none';
                        tCompleted.style.display = 'none';
                        tProgress.textContent = 'Bienvenida';
                        tFooter.innerHTML = d.steps.map((_, i) =>
                            `<span class="t-dot${i === 0 ? ' t-active' : ''}"></span>`
                        ).join('');
                        setPosition(false);
                    } else {
                        renderStep(d);
                    }
                })
                .catch(() => {});
        }

        function advanceStep() {
            if (!tutorialState) {
                loadTutorial();
                return;
            }
            const prevStep = tutorialState.currentStep;
            fetch(BASE + 'api/tutorial/state')
                .then(r => r.json())
                .then(json => {
                    console.log(json)
                    if (!json.value) return;
                    const d = json.data;
                    tutorialState = d;

                    if (d.completed) {
                        sessionStorage.setItem('tutorial_done', '1');
                        overlay.style.display = 'none';
                        hideBackdrop();
                        return;
                    }

                    if (d.currentStep > prevStep) renderStep(d);
                })
                .catch(() => {});
        }

        /* ── Interceptar fetch para detectar mutaciones ───────────── */
        const origFetch = window.fetch;
        window.fetch = function() {
            const url = arguments[0];
            const opts = arguments[1] || {};
            const method = (opts.method || 'GET').toUpperCase();
            return origFetch.apply(this, arguments).then(response => {
                if (method !== 'GET' && typeof url === 'string' && url.includes('/api/')) {
                    setTimeout(advanceStep, 500);
                }
                return response;
            });
        };

        window.tutorialAvanzar = advanceStep;

        /* ── Eventos ──────────────────────────────────────────────── */
        tStartBtn.addEventListener('click', () => {
            if (!tutorialState) return;
            localStorage.setItem('tutorial_welcomed', '1');
            tWelcome.style.display = 'none';
            tStepWrap.style.display = '';
            renderStep(tutorialState);
        });

        tBtnMin.addEventListener('click', () => {
            isMinimized = !isMinimized;
            panel.classList.toggle('t-minimized', isMinimized);
            tMinIcon.className = isMinimized ? 'mdi mdi-chevron-up' : 'mdi mdi-minus';
        });

        tBtnClose.addEventListener('click', () => {
            overlay.style.display = 'none';
            hideBackdrop();
        });

        loadTutorial();
    });
</script>