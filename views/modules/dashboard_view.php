<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1" style="color: #4a5568;"><i class="bx bx-bar-chart-alt-2 text-primary me-2"></i>Dashboard</h4>
            <p class="text-muted mb-0 small" id="dashboardSubtitle">Cargando...</p>
        </div>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <select class="form-select form-select-sm" id="dashEmpresa" style="width: auto; min-width: 180px; border-radius: 8px;" onchange="filtrarCampanias(); cargarDashboard();">
                <option value="">Todas las empresas</option>
            </select>
            <select class="form-select form-select-sm" id="dashCampania" style="width: auto; min-width: 180px; border-radius: 8px;" onchange="cargarDashboard();">
                <option value="">Todas las campañas</option>
            </select>
            <button class="btn btn-primary shadow-sm" onclick="cargarDashboard()" style="border-radius: 8px;">
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
let _dashEmpresaId = '';
let _dashTempId = '';

document.addEventListener('DOMContentLoaded', async () => {
    window._dashTemporadas = [];
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
            renderDashboard(json.data);
        } else {
            container.innerHTML = `<div class="alert alert-danger">${json.message}</div>`;
        }
    } catch (e) {
        container.innerHTML = `<div class="alert alert-danger">Error de conexión.</div>`;
    }
}

function renderDashboard(d) {
    const t = d.temporada;
    const subtitle = t ? `Temporada actual: <strong>${t.nombre}</strong>` : 'Sin temporada activa';
    document.getElementById('dashboardSubtitle').innerHTML = subtitle;

    const html = `
        ${renderMetricCards(d)}
        ${renderMorosidadPendiente(d)}
        ${renderCharts(d)}
        ${renderTopVendedoresTable(d)}
        ${renderRankingResponsabilidad(d)}
    `;
    document.getElementById('dashboardContent').innerHTML = html;
    initCharts(d);
}

function renderMetricCards(d) {
    const fmt = v => Number(v).toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const cards = [
        { icon: 'bx bx-collection', label: 'Asignaciones Activas', value: d.asignaciones_activas, color: '#7367f0', bg: 'rgba(115,103,240,0.1)' },
        { icon: 'bx bx-dollar', label: 'Volumen Ventas', value: '$ ' + fmt(d.volumen_ventas), color: '#28c76f', bg: 'rgba(40,199,111,0.1)' },
        { icon: 'bx bx-trending-up', label: 'Ganancia Proyectada', value: '$ ' + fmt(d.ganancia_proyectada), color: '#ff9f43', bg: 'rgba(255,159,67,0.1)' },
        { icon: 'bx bx-check-circle', label: 'Tasa Conversión', value: d.conversion.tasa + '%', color: '#00cfe8', bg: 'rgba(0,207,232,0.1)' },
    ];

    return `<div class="row g-4 mb-4">
        ${cards.map(c => `
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
                    <div class="card-body d-flex align-items-center p-3">
                        <div class="avatar-sm d-flex justify-content-center align-items-center rounded-circle me-3" style="width: 48px; height: 48px; background: ${c.bg}; color: ${c.color}; font-size: 1.4rem;">
                            <i class="${c.icon}"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="text-muted small text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">${c.label}</div>
                            <div class="fw-bold fs-5 mt-1" style="color: #4a5568;">${c.value}</div>
                        </div>
                    </div>
                </div>
            </div>
        `).join('')}
    </div>`;
}

