<?php
// TODO: Pendientes
// Verificaciones para hembras:
// - Edad mínima: 8 meses
// - Estado de preñez: No preñada
// el servicio no puede ocurrir antes del inicio del periodo

?>

<div class="container-fluid">
    <!-- Título y Botón de Nuevo Periodo -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <button type="button" class="btn btn-primary" id="btnNuevoPeriodo">
                        <i class="mdi mdi-plus"></i> Nuevo Periodo de Monta
                    </button>
                </div>
                <h4 class="page-title">Gestión Agro — Registro de montas</h4>
            </div>
        </div>
    </div>
    <!-- Tabla Principal -->
    <div class="row" id="tablaPrincipal">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <table id="tablaPeriodosMonta" data-toggle="table"
                        data-url="<?php echo BASE_URL; ?>api/periodos_servicios"
                        data-response-handler="responseHandler" data-pagination="true" data-search="true"
                        data-show-refresh="true" data-show-columns="true" data-locale="es-ES"
                        class="table table-striped table-hover" style="width:100%">
                        <thead>
                            <tr>
                                <th data-field="verraco_identificador" data-sortable="true">Verraco</th>
                                <th data-field="hembra_identificador" data-sortable="true">Hembra</th>
                                <th data-field="servicios_totales" data-align="center">N° Servicios</th>
                                <th data-field="fecha_inicio" data-sortable="true">Inicio</th>
                                <th data-field="fecha_ultima_monta" data-sortable="true">Último Servicio</th>
                                <th data-field="estado_periodo" data-formatter="estatusFomatter" data-align="center">Estado</th>
                                <th data-field="resultado_revision" data-formatter="resultadoFormatter" data-align="center">Resultado</th>
                                <th data-field="periodo_id" data-formatter="accionesFormatter" data-align="center">Acciones</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row hide" id="divPeriodoServicio">
        <div class="col-12">
            <div class="card">
                <div class="card-body">

                    <form id="formPeriodoServicio">

                        <div class="modal-header d-flex justify-content-between">
                            <h5 class="modal-title">Registrar Nuevo Periodo de Monta</h5>
                            <button class="btn btn-sm btn-danger" id="cancelarRegistroBtn">Cancelar</button>
                        </div>

                        <div class="modal-body">
                            <!-- Wizard Steps -->
                            <ul class="nav nav-tabs" id="wizardTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active disabled" id="paso1-tab" data-bs-toggle="tab" data-bs-target="#paso1" type="button"
                                        role="tab">1. Selección de Animales</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link disabled" id="paso2-tab" data-bs-toggle="tab" data-bs-target="#paso2" type="button"
                                        role="tab">2. Información del Periodo</button>
                                </li>
                            </ul>
                            <div class="tab-content mt-3">
                                <!-- Paso 1 -->
                                <div class="tab-pane fade show active" id="paso1" role="tabpanel">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="verraco_id" class="form-label">Seleccionar Verraco</label>
                                            <select class="form-select" id="verraco_id" name="verraco_id" required>
                                                <option value="">Seleccione...</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="hembra_id" class="form-label">Seleccionar Hembra</label>
                                            <select class="form-select" id="hembra_id" name="hembra_id" required>
                                                <option value="">Seleccione...</option>

                                            </select>
                                        </div>

                                        <div class="col-lg-6 d-flex">
                                            <div class="m-auto overflow-hidden" id="viz"></div>
                                        </div>
                                        <div class="col-lg-6 d-flex">
                                            <div class="m-auto overflow-hidden" id="viz2"></div>
                                        </div>

                                        <div class="col-md-12">
                                            <div class="alert alert-danger mt-2" id="infoCruce" style="display:none;">
                                                <strong>Origen Genético:</strong> <span id="origenGenetico">Cruce no recomendado, los animales están emparentados.</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <button type="button" class="btn btn-primary" id="btnSiguientePaso">Siguiente</button>
                                    </div>
                                </div>

                                <!-- Paso 2 -->
                                <div class="tab-pane fade" id="paso2" role="tabpanel">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="fecha_inicio" class="form-label">Fecha de Inicio</label>
                                            <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="numero_servicios" class="form-label">Cantidad de serivicios estimados</label>
                                            <input type="number" class="form-control" id="numero_servicios" name="numero_servicios" required>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="frecuencia_servicios" class="form-label">Frecuencia</label>
                                            <select class="form-control" id="frecuencia_servicios" name="frecuencia_servicios" required>
                                                <option value="">Seleccione</option>
                                                <option value="diaria">Diaria</option>
                                                <option value="cada_2_dias">Cada 2 días</option>
                                                <option value="cada_3_dias">Cada 3 días</option>
                                                <option value="cada_4_dias">Cada 4 días</option>
                                                <option value="cada_5_dias">Cada 5 días</option>
                                            </select>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="hora_servicio" class="form-label">Hora del servicio</label>
                                            <input type="time" class="form-control" id="hora_servicio" name="hora_servicio" required>
                                        </div>
                                        <div class="col-md-12 mb-3">
                                            <label for="observaciones" class="form-label">Observaciones</label>
                                            <textarea class="form-control" id="observaciones" name="observaciones" rows="3"
                                                placeholder="Notas sobre comportamiento, condiciones o manejo"></textarea>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <button type="button" class="btn btn-secondary me-2" id="btnAnteriorPaso">Atrás</button>
                                        <button type="submit" class="btn btn-success">Guardar Periodo</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Detalles del periodo -->
