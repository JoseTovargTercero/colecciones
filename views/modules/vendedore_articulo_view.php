<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex justify-content-between align-items-center">
                <h4 class="page-title">Asignaciones de Artículos</h4>
                <button class="btn btn-primary" onclick="window.va.showForm()">+ Nueva venta</button>
            </div>
        </div>
    </div>

    <div id="vaListView">
        <div class="card">
            <div class="card-header p-0">
                <ul class="nav nav-tabs" id="vaTabs" role="tablist"></ul>
            </div>
            <div class="card-body">
                <table id="vaTabla" class="table table-hover"
                    data-toggle="table"
                    data-url="<?= BASE_URL ?>api/asignaciones-articulos"
                    data-query-params="window.va.queryParams"
                    data-response-handler="window.va.resp"
                    data-search="true" data-pagination="true" data-page-size="15">
                    <thead>
                        <tr>
                            <th data-field="vendedor" data-formatter="window.va.fVendedor" data-sortable="true">Vendedor</th>
                            <th data-field="articulos" data-formatter="window.va.fArticulos" data-sortable="true">Artículos</th>
                            <th data-field="empresa_nombre" data-sortable="true">Empresa</th>
                            <th data-field="monto_total" data-formatter="window.va.fMonto">Total</th>
                            <th data-field="estado" data-formatter="window.va.fEstado">Estado</th>
                            <th data-formatter="window.va.fAcc" data-align="center">Acciones</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <div id="vaFormView" style="display:none">
        <div class="card">
            <div class="card-body">
                <ul class="nav nav-pills mb-4 justify-content-center" id="vaWizardSteps">
                    <li class="nav-item">
                        <a class="nav-link active" data-step="1" href="javascript:void(0)" onclick="window.va.goStep(1)">
                            <i class="bx bx-user me-1"></i> Vendedor
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-step="2" href="javascript:void(0)" onclick="window.va.goStep(2)">
                            <i class="bx bx-cube me-1"></i> Artículos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-step="3" href="javascript:void(0)" onclick="window.va.goStep(3)">
                            <i class="bx bx-credit-card me-1"></i> Cuotas
                        </a>
                    </li>
                </ul>

                <div class="wizard-step" id="vaWStep1">
                    <div class="row justify-content-center">
                        <div class="col-lg-6">
                            <h5 class="mb-3 text-center">Selecciona el Vendedor</h5>
                            <div class="mb-3">
                                <label for="vaVendedor" class="form-label">Cliente <span class="text-danger">*</span></label>
                                <div class="d-flex gap-2">
                                    <select class="form-select" id="vaVendedor" required style="flex:1">
                                        <option value="">Seleccione vendedor...</option>
                                    </select>
                                    <button class="btn btn-outline-primary" type="button" onclick="window.va.nuevoVendedor()" title="Nuevo vendedor">
                                        <i class="bx bx-plus"></i> Nuevo
                                    </button>
                                </div>
                            </div>
                            <div class="text-center mt-4">
                                <button class="btn btn-primary px-4" onclick="window.va.goStep(2)">
                                    Siguiente <i class="bx bx-chevron-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="wizard-step" id="vaWStep2" style="display:none">
                    <div class="row justify-content-center">
                        <div class="col-lg-8">
                            <h5 class="mb-3 text-center">Artículos y Temporada</h5>
                            <div class="mb-3">
                                <label for="vaEmpresa" class="form-label">Empresa <span class="text-danger">*</span></label>
                                <select class="form-select" id="vaEmpresa" required>
                                    <option value="">Seleccione empresa...</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="vaTemporada" class="form-label">Temporada <span class="text-danger">*</span></label>
                                <select class="form-select" id="vaTemporada" required>
                                    <option value="">Seleccione temporada...</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="vaFechaAsig" class="form-label">Fecha de Asignación <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="vaFechaAsig" required>
                            </div>
                            <hr>
                            <h6>Agregar Artículos</h6>
                            <div class="row g-2 mb-3">
                                <div class="col-md-6">
                                    <select class="form-select" id="vaArticuloSel">
                                        <option value="">Seleccione artículo...</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <input type="number" class="form-control" id="vaCantidad" value="1" min="1">
                                </div>
                                <div class="col-md-4">
                                    <button class="btn btn-success w-100" onclick="window.va.agregarItem()">
                                        <i class="bx bx-plus"></i> Agregar
                                    </button>
                                </div>
                            </div>
                            <div class="table-responsive mb-3">
                                <table class="table table-sm table-bordered" id="vaItemsTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Artículo</th>
                                            <th>Precio Unit.</th>
                                            <th>Cant.</th>
                                            <th>Subtotal</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody id="vaItemsBody"></tbody>
                                </table>
                            </div>
                            <div class="text-end fw-bold mb-3">Total: $<span id="vaItemsTotal">0.00</span></div>
                            <div class="text-center mt-4 d-flex justify-content-between">
                                <button class="btn btn-secondary" onclick="window.va.goStep(1)">
                                    <i class="bx bx-chevron-left"></i> Anterior
                                </button>
                                <button class="btn btn-primary px-4" onclick="window.va.goStep(3)">
                                    Siguiente <i class="bx bx-chevron-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="wizard-step" id="vaWStep3" style="display:none">
                    <h5 class="mb-3 text-center">Configuración de Cuotas</h5>
                    <div class="row justify-content-center mb-3">
                        <div class="col-lg-3">
                            <label for="vaCalculo" class="form-label">Cálculo <span class="text-danger">*</span></label>
                            <select class="form-select" id="vaCalculo" onchange="window.va.onCalcChange()">
                                <option value="automatico">Automático</option>
                                <option value="manual">Manual</option>
                            </select>
                        </div>
                        <div id="vaAutoFields" class="col-lg-3">
                            <label for="vaFechaPrimera" class="form-label">Primera Cuota <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="vaFechaPrimera">
                        </div>
                        <div id="vaIntervaloField" class="col-lg-3">
                            <label for="vaIntervalo" class="form-label">Intervalo</label>
                            <select class="form-select" id="vaIntervalo">
                                <option value="quincenal">Quincenal</option>
                                <option value="mensual">Mensual</option>
                            </select>
                        </div>
                    </div>
                    <div class="row justify-content-center">
                        <div class="col-lg-8">
                            <button class="btn btn-warning w-100 mb-3" onclick="window.va.calcular()">
                                <i class="bx bx-calculator"></i> Calcular Cuotas
                            </button>
                        </div>
                    </div>
                    <div id="vaResumenCard" style="display:none">
                        <div id="vaResumenInfo" class="mb-3"></div>
                        <div class="table-responsive">
                            <div id="vaCuotasDetalle"></div>
                        </div>
                        <div class="row justify-content-center mt-4">
                            <div class="col-lg-6 d-flex justify-content-between">
                                <button class="btn btn-secondary" onclick="window.va.goStep(2)">
                                    <i class="bx bx-chevron-left"></i> Anterior
                                </button>
                                <div>
                                    <button class="btn btn-secondary me-2" onclick="window.va.hideForm()">Cancelar</button>
                                    <button class="btn btn-primary" onclick="window.va.guardar()">
                                        <i class="bx bx-save"></i> Guardar Asignación
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="vaResumenEmpty" style="display:block">
                        <div class="text-center py-4 text-muted">
                            <i class="bx bx-calculator fs-1"></i>
                            <p class="mt-2">Presiona "Calcular Cuotas" para ver el resumen.</p>
                        </div>
                        <div class="text-center">
                            <button class="btn btn-secondary" onclick="window.va.goStep(2)">
                                <i class="bx bx-chevron-left"></i> Anterior
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="vaCuotasModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cuotas de la Asignación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>%</th>
                                <th>Monto</th>
                                <th>Fecha Pago</th>
                                <th>Estatus</th>
                            </tr>
                        </thead>
                        <tbody id="vaCuotasModalBody">
                            <tr>
                                <td colspan="6" class="text-center text-muted">Cargando...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="vaDetalleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Artículos de la Asignación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead>
                            <tr>
                                <th>Artículo</th>
                                <th>Precio Unit.</th>
                                <th>Cant.</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody id="vaDetalleModalBody">
                            <tr>
                                <td colspan="4" class="text-center text-muted">Cargando...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="vaNuevoVendedorModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="vaNuevoVendedorForm" onsubmit="window.va.saveNuevoVendedor(event)">
                <div class="modal-header">
                    <h5 class="modal-title">Nuevo Vendedor</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label for="vaNVNombre" class="form-label">Nombre</label>
                        <input class="form-control" id="vaNVNombre" placeholder="Nombre completo" required>
                    </div>
                    <div class="mb-2">
                        <label for="vaNVCedula" class="form-label">Cédula</label>
                        <input class="form-control" id="vaNVCedula" placeholder="Cédula de identidad" required>
                    </div>
                    <div class="mb-2">
                        <label for="vaNVTelefono" class="form-label">Teléfono</label>
                        <input class="form-control" id="vaNVTelefono" placeholder="Teléfono">
                    </div>
                    <div class="mb-2 d-none">
                        <label for="vaNVNivel" class="form-label">Nivel</label>
                        <select class="form-control" id="vaNVNivel" required>
                            <option value="COMPRADOR FINAL">COMPRADOR FINAL</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary">Guardar</button></div>
            </form>
        </div>
    </div>
