<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex justify-content-between align-items-center">
                <h4 class="page-title">Control de pagos</h4>


                <div class="d-flex gap-3 legend">
                    <div class="gap-2">
                        <span class="badge bg-secondary" title="PENDIENTE">P</span> <small>PENDIENTE</small>
                    </div>
                    <div class="gap-2">
                        <span class="badge bg-success" title="REALIZADO">R</span> <small>REALIZADO</small>
                    </div>
                    <div class="gap-2">
                        <span class="badge bg-danger" title="VENCIDO">V</span> <small>VENCIDO</small>
                    </div>
                    <div class="gap-2">
                        <span class="badge bg-warning text-dark" title="MARGEN DE PAGO">M</span> <small>MARGEN DE PAGO</small>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">

            <div class="d-flex gap-3 mb-3 align-items-end flex-wrap">
                <div>
                    <label for="empresa" class="form-label">Empresa</label>
                    <select id="empresa" class="form-select"></select>
                </div>
                <div>
                    <label for="campaniaSelect" class="form-label">Campaña</label>
                    <select id="campaniaSelect" class="form-select"></select>
                </div>
                <div>
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <input type="text" class="form-control" id="buscador" placeholder="Buscar vendedor..." style="width:200px">
                    </div>
                </div>
                <div>
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-1">
                        <button class="btn btn-outline-primary" id="btnAgrupar">Desagrupar</button>
                        <button class="btn btn-outline-secondary" id="btnScroll" title="Desplazar horizontalmente">→</button>
                    </div>
                </div>
            </div>

            <div class="table-responsive" id="tableWrapper" style="max-height:600px;overflow:auto">
                <table id="aTabla" class="table table-bordered table-striped table-sm mb-0" style="min-width:600px">
                    <thead>
                        <tr id="aTablaHead"></tr>
                    </thead>
                    <tbody id="aTablaBody">
                        <tr>
                            <td colspan="3" class="text-center text-muted">Seleccione empresa y campaña.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Cargar Pago -->
<div class="modal fade" id="cpModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" id="cpModalDialog">
        <div class="modal-content">
            <form id="cpForm">
                <input type="hidden" name="empresa_id" id="cpEmpresaId">
                <input type="hidden" name="temporada_id" id="cpTempId">
                <input type="hidden" name="vendedor_id" id="cpVendedorId">
                <div class="modal-header">
                    <h5 class="modal-title">Cargar Pago</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12" id="cpFormCol">
                            <p><strong>Vendedor:</strong> <span id="cpVendedorLabel"></span></p>
                            <p><strong>Deuda total:</strong> <span id="cpDeudaTotal">$0.00</span></p>
                            <hr>
                            <div class="mb-3">
                                <label for="cpTipoPago" class="form-label">Tipo de pago</label>
                                <select class="form-select" id="cpTipoPago" name="tipo_pago" required>
                                    <option value="">Seleccione...</option>
                                    <option value="total">Liquidación Total de la deuda</option>
                                    <!-- <option value="cuota_exacta">Pago de cuota exacta</option> -->
                                    <option value="abono">Abono</option>
                                </select>
                            </div>
                            <div class="mb-3" id="cpCuotaGroup" style="display:none">
                                <label for="cpCuotaSelect" class="form-label">Seleccionar cuota</label>
                                <select class="form-select" id="cpCuotaSelect" name="cuota_id">
                                    <option value="">Seleccione cuota...</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="cpMonto" class="form-label" id="cpMontoLabel">Monto pagado (USD)</label>
                                <input type="number" step="0.01" class="form-control" id="cpMonto" name="monto" required>
                            </div>
                            <div class="mb-3">
                                <label for="cpNumOp" class="form-label">Número de operación</label>
                                <input type="text" class="form-control" id="cpNumOp" name="numero_operacion">
                            </div>
                            <div class="mb-3">
                                <label for="cpComprobante" class="form-label">Comprobante (imagen)</label>
                                <input type="file" class="form-control" id="cpComprobante" name="comprobante" accept="image/*">
                            </div>
                        </div>
                        <div class="col-md-6" id="cpAbonoPreview" style="display:none">
                            <h6>Distribución del abono</h6>
                            <div class="table-responsive" style="max-height:400px;overflow-y:auto">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th>#</th>
                                            <th>Fecha</th>
                                            <th>Colección</th>
                                            <th>Monto</th>
                                            <th>Pendiente</th>
                                            <th>Resultado</th>
                                        </tr>
                                    </thead>
                                    <tbody id="cpAbonoPreviewBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Registrar pago</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Estatus de Deuda -->
