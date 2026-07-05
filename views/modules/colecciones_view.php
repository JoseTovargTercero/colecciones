<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex justify-content-between align-items-center">
                <h4 class="page-title">Colecciones y Combos</h4>
                <button class="btn btn-primary" onclick="window.c.add()">+ Nueva Colección</button>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header p-0">
            <ul class="nav nav-tabs" id="cTabs" role="tablist"></ul>
        </div>
        <div class="card-body">
            <table id="cTabla" class="table table-hover" data-toggle="table"
                data-url="<?= BASE_URL ?>api/colecciones"
                data-query-params="window.c.queryParams"
                data-response-handler="window.c.resp"
                data-search="true" data-pagination="true" data-page-size="15">
                <thead>
                    <tr>
                        <th data-field="foto" data-formatter="window.c.fFoto">Foto</th>
                        <th data-field="nombre" data-sortable="true" data-formatter="window.c.fNombre">Nombre</th>
                        <th data-field="precio_base" data-sortable="true" data-formatter="window.c.fVal" title="Precio Base">PB</th>
                        <th data-field="precio_venta_vendedor" data-sortable="true" data-formatter="window.c.fValV" title="Precio Vendedor">PV</th>
                        <th data-field="ganancia_vendedor" data-sortable="true" data-formatter="window.c.fVal" title="Ganancia Vendedor">GV</th>
                        <th data-formatter="window.c.fAcc">Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="cModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="cForm" onsubmit="window.c.save(event)">
                <div class="modal-header">
                    <h5 class="modal-title">Colección/Combo</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="cEmp" class="form-label">Empresa</label>
                        <select class="form-control mb-2" id="cEmp" required>
                            <option value="">Empresa...</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="cNombre" class="form-label">Nombre</label>
                        <input class="form-control mb-2" id="cNombre" placeholder="Nombre" required>
                    </div>

                    <div class="mb-3">
                        <label for="cFoto" class="form-label">Foto</label>
                        <input type="file" class="form-control mb-2" id="cFoto" accept="image/*">
                        <input type="hidden" id="cFotoActual">
                    </div>

                    <div class="mb-3">
                        <label for="cTipo" class="form-label">Tipo</label>
                        <select class="form-control mb-2" id="cTipo" onchange="window.c.tg();window.c.validarCampos()" required>
                            <option value="coleccion">Colección</option>
                            <option value="combo">Combo</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="cPb" class="form-label">
                            Precio de la empresa
                        </label>


                        <div class="input-group input-group-merge form-send-message">
                            <input type="number" step="0.01" min="0" class="form-control mb-1" id="cPb" placeholder="Precio Base" required oninput="window.c.validarCampos()">
                            <span class="message-actions input-group-text" title="Costo interno que maneja la empresa por la coleccion (El precio al que recibes al colección)">
                                <i class="icon-base text-primary bx bx-help-circle"></i>
                            </span>
                        </div>

                        <div class="invalid-feedback" id="cPbError"></div>






                    </div>

                    <div class="mb-3">
                        <label for="cPvv" class="form-label">Precio al entregar al vendedor</label>
                        <input type="number" step="0.01" min="0" class="form-control mb-1" id="cPvv" placeholder="Precio Venta Vendedor" oninput="window.c.validarCampos()">
                        <div class="invalid-feedback" id="cPvvError"></div>
                    </div>

                    <div class="mb-3">
                        <label for="cGv" class="form-label">Ganancia del vendedor (USD)</label>
                        <input type="number" step="0.01" min="0" class="form-control mb-1" id="cGv" placeholder="Ganancia Vendedor (USD)" oninput="window.c.validarCampos()">
                        <div class="invalid-feedback" id="cGvError"></div>
                    </div>

                    <input type="hidden" id="cId">
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary">Guardar</button></div>
            </form>
        </div>
    </div>
</div>

