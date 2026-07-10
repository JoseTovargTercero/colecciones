<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex justify-content-between align-items-center">
                <h4 class="page-title">Artículos</h4>
                <button class="btn btn-primary" onclick="window.a.add()">+ Nuevo Artículo</button>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header p-0">
            <ul class="nav nav-tabs" id="aTabs" role="tablist"></ul>
        </div>
        <div class="card-body">
            <table id="aTabla" class="table table-hover" data-toggle="table"
                data-url="<?= BASE_URL ?>api/articulos"
                data-query-params="window.a.queryParams"
                data-response-handler="window.a.resp"
                data-search="true" data-pagination="true" data-page-size="15">
                <thead>
                    <tr>
                        <th data-field="foto" data-formatter="window.a.fFoto">Foto</th>
                        <th data-field="nombre" data-sortable="true" data-formatter="window.a.fNombre">Nombre</th>
                        <th data-field="precio_final" data-sortable="true" data-formatter="window.a.fVal" title="Precio Final">Precio</th>
                        <th data-formatter="window.a.fAcc">Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="aModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="aForm" onsubmit="window.a.save(event)">
                <div class="modal-header">
                    <h5 class="modal-title">Artículo</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="aEmp" class="form-label">Empresa</label>
                        <select class="form-control mb-2" id="aEmp" required>
                            <option value="">Empresa...</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="aColeccion" class="form-label">Colección (opcional)</label>
                        <select class="form-control mb-2" id="aColeccion">
                            <option value="">Sin colección...</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="aNombre" class="form-label">Nombre</label>
                        <input class="form-control mb-2" id="aNombre" placeholder="Nombre" required>
                    </div>

                    <div class="mb-3">
                        <label for="aFoto" class="form-label">Foto</label>
                        <input type="file" class="form-control mb-2" id="aFoto" accept="image/*">
                        <input type="hidden" id="aFotoActual">
                    </div>

                    <div class="mb-3">
                        <label for="aPrecio" class="form-label">Precio Final</label>
                        <input type="number" step="0.01" min="0" class="form-control mb-1" id="aPrecio" placeholder="Precio Final" required>
                    </div>

                    <input type="hidden" id="aId">
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary">Guardar</button></div>
            </form>
        </div>
    </div>
</div>