<div class="modal fade" id="cpDeudaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">
                    <i class="bx bx-pie-chart-alt-2 me-2 text-primary"></i>Estatus de Deuda
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-3">
                <div id="cpDeudaLoading" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="text-muted mt-2 mb-0">Cargando información de deuda...</p>
                </div>
                <div id="cpDeudaContent" style="display:none">
                    <!-- Resumen -->
                    <div class="row g-3 mb-4">
                        <div class="col-sm-4">
                            <div class="card bg-soft-primary border-0 shadow-sm">
                                <div class="card-body text-center py-3">
                                    <div class="text-muted small text-uppercase fw-semibold tracking-wide">Deuda Total</div>
                                    <div class="display-6 fw-bold text-primary mt-1" id="cpDeudaTotalLabel">$0.00</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="card bg-soft-success border-0 shadow-sm">
                                <div class="card-body text-center py-3">
                                    <div class="text-muted small text-uppercase fw-semibold tracking-wide">Total Pagado</div>
                                    <div class="display-6 fw-bold text-success mt-1" id="cpDeudaPagadoLabel">$0.00</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="card bg-soft-warning border-0 shadow-sm">
                                <div class="card-body text-center py-3">
                                    <div class="text-muted small text-uppercase fw-semibold tracking-wide">Pendiente</div>
                                    <div class="display-6 fw-bold text-warning mt-1" id="cpDeudaPendienteLabel">$0.00</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Barra de progreso -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <small class="text-muted fw-semibold">Progreso de pago</small>
                            <small class="fw-bold" id="cpDeudaProgresoLabel">0%</small>
                        </div>
                        <div class="progress" style="height:12px;border-radius:6px">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" id="cpDeudaBarra"
                                role="progressbar" style="width:0%;border-radius:6px;background:linear-gradient(90deg,#7367f0,#0d6efd)" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                            </div>
                        </div>
                    </div>
                    <h6 class="fw-semibold mb-2"><i class="bx bx-list-ul me-1 text-muted"></i>Detalle de cuotas</h6>
                    <div class="table-responsive" style="max-height:350px;overflow-y:auto">
                        <table class="table table-sm table-hover mb-0 border">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>#</th>
                                    <th>Colección</th>
                                    <th>Monto</th>
                                    <th>Pagado</th>
                                    <th>Pendiente</th>
                                    <th>Estatus</th>
                                </tr>
                            </thead>
                            <tbody id="cpDeudaTableBody"></tbody>
                        </table>
                    </div>
                    <!-- Comprobantes -->
                    <div class="mt-3" id="cpDeudaCompSection">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <h6 class="fw-semibold mb-0"><i class="bx bx-receipt me-1 text-muted"></i>Comprobantes</h6>
                            <span class="badge bg-primary rounded-pill" id="cpDeudaCompCount">0</span>
                            <button class="btn btn-sm btn-link text-decoration-none p-0 ms-auto" type="button" id="cpDeudaCompToggle" data-bs-toggle="collapse" data-bs-target="#cpDeudaCompList" aria-expanded="false">
                                <i class="bx bx-chevron-down fs-5"></i>
                            </button>
                        </div>
                        <div class="collapse" id="cpDeudaCompList">
                            <div class="table-responsive" style="max-height:250px;overflow-y:auto">
                                <table class="table table-sm table-hover mb-0 border">
                                    <thead class="table-light">
                                        <tr>
                                            <th># Cuota</th>
                                            <th>Monto</th>
                                            <th>N° Operación</th>
                                            <th>Archivo</th>
                                            <th>Fecha</th>
                                        </tr>
                                    </thead>
                                    <tbody id="cpDeudaCompBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Custom dropdown menu (appended to body) -->
