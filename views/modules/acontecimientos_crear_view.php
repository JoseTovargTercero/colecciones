<?php
// views/modules/acontecimientos_crear_view.php
?>
<div class="container-fluid">
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <?php if (tienePermiso('acontecimientos')): ?>
                    <a href="<?= BASE_URL ?>acontecimientos" class="btn btn-secondary btn-sm">
                        <i class="mdi mdi-arrow-left me-1"></i> Volver al Muro
                    </a>
                    <?php endif; ?>
                </div>
                <h4 class="page-title">Crear Nuevo Acontecimiento</h4>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row justify-content-center">
        <!-- Center Form -->
        <div class="col-xxl-8 col-lg-10">
            <!-- Create Post Box -->
            <div class="card">
                <div class="card-body">
                    <form id="formAcontecimiento" enctype="multipart/form-data">
                        <div class="d-flex align-items-start mb-3">
                            <img class="me-2 avatar-sm rounded-circle" src="<?= BASE_URL ?>public/assets/images/users/avatar-1.jpg" alt="Generic placeholder image">
                            <div class="w-100">
                                <h5 class="mt-0 mb-1">Registrar Nuevo Acontecimiento</h5>
                                <p class="text-muted mb-0"><small>Complete los campos requeridos según el tipo de evento</small></p>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Tipo de Acontecimiento -->
                            <div class="col-md-6 mb-3">
                                <label for="tipo" class="form-label">Tipo de Evento <span class="text-danger">*</span></label>
                                <select class="form-select" id="tipo" name="tipo" required>
                                    <option value="">Seleccione...</option>
                                    <option value="vacunacion">Vacunación</option>
                                    <option value="decesos">Decesos</option>
                                    <option value="revision">Revisión Veterinaria</option>
                                    <option value="cuarentena">Cuarentena</option>
                                    <option value="tratamiento">Tratamiento</option>
                                    <option value="brote">Brote de Enfermedad</option>
                                    <option value="limpieza">Limpieza y Desinfección</option>
                                    <option value="beneficios">Beneficios</option>
                                </select>
                            </div>

                            <!-- Selección de Animales (Oculto por defecto, excepto para limpieza) -->
                            <div class="col-md-6 mb-3" id="animales_involucrados">
                                <label for="animales_seleccion" class="form-label">Animales Involucrados</label>
                                <select class="form-select" id="animales_seleccion" name="animales_seleccion">
                                    <option value="">Seleccione modo...</option>
                                    <option value="fincas">Por Finca</option>
                                    <option value="apriscos">Por Aprisco</option>
                                    <option value="areas">Por Área</option>
                                    <option value="manual">Selección Manual</option>
                                </select>
                            </div>
                        </div>

                        <!-- Secciones Dinámicas de Selección -->
                        <div id="sect_fincas" class="sections_select mb-3 hide">
                            <label class="form-label">Finca</label>
                            <select id="fincas" name="fincas" class="form-control"></select>
                        </div>
                        <div id="sect_apriscos" class="sections_select mb-3 hide">
                            <label class="form-label">Aprisco</label>
                            <select id="apriscos" name="apriscos" class="form-control"></select>
                        </div>
                        <div id="sect_areas" class="sections_select mb-3 hide">
                            <label class="form-label">Área</label>
                            <select id="areas" name="areas" class="form-control" multiple></select>
                        </div>
                        <div id="sect_animales" class="sections_select mb-3 hide">
                            <label class="form-label">Animales</label>
                            <select id="animales" name="animales" class="form-control" multiple></select>
                        </div>

                        <!-- Campos Específicos por Tipo -->
                        <!-- Vacunación -->
                        <div id="campos-vacunacion" class="hide mb-3">
                            <div class="row">
                                <div class="col-md-4"><input type="text" class="form-control" id="vacuna_nombre" placeholder="Nombre Vacuna"></div>
                                <div class="col-md-4"><input type="date" class="form-control" id="vacuna_fecha"></div>
                                <div class="col-md-4"><input type="text" class="form-control" id="vacuna_dosis" placeholder="Dosis"></div>
                            </div>
                        </div>
                        <!-- Decesos -->
                        <div id="campos-decesos" class="hide mb-3">
                            <div class="row">
                                <div class="col-md-4"><input type="number" class="form-control" id="deceso_cantidad" placeholder="Cantidad"></div>
                                <div class="col-md-4"><input type="text" class="form-control" id="deceso_causa" placeholder="Causa"></div>
                                <div class="col-md-4"><input type="date" class="form-control" id="deceso_fecha"></div>
                            </div>
                        </div>
                        <!-- Revisión -->
                        <div id="campos-revision" class="hide mb-3">
                            <div class="row">
                                <div class="col-md-6"><input type="text" class="form-control" id="revision_veterinario" placeholder="Veterinario"></div>
                                <div class="col-md-6"><input type="date" class="form-control" id="revision_fecha"></div>
                            </div>
                        </div>
                        <!-- Cuarentena -->
                        <div id="campos-cuarentena" class="hide mb-3">
                            <div class="row">
                                <div class="col-md-4"><input type="date" class="form-control" id="cuarentena_inicio" placeholder="Inicio"></div>
                                <div class="col-md-4"><input type="date" class="form-control" id="cuarentena_fin" placeholder="Fin Estimado"></div>
                                <div class="col-md-4"><input type="text" class="form-control" id="cuarentena_motivo" placeholder="Motivo"></div>
                            </div>
                        </div>
                        <!-- Tratamiento -->
                        <div id="campos-tratamiento" class="hide mb-3">
                            <div class="row">
                                <div class="col-md-6"><input type="text" class="form-control" id="tratamiento_medicamento" placeholder="Medicamento"></div>
                                <div class="col-md-6"><input type="text" class="form-control" id="tratamiento_dosis" placeholder="Dosis/Instrucciones"></div>
                            </div>
                        </div>
                        <!-- Brote -->
                        <div id="campos-brote" class="hide mb-3">
                            <div class="row">
                                <div class="col-md-4"><input type="text" class="form-control" id="brote_tipo" placeholder="Tipo Enfermedad"></div>
                                <div class="col-md-4"><input type="number" class="form-control" id="brote_afectados" placeholder="N° Afectados"></div>
                                <div class="col-md-4">
                                    <select class="form-select" id="brote_severidad">
                                        <option value="baja">Baja</option>
                                        <option value="media">Media</option>
                                        <option value="alta">Alta</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <!-- Limpieza -->
                        <div id="campos-limpieza" class="hide mb-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <label>Áreas a Limpiar</label>
                                    <select id="limpieza_area" name="limpieza_area" class="form-control" multiple></select>
                                </div>
                                <div class="col-md-6">
                                    <label>Fecha</label>
                                    <input type="date" class="form-control" id="limpieza_fecha">
                                </div>
                            </div>
                        </div>

                        <!-- Beneficios -->
                        <div id="campos-beneficios" class="hide mb-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <label>Ingreso global ($)</label>
                                    <input type="text" placeholder="0.00" inputmode="decimal" autocomplete="off" id="ingreso" name="ingreso" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label>Peso de la tanda (Kg)</label>
                                    <input type="text" placeholder="0.00" inputmode="decimal" autocomplete="off" class="form-control" step="0.01" id="kilogramos" name="kilogramos">
                                </div>
                            </div>
                        </div>



                        <!-- Observación General -->
                        <div class="mb-3">
                            <label for="observacion" class="form-label">Observación</label>
                            <textarea class="form-control" id="observacion" name="observacion" rows="3" placeholder="Escribe una observación..."></textarea>
                        </div>

                        <!-- Footer con Botones -->
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <label class="btn btn-sm btn-light text-muted">
                                    <i class="mdi mdi-image-outline me-1"></i> Fotos
                                    <input type="file" name="photo[]" multiple hidden accept="image/*">
                                </label>
                            </div>
                            <div>
                                <?php if (tienePermiso('acontecimientos')): ?>
                                <a href="<?= BASE_URL ?>acontecimientos" class="btn btn-secondary btn-sm me-2">Cancelar</a>
                                <?php endif; ?>
                                <button type="submit" class="btn btn-primary btn-sm">Publicar Evento</button>
                            </div>
                        </div>

                        <!-- Image Preview Container -->
                        <div id="image-preview-container" class="row mt-3"></div>
                    </form>
                </div>
            </div>
            <!-- End Create Post Box -->
        </div>
    </div>
</div>

<style>
    .hide { display: none; }
</style>

<script>
    const baseUrl = "<?= BASE_URL ?>";
</script>

<!-- Tom Select -->
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

<!-- Module JS -->
<script type="module" src="<?= BASE_URL ?>public/assets/js/modules/acontecimientos_crear_view.js"></script>
