<?php
// Simula la obtención de valores para los ENUMs
$enums = [
    'sexo' => ['MACHO', 'HEMBRA'],
    'especie' => ['', 'BOVINO', 'OVINO', 'CAPRINO', 'PORCINO', 'OTRO'],
    'estado_animal' => ['ACTIVO', 'INACTIVO', 'MUERTO', 'VENDIDO'],
    'etapa_productiva' => ['TERNERO', 'LEVANTE', 'CEBA', 'REPRODUCTOR', 'LACTANTE', 'SECA', 'GESTANTE', 'OTRO'],
    'categoria' => ['CRIA', 'MADRE', 'PADRE', 'ENGORDE', 'REEMPLAZO', 'OTRO'],
    'origen' => ['NACIMIENTO', 'COMPRA', 'TRASLADO', 'OTRO'],
    'tipo_movimiento' => ['INGRESO', 'EGRESO', 'TRASLADO', 'VENTA', 'COMPRA', 'NACIMIENTO', 'MUERTE', 'OTRO'],
    'motivo_movimiento' => ['TRASLADO', 'INGRESO', 'EGRESO', 'AISLAMIENTO', 'VENTA', 'OTRO'],
    'estado_movimiento' => ['REGISTRADO', 'ANULADO'],
    'tipo_evento_salud' => ['ENFERMEDAD', 'VACUNACION', 'DESPARASITACION', 'REVISION', 'TRATAMIENTO', 'OTRO'],
    'severidad_salud' => ['LEVE', 'MODERADA', 'GRAVE', 'NO_APLICA'],
    'estado_salud' => ['ABIERTO', 'SEGUIMIENTO', 'CERRADO'],
    'motivo_ubicacion' => ['TRASLADO', 'INGRESO', 'EGRESO', 'AISLAMIENTO', 'VENTA', 'OTRO'],
];