function renderMorosidadPendiente(d) {
    const pct = d.morosidad.porcentaje;
    const color = pct > 30 ? '#ea5455' : pct > 15 ? '#ff9f43' : '#28c76f';
    const bg = pct > 30 ? 'rgba(234,84,85,0.1)' : pct > 15 ? 'rgba(255,159,67,0.1)' : 'rgba(40,199,111,0.1)';
    const fmt = v => Number(v).toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    return `<div class="row g-4 mb-4">
        <div class="col-12 col-sm-6">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm d-flex justify-content-center align-items-center rounded-circle me-3" style="width: 48px; height: 48px; background: rgba(234,84,85,0.1); color: #ea5455; font-size: 1.4rem;">
                            <i class="bx bx-time"></i>
                        </div>
                        <div>
                            <div class="text-muted small text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">Monto Pendiente</div>
                            <div class="fw-bold fs-5 mt-1" style="color: #4a5568;">$ ${fmt(d.monto_pendiente)}</div>
                            <small class="text-muted">Cuotas con estatus diferente a realizado</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm d-flex justify-content-center align-items-center rounded-circle me-3" style="width: 48px; height: 48px; background: ${bg}; color: ${color}; font-size: 1.4rem;">
                            <i class="bx bx-error"></i>
                        </div>
                        <div>
                            <div class="text-muted small text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">Morosidad</div>
                            <div class="fw-bold fs-5 mt-1" style="color: #4a5568;">${pct}%</div>
                            <small class="text-muted">${d.morosidad.vencidas} de ${d.morosidad.total} cuotas vencidas</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>`;
}

function renderCharts(d) {
    return `<div class="row g-4 mb-4">
        <div class="col-12 col-xl-7">
            <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center py-3">
                    <h6 class="mb-0 fw-semibold"><i class="bx bx-credit-card text-warning me-1"></i> Pagos Pendientes</h6>
                    <small class="text-muted">Por fecha de vencimiento</small>
                </div>
                <div class="card-body">
                    <div id="chartIngresos"></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-5">
            <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center py-3">
                    <h6 class="mb-0 fw-semibold"><i class="bx bx-bar-chart text-primary me-1"></i> Top Vendedores</h6>
                    <small class="text-muted">Por asignaciones</small>
                </div>
                <div class="card-body">
                    <div id="chartVendedores"></div>
                </div>
            </div>
        </div>
    </div>`;
}