<div id="cpDropMenu" class="dropdown-menu shadow border-0" style="position:fixed;display:none;min-width:180px;z-index:9999">
    <a class="dropdown-item py-2" href="#" id="cpDropPago"><i class="bx bx-dollar-circle me-2 text-success"></i>Cargar pago</a>
    <hr class="dropdown-divider my-1">
    <a class="dropdown-item py-2" href="#" id="cpDropDeuda"><i class="bx bx-bar-chart-alt-2 me-2 text-primary"></i>Estatus de deuda</a>
</div>

<style>
    #aTabla th:nth-child(1),
    #aTabla td:nth-child(1) {
        position: sticky;
        left: 0;
        z-index: 3;
        background: #fff;
    }

    #aTabla th:nth-child(2),
    #aTabla td:nth-child(2) {
        position: sticky;
        left: 200px;
        z-index: 2;
        background: #fff;
    }

    #aTabla th:nth-child(1) {
        z-index: 5;
    }

    #aTabla th:nth-child(2) {
        z-index: 4;
    }


    #aTabla .cuota-header {
        white-space: nowrap;
        min-width: 60px;
        font-size: .75rem;
        vertical-align: middle;
        text-align: center !important;
        line-height: 1.2;
    }

    .cuota-pagada {
        background: #d4edda !important;
    }

    .cuota-vencido {
        background: #f8d7da !important;
    }

    .cuota-dentro_de_margen {
        background: #fff3cd !important;
    }

    .cuota-proxima {
        background: #cce5ff !important;
        font-weight: bold;
        color: #004085;
    }

    /* bg-soft helpers */
    .bg-soft-primary {
        background: rgba(115, 103, 240, .08) !important;
    }

    .bg-soft-success {
        background: rgba(40, 199, 111, .08) !important;
    }

    .bg-soft-warning {
        background: rgba(255, 171, 0, .08) !important;
    }

    .tracking-wide {
        letter-spacing: .04em;
    }

    .dropdown-item:active {
        background: #7367f0;
    }
</style>

