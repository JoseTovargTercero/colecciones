<?php
// Nuevos tipos de incidencia
$tipos_incidencia = [
    'RECHAZO_CRIAS',
    'FUGA',
    'AGRESIVIDAD',   // Habilita consecuencias
    'RIÑA',          // Habilita consecuencias
    'OTRA'
];
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <button type="button" class="btn btn-primary" id="btnNuevaIncidencia">
                        <i class="mdi mdi-plus"></i> Nueva Incidencia
                    </button>
                </div>
                <h4 class="page-title">GESTIÓN DE INCIDENCIAS</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">

                    <ul class="nav nav-tabs nav-bordered mb-3" id="incidenciasTabs" role="tablist">
                        <li class="nav-item">
                            <a href="#tab-global" data-bs-toggle="tab" aria-expanded="false" class="nav-link active">
                                <i class="mdi mdi-format-list-bulleted d-md-none d-block"></i>
                                <span class="d-none d-md-block">Listado Global</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#tab-por-animal" data-bs-toggle="tab" aria-expanded="true" class="nav-link">
                                <i class="mdi mdi-cow d-md-none d-block"></i>
                                <span class="d-none d-md-block">Agrupado por Animal</span>
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content">

                        <div class="tab-pane show active" id="tab-global">

                            <div class="accordion mb-3" id="accordionFiltros">
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="headingFiltros">
                                        <button class="accordion-button collapsed py-2" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#collapseFiltros">
                                            <i class="mdi mdi-filter-variant me-2"></i> Filtros de Búsqueda
                                        </button>
                                    </h2>
                                    <div id="collapseFiltros" class="accordion-collapse collapse"
                                        data-bs-parent="#accordionFiltros">
                                        <div class="accordion-body">
                                            <div class="row g-2">
                                                <div class="col-md-3">
                                                    <label class="form-label small">Animal</label>
                                                    <select id="filtroAnimal" class="form-select"></select>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label small">Área</label>
                                                    <select id="filtroArea" class="form-select"></select>
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label small">Tipo</label>
                                                    <select id="filtroTipo" class="form-select">
                                                        <option value="">Todos</option>
                                                        <?php foreach ($tipos_incidencia as $opt): ?>
                                                            <option value="<?= $opt ?>">
                                                                <?= str_replace('_', ' ', strtoupper($opt)) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label small">Desde</label>
                                                    <input type="date" id="filtroDesde" class="form-control">
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label small">Hasta</label>
                                                    <input type="date" id="filtroHasta" class="form-control">
                                                </div>
                                                <div class="col-12 text-end">
                                                    <button type="button" class="btn btn-sm btn-light border"
                                                        id="btnResetFilters">Limpiar</button>
                                                    <button type="button" class="btn btn-sm btn-primary"
                                                        id="btnAplicarFiltros">Buscar</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <table id="tablaIncidencias" data-toggle="table"
                                data-url="<?php echo BASE_URL; ?>api/incidencias"
                                data-response-handler="responseHandler" data-pagination="true"
                                data-side-pagination="server" data-search="true" data-show-refresh="true"
                                data-locale="es-ES" data-sort-name="fecha_evento" data-sort-order="desc"
                                class="table table-striped table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th data-field="fecha_evento" data-formatter="fechaHoraFormatter"
                                            data-sortable="true">Fecha</th>
                                        <th data-field="animal_identificador" data-sortable="true">Animal</th>

                                        <th data-field="fotografia_url" data-formatter="fotoTableFormatter"
                                            data-align="center">Evidencia</th>

                                        <th data-field="tipo" data-formatter="tipoFormatter" data-align="center"
                                            data-sortable="true">Tipo</th>
                                        <th data-field="descripcion" data-sortable="true">Descripción</th>
                                        <th data-field="area_nombre" data-sortable="true">Ubicación</th>
                                        <th data-field="incidencia_id" data-formatter="incidenciaAccionesFormatter"
                                            data-align="center">Acciones</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>

                        <div class="tab-pane" id="tab-por-animal">
                            <div class="alert alert-light border mb-3">
                                <i class="mdi mdi-information-outline"></i> Seleccione un animal para ver su historial
                                clínico de incidencias.
                            </div>
                            <table id="tablaAnimalesIncidencias" data-toggle="table"
                                data-url="<?php echo BASE_URL; ?>api/animales" data-pagination="true" data-search="true"
                                data-response-handler="responseHandler" class="table table-hover">
                                <thead>
                                    <tr>
                                        <th data-field="identificador" data-sortable="true">Identificador</th>
                                        <th data-field="especie" data-sortable="true">Especie</th>
                                        <th data-field="sexo" data-sortable="true">Sexo</th>
                                        <th data-field="ubicacion_actual" data-formatter="ubicacionFormatter">Ubicación
                                        </th>
                                        <th data-field="animal_id" data-formatter="historialBtnFormatter"
                                            data-align="center">Historial</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDetalle" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalles de la Incidencia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalDetalleBody">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalHistorialAnimal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title">Historial de Incidencias: <span id="lblHistorialAnimal"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <table id="tablaHistorialIndividual" class="table table-striped table-borderless mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Descripción</th>
                            <th>Ubicación</th>
                            <th>Responsable</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="bodyHistorialIndividual"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalIncidencia" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalIncidenciaLabel">Nueva Incidencia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formIncidencia" data-validation="reactive">
                <div class="modal-body">
                    <input type="hidden" name="incidencia_id" id="incidencia_id">

                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label">Animal Protagonista*</label>
                            <div id="animal_id-container">
                                <select class="form-select" id="animal_id" name="animal_id"
                                    data-rules="noVacio"></select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Ubicación del Suceso</label>
                            <div id="area_id-container">
                                <select class="form-select" id="area_id" name="area_id"></select>
                            </div>
                            <div class="form-text text-muted small" style="font-size: 0.7rem;">Dejar vacío para usar
                                ubicación actual.</div>
                        </div>
                    </div>

                    <div class="row g-2 mt-2">
                        <div class="col-md-6">
                            <label class="form-label">Tipo*</label>
                            <select class="form-select" id="tipo" name="tipo" data-rules="noVacio">
                                <option value="">Seleccione...</option>
                                <?php foreach ($tipos_incidencia as $opt): ?>
                                    <option value="<?= $opt ?>"><?= str_replace('_', ' ', strtoupper($opt)) ?>
                                    </option>


                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fecha y Hora*</label>
                            <input type="datetime-local" class="form-control" id="fecha_evento" name="fecha_evento"
                                data-rules="noVacio">
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="2"></textarea>
                    </div>

                    <div class="mt-2">
                        <label class="form-label">Responsable</label>
                        <input type="text" class="form-control" id="responsable" name="responsable">
                    </div>
                    <div class="mt-2">
                        <label class="form-label">Fotografía (Opcional)</label>
                        <input type="file" class="form-control" id="fotografia" name="fotografia" accept="image/*">
                        <div class="form-text text-muted small" style="font-size: 0.7rem;">Max 20MB. Tipos permitidos:
                            JPG, PNG, WEBP.</div>

                        <div id="foto-preview-container" class="mt-2 border p-1 rounded d-none">
                            <img id="foto-preview" src="#" alt="Vista previa de la imagen" class="img-fluid rounded"
                                style="max-height: 150px;">
                            <div class="d-flex justify-content-between mt-1">
                                <span class="text-muted small" id="foto-filename"></span>
                                <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1"
                                    id="btn-eliminar-foto-existente">
                                    <i class="mdi mdi-trash-can-outline"></i> Eliminar
                                </button>
                            </div>
                            <input type="hidden" name="eliminar_foto_existente" id="eliminar_foto_existente" value="0">
                        </div>
                    </div>

                    <div id="seccion-consecuencias" class="card mt-3 border-danger d-none">
                        <div class="card-header bg-danger text-white py-1 d-flex justify-content-between">
                            <small class="fw-bold"><i class="mdi mdi-medical-bag"></i> Víctimas / Consecuencias</small>
                            <small>Genera registros de salud</small>
                        </div>
                        <div class="card-body p-2 bg-light">
                            <div class="row mb-1 text-muted small fw-bold px-1">
                                <div class="col-md-4">Animal Afectado</div>
                                <div class="col-md-4">Detalle Herida</div>
                                <div class="col-md-3">Severidad</div>
                                <div class="col-md-1"></div>
                            </div>
                            <div id="lista-victimas"></div>
                            <button type="button" class="btn btn-sm btn-outline-secondary w-100 mt-2 border-dashed"
                                id="btnAgregarVictima">
                                <i class="mdi mdi-plus"></i> Agregar Animal Afectado
                            </button>
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

<script> const baseUrl = "<?= BASE_URL ?>"; </script>
<script type="module" src="<?= BASE_URL ?>public/assets/js/modules/animales_incidencias_view.js"></script>