$tipos_incidencia = [
    'RECHAZO_CRIAS',
    'FUGA',
    'AGRESIVIDAD',
    'RIÑA', // Riña o Pelea
    'OTRA'
];
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <button type="button" class="btn btn-primary" id="btnNuevoAnimal">
                        <i class="mdi mdi-plus"></i> Registrar Animal
                    </button>
                </div>
                <h4 class="page-title">GESTIÓN DE ANIMALES</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <table id="tablaAnimales" data-toggle="table" data-url="<?php echo BASE_URL; ?>api/animales"
                        data-response-handler="responseHandler" data-pagination="true" data-search="true"
                        data-show-refresh="true" data-show-columns="true" data-locale="es-ES"
                        class="table table-striped table-hover" style="width:100%">
                        <thead>
                            <tr>
                                <th data-field="identificador" data-sortable="true">Identificador</th>
                                <th data-field="sexo" data-sortable="true">Sexo</th>
                                <th data-field="especie" data-sortable="true">Especie</th>
                                <th data-field="raza_nombre" data-sortable="true">Raza</th>
                                <th data-field="ultimo_peso_kg" data-sortable="true" data-formatter="pesoFormatter">
                                    Último Peso</th>
                                <th data-field="ubicacion_actual" data-sortable="true"
                                    data-formatter="ubicacionFormatter">Ubicación Actual</th>
                                <th data-field="animal_id" data-formatter="accionesFormatter" data-halign="center"
                                    data-align="center">Acciones</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAnimal" tabindex="-1" aria-labelledby="modalAnimalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAnimalLabel">Registrar Nuevo Animal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formAnimal" data-validation="reactive" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" id="animal_id" name="animal_id">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="identificador" class="form-label">Identificador</label>
                            <input type="text" class="form-control" id="identificador" name="identificador"
                                data-rules="noVacio|longitudMaxima:50"
                                data-message-no-vacio="El identificador es requerido."
                                data-message-longitud-maxima="El identificador no puede exceder 50 caracteres."
                                data-validate-duplicate-url="api/animales/check_identificador"
                                data-record-id-selector="#animal_id"
                                data-message-duplicado="Este identificador ya está en uso.">
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
                            <label for="especie" class="form-label">Especie</label>
                            <select class="form-select" id="especie" name="especie" data-rules="noVacio"
                                data-message-no-vacio="Debe seleccionar la especie.">
                                <?php foreach ($enums['especie'] as $value): ?>
                                    <!-- Si valor es vacío, colocar texto "seleccionar... -->
                                    <option value="<?php echo $value; ?>">
                                        <?php echo $value === '' ? 'Seleccionar...' : ucfirst(strtolower($value)); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>


                        <div class="col-md-6 mb-3">
                            <label for="raza_id" class="form-label">Raza</label>
                            <div id="raza_id-container">
                                <!-- data-rules="noVacio" data-message-no-vacio="Debe seleccionar la raza." -->
                                <select class="form-select" id="raza_id" name="raza_id" style="width: 100%;"
                                    data-error-container="#raza_id-container">
                                </select>
                            </div>
                            <div class="form-text">El selector se filtrará según la especie seleccionada.</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="raza" class="form-label">Raza no registrada (Opcional)</label>
                            <input type="text" class="form-control" id="raza" name="raza"
                                data-rules="longitudMaxima:100"
                                data-message-longitud-maxima="La raza no puede exceder 100 caracteres.">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                            <input type="date" data-rules="noVacio"
                                data-message-no-vacio="La fecha de nacimiento es requerida." class="form-control"
                                id="fecha_nacimiento" name="fecha_nacimiento">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="estado" class="form-label">Estado del Animal</label>
                            <select class="form-select" id="estado" name="estado" data-rules="noVacio"
                                data-message-no-vacio="Debe seleccionar el estado.">
                                <?php foreach ($enums['estado_animal'] as $value): ?>
                                    <option value="<?php echo $value; ?>"><?php echo ucfirst(strtolower($value)); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="origen" class="form-label">Origen</label>
                            <select class="form-select" id="origen" name="origen" data-rules="noVacio"
                                data-message-no-vacio="Debe seleccionar el origen.">
                                <?php foreach ($enums['origen'] as $value): ?>
                                    <option value="<?php echo $value; ?>"><?php echo ucfirst(strtolower($value)); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="madre_id" class="form-label">Madre (Opcional)</label>
                            <div id="madre_id-container">
                                <select class="form-select" id="madre_id" name="madre_id" style="width: 100%;"
                                    data-error-container="#madre_id-container">
                                    <option value="">Seleccione una opción</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="padre_id" class="form-label">Padre (Opcional)</label>
                            <div id="padre_id-container">
                                <select class="form-select" id="padre_id" name="padre_id" style="width: 100%;"
                                    data-error-container="#padre_id-container">
                                    <option value="">Seleccione una opción</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="fotografia" class="form-label">Fotografía (Opcional)</label>
                            <input type="file" class="form-control" id="fotografia" name="fotografia"
                                accept="image/png, image/jpeg, image/webp"
                                data-rules="esTipoArchivo:image/png,image/jpeg,image/webp|tamanoMaximoArchivo:5"
                                data-message-es-tipo-archivo="Solo se permiten imágenes (png, jpg, webp)."
                                data-message-tamano-maximo-archivo="La imagen no puede exceder los 5 MB.">
                        </div>
                        <div class="col-md-12 text-center">
                            <img id="fotografia-preview" src="https://placehold.co/200x200?text=Vista+Previa"
                                class="img-fluid rounded mt-2" style="max-height: 200px;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>



