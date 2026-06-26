<?php
// Simulación de enums (cópialos de animales_view.php)
$enums = [
    'estado_parto' => ['NORMAL', 'DISTOCIA', 'MUERTE_PERINATAL', 'OTRO'],
    'sexo' => ['MACHO', 'HEMBRA'],
    'especie' => ['', 'BOVINO', 'OVINO', 'CAPRINO', 'PORCINO', 'OTRO'],
    'estado_animal' => ['ACTIVO', 'INACTIVO', 'MUERTO', 'VENDIDO'],
    'origen' => ['NACIMIENTO', 'COMPRA', 'TRASLADO', 'OTRO'],
];
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">GESTIÓN DE PARTOS Y CAMADAS</h4>
            </div>
        </div>
    </div>

    <ul class="nav nav-tabs" id="mainGestacionTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-gestantes-link" data-bs-toggle="tab" data-bs-target="#tab-gestantes"
                type="button" role="tab" aria-controls="tab-gestantes" aria-selected="true">
                <i class="mdi mdi-clock-alert-outline"></i> Hembras en Gestación
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-camadas-link" data-bs-toggle="tab" data-bs-target="#tab-camadas"
                type="button" role="tab" aria-controls="tab-camadas" aria-selected="false">
                <i class="mdi mdi-pig"></i> Camadas Activas
            </button>
        </li>
    </ul>

    <div class="tab-content" id="mainGestacionTabContent">
        <div class="tab-pane fade show active" id="tab-gestantes" role="tabpanel" aria-labelledby="tab-gestantes-link">
            <div class="card">
                <div class="card-body">
                    <p class="text-muted">Lista de hembras con preñez confirmada que aún no tienen un parto registrado.
                    </p>
                    <table id="tablaGestantes" data-toggle="table"
                        data-url="<?php echo BASE_URL; ?>api/partos/gestantes" data-response-handler="responseHandler"
                        data-pagination="true" data-search="true" data-show-refresh="true" data-locale="es-ES"
                        class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th data-field="hembra_identificador" data-sortable="true">Identificador Hembra</th>
                                <th data-field="fecha_monta" data-sortable="true" data-formatter="fDate">Fecha Monta
                                </th>
                                <th data-field="fecha_confirmacion" data-sortable="true" data-formatter="fDate">Fecha
                                    Confirmación</th>
                                <th data-field="dias_gestacion" data-sortable="true">Días Gestación</th>
                                <th data-field="periodo_id" data-formatter="gestantesAccionesFormatter"
                                    data-halign="center" data-align="center">Acciones</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="tab-camadas" role="tabpanel" aria-labelledby="tab-camadas-link">
            <div class="card">
                <div class="card-body">
                    <p class="text-muted">Lista de camadas activas (lechones pendientes por registrar o dar de baja).
                    </p>
                    <table id="tablaCamadas" data-toggle="table" data-url="<?php echo BASE_URL; ?>api/camadas"
                        data-response-handler="responseHandler" data-pagination="true" data-search="true"
                        data-show-refresh="true" data-locale="es-ES" class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th data-field="madre_identificador" data-sortable="true">Identificador Madre</th>
                                <th data-field="fecha_parto" data-sortable="true" data-formatter="fDate">Fecha Parto
                                </th>
                                <th data-field="cantidad_inicial" data-sortable="true" data-align="center">Nacidos</th>
                                <th data-field="registrados_count" data-sortable="true" data-align="center">
                                    Registrados</th>
                                <th data-field="bajas_count" data-sortable="true" data-align="center">Bajas</th>
                                <th data-field="pendientes_count" data-sortable="true"
                                    data-formatter="pendientesFormatter" data-align="center">Pendientes</th>
                                <th data-field="camada_id" data-formatter="camadasAccionesFormatter"
                                    data-halign="center" data-align="center">Acciones</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalRegistrarParto" tabindex="-1" aria-labelledby="modalRegistrarPartoLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalRegistrarPartoLabel">Registrar Parto y Transferir Madre</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formRegistrarParto" data-validation="reactive">
                <div class="modal-body">
                    <input type="hidden" name="periodo_id" id="parto_periodo_id">
                    <input type="hidden" name="animal_id" id="parto_hembra_id"> <input type="hidden"
                        name="finca_origen_id" id="parto_finca_origen_id">
                    <input type="hidden" name="aprisco_origen_id" id="parto_aprisco_origen_id">
                    <input type="hidden" name="area_origen_id" id="parto_area_origen_id">
                    <input type="hidden" name="recinto_id_origen" id="parto_recinto_id_origen">
                    <div class="row">
                        <div class="col-12">
                            <h6>1. Datos del Parto</h6>
                            <hr class="mt-0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="parto_fecha_parto" class="form-label">Fecha del Parto</label>
                            <input type="date" class="form-control" id="parto_fecha_parto" name="fecha_parto"
                                data-rules="noVacio" data-message-no-vacio="La fecha es requerida.">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="parto_estado_parto" class="form-label">Estado del Parto</label>
                            <select class="form-select" id="parto_estado_parto" name="estado_parto" data-rules="noVacio"
                                data-message-no-vacio="El estado es requerido.">
                                <?php foreach ($enums['estado_parto'] as $value): ?>
                                    <option value="<?php echo $value; ?>"><?php echo ucfirst(strtolower($value)); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="parto_crias_machos" class="form-label">Crías Machos (Vivos)</label>
                            <input type="number" class="form-control" id="parto_crias_machos" name="crias_machos"
                                value="0" data-rules="noVacio|esEnteroPositivo"
                                data-message-no-vacio="Campo requerido (mínimo 0)."
                                data-message-es-entero-positivo="Debe ser un número entero >= 0.">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="parto_crias_hembras" class="form-label">Crías Hembras (Vivas)</label>
                            <input type="number" class="form-control" id="parto_crias_hembras" name="crias_hembras"
                                value="0" data-rules="noVacio|esEnteroPositivo"
                                data-message-no-vacio="Campo requerido (mínimo 0)."
                                data-message-es-entero-positivo="Debe ser un número entero >= 0.">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="parto_peso_promedio_kg" class="form-label">Peso Promedio (Kg)</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="parto_peso_promedio_kg"
                                    name="peso_promedio_kg" placeholder="0.00" step="0.01" min="0">
                                <span class="input-group-text">kg</span>
                            </div>
                            <div class="form-text text-muted small">Opcional.</div>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label for="parto_observaciones" class="form-label">Observaciones del Parto</label>
                            <textarea class="form-control" id="parto_observaciones" name="observaciones"
                                rows="2"></textarea>
                        </div>

                    </div>

                    <div class="row mt-3">
                        <div class="col-12">
                            <h6>2. Transferir Madre a Maternidad</h6>
                            <hr class="mt-0">
                            <p class="text-muted">Seleccione la nueva ubicación (ej. Maternidad) para la madre. Esto es
                                requerido.</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Finca Destino</label>
                            <div id="parto_finca_destino_id-container">
                                <select name="finca_destino_id" class="form-select"
                                    data-error-container="#parto_finca_destino_id-container" data-rules="noVacio"
                                    data-message-no-vacio="La finca es requerida."></select>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Aprisco Destino</label>
                            <div id="parto_aprisco_destino_id-container">
                                <select name="aprisco_destino_id" class="form-select"
                                    data-error-container="#parto_aprisco_destino_id-container" data-rules="noVacio"
                                    data-message-no-vacio="El aprisco es requerido."></select>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Área Destino</label>
                            <div id="parto_area_destino_id-container">
                                <select name="area_destino_id" class="form-select"
                                    data-error-container="#parto_area_destino_id-container" data-rules="noVacio"
                                    data-message-no-vacio="El área es requerida."></select>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Recinto Destino</label>
                            <div id="parto_recinto_id_destino-container">
                                <select name="recinto_id_destino" class="form-select"
                                    data-error-container="#parto_recinto_id_destino-container" data-rules="noVacio"
                                    data-message-no-vacio="El recinto es requerido."></select>
                            </div>
                            <div class="form-text text-danger no-options-message" style="display: none;"></div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-12">
                            <h6>3. Evidencia Fotográfica (Opcional)</h6>
                            <hr class="mt-0">
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Fotografía</label>
                            <input type="file" class="form-control" id="parto_fotografia" name="fotografia"
                                accept="image/png, image/jpeg, image/webp">

                            <div class="form-text text-muted small" style="font-size: 0.7rem;">
                                Max 20MB. Tipos permitidos: JPG, PNG, WEBP.
                            </div>

                            <div id="parto-foto-preview-container" class="mt-2 border p-1 rounded d-none">
                                <img id="parto-foto-preview" src="#" alt="Vista previa" class="img-fluid rounded"
                                    style="max-height: 150px;">

                                <div class="d-flex justify-content-between mt-1">
                                    <span class="text-muted small" id="parto-foto-filename"></span>
                                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1"
                                        id="btn-eliminar-parto-foto">
                                        <i class="mdi mdi-trash-can-outline"></i> Quitar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Registrar Parto y Transferir</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalGestionarCamada" tabindex="-1" aria-labelledby="modalGestionarCamadaLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalGestionarCamadaLabel">Gestionar Camada</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="camada-loader" class="text-center">
                    <div class="spinner-border" role="status"></div>
                </div>
                <div id="camada-content" class="d-none">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Resumen de Camada</h5>
                                    <div id="gestion_foto_container" class="text-center mb-3 d-none">
                                        <img id="gestion_foto_parto" src="#" alt="Foto Parto"
                                            class="img-fluid rounded border"
                                            style="max-height: 150px; object-fit: cover; cursor: pointer;"
                                            onclick="window.open(this.src, '_blank')">
                                        <small class="d-block text-muted mt-1" style="font-size: 0.7rem;">
                                            <i class="mdi mdi-camera"></i> Evidencia del Parto
                                        </small>
                                    </div>
                                    <p><strong>Madre:</strong> <span id="gestion_madre"></span></p>
                                    <p><strong>Padre:</strong> <span id="gestion_padre"></span></p>
                                    <p><strong>Fecha Parto:</strong> <span id="gestion_fecha_parto"></span></p>
                                    <hr>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span>Nacidos (Total):</span>
                                        <span class="badge bg-dark fs-6" id="gestion_total_nacidos">0</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span>Registrados (Vivos):</span>
                                        <span class="badge bg-success fs-6" id="gestion_total_registrados">0</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span>Bajas (Muertos):</span>
                                        <span class="badge bg-danger fs-6" id="gestion_total_bajas">0</span>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded">
                                        <strong class="fs-5">Pendientes por Registrar:</strong>
                                        <span class="badge bg-primary fs-4" id="gestion_total_pendientes">0</span>
                                    </div>
                                    <hr>
                                    <div class="d-grid gap-2">
                                        <button class="btn btn-success" id="btnAbrirRegistroLechon">
                                            <i class="mdi mdi-plus-box-outline"></i> Registrar Lechón Individual
                                        </button>
                                        <button class="btn btn-danger" id="btnAbrirReportarBaja">
                                            <i class="mdi mdi-alert-octagon-outline"></i> Reportar Baja (Muerte)
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <h6>Lechones Registrados (Vivos)</h6>
                            <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                                <table class="table table-sm table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Identificador</th>
                                            <th>Sexo</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tablaLechonesRegistrados">
                                    </tbody>
                                </table>
                            </div>

                            <h6 class="mt-4">Bajas Reportadas (Muertos)</h6>
                            <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                                <table class="table table-sm table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Cantidad</th>
                                            <th>Causa</th>
                                            <th>Acta</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tablaBajasReportadas">
                                    </tbody>
                                </table>
                            </div>
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

