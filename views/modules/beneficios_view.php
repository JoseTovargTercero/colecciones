<div class="container-fluid">
    <h3>Beneficios por Matanza</h3>

    <div class="card">
        <div class="card-body">
            <table id="tablaBeneficios"
                   data-toggle="table"
                   data-url="<?php echo BASE_URL ?>api/beneficios"
                data-response-handler="responseHandler"
                    data-pagination="true"
                    data-search="true"
                    data-show-refresh="true"
                    data-show-columns="true"
                    data-locale="es-ES"
                    class="table table-striped table-hover"
                    style="width:100%">
        <thead>
        <tr>
            <th data-field="fecha">Fecha</th>
            <th data-field="total_animales">Animales</th>
            <th data-field="kg_total">Kg Totales</th>
            <th data-field="ingreso_total">Ingreso</th>
            <th data-formatter="accionesFormatter">Acciones</th>
        </tr>
        </thead>
    </table>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h4>Resumen Mensual</h4>
            <div id="graficoBeneficios"></div>
        </div>
    </div>


</div>

<!-- Modal -->
<div class="modal fade" id="modalDetalleBeneficio">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Detalle de animales beneficiados</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="table table-sm">
                    <thead>
                    <tr>
                        <th>Identificador</th>
                        <th>Especie</th>
                        <th>Sexo</th>
                        <th>Último peso</th>
                    </tr>
                    </thead>
                    <tbody id="detalleBeneficioBody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<!-- ApexCharts -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<script type="module" src="<?= BASE_URL ?>public/assets/js/modules/beneficios.js"></script>
