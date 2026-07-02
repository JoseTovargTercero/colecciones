<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex justify-content-between align-items-center">
                <h4 class="page-title">Asignaciones de Colecciones</h4>
                <button class="btn btn-primary" onclick="window.a.showForm()">+ Nueva Asignación</button>
            </div>
        </div>
    </div>

    <!-- Tabla listado -->
    <div id="aListView">
        <div class="card">
            <div class="card-header p-0">
                <ul class="nav nav-tabs" id="aTabs" role="tablist"></ul>
            </div>
            <div class="card-body">
                <table id="aTabla" class="table table-hover"
                    data-toggle="table"
                    data-url="<?= BASE_URL ?>api/asignaciones"
                    data-query-params="window.a.queryParams"
                    data-response-handler="window.a.resp"
                    data-search="true" data-pagination="true" data-page-size="15">
                    <thead>
                        <tr>
                            <th data-field="vendedor" data-formatter="window.a.fVendedor" data-sortable="true">Vendedor</th>
                            <th data-field="coleccion" data-formatter="window.a.fColeccion" data-sortable="true">Colección</th>
                            <th data-field="coleccion_tipo" data-formatter="window.a.fTipo">Tipo</th>
                            <th data-field="empresa_nombre" data-sortable="true">Empresa</th>
                            <th data-field="precio_venta_vendedor" data-formatter="window.a.fMonto">Monto</th>

                            <th data-field="estado" data-formatter="window.a.fEstado">Estado</th>
                            <th data-formatter="window.a.fAcc" data-align="center">Acciones</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <!-- Formulario Wizard -->
    <div id="aFormView" style="display:none">
        <div class="card">
            <div class="card-body">
                <!-- Steps -->
                <ul class="nav nav-pills mb-4 justify-content-center" id="aWizardSteps">
                    <li class="nav-item">
                        <a class="nav-link active" data-step="1" href="javascript:void(0)" onclick="window.a.goStep(1)">
                            <i class="bx bx-user me-1"></i> Vendedor
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-step="2" href="javascript:void(0)" onclick="window.a.goStep(2)">
                            <i class="bx bx-collection me-1"></i> Colección
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-step="3" href="javascript:void(0)" onclick="window.a.goStep(3)">
                            <i class="bx bx-credit-card me-1"></i> Cuotas
                        </a>
                    </li>
                </ul>

                <!-- Step 1: Vendedor -->
                <div class="wizard-step" id="aWStep1">
                    <div class="row justify-content-center">
                        <div class="col-lg-6">
                            <h5 class="mb-3 text-center">Selecciona el Vendedor</h5>
                            <div class="mb-3">
                                <label for="aVendedor" class="form-label">Vendedor <span class="text-danger">*</span></label>
                                <select class="form-select" id="aVendedor" required>
                                    <option value="">Seleccione vendedor...</option>
                                </select>
                            </div>
                            <div class="text-center mt-4">
                                <button class="btn btn-primary px-4" onclick="window.a.goStep(2)">
                                    Siguiente <i class="bx bx-chevron-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Colección + Temporada -->
                <div class="wizard-step" id="aWStep2" style="display:none">
                    <div class="row justify-content-center">
                        <div class="col-lg-6">
                            <h5 class="mb-3 text-center">Colección y Temporada</h5>
                            <div class="mb-3">
                                <label for="aEmpresa" class="form-label">Empresa <span class="text-danger">*</span></label>
                                <select class="form-select" id="aEmpresa" required>
                                    <option value="">Seleccione empresa...</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="aColeccion" class="form-label">Colección/Combo <span class="text-danger">*</span></label>
                                <select class="form-select" id="aColeccion" required>
                                    <option value="">Primero seleccione empresa...</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Cantidad de colecciones <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <button class="btn btn-outline-secondary" type="button" onclick="window.a.cantDec()">−</button>
                                    <input type="number" class="form-control text-center" id="aCantidad" value="1" min="1" max="29" readonly>
                                    <button class="btn btn-outline-secondary" type="button" onclick="window.a.cantInc()">+</button>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="aTemporada" class="form-label">Temporada <span class="text-danger">*</span></label>
                                <select class="form-select" id="aTemporada" required>
                                    <option value="">Seleccione temporada...</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="aFechaAsig" class="form-label">Fecha de Asignación <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="aFechaAsig" required>
                            </div>
                            <div class="text-center mt-4 d-flex justify-content-between">
                                <button class="btn btn-secondary" onclick="window.a.goStep(1)">
                                    <i class="bx bx-chevron-left"></i> Anterior
                                </button>
                                <button class="btn btn-primary px-4" onclick="window.a.goStep(3)">
                                    Siguiente <i class="bx bx-chevron-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Cuotas + Resumen -->
                <div class="wizard-step" id="aWStep3" style="display:none">
                    <h5 class="mb-3 text-center">Configuración de Cuotas</h5>
                    <div class="row justify-content-center mb-3">
                        <div class="col-lg-3">
                            <label for="aCalculo" class="form-label">Cálculo <span class="text-danger">*</span></label>
                            <select class="form-select" id="aCalculo" onchange="window.a.onCalcChange()">
                                <option value="automatico">Automático</option>
                                <option value="manual">Manual</option>
                            </select>
                        </div>
                        <div id="aAutoFields" class="col-lg-3">
                            <label for="aFechaPrimera" class="form-label">Primera Cuota <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="aFechaPrimera">
                        </div>
                        <div id="aIntervaloField" class="col-lg-3">
                            <label for="aIntervalo" class="form-label">Intervalo</label>
                            <select class="form-select" id="aIntervalo">
                                <option value="quincenal">Quincenal</option>
                                <option value="mensual">Mensual</option>
                            </select>
                        </div>
                    </div>

                    <div class="row justify-content-center">
                        <div class="col-lg-8">
                            <button class="btn btn-warning w-100 mb-3" onclick="window.a.calcular()">
                                <i class="bx bx-calculator"></i> Calcular Cuotas
                            </button>
                        </div>
                    </div>

                    <div id="aResumenCard" style="display:none">
                        <div id="aResumenInfo" class="mb-3"></div>
                        <div class="table-responsive">
                            <div id="aCuotasDetalle"></div>
                        </div>
                        <div class="row justify-content-center mt-4">
                            <div class="col-lg-6 d-flex justify-content-between">
                                <button class="btn btn-secondary" onclick="window.a.goStep(2)">
                                    <i class="bx bx-chevron-left"></i> Anterior
                                </button>
                                <div>
                                    <button class="btn btn-secondary me-2" onclick="window.a.hideForm()">Cancelar</button>
                                    <button class="btn btn-primary" onclick="window.a.guardar()">
                                        <i class="bx bx-save"></i> Guardar Asignación
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="aResumenEmpty" style="display:block">
                        <div class="text-center py-4 text-muted">
                            <i class="bx bx-calculator fs-1"></i>
                            <p class="mt-2">Presiona "Calcular Cuotas" para ver el resumen.</p>
                        </div>
                        <div class="text-center">
                            <button class="btn btn-secondary" onclick="window.a.goStep(2)">
                                <i class="bx bx-chevron-left"></i> Anterior
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Ver Cuotas -->
<div class="modal fade" id="aCuotasModal" tabindex="-1" aria-hidden="true">
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
                        <tbody id="aCuotasModalBody">
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

