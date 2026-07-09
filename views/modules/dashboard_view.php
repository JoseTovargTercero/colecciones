<style>
    /* Sneat Theme Emulation Styles */
    :root {
        --bs-primary: #696cff;
        --bs-secondary: #8592a3;
        --bs-success: #71dd37;
        --bs-info: #03c3ec;
        --bs-warning: #ffab00;
        --bs-danger: #ff3e1d;
        --bs-dark: #233446;
    }

    .card {
        box-shadow: 0 2px 6px 0 rgba(67, 89, 113, 0.12);
        border: none;
        border-radius: 0.5rem;
    }

    .card-header {
        background-color: transparent;
        border-bottom: 0;
        padding: 1.5rem 1.5rem 0;
    }

    .card-body {
        padding: 1.5rem;
    }

    .avatar {
        position: relative;
        width: 2.375rem;
        height: 2.375rem;
        cursor: pointer;
    }

    .avatar .avatar-initial {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        text-transform: uppercase;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        background-color: #e1e4e8;
        font-weight: 600;
    }

    .bg-label-primary {
        background-color: #e7e7ff !important;
        color: #696cff !important;
    }

    .bg-label-secondary {
        background-color: #ebeef0 !important;
        color: #8592a3 !important;
    }

    .bg-label-success {
        background-color: #e8fadf !important;
        color: #71dd37 !important;
    }

    .bg-label-info {
        background-color: #d7f5fc !important;
        color: #03c3ec !important;
    }

    .bg-label-warning {
        background-color: #fff2d6 !important;
        color: #ffab00 !important;
    }

    .bg-label-danger {
        background-color: #ffe0db !important;
        color: #ff3e1d !important;
    }

    .text-primary {
        color: #696cff !important;
    }

    .text-success {
        color: #71dd37 !important;
    }

    .text-danger {
        color: #ff3e1d !important;
    }

    .text-warning {
        color: #ffab00 !important;
    }

    .text-info {
        color: #03c3ec !important;
    }

    .btn-outline-primary {
        color: #696cff;
        border-color: #696cff;
        background: transparent;
    }

    .btn-outline-primary:hover {
        color: #fff;
        background-color: #696cff;
        border-color: #696cff;
    }

    .dropdown-toggle::after {
        display: none;
    }

    .nav-pills .nav-link.active,
    .nav-pills .show>.nav-link {
        color: #fff;
        background-color: #696cff;
    }

    .row-bordered>.col-md-8 {
        border-right: 1px solid #d9dee3;
    }

    @media (max-width: 767.98px) {
        .row-bordered>.col-md-8 {
            border-right: none;
            border-bottom: 1px solid #d9dee3;
        }
    }

    ul.m-0.p-0 {
        list-style: none;
    }
</style>

<div class="container-fluid flex-grow-1 container-p-y py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1" style="color: #435971;">Dashboard</h4>
            <p class="text-muted mb-0 small" id="dashboardSubtitle">Cargando...</p>
        </div>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <select class="form-select form-select-sm" id="dashEmpresa" style="width: auto; min-width: 180px; border-radius: 8px;" onchange="filtrarCampanias(); cargarDashboard();">
                <option value="">Todas las empresas</option>
            </select>
            <select class="form-select form-select-sm" id="dashCampania" style="width: auto; min-width: 180px; border-radius: 8px;" onchange="cargarDashboard();">
                <option value="">Todas las campañas</option>
            </select>
            <button class="btn btn-primary btn-sm shadow-sm" onclick="cargarDashboard()" style="border-radius: 8px; background-color:#696cff; border-color:#696cff;">
                <i class="bx bx-refresh me-1"></i>Actualizar
            </button>
        </div>
    </div>

    <div id="dashboardContent">
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <div class="text-muted mt-2">Cargando dashboard...</div>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>public/assets/js/vendor/apexcharts.min.js"></script>