<div class="modal fade" id="modalAnimal" tabindex="-1" aria-labelledby="modalAnimalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAnimalLabel">Registrar Nuevo Lechón</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formAnimal" data-validation="reactive" enctype="multipart/form-data">
                <div class="modal-body">

                    <div class="modal-body-content">
                        <div class="modal-body">
                            <input type="hidden" name="periodo_id" id="parto_periodo_id">
                            <input type="hidden" name="animal_id" id="parto_hembra_id"> <input type="hidden"
                                name="finca_origen_id" id="parto_finca_origen_id">
                            <input type="hidden" name="aprisco_origen_id" id="parto_aprisco_origen_id">
                            <input type="hidden" name="area_origen_id" id="parto_area_origen_id">
                            <input type="hidden" name="recinto_id_origen" id="parto_recinto_id_origen">

                        </div>
                    </div>
                    <div class="modal-body-loader text-center p-5" style="display: none;">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Cargando ubicación actual...</span>
                        </div>
                        <p class="mt-2">Cargando ubicación actual de la madre...</p>
                    </div>

                    <input type="hidden" id="animal_id" name="animal_id">
                    <input type="hidden" id="animal_camada_id" name="camada_id">
                    <div class="alert alert-info">
                        La Madre, Padre, Fecha de Nacimiento y Origen son asignados automáticamente por la camada.
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="identificador" class="form-label">Identificador</label>
                            <input type="text" class="form-control" id="identificador" name="identificador"
                                data-rules="noVacio|longitudMaxima:50"
                                data-message-no-vacio="El identificador es requerido."
                                data-validate-duplicate-url="api/animales/check_identificador">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="sexo" class="form-label">Sexo</label>
                            <select class="form-select" id="sexo" name="sexo" data-rules="noVacio"
                                data-message-no-vacio="Debe seleccionar el sexo.">
                                <?php foreach ($enums['sexo'] as $value): ?>
                                    <option value="<?php echo $value; ?>"><?php echo ucfirst(strtolower($value)); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                            <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento"
                                readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="origen" class="form-label">Origen</label>
                            <input type="text" class="form-control" id="origen" name="origen" value="NACIMIENTO"
                                readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="madre_id" class="form-label">Madre</label>
                            <select class="form-select" id="madre_id" name="madre_id" style="width: 100%;"
                                disabled></select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="padre_id" class="form-label">Padre</label>
                            <select class="form-select" id="padre_id" name="padre_id" style="width: 100%;"
                                disabled></select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="especie" class="form-label">Especie</label>
                            <input type="text" class="form-control" id="especie" name="especie" value="PORCINO"
                                readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="raza_id" class="form-label">Raza</label>
                            <select class="form-select" id="raza_id" name="raza_id" style="width: 100%;"></select>
                            <small>
                                <span class="text-danger">*</span> Por defecto se selecciona la raza de la madre del
                                animal.


                            </small>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="fotografia" class="form-label">Fotografía (Opcional)</label>
                            <input type="file" class="form-control" id="fotografia" name="fotografia"
                                accept="image/png, image/jpeg, image/webp">
                        </div>
                        <div class="col-md-6 text-center">
                            <img id="fotografia-preview" src="https://placehold.co/200x200?text=Vista+Previa"
                                class="img-fluid rounded mt-2" style="max-height: 150px;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                        id="btnCancelarAnimal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Lechón</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalReportarBaja" tabindex="-1" aria-labelledby="modalReportarBajaLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalReportarBajaLabel">Reportar Baja de Camada</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formReportarBaja" data-validation="reactive" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="baja_fecha_baja" class="form-label">Fecha de la Baja</label>
                        <input type="date" class="form-control" id="baja_fecha_baja" name="fecha_baja"
                            data-rules="noVacio" data-message-no-vacio="La fecha es requerida.">
                    </div>
                    <div class="mb-3">
                        <label for="baja_cantidad" class="form-label">Cantidad</label>
                        <input type="number" class="form-control" id="baja_cantidad" name="cantidad" value="1"
                            data-rules="noVacio|esEnteroPositivo" data-message-no-vacio="La cantidad es requerida."
                            data-message-es-entero-positivo="Debe ser 1 o más.">
                    </div>
                    <div class="mb-3">
                        <label for="baja_causa_deceso" class="form-label">Causa del Deceso</label>
                        <select class="form-select" id="baja_causa_deceso" name="causa_deceso" data-rules="noVacio"
                            data-message-no-vacio="Debe seleccionar una causa.">
                            <option value="">Seleccione una causa...</option>
                            <option value="APLASTAMIENTO">Aplastamiento</option>
                            <option value="INANICION">Inanición (Hambre)</option>
                            <option value="HIPOTERMIA">Hipotermia (Frío)</option>
                            <option value="ENFERMEDAD">Enfermedad</option>
                            <option value="BAJO_PESO_NACER">Bajo Peso al Nacer</option>
                            <option value="MALFORMACION">Malformación</option>
                            <option value="OTRA">Otra</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="baja_documento_acta" class="form-label">Adjuntar Acta (Opcional)</label>
                        <input type="file" class="form-control" id="baja_documento_acta" name="documento_acta"
                            accept=".pdf,.doc,.docx,.jpg,.png">
                    </div>
                    <div class="mb-3">
                        <label for="baja_observaciones" class="form-label">Observaciones</label>
                        <textarea class="form-control" id="baja_observaciones" name="observaciones" rows="2"></textarea>
                    </div>



                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                        id="btnCancelarBaja">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Confirmar Baja</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const baseUrl = "<?php echo BASE_URL; ?>";
</script>
<script type="module" src="<?= BASE_URL ?>public/assets/js/modules/gestacion_view.js"></script>