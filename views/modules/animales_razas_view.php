<?php
// Valores para los dropdowns de filtros y formularios
//
$especie_opts = ['BOVINO', 'OVINO', 'CAPRINO', 'PORCINO', 'OTRO'];
$estado_opts = ['ACTIVA', 'INACTIVA'];
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <button type="button" class="btn btn-primary" id="btnNuevaRaza">
                        <i class="mdi mdi-plus"></i> Nueva Raza
                    </button>
                </div>
                <h4 class="page-title">GESTIÓN DE RAZAS</h4>
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
                                <label class="form-label mb-1">Especie</label>
                                <select id="filtroEspecie" class="form-select">
                                    <option value="">Todas</option>
                                    <?php foreach ($especie_opts as $opt): ?>
                                        <option value="<?= $opt ?>"><?= ucfirst(strtolower($opt)) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div style="min-width: 150px; flex-grow: 1;">
                                <label class="form-label mb-1">Estado</label>
                                <select id="filtroEstado" class="form-select">
                                    <option value="">Todos</option>
                                    <?php foreach ($estado_opts as $opt): ?>
                                        <option value="<?= $opt ?>"><?= ucfirst(strtolower($opt)) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div style="min-width: 200px; flex-grow: 2;">
                                <label class="form-label mb-1">Buscar (Código o Nombre)</label>
                                <input type="text" id="filtroQ" class="form-control" placeholder="Buscar...">
                            </div>
                            <div style="min-width: 150px; flex-grow: 1; align-self: flex-end;">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="filtroIncluirEliminados">
                                    <label class="form-check-label" for="filtroIncluirEliminados">Incluir
                                        Eliminados</label>
                                </div>
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
                    <table id="tablaRazas" class="table table-striped table-hover align-middle" style="width:100%"
                        data-toggle="table" data-url="<?php echo BASE_URL; ?>api/razas"
                        data-response-handler="responseHandler" data-pagination="true" data-show-refresh="true"
                        data-locale="es-ES" data-sort-name="especie" data-sort-order="asc">
                        <thead>
                            <tr>
                                <th data-field="especie" data-sortable="true">Especie</th>
                                <th data-field="codigo" data-sortable="true">Código</th>
                                <th data-field="nombre" data-sortable="true">Nombre</th>
                                <th data-field="descripcion">Descripción</th>
                                <th data-field="estado" data-formatter="estadoFormatter" data-align="center"
                                    data-sortable="true">Estado</th>
                                <th data-field="raza_id" data-formatter="accionesFormatter" data-align="center">Acciones
                                </th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalRaza" tabindex="-1" aria-labelledby="modalRazaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalRazaLabel">Nueva Raza</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formRaza" data-validation="reactive">
                <div class="modal-body">
                    <input type="hidden" id="raza_id" name="raza_id">

                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label" for="especie">Especie*</label>
                            <div id="especie-container">
                                <select class="form-select" id="especie" name="especie" data-rules="noVacio"
                                    data-message-no-vacio="Debe seleccionar una especie."
                                    data-error-container="#especie-container">
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($especie_opts as $opt): ?>
                                        <option value="<?= $opt ?>"><?= ucfirst(strtolower($opt)) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="estado">Estado*</label>
                            <div id="estado-container">
                                <select class="form-select" id="estado" name="estado" data-rules="noVacio"
                                    data-message-no-vacio="Debe seleccionar un estado."
                                    data-error-container="#estado-container">
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($estado_opts as $opt): ?>
                                        <option value="<?= $opt ?>"><?= ucfirst(strtolower($opt)) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="form-label" for="nombre">Nombre*</label>
                        <input type="text" class="form-control" id="nombre" name="nombre"
                            data-rules="noVacio|longitudMaxima:120" data-message-no-vacio="El nombre es requerido."
                            data-message-longitud-maxima="El nombre no puede exceder los 120 caracteres.">
                    </div>

                    <div class="mt-3">
                        <label class="form-label" for="codigo">Código (Opcional)</label>
                        <input type="text" class="form-control" id="codigo" name="codigo" data-rules="longitudMaxima:32"
                            data-message-longitud-maxima="El código no puede exceder los 32 caracteres.">
                        <div class="form-text">Identificador único (ej. ANGUS, LR). Se guardará en mayúsculas.</div>
                    </div>

                    <div class="mt-3">
                        <label class="form-label" for="descripcion">Descripción (Opcional)</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3"
                            data-rules="longitudMaxima:1000"
                            data-message-longitud-maxima="La descripción no puede exceder los 1000 caracteres."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary" type="submit">Guardar Raza</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const baseUrl = "<?= BASE_URL ?>";
</script>
<script type="module" src="<?= BASE_URL ?>public/assets/js/modules/animales_razas_view.js"></script>