<script>
    window.a = {
        api: '<?= BASE_URL ?>api/articulos',
        apiE: '<?= BASE_URL ?>api/empresas',
        apiC: '<?= BASE_URL ?>api/colecciones',
        emps: [],
        colecciones: [],
        currentEmpresa: '',
        resp: (res) => ({
            rows: res.data || [],
            total: res.data?.length || 0
        }),
        queryParams: (p) => {
            if (window.a.currentEmpresa) p.empresa_id = window.a.currentEmpresa;
            return p;
        },
        fFoto: (v) => v ? `<img src="<?= BASE_URL ?>${v}" width="50">` : 'Sin foto',
        fNombre: (v, x) => `${v}`,
        fVal: (v) => `$${v}`,
        fAcc: (v, x) => {
            let xJ = JSON.stringify(x).replace(/'/g, "&apos;");
            return `<button class="btn btn-sm btn-info" onclick='window.a.edit(${xJ})'>Editar</button>
                    <button class="btn btn-sm btn-danger" onclick="window.a.del('${x.id}')">Borrar</button>`;
        },
        load(empresaId) {
            if (empresaId !== undefined) this.currentEmpresa = empresaId;
            $('#aTabla').bootstrapTable('refresh');
        },
        initTabs() {
            const ul = document.getElementById('aTabs');
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
        cargarColecciones(empresaId, selectedId) {
            const sel = document.getElementById('aColeccion');
            if (!empresaId) {
                sel.innerHTML = '<option value="">Sin colección...</option>';
                return;
            }
            fetch(this.apiC + '?empresa_id=' + empresaId).then(r => r.json()).then(d => {
                const items = d.data || [];
                sel.innerHTML = '<option value="">Sin colección...</option>' + items.map(c =>
                    `<option value="${c.id}" ${c.id == selectedId ? 'selected' : ''}>${c.nombre}</option>`
                ).join('');
            });
        },
        init() {
            fetch(this.apiE).then(r => r.json()).then(d => {
                this.emps = d.data || [];
                $('#aEmp').html('<option value="">Empresa...</option>' + this.emps.map(e => `<option value="${e.id}">${e.nombre}</option>`).join(''));
                this.initTabs();
                this.load();
            });
            document.getElementById('aEmp')?.addEventListener('change', (e) => {
                this.cargarColecciones(e.target.value);
            });
        },
        add() {
            const aForm = document.getElementById('aForm');
            if (aForm) aForm.reset();

            if (document.getElementById('aId')) document.getElementById('aId').value = '';
            if (document.getElementById('aFotoActual')) document.getElementById('aFotoActual').value = '';

            const aEmp = document.getElementById('aEmp');
            if (aEmp) {
                aEmp.disabled = false;
                const active = document.querySelector('#aTabs .nav-link.active');
                if (active && active.getAttribute('data-empresa')) {
                    aEmp.value = active.getAttribute('data-empresa');
                    this.cargarColecciones(aEmp.value);
                }
            }

            new bootstrap.Modal(document.getElementById('aModal')).show();
        },
        edit(x) {
            if (document.getElementById('aId')) document.getElementById('aId').value = x.id;
            if (document.getElementById('aFotoActual')) document.getElementById('aFotoActual').value = x.foto;

            const aEmp = document.getElementById('aEmp');
            if (aEmp) {
                aEmp.value = x.empresa_id;
                aEmp.disabled = true;
                this.cargarColecciones(x.empresa_id, x.coleccion_id);
            }
            if (document.getElementById('aNombre')) document.getElementById('aNombre').value = x.nombre;
            if (document.getElementById('aFoto')) document.getElementById('aFoto').value = '';
            if (document.getElementById('aPrecio')) document.getElementById('aPrecio').value = x.precio_final;

            new bootstrap.Modal(document.getElementById('aModal')).show();
        },
        async save(e) {
            e.preventDefault();

            let i = document.getElementById('aId')?.value || '';

            const aFotoInput = document.getElementById('aFoto');
            const hasFile = aFotoInput && aFotoInput.files && aFotoInput.files[0];

            let body, headers = {};
            if (hasFile) {
                let fd = new FormData();
                fd.append('nombre', document.getElementById('aNombre')?.value || '');
                fd.append('precio_final', document.getElementById('aPrecio')?.value || '');
                fd.append('coleccion_id', document.getElementById('aColeccion')?.value || '');
                fd.append('foto_actual', document.getElementById('aFotoActual')?.value || '');
                if (!i) fd.append('empresa_id', document.getElementById('aEmp')?.value || '');
                fd.append('foto', aFotoInput.files[0]);
                body = fd;
            } else {
                headers['Content-Type'] = 'application/json';
                body = JSON.stringify({
                    nombre: document.getElementById('aNombre')?.value || '',
                    precio_final: document.getElementById('aPrecio')?.value || '',
                    coleccion_id: document.getElementById('aColeccion')?.value || '',
                    foto_actual: document.getElementById('aFotoActual')?.value || '',
                    ...(i ? {} : { empresa_id: document.getElementById('aEmp')?.value || '' })
                });
            }

            const resp = await fetch(this.api + (i ? '/' + i : ''), {
                method: 'POST',
                headers,
                body
            });
            let msg;
            let text = await resp.text();
            try {
                const json = JSON.parse(text);
                if (json.value) {
                    const modalEl = document.getElementById('aModal');
                    if (modalEl) bootstrap.Modal.getInstance(modalEl).hide();
                    this.load();
                    if (typeof window.tutorialAvanzar === 'function') window.tutorialAvanzar();
                    return;
                }
                msg = json.message;
            } catch (_) {
                msg = text || 'Error desconocido';
            }
            Swal.fire({ icon: 'error', title: 'Error', text: msg });
        },
        async del(i) {
            if (!confirm('¿Borrar?')) return;
            await fetch(this.api + '/' + i, {
                method: 'DELETE'
            });
            this.load();
        }
    };

    document.addEventListener('DOMContentLoaded', () => window.a.init());
</script>