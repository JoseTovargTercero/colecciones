<?php

$userName = $_SESSION['nombre'] ?? 'Invitado';
$userType = $_SESSION['codigo'] ?? '';

// ── Badge de suscripción ──────────────────────────────────────────────────────
$_susc       = $_SESSION['suscripcion'] ?? null;
$_suscBadge  = null; // null = no mostrar

if ($_susc && !empty($_susc['fecha_fin'])) {
    $_hoy  = new DateTime('today');
    $_fin  = new DateTime($_susc['fecha_fin']);
    $_dias = max(0, (int)$_hoy->diff($_fin)->days);

    if ($_susc['tipo'] === 'trial') {
        // Siempre visible durante el trial
        $_suscBadge = [
            'tipo'  => 'trial',
            'dias'  => $_dias,
            'color' => $_dias <= 2 ? 'danger' : ($_dias <= 4 ? 'warning' : 'info'),
        ];
    } elseif ($_dias <= 10) {
        // Suscripción paga: solo cuando faltan ≤ 10 días
        $_suscBadge = [
            'tipo'  => 'pago',
            'dias'  => $_dias,
            'color' => $_dias <= 2 ? 'danger' : 'warning',
        ];
    }
}
// ─────────────────────────────────────────────────────────────────────────────
?>


<nav
    class="layout-navbar container-fluid navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme"
    id="layout-navbar">
    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
        <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
            <i class="bx bx-menu bx-sm"></i>
        </a>
    </div>

    <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
        <!-- Search -->
        <div class="navbar-nav align-items-center position-relative" id="tbSearchWrapper">
            <div class="nav-item d-flex align-items-center">
                <i class="bx bx-search fs-4 lh-0"></i>
                <input
                    type="text"
                    id="tbSearchInput"
                    class="form-control border-0 shadow-none"
                    placeholder="Buscar vendedor (cédula o nombre)..."
                    aria-label="Buscar vendedor"
                    autocomplete="off" />
                <button class="btn btn-sm btn-link text-muted p-0 ms-1" id="tbSearchBtn" title="Buscar" style="display:none">
                    <i class="bx bx-search fs-5"></i>
                </button>
            </div>
            <div id="tbSearchResults" class="w-100 shadow-sm border-0 mt-1 p-0" style="display:none;position:absolute;top:100%;left:0;z-index:9999;border-radius:8px;max-height:400px;overflow-y:auto;background:#fff"></div>
        </div>

        <ul class="navbar-nav flex-row align-items-center ms-auto">

            <!-- Download App -->
            <li class="nav-item lh-1 me-2" id="download-app-li" style="display: none;">
                <a class="nav-link" href="#" id="download-app-button" title="Descargar Aplicación">
                    <i class="bx bx-download bx-sm"></i>
                </a>
            </li>

            <!-- Notifications -->
            <li class="nav-item dropdown me-2 d-none">
                <a class="nav-link dropdown-toggle hide-arrow" href="#" data-bs-toggle="dropdown" id="notification-dropdown-toggle">
                    <i class="bx bx-bell bx-sm"></i>
                    <span class="badge bg-danger rounded-pill badge-notifications" id="alert-count">0</span>
                </a>
                <div class="dropdown-menu dropdown-menu-end dropdown-lg py-0" id="dropdown-container">
                    <div class="p-2 border-top-0 border-start-0 border-end-0 border-dashed border">
                        <div class="row align-items-center">
                            <div class="col">
                                <h6 class="m-0 fw-semibold">Notificaciones</h6>
                            </div>
                            <div class="col-auto">
                                <a href="javascript:void(0);" id="clear-all-alerts" class="text-decoration-underline">
                                    <small>Marcar Como Leídas</small>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="p-2">
                        <div class="d-flex gap-1" id="notification-tabs" role="tablist">
                            <button class="btn btn-outline-primary btn-sm" id="all-tab" type="button">Todas</button>
                            <button class="btn btn-outline-primary btn-sm active" id="unread-tab" type="button">
                                No leídas (<span id="unread-count">0</span>)
                            </button>
                        </div>
                    </div>

                    <div class="px-1 overflow-auto" style="max-height: 300px;" data-simplebar="init" id="alerts-scroll-container">
                        <div class="simplebar-content" style="padding: 0px 6px;" id="alerts-container"></div>
                    </div>

                    <a href="notifications" class="dropdown-item text-center notify-item border-top py-2">
                        Ver notificaciones
                    </a>
                </div>
            </li>

            <!-- BCV Rate -->
            <li class="nav-item lh-1 me-3">
                <div class="d-flex align-items-center gap-1 text-muted small" style="font-size:0.8rem">
                    <i class="bx bx-dollar-circle text-success"></i>
                    <span>BCV:</span>
                    <?php if (!empty($_SESSION['bcv_ok']) && $_SESSION['bcv_ok']): ?>
                        <span class="fw-semibold text-dark">Bs. <?= number_format($_SESSION['bcv_valor'], 2, ',', '.') ?></span>
                        <span style="font-size:0.7rem;opacity:0.7">(<?= date('d/m h:s A', strtotime($_SESSION['bcv_time'])) ?>)</span>
                    <?php else: ?>
                        <span class="fw-semibold text-muted">N/D</span>
                    <?php endif; ?>
                </div>
            </li>

            <!-- Suscripción badge -->
            <?php if ($_suscBadge): ?>
                <li class="nav-item lh-1 me-3">
                    <a href="<?= BASE_URL ?><?= $_suscBadge['tipo'] === 'trial' ? 'suscripcion/plan' : 'suscripcion/plan' ?>"
                        class="susc-badge susc-badge--<?= $_suscBadge['color'] ?>"
                        title="<?= $_suscBadge['tipo'] === 'trial' ? 'Prueba gratuita' : 'Renovar suscripción' ?>">
                        <?php if ($_suscBadge['tipo'] === 'trial'): ?>
                        <span class="susc-badge__icon"><i class="mdi mdi-gift-outline"></i></span>
                        <span class="susc-badge__text">
                            Trial · <strong><?= $_suscBadge['dias'] ?></strong> día<?= $_suscBadge['dias'] !== 1 ? 's' : '' ?>
                        </span>
                    <?php else: ?>
                        <span class="susc-badge__icon"><i class="mdi mdi-alert-circle-outline"></i></span>
                        <span class="susc-badge__text">
                            Renueva · <strong><?= $_suscBadge['dias'] ?></strong> día<?= $_suscBadge['dias'] !== 1 ? 's' : '' ?>
                        </span>
                    <?php endif; ?>
                    </a>
                </li>
            <?php endif; ?>
            <!-- /Suscripción badge -->

            <!-- User -->
            <li class="nav-item navbar-dropdown dropdown-user dropdown">
                <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                    <div class="avatar avatar-online">
                        <img src="<?= BASE_URL ?>public/assets/images/users/avatar-1.png" alt class="w-px-40 h-auto rounded-circle" />
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="#">
                            <div class="d-flex">
                                <div class="flex-shrink-0 me-3">
                                    <div class="avatar avatar-online">
                                        <img src="<?= BASE_URL ?>public/assets/images/users/avatar-1.png" alt class="w-px-40 h-auto rounded-circle" />
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <span class="fw-semibold d-block"><?= $userName ?></span>
                                    <small class="text-muted"><?= $userType ?></small>
                                </div>
                            </div>
                        </a>
                    </li>
                    <li>
                        <div class="dropdown-divider"></div>
                    </li>
                    <li>
                        <a class="dropdown-item" href="<?= BASE_URL ?>perfil">
                            <i class="bx bx-user me-2"></i>
                            <span class="align-middle">Mi Perfil</span>
                        </a>
                    </li>
                    <li>
                        <div class="dropdown-divider"></div>
                    </li>
                    <li>
                        <a class="dropdown-item" href="<?= BASE_URL ?>api/logout">
                            <i class="bx bx-power-off me-2"></i>
                            <span class="align-middle">Cerrar Sesión</span>
                        </a>
                    </li>
                </ul>
            </li>
            <!--/ User -->
        </ul>
    </div>
