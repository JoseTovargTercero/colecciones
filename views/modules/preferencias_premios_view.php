<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1" style="color: #4a5568;"><i class="bx bx-gift text-primary me-2"></i>Preferencias de Premios</h4>
            <p class="text-muted mb-0 small">Listado de premios pendientes y vendedores con pagos a tiempo.</p>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
        <div class="card-body p-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="ppEmpresa" class="form-label">Empresa</label>
                    <select id="ppEmpresa" class="form-select"></select>
                </div>
                <div class="col-md-3">
                    <label for="ppCampania" class="form-label">Campaña</label>
                    <select id="ppCampania" class="form-select"></select>
                </div>
                <div class="col-md-3">
                    <label for="ppSearchInput" class="form-label">Buscar</label>
                    <input type="text" class="form-control" id="ppSearchInput" placeholder="Nombre, cédula o premio...">
                </div>
                <div class="col-md-3">
                    <button class="btn btn-primary shadow-sm w-100" onclick="cargarTabla()" style="border-radius: 8px;">
                        <i class="bx bx-refresh me-1"></i>Actualizar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
        <div class="card-header bg-white border-bottom nav d-flex gap-3 p-0 ps-3 pp-tabs" style="border-radius: 12px 12px 0 0;" role="tablist">
            <button class="nav-link active px-3 py-3 fw-semibold" id="tab-premios" data-bs-toggle="tab" data-bs-target="#tabPremios" type="button" role="tab">
                <i class="bx bx-gift me-1"></i>Premios Asignados
            </button>
            <button class="nav-link px-3 py-3 fw-semibold" id="tab-pagos-tiempo" data-bs-toggle="tab" data-bs-target="#tabPagosTiempo" type="button" role="tab">
                <i class="bx bx-check-circle me-1"></i>Premiar responsabilidad
            </button>
            <button class="nav-link px-3 py-3 fw-semibold" id="tab-historial" data-bs-toggle="tab" data-bs-target="#tabHistorial" type="button" role="tab">
                <i class="bx bx-history me-1"></i>Historial
            </button>
        </div>

        <div class="tab-content" id="ppTabContent">
            <div class="tab-pane fade show active" id="tabPremios" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="ppTable">
                        <thead class="bg-light text-muted" style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">
                            <tr>
                                <th class="ps-4 border-0">Vendedor</th>
                                <th class="border-0">Empresa / Temp</th>
                                <th class="border-0">Premio</th>
                                <th class="border-0 text-center">Estatus</th>
                                <th class="border-0">Fecha Solicitud</th>
                                <th class="pe-4 border-0 text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="ppTableBody" class="border-top-0">
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="spinner-border text-primary" role="status"></div>
                                    <div class="text-muted mt-2">Cargando datos...</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="tabPagosTiempo" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="ppTablePagosTiempo">
                        <thead class="bg-light text-muted" style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">
                            <tr>
                                <th class="ps-4 border-0">Vendedor</th>
                                <th class="border-0">Cédula</th>
                                <th class="border-0 text-center">Cuotas Pagadas a Tiempo</th>
                                <th class="pe-4 border-0 text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="ppTablePagosTiempoBody">
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">Seleccione empresa y campaña para ver resultados.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="tabHistorial" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="ppTableHistorial">
                        <thead class="bg-light text-muted" style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">
                            <tr>
                                <th class="ps-4 border-0">Vendedor</th>
                                <th class="border-0">Empresa / Temp</th>
                                <th class="border-0">Premio</th>
                                <th class="border-0">Fecha Entrega</th>
                            </tr>
                        </thead>
                        <tbody id="ppTableHistorialBody">
                            <tr>
                                <td colspan="4" class="text-center py-5">
                                    <div class="spinner-border text-primary" role="status"></div>
                                    <div class="text-muted mt-2">Cargando...</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Asignar Premios (Pagos a Tiempo) -->
