<link href="<?= BASE_URL ?>/public/assets/css/vendor/fullcalendar.min.css" rel="stylesheet">
<script src="<?= BASE_URL ?>/public/assets/js/vendor/fullcalendar.min.js"></script>

<!-- Bootstrap 5 -->

<div class="container-fluid">
    <!-- Título y Botón de Nuevo Periodo -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Gestión Agro — Agenda reproductiva</h4>
            </div>
        </div>
    </div>

    <ul class="nav nav-tabs" id="agroTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-calendar" data-bs-toggle="tab" data-bs-target="#pane-calendar" type="button"
                role="tab">Calendario</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-table" data-bs-toggle="tab" data-bs-target="#pane-table" type="button"
                role="tab">Tabla</button>
        </li>

    </ul>

    <div class="tab-content p-0">
        <div class="tab-pane fade show active" id="pane-calendar" role="tabpanel" aria-labelledby="tab-calendar">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-3 ">
                            <h5>Eventos</h5>

                            <div id="external-events" class="m-t-20">
                                <br>
                                <p class="text-muted">Drag and drop your event or click in the calendar
                                </p>
                                <div class="external-event bg-info-lighten text-info" data-class="bg-info">
                                    <i class="mdi mdi-checkbox-blank-circle me-2"></i>Revisiones
                                </div>
                                <div class="external-event bg-danger-lighten text-danger" data-class="bg-danger">
                                    <i class="mdi mdi-checkbox-blank-circle me-2"></i>Partos
                                </div>
                            </div>


                        </div>
                        <div class="col-lg-9">
                            <div id="calendar"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="pane-table" role="tabpanel" aria-labelledby="tab-table">
            <div class="card">
                <div class="card-body">
                    <table id="tablaPeriodosMonta" data-toggle="table"
                        data-url="<?php echo BASE_URL; ?>api/agenda_reproductiva"
                        data-response-handler="responseHandler" data-pagination="true" data-search="true"
                        data-show-refresh="true" data-show-columns="true" data-locale="es-ES"
                        class="table table-striped table-hover" style="width:100%">
                        <thead>
                            <tr>
                                <th data-field="title" data-sortable="true">Verraco</th>
                                <th data-field="start" data-sortable="true">Hembra</th>
                                <th data-field="notas" data-align="center">N° Servicios</th>
                                <th data-field="estado" data-sortable="true">Inicio</th>
                                <th data-field="className" data-sortable="true">Último Servicio</th>
                                <th data-field="estado" data-formatter="estatusFomatter" data-align="center">Estado</th>
                                <th data-field="className" data-formatter="accionesFormatter" data-align="center">Acciones</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>



<!-- Modal -->
<div class="modal fade" id="eventModalRevision" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header pb-0">
                <ul class="nav nav-tabs nav-bordered" role="tablist">

                    <li class="nav-item pointer " role="presentation">
                        <a id="tab-info" data-bs-toggle="tab" data-bs-target="#pane-info" aria-expanded="false" class="nav-link pointer active">
                            Detalles del periodo
                        </a>
                    </li>
                    <li class="nav-item pointer " role="presentation">
                        <a id="tab-form" data-bs-toggle="tab" data-bs-target="#pane-form" class="nav-link pointer">
                            Resultados
                        </a>
                    </li>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="tab-content p-0">
                    <div class="tab-pane fade show active" id="pane-info" role="tabpanel" aria-labelledby="tab-info">
                        <div class="card">
                            <div class="card-body">


                                <div class="row g-4">
                                    <div class="col-12">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <h2 class="h4 mb-0">Periodo / Servicios</h2>
                                        </div>
                                        <div class="">
                                            <div class="row gy-3">
                                                <div class="col-md-6">
                                                    <p class="mb-1"><span class="muted-small">hembra</span>: <strong id="hembraIdent"></strong> </p>
                                                    <p class="mb-0"><span class="muted-small">verraco</span>: <strong id="verracoIdent"></strong> </p>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-1">Inicio</div>
                                                    <div><strong id="fechaInicio"></strong></div>
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
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <!-- filas generadas por JS -->
                                                    </tbody>
                                                </table>
                                            </div>

                                    </div>
                                </div>


                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="pane-form" role="tabpanel" aria-labelledby="tab-form">
                        <div class="card">
                            <div class="card-body">
                                <form id="customEventForm">
                                    <div class="mb-3">
                                        <label>Resultados</label>
                                        <select class="form-select" id="eventStatus">
                                            <option value="">SELECCIONES</option>
                                            <option value="ENTRO_EN_CELO">ENTRO EN CELO</option>
                                            <option value="SOSPECHA_PREÑEZ">SOSPECHA DE PREÑEZ</option>
                                            <option value="SIN_SEÑALES">SIN SEÑALES</option>
                                            <option value="CONFIRMADA_PREÑEZ">PREÑEZ CONFIRMADA</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label>Observación</label>
                                        <textarea class="form-control" id="eventNotes"></textarea>
                                    </div>

                                </form>
                            </div>
                        </div>
                    </div>
                </div>






            </div>
            <div class="modal-footer">
                <button id="btnSaveCustom" class="btn btn-primary hide">Guardar</button>
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>





<script>
    const baseUrl = "<?= BASE_URL ?>";

    document.addEventListener("DOMContentLoaded", function() {
        const tabForm = document.getElementById("tab-form");
        const tabInfo = document.getElementById("tab-info");
        const btnResultados = document.getElementById("btnSaveCustom");

        tabForm.addEventListener("click", function() {
            btnResultados.classList.remove("hide"); // mostrar
        });

        tabInfo.addEventListener("click", function() {
            btnResultados.classList.add("hide"); // ocultar
        });
    });
</script>

<script type="module" src="<?= BASE_URL ?>/public/assets/js/modules/agenda_reproductiva_view.js"></script>
<script type="module" src="<?= BASE_URL ?>/public/assets/js/pages/app.calendar.agenda_reproductiva.js"></script>