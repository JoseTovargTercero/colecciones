<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <button type="button" class="btn btn-primary" id="btnEditarPerfil">
                        <i class="mdi mdi-pencil"></i> Editar Perfil
                    </button>
                </div>
                <h4 class="page-title">MI PERFIL</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title mt-0 mb-3">Información de la Cuenta</h4>
                    <div class="text-center">
                        <i class="mdi mdi-account-circle" style="font-size: 80px;"></i>
                        <h4 class="mt-2" id="perfil_nombre">
                            <span class="spinner-border spinner-border-sm" role="status"></span>
                        </h4>
                        <p class="text-muted" id="perfil_email">
                            <span class="spinner-border spinner-border-sm" role="status"></span>
                        </p>
                    </div>
                    <hr class="my-3">
                    <p><strong>Nivel:</strong> <span id="perfil_nivel">...</span></p>
                    <p><strong>Estado:</strong> <span id="perfil_estado">...</span></p>
                    <p><strong>Registrado el:</strong> <span id="perfil_creado">...</span></p>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title mt-0 mb-3">Mis Permisos Asignados</h4>
                    <p class="text-muted">Estos son los módulos a los que tienes acceso.</p>
                    <div class="accordion custom-accordion" id="accordionPermisos">
                        <div class="text-center p-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Cargando permisos...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalPerfil" tabindex="-1" aria-labelledby="modalPerfilLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalPerfilLabel">Editar Mi Perfil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formPerfil" data-validation="reactive">
                <div class="modal-body">
                    <input type="hidden" id="user_id" name="user_id">

                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="nombre" name="nombre"
                            data-rules="noVacio|longitudMaxima:150" data-message-no-vacio="El nombre es requerido."
                            data-message-longitud-maxima="El nombre no puede exceder los 150 caracteres.">
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email"
                            data-rules="noVacio|email|longitudMaxima:100" data-message-no-vacio="El email es requerido."
                            data-message-email="Debe ingresar un email válido."
                            data-message-longitud-maxima="El email no puede exceder los 100 caracteres."
                            data-validate-duplicate-url="api/system_users/check_email"
                            data-record-id-selector="#user_id" data-message-duplicado="Este email ya está en uso.">
                    </div>



                    <small class="text-muted mb-2">* Para cambiar su contraseña, ingrese la actual y luego la
                        nueva.</small>
                    <hr class="mb-2">

                    <div class="mb-3">
                        <label for="contrasena_actual" class="form-label">Contraseña Actual</label>
                        <input type="password" class="form-control" id="contrasena_actual" name="contrasena_actual">
                    </div>
                    <div class="mb-3">
                        <label for="contrasena" class="form-label">Nueva Contraseña</label>
                        <input type="password" class="form-control" id="contrasena" name="contrasena"
                            data-rules="longitudMinima:8"
                            data-message-longitud-minima="La contraseña debe tener al menos 8 caracteres."
                            data-revalidate-targets="#contrasena_confirm">
                    </div>

                    <div class="mb-3">
                        <label for="contrasena_confirm" class="form-label">Confirmar Nueva Contraseña</label>
                        <input type="password" class="form-control" id="contrasena_confirm" name="contrasena_confirm"
                            data-rules="coincideCon:#contrasena"
                            data-message-coincide-con="Las contraseñas no coinciden.">
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
    const baseUrl = "<?php echo BASE_URL; ?>";
</script>
<script type="module" src="<?= BASE_URL ?>/public/assets/js/modules/perfil_view.js"></script>