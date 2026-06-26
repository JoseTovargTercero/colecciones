<div class="container-fluid">
    <h3 class="mb-3">Registro diario de consumo</h3>

 
    <div class="d-flex justify-content-between">
           

        <button id="btnRegistrarTodos" class="btn btn-success mb-3">
            Registrar todos los planes de hoy
        </button>

           <div class="form-check form-switch mb-3 hide">
               <input class="form-check-input" type="checkbox" id="modoAutomatico">
      <label class="form-check-label" for="modoAutomatico">

            Modo automático (cron)
        </label>
    </div>

       
    </div>

   <div class="card">
    <div class="card-body">
         <table id="tablaConsumo"
           data-toggle="table"
           data-url="<?= BASE_URL ?>api/alimentos_consumo/estado"
           data-response-handler="responseHandler"
           data-pagination="true"
           data-search="true"
           data-show-refresh="true"
           data-locale="es-ES"
           class="table table-striped table-hover">
        <thead>
            <tr>
                <th data-field="ubicacion">Ubicación</th>
                <th data-field="nombre">Plan</th>
                <th data-field="estado">Estado hoy</th>
                <th data-field="acciones"
                    data-formatter="accionesFormatter"
                    data-align="center">
                    Acciones
                </th>
            </tr>
        </thead>
    </table>
    </div>
   </div>
</div>


<div class="modal fade" id="modalRegistroPlan" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">
                    Registro manual de consumo
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <div class="mb-3">
                    <strong>Plan:</strong>
                    <span id="detallePlanNombre"></span><br>

                    <strong>Ubicación:</strong>
                    <span id="detallePlanUbicacion"></span><br>

                    <strong>Fecha:</strong>
                    <span id="detallePlanFecha"></span>
                </div>

                <table class="table  table-hover" id="tablaDetallePlan">
                    <thead >
                        <tr>
                            <th>Hora</th>
                            <th>Cantidad (kg)</th>
                            <th>Estado</th>
                            <th class="text-center">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- dinámico -->
                    </tbody>
                </table>

            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">
                    Cerrar
                </button>
            </div>

        </div>
    </div>
</div>

<!-- MODAL REGISTRO MANUAL -->
<div class="modal fade" id="modalRegistroPlan" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Registro manual del plan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <table id="tablaDetallePlan" class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Alimento</th>
                            <th>Hora</th>
                            <th>Consumo (kg)</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">
                    Cancelar
                </button>
                <button id="btnConfirmarRegistro" class="btn btn-primary">
                    Registrar consumo
                </button>
            </div>
        </div>
    </div>
</div>

<script type="module" src="<?= BASE_URL ?>public/assets/js/modules/alimentos_consumo.js"></script>
