<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex justify-content-between align-items-center">
                <h4 class="page-title">Gerencias</h4>
                <button class="btn btn-primary" onclick="window.g.add()">+ Nueva Gerencia</button>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">

            <div class="info-banner" role="alert">
                <div class="icon-wrap"><i class="bx bx-info-circle"></i></div>
                <div class="info-text" style="font-size: 15px;">
                    <b>Opcional.</b> Útil únicamente si gestionas varias gerencias; permite agrupar las cobranzas de tus vendedores por área para agilizar la liquidación de cada factura.
                </div>
            </div>

            <div id="vTablaToolbar" class="d-flex align-items-center gap-2 py-1">
                <h6 class="mb-0 fw-semibold" style="color:#495057;font-size:0.9rem">Listado de gerencias</h6>
            </div>
            <table id="gTabla" class="table table-hover" data-toolbar="#vTablaToolbar" data-toggle="table" data-url="<?= BASE_URL ?>api/gerencias" data-response-handler="window.g.resp" data-search="true" data-pagination="true" data-page-size="15">
                <thead>
                    <tr>
                        <th data-field="nombre" data-sortable="true">Nombre</th>
                        <th data-formatter="window.g.fAcc">Acciones</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="gModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="gForm" onsubmit="window.g.save(event)">
                <div class="modal-header">
                    <h5 class="modal-title">Gerencia</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="gId">
                    <div class="mb-3">
                        <label for="gNombre" class="form-label">Nombre de la gerencia</label>
                        <input class="form-control" id="gNombre" placeholder="Nombre" required autofocus>
                    </div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary">Guardar</button></div>
            </form>
        </div>
    </div>
</div>

<script>
    window.g = {
        api: '<?= BASE_URL ?>api/gerencias',
        resp: (res) => ({
            rows: res.data || [],
            total: res.data?.length || 0
        }),
        fAcc: (v, x) => {
            let xJ = JSON.stringify(x).replace(/'/g, "&apos;");
            return `<button class="btn btn-sm btn-info" onclick='window.g.edit(${xJ})'>Editar</button>
                    <button class="btn btn-sm btn-danger" onclick="window.g.del('${x.id}')">Borrar</button>`;
        },
        add() {
            document.getElementById('gForm').reset();
            document.getElementById('gId').value = '';
            new bootstrap.Modal(document.getElementById('gModal')).show();
        },
        edit(x) {
            document.getElementById('gId').value = x.id;
            document.getElementById('gNombre').value = x.nombre;
            new bootstrap.Modal(document.getElementById('gModal')).show();
        },
        async save(e) {
            e.preventDefault();
            let i = document.getElementById('gId')?.value || '';
            let b = {
                nombre: document.getElementById('gNombre')?.value || ''
            };
            await fetch(this.api + (i ? '/' + i : ''), {
                method: i ? 'PUT' : 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(b)
            });
            const modalEl = document.getElementById('gModal');
            if (modalEl) bootstrap.Modal.getInstance(modalEl).hide();
            $('#gTabla').bootstrapTable('refresh');
        },
        async del(i) {
            if (!confirm('¿Borrar?')) return;
            await fetch(this.api + '/' + i, {
                method: 'DELETE'
            });
            $('#gTabla').bootstrapTable('refresh');
        }
    };
    document.addEventListener('DOMContentLoaded', () => {});
</script>