<div class="modal fade" id="modalDetallesAnimal" tabindex="-1" aria-labelledby="modalDetallesAnimalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDetallesAnimalLabel">Ficha individual de animal: <span
                        id="detalle_identificador_titulo"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-custom-gray">
                <div id="detalles-loader" class="text-center">
                    <div class="spinner-border" role="status"></div>
                </div>
                <div id="detalles-content" class="d-none">
                    <!-- Nav tabs -->
                    <ul class="nav nav-tabs" id="animalDetailsTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#info"
                                type="button" role="tab" aria-controls="info" aria-selected="true">Información
                                General</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="pesos-tab" data-bs-toggle="tab" data-bs-target="#pesos"
                                type="button" role="tab" aria-controls="pesos" aria-selected="false">Pesos</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="salud-tab" data-bs-toggle="tab" data-bs-target="#salud"
                                type="button" role="tab" aria-controls="salud" aria-selected="false">Salud</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="movimientos-tab" data-bs-toggle="tab"
                                data-bs-target="#movimientos" type="button" role="tab" aria-controls="movimientos"
                                aria-selected="false">Movimientos</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="ubicaciones-tab" data-bs-toggle="tab"
                                data-bs-target="#ubicaciones" type="button" role="tab" aria-controls="ubicaciones"
                                aria-selected="false">Ubicaciones</button>
                        </li>

                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="incidencias-tab" data-bs-toggle="tab"
                                data-bs-target="#incidencias" type="button" role="tab" aria-controls="incidencias"
                                aria-selected="false">Incidencias</button>
                        </li>
                    </ul>

                    <!-- Tab panes -->
                    <div class="tab-content mt-3" id="animalDetailsTabContent">
                        <div class="tab-pane fade show active" id="info" role="tabpanel" aria-labelledby="info-tab">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="section-title">Fotografía</div>
                                            <img id="detalle_fotografia"
                                                src="https://placehold.co/300x300?text=Sin+Foto"
                                                class="img-fluid rounded w-100">

                                            <div class="mt-3">
                                                <i class="mdi mdi-map-marker"></i> Ubicación Actual:
                                                <b>
                                                    <span id="detalle_ubicacion_actual"></span>
                                                </b>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="card">
                                        <div class="card-body">
                                            <div id="badge_alerta_riesgo" class="alert alert-danger d-none p-2"
                                                role="alert" style="font-size: 0.9rem;">
                                                <i class="mdi mdi-alert-octagon-outline"></i> <strong>Alerta de
                                                    Riesgo:</strong>
                                                Este animal tiene antecedentes de riesgo (aplastamiento). Revise la
                                                pestaña
                                                de Incidencias.
                                            </div>
                                            <div id="badge_alerta_peso" class="alert d-none p-2" role="alert"
                                                style="font-size: 0.9rem;">
                                                <i class="mdi mdi-scale-balance"></i>
                                                <strong>Estado de Peso:</strong>
                                                <span id="peso_status_mensaje"></span>
                                            </div>

                                            <div class="row">
                                                <div class="col-lg-4">
                                                    <div>
                                                        <p class="bold form-label">Identificador</p>
                                                        <p class="form-control" id="detalle_identificador">
                                                    </div>
                                                </div>
                                                <div class="col-lg-4">
                                                    <div>
                                                        <p class="bold form-label">Sexo</p>
                                                        <p class="form-control" id="detalle_sexo">
                                                    </div>
                                                </div>
                                                <div class="col-lg-4">
                                                    <div>
                                                        <p class="bold form-label">Especie</p>
                                                        <p class="form-control" id="detalle_especie">
                                                    </div>
                                                </div>
                                                <div class="col-lg-4">
                                                    <div>
                                                        <p class="bold form-label">Raza</p>
                                                        <p class="form-control" id="detalle_raza">
                                                    </div>
                                                </div>
                                                <div class="col-lg-4">
                                                    <div>
                                                        <p class="bold form-label">Fecha de Nacimiento / Edad</p>
                                                        <p class="form-control" id="detalle_fecha_nacimiento">
                                                    </div>
                                                </div>
                                                <div class="col-lg-4">
                                                    <div>
                                                        <p class="bold form-label">Ultimo Peso</p>
                                                        <p class="form-control" id="detalle_ultimo_peso">
                                                    </div>
                                                </div>
                                                <div class="col-lg-4">
                                                    <div>
                                                        <p class="bold form-label">Estado</p>
                                                        <p class="form-control" id="detalle_estado">
                                                    </div>
                                                </div>
                                                <div class="col-lg-4">
                                                    <div>
                                                        <p class="bold form-label">Origen</p>
                                                        <p class="form-control" id="detalle_origen">
                                                    </div>
                                                </div>
                                                <div class="col-lg-4">
                                                    <div>
                                                        <p class="bold form-label">Fecha de registro</p>
                                                        <p class="form-control" id="detalle_created_at">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="section-title">Curva de crecimiento</div>
                                            <div dir="ltr">
                                                <div id="peso-chart" class="apex-charts mt-3"
                                                    data-colors="#727cf5,#0acf97"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="pesos" role="tabpanel" aria-labelledby="pesos-tab">
                            <div class="card">
                                <div class="card-body">
                                    <button class="btn btn-success btn-sm mb-2" id="btnRegistrarPeso"><i
                                            class="mdi mdi-weight-kilogram"></i> Registrar Peso</button>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Fecha</th>
                                                    <th>Peso (kg)</th>
                                                    <th>Método</th>
                                                    <th>Observaciones</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tablaDetallesPesos"></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="salud" role="tabpanel" aria-labelledby="salud-tab">
                            <div class="card">
                                <div class="card-body">
                                    <button class="btn btn-success btn-sm mb-2" id="btnRegistrarSalud"><i
                                            class="mdi mdi-medical-bag"></i> Registrar Evento de Salud</button>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Fecha</th>
                                                    <th>Tipo</th>
                                                    <th>Diagnóstico</th>
                                                    <th>Estado</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tablaDetallesSalud"></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="movimientos" role="tabpanel" aria-labelledby="movimientos-tab">
                            <div class="card">
                                <div class="card-body">
                                    <button class="btn btn-success btn-sm mb-2" id="btnRegistrarMovimiento"><i
                                            class="mdi mdi-swap-horizontal"></i> Registrar Movimiento</button>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Fecha</th>
                                                    <th>Tipo</th>
                                                    <th>Motivo</th>
                                                    <th>Origen</th>
                                                    <th>Destino</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tablaDetallesMovimientos"></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="ubicaciones" role="tabpanel" aria-labelledby="ubicaciones-tab">
                            <div class="card">
                                <div class="card-body">
                                    <button class="btn btn-success btn-sm mb-2" id="btnRegistrarUbicacion"><i
                                            class="mdi mdi-map-marker"></i> Registrar Ubicación</button>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Desde</th>
                                                    <th>Hasta</th>
                                                    <th>Ubicación</th>
                                                    <th>Motivo</th>
                                                    <th>Estado</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tablaDetallesUbicaciones"></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="incidencias" role="tabpanel" aria-labelledby="incidencias-tab">
                            <div class="card">
                                <div class="card-body">
                                    <button class="btn btn-success btn-sm mb-2" id="btnRegistrarIncidencia"><i
                                            class="mdi mdi-alert-plus"></i> Registrar Incidencia</button>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Fecha Evento</th>
                                                    <th>Tipo</th>
                                                    <th>Descripción</th>
                                                    <th>Responsable</th>
                                                    <th>Área</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tablaDetallesIncidencias"></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalRegistroPeso" tabindex="-1" aria-labelledby="modalRegistroPesoLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalRegistroPesoLabel">Registrar Peso</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formRegistroPeso" data-validation="reactive">
                <div class="modal-body">
                    <input type="hidden" name="animal_id" id="peso_animal_id">

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
                                data-message-es-numero-positivo="El peso debe ser un número positivo.">
                        </div>
                        <div class="col-4">
                            <label for="unidad" class="form-label">Unidad</label>
                            <select class="form-select" id="unidad" name="unidad" data-rules="noVacio"
                                data-message-no-vacio="Seleccione unidad.">
                                <option value="KG">KG</option>
                                <option value="LB">LB</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="metodo" class="form-label">Método</label>
                        <input type="text" class="form-control" id="metodo" name="metodo"
                            data-rules="longitudMaxima:100" data-message-longitud-maxima="Máximo 100 caracteres.">
                    </div>
                    <div class="mb-3">
                        <label for="observaciones_peso" class="form-label">Observaciones</label>
                        <textarea class="form-control" id="observaciones_peso" name="observaciones"
                            data-rules="longitudMaxima:500"
                            data-message-longitud-maxima="Máximo 500 caracteres."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                        id="btnCancelarRegistroPeso">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Peso</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalRegistroSalud" tabindex="-1" aria-labelledby="modalRegistroSaludLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalRegistroSaludLabel">Registrar Evento de Salud</h5><button type="button"
                    class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formRegistroSalud" data-validation="reactive">
                <div class="modal-body">
                    <input type="hidden" name="animal_id" id="salud_animal_id">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="fecha_evento" class="form-label">Fecha del Evento</label>
                            <input type="date" class="form-control" id="fecha_evento" name="fecha_evento"
                                data-rules="noVacio" data-message-no-vacio="La fecha es requerida.">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="tipo_evento" class="form-label">Tipo de Evento</label>
                            <select class="form-select" id="tipo_evento" name="tipo_evento" data-rules="noVacio"
                                data-message-no-vacio="El tipo de evento es requerido.">
                                <?php foreach ($enums['tipo_evento_salud'] as $value): ?>
                                    <option value="<?php echo $value; ?>"><?php echo ucfirst(strtolower($value)); ?>
                                    </option><?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="diagnostico" class="form-label">Diagnóstico</label>
                            <input type="text" class="form-control" id="diagnostico" name="diagnostico"
                                data-rules="longitudMaxima:255" data-message-longitud-maxima="Máximo 255 caracteres.">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="severidad" class="form-label">Severidad</label>
                            <select class="form-select" id="severidad" name="severidad">
                                <?php foreach ($enums['severidad_salud'] as $value): ?>
                                    <option value="<?php echo $value; ?>">
                                        <?php echo str_replace('_', ' ', ucfirst(strtolower($value))); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label for="tratamiento" class="form-label">Tratamiento</label>
                            <textarea class="form-control" id="tratamiento" name="tratamiento" rows="2"
                                data-rules="longitudMaxima:1000"
                                data-message-longitud-maxima="Máximo 1000 caracteres."></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="medicamento" class="form-label">Medicamento</label>
                            <input type="text" class="form-control" id="medicamento" name="medicamento"
                                data-rules="longitudMaxima:255" data-message-longitud-maxima="Máximo 255 caracteres.">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="dosis" class="form-label">Dosis</label>
                            <input type="text" class="form-control" id="dosis" name="dosis"
                                data-rules="longitudMaxima:50" data-message-longitud-maxima="Máximo 50 caracteres.">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="via_administracion" class="form-label">Vía Admin.</label>
                            <input type="text" class="form-control" id="via_administracion" name="via_administracion"
                                data-rules="longitudMaxima:100" data-message-longitud-maxima="Máximo 100 caracteres.">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="costo" class="form-label">Costo</label>
                            <input type="number" step="0.01" class="form-control" id="costo" name="costo"
                                placeholder="0.00" data-rules="longitudMaxima:20"
                                data-message-longitud-maxima="Valor inválido.">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="estado_salud" class="form-label">Estado del Caso</label>
                            <select class="form-select" id="estado_salud" name="estado" data-rules="noVacio"
                                data-message-no-vacio="El estado es requerido.">
                                <?php foreach ($enums['estado_salud'] as $value): ?>
                                    <option value="<?php echo $value; ?>"><?php echo ucfirst(strtolower($value)); ?>
                                    </option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="proxima_revision" class="form-label">Próxima Revisión</label>
                            <input type="date" class="form-control" id="proxima_revision" name="proxima_revision">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="responsable" class="form-label">Responsable</label>
                            <input type="text" class="form-control" id="responsable" name="responsable"
                                data-rules="longitudMaxima:150" data-message-longitud-maxima="Máximo 150 caracteres.">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label for="observaciones_salud" class="form-label">Observaciones</label>
                            <textarea class="form-control" id="observaciones_salud" name="observaciones" rows="2"
                                data-rules="longitudMaxima:1000"
                                data-message-longitud-maxima="Máximo 1000 caracteres."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                        id="btnCancelarRegistroSalud">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Evento</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Detalles del Evento de Salud -->
