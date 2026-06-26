<div class="container-fluid">
<div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Planes Alimenticios</h4>
        <button class="btn btn-primary" id="btnNuevoPlan">
            <i class="fa fa-plus"></i> Nuevo plan
        </button>
    </div>
    <div class="row">
        <div class="col-lg-12">
<div class="card">
    <div class="card-body">
        

    <table id="tablaPlanes"
           data-toggle="table"
           data-url="<?php echo BASE_URL; ?>api/alimentos_planes"
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
            <th data-field="ubicacion">Ubicación</th>
            <th data-field="cantidad_animales_estimados">Animales</th>
            <th data-field="activo" data-formatter="activoFormatter">Activo</th>
            <th data-field="acciones" data-formatter="accionesFormatter">Acciones</th>
        </tr>
        </thead>
    </table>
    </div>
</div>
        </div>
    </div>

</div>

<!-- Modal -->
<div class="modal fade" id="modalPlan" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form id="formPlan" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Plan alimenticio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <input type="hidden" id="plan_id">

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label>Ubicación</label>
                        <select id="ubicacion_id" class="form-select" required></select>
                    </div>
                    <div class="col-md-6">
                        <label>Nombre</label>
                        <input type="text" id="nombre" class="form-control">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label>Animales estimados</label>
                        <input type="number" id="cantidad_animales_estimados" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label>Observación</label>
                        <input type="text" id="observacion" class="form-control">
                    </div>
                </div>

                <hr>
                <h6>Horarios y consumo</h6>

                <div id="detallePlan"></div>

                <button type="button" class="btn btn-outline-secondary mt-2" id="btnAgregarHorario">
                    + Agregar horario
                </button>

            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-primary" type="submit">Guardar</button>
            </div>
        </form>
    </div>
</div>
<script type="module" src="<?= BASE_URL ?>/public/assets/js/modules/alimentos_planes.js"></script>