</nav>

<style>
    /* ── Subscription badge ── */
    .susc-badge {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .3rem .75rem;
        border-radius: 50px;
        font-size: .78rem;
        font-weight: 500;
        text-decoration: none !important;
        transition: transform .18s, box-shadow .18s;
        white-space: nowrap;
    }

    .susc-badge:hover {
        transform: translateY(-1px);
    }

    .susc-badge--info {
        background: rgba(13, 202, 240, .12);
        color: #0dcaf0;
        border: 1px solid rgba(13, 202, 240, .3);
    }

    .susc-badge--warning {
        background: rgba(255, 193, 7, .13);
        color: #cc9a00;
        border: 1px solid rgba(255, 193, 7, .35);
        animation: susc-pulse-warn 2.4s ease-in-out infinite;
    }

    .susc-badge--danger {
        background: rgba(220, 53, 69, .12);
        color: #dc3545;
        border: 1px solid rgba(220, 53, 69, .3);
        animation: susc-pulse-danger 1.6s ease-in-out infinite;
    }

    .susc-badge--info:hover {
        box-shadow: 0 4px 12px rgba(13, 202, 240, .25);
    }

    .susc-badge--warning:hover {
        box-shadow: 0 4px 12px rgba(255, 193, 7, .3);
    }

    .susc-badge--danger:hover {
        box-shadow: 0 4px 12px rgba(220, 53, 69, .3);
    }

    .susc-badge__icon {
        font-size: 1rem;
        line-height: 1;
    }

    @keyframes susc-pulse-warn {

        0%,
        100% {
            box-shadow: 0 0 0 0 rgba(255, 193, 7, .3);
        }

        50% {
            box-shadow: 0 0 0 5px rgba(255, 193, 7, 0);
        }
    }

    @keyframes susc-pulse-danger {

        0%,
        100% {
            box-shadow: 0 0 0 0 rgba(220, 53, 69, .4);
        }

        50% {
            box-shadow: 0 0 0 6px rgba(220, 53, 69, 0);
        }
    }

    @media (max-width: 576px) {
        .susc-badge__text {
            display: none;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Menu toggle
        document.querySelectorAll('.layout-menu-toggle').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                var isXl = window.matchMedia('(min-width: 1200px)').matches;
                if (isXl) {
                    document.body.classList.toggle('layout-menu-collapsed');
                } else {
                    document.body.classList.toggle('layout-menu-expanded');
                }
            });
        });

        const userAgent = navigator.userAgent;
        const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(userAgent);
        const downloadLi = document.getElementById('download-app-li');
        const downloadButton = document.getElementById('download-app-button');

        if (isMobile) {
            downloadLi.style.display = 'list-item';
            downloadButton.addEventListener('click', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: '¿Descargar Aplicación?',
                    text: "Se descargará el archivo de instalación para Android.",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, descargar',
                    cancelButtonText: 'Cancelar',
                }).then((result) => {
                    if (result.isConfirmed) {
                        const downloadUrl = '<?= BASE_URL ?>downloads/app.txt';
                        const link = document.createElement('a');
                        link.href = downloadUrl;
                        link.setAttribute('download', 'app.apk');
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    }
                });
            });
        }

        // Search by cedula
        const searchInput = document.getElementById('tbSearchInput');
        const searchBtn = document.getElementById('tbSearchBtn');
        const resultsEl = document.getElementById('tbSearchResults');
        const BASE = '<?= rtrim(BASE_URL, '/') . '/' ?>';
        let searchTimeout = null;

        function doSearch() {
            const q = searchInput.value.trim();
            if (!q) {
                resultsEl.style.display = 'none';
                return;
            }

            resultsEl.style.display = 'block';
            resultsEl.innerHTML = '<div class="p-3 text-center"><div class="spinner-border spinner-border-sm text-primary" role="status"></div></div>';

            fetch(BASE + 'api/vendedores/buscar?q=' + encodeURIComponent(q))
                .then(r => r.json())
                .then(json => {
                    if (!json.value || !json.data) {
                        resultsEl.innerHTML = `<div class="p-3 text-muted small text-center">${json.message || 'Sin resultados'}</div>`;
                        resultsEl.style.display = 'block';
                        return;
                    }
                    const d = json.data;
                    const v = d.vendedor || {};
                    const asigs = d.asignaciones || [];
                    const premios = d.premios || [];

                    // Merge empresas from both lists
                    const empMap = {};
                    asigs.forEach(a => {
                        empMap[a.empresa_id] = {
                            nombre: a.empresa_nombre,
                            asig: parseInt(a.total_asignaciones) || 0,
                            prem: 0
                        };
                    });
                    premios.forEach(p => {
                        if (empMap[p.empresa_id]) empMap[p.empresa_id].prem = parseInt(p.total_premios) || 0;
                        else empMap[p.empresa_id] = {
                            nombre: p.empresa_nombre,
                            asig: 0,
                            prem: parseInt(p.total_premios) || 0
                        };
                    });

                    const empresas = Object.values(empMap);
                    let html = `<div class="p-2 border-bottom bg-light"><strong class="small">${v.nombre || 'Vendedor'}</strong> <span class="text-muted small">(${v.cedula || ''})</span></div>`;
                    if (!empresas.length) {
                        html += `<div class="p-3 text-muted small text-center">Sin asignaciones activas ni premios pendientes.</div>`;
                    } else {
                        empresas.forEach(e => {
                            html += `<div class="px-3 py-2 border-bottom">
                                <div class="fw-semibold small mb-1">${e.nombre}</div>
                                <div class="d-flex gap-3 small">
                                    <a href="${BASE}control_pagos?cedula=${encodeURIComponent(v.cedula || '')}" class="text-decoration-none">
                                        <span class="badge bg-primary bg-gradient">${e.asig}</span> Asignaciones
                                    </a>
                                    <a href="${BASE}preferencias-premios?cedula=${encodeURIComponent(v.cedula || '')}" class="text-decoration-none">
                                        <span class="badge bg-warning text-dark bg-gradient">${e.prem}</span> Premios
                                    </a>
                                </div>
                            </div>`;
                        });
                    }
                    resultsEl.innerHTML = html;
                    resultsEl.style.display = 'block';
                })
                .catch(() => {
                    resultsEl.innerHTML = `<div class="p-3 text-danger small text-center">Error de conexión.</div>`;
                    resultsEl.style.display = 'block';
                });
        }

        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            if (!this.value.trim()) {
                resultsEl.style.display = 'none';
                return;
            }
            searchTimeout = setTimeout(doSearch, 300);
        });

        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                clearTimeout(searchTimeout);
                doSearch();
            }
        });

        // Close on click outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('#tbSearchWrapper')) {
                resultsEl.style.display = 'none';
            }
        });
    });
</script>