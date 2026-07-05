<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex justify-content-between align-items-center">
                <h4 class="page-title">Vendedores</h4>
                <button class="btn btn-primary" onclick="window.v.add()">+ Nuevo vendedor</button>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table id="vTabla" class="table table-hover" data-toggle="table" data-url="<?= BASE_URL ?>api/vendedores" data-response-handler="window.v.resp" data-search="true" data-pagination="true" data-page-size="15">
                <thead>
                    <tr>
                        <th data-field="nombre" data-sortable="true">Nombre</th>
                        <th data-field="cedula" data-sortable="true">Cédula</th>
                        <th data-field="telefono" data-sortable="true">Teléfono</th>
                        <th data-field="nivel" data-sortable="true" data-formatter="window.v.fNivel">Nivel</th>
                        <th data-formatter="window.v.fAcc" data-align="center">Acciones</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="vModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="vForm" onsubmit="window.v.save(event)">
                <div class="modal-header">
                    <h5 class="modal-title">Vendedor</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="vId">
                    <div class="mb-2">
                        <label for="vNombre" class="form-label">Nombre</label>
                        <input class="form-control" id="vNombre" placeholder="Nombre completo" required>
                    </div>
                    <div class="mb-2">
                        <label for="vCedula" class="form-label">Cédula</label>
                        <input class="form-control" id="vCedula" placeholder="Cédula de identidad" required>
                    </div>
                    <div class="mb-2">
                        <label for="vTelefono" class="form-label">Teléfono</label>
                        <input class="form-control" id="vTelefono" placeholder="Teléfono">
                    </div>
                    <div class="mb-2">
                        <label for="vNivel" class="form-label">Nivel</label>
                        <select class="form-control" id="vNivel" required>
                            <option value="1">1 - Inicial</option>
                            <option value="2">2 - Básico</option>
                            <option value="3">3 - Intermedio</option>
                            <option value="4">4 - Avanzado</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary">Guardar</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Detalles Vendedor -->