<!-- Modal Ver Comprobante -->
<div class="modal fade" id="aComprobanteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalle de pago</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="aComprobanteModalBody">
                <p class="text-muted">Cargando...</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
    window.a = {
        api: '<?= BASE_URL ?>api/asignaciones',
        apiE: '<?= BASE_URL ?>api/empresas',
        apiV: '<?= BASE_URL ?>api/vendedores',
        apiT: '<?= BASE_URL ?>api/temporadas',
        apiC: '<?= BASE_URL ?>api/colecciones',
        BASE: '<?= BASE_URL ?>',
        empresas: [],
        colecciones: [],
        temporadas: [],
        cuotasCalc: [],
        currentEmpresa: '',

        resp: (r) => ({
            rows: r.data || [],
            total: r.data?.length || 0
        }),

        queryParams: (p) => {
            if (window.a.currentEmpresa) p.empresa_id = window.a.currentEmpresa;
            return p;
        },

        load(empresaId) {
            if (empresaId !== undefined) this.currentEmpresa = empresaId;
            $('#aTabla').bootstrapTable('refresh');
        },

        initTabs() {
            const ul = document.getElementById('aTabs');
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
                    <div class="avatar avatar-sm">
                        <span class="avatar-initial rounded-circle bg-primary">${(row.vendedor_nombre||'?')[0]}</span>
                    </div>
                    <div>
                        <div class="fw-semibold">${row.vendedor_nombre}</div>
                        <small class="text-muted">${row.vendedor_cedula}</small>
                    </div>
                </div>`;
        },

        fColeccion(v, row) {
            return `<div>
                    <div class="fw-semibold">${row.coleccion_nombre}</div>
                    <small class="text-muted">${row.temporada_nombre}</small>
                </div>`;
        },

        fTipo: (v) => v === 'combo' ?
            '<span class="badge bg-info">Combo</span>' : '<span class="badge bg-success">Colección</span>',

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
                    <button class="btn btn-sm btn-outline-secondary a-dd-btn" data-id="${row.id}">⋮</button>
                </div>`;
        },

        _closeMenu() {
            document.querySelectorAll('.a-dd-menu').forEach(m => m.remove());
            this._menuOpen = false;
        },

        _openMenu(btn, id) {
            this._closeMenu();
            const rect = btn.getBoundingClientRect();
            const menu = document.createElement('ul');
            menu.className = 'dropdown-menu show a-dd-menu';
            menu.style.cssText = 'position:fixed;left:' + (rect.right - 160) + 'px;top:' + rect.bottom + 'px;z-index:9999';
            menu.innerHTML =
                '<li><a class="dropdown-item" href="javascript:void(0)" onclick="window.a.verCuotas(\'' + id + '\')"><i class="bx bx-list-ul me-2"></i>Ver Cuotas</a></li>' +
                '<li><hr class="dropdown-divider"></li>' +
                '<li><a class="dropdown-item text-danger" href="javascript:void(0)" onclick="window.a.del(\'' + id + '\')"><i class="bx bx-trash me-2"></i>Eliminar</a></li>';
            document.body.appendChild(menu);
            this._menuOpen = true;
            setTimeout(() => document.addEventListener('click', this._onDocClick), 0);
        },

        _onDocClick(e) {
            if (!e.target.closest('.a-dd-menu, .a-dd-btn')) {
                window.a._closeMenu();
                document.removeEventListener('click', window.a._onDocClick);
            }
        },

        async init() {
            const [re, rv, rt, rc] = await Promise.all([
                fetch(this.apiE).then(r => r.json()),
                fetch(this.apiV).then(r => r.json()),
                fetch(this.apiT).then(r => r.json()),
                fetch(this.apiC).then(r => r.json()),
            ]);
            this.empresas = re.data || [];
            this.temporadas = rt.data || [];
            this.colecciones = rc.data || [];

            this._fillSelect('aEmpresa', this.empresas, e => ({
                v: e.id,
                l: e.nombre,
                data: e
            }));
            this._fillSelect('aVendedor', rv.data || [], v => ({
                v: v.id,
                l: `${v.nombre} — ${v.cedula}`
            }));
            this._fillSelect('aTemporada', this.temporadas, t => ({
                v: t.id,
                l: t.nombre
            }));

            this.initTabs();
            this.load();
            document.getElementById('aEmpresa').addEventListener('change', () => this.onEmpresaChange());

            document.getElementById('aTabla').addEventListener('click', e => {
                const btn = e.target.closest('.a-dd-btn');
                if (btn) this._openMenu(btn, btn.dataset.id);
            });
        },

        _fillSelect(id, items, mapper, empty = 'Seleccione...') {
            const el = document.getElementById(id);
            el.innerHTML = `<option value="">${empty}</option>` +
                items.map(i => {
                    const m = mapper(i);
                    return `<option value="${m.v}"${m.data?` data-row='${JSON.stringify(m.data).replace(/'/g,"&apos;")}'`:''}>${m.l}</option>`;
                }).join('');
        },

        onEmpresaChange() {
            const empId = document.getElementById('aEmpresa').value;
            const emp = this.empresas.find(e => e.id === empId);
            this.cfgCuotas = emp || null;

            const cols = this.colecciones.filter(c => c.empresa_id === empId);
            this._fillSelect('aColeccion', cols, c => ({
                v: c.id,
                l: `${c.nombre} (${c.tipo})`,
                data: c
            }), 'Seleccione colección...');

            const temps = this.temporadas.filter(t => t.empresa_id === empId);
            this._fillSelect('aTemporada', temps, t => ({
                v: t.id,
                l: t.nombre
            }), 'Seleccione temporada...');

            document.getElementById('aResumenCard').style.display = 'none';
            document.getElementById('aResumenEmpty').style.display = 'block';
            this.cuotasCalc = [];
        },

        onCalcChange() {
            const manual = document.getElementById('aCalculo').value === 'manual';
            document.getElementById('aAutoFields').style.display = manual ? 'none' : '';
            document.getElementById('aIntervaloField').style.display = manual ? 'none' : '';
            document.getElementById('aResumenCard').style.display = 'none';
            document.getElementById('aResumenEmpty').style.display = 'block';
            this.cuotasCalc = [];
        },

        cantInc() {
            const el = document.getElementById('aCantidad');
            let v = parseInt(el.value) || 1;
            if (v < 29) el.value = v + 1;
        },
        cantDec() {
            const el = document.getElementById('aCantidad');
            let v = parseInt(el.value) || 1;
            if (v > 1) el.value = v - 1;
        },

        goStep(n) {
            document.querySelectorAll('.wizard-step').forEach(el => el.style.display = 'none');
            document.getElementById('aWStep' + n).style.display = '';
            document.querySelectorAll('#aWizardSteps .nav-link').forEach(el => {
                el.classList.toggle('active', parseInt(el.dataset.step) === n);
            });
        },

        calcular() {
            const empId = document.getElementById('aEmpresa').value;
            const colOpt = document.getElementById('aColeccion').selectedOptions[0];
            const calculo = document.getElementById('aCalculo').value;
            const fechaAsig = document.getElementById('aFechaAsig').value;

            if (!empId || !colOpt?.value || !fechaAsig) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Campos incompletos',
                    text: 'Complete empresa, colección y fecha de asignación.'
                });
                return;
            }

            const emp = this.empresas.find(e => e.id === empId);
            if (!emp || !emp.cantidad_cuotas) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Sin configuración',
                    text: 'La empresa no tiene configuración de cuotas.'
                });
                return;
            }

            const colData = JSON.parse(colOpt.getAttribute('data-row') || '{}');
            const monto = parseFloat(colData.precio_venta_vendedor || 0);
            const cantidad = parseInt(document.getElementById('aCantidad').value) || 1;
            const montoTotal = monto * cantidad;
            const cuotas = Array.isArray(emp.cuotas) ? emp.cuotas : [];
            const n = parseInt(emp.cantidad_cuotas);

            this.cuotasCalc = [];

            if (calculo === 'automatico') {
                const fechaPrimera = document.getElementById('aFechaPrimera').value;
                const intervalo = document.getElementById('aIntervalo').value;
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
                    const mt = (monto * pct / 100);
                    const fecha = this._sumarIntervalo(fechaPrimera, intervalo, i);
                    this.cuotasCalc.push({
                        numero: i + 1,
                        porcentaje: pct,
                        monto: mt,
                        fecha_pago: fecha
                    });
                }
            } else {
                for (let i = 0; i < n; i++) {
                    const pct = parseFloat(cuotas[i] || (100 / n));
                    const mt = (monto * pct / 100);
                    this.cuotasCalc.push({
                        numero: i + 1,
                        porcentaje: pct,
                        monto: mt,
                        fecha_pago: ''
                    });
                }
            }

            this._renderResumen(montoTotal, calculo);
        },

        _sumarIntervalo(base, intervalo, i) {
            if (i === 0) return base;
            if (intervalo === 'mensual') {
                const d = new Date(base + 'T00:00:00');
                d.setMonth(d.getMonth() + i);
                return d.toISOString().slice(0, 10);
            }
            // quincenal: 15 y último día del mes
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

        _renderResumen(monto, calculo) {
            const total = this.cuotasCalc.reduce((s, c) => s + c.monto, 0);
            document.getElementById('aResumenInfo').innerHTML = `
            <div class="row text-center g-2">
                <div class="col-4"><div class="p-3 bg-light rounded"><small class="text-muted">Monto Total</small><div class="fw-bold fs-5">$${monto.toFixed(2)}</div></div></div>
                <div class="col-4"><div class="p-3 bg-light rounded"><small class="text-muted">Cuotas</small><div class="fw-bold fs-5">${this.cuotasCalc.length}</div></div></div>
                <div class="col-4"><div class="p-3 bg-light rounded"><small class="text-muted">Total Calculado</small><div class="fw-bold fs-5">$${total.toFixed(2)}</div></div></div>
            </div>`;

            let rows = '';
            this.cuotasCalc.forEach((c, idx) => {
                const fechaInput = calculo === 'manual' ?
                    `<input type="date" class="form-control form-control-sm cuota-fecha" data-idx="${idx}" required>` :
                    `<span class="fw-medium">${c.fecha_pago}</span>`;
                rows += `<tr>
                <td class="fw-medium">${c.numero}</td>
                <td>${c.porcentaje.toFixed(2)}%</td>
                <td class="fw-medium">$${c.monto.toFixed(2)}</td>
                <td>${fechaInput}</td>
                <td>${calculo === 'manual' ? '' : this._sumarFecha(c.fecha_pago, 3)}</td>
            </tr>`;
            });

            document.getElementById('aCuotasDetalle').innerHTML = `
            <table class="table table-sm table-bordered table-striped mb-0">
                <thead class="table-light">
                    <tr><th>#</th><th>%</th><th>Monto</th><th>Fecha Pago</th><th>Vencimiento</th></tr>
                </thead>
                <tbody>${rows}</tbody>
            </table>`;

            document.querySelectorAll('.cuota-fecha').forEach(inp => {
                inp.addEventListener('change', e => {
                    const idx = parseInt(e.target.getAttribute('data-idx'));
                    this.cuotasCalc[idx].fecha_pago = e.target.value;
                    const td = e.target.closest('tr').querySelector('td:last-child');
                    if (td) td.textContent = this._sumarFecha(e.target.value, 3);
                });
            });

            document.getElementById('aResumenCard').style.display = '';
            document.getElementById('aResumenEmpty').style.display = 'none';
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

            const incompletas = this.cuotasCalc.filter(c => !c.fecha_pago);
            if (incompletas.length) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Fechas incompletas',
                    text: 'Complete las fechas de todas las cuotas.'
                });
                return;
            }

            const payload = {
                vendedor_id: document.getElementById('aVendedor').value,
                coleccion_id: document.getElementById('aColeccion').value,
                temporada_id: document.getElementById('aTemporada').value,
                fecha_asignacion: document.getElementById('aFechaAsig').value,
                cantidad: parseInt(document.getElementById('aCantidad').value) || 1,
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
            const result = await Swal.fire({
                icon: 'question',
                title: '¿Eliminar?',
                text: 'Esta acción eliminará la asignación y sus cuotas.',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            });
            if (!result.isConfirmed) return;
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
            const tbody = document.getElementById('aCuotasModalBody');
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Cargando...</td></tr>';
            const modal = new bootstrap.Modal(document.getElementById('aCuotasModal'));
            modal.show();

            try {
                const res = await fetch(`${this.api}/${id}/cuotas`);
                const data = await res.json();
                if (data.value && data.data?.length) {
                    const estatusBadge = {
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
                        },
                    };
                    tbody.innerHTML = data.data.map(c => {
                        const cfg = estatusBadge[c.estatus_pago] || {
                            bg: 'bg-secondary',
                            label: c.estatus_pago,
                            title: c.estatus_pago
                        };
                        const comprobantes = c.comprobante ? c.comprobante.split('|') : [];
                        const compCell = comprobantes.length ?
                            `<button class="btn btn-sm btn-outline-primary" onclick="window.a.verComprobante('${id}',${c.numero_cuota})"><i class="bx bx-show"></i> Ver (${comprobantes.length})</button>` :
                            '<span class="text-muted">—</span>';
                        return `<tr>
                        <td class="fw-medium">${c.numero_cuota}</td>
                        <td>${parseFloat(c.porcentaje).toFixed(2)}%</td>
                        <td class="fw-medium">$${parseFloat(c.monto_a_pagar).toFixed(2)}</td>
                        <td>${c.fecha_pago}</td>
                        <td><span class="badge ${cfg.bg}" title="${cfg.title}">${cfg.label}</span></td>
                    </tr>`;
                    }).join('');
                } else {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Sin cuotas registradas.</td></tr>';
                }
            } catch (e) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Error al cargar cuotas.</td></tr>';
            }
        },

        async verComprobante(asignacionId, numCuota) {
            const body = document.getElementById('aComprobanteModalBody');
            body.innerHTML = '<p class="text-muted">Cargando...</p>';
            const modal = new bootstrap.Modal(document.getElementById('aComprobanteModal'));
            modal.show();

            try {
                const res = await fetch(`${this.api}/${asignacionId}/cuotas`);
                const data = await res.json();
                const cuota = (data.data || []).find(c => parseInt(c.numero_cuota) === numCuota);
                if (!cuota || !cuota.comprobante_ids?.length) {
                    body.innerHTML = '<p class="text-danger">Sin comprobantes.</p>';
                    return;
                }
                const archivos = cuota.comprobante_rutas || [];
                const base = this.BASE;
                let html = `
                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-bordered mb-0">
                            <tbody>
                                <tr><th class="w-25">Cuota #</th><td>${cuota.numero_cuota}</td></tr>
                                <tr><th>Porcentaje</th><td>${parseFloat(cuota.porcentaje).toFixed(2)}%</td></tr>
                                <tr><th>Monto</th><td>$${parseFloat(cuota.monto_a_pagar).toFixed(2)}</td></tr>
                                <tr><th>Fecha pago</th><td>${cuota.fecha_pago}</td></tr>
                                <tr><th>Estatus</th><td><span class="badge bg-${cuota.estatus_pago === 'realizado' ? 'success' : cuota.estatus_pago === 'vencido' ? 'danger' : cuota.estatus_pago === 'dentro_de_margen' ? 'warning text-dark' : 'secondary'}">${cuota.estatus_pago}</span></td></tr>
                                <tr><th>Comprobantes</th><td>${archivos.length}</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="row g-2">`;
                archivos.forEach((c, idx) => {
                    const url = base + c.comprobante;
                    html += `
                        <div class="col-md-6">
                            <div class="card">
                                <a href="${url}" target="_blank">
                                    <img src="${url}" class="card-img-top" style="max-height:250px;object-fit:contain;background:#f5f5f5" alt="Comprobante ${idx+1}">
                                </a>
                                <div class="card-footer text-center small text-muted">
                                    Comprobante #${c.id} — <a href="${url}" target="_blank">Abrir</a>
                                </div>
                            </div>
                        </div>`;
                });
                html += '</div>';
                body.innerHTML = html;
            } catch (e) {
                body.innerHTML = '<p class="text-danger">Error al cargar comprobante.</p>';
            }
        },

        showForm() {
            document.getElementById('aListView').style.display = 'none';
            document.getElementById('aFormView').style.display = '';
            document.getElementById('aResumenCard').style.display = 'none';
            document.getElementById('aResumenEmpty').style.display = 'block';
            this.cuotasCalc = [];
            this.goStep(1);
        },

        hideForm() {
            document.getElementById('aFormView').style.display = 'none';
            document.getElementById('aListView').style.display = '';
        }
    };

    document.addEventListener('DOMContentLoaded', () => window.a.init());
</script>