<div class="container-fluid">




   <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <button class="btn btn-primary" id="btnNuevoAlimento">
                        <i class="fa fa-plus"></i> Nuevo alimento
                    </button>
                    <button class="btn btn-success" id="btnRegistrarIngreso">
                        <i class="fa fa-arrow-down"></i> Registrar Ingreso
                    </button>
                </div>
                <h4 class="page-title">Registro de Alimentos</h4>
            </div>
    </div>
    </div>


    <div class="row">
        <div class="col-12">
            <div class="card">
 <div class="card-body">
       <table id="tablaAlimentos"
           data-toggle="table"
           data-url="<?php echo BASE_URL; ?>api/alimentos"
           data-response-handler="responseHandler"
           data-pagination="true"
           data-search="true"
           data-show-refresh="true"
           data-show-columns="true"
           data-locale="es-ES"
           class="table table-striped table-hover"
           style="width:100%">
        <thead>
            <tr>
                <th data-field="nombre">Nombre</th>
                <th data-field="tipo">Tipo</th>
                <th data-field="stock_kg">Stock (kg)</th>
                <th data-field="stock_minimo_kg">Stock mínimo</th>
                <th data-field="activo" data-formatter="activoFormatter">Activo</th>
                <th data-field="acciones" data-formatter="accionesFormatter">Acciones</th>
            </tr>
        </thead>
    </table>
 </div>
            </div>
        </div>
    </div>

<!-- Modal -->
<div class="modal fade" id="modalAlimento" tabindex="-1">
    <div class="modal-dialog">
        <form id="formAlimento" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Alimento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <input type="hidden" id="alimento_id">

                <div class="mb-3">
                    <label class="form-label">Nombre</label>
                    <input type="text" id="nombre" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Tipo</label>
                    <input type="text" id="tipo" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Stock mínimo (kg)</label>
                    <input type="number" step="0.01" id="stock_minimo_kg" class="form-control">
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Ingreso -->
<div class="modal fade" id="modalIngreso" tabindex="-1">
    <div class="modal-dialog">
        <form id="formIngreso" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Registrar Ingreso de Alimento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Alimento</label>
                    <select id="ingreso_alimento_id" class="form-select" required>
                        <option value="">Seleccione...</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Cantidad (kg)</label>
                    <input type="number" step="0.01" min="1" id="ingreso_cantidad" class="form-control" required>
                    <div class="form-text">Debe ser mayor o igual a 1 kg.</div>
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Registrar</button>
            </div>
        </form>
    </div>
</div>
<script type="module" src="<?= BASE_URL ?>/public/assets/js/modules/alimentos.js"></script>