<script>
    window.c = {
        api: '<?= BASE_URL ?>api/colecciones',
        apiE: '<?= BASE_URL ?>api/empresas',
        emps: [],
        currentEmpresa: '',
        resp: (res) => ({
            rows: res.data || [],
            total: res.data?.length || 0
        }),
        queryParams: (p) => {
            if (window.c.currentEmpresa) p.empresa_id = window.c.currentEmpresa;
            return p;
        },
        fFoto: (v) => v ? `<img src="<?= BASE_URL ?>${v}" width="50">` : 'Sin foto',
        fNombre: (v, x) => `${v}<br><span class="badge bg-${x.tipo=='combo'?'info':'success'}">${x.tipo}</span>`,
        fVal: (v) => `$${v}`,
        fValV: (v, x) => `$${x.tipo=='combo'?x.precio_base:v}`,
        fAcc: (v, x) => {
            let xJ = JSON.stringify(x).replace(/'/g, "&apos;");
            return `<button class="btn btn-sm btn-info" onclick='window.c.edit(${xJ})'>Editar</button>
                    <button class="btn btn-sm btn-danger" onclick="window.c.del('${x.id}')">Borrar</button>`;
        },
        load(empresaId) {
            if (empresaId !== undefined) this.currentEmpresa = empresaId;
            $('#cTabla').bootstrapTable('refresh');
        },
        initTabs() {
            const ul = document.getElementById('cTabs');
            let html = `<li class="nav-item"><a class="nav-link active" data-empresa="" href="#">Todas</a></li>`;
            this.emps.forEach(e => {
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
        init() {
            fetch(this.apiE).then(r => r.json()).then(d => {
                this.emps = d.data || [];
                $('#cEmp').html('<option value="">Empresa...</option>' + this.emps.map(e => `<option value="${e.id}">${e.nombre}</option>`).join(''));
                this.initTabs();
                this.load();
            });
        },
        validarCampos() {
            const pb = parseFloat(document.getElementById('cPb')?.value) || 0;
            const pvv = parseFloat(document.getElementById('cPvv')?.value) || 0;
            const gv = parseFloat(document.getElementById('cGv')?.value) || 0;
            const esCombo = document.getElementById('cTipo')?.value === 'combo';

            let ok = true;

            function setErr(id, msg) {
                const el = document.getElementById(id);
                const input = el?.previousElementSibling;
                if (msg) {
                    el.textContent = msg;
                    input?.classList.add('is-invalid');
                    ok = false;
                } else {
                    el.textContent = '';
                    input?.classList.remove('is-invalid');
                }
            }

            if (pb <= 0) {
                setErr('cPbError', 'Debe ser mayor a cero.');
            } else {
                setErr('cPbError', '');
            }

            if (esCombo) {
                setErr('cPvvError', '');
                setErr('cGvError', '');
            } else {
                if (pvv < 0) {
                    setErr('cPvvError', 'No puede ser negativo.');
                } else if (pvv < pb) {
                    setErr('cPvvError', `Debe ser ≥ $${pb.toFixed(2)} (precio empresa).`);
                } else {
                    setErr('cPvvError', '');
                }

                if (gv < 0) {
                    setErr('cGvError', 'No puede ser negativo.');
                } else if (gv > pvv) {
                    setErr('cGvError', `No puede superar $${pvv.toFixed(2)} (precio vendedor).`);
                } else {
                    setErr('cGvError', '');
                }
            }

            return ok;
        },
        tg() {

            if (cTipo && cPvv) {
                let t = cTipo.value == 'combo';
                const pvvGroup = cPvv.closest('.mb-3');
                const gvGroup = document.getElementById('cGv')?.closest('.mb-3');
                if (t) {
                    pvvGroup.style.display = 'none';
                    gvGroup.style.display = 'none';
                    cPvv.value = '';
                    document.getElementById('cGv').value = '';
                } else {
                    pvvGroup.style.display = '';
                    gvGroup.style.display = '';
                }
            }
        },
        add() {
            const cForm = document.getElementById('cForm');
            if (cForm) cForm.reset();

            if (document.getElementById('cId')) document.getElementById('cId').value = '';
            if (document.getElementById('cFotoActual')) document.getElementById('cFotoActual').value = '';

            const cEmp = document.getElementById('cEmp');
            if (cEmp) {
                cEmp.disabled = false;
                const active = document.querySelector('#cTabs .nav-link.active');
                if (active && active.getAttribute('data-empresa')) {
                    cEmp.value = active.getAttribute('data-empresa');
                }
            }

            this.tg();
            new bootstrap.Modal(document.getElementById('cModal')).show();
        },
        edit(x) {
            if (document.getElementById('cId')) document.getElementById('cId').value = x.id;
            if (document.getElementById('cFotoActual')) document.getElementById('cFotoActual').value = x.foto;

            const cEmp = document.getElementById('cEmp');
            if (cEmp) {
                cEmp.value = x.empresa_id;
                cEmp.disabled = true;
            }

            if (document.getElementById('cNombre')) document.getElementById('cNombre').value = x.nombre;
            if (document.getElementById('cTipo')) document.getElementById('cTipo').value = x.tipo;
            if (document.getElementById('cFoto')) document.getElementById('cFoto').value = '';
            if (document.getElementById('cPb')) document.getElementById('cPb').value = x.precio_base;
            if (document.getElementById('cPvv')) document.getElementById('cPvv').value = x.precio_venta_vendedor;
            if (document.getElementById('cGv')) document.getElementById('cGv').value = x.ganancia_vendedor;

            this.tg();
            new bootstrap.Modal(document.getElementById('cModal')).show();
        },
        async save(e) {
            e.preventDefault();
            if (!this.validarCampos()) return;

            let i = document.getElementById('cId')?.value || '',
                t = document.getElementById('cTipo')?.value || '';

            const cFotoInput = document.getElementById('cFoto');
            const hasFile = cFotoInput && cFotoInput.files && cFotoInput.files[0];

            let body, headers = {};
            if (hasFile) {
                let fd = new FormData();
                fd.append('nombre', document.getElementById('cNombre')?.value || '');
                fd.append('tipo', t);
                fd.append('precio_base', document.getElementById('cPb')?.value || '');
                fd.append('ganancia_vendedor', document.getElementById('cGv')?.value || '');
                fd.append('precio_venta_vendedor', t == 'combo' ? (document.getElementById('cPb')?.value || '') : (document.getElementById('cPvv')?.value || ''));
                fd.append('foto_actual', document.getElementById('cFotoActual')?.value || '');
                if (!i) fd.append('empresa_id', document.getElementById('cEmp')?.value || '');
                fd.append('foto', cFotoInput.files[0]);
                body = fd;
            } else {
                headers['Content-Type'] = 'application/json';
                body = JSON.stringify({
                    nombre: document.getElementById('cNombre')?.value || '',
                    tipo: t,
                    precio_base: document.getElementById('cPb')?.value || '',
                    ganancia_vendedor: document.getElementById('cGv')?.value || '',
                    precio_venta_vendedor: t == 'combo' ? (document.getElementById('cPb')?.value || '') : (document.getElementById('cPvv')?.value || ''),
                    foto_actual: document.getElementById('cFotoActual')?.value || '',
                    ...(i ? {} : { empresa_id: document.getElementById('cEmp')?.value || '' })
                });
            }

            await fetch(this.api + (i ? '/' + i : ''), {
                method: 'POST',
                headers,
                body
            });

            const modalEl = document.getElementById('cModal');
            if (modalEl) {
                bootstrap.Modal.getInstance(modalEl).hide();
            }
            this.load();
        },
        async del(i) {
            if (!confirm('¿Borrar?')) return;
            await fetch(this.api + '/' + i, {
                method: 'DELETE'
            });
            this.load();
        }
    };

    document.addEventListener('DOMContentLoaded', () => window.c.init());
</script>