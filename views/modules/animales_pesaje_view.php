<?php
// animal_pesos_view.php
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">GESTIÓN DE PESO Y CRECIMIENTO</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <table id="tablaAnimalesPesos" data-toggle="table" data-url="<?php echo BASE_URL; ?>api/animales"
                        data-response-handler="responseHandler" data-pagination="true" data-search="true"
                        data-show-refresh="true" data-locale="es-ES" class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th data-field="identificador" data-sortable="true">Identificador</th>
                                <th data-field="especie" data-sortable="true">Especie</th>
                                <th data-field="raza_nombre" data-sortable="true">Raza</th>
                                <th data-field="sexo" data-sortable="true">Sexo</th>
                                <th data-field="ultimo_peso_kg" data-sortable="true" data-formatter="pesoMainFormatter">
                                    Último Peso</th>
                                <th data-field="fecha_nacimiento" data-sortable="true" data-formatter="edadFormatter">
                                    Edad</th>
                                <th data-field="animal_id" data-formatter="accionesPesoFormatter" data-halign="center"
                                    data-align="center">Acciones</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalRegistroPeso" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Registrar Nuevo Peso</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formRegistroPeso" data-validation="reactive">
                <div class="modal-body">
                    <input type="hidden" name="animal_id" id="peso_animal_id">

                    <div class="alert alert-info py-2">
                        <i class="mdi mdi-cow"></i> Animal: <strong id="lbl_animal_identificador">---</strong>
                    </div>

                    <div class="mb-3">
                        <label for="fecha_peso" class="form-label">Fecha del Pesaje</label>
                        <input type="date" class="form-control" id="fecha_peso" name="fecha_peso" data-rules="noVacio"
                            data-message-no-vacio="La fecha es requerida.">
                    </div>
                    <div class="row mb-3">
                        <div class="col-8">
                            <label for="peso_kg" class="form-label">Peso</label>
                            <input type="number" step="0.01" class="form-control" id="peso_kg" name="peso_kg"
                                data-rules="noVacio|esNumeroPositivo" data-message-no-vacio="El peso es requerido."
                                data-message-es-numero-positivo="El peso debe ser positivo.">
                        </div>
                        <div class="col-4">
                            <label for="unidad" class="form-label">Unidad</label>
                            <select class="form-select" id="unidad" name="unidad">
                                <option value="KG">KG</option>
                                <option value="LB">LB</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="metodo" class="form-label">Método (Báscula, Cinta, etc.)</label>
                        <input type="text" class="form-control" id="metodo" name="metodo"
                            data-rules="longitudMaxima:100">
                    </div>
                    <div class="mb-3">
                        <label for="observaciones_peso" class="form-label">Observaciones</label>
                        <textarea class="form-control" id="observaciones_peso" name="observaciones" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Guardar Peso</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalHistorialPesos" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Historial de Crecimiento: <span id="historial_titulo_animal"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light">

                <div class="card shadow-sm mb-3">
                    <div class="card-body p-2">
                        <h6 class="card-title text-muted mb-0">Curva de Peso</h6>
                        <div dir="ltr">
                            <div id="peso-chart" class="apex-charts" data-colors="#727cf5,#0acf97"></div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-0">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-sm mb-0 table-centered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Peso (kg)</th>
                                        <th>Ganancia</th>
                                        <th>Método</th>
                                        <th>Notas</th>
                                        <th style="width: 50px;">Acción</th>
                                    </tr>
                                </thead>
                                <tbody id="tablaHistorialBody">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
    const baseUrl = "<?php echo BASE_URL; ?>";
</script>
<script src="<?= BASE_URL ?>public/assets/js/vendor/apexcharts.min.js"></script>
<script type="module" src="<?= BASE_URL ?>public/assets/js/modules/animales_pesaje_view.js"></script>