<div class="modal fade" id="vDetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">
                    <i class="bx bx-user me-2 text-primary"></i>Detalles del Vendedor
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-3">
                <div id="vDetLoading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="text-muted mt-2 mb-0">Cargando información...</p>
                </div>
                <div id="vDetContent" style="display:none">
                    <!-- Info del vendedor -->
                    <div class="d-flex align-items-center mb-4 p-3 bg-light rounded-3">
                        <div class="avatar-sm bg-primary rounded-circle text-white d-flex justify-content-center align-items-center me-3" style="width:56px;height:56px;font-size:1.5rem">
                            <i class="bx bx-user"></i>
                        </div>
                        <div>
                            <h5 class="mb-1 fw-bold" id="vDetNombre">—</h5>
                            <div class="text-muted small" id="vDetMeta">
                                <span id="vDetCedula">—</span> &middot; <span id="vDetTelefono">—</span> &middot; Nivel <span id="vDetNivel">—</span>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <div class="card bg-soft-primary border-0 h-100">
                                <div class="card-body p-3">
                                    <h6 class="fw-semibold mb-0 small text-uppercase"><i class="bx bx-package me-1"></i>Asignaciones Activas</h6>
                                    <div id="vDetAsignaciones" class="mt-2" style="max-height:160px;overflow-y:auto"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="card border-0 h-100" style="background:rgba(165,148,249,0.08)">
                                <div class="card-body p-3">
                                    <h6 class="fw-semibold mb-0 small text-uppercase"><i class="bx bx-gift me-1" style="color:#a594f9"></i>Premios Pendientes</h6>
                                    <div id="vDetPremios" class="mt-2" style="max-height:160px;overflow-y:auto"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-semibold mb-2"><i class="bx bx-credit-card me-1 text-warning"></i>Cuotas Pendientes</h6>
                    <div style="max-height:280px;overflow-y:auto;border:1px solid #e9ecef;border-radius:8px" id="vDetCuotas"></div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
    window.v = {
        api: '<?= BASE_URL ?>api/vendedores',
        resp: (res) => ({
            rows: res.data || [],
            total: res.data?.length || 0
        }),
        fNivel: (v) => `<span class="badge bg-secondary">Nivel ${v}</span>`,
        fAcc: (val, x) => {
            let xJ = JSON.stringify(x).replace(/'/g, "&apos;");
            return `<button class="btn btn-sm btn-info" onclick='window.v.edit(${xJ})'>Editar</button>
                <button class="btn btn-sm btn-secondary" onclick='window.v.detalles(${xJ})'>Detalles</button>
                <button class="btn btn-sm btn-danger" onclick="window.v.del('${x.id}')">Borrar</button>`;
        },
        add() {
            document.getElementById('vForm').reset();
            document.getElementById('vId').value = '';
            new bootstrap.Modal(document.getElementById('vModal')).show();
        },
        edit(x) {
            document.getElementById('vId').value = x.id;
            document.getElementById('vNombre').value = x.nombre;
            document.getElementById('vCedula').value = x.cedula;
            document.getElementById('vTelefono').value = x.telefono;
            document.getElementById('vNivel').value = x.nivel;
            new bootstrap.Modal(document.getElementById('vModal')).show();
        },
        async save(e) {
            e.preventDefault();
            let i = document.getElementById('vId').value;
            let b = {
                nombre: document.getElementById('vNombre').value,
                cedula: document.getElementById('vCedula').value,
                telefono: document.getElementById('vTelefono').value,
                nivel: document.getElementById('vNivel').value
            };
            await fetch(this.api + (i ? '/' + i : ''), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(b)
            });
            bootstrap.Modal.getInstance(document.getElementById('vModal')).hide();
            $('#vTabla').bootstrapTable('refresh');
        },
        async del(i) {
            if (!confirm('¿Borrar?')) return;
            await fetch(this.api + '/' + i, {
                method: 'DELETE'
            });
            $('#vTabla').bootstrapTable('refresh');
        },
        async detalles(x) {
            document.getElementById('vDetLoading').style.display = '';
            document.getElementById('vDetContent').style.display = 'none';
            const modal = new bootstrap.Modal(document.getElementById('vDetModal'));
            modal.show();

            try {
                const res = await fetch(this.api + '/' + x.id + '/detalles');
                const json = await res.json();
                if (!json.value || !json.data) {
                    document.getElementById('vDetLoading').innerHTML = '<p class="text-danger">' + (json.message || 'Error') + '</p>';
                    return;
                }
                const d = json.data;

                document.getElementById('vDetNombre').textContent = d.vendedor.nombre || '—';
                document.getElementById('vDetCedula').textContent = d.vendedor.cedula || '—';
                document.getElementById('vDetTelefono').textContent = d.vendedor.telefono || '—';
                document.getElementById('vDetNivel').textContent = d.vendedor.nivel || '—';

                // Asignaciones
                const asigEl = document.getElementById('vDetAsignaciones');
                if (d.asignaciones && d.asignaciones.length) {
                    asigEl.innerHTML = d.asignaciones.map(a =>
                        `<div class="d-flex justify-content-between align-items-center border-bottom py-2">
                            <div><span class="fw-semibold">${a.coleccion || '—'}</span><br><small class="text-muted">${a.empresa || ''}</small></div>
                            <span class="badge bg-${a.estado === 'activa' ? 'success' : 'secondary'}">${a.estado || ''}</span>
                        </div>`
                    ).join('');
                } else {
                    asigEl.innerHTML = '<div class="text-muted py-2">Sin asignaciones activas.</div>';
                }

                // Cuotas
                const cuoEl = document.getElementById('vDetCuotas');
                if (d.cuotas && d.cuotas.length) {
                    cuoEl.innerHTML = '<table class="table table-sm table-hover mb-0"><thead class="table-light"><tr><th>#</th><th>Colección</th><th>Empresa</th><th>Vence</th><th class="text-end">Monto</th><th class="text-center">Status</th></tr></thead><tbody>' +
                        d.cuotas.map(c => {
                            const badgeCls = {pendiente:'bg-secondary',vencido:'bg-danger','dentro_de_margen':'bg-warning text-dark'};
                            return `<tr>
                                <td class="fw-medium">${c.numero_cuota || '—'}</td>
                                <td>${c.coleccion || ''}</td>
                                <td>${c.empresa || ''}</td>
                                <td class="text-nowrap">${c.fecha_pago || ''}</td>
                                <td class="text-end fw-semibold">$${parseFloat(c.monto_pendiente||0).toFixed(2)}</td>
                                <td class="text-center"><span class="badge ${badgeCls[c.estatus_pago] || 'bg-secondary'}">${c.estatus_pago || ''}</span></td>
                            </tr>`;
                        }).join('') + '</tbody></table>';
                } else {
                    cuoEl.innerHTML = '<div class="text-muted py-2">Sin cuotas pendientes.</div>';
                }

                // Premios
                const premEl = document.getElementById('vDetPremios');
                if (d.premios && d.premios.length) {
                    premEl.innerHTML = d.premios.map(p =>
                        `<div class="d-flex justify-content-between align-items-center border-bottom py-2">
                            <div><span class="fw-semibold">${p.nombre || '—'}</span><br><small class="text-muted">$${parseFloat(p.valor||0).toFixed(2)}</small></div>
                            <span class="badge bg-warning text-dark">${p.status || ''}</span>
                        </div>`
                    ).join('');
                } else {
                    premEl.innerHTML = '<div class="text-muted py-2">Sin premios pendientes.</div>';
                }

                document.getElementById('vDetLoading').style.display = 'none';
                document.getElementById('vDetContent').style.display = '';
            } catch (e) {
                document.getElementById('vDetLoading').innerHTML = '<p class="text-danger">Error de conexión.</p>';
            }
        }
    };
</script>