<div class="modal fade" id="modalDetallesSalud" tabindex="-1" aria-labelledby="modalDetallesSaludLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDetallesSaludLabel">Detalles de Evento de Salud</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong>Fecha del Evento:</strong> <span id="detalle_salud_fecha"></span></p>
                <p><strong>Tipo de Evento:</strong> <span id="detalle_salud_tipo"></span></p>
                <p><strong>Diagnóstico:</strong> <span id="detalle_salud_diagnostico"></span></p>
                <p><strong>Severidad:</strong> <span id="detalle_salud_severidad"></span></p>
                <p><strong>Tratamiento:</strong> <span id="detalle_salud_tratamiento"></span></p>
                <p><strong>Medicamento:</strong> <span id="detalle_salud_medicamento"></span></p>
                <p><strong>Dosis:</strong> <span id="detalle_salud_dosis"></span></p>
                <p><strong>Vía de Administración:</strong> <span id="detalle_salud_via"></span></p>
                <p><strong>Costo:</strong> <span id="detalle_salud_costo"></span></p>
                <p><strong>Estado:</strong> <span id="detalle_salud_estado"></span></p>
                <p><strong>Próxima Revisión:</strong> <span id="detalle_salud_revision"></span></p>
                <p><strong>Responsable:</strong> <span id="detalle_salud_responsable"></span></p>
                <p><strong>Observaciones:</strong> <span id="detalle_salud_observaciones"></span></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                    id="btnCerrarDetalleSalud">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalRegistroMovimiento" tabindex="-1" role="dialog"
    aria-labelledby="modalRegistroMovimientoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalRegistroMovimientoLabel">Registrar Movimiento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formRegistroMovimiento" data-validation="reactive">
                <div class="modal-body">
                    <input type="hidden" name="animal_id" id="movimiento_animal_id">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="fecha_mov" class="form-label">Fecha Movimiento</label>
                            <input type="date" class="form-control" id="fecha_mov" name="fecha_mov" data-rules="noVacio"
                                data-message-no-vacio="La fecha es requerida.">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="tipo_movimiento" class="form-label">Tipo Movimiento</label>
                            <select class="form-select" id="tipo_movimiento" name="tipo_movimiento" data-rules="noVacio"
                                data-message-no-vacio="El tipo es requerido.">
                                <?php foreach ($enums['tipo_movimiento'] as $value): ?>
                                    <option value="<?php echo $value; ?>"><?php echo ucfirst(strtolower($value)); ?>
                                    </option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="motivo_movimiento" class="form-label">Motivo</label>
                            <select class="form-select" id="motivo_movimiento" name="motivo" data-rules="noVacio"
                                data-message-no-vacio="El motivo es requerido.">
                                <?php foreach ($enums['motivo_movimiento'] as $value): ?>
                                    <option value="<?php echo $value; ?>"><?php echo ucfirst(strtolower($value)); ?>
                                    </option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="costo_movimiento" class="form-label">Costo (Opcional)</label>
                            <input type="number" step="0.01" class="form-control" id="costo_movimiento" name="costo"
                                data-rules="longitudMaxima:20" data-message-longitud-maxima="Valor inválido.">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Origen</h6>
                            <p><em>(Requerido para Egresos, Ventas, Muertes, Traslados)</em></p>
                            <div class="mb-3">
                                <label class="form-label">Finca Origen</label>
                                <div id="finca_origen_id-container">
                                    <select name="finca_origen_id" class="form-select"
                                        data-error-container="#finca_origen_id-container"></select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Aprisco Origen</label>
                                <div id="aprisco_origen_id-container">
                                    <select name="aprisco_origen_id" class="form-select"
                                        data-error-container="#aprisco_origen_id-container"></select>
                                </div>
                                <div class="form-text text-danger no-options-message" style="display: none;"></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Área Origen</label>
                                <div id="area_origen_id-container">
                                    <select name="area_origen_id" class="form-select"
                                        data-error-container="#area_origen_id-container"></select>
                                </div>
                                <div class="form-text text-danger no-options-message" style="display: none;"></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Recinto Origen</label>
                                <div id="recinto_id_origen-container">
                                    <select name="recinto_id_origen" class="form-select"
                                        data-error-container="#recinto_id_origen-container"></select>
                                </div>
                                <div class="form-text text-danger no-options-message" style="display: none;"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6>Destino</h6>
                            <p><em>(Requerido para Ingresos, Compras, Nacimientos, Traslados)</em></p>
                            <div class="mb-3">
                                <label class="form-label">Finca Destino</label>
                                <div id="finca_destino_id-container">
                                    <select name="finca_destino_id" class="form-select"
                                        data-error-container="#finca_destino_id-container"></select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Aprisco Destino</label>
                                <div id="aprisco_destino_id-container">
                                    <select name="aprisco_destino_id" class="form-select"
                                        data-error-container="#aprisco_destino_id-container"></select>
                                </div>
                                <div class="form-text text-danger no-options-message" style="display: none;"></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Área Destino</label>
                                <div id="area_destino_id-container">
                                    <select name="area_destino_id" class="form-select"
                                        data-error-container="#area_destino_id-container"></select>
                                </div>
                                <div class="form-text text-danger no-options-message" style="display: none;"></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Recinto Destino</label>
                                <div id="recinto_id_destino-container">
                                    <select name="recinto_id_destino" class="form-select"
                                        data-error-container="#recinto_id_destino-container"></select>
                                </div>
                                <div class="form-text text-danger no-options-message" style="display: none;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="observaciones_movimiento" class="form-label">Observaciones</label>
                        <textarea class="form-control" id="observaciones_movimiento" name="observaciones"
                            data-rules="longitudMaxima:1000"
                            data-message-longitud-maxima="Máximo 1000 caracteres."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                        id="btnCancelarRegistroMovimiento">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Movimiento</button>
                </div>
            </form>
        </div>
    </div>