<div class="modal fade" id="modalDetalles" tabindex="-1" aria-labelledby="modalServicioLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-body">
                <div class="row g-4">
                    <div class="col-12">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h2 class="h4 mb-0">Periodo / Servicios</h2>
                        </div>
                        <div class="">
                            <div class="row gy-3">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong id="hembraIdent"></strong> <span class="text-muted">(hembra)</span></p>
                                    <p class="mb-0"><strong id="verracoIdent"></strong> <span class="text-muted">(verraco)</span></p>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-1">Inicio</div>
                                    <div><strong id="fechaInicio"></strong></div>
                                </div>
                                <div class="col-md-3 text-md-end">
                                    <div class="mb-1">Estado</div>
                                    <div id="estadoPeriodoWrap">
                                        <span id="estadoPeriodo" class="badge bg-secondary status-badge"></span>

                                    </div>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <h5 class="mb-0">Lista de servicios</h6>

                            <div class="table-responsive">
                                <table class="table table-borderless align-middle" id="tablaServicios">
                                    <thead>
                                        <tr>
                                            <th style="width:72px">#</th>
                                            <th>Fecha</th>
                                            <th>Estatus</th>
                                            <th>Sub estatus</th>
                                            <th class="text-end">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Modal: Registrar Servicio (Monta Individual) -->
<div class="modal fade" id="modalServicio" tabindex="-1" aria-labelledby="modalServicioLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formServicio">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalServicioLabel">Registrar Servicio de Monta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row">

                        <div class="col-md-12 mb-3">
                            <label for="fecha_servicio" class="form-label">Fecha del Servicio</label>
                            <input type="text" readonly class="form-control" id="fecha_servicio" name="fecha_servicio" required>
                        </div>


                        <div class="col-md-12 mb-3">
                            <label for="observacion_servicio" class="form-label">Observaciones</label>
                            <textarea class="form-control" id="observacion_servicio" name="observacion_servicio" rows="3"
                                placeholder="Ej: la hembra no presentó rechazo, comportamiento normal..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <input type="hidden" id="periodo_id" name="periodo_id">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Guardar Servicio</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://d3js.org/d3.v3.min.js"></script>
<script>
    const baseUrl = "<?= BASE_URL ?>";
</script>
<script type="module" src="<?= BASE_URL ?>/public/assets/js/modules/montas_view.js"></script>