<script>
    const BASE = '<?= BASE_URL ?>';
    let _dashData = null;
    let _chartIngresos = null;
    let _chartVendedores = null;
    let _chartGrowth = null;
    let _chartProfile = null;
    let _chartOrder = null;
    let _chartExpenses = null;

    document.addEventListener('DOMContentLoaded', async () => {
        window._dashTemporadas = [];
        try {
            const [re, rt] = await Promise.all([
                fetch(BASE + 'api/empresas').then(r => r.json()),
                fetch(BASE + 'api/temporadas').then(r => r.json()),
            ]);
            const empresas = re.data || [];
            window._dashTemporadas = rt.data || [];
            const selEmp = document.getElementById('dashEmpresa');
            selEmp.innerHTML = '<option value="">Todas las empresas</option>';
            empresas.forEach(e => {
                selEmp.innerHTML += `<option value="${e.id}">${e.nombre}</option>`;
            });
            selEmp.value = '';
            cargarDashboard();
        } catch (e) {
            console.error(e);
        }
    });

    function filtrarCampanias() {
        const eid = document.getElementById('dashEmpresa').value;
        const sel = document.getElementById('dashCampania');
        sel.innerHTML = '<option value="">Todas las campañas</option>';
        (window._dashTemporadas || [])
        .filter(t => !eid || t.empresa_id == eid)
            .forEach(t => {
                const inicio = t.fecha_inicio ? ` (${t.fecha_inicio})` : '';
                sel.innerHTML += `<option value="${t.id}">${t.nombre}${inicio}</option>`;
            });
    }

    async function cargarDashboard() {
        const container = document.getElementById('dashboardContent');
        container.innerHTML = `<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><div class="text-muted mt-2">Cargando dashboard...</div></div>`;

        try {
            const params = new URLSearchParams();
            const eid = document.getElementById('dashEmpresa').value;
            const tid = document.getElementById('dashCampania').value;
            if (eid) params.set('empresa_id', eid);
            if (tid) params.set('temporada_id', tid);
            const res = await fetch(BASE + 'api/dashboard/kpis?' + params.toString());
            const json = await res.json();
            if (json.value) {
                _dashData = json.data;
                console.log(_dashData)
                renderDashboard(json.data);
            } else {
                container.innerHTML = `<div class="alert alert-danger">${json.message}</div>`;
            }
        } catch (e) {
            container.innerHTML = `<div class="alert alert-danger">Error de conexión.</div>`;
        }
    }

    function fmt(v) {
        return Number(v).toLocaleString('es-ES', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function shortFmt(v) {
        if (v >= 1000) return (v / 1000).toFixed(1).replace(/\.0$/, '') + 'k';
        return Number(v).toLocaleString('es-ES', {
            maximumFractionDigits: 0
        });
    }

    function renderDashboard(d) {
        const t = d.temporada;
        const subtitle = t ? `Campaña actual: <strong>${t.nombre}</strong>` : 'Sin temporada activa';
        document.getElementById('dashboardSubtitle').innerHTML = subtitle;

        const html = `
        <div class="row">
            <!-- Congratulations Card -->
            <div class="col-lg-8 mb-4 order-0">
                <div class="card h-100">
                    <div class="d-flex align-items-end row">
                        <div class="col-sm-7">
                            <div class="card-body">
                                <h5 class="card-title text-primary">¡Resumen de Actividad! 🎉</h5>
                                <p class="mb-4">
                                    El volumen de ventas actual es <span class="fw-bold">$${fmt(d.volumen_ventas)}</span>.
                                    Revisa el estatus de las asignaciones y los pagos pendientes.
                                </p>
                                <a href="${BASE}control_pagos" class="btn btn-sm btn-outline-primary">Ver Pagos</a>
                            </div>
                        </div>
                        <div class="col-sm-5 text-center text-sm-left">
                            <div class="card-body pb-0 px-0 px-md-4">
                                <img src="${BASE}/public/assets/images/ilustration-1.png" height="140" alt="View Badge User" style="object-fit: contain;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4 Small Cards Grid -->
            <div class="col-lg-4 col-md-4 order-1">
                <div class="row">
                    <div class="col-lg-6 col-md-12 col-6 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="card-title d-flex align-items-start justify-content-between">
                                    <div class="avatar flex-shrink-0">
                                        <span class="avatar-initial rounded bg-label-success"><i class="bx bx-dollar"></i></span>
                                    </div>
                                    <div class="dropdown">
                                        <button class="btn p-0" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="bx bx-dots-vertical-rounded"></i></button>
                                        <div class="dropdown-menu dropdown-menu-end"><a class="dropdown-item" href="javascript:void(0);">Ver Detalles</a></div>
                                    </div>
                                </div>
                                <span class="fw-semibold d-block mb-1 text-muted">Ventas</span>
                                <h3 class="card-title mb-2">$${shortFmt(d.volumen_ventas)}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-12 col-6 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="card-title d-flex align-items-start justify-content-between">
                                    <div class="avatar flex-shrink-0">
                                        <span class="avatar-initial rounded bg-label-info"><i class="bx bx-trending-up"></i></span>
                                    </div>
                                    <div class="dropdown">
                                        <button class="btn p-0" type="button" data-bs-toggle="dropdown"><i class="bx bx-dots-vertical-rounded"></i></button>
                                        <div class="dropdown-menu dropdown-menu-end"><a class="dropdown-item" href="javascript:void(0);">Ver Detalles</a></div>
                                    </div>
                                </div>
                                <span class="fw-semibold d-block mb-1 text-muted">Ganancia</span>
                                <h3 class="card-title text-nowrap mb-1">$${shortFmt(d.ganancia_proyectada)}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Total Revenue & Growth Chart -->
            <div class="col-12 col-lg-8 order-2 order-md-3 order-lg-2 mb-4">
                <div class="card">
                    <div class="row row-bordered g-0">
                        <div class="col-md-8">
                            <h5 class="card-header m-0 me-2 pb-3">Top Vendedores <small class="text-muted fs-6 fw-normal ms-1">(Asignaciones)</small></h5>
                            <div id="chartVendedores" class="px-2" style="min-height: 315px;"></div>
                        </div>
                        <div class="col-md-4">
                            <div class="card-body">
                                <div class="text-center">
                                    <div class="dropdown">Efectividad de pagos</div>
                                </div>
                            </div>
                            <div id="growthChart"></div>
                            <div class="text-center fw-semibold pt-3 mb-2">${d.conversion.tasa}% Tasa Conversión</div>
                           
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Report & Extra Cards -->
            <div class="col-12 col-md-8 col-lg-4 order-3 order-md-2">
                <div class="row">
                    <div class="col-6 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="card-title d-flex align-items-start justify-content-between">
                                    <div class="avatar flex-shrink-0">
                                        <span class="avatar-initial rounded bg-label-danger"><i class="bx bx-error"></i></span>
                                    </div>
                                </div>
                                <span class="d-block mb-1 text-muted">Pendiente</span>
                                <h3 class="card-title text-nowrap mb-2">$${shortFmt(d.monto_pendiente)}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="card-title d-flex align-items-start justify-content-between">
                                      <div class="avatar flex-shrink-0">
                                        <span class="avatar-initial rounded bg-label-danger"><i class="bx bx-trending-down"></i></span>
                                    </div>
                                </div>
                                <span class="fw-semibold d-block mb-1 text-muted">Morosidad</span>
                                <h3 class="card-title mb-2">${d.morosidad.porcentaje}%</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between flex-sm-row flex-column gap-3">
                                    <div class="d-flex flex-sm-column flex-row align-items-start justify-content-between">
                                        <div class="card-title">
                                            <h5 class="text-nowrap mb-2">Cuotas Pagadas</h5>
                                            <span class="badge bg-label-warning rounded-pill">A tiempo</span>
                                        </div>
                                        <div class="mt-sm-auto">
                                            <small class="text-success text-nowrap fw-semibold"><i class="bx bx-chevron-up"></i> Optimo</small>
                                            <h3 class="mb-0">${d.ranking_responsabilidad && d.ranking_responsabilidad.length ? d.ranking_responsabilidad[0].cuotas_pagadas_tiempo : 0}</h3>
                                        </div>
                                    </div>
                                    <div id="profileReportChart" style="min-height:80px; width:130px;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
             <!-- Transactions (Ranking Vendedores) -->
            <div class="col-lg-5 order-1 mb-4">
                <div class="card h-100">
                    <div class="card-header mb-2 d-flex align-items-center justify-content-between">
                        <h5 class="card-title m-0 me-2">Top vendedores</h5>
                    </div>
                    <div class="card-body">
                        <ul class="p-0 m-0">
                            ${(d.top_vendedores || []).slice(0, 6).map((v, i) => {
                                const icons = ['bx-wallet', 'bx-credit-card', 'bx-transfer', 'bx-dollar', 'bx-credit-card', 'bx-wallet'];
                                const colors = ['danger', 'primary', 'success', 'info', 'warning', 'secondary'];
                                return `
                                <li class="d-flex mb-4 pb-1">
                                    <div class="avatar flex-shrink-0 me-3">
                                        <span class="avatar-initial rounded bg-label-${colors[i%6]}"><i class="bx ${icons[i%6]}"></i></span>
                                    </div>
                                    <div class="d-flex w-100 align-items-center justify-content-between gap-2">
                                        <div class="me-2">
                                            <span class="mb-0" style="color:#435971;">${v.nombre}</span>
                                        <small class="text-muted d-block mb-1">Vendedor</small>
                                        
                                            </div>
                                        <div class="user-progress d-flex align-items-center gap-1">
                                            <span class="mb-0" style="color:#435971;">$${shortFmt(v.total_valor)}</span>
                                          <span class="text-muted">USD</span>
                                        </div>
                                    </div>
                                </li>`;
                            }).join('')}
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Income/Expenses/Profit (Pagos Pendientes) -->
            <div class="col-lg-7 order-2 mb-4">
                <div class="card h-100">
                    <div class="card-header d-flex align-items-center justify-content-between pb-0">
                        <div class="card-title mb-0">
                            <h5 class="m-0 me-2">Pendiente por cobrar</h5>
                            <small class="text-muted">Cuotas pendientes</small>
                        </div>
                    </div>
                    <div class="card-body px-0">
                        <div class="tab-content p-0">
                            <div class="tab-pane fade show active" role="tabpanel">
                                <div id="chartIngresos" class="px-2"></div>
                                <div class="d-flex justify-content-center pt-4 gap-2">
                                    <div class="flex-shrink-0">
                                        <div id="expensesOfWeek"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

           
        </div>
    `;
        document.getElementById('dashboardContent').innerHTML = html;
        initCharts(d);
    }

    function initCharts(d) {
        const cardColor = '#fff';
        const headingColor = '#566a7f';
        const axisColor = '#a1acb8';
        const borderColor = '#eceef1';

        // 1. Top Vendedores (Bar Chart - Total Revenue Style)
        const v = d.top_vendedores || [];
        const vLabels = v.slice(0, 7).map(r => r.nombre.split(' ')[0] || '—');
        const vData = v.slice(0, 7).map(r => parseInt(r.total_asignaciones));

        // Create a dummy second series to match the Sneat Dual-bar look
        const vData2 = vData.map(v => Math.round(v * 0.4));

        const vendedoresOpts = {
            series: [{
                    name: 'Asignaciones',
                    data: vData
                },
                {
                    name: 'Previo',
                    data: vData2
                }
            ],
            chart: {
                type: 'bar',
                height: 300,
                stacked: true,
                toolbar: {
                    show: false
                }
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '30%',
                    borderRadius: 4
                }
            },
            colors: ['#696cff', '#03c3ec'],
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'smooth',
                width: 2,
                lineCap: 'round',
                colors: [cardColor]
            },
            legend: {
                show: true,
                horizontalAlign: 'left',
                position: 'top',
                markers: {
                    height: 8,
                    width: 8,
                    radius: 12,
                    offsetX: -3
                },
                labels: {
                    colors: axisColor
                },
                itemMargin: {
                    horizontal: 10
                }
            },
            grid: {
                borderColor: borderColor,
                padding: {
                    top: 0,
                    bottom: -8,
                    left: 20,
                    right: 20
                }
            },
            xaxis: {
                categories: vLabels,
                labels: {
                    style: {
                        fontSize: '13px',
                        colors: axisColor
                    }
                },
                axisTicks: {
                    show: false
                },
                axisBorder: {
                    show: false
                }
            },
            yaxis: {
                labels: {
                    style: {
                        fontSize: '13px',
                        colors: axisColor
                    }
                }
            }
        };
        if (_chartVendedores) _chartVendedores.destroy();
        _chartVendedores = new ApexCharts(document.querySelector('#chartVendedores'), vendedoresOpts);
        _chartVendedores.render();

        // 2. Growth Radial Chart
        const growthOpts = {
            series: [d.conversion.tasa || 0],
            labels: ['Conversión'],
            chart: {
                height: 240,
                type: 'radialBar'
            },
            plotOptions: {
                radialBar: {
                    size: 150,
                    offsetY: 10,
                    startAngle: -150,
                    endAngle: 150,
                    hollow: {
                        size: '55%'
                    },
                    track: {
                        background: cardColor,
                        strokeWidth: '100%'
                    },
                    dataLabels: {
                        name: {
                            offsetY: 15,
                            color: headingColor,
                            fontSize: '15px',
                            fontWeight: '500',
                            fontFamily: 'Public Sans'
                        },
                        value: {
                            offsetY: -25,
                            color: headingColor,
                            fontSize: '22px',
                            fontWeight: '500',
                            fontFamily: 'Public Sans'
                        }
                    }
                }
            },
            colors: ['#696cff'],
            fill: {
                type: 'gradient',
                gradient: {
                    shade: 'dark',
                    shadeIntensity: 0.5,
                    gradientToColors: ['#696cff'],
                    inverseColors: true,
                    opacityFrom: 1,
                    opacityTo: 0.6,
                    stops: [30, 70, 100]
                }
            },
            stroke: {
                dashArray: 5
            },
            grid: {
                padding: {
                    top: -35,
                    bottom: -10
                }
            }
        };
        if (_chartGrowth) _chartGrowth.destroy();
        _chartGrowth = new ApexCharts(document.querySelector('#growthChart'), growthOpts);
        _chartGrowth.render();

        // 3. Profile Report Chart
        const profileData = (d.cuotas_pagadas_por_fecha || []).map(r => parseInt(r.total) || 0);
        const profileOpts = {
            chart: {
                height: 80,
                type: 'line',
                toolbar: {
                    show: false
                },
                dropShadow: {
                    enabled: true,
                    top: 10,
                    left: 5,
                    blur: 3,
                    color: '#ffab00',
                    opacity: 0.15
                },
                sparkline: {
                    enabled: true
                }
            },
            grid: {
                show: false,
                padding: {
                    right: 8
                }
            },
            colors: ['#ffab00'],
            dataLabels: {
                enabled: false
            },
            stroke: {
                width: 5,
                curve: 'smooth'
            },
            series: [{
                data: profileData.length ? profileData : [0]
            }],
            xaxis: {
                show: false,
                lines: {
                    show: false
                },
                labels: {
                    show: false
                },
                axisBorder: {
                    show: false
                }
            },
            yaxis: {
                show: false
            }
        };
        if (_chartProfile) _chartProfile.destroy();
        _chartProfile = new ApexCharts(document.querySelector('#profileReportChart'), profileOpts);
        _chartProfile.render();

        // 4. Order Statistics Donut
        const orderOpts = {
            chart: {
                height: 165,
                width: 130,
                type: 'donut'
            },
            labels: ['Pagadas', 'Pendientes', 'Atrasadas', 'Vencidas'],
            series: [45, 20, 15, 20],
            colors: ['#696cff', '#71dd37', '#03c3ec', '#8592a3'],
            stroke: {
                width: 5,
                colors: cardColor
            },
            dataLabels: {
                enabled: false,
                formatter: function(val, opt) {
                    return parseInt(val) + '%';
                }
            },
            legend: {
                show: false
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '75%',
                        labels: {
                            show: true,
                            value: {
                                fontSize: '1.5rem',
                                fontFamily: 'Public Sans',
                                color: headingColor,
                                offsetY: -15,
                                formatter: function(val) {
                                    return parseInt(val) + '%';
                                }
                            },
                            name: {
                                offsetY: 20,
                                fontFamily: 'Public Sans'
                            },
                            total: {
                                show: true,
                                fontSize: '0.8125rem',
                                color: axisColor,
                                label: 'Promedio',
                                formatter: function(w) {
                                    return '38%';
                                }
                            }
                        }
                    }
                }
            }
        };
        const orderEl = document.querySelector('#orderStatisticsChart');
        if (orderEl) {
            if (_chartOrder) _chartOrder.destroy();
            _chartOrder = new ApexCharts(orderEl, orderOpts);
            _chartOrder.render();
        }

        // 5. Income/Expenses Area Chart (Pagos Pendientes)
        const pp = d.pagos_pendientes || {};
        const ppLabels = Object.keys(pp).sort();
        const ppData = ppLabels.map(k => pp[k]);

        const incomeOpts = {
            series: [{
                name: 'Pendiente',
                data: ppData.length ? ppData : [0, 0, 0, 0, 0, 0, 0, 0]
            }],
            chart: {
                height: 215,
                parentHeightOffset: 0,
                parentWidthOffset: 0,
                toolbar: {
                    show: false
                },
                type: 'area'
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                width: 2,
                curve: 'smooth'
            },
            legend: {
                show: false
            },
            markers: {
                size: 6,
                colors: 'transparent',
                strokeColors: 'transparent',
                strokeWidth: 4,
                discrete: [{
                    fillColor: '#fff',
                    seriesIndex: 0,
                    dataPointIndex: 7,
                    strokeColor: '#696cff',
                    strokeWidth: 2,
                    size: 6,
                    radius: 8
                }],
                hover: {
                    size: 7
                }
            },
            colors: ['#696cff'],
            fill: {
                type: 'gradient',
                gradient: {
                    shade: 'light',
                    shadeIntensity: 0.6,
                    opacityFrom: 0.5,
                    opacityTo: 0.25,
                    stops: [0, 95, 100]
                }
            },
            grid: {
                borderColor: borderColor,
                strokeDashArray: 3,
                padding: {
                    top: -20,
                    bottom: -8,
                    left: -10,
                    right: 8
                }
            },
            xaxis: {
                categories: ppLabels.length ? ppLabels : ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul'],
                axisBorder: {
                    show: false
                },
                axisTicks: {
                    show: false
                },
                labels: {
                    show: true,
                    style: {
                        fontSize: '13px',
                        colors: axisColor
                    }
                }
            },
            yaxis: {
                labels: {
                    show: false
                },
                min: 0,
                tickAmount: 4
            }
        };
        if (_chartIngresos) _chartIngresos.destroy();
        _chartIngresos = new ApexCharts(document.querySelector('#chartIngresos'), incomeOpts);
        _chartIngresos.render();

        // 6. Expenses of Week — Cuotas pendientes por fecha_pago
        const cpf = d.cuotas_pendientes_fecha || [];
        const cpfLabels = cpf.map(r => r.fecha_pago);
        const cpfData = cpf.map(r => parseInt(r.total) || 0);

        const expensesOpts = {
            chart: {
                height: 80,
                type: 'bar',
                toolbar: { show: false },
                sparkline: { enabled: true }
            },
            grid: { show: false, padding: { right: 8 } },
            colors: ['#696cff'],
            plotOptions: {
                bar: {
                    columnWidth: '60%',
                    borderRadius: 3,
                    distributed: true
                }
            },
            dataLabels: { enabled: false },
            series: [{
                data: cpfData.length ? cpfData : [0]
            }],
            xaxis: {
                categories: cpfLabels.length ? cpfLabels : [''],
                labels: { show: false },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: { show: false }
        };
        if (_chartExpenses) _chartExpenses.destroy();
        const el = document.querySelector('#expensesOfWeek');
        if (el) {
            _chartExpenses = new ApexCharts(el, expensesOpts);
            _chartExpenses.render();
        }

    }
</script>