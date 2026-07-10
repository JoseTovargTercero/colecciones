<style>
    .btn-primary-light {
        gap: 3;
        text-wrap-mode: nowrap;
        color: #303030;
        background-color: #696cff17;
        border-color: #696cff6b;
        box-shadow: 0 0.125rem 0.25rem 0 rgba(105, 108, 255, 0.4);
    }

    .info-banner {
        position: relative;
        border: none;
        border-radius: 10px;
        background: linear-gradient(135deg, #f0f2ff 0%, #e8ecff 100%);
        padding: 16px 20px 16px 52px;
        margin-bottom: 16px;
        box-shadow: 0 1px 3px rgba(105, 108, 255, 0.08);
    }

    .info-banner::before {
        content: '';
        position: absolute;
        left: 0;
        top: 8px;
        bottom: 8px;
        width: 3px;
        border-radius: 2px;
        background: linear-gradient(180deg, #696cff, #8f93ff);
    }

    .info-banner .icon-wrap {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        width: 24px;
        height: 24px;
        border-radius: 6px;
        background: linear-gradient(135deg, #696cff, #8f93ff);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        box-shadow: 0 2px 6px rgba(105, 108, 255, 0.3);
    }

    .info-banner .info-text {
        font-size: 0.8125rem;
        line-height: 1.6;
        color: #3a3a5c;
    }

    .info-banner .info-text strong {
        color: #1e1e3a;
    }

    .info-banner .badge-solicitar {
        display: inline-block;
        font-size: 0.6875rem;
        font-weight: 600;
        padding: 1px 8px;
        border-radius: 4px;
        line-height: 1.7;
        color: #303030;
        background-color: #696cff17;
        border: 1px solid #696cff6b;
        vertical-align: middle;
    }
</style>
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
            <div class="info-banner" role="alert">
                <div class="icon-wrap"><i class="bx bx-info-circle"></i></div>
                <div class="info-text" style="font-size: 15px;">
                    El botón <span class="badge-solicitar">SOLICITAR PAGO <img src="<?= BASE_URL ?>/public/assets/images/logo-cd.svg" alt="Logo" width="15" height="15"></span> solo se muestra cuando el vendedor está registrado en la plataforma y tiene cuentas por pagar.
                    Invita a tus vendedores a registrarse para que puedas <strong>solicitar y recibir pagos, validar transacciones y gestionar sus solicitudes de premios, todo directamente desde la aplicación.</strong>
                </div>
            </div>
            <div id="vTablaToolbar" class="d-flex align-items-center gap-2 py-1">
                <h6 class="mb-0 fw-semibold" style="color:#495057;font-size:0.9rem">Listado de vendedores</h6>
            </div>
            <table id="vTabla" class="table table-hover" data-toggle="table" data-toolbar="#vTablaToolbar" data-url="<?= BASE_URL ?>api/vendedores" data-response-handler="window.v.resp" data-search="true" data-pagination="true" data-page-size="15">
                <thead>
                    <tr>
                        <th data-field="nombre" data-sortable="true">Nombre</th>
                        <th data-field="cedula" data-sortable="true">Cédula</th>
                        <th data-field="telefono" data-sortable="true">Teléfono</th>
                        <th data-field="nivel" data-sortable="true" data-formatter="window.v.fNivel">Nivel</th>
                        <th data-align="center" data-formatter="window.v.notif">Solicitar pago</th>
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
                            <option value="VENDEDOR">VENDEDOR</option>
                            <option value="DISTRIBUIDOR">DISTRIBUIDOR</option>
                            <option value="GTE DISTRITO">GTE DISTRITO</option>
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
        fNivel: (v) => {
            const cls = v === 'VENDEDOR' ? 'bg-secondary' : v === 'DISTRIBUIDOR' ? 'bg-primary' : 'bg-warning text-dark';
            return `<span class="badge ${cls}">${v}</span>`;
        },

        fAcc: (v, row) => {
            return `<div class="text-center">
                    <button class="btn btn-sm btn-outline-secondary a-dd-btn" data-id="${row.id}">⋮</button>
                </div>`;
        },
        _closeMenu() {
            document.querySelectorAll('.a-dd-menu').forEach(m => m.remove());
            this._menuOpen = false;
        },

        _openMenu(btn, id, x) {
            this._closeMenu();
            const rect = btn.getBoundingClientRect();
            const menu = document.createElement('ul');
            menu.className = 'dropdown-menu show a-dd-menu';
            menu.style.cssText = 'position:fixed;left:' + (rect.right - 160) + 'px;top:' + rect.bottom + 'px;z-index:9999';

            menu.innerHTML = `
                <li><a class="dropdown-item" href="javascript:void(0)" onclick='window.v.edit(${id})'><i class="bx bx-edit me-2"></i> Editar</a></li>
                <li><a class="dropdown-item" href="javascript:void(0)" onclick='window.v.detalles(${id})'><i class="bx bx-detail  me-2"></i> Detalles</a></li>
                <li><a class="dropdown-item" href="javascript:void(0)" onclick='window.v.del(${id})'><i class="bx bx-trash me-2"></i> Eliminar</a></li>
            `;

            document.body.appendChild(menu);
            this._menuOpen = true;
            setTimeout(() => document.addEventListener('click', this._onDocClick), 0);
        },


        _onDocClick(e) {
            if (!e.target.closest('.a-dd-menu, .a-dd-btn')) {
                window.v._closeMenu();
                document.removeEventListener('click', window.v._onDocClick);
            }
        },


        notif: (val, x) => {
            let xJ = JSON.stringify(x).replace(/'/g, "&apos;");
            if (x.tiene_cuenta == 1 && parseFloat(x.total_deuda) > 0) {
                return `<button class="btn btn-sm btn-primary-light" onclick='window.v.solictar_pago(${xJ})'><span>SOLICITAR</span> <img src="<?= BASE_URL ?>/public/assets/images/logo-cd.svg" alt="Logo" width="15" height="15"></button>`;
            }
            if (x.nivel === 'COMPRADOR FINAL') return;

            let tel = x.telefono ? x.telefono.replace(/[^0-9]/g, '') : '';
            if (tel.length === 11 && tel.startsWith('0')) tel = '58' + tel.slice(1);
            else if (tel.length <= 10) tel = '58' + tel;
            let msg = encodeURIComponent('¡Hola! 👋 Te invito a unirte a nuestra plataforma de colecciones. Desde allí podrás registrar tus clientes, controlar tus ventas, llevar el seguimiento de las cuotas y gestionar tus cobros de forma rápida y sencilla. Ingresa aquí: <?= rtrim(BASE_URL, '/') ?>/registro');
            return `<a class="btn btn-sm btn-success" href="https://wa.me/${tel}?text=${msg}" target="_blank" rel="noopener"><i class="bx bxl-whatsapp me-1"></i> Invitar</a>`;
        },
        add() {
            document.getElementById('vForm').reset();
            document.getElementById('vId').value = '';
            new bootstrap.Modal(document.getElementById('vModal')).show();
        },
        async edit(id) {

            const res = await fetch(this.api + '/' + id + '/detalles');
            const json = await res.json();
            if (!json.value || !json.data) {
                document.getElementById('vDetLoading').innerHTML = '<p class="text-danger">' + (json.message || 'Error') + '</p>';
                return;
            }
            const d = json.data;
            document.getElementById('vId').value = id;
            document.getElementById('vNombre').value = d.vendedor.nombre || '—';
            document.getElementById('vCedula').value = d.vendedor.cedula || '—';
            document.getElementById('vTelefono').value = d.vendedor.telefono || '—';
            document.getElementById('vNivel').value = d.vendedor.nivel || '—';

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
            const resp = await fetch(this.api + (i ? '/' + i : ''), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(b)
            });
            let msg;
            try {
                const json = await resp.json();
                if (json.value) {
                    bootstrap.Modal.getInstance(document.getElementById('vModal')).hide();
                    $('#vTabla').bootstrapTable('refresh');
                    return;
                }
                msg = json.message;
            } catch (_) {
                msg = await resp.text() || 'Error desconocido';
            }
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: msg
            });
        },
        async del(i) {
            if (!confirm('¿Borrar?')) return;
            await fetch(this.api + '/' + i, {
                method: 'DELETE'
            });
            $('#vTabla').bootstrapTable('refresh');
        },
        async detalles(id) {
            document.getElementById('vDetLoading').style.display = '';
            document.getElementById('vDetContent').style.display = 'none';
            const modal = new bootstrap.Modal(document.getElementById('vDetModal'));
            modal.show();

            try {
                const res = await fetch(this.api + '/' + id + '/detalles');
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
                            const badgeCls = {
                                pendiente: 'bg-secondary',
                                vencido: 'bg-danger',
                                'dentro_de_margen': 'bg-warning text-dark'
                            };
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
        },
        async init() {

            document.getElementById('vTabla').addEventListener('click', e => {
                const btn = e.target.closest('.a-dd-btn');
                if (btn) this._openMenu(btn, btn.dataset.id);
            });
        }

    };
    document.addEventListener('DOMContentLoaded', () => window.v.init());
</script>