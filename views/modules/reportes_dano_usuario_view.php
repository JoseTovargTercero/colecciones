<?php
// Valores para los dropdowns de filtros y formularios
$criticidad_opts = ['BAJA', 'MEDIA', 'ALTA'];
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <button type="button" class="btn btn-primary" id="btnNuevoReporte">
                        <i class="mdi mdi-plus"></i> Nuevo Reporte
                    </button>
                </div>
                <h4 class="page-title">MIS REPORTES DE DAÑO</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <a data-bs-toggle="collapse" href="#collapseFiltros" aria-expanded="false"
                            aria-controls="collapseFiltros" class="text-dark">
                            <i class="mdi mdi-filter-variant"></i> Filtros de Búsqueda
                            <i class="mdi mdi-chevron-down float-end"></i>
                        </a>
                    </h5>
                </div>
                <div class="collapse" id="collapseFiltros">
                    <div class="card-body">
                        <div class="d-flex flex-wrap gap-2" style="width: 100%;">
                            <div style="min-width: 150px; flex-grow: 1;">
                                <label class="form-label mb-1">Finca</label>
                                <select id="filtroFinca" class="form-select"></select>
                            </div>
                            <div style="min-width: 150px; flex-grow: 1;">
                                <label class="form-label mb-1">Aprisco</label>
                                <select id="filtroAprisco" class="form-select"></select>
                            </div>
                            <div style="min-width: 150px; flex-grow: 1;">
                                <label class="form-label mb-1">Área</label>
                                <select id="filtroArea" class="form-select"></select>
                            </div>
                            <div style="min-width: 150px; flex-grow: 1;">
                                <label class="form-label mb-1">Recinto</label>
                                <select id="filtroRecinto" class="form-select"></select>
                            </div>
                            <div style="min-width: 150px; flex-grow: 1;">
                                <label class="form-label mb-1">Estado</label>
                                <select id="filtroEstado" class="form-select">
                                    <option value="">Todos</option>
                                    <option value="ABIERTO">Abierto</option>
                                    <option value="EN_PROCESO">En Proceso</option>
                                    <option value="CERRADO">Cerrado</option>
                                </select>
                            </div>
                            <div style="min-width: 150px; flex-grow: 1;">
                                <label class="form-label mb-1">Criticidad</label>
                                <select id="filtroCriticidad" class="form-select">
                                    <option value="">Todas</option>
                                    <?php foreach ($criticidad_opts as $opt): ?>
                                        <option value="<?= $opt ?>"><?= ucfirst(strtolower($opt)) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div style="min-width: 150px; flex-grow: 1; align-self: flex-end;">
                                <button type="button" class="btn btn-outline-secondary w-100" id="btnResetFilters">
                                    <i class="mdi mdi-filter-remove-outline"></i> Limpiar Filtros
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <table id="tablaReportes" class="table table-striped table-hover align-middle" style="width:100%"
                        data-toggle="table" data-url="<?php echo BASE_URL; ?>api/reportes_dano"
                        data-response-handler="responseHandler" data-pagination="true" data-search="true"
                        data-show-refresh="true" data-locale="es-ES" data-sort-name="fecha_reporte"
                        data-sort-order="desc">
                        <thead>
                            <tr>
                                <th data-field="fecha_reporte" data-formatter="reporteFechaFormatter"
                                    data-sortable="true">Fecha</th>
                                <th data-field="titulo" data-sortable="true">Título</th>
                                <th data-field="finca_nombre" data-sortable="true">Finca</th>
                                <th data-field="aprisco_nombre" data-sortable="true">Aprisco</th>
                                <th data-field="area_label" data-sortable="true">Área</th>
                                <th data-field="recinto_label" data-sortable="true">Recinto</th>
                                <th data-field="criticidad" data-formatter="criticidadFormatter" data-align="center"
                                    data-sortable="true">Criticidad</th>
                                <th data-field="estado_reporte" data-formatter="reporteEstadoFormatter"
                                    data-align="center" data-sortable="true">Estado</th>
                                <th data-field="reporte_id" data-formatter="reporteAccionesFormatter"
                                    data-align="center">Acciones</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDetalle" tabindex="-1" aria-labelledby="modalDetalleLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDetalleLabel">Detalles del Reporte</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="modalDetalleBody">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalReporte" tabindex="-1" aria-labelledby="modalReporteLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalReporteLabel">Nuevo Reporte de Daño</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formReporte" data-validation="reactive">
                <div class="modal-body">
                    <p class="text-muted">La ubicación es opcional, pero ayuda a identificar el problema rápidamente.
                    </p>
                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-label" for="finca_id">Finca</label>
                            <div id="finca_id-container">
                                <select class="form-select" id="finca_id" name="finca_id"
                                    data-error-container="#finca_id-container"></select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="aprisco_id">Aprisco</label>
                            <div id="aprisco_id-container">
                                <select class="form-select" id="aprisco_id" name="aprisco_id"
                                    data-error-container="#aprisco_id-container"></select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="area_id">Área</label>
                            <div id="area_id-container">
                                <select class="form-select" id="area_id" name="area_id"
                                    data-error-container="#area_id-container"></select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="recinto_id">Recinto</label>
                            <div id="recinto_id-container">
                                <select class="form-select" id="recinto_id" name="recinto_id"
                                    data-error-container="#recinto_id-container"></select>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label" for="titulo">Título</label>
                        <input type="text" class="form-control" id="titulo" name="titulo"
                            data-rules="noVacio|longitudMaxima:255" data-message-no-vacio="El título es requerido."
                            data-message-longitud-maxima="El título no puede exceder los 255 caracteres.">
                    </div>
                    <div class="mt-3">
                        <label class="form-label" for="descripcion">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3"
                            data-rules="noVacio|longitudMaxima:1000"
                            data-message-no-vacio="La descripción es requerida."
                            data-message-longitud-maxima="La descripción no puede exceder los 1000 caracteres."></textarea>
                    </div>
                    <div class="row g-2 mt-3">
                        <div class="col-12">
                            <label class="form-label" for="criticidad">Criticidad</label>
                            <select class="form-select" id="criticidad" name="criticidad" data-rules="noVacio"
                                data-message-no-vacio="Debe seleccionar una criticidad.">
                                <option value="">Seleccione...</option>
                                <?php foreach ($criticidad_opts as $opt): ?>
                                    <option value="<?= $opt ?>"><?= ucfirst(strtolower($opt)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary" type="submit">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* Estilos (Sin cambios) */
</style>

<script>
    const baseUrl = "<?= BASE_URL ?>";
</script>
<script type="module" src="<?= BASE_URL ?>public/assets/js/modules/reportes_dano_usuario_view.js"></script>