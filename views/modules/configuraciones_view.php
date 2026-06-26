<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">CONFIGURACIONES DEL SISTEMA</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="alert alert-info" role="alert">
                        <i class="mdi mdi-information-outline me-2"></i>
                        Modifique los valores de configuración del sistema. Estos cambios pueden afectar el
                        comportamiento de la aplicación.
                    </div>

                    <table id="tablaConfiguraciones" 
                           data-toggle="table"
                           data-url="<?php echo BASE_URL; ?>api/configuraciones"
                           data-response-handler="responseHandler" 
                           data-pagination="false" 
                           data-search="true"
                           data-show-refresh="true" 
                           data-show-columns="true" 
                           data-locale="es-ES"
                           class="table table-striped table-hover" style="width:100%">
                        <thead>
                            <tr>
                                <th data-field="config_key" data-sortable="true" data-width="200">Clave</th>
                                <th data-field="descripcion" data-sortable="false">Descripción</th>
                                <th data-field="config_value" data-formatter="valorFormatter" data-halign="center"
                                    data-align="center" data-width="150">Valor Actual</th>
                                <th data-field="updated_at" data-formatter="dateFormatter" data-sortable="true"
                                    data-width="180">Última Modificación</th>
                                <th data-field="config_key" data-formatter="accionesFormatter" data-halign="center"
                                    data-align="center" data-width="100">Acciones</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalConfiguracion" tabindex="-1" aria-labelledby="modalConfigLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalConfigLabel">Editar Configuración</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formConfiguracion">
                <div class="modal-body">
                    <input type="hidden" id="config_key" name="config_key">

                    <p class="mb-2">
                        <strong>Clave:</strong>
                        <br>
                        <code id="detalle_clave" class="fs-6"></code>
                    </p>
                    <p>
                        <strong>Descripción:</strong>
                        <br>
                        <span id="detalle_descripcion" class="text-muted"></span>
                    </p>

                    <hr>
                    <label class="form-label fw-bold">Nuevo Valor:</label>
                    
                    <div id="valorInputContainer">
                        </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Pasa la URL base de PHP a JavaScript
    const baseUrl = "<?php echo BASE_URL; ?>";
</script>
<script type="module" src="<?= BASE_URL ?>public/assets/js/modules/configuraciones_view.js"></script>