function renderTopVendedoresTable(d) {
    const v = d.top_vendedores;
    if (!v.length) return '';
    return `<div class="card border-0 shadow-sm" style="border-radius: 12px;">
        <div class="card-header bg-transparent border-bottom py-3">
            <h6 class="mb-0 fw-semibold"><i class="bx bx-trophy text-primary me-1"></i> Ranking de Vendedores</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">
                    <tr>
                        <th class="border-0 ps-4">#</th>
                        <th class="border-0">Vendedor</th>
                        <th class="border-0 text-center">Asignaciones</th>
                        <th class="border-0 text-end pe-4">Valor Total</th>
                    </tr>
                </thead>
                <tbody>
                    ${v.map((r, i) => `
                        <tr>
                            <td class="ps-4 fw-bold" style="color: ${i === 0 ? '#ff9f43' : i === 1 ? '#a8aaae' : i === 2 ? '#cd7f32' : '#4a5568'};">${i + 1}</td>
                            <td><span class="fw-medium">${r.nombre || '—'}</span></td>
                            <td class="text-center">${r.total_asignaciones}</td>
                            <td class="text-end pe-4">$ ${Number(r.total_valor).toLocaleString('es-ES', { minimumFractionDigits: 2 })}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    </div>`;
}

function renderRankingResponsabilidad(d) {
    const v = d.ranking_responsabilidad;
    if (!v || !v.length) return '';
    const maxCuotas = Math.max(...v.map(r => parseInt(r.cuotas_pagadas_tiempo)), 1);
    return `<div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
        <div class="card-header bg-transparent border-bottom py-3">
            <h6 class="mb-0 fw-semibold"><i class="bx bx-check-shield text-success me-1"></i> Ranking de Responsabilidad <span class="badge bg-soft-success text-success ms-2 fw-normal" style="font-size: 0.7rem;">Pagos a Tiempo</span></h6>
            <p class="text-muted small mb-0 mt-1">Vendedores ordenados por cuotas pagadas antes de su fecha de vencimiento</p>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">
                    <tr>
                        <th class="border-0 ps-4">#</th>
                        <th class="border-0">Vendedor</th>
                        <th class="border-0 text-center">Pagadas a Tiempo</th>
                        <th class="border-0 pe-4" style="min-width: 180px;">Responsabilidad</th>
                    </tr>
                </thead>
                <tbody>
                    ${v.map((r, i) => {
                        const pct = r.total_cuotas > 0 ? Math.round((r.cuotas_pagadas_tiempo / r.total_cuotas) * 100) : 0;
                        const barColor = pct >= 80 ? '#28c76f' : pct >= 50 ? '#ff9f43' : '#ea5455';
                        const medal = i === 0 ? '🥇' : i === 1 ? '🥈' : i === 2 ? '🥉' : '';
                        return `<tr>
                            <td class="ps-4 fw-bold" style="font-size: 1.1rem; color: ${i === 0 ? '#ff9f43' : i === 1 ? '#a8aaae' : i === 2 ? '#cd7f32' : '#4a5568'};">${medal || (i + 1)}</td>
                            <td><span class="fw-medium">${r.nombre || '—'}</span></td>
                            <td class="text-center">
                                <span class="badge bg-success bg-gradient px-3 py-2 fs-6">${r.cuotas_pagadas_tiempo}</span>
                                <small class="text-muted d-block mt-1">de ${r.total_cuotas} cuotas</small>
                            </td>
                            <td class="pe-4">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height: 10px; border-radius: 5px; background: #e9ecef;">
                                        <div class="progress-bar" role="progressbar" style="width: ${pct}%; background: ${barColor}; border-radius: 5px;" aria-valuenow="${pct}" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <span class="fw-bold small" style="color: ${barColor};">${pct}%</span>
                                </div>
                            </td>
                        </tr>`;
                    }).join('')}
                </tbody>
            </table>
        </div>
    </div>`;
}

function initCharts(d) {
    // Pagos Pendientes
    const pp = d.pagos_pendientes || {};
    const labels = Object.keys(pp).sort();
    const data = labels.map(k => pp[k]);

    const ingresosOpts = {
        chart: { type: 'area', height: 300, toolbar: { show: false }, fontFamily: 'inherit' },
        series: [{ name: 'Por Cobrar', data }],
        xaxis: { type: 'datetime', categories: labels, labels: { format: 'dd MMM', style: { fontSize: '11px' } } },
        yaxis: { labels: { formatter: v => '$' + Number(v).toLocaleString('es-ES') } },
        colors: ['#ff9f43'],
        fill: { type: 'gradient', gradient: { shadeIntensity: 0.3, opacityFrom: 0.4, opacityTo: 0.1 } },
        stroke: { curve: 'smooth', width: 2 },
        markers: { size: 6, strokeWidth: 0, hover: { size: 8 } },
        dataLabels: { enabled: false },
        tooltip: { x: { format: 'dd MMM yyyy' }, y: { formatter: v => '$' + Number(v).toLocaleString('es-ES', { minimumFractionDigits: 2 }) } },
        grid: { borderColor: '#f0f0f0' },
    };
    if (_chartIngresos) _chartIngresos.destroy();
    _chartIngresos = new ApexCharts(document.querySelector('#chartIngresos'), ingresosOpts);
    _chartIngresos.render();

    // Top Vendedores
    const v = d.top_vendedores;
    const vLabels = v.map(r => r.nombre || '—');
    const vData = v.map(r => parseInt(r.total_asignaciones));

    const vendedoresOpts = {
        chart: { type: 'bar', height: 300, toolbar: { show: false }, fontFamily: 'inherit' },
        series: [{ name: 'Asignaciones', data: vData }],
        xaxis: { categories: vLabels, labels: { style: { fontSize: '11px' }, rotate: -45 } },
        colors: ['#28c76f'],
        plotOptions: { bar: { borderRadius: 4, horizontal: false, columnWidth: '50%' } },
        dataLabels: { enabled: false },
        tooltip: { y: { formatter: v => v + ' asignaciones' } },
        grid: { borderColor: '#f0f0f0' },
    };
    if (_chartVendedores) _chartVendedores.destroy();
    _chartVendedores = new ApexCharts(document.querySelector('#chartVendedores'), vendedoresOpts);
    _chartVendedores.render();
}
</script>
<style>
.bg-soft-success { background-color: rgba(40, 199, 111, 0.12) !important; }
</style>