</div>


<div class="modal fade" id="modalRegistroUbicacion" tabindex="-1" role="dialog"
    aria-labelledby="modalRegistroUbicacionLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalRegistroUbicacionLabel">Registrar Nueva Ubicación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formRegistroUbicacion" data-validation="reactive">
                <div class="modal-body">
                    <input type="hidden" name="animal_id" id="ubicacion_animal_id">
                    <p class="alert alert-info">Al registrar una nueva ubicación activa, la anterior (si existe) se
                        cerrará automáticamente.</p>

                    <div class="mb-3">
                        <label for="fecha_desde_ubicacion" class="form-label">Fecha Desde</label>
                        <input type="date" class="form-control" id="fecha_desde_ubicacion" name="fecha_desde"
                            data-rules="noVacio" data-message-no-vacio="La fecha es requerida.">
                    </div>
                    <div class="mb-3">
                        <label for="fecha_hasta_ubicacion" class="form-label">Fecha Hasta (Opcional)</label>
                        <input type="date" class="form-control" id="fecha_hasta_ubicacion" name="fecha_hasta">
                    </div>
                    <div class="mb-3">
                        <label for="motivo_ubicacion" class="form-label">Motivo</label>
                        <select class="form-select" id="motivo_ubicacion" name="motivo" data-rules="noVacio"
                            data-message-no-vacio="El motivo es requerido.">
                            <?php foreach ($enums['motivo_ubicacion'] as $value): ?>
                                <option value="<?php echo $value; ?>"><?php echo ucfirst(strtolower($value)); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Finca</label>
                        <div id="finca_id-container">
                            <select name="finca_id" id="finca_id" class="form-select"
                                data-error-container="#finca_id-container" data-rules="noVacio"
                                data-message-no-vacio="Debe seleccionar una finca."></select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Aprisco</label>
                        <div id="aprisco_id-container">
                            <select name="aprisco_id" id="aprisco_id" class="form-select"
                                data-error-container="#aprisco_id-container" data-rules="noVacio"
                                data-message-no-vacio="Debe seleccionar un aprisco."></select>
                        </div>
                        <div class="form-text text-danger no-options-message" style="display: none;"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Área</label>
                        <div id="area_id-container">
                            <select name="area_id" id="area_id" class="form-select"
                                data-error-container="#area_id-container" data-rules="noVacio"
                                data-message-no-vacio="Debe seleccionar un área."></select>
                        </div>
                        <div class="form-text text-danger no-options-message" style="display: none;"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Recinto</label>
                        <div id="recinto_id-container">
                            <select name="recinto_id" id="recinto_id" class="form-select"></select>
                        </div>
                        <div class=" form-text text-danger no-options-message" style="display: none;">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="observaciones_ubicacion" class="form-label">Observaciones</label>
                        <textarea class="form-control" id="observaciones_ubicacion" name="observaciones"
                            data-rules="longitudMaxima:1000"
                            data-message-longitud-maxima="Máximo 1000 caracteres."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                        id="btnCancelarRegistroUbicacion">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Ubicación</button>
                </div>
            </form>
        </div>
    </div>