</div>

<script>
    window.va = {
        api: '<?= BASE_URL ?>api/asignaciones-articulos',
        apiE: '<?= BASE_URL ?>api/empresas',
        apiV: '<?= BASE_URL ?>api/vendedores',
        apiT: '<?= BASE_URL ?>api/temporadas',
        apiA: '<?= BASE_URL ?>api/articulos',
        BASE: '<?= BASE_URL ?>',
        empresas: [],
        articulos: [],
        temporadas: [],
        cuotasCalc: [],
        items: [],
        currentEmpresa: '',

        resp: (r) => ({
            rows: r.data || [],
            total: r.data?.length || 0
        }),

        queryParams: (p) => {
            if (window.va.currentEmpresa) p.empresa_id = window.va.currentEmpresa;
            return p;
        },

        load(empresaId) {
            if (empresaId !== undefined) this.currentEmpresa = empresaId;
            $('#vaTabla').bootstrapTable('refresh');
        },

        initTabs() {
            const ul = document.getElementById('vaTabs');
            let html = `<li class="nav-item"><a class="nav-link active" data-empresa="" href="#">Todas</a></li>`;
            this.empresas.forEach(e => {
                html += `<li class="nav-item"><a class="nav-link" data-empresa="${e.id}" href="#">${e.nombre}</a></li>`;
            });
            ul.innerHTML = html;
            ul.querySelectorAll('.nav-link').forEach(el => {
                el.addEventListener('click', (ev) => {
                    ev.preventDefault();
                    ul.querySelector('.nav-link.active')?.classList.remove('active');
                    el.classList.add('active');
                    this.load(el.getAttribute('data-empresa'));
                });
            });
        },

        fVendedor(v, row) {
            return `<div class="d-flex align-items-center gap-2">
                    <div class="avatar avatar-sm"><span class="avatar-initial rounded-circle bg-primary">${(row.vendedor_nombre||'?')[0]}</span></div>
                    <div><div class="fw-semibold">${row.vendedor_nombre}</div><small class="text-muted">${row.vendedor_cedula}</small></div>
                </div>`;
        },

        fArticulos(v, row) {
            const nombres = row.articulos_nombres || '';
            return `<div><div class="fw-semibold">${nombres}</div><small class="text-muted">${row.temporada_nombre}</small></div>`;
        },

        fEstado: (v) => {
            const m = {
                activa: 'primary',
                inactiva: 'secondary',
                finalizada: 'success'
            };
            return `<span class="badge bg-${m[v]||'success'}">${v}</span>`;
        },

        fMonto: (v) => `$${parseFloat(v||0).toFixed(2)}`,

        fAcc: (v, row) => {
            return `<div class="text-center">
                    <button class="btn btn-sm btn-outline-secondary va-dd-btn" data-id="${row.id}">⋮</button>
                </div>`;
        },

        _closeMenu() {
            document.querySelectorAll('.va-dd-menu').forEach(m => m.remove());
            this._menuOpen = false;
        },

        _openMenu(btn, id) {
            this._closeMenu();
            const rect = btn.getBoundingClientRect();
            const menu = document.createElement('ul');
            menu.className = 'dropdown-menu show va-dd-menu';
            menu.style.cssText = 'position:fixed;left:' + (rect.right - 160) + 'px;top:' + rect.bottom + 'px;z-index:9999';
            menu.innerHTML =
                '<li><a class="dropdown-item" href="javascript:void(0)" onclick="window.va.verDetalle(\'' + id + '\')"><i class="bx bx-list-ul me-2"></i>Ver Artículos</a></li>' +
                '<li><a class="dropdown-item" href="javascript:void(0)" onclick="window.va.verCuotas(\'' + id + '\')"><i class="bx bx-credit-card me-2"></i>Ver Cuotas</a></li>' +
                '<li><hr class="dropdown-divider"></li>' +
                '<li><a class="dropdown-item text-danger" href="javascript:void(0)" onclick="window.va.del(\'' + id + '\')"><i class="bx bx-trash me-2"></i>Eliminar</a></li>';
            document.body.appendChild(menu);
            this._menuOpen = true;
            setTimeout(() => document.addEventListener('click', this._onDocClick), 0);
        },

        _onDocClick(e) {
            if (!e.target.closest('.va-dd-menu, .va-dd-btn')) {
                window.va._closeMenu();
                document.removeEventListener('click', window.va._onDocClick);
            }
        },

        async init() {
            const [re, rv, rt, ra] = await Promise.all([
                fetch(this.apiE).then(r => r.json()),
                fetch(this.apiV).then(r => r.json()),
                fetch(this.apiT).then(r => r.json()),
                fetch(this.apiA).then(r => r.json()),
            ]);
            this.empresas = re.data || [];
            this.temporadas = rt.data || [];
            this.articulos = ra.data || [];

            this._fillSelect('vaEmpresa', this.empresas, e => ({
                v: e.id,
                l: e.nombre,
                data: e
            }));
            this._fillSelect('vaVendedor', rv.data || [], v => ({
                v: v.id,
                l: `${v.nombre} — ${v.cedula}`
            }));
            this._fillSelect('vaTemporada', this.temporadas, t => ({
                v: t.id,
                l: t.nombre
            }));

            $('#vaVendedor').select2({
                width: '100%',
                dropdownParent: $('#vaWStep1')
            });

            this.initTabs();
            this.load();
            document.getElementById('vaEmpresa').addEventListener('change', () => this.onEmpresaChange());
            document.getElementById('vaTabla').addEventListener('click', e => {
                const btn = e.target.closest('.va-dd-btn');
                if (btn) this._openMenu(btn, btn.dataset.id);
            });
        },

        _fillSelect(id, items, mapper, empty = 'Seleccione...') {
            const el = document.getElementById(id);
            if (!el) return;
            el.innerHTML = `<option value="">${empty}</option>` +
                items.map(i => {
                    const m = mapper(i);
                    return `<option value="${m.v}"${m.data ? ` data-row='${JSON.stringify(m.data).replace(/'/g,"&apos;")}'` : ''}>${m.l}</option>`;
                }).join('');
        },

        onEmpresaChange() {
            const empId = document.getElementById('vaEmpresa').value;
            const emp = this.empresas.find(e => e.id == empId);
            this.cfgCuotas = emp || null;
            this.diasRetraso = emp ? (parseInt(emp.dias_retraso_permitido) || 3) : 3;

            const arts = this.articulos.filter(a => a.empresa_id == empId);
            this._fillSelect('vaArticuloSel', arts, a => ({
                v: a.id,
                l: `${a.nombre} ($${parseFloat(a.precio_final).toFixed(2)})`,
                data: a
            }), 'Seleccione artículo...');

            const temps = this.temporadas.filter(t => t.empresa_id == empId);
            this._fillSelect('vaTemporada', temps, t => ({
                v: t.id,
                l: t.nombre
            }), 'Seleccione temporada...');

            this.items = [];
            this._renderItems();
            document.getElementById('vaEmpresa').disabled = false;
            document.getElementById('vaTemporada').disabled = false;
        },

        agregarItem() {
            const sel = document.getElementById('vaArticuloSel');
            const opt = sel.selectedOptions[0];
            if (!opt || !opt.value) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Seleccione un artículo'
                });
                return;
            }
            const data = JSON.parse(opt.getAttribute('data-row') || '{}');
            const cantidad = parseInt(document.getElementById('vaCantidad').value) || 1;
            const exist = this.items.find(i => i.articulo_id === data.id);
            if (exist) {
                exist.cantidad += cantidad;
            } else {
                this.items.push({
                    articulo_id: data.id,
                    nombre: data.nombre,
                    precio_unitario: parseFloat(data.precio_final),
                    cantidad: cantidad
                });
            }
            document.getElementById('vaEmpresa').disabled = true;
            document.getElementById('vaTemporada').disabled = true;
            this._renderItems();
        },

        quitarItem(idx) {
            this.items.splice(idx, 1);
            this._renderItems();
        },

        _renderItems() {
            const tbody = document.getElementById('vaItemsBody');
            let total = 0;
            tbody.innerHTML = this.items.map((it, i) => {
                const sub = it.precio_unitario * it.cantidad;
                total += sub;
                return `<tr>
                    <td>${it.nombre}</td>
                    <td>$${it.precio_unitario.toFixed(2)}</td>
                    <td>${it.cantidad}</td>
                    <td>$${sub.toFixed(2)}</td>
                    <td><button class="btn btn-sm btn-danger" onclick="window.va.quitarItem(${i})"><i class="bx bx-x"></i></button></td>
                </tr>`;
            }).join('');
            if (!this.items.length) tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Sin artículos agregados</td></tr>';
            document.getElementById('vaItemsTotal').textContent = total.toFixed(2);
        },

        onCalcChange() {
            const manual = document.getElementById('vaCalculo').value === 'manual';
            document.getElementById('vaAutoFields').style.display = manual ? 'none' : '';
            document.getElementById('vaIntervaloField').style.display = manual ? 'none' : '';
            document.getElementById('vaResumenCard').style.display = 'none';
            document.getElementById('vaResumenEmpty').style.display = 'block';
            this.cuotasCalc = [];
        },

        nuevoVendedor() {
            document.getElementById('vaNuevoVendedorForm').reset();
            new bootstrap.Modal(document.getElementById('vaNuevoVendedorModal')).show();
        },

        async saveNuevoVendedor(e) {
            e.preventDefault();
            const body = {
                nombre: document.getElementById('vaNVNombre').value,
                cedula: document.getElementById('vaNVCedula').value,
                telefono: document.getElementById('vaNVTelefono').value,
                nivel: document.getElementById('vaNVNivel').value
            };
            try {
                const res = await fetch(this.apiV, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(body)
                });
                const json = await res.json();
                if (json.value) {
                    bootstrap.Modal.getInstance(document.getElementById('vaNuevoVendedorModal')).hide();
                    const rv = await fetch(this.apiV).then(r => r.json());
                    this._fillSelect('vaVendedor', rv.data || [], v => ({
                        v: v.id,
                        l: `${v.nombre} — ${v.cedula}`
                    }));
                    const sel = document.getElementById('vaVendedor');
                    if (json.data?.id) sel.value = json.data.id;
                    $('#vaVendedor').select2('destroy').select2({
                        width: '100%',
                        dropdownParent: $('#vaWStep1')
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: json.message || 'Error al guardar'
                    });
                }
            } catch (e) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al guardar el vendedor.'
                });
            }
        },

        goStep(n) {
            document.querySelectorAll('.wizard-step').forEach(el => el.style.display = 'none');
            document.getElementById('vaWStep' + n).style.display = '';
            document.querySelectorAll('#vaWizardSteps .nav-link').forEach(el => {
                el.classList.toggle('active', parseInt(el.dataset.step) === n);
            });
        },

        calcular() {
            const empId = document.getElementById('vaEmpresa').value;
            const calculo = document.getElementById('vaCalculo').value;
            const fechaAsig = document.getElementById('vaFechaAsig').value;

            if (!empId || !this.items.length || !fechaAsig) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Campos incompletos',
                    text: 'Complete empresa, agregue artículos y fecha de asignación.'
                });
                return;
            }
            const emp = this.empresas.find(e => e.id == empId);
            if (!emp || !emp.cantidad_cuotas) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Sin configuración',
                    text: 'La empresa no tiene configuración de cuotas.'
                });
                return;
            }
            const total = this.items.reduce((s, it) => s + it.precio_unitario * it.cantidad, 0);
            const cuotas = Array.isArray(emp.cuotas) ? emp.cuotas : [];
            const n = parseInt(emp.cantidad_cuotas);
            this.cuotasCalc = [];

            if (calculo === 'automatico') {
                const fechaPrimera = document.getElementById('vaFechaPrimera').value;
                const intervalo = document.getElementById('vaIntervalo').value;
                if (!fechaPrimera) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Fecha requerida',
                        text: 'Indique la fecha de la primera cuota.'
                    });
                    return;
                }
                for (let i = 0; i < n; i++) {
                    const pct = parseFloat(cuotas[i] || (100 / n));
                    this.cuotasCalc.push({
                        numero: i + 1,
                        porcentaje: pct,
                        monto: total * pct / 100,
                        fecha_pago: this._sumarIntervalo(fechaPrimera, intervalo, i)
                    });
                }
            } else {
                for (let i = 0; i < n; i++) {
                    const pct = parseFloat(cuotas[i] || (100 / n));
                    this.cuotasCalc.push({
                        numero: i + 1,
                        porcentaje: pct,
                        monto: total * pct / 100,
                        fecha_pago: ''
                    });
                }
            }
            this._renderResumen(total, calculo);
        },

        _sumarIntervalo(base, intervalo, i) {
            if (i === 0) return base;
            if (intervalo === 'mensual') {
                const d = new Date(base + 'T00:00:00');
                d.setMonth(d.getMonth() + i);
                return d.toISOString().slice(0, 10);
            }
            const d = new Date(base + 'T00:00:00');
            let y = d.getFullYear(),
                m = d.getMonth(),
                dia = d.getDate();
            for (let j = 0; j < i; j++) {
                if (dia <= 15) {
                    dia = new Date(y, m + 1, 0).getDate();
                } else {
                    if (m === 11) {
                        y++;
                        m = 0;
                    } else {
                        m++;
                    }
                    dia = 15;
                }
            }
            return y + '-' + String(m + 1).padStart(2, '0') + '-' + String(dia).padStart(2, '0');
        },

        _renderResumen(total, calculo) {
            const sum = this.cuotasCalc.reduce((s, c) => s + c.monto, 0);
            document.getElementById('vaResumenInfo').innerHTML = `
                <div class="row text-center g-2">
                    <div class="col-4"><div class="p-3 bg-light rounded"><small class="text-muted">Monto Total</small><div class="fw-bold fs-5">$${total.toFixed(2)}</div></div></div>
                    <div class="col-4"><div class="p-3 bg-light rounded"><small class="text-muted">Cuotas</small><div class="fw-bold fs-5">${this.cuotasCalc.length}</div></div></div>
                    <div class="col-4"><div class="p-3 bg-light rounded"><small class="text-muted">Total Calculado</small><div class="fw-bold fs-5">$${sum.toFixed(2)}</div></div></div>
                </div>`;
            let rows = '';
            this.cuotasCalc.forEach((c, idx) => {
                const f = calculo === 'manual' ?
                    `<input type="date" class="form-control form-control-sm cuota-fecha" data-idx="${idx}" required>` :
                    `<span class="fw-medium">${c.fecha_pago}</span>`;
                rows += `<tr><td class="fw-medium">${c.numero}</td><td>${c.porcentaje.toFixed(2)}%</td><td class="fw-medium">$${c.monto.toFixed(2)}</td><td>${f}</td><td>${calculo === 'manual' ? '' : this._sumarFecha(c.fecha_pago, this.diasRetraso)}</td></tr>`;
            });
            document.getElementById('vaCuotasDetalle').innerHTML = `<table class="table table-sm table-bordered table-striped mb-0"><thead class="table-light"><tr><th>#</th><th>%</th><th>Monto</th><th>Fecha Pago</th><th>Vencimiento</th></tr></thead><tbody>${rows}</tbody></table>`;
            document.querySelectorAll('.cuota-fecha').forEach(inp => {
                inp.addEventListener('change', e => {
                    const idx = parseInt(e.target.getAttribute('data-idx'));
                    this.cuotasCalc[idx].fecha_pago = e.target.value;
                    const td = e.target.closest('tr').querySelector('td:last-child');
                    if (td) td.textContent = this._sumarFecha(e.target.value, this.diasRetraso);
                });
            });
            document.getElementById('vaResumenCard').style.display = '';
            document.getElementById('vaResumenEmpty').style.display = 'none';
        },

        _sumarFecha(fecha, dias) {
            if (!fecha) return '';
            const d = new Date(fecha + 'T00:00:00');
            d.setDate(d.getDate() + dias);
            return d.toISOString().slice(0, 10);
        },

        async guardar() {
            if (!this.cuotasCalc.length) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Sin cuotas',
                    text: 'Calcule las cuotas primero.'
                });
                return;
            }
            const inc = this.cuotasCalc.filter(c => !c.fecha_pago);
            if (inc.length) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Fechas incompletas',
                    text: 'Complete las fechas de todas las cuotas.'
                });
                return;
            }

            const payload = {
                vendedor_id: document.getElementById('vaVendedor').value,
                empresa_id: document.getElementById('vaEmpresa').value,
                temporada_id: document.getElementById('vaTemporada').value,
                fecha_asignacion: document.getElementById('vaFechaAsig').value,
                items: this.items,
                cuotas: this.cuotasCalc
            };
            try {
                const res = await fetch(this.api, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.value) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Guardado',
                        text: 'Asignación creada correctamente.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    this.hideForm();
                    this.load();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message
                    });
                }
            } catch (e) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al guardar la asignación.'
                });
            }
        },

        async del(id) {
            const r = await Swal.fire({
                icon: 'question',
                title: '¿Eliminar?',
                text: 'Esta acción eliminará la asignación y sus cuotas.',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            });
            if (!r.isConfirmed) return;
            try {
                await fetch(this.api + '/' + id, {
                    method: 'DELETE'
                });
                this.load();
                Swal.fire({
                    icon: 'success',
                    title: 'Eliminado',
                    timer: 1000,
                    showConfirmButton: false
                });
            } catch (e) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al eliminar.'
                });
            }
        },

        async verCuotas(id) {
            const tbody = document.getElementById('vaCuotasModalBody');
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Cargando...</td></tr>';
            new bootstrap.Modal(document.getElementById('vaCuotasModal')).show();
            try {
                const res = await fetch(this.api + '/' + id + '/cuotas');
                const data = await res.json();
                if (data.value && data.data?.length) {
                    const b = {
                        pendiente: {
                            bg: 'bg-secondary',
                            label: 'P',
                            title: 'PENDIENTE'
                        },
                        realizado: {
                            bg: 'bg-success',
                            label: 'R',
                            title: 'REALIZADO'
                        },
                        vencido: {
                            bg: 'bg-danger',
                            label: 'V',
                            title: 'VENCIDO'
                        },
                        dentro_de_margen: {
                            bg: 'bg-warning text-dark',
                            label: 'M',
                            title: 'MARGEN DE PAGO'
                        }
                    };
                    tbody.innerHTML = data.data.map(c => {
                        const cfg = b[c.estatus_pago] || {
                            bg: 'bg-secondary',
                            label: c.estatus_pago,
                            title: c.estatus_pago
                        };
                        return `<tr><td class="fw-medium">${c.numero_cuota}</td><td>${parseFloat(c.porcentaje).toFixed(2)}%</td><td class="fw-medium">$${parseFloat(c.monto_a_pagar).toFixed(2)}</td><td>${c.fecha_pago}</td><td><span class="badge ${cfg.bg}" title="${cfg.title}">${cfg.label}</span></td></tr>`;
                    }).join('');
                } else {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Sin cuotas registradas.</td></tr>';
                }
            } catch (e) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Error al cargar cuotas.</td></tr>';
            }
        },

        async verDetalle(id) {
            const tbody = document.getElementById('vaDetalleModalBody');
            tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Cargando...</td></tr>';
            new bootstrap.Modal(document.getElementById('vaDetalleModal')).show();
            try {
                const res = await fetch(this.api + '/' + id + '/detalle');
                const data = await res.json();
                if (data.value && data.data?.length) {
                    let total = 0;
                    tbody.innerHTML = data.data.map(d => {
                        const st = parseFloat(d.precio_unitario) * parseInt(d.cantidad);
                        total += st;
                        return `<tr><td>${d.articulo_nombre}</td><td>$${parseFloat(d.precio_unitario).toFixed(2)}</td><td>${d.cantidad}</td><td>$${st.toFixed(2)}</td></tr>`;
                    }).join('');
                    tbody.innerHTML += `<tr class="fw-bold"><td colspan="3" class="text-end">Total</td><td>$${total.toFixed(2)}</td></tr>`;
                } else {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Sin artículos registrados.</td></tr>';
                }
            } catch (e) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center text-danger">Error al cargar detalle.</td></tr>';
            }
        },

        showForm() {
            document.getElementById('vaListView').style.display = 'none';
            document.getElementById('vaFormView').style.display = '';
            document.getElementById('vaResumenCard').style.display = 'none';
            document.getElementById('vaResumenEmpty').style.display = 'block';
            this.cuotasCalc = [];
            this.items = [];
            $('#vaVendedor').select2({
                width: '100%',
                dropdownParent: $('#vaWStep1')
            });
            this.goStep(1);
        },

        hideForm() {
            document.getElementById('vaFormView').style.display = 'none';
            document.getElementById('vaListView').style.display = '';
            try {
                $('#vaVendedor').select2('destroy');
            } catch (e) {}
        }
    };

    document.addEventListener('DOMContentLoaded', () => window.va.init());
</script>