<script>
    const BASE = '<?= BASE_URL ?>';
    let _allRows = [];
    let _fechas = [];
    let _agrupado = true;
    let _diasRetraso = 3;
    let _cpCuotas = [];

    const badgeMap = {
        pendiente: '<span class="badge bg-secondary" title="PENDIENTE">P</span>',
        realizado: '<span class="badge bg-success" title="REALIZADO">R</span>',
        vencido: '<span class="badge bg-danger" title="VENCIDO">V</span>',
        dentro_de_margen: '<span class="badge bg-warning text-dark" title="MARGEN DE PAGO">M</span>',
    };
    const classMap = {
        realizado: 'cuota-pagada',
        vencido: 'cuota-vencido',
        dentro_de_margen: 'cuota-dentro_de_margen',
    };

    function remarcarIdx() {
        const hoyMs = Date.now();
        const dPerm = _diasRetraso;
        let idx = 0;
        for (let i = 0; i < _fechas.length; i++) {
            const fMs = new Date(_fechas[i] + 'T12:00:00').getTime();
            if (fMs <= hoyMs) {
                const dias = Math.floor((hoyMs - fMs) / 86400000);
                if (dias <= dPerm) {
                    idx = i;
                    break;
                }
            } else {
                if (i === 0) {
                    idx = 0;
                    break;
                }
                const prevMs = new Date(_fechas[i - 1] + 'T12:00:00').getTime();
                if (prevMs > hoyMs || Math.floor((hoyMs - prevMs) / 86400000) > dPerm) {
                    idx = i;
                    break;
                }
            }
            if (i === _fechas.length - 1) idx = i;
        }
        return idx;
    }

    function renderizar(filas) {
        const thead = document.getElementById('aTablaHead');
        const tbody = document.getElementById('aTablaBody');
        const colsExtra = _agrupado ? 2 : 3;
        const remIdx = remarcarIdx();

        let headerHtml = `<th style="position:sticky;left:0;z-index:3;background:#fff;min-width:200px">Vendedor</th>
        <th style="position:sticky;left:200px;z-index:3;background:#fff;width:40px"></th>`;
        if (!_agrupado) {
            headerHtml += `<th style="min-width:140px">Colección</th>`;
        }
        headerHtml += `<th style="min-width:80px">Monto</th>`;
        _fechas.forEach((f, i) => {
            const [y, m, d] = f.split('-');
            const label = `${parseInt(m)}/${parseInt(d)}<br><small class="text-muted">${y}</small>`;
            headerHtml += `<th class="cuota-header${i === remIdx ? ' cuota-proxima' : ''}" style="min-width:70px">${label}</th>`;
        });
        thead.innerHTML = headerHtml;

        if (!filas.length) {
            tbody.innerHTML = `<tr><td colspan="${colsExtra + _fechas.length}" class="text-center text-muted">Sin datos.</td></tr>`;
            return;
        }

        tbody.innerHTML = filas.map(r => {
            const cuotasMap = {};
            (r.cuotas || []).forEach(c => {
                cuotasMap[c.fecha_pago] = c;
            });

            const dots = `<button class="btn btn-sm btn-outline-secondary a-dd-btn" onclick="event.stopPropagation();abrirMenu(this,${r.vendedor_id})">⋮</button>`;

            let cells = `<td style="position:sticky;left:0;background:#fff"><strong>${r.vendedor_nombre}</strong><br><small class="text-muted">${r.vendedor_cedula || ''}</small></td>
            <td style="position:sticky;left:200px;background:#fff;text-align:center">${dots}</td>`;
            if (!_agrupado) {
                cells += `<td>${r.coleccion_nombre}<br><small class="text-muted">${r.coleccion_tipo}</small></td>`;
            }
            cells += `<td>$${parseFloat(r.precio_venta_vendedor || 0).toFixed(2)}</td>`;

            _fechas.forEach(f => {
                const c = cuotasMap[f];
                const badge = c ? (badgeMap[c.estatus_pago] || c.estatus_pago) : '<span class="text-muted">—</span>';
                const cls = c ? (classMap[c.estatus_pago] || '') : '';
                cells += `<td class="${cls} text-center">${badge}</td>`;
            });

            return `<tr>${cells}</tr>`;
        }).join('');
    }

    function agruparPorVendedor(rows) {
        const grupos = {};
        rows.forEach(r => {
            const key = r.vendedor_nombre + '|' + (r.vendedor_cedula || '');
            if (!grupos[key]) {
                grupos[key] = {
                    vendedor_id: r.vendedor_id,
                    vendedor_nombre: r.vendedor_nombre,
                    vendedor_cedula: r.vendedor_cedula,
                    precio_venta_vendedor: 0,
                    cuotas: [],
                };
            }
            grupos[key].precio_venta_vendedor += parseFloat(r.precio_venta_vendedor || 0);
            (r.cuotas || []).forEach(c => grupos[key].cuotas.push(c));
        });
        return Object.values(grupos);
    }

    function filtrarLocal(rows) {
        const q = document.getElementById('buscador').value.toLowerCase().trim();
        if (!q) return rows;
        return rows.filter(r => (r.vendedor_nombre || '').toLowerCase().includes(q) || (r.vendedor_cedula || '').includes(q));
    }

    async function cargarTabla() {
        const empresaId = document.getElementById('empresa').value;
        const tempId = document.getElementById('campaniaSelect').value;
        if (!empresaId || !tempId) return;

        const res = await fetch(`${BASE}api/control-pagos?empresa_id=${empresaId}&temporada_id=${tempId}`);
        const json = await res.json();

        _fechas = json.data?.cuotas_fechas || [];
        _allRows = json.data?.rows || [];
        _diasRetraso = json.data?.dias_retraso_permitido ?? 3;

        const filas = _agrupado ? agruparPorVendedor(_allRows) : _allRows;
        renderizar(filtrarLocal(filas));
    }

    async function init() {
        const [re, rt] = await Promise.all([
            fetch(BASE + 'api/empresas').then(r => r.json()),
            fetch(BASE + 'api/temporadas').then(r => r.json()),
        ]);
        const empresas = re.data || [];
        const temporadas = rt.data || [];
        const hoyStr = new Date().toISOString().slice(0, 10);

        const selEmp = document.getElementById('empresa');
        empresas.forEach(e => {
            selEmp.innerHTML += `<option value="${e.id}">${e.nombre}</option>`;
        });

        function filtrarTemporadas(empresaId) {
            const filtradas = temporadas.filter(t => t.empresa_id === empresaId);
            const selCamp = document.getElementById('campaniaSelect');
            selCamp.innerHTML = '';
            let found = false;
            filtradas.forEach(t => {
                const enCurso = hoyStr >= t.fecha_inicio && hoyStr <= t.fecha_fin;
                if (enCurso) found = true;
                selCamp.innerHTML += `<option value="${t.id}"${enCurso ? ' selected' : ''}>${t.nombre}</option>`;
            });
            if (!found && selCamp.options.length) selCamp.options[0].selected = true;
        }

        if (empresas.length) {
            selEmp.value = empresas[0].id;
            filtrarTemporadas(selEmp.value);
            cargarTabla();
        }

        selEmp.addEventListener('change', () => {
            filtrarTemporadas(selEmp.value);
            cargarTabla();
        });
        document.getElementById('campaniaSelect').addEventListener('change', cargarTabla);

        // Scroll
        const wrapper = document.getElementById('tableWrapper');
        document.getElementById('btnScroll').addEventListener('click', () => wrapper.scrollBy({
            left: 300,
            behavior: 'smooth'
        }));

        // Toggle agrupar
        const btnAgrupar = document.getElementById('btnAgrupar');
        btnAgrupar.addEventListener('click', () => {
            _agrupado = !_agrupado;
            btnAgrupar.textContent = _agrupado ? 'Desagrupar' : 'Agrupar';
            btnAgrupar.classList.toggle('btn-outline-primary', _agrupado);
            btnAgrupar.classList.toggle('btn-outline-warning', !_agrupado);
            const filas = _agrupado ? agruparPorVendedor(_allRows) : _allRows;
            renderizar(filtrarLocal(filas));
        });

        // Buscador
        document.getElementById('buscador').addEventListener('input', () => {
            const filas = _agrupado ? agruparPorVendedor(_allRows) : _allRows;
            renderizar(filtrarLocal(filas));
        });
    }

    // ============ Custom Dropdown ============

    let _cpVendorActivo = 0;
    let _cpBtnActivo = null;

    window.abrirMenu = function(btn, vendedorId) {
        _cpVendorActivo = vendedorId;
        _cpBtnActivo = btn;
        const menu = document.getElementById('cpDropMenu');
        const rect = btn.getBoundingClientRect();
        menu.style.display = 'block';
        menu.style.left = (rect.left + rect.width) + 'px';
        menu.style.top = rect.top + 'px';
    };

    document.addEventListener('click', function(e) {
        if (!e.target.closest('#cpDropMenu')) {
            document.getElementById('cpDropMenu').style.display = 'none';
        }
    });

    document.getElementById('cpDropPago').addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('cpDropMenu').style.display = 'none';
        const btn = _cpBtnActivo;
        if (!btn) return;
        abrirPago(btn, _cpVendorActivo);
    });

    document.getElementById('cpDropDeuda').addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('cpDropMenu').style.display = 'none';
        const btn = _cpBtnActivo;
        if (!btn) return;
        verDeuda(btn, _cpVendorActivo);
    });

    // ============ Cargar Pago ============

    function abrirPago(btn, vendedorId) {
        const empresaId = document.getElementById('empresa').value;
        const tempId = document.getElementById('campaniaSelect').value;
        const vendedorNombre = btn.closest('tr').querySelector('td:nth-child(1)').textContent.trim().split('\n')[0];

        document.getElementById('cpVendedorId').value = vendedorId;
        document.getElementById('cpEmpresaId').value = empresaId;
        document.getElementById('cpTempId').value = tempId;
        document.getElementById('cpVendedorLabel').textContent = vendedorNombre;
        document.getElementById('cpTipoPago').value = '';
        document.getElementById('cpMonto').value = '';
        document.getElementById('cpNumOp').value = '';
        document.getElementById('cpComprobante').value = '';
        document.getElementById('cpCuotaGroup').style.display = 'none';
        document.getElementById('cpMontoLabel').textContent = 'Monto pagado (USD)';

        fetch(`${BASE}api/cargar-pago/cuotas?empresa_id=${empresaId}&temporada_id=${tempId}&vendedor_id=${vendedorId}`)
            .then(r => r.json())
            .then(json => {
                const cuotas = json.data?.cuotas || [];
                const deudaTotal = json.data?.deuda_total || 0;
                _cpCuotas = cuotas;
                document.getElementById('cpDeudaTotal').textContent = '$' + deudaTotal.toFixed(2);

                const sel = document.getElementById('cpCuotaSelect');
                sel.innerHTML = '<option value="">Seleccione cuota...</option>';
                cuotas.forEach(c => {
                    sel.innerHTML += `<option value="${c.id}">#${c.numero_cuota} - ${c.coleccion_nombre} - $${parseFloat(c.monto_pendiente).toFixed(2)}</option>`;
                });

                new bootstrap.Modal(document.getElementById('cpModal')).show();
            });
    }

    // ============ Estatus de Deuda ============

    function verDeuda(btn, vendedorId) {
        const empresaId = document.getElementById('empresa').value;
        const tempId = document.getElementById('campaniaSelect').value;
        const vendedorNombre = btn.closest('tr').querySelector('td:nth-child(1)').textContent.trim().split('\n')[0];

        document.getElementById('cpDeudaLoading').style.display = '';
        document.getElementById('cpDeudaContent').style.display = 'none';
        const modal = new bootstrap.Modal(document.getElementById('cpDeudaModal'));
        modal.show();

        fetch(`${BASE}api/cargar-pago/deuda?empresa_id=${empresaId}&temporada_id=${tempId}&vendedor_id=${vendedorId}`)
            .then(r => r.json())
            .then(json => {
                if (!json.value) {
                    document.getElementById('cpDeudaLoading').innerHTML = '<p class="text-danger">' + (json.message || 'Error al cargar') + '</p>';
                    return;
                }
                const d = json.data;
                const cuotas = d.cuotas || [];
                const total = parseFloat(d.total_deuda) || 0;
                const pagado = parseFloat(d.total_pagado) || 0;
                const pendiente = Math.max(0, parseFloat(d.pendiente) || 0);
                const pct = total > 0 ? (pagado / total * 100) : 0;

                document.getElementById('cpDeudaTotalLabel').textContent = '$' + total.toFixed(2);
                document.getElementById('cpDeudaPagadoLabel').textContent = '$' + pagado.toFixed(2);
                document.getElementById('cpDeudaPendienteLabel').textContent = '$' + pendiente.toFixed(2);
                document.getElementById('cpDeudaProgresoLabel').textContent = pct.toFixed(1) + '%';
                const barra = document.getElementById('cpDeudaBarra');
                barra.style.width = pct + '%';
                barra.setAttribute('aria-valuenow', pct);
                barra.textContent = pct > 0 ? pct.toFixed(1) + '%' : '';

                const tbody = document.getElementById('cpDeudaTableBody');
                tbody.innerHTML = cuotas.map(c => {
                    const mp = parseFloat(c.monto_a_pagar) || 0;
                    const pp = parseFloat(c.monto_pagado) || 0;
                    const pe = parseFloat(c.monto_pendiente) || 0;
                    const badgeMap = {
                        realizado: '<span class="badge bg-success" title="REALIZADO">R</span>',
                        pendiente: '<span class="badge bg-secondary" title="PENDIENTE">P</span>',
                        vencido: '<span class="badge bg-danger" title="VENCIDO">V</span>',
                        dentro_de_margen: '<span class="badge bg-warning text-dark" title="MARGEN DE PAGO">M</span>',
                    };
                    return `<tr>
                        <td class="fw-medium">${c.numero_cuota}</td>
                        <td>${c.coleccion_nombre || ''}</td>
                        <td>$${mp.toFixed(2)}</td>
                        <td>$${pp.toFixed(2)}</td>
                        <td>$${pe.toFixed(2)}</td>
                        <td>${badgeMap[c.estatus_pago] || c.estatus_pago}</td>
                    </tr>`;
                }).join('');

                // Comprobantes
                const comps = d.comprobantes || [];
                document.getElementById('cpDeudaCompCount').textContent = comps.length;
                const compBody = document.getElementById('cpDeudaCompBody');
                if (comps.length) {
                    compBody.innerHTML = comps.map(cp => {
                        const archivo = cp.comprobante ? cp.comprobante.split('/').pop() : '—';
                        const url = cp.comprobante ? BASE + cp.comprobante : '#';
                        const fecha = cp.created_at ? cp.created_at.slice(0, 10) : '—';
                        return `<tr>
                            <td class="fw-medium">${cp.numero_cuota || '—'}</td>
                            <td>$${parseFloat(cp.monto || 0).toFixed(2)}</td>
                            <td>${cp.numero_operacion || '—'}</td>
                            <td><a href="${url}" target="_blank" class="text-primary"><i class="bx bx-file me-1"></i>${archivo}</a></td>
                            <td class="text-nowrap">${fecha}</td>
                        </tr>`;
                    }).join('');
                } else {
                    compBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-2">Sin comprobantes registrados.</td></tr>';
                }

                document.getElementById('cpDeudaLoading').style.display = 'none';
                document.getElementById('cpDeudaContent').style.display = '';
            })
            .catch(() => {
                document.getElementById('cpDeudaLoading').innerHTML = '<p class="text-danger">Error de conexión.</p>';
            });
    }

    function simularAbonoPreview(monto) {
        const tbody = document.getElementById('cpAbonoPreviewBody');
        const preview = document.getElementById('cpAbonoPreview');
        if (!monto || monto <= 0) {
            preview.style.display = 'none';
            return;
        }

        preview.style.display = '';
        tbody.innerHTML = '';

        const cuotas = _cpCuotas.filter(c => c.estatus_pago !== 'realizado')
            .sort((a, b) => a.fecha_pago.localeCompare(b.fecha_pago));

        let restante = monto;
        let rows = '';
        cuotas.forEach(c => {
            const pendiente = parseFloat(c.monto_pendiente || 0);
            let pagadoAhora, resultado, clase;
            if (restante <= 0) {
                pagadoAhora = 0;
                resultado = 'Sin cambios';
                clase = '';
            } else if (restante >= pendiente) {
                pagadoAhora = pendiente;
                resultado = 'Pagado';
                clase = 'text-success fw-bold';
                restante -= pendiente;
            } else {
                pagadoAhora = restante;
                resultado = 'Parcial';
                clase = 'text-warning fw-bold';
                restante = 0;
            }
            rows += `<tr${clase ? ' class="' + clase + '"' : ''}>
                <td>${c.numero_cuota}</td>
                <td>${c.fecha_pago}</td>
                <td>${c.coleccion_nombre || ''}</td>
                <td>$${pendiente.toFixed(2)}</td>
                <td>$${(pendiente - pagadoAhora).toFixed(2)}</td>
                <td>${resultado}</td>
            </tr>`;
        });
        tbody.innerHTML = rows;
    }

    document.getElementById('cpTipoPago').addEventListener('change', function() {
        const val = this.value;
        const dialog = document.getElementById('cpModalDialog');
        const formCol = document.getElementById('cpFormCol');
        const preview = document.getElementById('cpAbonoPreview');

        document.getElementById('cpCuotaGroup').style.display = val === 'cuota_exacta' ? '' : 'none';
        if (val === 'total') {
            document.getElementById('cpMontoLabel').textContent = 'Monto pagado (USD) — Deuda total: ';
        } else {
            document.getElementById('cpMontoLabel').textContent = 'Monto pagado (USD)';
        }
        if (val === 'abono') {
            dialog.classList.add('modal-xl');
            formCol.className = 'col-md-6';
            preview.style.display = '';
            simularAbonoPreview(parseFloat(document.getElementById('cpMonto').value));
        } else {
            dialog.classList.remove('modal-xl');
            formCol.className = 'col-md-12';
            preview.style.display = 'none';
        }
    });

    document.getElementById('cpMonto').addEventListener('input', function() {
        if (document.getElementById('cpTipoPago').value === 'abono') {
            simularAbonoPreview(parseFloat(this.value) || 0);
        }
    });

    document.getElementById('cpForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        const res = await fetch(BASE + 'api/cargar-pago', {
            method: 'POST',
            body: fd
        });
        const json = await res.json();
        if (json.value) {
            Swal.fire({
                icon: 'success',
                title: 'Pago registrado',
                timer: 1500,
                showConfirmButton: false
            });
            bootstrap.Modal.getInstance(document.getElementById('cpModal')).hide();
            cargarTabla();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: json.message
            });
        }
    });

    document.addEventListener('DOMContentLoaded', init);
</script>