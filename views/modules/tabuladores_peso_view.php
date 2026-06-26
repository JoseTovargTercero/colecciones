<?php
// Especies para el filtro de razas
$especie_opts = ['BOVINO', 'OVINO', 'CAPRINO', 'PORCINO', 'OTRO'];
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">GESTIÓN DE TABULADORES DE PESO</h4>
            </div>
            <p class="text-muted">Seleccione una raza para administrar sus rangos de peso y edad.</p>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex flex-wrap gap-2" style="width: 100%;">
                        <div style="min-width: 250px; flex-grow: 1;">
                            <label class="form-label mb-1">Filtrar por Especie</label>
                            <select id="filtroEspecie" class="form-select">
                                <option value="">Todas las especies</option>
                                <?php foreach ($especie_opts as $opt): ?>
                                    <option value="<?= $opt ?>"><?= ucfirst(strtolower($opt)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <table id="tablaRazas" class="table table-striped table-hover align-middle"
                        style="width:100%" data-toggle="table"
                        data-url="<?php echo BASE_URL; ?>api/razas"
                        data-response-handler="responseHandler" data-pagination="true" data-search="true"
                        data-show-refresh="true" data-locale="es-ES" data-sort-name="especie"
                        data-sort-order="asc">
                        <thead>
                            <tr>
                                <th data-field="especie" data-sortable="true">Especie</th>
                                <th data-field="codigo" data-sortable="true">Código</th>
                                <th data-field="nombre" data-sortable="true">Nombre de Raza</th>
                                <th data-field="estado" data-formatter="estadoRazaFormatter" data-align="center"
                                    data-sortable="true">Estado</th>
                                <th data-field="raza_id" data-formatter="gestionarFormatter"
                                    data-align="center">Acciones</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalGestionRangos" tabindex="-1" aria-labelledby="modalGestionLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalGestionLabel">Gestionar Rangos para: </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                
                <h6 id="formRangoTitulo">Añadir Nuevo Rango</h6>
                <form id="formRango" data-validation="reactive" class="card card-body mb-3">
                    <input type="hidden" id="tab_peso_id" name="tab_peso_id">
                    <div class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label" for="edad_min_dias">Edad Mín. (días)*</label>
                            <input type="number" class="form-control" id="edad_min_dias" name="edad_min_dias"
                                data-rules="noVacio|esEnteroPositivo" data-message-no-vacio="Requerido."
                                data-message-es-entero-positivo="Debe ser >= 0.">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="edad_max_dias">Edad Máx. (días)*</label>
                            <input type="number" class="form-control" id="edad_max_dias" name="edad_max_dias"
                                data-rules="noVacio|esEnteroPositivo" data-message-no-vacio="Requerido."
                                data-message-es-entero-positivo="Debe ser >= 0.">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="peso_ideal">P. Ideal*</label>
                            <input type="number" step="0.01" class="form-control" id="peso_ideal" name="peso_ideal"
                                data-rules="noVacio|esNumeroPositivo" data-message-no-vacio="Req."
                                data-message-es-numero-positivo="> 0.">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="margen_min">M. Mín.*</label>
                            <input type="number" step="0.01" class="form-control" id="margen_min" name="margen_min"
                                data-rules="noVacio|esNumeroPositivo" data-message-no-vacio="Req."
                                data-message-es-numero-positivo="> 0.">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="margen_max">M. Máx.*</label>
                            <input type="number" step="0.01" class="form-control" id="margen_max" name="margen_max"
                                data-rules="noVacio|esNumeroPositivo" data-message-no-vacio="Req."
                                data-message-es-numero-positivo="> 0.">
                        </div>
                    </div>
                    <div class="d-flex justify-content-end gap-2 mt-3">
                         <button type="button" class="btn btn-sm btn-outline-secondary" id="btnCancelarEdicionRango" style="display: none;">Cancelar</button>
                         <button class="btn btn-sm btn-primary" type="submit" id="btnSubmitRango">
                            <i class="mdi mdi-plus"></i> Añadir Rango
                         </button>
                    </div>
                </form>

                <h6 class="mt-4">Rangos Registrados</h6>
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-sm table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Rango de Edad (días)</th>
                                <th>Peso Ideal</th>
                                <th>Rango Aceptable</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tablaRangosBody">
                            </tbody>
                    </table>
                </div>
                 <div id="rangos-loader" class="text-center p-3" style="display: none;">
                    <div class="spinner-border" role="status"></div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
    const baseUrl = "<?= BASE_URL ?>";
</script>
<script type="module" src="<?= BASE_URL ?>public/assets/js/modules/tabuladores_peso_view.js"></script>