</div>


<div class="modal fade" id="modalRegistroIncidencia" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalRegistroIncidenciaLabel">Registrar Incidencia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formRegistroIncidencia" data-validation="reactive">
                <div class="modal-body">
                    <input type="hidden" name="animal_id" id="incidencia_animal_id">
                    <input type="hidden" name="incidencia_id" id="incidencia_id">

                    <div class="alert alert-info py-1 px-2 mb-3 small">
                        <i class="mdi mdi-information"></i>
                        Si selecciona <strong>Riña, Agresividad o Aplastamiento</strong>, podrá registrar las heridas
                        causadas.
                    </div>

                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label" for="incidencia_tipo">Tipo de Incidencia*</label>
                            <select class="form-select" id="incidencia_tipo" name="tipo" data-rules="noVacio"
                                data-message-no-vacio="Debe seleccionar un tipo.">
                                <option value="">Seleccione...</option>
                                <?php foreach ($tipos_incidencia as $opt): ?>
                                    <option value="<?= $opt ?>"><?= str_replace('_', ' ', strtoupper($opt)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="incidencia_fecha_evento">Fecha y Hora Evento*</label>
                            <input type="datetime-local" class="form-control" id="incidencia_fecha_evento"
                                name="fecha_evento" data-rules="noVacio" data-message-no-vacio="La fecha es requerida.">
                        </div>
                    </div>

                    <div class="row g-2 mt-2">
                        <div class="col-md-6">
                            <label class="form-label" for="incidencia_area_id">Ubicación del suceso</label>
                            <div id="incidencia_area_id-container">
                                <select class="form-select" id="incidencia_area_id" name="area_id"
                                    data-error-container="#incidencia_area_id-container"></select>
                            </div>
                            <div class="form-text text-muted small" style="font-size: 0.75rem;">
                                Dejar vacío para usar la ubicación actual del animal.
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="incidencia_responsable">Responsable (Opcional)</label>
                            <input type="text" class="form-control" id="incidencia_responsable" name="responsable"
                                placeholder="Ej. Operario de turno" maxlength="100">
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="form-label" for="incidencia_descripcion">Descripción General</label>
                        <textarea class="form-control" id="incidencia_descripcion" name="descripcion" rows="2"
                            placeholder="Describa brevemente lo sucedido..."></textarea>
                    </div>

                    <div class="mt-2">
                        <label class="form-label">Fotografía (Opcional)</label>
                        <input type="file" class="form-control" id="incidencia_fotografia" name="fotografia"
                            accept="image/png, image/jpeg, image/webp">
                        <div class="form-text text-muted small" style="font-size: 0.7rem;">Max 20MB. Tipos permitidos:
                            JPG, PNG, WEBP.</div>

                        <div id="incidencia-foto-preview-container" class="mt-2 border p-1 rounded d-none">
                            <img id="incidencia-foto-preview" src="#" alt="Vista previa de la imagen"
                                class="img-fluid rounded" style="max-height: 150px;">
                            <div class="d-flex justify-content-between mt-1">
                                <span class="text-muted small" id="incidencia-foto-filename"></span>
                                <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1"
                                    id="btn-eliminar-incidencia-foto-existente">
                                    <i class="mdi mdi-trash-can-outline"></i> Eliminar
                                </button>
                            </div>
                            <input type="hidden" name="eliminar_foto_existente" id="eliminar_foto_existente" value="0">
                            <input type="hidden" name="fotografia_url_existente" id="fotografia_url_existente" value="">
                        </div>
                    </div>

                    <div id="seccion-consecuencias" class="card mt-3 border-danger d-none">
                        <div
                            class="card-header bg-danger text-white py-1 d-flex justify-content-between align-items-center">
                            <small class="fw-bold"><i class="mdi mdi-medical-bag"></i> Consecuencias de Salud
                                (Víctimas)</small>
                            <small>Se crearán registros de salud automáticamente</small>
                        </div>
                        <div class="card-body p-2 bg-light">
                            <div class="row mb-1 text-muted small fw-bold ps-1">
                                <div class="col-md-4">Animal Afectado</div>
                                <div class="col-md-4">Detalle de la Herida</div>
                                <div class="col-md-3">Severidad</div>
                                <div class="col-md-1"></div>
                            </div>

                            <div id="lista-victimas">
                            </div>

                            <button type="button" class="btn btn-sm btn-outline-secondary mt-2 w-100 dashed-border"
                                id="btnAgregarVictima">
                                <i class="mdi mdi-plus"></i> Agregar Animal Afectado
                            </button>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                        id="btnCancelarRegistroIncidencia">Cancelar</button>
                    <button class="btn btn-primary" type="submit">Guardar Incidencia</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="modalDetallesIncidencia" tabindex="-1" aria-labelledby="modalDetallesIncidenciaLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDetallesIncidenciaLabel">Detalles de la Incidencia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="modalDetallesIncidenciaBody">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                    id="btnCerrarDetalleIncidencia">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
    const baseUrl = "<?php echo BASE_URL; ?>";
</script>
<script type="module" src="<?= BASE_URL ?>public/assets/js/modules/animales_view.js"></script>

<script src="<?= BASE_URL ?>public/assets/js/vendor/apexcharts.min.js"></script>