<div class="modal fade" id="ppAsignarModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
            <div class="modal-header border-bottom-0 pb-0">
                <div class="py-2 px-1">
                    <h5 class="modal-title fw-bold mb-1">
                        <i class="bx bx-gift me-2"></i>Asignar Premio(s)
                    </h5>
                    <p class="text-black-50 mb-0 small">Seleccione un premio para cada cuota pagada a tiempo</p>
                </div>
                <button type="button" class="btn-close btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="ppAsignarForm">
                <input type="hidden" name="empresa_id" id="ppAsignarEmpresaId">
                <input type="hidden" name="temporada_id" id="ppAsignarTempId">
                <input type="hidden" name="vendedor_id" id="ppAsignarVendedorId">
                <div class="modal-body">
                    <div class="bg-light rounded-3 p-3 mb-3 d-flex align-items-center border-start border-4" style="border-color: #7367f0 !important;">
                        <div class="avatar-sm me-3 d-flex align-items-center justify-content-center rounded-circle" style="width: 42px; height: 42px; background: #7367f0;">
                            <i class="bx bx-user text-white fs-5"></i>
                        </div>
                        <div>
                            <small class="text-muted text-uppercase fw-semibold" style="font-size: 11px; letter-spacing: 0.5px;">Vendedor</small>
                            <p class="mb-0 fw-semibold" id="ppAsignarVendedorNombre" style="font-size: 15px;"></p>
                        </div>
                    </div>

                    <div id="ppAsignarCuotasLoading" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status"></div>
                        <div class="text-muted mt-2">Cargando cuotas...</div>
                    </div>
                    <div id="ppAsignarCuotasWrap" style="display:none">
                        <label class="form-label fw-semibold mb-2">
                            <i class="bx bx-check-circle me-1 text-success"></i>Cuotas pagadas a tiempo
                        </label>
                        <div class="table-responsive" style="max-height:320px;overflow-y:auto">
                            <table class="table table-sm table-hover align-middle mb-0" id="ppAsignarCuotasTable">
                                <thead class="table-light small text-muted text-uppercase" style="position:sticky;top:0;z-index:1">
                                    <tr>
                                        <th>#</th>
                                        <th>Colección</th>
                                        <th>Fecha pago</th>
                                        <th class="text-end">Monto</th>
                                        <th class="text-center">Premio</th>
                                    </tr>
                                </thead>
                                <tbody id="ppAsignarCuotasBody"></tbody>
                            </table>
                        </div>
                        <div id="ppAsignarResumen" class="mt-3 d-none">
                            <hr class="my-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted small">Cuotas con premio asignado:</span>
                                <span class="badge bg-primary rounded-pill" id="ppAsignarCount">0</span>
                            </div>
                        </div>
                    </div>
                    <div id="ppAsignarNoCuotas" class="text-center py-4 text-muted" style="display:none">
                        <i class="bx bx-info-circle fs-3 mb-2 d-block"></i>
                        Este vendedor no tiene cuotas pagadas a tiempo sin premiar.
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal" style="border-radius: 8px;">Cancelar</button>
                    <button type="submit" class="btn btn-primary px-4 shadow-sm" id="ppAsignarSubmitBtn" style="border-radius: 8px; background: #7367f0; border-color: #7367f0;">
                        <i class="bx bx-check me-1"></i>Asignar Premio(s)
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const BASE = '<?= BASE_URL ?>';
    let _allData = [];
    let _allPagosTiempo = [];

    document.addEventListener('DOMContentLoaded', async () => {
        const [re, rt] = await Promise.all([
            fetch(BASE + 'api/empresas').then(r => r.json()),
            fetch(BASE + 'api/temporadas').then(r => r.json()),
        ]);
        const empresas = re.data || [];
        const temporadas = rt.data || [];
        const hoyStr = new Date().toISOString().slice(0, 10);

        const selEmp = document.getElementById('ppEmpresa');
        empresas.forEach(e => {
            selEmp.innerHTML += `<option value="${e.id}">${e.nombre}</option>`;
        });

        function filtrarCampanias(empresaId) {
            const filtradas = temporadas.filter(t => t.empresa_id == empresaId);
            const selCamp = document.getElementById('ppCampania');
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
            filtrarCampanias(selEmp.value);
            cargarPagosTiempo();
        }
        cargarTabla();

        selEmp.addEventListener('change', () => {
            filtrarCampanias(selEmp.value);
            cargarTabla();
            cargarPagosTiempo();
        });
        document.getElementById('ppCampania').addEventListener('change', () => {
            cargarTabla();
            cargarPagosTiempo();
        });

        const cedula = new URLSearchParams(window.location.search).get('cedula');
        if (cedula) {
            document.getElementById('ppSearchInput').value = cedula;
        }

        document.getElementById('ppSearchInput').addEventListener('input', function(e) {
            renderTabla(e.target.value);
        });

        cargarHistorial();
        document.getElementById('tab-historial').addEventListener('shown.bs.tab', () => {
            const tbody = document.getElementById('ppTableHistorialBody');
            if (tbody.querySelector('.spinner-border')) cargarHistorial();
        });
    });

    async function cargarTabla() {
        const tbody = document.getElementById('ppTableBody');
        tbody.innerHTML = `<tr><td colspan="6" class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><div class="text-muted mt-2">Cargando datos...</div></td></tr>`;

        try {
            const res = await fetch(BASE + 'api/preferencias-premios');
            const json = await res.json();
            if (json.value) {
                _allData = json.data || [];
                renderTabla(document.getElementById('ppSearchInput').value);
            } else {
                tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-4">${json.message}</td></tr>`;
            }
        } catch (error) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-4">Error de conexión.</td></tr>`;
        }
    }

    function renderTabla(filtro = '') {
        const tbody = document.getElementById('ppTableBody');
        const search = filtro.toLowerCase().trim();

        let filtrados = _allData;
        if (search) {
            filtrados = _allData.filter(r =>
                (r.vendedor_nombres || '').toLowerCase().includes(search) ||
                (r.vendedor_cedula || '').toLowerCase().includes(search) ||
                (r.premio_nombre || '').toLowerCase().includes(search) ||
                (r.empresa_nombre || '').toLowerCase().includes(search)
            );
        }

        if (filtrados.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4"><i class="bx bx-info-circle fs-4 mb-2 d-block"></i>No se encontraron premios pendientes.</td></tr>`;
            return;
        }

        tbody.innerHTML = filtrados.map(r => {
            const fecha = r.fecha_solicitud ? r.fecha_solicitud.slice(0, 10) : '—';
            const nombreVendedor = `${r.vendedor_nombres || ''}`.trim();
            const valor = parseFloat(r.premio_valor || 0).toFixed(2);

            return `
                <tr>
                    <td class="ps-4">
                        <div class="d-flex align-items-center">
                            <div class="avatar-sm bg-soft-primary rounded-circle text-primary d-flex justify-content-center align-items-center me-3" style="width: 40px; height: 40px;">
                                <i class="bx bx-user fs-5"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-semibold">${nombreVendedor || 'Desconocido'}</h6>
                                <small class="text-muted">CI: ${r.vendedor_cedula || '—'}</small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="fw-medium">${r.empresa_nombre || '—'}</div>
                        <small class="text-muted">${r.temporada_nombre || '—'}</small>
                    </td>
                    <td>
                        <div class="fw-medium" style="color: #a594f9;"><i class="bx bx-gift me-1"></i>${r.premio_nombre || '—'}</div>
                        <small class="text-muted">$${valor}</small>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-${r.status == 'completado' ? 'info' : 'warning'} text-white text-uppercase shadow-sm" style="font-size: 0.75rem;">${r.status}</span>
                    </td>
                    <td class="text-muted">${fecha}</td>
                    <td class="pe-4 text-end">
                        <button class="btn btn-sm btn-success shadow-sm" onclick="entregarPremio(${r.id})" title="Marcar como entregado" style="border-radius: 6px;">
                            <i class="bx bx-check-circle me-1"></i>Entregado
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    async function cargarPagosTiempo() {
        const empresaId = document.getElementById('ppEmpresa').value;
        const tempId = document.getElementById('ppCampania').value;
        if (!empresaId || !tempId) return;

        const tbody = document.getElementById('ppTablePagosTiempoBody');
        tbody.innerHTML = `<tr><td colspan="4" class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><div class="text-muted mt-2">Cargando...</div></td></tr>`;

        try {
            const res = await fetch(`${BASE}api/preferencias-premios/pagos-tiempo?empresa_id=${empresaId}&temporada_id=${tempId}`);
            const json = await res.json();
            if (json.value) {
                _allPagosTiempo = json.data || [];
                renderPagosTiempo();
            } else {
                tbody.innerHTML = `<tr><td colspan="4" class="text-center text-danger py-4">${json.message}</td></tr>`;
            }
        } catch (error) {
            tbody.innerHTML = `<tr><td colspan="4" class="text-center text-danger py-4">Error de conexión.</td></tr>`;
        }
    }

    function renderPagosTiempo() {
        const tbody = document.getElementById('ppTablePagosTiempoBody');

        if (_allPagosTiempo.length === 0) {
            tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted py-4"><i class="bx bx-info-circle fs-4 mb-2 d-block"></i>No se encontraron vendedores con pagos a tiempo sin premiar.</td></tr>`;
            return;
        }

        tbody.innerHTML = _allPagosTiempo.map(r => `
            <tr>
                <td class="ps-4">
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm bg-soft-success rounded-circle text-success d-flex justify-content-center align-items-center me-3" style="width: 40px; height: 40px;">
                            <i class="bx bx-user fs-5"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-semibold">${r.nombre || 'Desconocido'}</h6>
                        </div>
                    </div>
                </td>
                <td>${r.cedula || '—'}</td>
                <td class="text-center">
                    <span class="badge bg-success bg-gradient fs-6 px-3 py-2">${r.cuotas_pagadas_tiempo}</span>
                </td>
                <td class="pe-4 text-end">
                    <button class="btn btn-sm btn-outline-primary shadow-sm" onclick="abrirAsignarPremios(${r.id}, '${r.nombre}')" title="Asignar premio(s)" style="border-radius: 6px;">
                        <i class="bx bx-gift me-1"></i>Asignar premio
                    </button>
                </td>
            </tr>
        `).join('');
    }

    async function cargarHistorial() {
        const tbody = document.getElementById('ppTableHistorialBody');
        tbody.innerHTML = `<tr><td colspan="4" class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><div class="text-muted mt-2">Cargando...</div></td></tr>`;
        try {
            const res = await fetch(BASE + 'api/preferencias-premios/historial');
            const json = await res.json();
            if (json.value) {
                renderHistorial(json.data || []);
            } else {
                tbody.innerHTML = `<tr><td colspan="4" class="text-center text-danger py-4">${json.message}</td></tr>`;
            }
        } catch (_) {
            tbody.innerHTML = `<tr><td colspan="4" class="text-center text-danger py-4">Error de conexión.</td></tr>`;
        }
    }

    function renderHistorial(data) {
        const tbody = document.getElementById('ppTableHistorialBody');
        if (!data.length) {
            tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted py-4"><i class="bx bx-info-circle fs-4 mb-2 d-block"></i>No hay premios entregados aún.</td></tr>`;
            return;
        }
        tbody.innerHTML = data.map(r => {
            const fecha = r.fecha_entrega ? r.fecha_entrega : (r.fecha_solicitud ? r.fecha_solicitud.slice(0, 10) : '—');
            const valor = parseFloat(r.premio_valor || 0).toFixed(2);
            return `<tr>
                <td class="ps-4">
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm bg-soft-success rounded-circle text-success d-flex justify-content-center align-items-center me-3" style="width:40px;height:40px">
                            <i class="bx bx-user fs-5"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-semibold">${r.vendedor_nombres || 'Desconocido'}</h6>
                            <small class="text-muted">CI: ${r.vendedor_cedula || '—'}</small>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="fw-medium">${r.empresa_nombre || '—'}</div>
                    <small class="text-muted">${r.temporada_nombre || '—'}</small>
                </td>
                <td>
                    <div class="fw-medium" style="color:#a594f9"><i class="bx bx-gift me-1"></i>${r.premio_nombre || '—'}</div>
                    <small class="text-muted">$${valor}</small>
                </td>
                <td class="text-muted">${fecha}</td>
            </tr>`;
        }).join('');
    }

    let _ppPremiosDisponibles = [];

    function abrirAsignarPremios(vendedorId, vendedorNombre) {
        const empresaId = document.getElementById('ppEmpresa').value;
        const tempId = document.getElementById('ppCampania').value;
        if (!empresaId || !tempId) {
            Swal.fire('Advertencia', 'Seleccione empresa y campaña primero.', 'warning');
            return;
        }

        document.getElementById('ppAsignarEmpresaId').value = empresaId;
        document.getElementById('ppAsignarTempId').value = tempId;
        document.getElementById('ppAsignarVendedorId').value = vendedorId;
        document.getElementById('ppAsignarVendedorNombre').textContent = vendedorNombre;
        document.getElementById('ppAsignarCuotasLoading').style.display = '';
        document.getElementById('ppAsignarCuotasWrap').style.display = 'none';
        document.getElementById('ppAsignarNoCuotas').style.display = 'none';
        document.getElementById('ppAsignarResumen').classList.add('d-none');

        const modal = new bootstrap.Modal(document.getElementById('ppAsignarModal'));
        modal.show();

        Promise.all([
            fetch(`${BASE}api/preferencias-premios/premios-disponibles?empresa_id=${empresaId}`).then(r => r.json()),
            fetch(`${BASE}api/preferencias-premios/pagos-tiempo/cuotas?vendedor_id=${vendedorId}&empresa_id=${empresaId}&temporada_id=${tempId}`).then(r => r.json())
        ]).then(([premiosRes, cuotasRes]) => {
            _ppPremiosDisponibles = premiosRes.data || [];
            const cuotas = cuotasRes.data || [];

            document.getElementById('ppAsignarCuotasLoading').style.display = 'none';

            if (!cuotas.length) {
                document.getElementById('ppAsignarNoCuotas').style.display = '';
                return;
            }

            document.getElementById('ppAsignarCuotasWrap').style.display = '';

            const tbody = document.getElementById('ppAsignarCuotasBody');
            tbody.innerHTML = cuotas.map(c => `
                <tr>
                    <td class="fw-medium">${c.numero_cuota}</td>
                    <td>${c.coleccion || '—'}</td>
                    <td class="text-nowrap">${c.fecha_pago || '—'}</td>
                    <td class="text-end fw-semibold">$${parseFloat(c.monto_a_pagar || 0).toFixed(2)}</td>
                    <td class="text-center">
                        <select class="form-select form-select-sm pp-premio-select" data-cuota-id="${c.id}" style="min-width:130px">
                            <option value="">—</option>
                            ${_ppPremiosDisponibles.map(p => `<option value="${p.id}">${p.nombre}</option>`).join('')}
                        </select>
                    </td>
                </tr>
            `).join('');

            document.querySelectorAll('.pp-premio-select').forEach(el => {
                el.addEventListener('change', _ppActualizarResumen);
            });
            _ppActualizarResumen();
        }).catch(() => {
            document.getElementById('ppAsignarCuotasLoading').style.display = 'none';
            document.getElementById('ppAsignarNoCuotas').style.display = '';
            Swal.fire('Error', 'Error al cargar datos.', 'error');
        });
    }

    function _ppActualizarResumen() {
        const selects = document.querySelectorAll('.pp-premio-select');
        let count = 0;
        selects.forEach(el => { if (el.value) count++; });
        const el = document.getElementById('ppAsignarResumen');
        if (!count) {
            el.classList.add('d-none');
            return;
        }
        el.classList.remove('d-none');
        document.getElementById('ppAsignarCount').textContent = count;
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('ppAsignarModal').addEventListener('hidden.bs.modal', () => {
            document.getElementById('ppAsignarCuotasBody').innerHTML = '';
        });
    });

    document.getElementById('ppAsignarForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const asignaciones = [];
        document.querySelectorAll('.pp-premio-select').forEach(el => {
            if (el.value) {
                asignaciones.push({ cuota_id: parseInt(el.dataset.cuotaId), premio_id: parseInt(el.value) });
            }
        });

        if (!asignaciones.length) {
            Swal.fire('Seleccione al menos un premio para alguna cuota', '', 'warning');
            return;
        }

        const btn = document.getElementById('ppAsignarSubmitBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Asignando...';

        try {
            const r = await fetch(BASE + 'api/preferencias-premios/asignar-premios', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    empresa_id: parseInt(document.getElementById('ppAsignarEmpresaId').value),
                    temporada_id: document.getElementById('ppAsignarTempId').value,
                    vendedor_id: parseInt(document.getElementById('ppAsignarVendedorId').value),
                    asignaciones: asignaciones
                })
            });
            const j = await r.json();
            if (j.value) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Premio(s) asignado(s)!',
                    timer: 2000,
                    showConfirmButton: false
                });
                bootstrap.Modal.getInstance(document.getElementById('ppAsignarModal')).hide();
                cargarTabla();
                cargarPagosTiempo();
            } else {
                Swal.fire('Error', j.message, 'error');
            }
        } catch (_) {
            Swal.fire('Error', 'Problema de conexión.', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="bx bx-check me-1"></i>Asignar Premio(s)';
        }
    });

    function entregarPremio(id) {
        Swal.fire({
            title: '¿Confirmar entrega?',
            text: "El premio se marcará como entregado y ya no aparecerá en esta lista.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, entregar',
            cancelButtonText: 'Cancelar'
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    const res = await fetch(BASE + 'api/preferencias-premios/' + id + '/entregar', {
                        method: 'POST'
                    });
                    const json = await res.json();
                    if (json.value) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Entregado!',
                            text: json.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                        cargarTabla();
                        cargarHistorial();
                    } else {
                        Swal.fire('Error', json.message, 'error');
                    }
                } catch (error) {
                    Swal.fire('Error', 'Problema de conexión.', 'error');
                }
            }
        });
    }
</script>

<style>
    .bg-soft-primary {
        background-color: rgba(115, 103, 240, 0.1) !important;
    }

    .bg-soft-success {
        background-color: rgba(40, 199, 111, 0.1) !important;
    }

    .text-primary {
        color: #7367f0 !important;
    }

    #ppTabContent .tab-pane {
        padding: 0;
    }

    .pp-tabs .nav-link {
        background: none !important;
        border: none !important;
        border-bottom: 2px solid transparent !important;
        margin-bottom: -1px !important;
        color: #6c757d !important;
        transition: color .15s ease-in-out, border-color .15s ease-in-out;
    }

    .pp-tabs .nav-link:hover {
        color: #7367f0 !important;
    }

    .pp-tabs .nav-link.active {
        color: #7367f0 !important;
        border-bottom-color: #7367f0 !important;
    }
</style>