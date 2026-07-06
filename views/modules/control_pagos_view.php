<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h4 class="page-title mb-0">Control de pagos</h4>
                <ul class="nav nav-tabs border-0 mb-0" role="tablist" style="gap:2px">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-pagos-btn" data-bs-toggle="tab" data-bs-target="#tab-pagos" type="button" role="tab" style="border-radius:8px 8px 0 0">
                            <i class="bx bx-table me-1"></i>Control de Pagos
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-historial-btn" data-bs-toggle="tab" data-bs-target="#tab-historial" type="button" role="tab" style="border-radius:8px 8px 0 0">
                            <i class="bx bx-history me-1"></i>Historial
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">

            <!-- Filters -->
            <div class="d-flex gap-3 mb-3 align-items-end flex-wrap">
                <div>
                    <label for="empresa" class="form-label">Empresa</label>
                    <select id="empresa" class="form-select"></select>
                </div>
                <div>
                    <label for="campaniaSelect" class="form-label">Campaña</label>
                    <select id="campaniaSelect" class="form-select"></select>
                </div>
                <div id="cpFiltroBuscador">
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <input type="text" class="form-control" id="buscador" placeholder="Buscar vendedor..." style="width:200px">
                    </div>
                </div>
                <div id="cpBtnGroup">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-1">
                        <button class="btn btn-outline-primary" id="btnAgrupar">Desagrupar</button>
                        <button class="btn btn-outline-secondary" id="btnScroll" title="Desplazar horizontalmente">→</button>
                    </div>
                </div>
            </div>

            <div class="tab-content">
                <!-- Tab 1: Control de Pagos -->
                <div class="tab-pane fade show active" id="tab-pagos" role="tabpanel">
                    <div class="d-flex gap-3 mb-3 legend">
                        <div class="gap-2">
                            <span class="badge bg-secondary" title="PENDIENTE">P</span> <small>PENDIENTE</small>
                        </div>
                        <div class="gap-2">
                            <span class="badge bg-success" title="REALIZADO">R</span> <small>REALIZADO</small>
                        </div>
                        <div class="gap-2">
                            <span class="badge bg-danger" title="VENCIDO">V</span> <small>VENCIDO</small>
                        </div>
                        <div class="gap-2">
                            <span class="badge bg-warning text-dark" title="MARGEN DE PAGO">M</span> <small>MARGEN DE PAGO</small>
                        </div>
                    </div>

                    <div class="table-responsive" id="tableWrapper" style="max-height:600px;overflow:auto">
                        <table id="aTabla" class="table table-bordered table-striped table-sm mb-0" style="min-width:600px">
                            <thead>
                                <tr id="aTablaHead"></tr>
                            </thead>
                            <tbody id="aTablaBody">
                                <tr>
                                    <td colspan="3" class="text-center text-muted">Seleccione empresa y campaña.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab 2: Historial -->
                <div class="tab-pane fade" id="tab-historial" role="tabpanel">
                    <div class="d-flex gap-2 mb-3 align-items-center">
                        <input type="text" class="form-control" id="historialBuscador" placeholder="Buscar por número de operación..." style="width:280px">
                        <small class="text-muted" id="historialCount"></small>
                    </div>
                    <div id="cpHistorialBody">
                        <p class="text-center text-muted py-5">Seleccione empresa y campaña.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Cargar Pago -->
<div class="modal fade" id="cpModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" id="cpModalDialog">
        <div class="modal-content">
            <form id="cpForm">
                <input type="hidden" name="empresa_id" id="cpEmpresaId">
                <input type="hidden" name="temporada_id" id="cpTempId">
                <input type="hidden" name="vendedor_id" id="cpVendedorId">
                <div class="modal-header">
                    <h5 class="modal-title">Cargar Pago</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12" id="cpFormCol">
                            <p><strong>Vendedor:</strong> <span id="cpVendedorLabel"></span></p>
                            <p><strong>Deuda total:</strong> <span id="cpDeudaTotal">$0.00</span></p>
                            <hr>
                            <div class="mb-3">
                                <label for="cpTipoPago" class="form-label">Tipo de pago</label>
                                <select class="form-select" id="cpTipoPago" name="tipo_pago" required>
                                    <option value="">Seleccione...</option>
                                    <option value="total">Liquidación Total de la deuda</option>
                                    <!-- <option value="cuota_exacta">Pago de cuota exacta</option> -->
                                    <option value="abono">Abono</option>
                                </select>
                            </div>
                            <div class="mb-3" id="cpCuotaGroup" style="display:none">
                                <label for="cpCuotaSelect" class="form-label">Seleccionar cuota</label>
                                <select class="form-select" id="cpCuotaSelect" name="cuota_id">
                                    <option value="">Seleccione cuota...</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="cpMontoBs" class="form-label">Monto pagado (BS)</label>
                                <input type="number" placeholder="Monto en Bolívares" step="0.01" class="form-control" id="cpMontoBs" name="monto_bs">
                            </div>
                            <div class="mb-3">
                                <label for="cpTasaBcv" class="form-label">Tasa BCV (Bs./USD)</label>
                                <div class="input-group">
                                    <input type="number" step="0.01" class="form-control" id="cpTasaBcv" name="tasa_dia" value="<?= htmlspecialchars($_SESSION['bcv_valor'] ?? '', ENT_QUOTES) ?>" readonly>
                                    <button class="btn btn-outline-secondary" type="button" id="btnEditTasa" title="Editar tasa"><i class="bx bx-pencil"></i></button>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="cpMonto" class="form-label" id="cpMontoLabel">Monto pagado (USD)</label>
                                <input type="number" placeholder="Monto en Dolares" step="0.01" class="form-control" id="cpMonto" name="monto" required readonly>
                            </div>
                            <div class="mb-3">
                                <label for="cpNumOp" class="form-label">Número de operación</label>
                                <input type="text" class="form-control" id="cpNumOp" name="numero_operacion">
                            </div>
                            <div class="mb-3">
                                <label for="cpFechaPago" class="form-label">Fecha del pago</label>
                                <input type="date" class="form-control" id="cpFechaPago" name="fecha_pago_comprobante">
                            </div>
                            <div class="mb-3">
                                <label for="cpComprobante" class="form-label">Comprobante (imagen)</label>
                                <input type="file" class="form-control" id="cpComprobante" name="comprobante" accept="image/*">
                            </div>
                        </div>
                        <div class="col-md-7" id="cpAbonoPreview" style="display:none">
                            <h6 class="fw-semibold mb-2"><i class="bx bx-donate-blood text-primary me-1"></i>Distribución del abono</h6>
                            <div style="max-height:420px;overflow-y:auto;border:1px solid #e9ecef;border-radius:8px">
                                <table class="table table-sm table-borderless mb-0">
                                    <thead class="table-light" style="position:sticky;top:0;z-index:2;">
                                        <tr>
                                            <th style="width:35%">Cuota / Fecha</th>
                                            <th style="width:30%">Por pagar</th>
                                            <th style="width:15%">Desc.</th>
                                            <th style="width:20%">Aplicación</th>
                                        </tr>
                                    </thead>
                                    <tbody id="cpAbonoPreviewBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Registrar pago</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Estatus de Deuda -->
<div class="modal fade" id="cpDeudaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">
                    <i class="bx bx-pie-chart-alt-2 me-2 text-primary"></i>Estatus de Deuda
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-3">
                <div id="cpDeudaLoading" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="text-muted mt-2 mb-0">Cargando información de deuda...</p>
                </div>
                <div id="cpDeudaContent" style="display:none">
                    <!-- Resumen -->
                    <div class="row g-3 mb-4">
                        <div class="col-sm-4 d-none">
                            <div class="card bg-soft-primary border-0 shadow-sm">
                                <div class="card-body text-center py-3">
                                    <div class="text-muted small text-uppercase fw-semibold tracking-wide">Deuda Total</div>
                                    <div class="display-6 fw-bold text-primary mt-1" id="cpDeudaTotalLabel">$0.00</div>
                                    <br>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="card bg-soft-success border-0 shadow-sm">
                                <div class="card-body text-center py-3">
                                    <div class="text-muted small text-uppercase fw-semibold tracking-wide">Total Pagado</div>
                                    <div class="display-6 fw-bold text-success mt-1" id="cpDeudaPagadoLabel">$0.00</div>
                                    <br>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="card bg-soft-warning border-0 shadow-sm">
                                <div class="card-body text-center py-3">
                                    <div class="text-muted small text-uppercase fw-semibold tracking-wide">Pendiente Efectivo</div>
                                    <div class="display-6 fw-bold text-warning mt-1" id="cpDeudaPendienteLabel">$0.00</div>
                                    <small class="text-muted mt-1" id="cpDeudaDescuentoLabel" style="display:none">
                                        <i class="bx bx-info-circle me-1"></i>Desc. ganancia: -<span id="cpDeudaDescuentoMonto">$0.00</span>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Barra de progreso -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <small class="text-muted fw-semibold">Progreso de pago</small>
                            <small class="fw-bold" id="cpDeudaProgresoLabel">0%</small>
                        </div>
                        <div class="progress" style="height:12px;border-radius:6px">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" id="cpDeudaBarra"
                                role="progressbar" style="width:0%;border-radius:6px;background:linear-gradient(90deg,#7367f0,#0d6efd)" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <h6 class="fw-semibold mb-0"><i class="bx bx-list-ul me-1 text-muted"></i>Detalle de cuotas</h6>
                        <div class="form-check form-switch ms-auto">
                            <input class="form-check-input" type="checkbox" id="cpTogglePagadas" role="switch">
                            <label class="form-check-label small" for="cpTogglePagadas">Mostrar pagadas</label>
                        </div>
                    </div>
                    <div class="table-responsive" style="max-height:350px;overflow-y:auto">
                        <table class="table table-sm table-hover mb-0 border">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>#</th>
                                    <th>Colección</th>
                                    <th>Monto</th>
                                    <th>Pagado</th>
                                    <th>Pendiente</th>
                                    <th>Ganancia</th>
                                    <th>Estatus</th>
                                </tr>
                            </thead>
                            <tbody id="cpDeudaTableBody"></tbody>
                        </table>
                    </div>
                    <!-- Comprobantes -->
                    <div class="mt-3" id="cpDeudaCompSection">
                        <div class="d-flex align-items-center gap-2 mb-2" data-bs-toggle="collapse" data-bs-target="#cpDeudaCompList" aria-expanded="false" style="cursor:pointer">
                            <h6 class="fw-semibold mb-0"><i class="bx bx-receipt me-1 text-muted"></i>Comprobantes</h6>
                            <span class="badge bg-primary rounded-pill" id="cpDeudaCompCount">0</span>
                            <i class="bx bx-chevron-down fs-5 ms-auto"></i>
                        </div>
                        <div class="collapse" id="cpDeudaCompList">
                            <div class="table-responsive" style="max-height:250px;overflow-y:auto">
                                <table class="table table-sm table-hover mb-0 border">
                                    <thead class="table-light">
                                        <tr>
                                            <th># Cuota</th>
                                            <th>Monto</th>
                                            <th>Monto BS</th>
                                            <th>Tasa</th>
                                            <th>N° Operación</th>
                                            <th>Archivo</th>
                                            <th>Fecha</th>
                                        </tr>
                                    </thead>
                                    <tbody id="cpDeudaCompBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <!-- Premios -->
                    <div class="mt-3" id="cpDeudaPremiosSection">
                        <div class="d-flex align-items-center gap-2 mb-2" data-bs-toggle="collapse" data-bs-target="#cpDeudaPremiosList" aria-expanded="false" style="cursor:pointer">
                            <h6 class="fw-semibold mb-0"><i class="bx bx-gift me-1 text-muted"></i>Premios Solicitados</h6>
                            <span class="badge bg-warning text-dark rounded-pill" id="cpDeudaPremiosCount">0</span>
                            <i class="bx bx-chevron-down fs-5 ms-auto"></i>
                        </div>
                        <div class="collapse" id="cpDeudaPremiosList">
                            <div class="table-responsive" style="max-height:250px;overflow-y:auto">
                                <table class="table table-sm table-hover mb-0 border">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Premio</th>
                                            <th>Valor</th>
                                            <th>Estatus</th>
                                            <th>Fecha</th>
                                        </tr>
                                    </thead>
                                    <tbody id="cpDeudaPremiosBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Custom dropdown menu (appended to body) -->
<div id="cpDropMenu" class="dropdown-menu shadow border-0" style="position:fixed;display:none;min-width:180px;z-index:9999">
    <a class="dropdown-item py-2" href="#" id="cpDropPago"><i class="bx bx-dollar-circle me-2 text-success"></i>Cargar pago</a>
    <hr class="dropdown-divider my-1">
    <a class="dropdown-item py-2" href="#" id="cpDropDeuda"><i class="bx bx-bar-chart-alt-2 me-2 text-primary"></i>Estatus de deuda</a>
    <hr class="dropdown-divider my-1">
    <a class="dropdown-item py-2" href="#" id="cpDropPremio"><i class="bx bx-gift me-2" style="color: #a594f9"></i>Solicitud de premio</a>
</div>

<!-- Modal Solicitud de Premio -->
<div class="modal fade" id="cpPremioModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">
                    <i class="bx bx-gift me-2"></i>Solicitud de Premio
                </h5>
                <button type="button" class="btn-close btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="cpPremioForm">
                <input type="hidden" name="vendedor_id" id="cpPremioVendedorId">
                <input type="hidden" name="empresa_id" id="cpPremioEmpresaId">
                <input type="hidden" name="temporada_id" id="cpPremioTempId">
                <div class="modal-body pt-4">
                    <div id="cpPremioLoading" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="text-muted mt-2 mb-0">Cargando información del vendedor...</p>
                    </div>
                    <div id="cpPremioContent" style="display:none">

                        <div class="d-flex align-items-center mb-4">
                            <div class="avatar-sm bg-soft-primary rounded-circle text-primary d-flex justify-content-center align-items-center me-3" style="width: 48px; height: 48px;">
                                <i class="bx bx-user fs-3"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold" id="cpPremioVendedorNombre">Nombre Vendedor</h6>
                                <small class="text-muted" id="cpPremioEmpresaNombre">Empresa</small>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-6">
                                <div class="card bg-soft-success border-0 shadow-none rounded-3 h-100">
                                    <div class="card-body p-3 text-center">
                                        <div class="text-muted small fw-semibold text-uppercase mb-1">Ganancia Disp.</div>
                                        <div class="fs-4 fw-bold text-success" id="cpPremioGananciaTotal">$0.00</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="card bg-soft-info border-0 shadow-none rounded-3 h-100">
                                    <div class="card-body p-3 text-center">
                                        <div class="text-muted small fw-semibold text-uppercase mb-1">Asignaciones</div>
                                        <div class="fs-4 fw-bold text-info" id="cpPremioAsignaciones">0</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-muted">Seleccionar Premio</label>
                            <select class="form-select form-select-lg" name="premio_id" id="cpPremioSelect" required style="border-radius: 8px;">
                                <option value="">Seleccione un premio...</option>
                            </select>
                            <div id="cpPremioWarning" class="form-text text-danger mt-2" style="display:none;">
                                <i class="bx bx-error-circle me-1"></i>El valor del premio supera la ganancia disponible.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light" id="cpPremioFooter" style="display:none;">
                    <button type="button" class="btn btn-light shadow-sm" data-bs-dismiss="modal" style="border-radius: 8px;">Cancelar</button>
                    <button type="submit" class="btn btn-primary shadow-sm" id="cpPremioSubmitBtn" style="border-radius: 8px; background: linear-gradient(135deg, #7367f0 0%, #a594f9 100%); border: none;">Asignar Premio</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Ver Comprobante -->
<div class="modal fade" id="cpVerCompModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bx bx-receipt me-2 text-primary"></i>Comprobante</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-7">
                        <div id="cpVerCompImg" class="text-center p-3 bg-light rounded-3" style="min-height:200px">
                            <img src="" class="img-fluid rounded shadow-sm" style="max-height:400px;object-fit:contain" alt="Comprobante">
                        </div>
                    </div>
                    <div class="col-md-5">
                        <table class="table table-sm table-borderless mb-0">
                            <tbody id="cpVerCompBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<style>
    #aTabla th:nth-child(1),
    #aTabla td:nth-child(1) {
        position: sticky;
        left: 0;
        z-index: 3;
        background: #fff;
    }

    #aTabla th:nth-child(2),
    #aTabla td:nth-child(2) {
        position: sticky;
        left: 200px;
        z-index: 2;
        background: #fff;
    }

    #aTabla th:nth-child(1) {
        z-index: 5;
    }

    #aTabla th:nth-child(2) {
        z-index: 4;
    }


    #aTabla .cuota-header {
        white-space: nowrap;
        min-width: 60px;
        font-size: .75rem;
        vertical-align: middle;
        text-align: center !important;
        line-height: 1.2;
    }

    .cuota-pagada {
        background: #d4edda !important;
    }

    .cuota-vencido {
        background: #f8d7da !important;
    }

    .cuota-dentro_de_margen {
        background: #fff3cd !important;
    }

    .cuota-proxima {
        background: #cce5ff !important;
        font-weight: bold;
        color: #004085;
    }

    /* bg-soft helpers */
    .bg-soft-primary {
        background: rgba(115, 103, 240, .08) !important;
    }

    .bg-soft-success {
        background: rgba(40, 199, 111, .08) !important;
    }

    .bg-soft-warning {
        background: rgba(255, 171, 0, .08) !important;
    }

    .tracking-wide {
        letter-spacing: .04em;
    }

    .dropdown-item:active {
        background: #7367f0;
    }

    #cpAbonoPreviewBody tr:not(.table-success):not(.table-warning) td {
        vertical-align: middle;
    }
</style>

<script>
    const BASE = '<?= BASE_URL ?>';
    let _allRows = [];
    let _fechas = [];
    let _agrupado = true;
    let _diasRetraso = 3;
    let _cpCuotas = [];
    let _historialData = [];

    const badgeMap = {
        pendiente: '<span class="badge bg-secondary" title="PENDIENTE">P</span>',
        realizado: '<span class="badge bg-success" title="REALIZADO">R</span>',
        vencido: '<span class="badge bg-danger" title="VENCIDO">V</span>',
        dentro_de_margen: '<span class="badge bg-warning text-dark" title="MARGEN DE PAGO">M</span>',
    };
    const classMap = {
        realizado: 'cuota-pagada',
        vencido: 'cuota-vencido',
        dentro_de_margen: 'cuota-dentro_de_margen',
    };

    function remarcarIdx() {
        const hoyMs = Date.now();
        const dPerm = _diasRetraso;
        let idx = 0;
        for (let i = 0; i < _fechas.length; i++) {
            const fMs = new Date(_fechas[i] + 'T12:00:00').getTime();
            if (fMs <= hoyMs) {
                const dias = Math.floor((hoyMs - fMs) / 86400000);
                if (dias <= dPerm) {
                    idx = i;
                    break;
                }
            } else {
                if (i === 0) {
                    idx = 0;
                    break;
                }
                const prevMs = new Date(_fechas[i - 1] + 'T12:00:00').getTime();
                if (prevMs > hoyMs || Math.floor((hoyMs - prevMs) / 86400000) > dPerm) {
                    idx = i;
                    break;
                }
            }
            if (i === _fechas.length - 1) idx = i;
        }
        return idx;
    }

    function renderizar(filas) {
        const thead = document.getElementById('aTablaHead');
        const tbody = document.getElementById('aTablaBody');
        const colsExtra = _agrupado ? 2 : 3;
        const remIdx = remarcarIdx();

        let headerHtml = `<th style="position:sticky;left:0;z-index:3;background:#fff;min-width:200px">Vendedor</th>
        <th style="position:sticky;left:200px;z-index:3;background:#fff;width:40px"></th>`;
        if (!_agrupado) {
            headerHtml += `<th style="min-width:140px">Colección</th>`;
        }
        headerHtml += `<th style="min-width:80px">Monto</th>`;
        _fechas.forEach((f, i) => {
            const [y, m, d] = f.split('-');
            const label = `${parseInt(m)}/${parseInt(d)}<br><small class="text-muted">${y}</small>`;
            headerHtml += `<th class="cuota-header${i === remIdx ? ' cuota-proxima' : ''}" style="min-width:70px">${label}</th>`;
        });
        thead.innerHTML = headerHtml;

        if (!filas.length) {
            tbody.innerHTML = `<tr><td colspan="${colsExtra + _fechas.length}" class="text-center text-muted">Sin datos.</td></tr>`;
            return;
        }

        tbody.innerHTML = filas.map(r => {
            const cuotasMap = {};
            (r.cuotas || []).forEach(c => {
                cuotasMap[c.fecha_pago] = c;
            });

            const dots = `<button class="btn btn-sm btn-outline-secondary a-dd-btn" onclick="event.stopPropagation();abrirMenu(this,${r.vendedor_id})">⋮</button>`;

            let cells = `<td style="position:sticky;left:0;background:#fff"><strong>${r.vendedor_nombre}</strong><br><small class="text-muted">${r.vendedor_cedula || ''}</small></td>
            <td style="position:sticky;left:200px;background:#fff;text-align:center">${dots}</td>`;
            if (!_agrupado) {
                cells += `<td>${r.coleccion_nombre}<br><small class="text-muted">${r.coleccion_tipo}</small></td>`;
            }
            cells += `<td>$${parseFloat(r.precio_venta_vendedor || 0).toFixed(2)}</td>`;

            _fechas.forEach(f => {
                const c = cuotasMap[f];
                const badge = c ? (badgeMap[c.estatus_pago] || c.estatus_pago) : '<span class="text-muted">—</span>';
                const cls = c ? (classMap[c.estatus_pago] || '') : '';
                cells += `<td class="${cls} text-center">${badge}</td>`;
            });

            return `<tr>${cells}</tr>`;
        }).join('');
    }

    function agruparPorVendedor(rows) {
        const grupos = {};
        rows.forEach(r => {
            const key = r.vendedor_nombre + '|' + (r.vendedor_cedula || '');
            if (!grupos[key]) {
                grupos[key] = {
                    vendedor_id: r.vendedor_id,
                    vendedor_nombre: r.vendedor_nombre,
                    vendedor_cedula: r.vendedor_cedula,
                    precio_venta_vendedor: 0,
                    cuotas: [],
                };
            }
            grupos[key].precio_venta_vendedor += parseFloat(r.precio_venta_vendedor || 0);
            (r.cuotas || []).forEach(c => grupos[key].cuotas.push(c));
        });
        return Object.values(grupos);
    }

    function filtrarLocal(rows) {
        const q = document.getElementById('buscador').value.toLowerCase().trim();
        if (!q) return rows;
        return rows.filter(r => (r.vendedor_nombre || '').toLowerCase().includes(q) || (r.vendedor_cedula || '').includes(q));
    }

    async function cargarTabla() {
        const empresaId = document.getElementById('empresa').value;
        const tempId = document.getElementById('campaniaSelect').value;
        if (!empresaId || !tempId) return;

        const res = await fetch(`${BASE}api/control-pagos?empresa_id=${empresaId}&temporada_id=${tempId}`);
        const json = await res.json();

        _fechas = json.data?.cuotas_fechas || [];
        _allRows = json.data?.rows || [];
        _diasRetraso = json.data?.dias_retraso_permitido ?? 3;

        const filas = _agrupado ? agruparPorVendedor(_allRows) : _allRows;
        renderizar(filtrarLocal(filas));
    }

    async function init() {
        const [re, rt] = await Promise.all([
            fetch(BASE + 'api/empresas').then(r => r.json()),
            fetch(BASE + 'api/temporadas').then(r => r.json()),
        ]);
        const empresas = re.data || [];
        const temporadas = rt.data || [];
        const hoyStr = new Date().toISOString().slice(0, 10);

        const selEmp = document.getElementById('empresa');
        empresas.forEach(e => {
            selEmp.innerHTML += `<option value="${e.id}">${e.nombre}</option>`;
        });

        function filtrarTemporadas(empresaId) {
            const filtradas = temporadas.filter(t => t.empresa_id === empresaId);
            const selCamp = document.getElementById('campaniaSelect');
            selCamp.innerHTML = '';
            let found = false;
            filtradas.forEach(t => {
                const enCurso = hoyStr >= t.fecha_inicio && hoyStr <= t.fecha_fin;
                if (enCurso) found = true;
                selCamp.innerHTML += `<option value="${t.id}"${enCurso ? ' selected' : ''}>${t.nombre}</option>`;
            });
            if (!found && selCamp.options.length) selCamp.options[0].selected = true;
        }

        if (empresas.length) {
            selEmp.value = empresas[0].id;
            filtrarTemporadas(selEmp.value);
            cargarTabla();
        }

        selEmp.addEventListener('change', () => {
            filtrarTemporadas(selEmp.value);
            cargarTabla();
        });
        document.getElementById('campaniaSelect').addEventListener('change', cargarTabla);

        // Scroll
        const wrapper = document.getElementById('tableWrapper');
        document.getElementById('btnScroll').addEventListener('click', () => wrapper.scrollBy({
            left: 300,
            behavior: 'smooth'
        }));

        // Toggle agrupar
        const btnAgrupar = document.getElementById('btnAgrupar');
        btnAgrupar.addEventListener('click', () => {
            _agrupado = !_agrupado;
            btnAgrupar.textContent = _agrupado ? 'Desagrupar' : 'Agrupar';
            btnAgrupar.classList.toggle('btn-outline-primary', _agrupado);
            btnAgrupar.classList.toggle('btn-outline-warning', !_agrupado);
            const filas = _agrupado ? agruparPorVendedor(_allRows) : _allRows;
            renderizar(filtrarLocal(filas));
        });

        // Buscador
        document.getElementById('buscador').addEventListener('input', () => {
            const filas = _agrupado ? agruparPorVendedor(_allRows) : _allRows;
            renderizar(filtrarLocal(filas));
        });

        // Inject cedula from URL query param
        const cedula = new URLSearchParams(window.location.search).get('cedula');
        if (cedula) {
            document.getElementById('buscador').value = cedula;
            // Re-filter after data loads
            const filas = _agrupado ? agruparPorVendedor(_allRows) : _allRows;
            renderizar(filtrarLocal(filas));
        }
    }

    // ============ Custom Dropdown ============

    let _cpVendorActivo = 0;
    let _cpBtnActivo = null;

    window.abrirMenu = function(btn, vendedorId) {
        _cpVendorActivo = vendedorId;
        _cpBtnActivo = btn;
        const menu = document.getElementById('cpDropMenu');
        const rect = btn.getBoundingClientRect();
        menu.style.display = 'block';
        menu.style.left = (rect.left + rect.width) + 'px';
        menu.style.top = rect.top + 'px';
    };

    document.addEventListener('click', function(e) {
        if (!e.target.closest('#cpDropMenu')) {
            document.getElementById('cpDropMenu').style.display = 'none';
        }
    });

    document.getElementById('cpDropPago').addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('cpDropMenu').style.display = 'none';
        const btn = _cpBtnActivo;
        if (!btn) return;
        abrirPago(btn, _cpVendorActivo);
    });

    document.getElementById('cpDropDeuda').addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('cpDropMenu').style.display = 'none';
        const btn = _cpBtnActivo;
        if (!btn) return;
        verDeuda(btn, _cpVendorActivo);
    });

    document.getElementById('cpDropPremio').addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('cpDropMenu').style.display = 'none';
        const btn = _cpBtnActivo;
        if (!btn) return;
        abrirPremio(btn, _cpVendorActivo);
    });

    // ============ Solicitud de Premio ============
    let _cpPremioGananciaActual = 0;

    function abrirPremio(btn, vendedorId) {
        const empresaId = document.getElementById('empresa').value;
        const tempId = document.getElementById('campaniaSelect').value;

        document.getElementById('cpPremioVendedorId').value = vendedorId;
        document.getElementById('cpPremioEmpresaId').value = empresaId;
        document.getElementById('cpPremioTempId').value = tempId;

        document.getElementById('cpPremioLoading').style.display = '';
        document.getElementById('cpPremioContent').style.display = 'none';
        document.getElementById('cpPremioFooter').style.display = 'none';

        const modal = new bootstrap.Modal(document.getElementById('cpPremioModal'));
        modal.show();

        fetch(`${BASE}api/control-pagos/premio-info?empresa_id=${empresaId}&temporada_id=${tempId}&vendedor_id=${vendedorId}`)
            .then(r => r.json())
            .then(json => {
                if (!json.value) {
                    document.getElementById('cpPremioLoading').innerHTML = '<p class="text-danger">' + (json.message || 'Error al cargar') + '</p>';
                    return;
                }
                const d = json.data;
                _cpPremioGananciaActual = parseFloat(d.ganancia_total) || 0;

                document.getElementById('cpPremioVendedorNombre').textContent = d.vendedor_nombre || 'Desconocido';
                document.getElementById('cpPremioEmpresaNombre').textContent = d.empresa_nombre || 'Empresa';
                document.getElementById('cpPremioGananciaTotal').textContent = '$' + _cpPremioGananciaActual.toFixed(2);
                document.getElementById('cpPremioAsignaciones').textContent = d.total_asignaciones;

                const select = document.getElementById('cpPremioSelect');
                select.innerHTML = '<option value="">Seleccione un premio...</option>';
                (d.premios || []).forEach(p => {
                    select.innerHTML += `<option value="${p.id}" data-valor="${p.valor}">${p.nombre} ($${parseFloat(p.valor).toFixed(2)})</option>`;
                });

                // Reset validations
                select.value = '';
                document.getElementById('cpPremioWarning').style.display = 'none';
                document.getElementById('cpPremioSubmitBtn').disabled = false;

                document.getElementById('cpPremioLoading').style.display = 'none';
                document.getElementById('cpPremioContent').style.display = '';
                document.getElementById('cpPremioFooter').style.display = '';
            })
            .catch(() => {
                document.getElementById('cpPremioLoading').innerHTML = '<p class="text-danger">Error de conexión.</p>';
            });
    }

    document.getElementById('cpPremioSelect').addEventListener('change', function() {
        const option = this.options[this.selectedIndex];
        if (!option.value) {
            document.getElementById('cpPremioWarning').style.display = 'none';
            document.getElementById('cpPremioSubmitBtn').disabled = false;
            return;
        }
        const valorPremio = parseFloat(option.getAttribute('data-valor')) || 0;
        if (valorPremio > _cpPremioGananciaActual) {
            document.getElementById('cpPremioWarning').style.display = '';
            document.getElementById('cpPremioSubmitBtn').disabled = true;
        } else {
            document.getElementById('cpPremioWarning').style.display = 'none';
            document.getElementById('cpPremioSubmitBtn').disabled = false;
        }
    });

    document.getElementById('cpPremioForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        const btn = document.getElementById('cpPremioSubmitBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Procesando...';

        try {
            const res = await fetch(BASE + 'api/control-pagos/solicitar-premio', {
                method: 'POST',
                body: fd
            });
            const json = await res.json();
            if (json.value) {
                Swal.fire({
                    icon: 'success',
                    title: 'Premio asignado',
                    text: 'La ganancia ha sido descontada exitosamente.',
                    timer: 2000,
                    showConfirmButton: false
                });
                bootstrap.Modal.getInstance(document.getElementById('cpPremioModal')).hide();
                cargarTabla();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: json.message
                });
            }
        } catch (e) {
            Swal.fire('Error', 'Problema de red o servidor.', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = 'Asignar Premio';
        }
    });

    // ============ Cargar Pago ============

    function abrirPago(btn, vendedorId) {
        const empresaId = document.getElementById('empresa').value;
        const tempId = document.getElementById('campaniaSelect').value;
        const vendedorNombre = btn.closest('tr').querySelector('td:nth-child(1)').textContent.trim().split('\n')[0];

        document.getElementById('cpVendedorId').value = vendedorId;
        document.getElementById('cpEmpresaId').value = empresaId;
        document.getElementById('cpTempId').value = tempId;
        document.getElementById('cpVendedorLabel').textContent = vendedorNombre;
        document.getElementById('cpTipoPago').value = '';
        document.getElementById('cpMonto').value = '';
        document.getElementById('cpMontoBs').value = '';
        document.getElementById('cpTasaBcv').value = '<?= htmlspecialchars($_SESSION['bcv_valor'] ?? '', ENT_QUOTES) ?>';
        document.getElementById('cpTasaBcv').readOnly = true;
        document.getElementById('cpNumOp').value = '';
        document.getElementById('cpComprobante').value = '';
        document.getElementById('cpFechaPago').value = '';
        document.getElementById('cpCuotaGroup').style.display = 'none';
        document.getElementById('cpAbonoPreview').style.display = 'none';
        document.getElementById('cpFormCol').className = 'col-md-12';
        document.getElementById('cpModalDialog').classList.remove('modal-xl');
        document.getElementById('cpMontoLabel').textContent = 'Monto pagado (USD)';

        fetch(`${BASE}api/cargar-pago/cuotas?empresa_id=${empresaId}&temporada_id=${tempId}&vendedor_id=${vendedorId}`)
            .then(r => r.json())
            .then(json => {
                const cuotas = json.data?.cuotas || [];
                const deudaTotal = json.data?.deuda_total || 0;
                const deudaEfectiva = json.data?.deuda_efectiva ?? deudaTotal;
                _cpCuotas = cuotas;
                const descuento = deudaTotal - deudaEfectiva;
                document.getElementById('cpDeudaTotal').innerHTML = '$' + deudaEfectiva.toFixed(2) +
                    (descuento > 0 ? ` <small class="text-success fw-normal">(Desc. -$${descuento.toFixed(2)})</small>` : '');

                const sel = document.getElementById('cpCuotaSelect');
                sel.innerHTML = '<option value="">Seleccione cuota...</option>';
                cuotas.forEach(c => {
                    sel.innerHTML += `<option value="${c.id}">#${c.numero_cuota} - ${c.coleccion_nombre} - $${parseFloat(c.monto_pendiente).toFixed(2)}</option>`;
                });

                new bootstrap.Modal(document.getElementById('cpModal')).show();
            });
    }

    // BCV auto-calc: BS / tasa = USD
    function actualizarUsdDesdeBs() {
        var bs = parseFloat(document.getElementById('cpMontoBs').value) || 0;
        var tasa = parseFloat(document.getElementById('cpTasaBcv').value) || 0;
        if (bs > 0 && tasa > 0) {
            document.getElementById('cpMonto').value = (bs / tasa).toFixed(2);
        } else {
            document.getElementById('cpMonto').value = '';
        }
        if (document.getElementById('cpTipoPago').value === 'abono') {
            simularAbonoPreview(parseFloat(document.getElementById('cpMonto').value) || 0);
        }
    }

    document.getElementById('cpMontoBs').addEventListener('input', actualizarUsdDesdeBs);
    document.getElementById('cpTasaBcv').addEventListener('input', actualizarUsdDesdeBs);

    // Edit tasa button: remove disabled on click, re-disable on blur if empty
    document.getElementById('btnEditTasa')?.addEventListener('click', function() {
        var inp = document.getElementById('cpTasaBcv');
        document.getElementById('cpTasaBcv').readOnly = false;
        inp.focus();
    });
    document.getElementById('cpTasaBcv')?.addEventListener('blur', function() {
        if (!this.value) this.setAttribute('disabled', '');
    });

    // ============ Estatus de Deuda ============

    function verDeuda(btn, vendedorId) {
        const empresaId = document.getElementById('empresa').value;
        const tempId = document.getElementById('campaniaSelect').value;
        const vendedorNombre = btn.closest('tr').querySelector('td:nth-child(1)').textContent.trim().split('\n')[0];

        document.getElementById('cpDeudaLoading').style.display = '';
        document.getElementById('cpDeudaContent').style.display = 'none';
        const modal = new bootstrap.Modal(document.getElementById('cpDeudaModal'));
        modal.show();

        fetch(`${BASE}api/cargar-pago/deuda?empresa_id=${empresaId}&temporada_id=${tempId}&vendedor_id=${vendedorId}`)
            .then(r => r.json())
            .then(json => {
                if (!json.value) {
                    document.getElementById('cpDeudaLoading').innerHTML = '<p class="text-danger">' + (json.message || 'Error al cargar') + '</p>';
                    return;
                }
                const d = json.data;
                console.log(d)
                const cuotas = d.cuotas || [];
                const total = parseFloat(d.total_deuda) || 0;
                const pagado = parseFloat(d.total_pagado) || 0;
                const pendiente = Math.max(0, parseFloat(d.pendiente) || 0);
                const totalGanancia = parseFloat(d.total_ganancia_vendedor) || 0;
                const pendienteEfectivo = d.pendiente_efectivo != null ? parseFloat(d.pendiente_efectivo) : Math.max(0, pendiente - totalGanancia);
                const pct = total > 0 ? (pagado / total * 100) : 0;

                document.getElementById('cpDeudaTotalLabel').textContent = '$' + total.toFixed(2);
                document.getElementById('cpDeudaPagadoLabel').textContent = '$' + pagado.toFixed(2);
                document.getElementById('cpDeudaPendienteLabel').textContent = '$' + pendienteEfectivo.toFixed(2);
                if (totalGanancia > 0) {
                    const descLabel = document.getElementById('cpDeudaDescuentoLabel');
                    descLabel.style.display = '';
                    document.getElementById('cpDeudaDescuentoMonto').textContent = totalGanancia.toFixed(2);
                }
                document.getElementById('cpDeudaProgresoLabel').textContent = pct.toFixed(1) + '%';
                const barra = document.getElementById('cpDeudaBarra');
                barra.style.width = pct + '%';
                barra.setAttribute('aria-valuenow', pct);
                barra.textContent = pct > 0 ? pct.toFixed(1) + '%' : '';

                // Determine last cuota per asignacion_id
                const lastCuotaPorAsignacion = {};
                cuotas.forEach(c => {
                    const aid = c.asignacion_id;
                    lastCuotaPorAsignacion[aid] = c;
                });

                const tbody = document.getElementById('cpDeudaTableBody');
                tbody.innerHTML = cuotas.map(c => {
                    const mp = parseFloat(c.monto_a_pagar) || 0;
                    const pp = parseFloat(c.monto_pagado) || 0;
                    const pe = parseFloat(c.monto_pendiente) || 0;
                    const gv = parseFloat(c.ganancia_vendedor) || 0;
                    const isLast = lastCuotaPorAsignacion[c.asignacion_id]?.id === c.id;
                    const tieneDescuento = isLast && gv > 0;
                    const badgeMap = {
                        realizado: '<span class="badge bg-success" title="REALIZADO">R</span>',
                        pendiente: '<span class="badge bg-secondary" title="PENDIENTE">P</span>',
                        vencido: '<span class="badge bg-danger" title="VENCIDO">V</span>',
                        dentro_de_margen: '<span class="badge bg-warning text-dark" title="MARGEN DE PAGO">M</span>',
                    };
                    const esPagada = c.estatus_pago === 'realizado';
                    return `<tr${tieneDescuento ? ' class="table-info"' : ''}${esPagada ? ' data-pagada="true" style="display:none"' : ''}>
                        <td class="fw-medium">${c.numero_cuota}</td>
                        <td>${c.coleccion_nombre || ''}</td>
                        <td>$${mp.toFixed(2)}</td>
                        <td>$${pp.toFixed(2)}</td>
                        <td>$${pe.toFixed(2)}${tieneDescuento ? `<br><small class="text-primary fw-bold">Efectivo: $${Math.max(0, pe - gv).toFixed(2)}</small>` : ''}</td>
                        <td>${tieneDescuento ? `<span class="badge bg-info text-dark">-$${gv.toFixed(2)}</span>` : '<span class="text-muted">—</span>'}</td>
                        <td>${badgeMap[c.estatus_pago] || c.estatus_pago}</td>
                    </tr>`;
                }).join('');

                // Comprobantes
                const comps = d.comprobantes || [];
                document.getElementById('cpDeudaCompCount').textContent = comps.length;
                const compBody = document.getElementById('cpDeudaCompBody');
                if (comps.length) {
                    compBody.innerHTML = comps.map(cp => {
                        const archivo = cp.comprobante ? cp.comprobante.split('/').pop() : '—';
                        const fecha = cp.fecha_pago_comprobante ? cp.fecha_pago_comprobante.slice(0, 10) : (cp.created_at ? cp.created_at.slice(0, 10) : '—');
                        const bs = parseFloat(cp.monto_bs || 0);
                        const tasa = parseFloat(cp.tasa_dia || 0);
                        return `<tr>
                            <td class="fw-medium">${cp.numero_cuota || '—'}</td>
                            <td>$${parseFloat(cp.monto || 0).toFixed(2)}</td>
                            <td>${bs > 0 ? 'Bs.' + bs.toFixed(2) : '—'}</td>
                            <td>${tasa > 0 ? tasa.toFixed(2) : '—'}</td>
                            <td>${cp.numero_operacion || '—'}</td>
                            <td><button type="button" class="btn btn-sm btn-outline-primary py-0" onclick="verComprobante(this)" data-comp=\'${JSON.stringify(cp).replace(/'/g,"&#39;")}\'><i class="bx bx-file me-1"></i>${archivo}</button></td>
                            <td class="text-nowrap">${fecha}</td>
                        </tr>`;
                    }).join('');
                } else {
                    compBody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-2">Sin comprobantes registrados.</td></tr>';
                }

                // Premios
                const premios = d.premios || [];
                document.getElementById('cpDeudaPremiosCount').textContent = premios.length;
                const premiosBody = document.getElementById('cpDeudaPremiosBody');
                if (premios.length) {
                    premiosBody.innerHTML = premios.map(pr => {
                        const fecha = pr.created_at ? pr.created_at.slice(0, 10) : '—';
                        const badgeClass = pr.status === 'entregado' ? 'bg-success' : 'bg-secondary';
                        return `<tr>
                            <td class="fw-medium">${pr.nombre || '—'}</td>
                            <td>$${parseFloat(pr.valor || 0).toFixed(2)}</td>
                            <td><span class="badge ${badgeClass} text-uppercase">${pr.status || 'pendiente'}</span></td>
                            <td class="text-nowrap">${fecha}</td>
                        </tr>`;
                    }).join('');
                } else {
                    premiosBody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-2">Sin premios asignados.</td></tr>';
                }

                document.getElementById('cpDeudaLoading').style.display = 'none';
                document.getElementById('cpDeudaContent').style.display = '';

                // Reset toggle state for paid rows
                var toggleEl = document.getElementById('cpTogglePagadas');
                if (toggleEl) toggleEl.checked = false;
            })
            .catch(() => {
                document.getElementById('cpDeudaLoading').innerHTML = '<p class="text-danger">Error de conexión.</p>';
            });
    }

    function simularAbonoPreview(monto) {
        const tbody = document.getElementById('cpAbonoPreviewBody');
        const preview = document.getElementById('cpAbonoPreview');
        if (!monto || monto <= 0) {
            preview.style.display = 'none';
            return;
        }

        preview.style.display = '';
        tbody.innerHTML = '';

        // Group by asignacion
        const grupos = {};
        _cpCuotas.filter(c => c.estatus_pago !== 'realizado').forEach(c => {
            const aid = c.asignacion_id;
            if (!grupos[aid]) grupos[aid] = {
                coleccion: c.coleccion_nombre || '',
                cuotas: []
            };
            grupos[aid].cuotas.push(c);
        });

        // Sort cuotas within each group by fecha_pago
        Object.values(grupos).forEach(g => g.cuotas.sort((a, b) => a.fecha_pago.localeCompare(b.fecha_pago)));
        const gruposOrd = Object.values(grupos).sort((a, b) => a.cuotas[0].fecha_pago.localeCompare(b.cuotas[0].fecha_pago));

        // Last cuota per asignacion (for discount)
        const lastByAsig = {};
        Object.values(grupos).forEach(g => {
            const cs = g.cuotas;
            lastByAsig[cs[0].asignacion_id] = cs[cs.length - 1];
        });

        // Pre-compute effective pending for each cuota
        const cuotaMap = {};
        let totalEfectivo = 0;
        let maxNum = 0;
        gruposOrd.forEach(g => {
            g.cuotas.forEach(c => {
                const pendienteReal = parseFloat(c.monto_pendiente || 0);
                const ganancia = parseFloat(c.ganancia_vendedor || 0);
                const esUltima = lastByAsig[c.asignacion_id]?.id === c.id;
                const descuento = (esUltima && ganancia > 0) ? Math.min(ganancia, pendienteReal) : 0;
                const pendienteEf = pendienteReal - descuento;
                cuotaMap[c.id] = {
                    pendienteReal,
                    descuento,
                    pendienteEf,
                    pagado: 0
                };
                totalEfectivo += pendienteEf;
                const n = parseInt(c.numero_cuota) || 0;
                if (n > maxNum) maxNum = n;
            });
        });

        // Distribute monto round-robin: for each cuota number, across all groups
        let restante = parseFloat(monto) || 0;
        for (let num = 1; num <= maxNum && restante > 0; num++) {
            for (let gi = 0; gi < gruposOrd.length && restante > 0; gi++) {
                const c = gruposOrd[gi].cuotas.find(x => (parseInt(x.numero_cuota) || 0) === num);
                if (!c) continue;
                const p = cuotaMap[c.id];
                if (!p || p.pagado >= p.pendienteEf) continue;
                const falta = p.pendienteEf - p.pagado;
                if (restante >= falta) {
                    p.pagado = p.pendienteEf;
                    restante -= falta;
                } else {
                    p.pagado += restante;
                    restante = 0;
                }
            }
        }

        // Render rows grouped by coleccion
        let rows = '';
        gruposOrd.forEach((g, gi) => {
            const coleccionLabel = g.coleccion || 'Colección';

            rows += `<tr style="background:#f8f9fa;border-top:${gi > 0 ? '2px solid #dee2e6' : '0'}">
                <td colspan="4" class="py-1">
                    <span class="fw-semibold small"><i class="bx bx-package me-1 text-muted"></i>${coleccionLabel}</span>
                </td>
            </tr>`;

            g.cuotas.forEach(c => {
                const p = cuotaMap[c.id];
                if (!p) return;
                let resultado, rowCls;
                if (p.pagado <= 0) {
                    resultado = '—';
                    rowCls = 'text-muted';
                } else if (p.pagado >= p.pendienteEf) {
                    resultado = 'Pagado';
                    rowCls = 'table-success';
                } else {
                    resultado = 'Parcial';
                    rowCls = 'table-warning';
                }

                const pendStr = p.descuento > 0 ?
                    `<span class="fw-medium"><s>$${p.pendienteReal.toFixed(2)}</s></span><br><span class="text-primary fw-semibold">$${p.pendienteEf.toFixed(2)}</span>` :
                    `<span class="fw-medium">$${p.pendienteReal.toFixed(2)}</span>`;
                const descStr = p.descuento > 0 ?
                    `<span class="badge bg-info text-dark" style="font-size:.7rem">-${p.descuento.toFixed(2)}</span>` :
                    '<span class="text-muted" style="font-size:.8rem">—</span>';
                const badgeCls = resultado === 'Pagado' ? 'badge bg-success bg-gradient' : resultado === 'Parcial' ? 'badge bg-warning text-dark bg-gradient' : 'badge bg-light text-muted';

                rows += `<tr class="${rowCls}">
                    <td class="py-1"><span class="fw-medium" style="font-size:.85rem">#${c.numero_cuota}</span><br><small class="text-muted">${c.fecha_pago}</small></td>
                    <td class="py-1">${pendStr}</td>
                    <td class="py-1 text-center">${descStr}</td>
                    <td class="py-1"><span class="${badgeCls}" style="font-size:.75rem">${resultado}</span></td>
                </tr>`;
            });
        });

        tbody.innerHTML = rows;

        // Legend
        const hasDiscount = [..._cpCuotas].some(c => {
            const g = parseFloat(c.ganancia_vendedor || 0);
            return c.estatus_pago !== 'realizado' && g > 0;
        });
        if (hasDiscount) {
            tbody.innerHTML += `<tr style="background:#f8f9fa;border-top:1px solid #dee2e6">
                <td colspan="4" class="py-1 small text-muted">
                    <i class="bx bx-info-circle me-1"></i>Montos tachados incluyen descuento por ganancia del vendedor. El valor en <span class="text-primary fw-semibold">violeta</span> es el efectivo a pagar.
                </td>
            </tr>`;
        }

        // Summary row at end
        const aplicado = Math.min(parseFloat(monto) || 0, totalEfectivo);
        const restanteDeuda = Math.max(0, totalEfectivo - aplicado);
        tbody.innerHTML += `<tr style="border-top:2px solid #dee2e6;background:#fff">
            <td class="py-2"><span class="fw-semibold">Total abonado</span></td>
            <td class="py-2"></td>
            <td class="py-2"></td>
            <td class="py-2"><span class="fw-bold fs-6 text-primary">$${aplicado.toFixed(2)}</span></td>
        </tr>
        <tr style="background:#fff">
            <td class="py-1 pb-2"><span class="text-muted">Restante por pagar</span></td>
            <td class="py-1 pb-2"></td>
            <td class="py-1 pb-2"></td>
            <td class="py-1 pb-2"><span class="fw-semibold text-danger">$${restanteDeuda.toFixed(2)}</span></td>
        </tr>`;
    }

    document.getElementById('cpTipoPago').addEventListener('change', function() {
        const val = this.value;
        const dialog = document.getElementById('cpModalDialog');
        const formCol = document.getElementById('cpFormCol');
        const preview = document.getElementById('cpAbonoPreview');

        document.getElementById('cpCuotaGroup').style.display = val === 'cuota_exacta' ? '' : 'none';
        if (val === 'total') {
            document.getElementById('cpMontoLabel').textContent = 'Monto pagado (USD) — Deuda total: ';
        } else {
            document.getElementById('cpMontoLabel').textContent = 'Monto pagado (USD)';
        }
        if (val === 'abono') {
            dialog.classList.add('modal-xl');
            formCol.className = 'col-md-5';
            preview.style.display = '';
            simularAbonoPreview(parseFloat(document.getElementById('cpMonto').value));
        } else {
            dialog.classList.remove('modal-xl');
            formCol.className = 'col-md-12';
            preview.style.display = 'none';
        }
    });

    document.getElementById('cpMonto').addEventListener('input', function() {
        if (document.getElementById('cpTipoPago').value === 'abono') {
            simularAbonoPreview(parseFloat(this.value) || 0);
        }
    });

    document.getElementById('cpForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        const res = await fetch(BASE + 'api/cargar-pago', {
            method: 'POST',
            body: fd
        });
        const json = await res.json();
        if (json.value) {
            Swal.fire({
                icon: 'success',
                title: 'Pago registrado',
                timer: 1500,
                showConfirmButton: false
            });
            bootstrap.Modal.getInstance(document.getElementById('cpModal')).hide();
            cargarTabla();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: json.message
            });
        }
    });

    document.addEventListener('DOMContentLoaded', init);

    // ============ Historial Tab ============
    let _historialCargado = false;

    document.querySelector('#tab-historial-btn').addEventListener('shown.bs.tab', function() {
        document.getElementById('cpFiltroBuscador').style.display = 'none';
        document.getElementById('cpBtnGroup').style.display = 'none';
        if (!_historialCargado) cargarHistorial();
    });

    document.querySelector('#tab-pagos-btn').addEventListener('shown.bs.tab', function() {
        document.getElementById('cpFiltroBuscador').style.display = '';
        document.getElementById('cpBtnGroup').style.display = '';
    });

    function _cpCompPill(c) {
        const archivo = c.comprobante ? c.comprobante.split('/').pop() : '—';
        const bs = parseFloat(c.monto_bs || 0);
        const extra = bs > 0 ? ' Bs.' + bs.toFixed(2) : '';
        return `<button type="button" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1 mb-1" style="border-radius:6px;font-size:.75rem" onclick="verComprobante(this)" data-comp=\'${JSON.stringify(c).replace(/'/g,"&#39;")}\'>
            <i class="bx bx-file"></i>${archivo}${extra}
        </button>`;
    }

    window.verComprobante = function(btn) {
        try {
            var c = JSON.parse(btn.getAttribute('data-comp'));
        } catch(e) { return; }
        var img = document.querySelector('#cpVerCompModal img');
        if (img) img.src = c.comprobante ? BASE + c.comprobante : '';
        var body = document.getElementById('cpVerCompBody');
        if (!body) return;
        var bs = parseFloat(c.monto_bs || 0);
        var tasa = parseFloat(c.tasa_dia || 0);
        var rows = [
            ['N° Operación', c.numero_operacion || '—'],
            ['Monto (USD)', '$' + parseFloat(c.monto || 0).toFixed(2)],
            ['Monto (BS)', bs > 0 ? 'Bs.' + bs.toFixed(2) : '—'],
            ['Tasa del día', tasa > 0 ? tasa.toFixed(2) : '—'],
        ];
        if (c.fecha_pago_comprobante) {
            rows.push(['Fecha', c.fecha_pago_comprobante.slice(0, 10)]);
        } else if (c.created_at) {
            rows.push(['Registrado', c.created_at.slice(0, 10)]);
        }
        if (c.numero_cuota) {
            rows.splice(0, 0, ['Cuota #', c.numero_cuota]);
        }
        body.innerHTML = rows.map(function(r) {
            return '<tr><td class="fw-semibold text-muted small text-nowrap" style="width:40%">' + r[0] + '</td><td>' + r[1] + '</td></tr>';
        }).join('');
        var modal = new bootstrap.Modal(document.getElementById('cpVerCompModal'));
        modal.show();
    };

    function _cpHistorialResumen(cuotas) {
        const totalPagado = cuotas.reduce((s, c) => s + parseFloat(c.monto_a_pagar || 0), 0);
        let totalComps = 0;
        cuotas.forEach(c => totalComps += (c.comprobantes || []).length);
        return `<div class="d-flex gap-3 flex-wrap">
            <span class="badge bg-success bg-gradient px-3 py-2 fs-6">${cuotas.length} cuota${cuotas.length !== 1 ? 's' : ''}</span>
            <span class="badge bg-primary bg-gradient px-3 py-2 fs-6">$${totalPagado.toFixed(2)}</span>
            <span class="badge bg-info bg-gradient px-3 py-2 fs-6">${totalComps} comprobante${totalComps !== 1 ? 's' : ''}</span>
        </div>`;
    }

    async function cargarHistorial() {
        const empresaId = document.getElementById('empresa').value;
        const tempId = document.getElementById('campaniaSelect').value;
        if (!empresaId || !tempId) return;

        const body = document.getElementById('cpHistorialBody');
        body.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><div class="text-muted mt-2">Cargando historial...</div></div>';

        try {
            const res = await fetch(`${BASE}api/control-pagos/historial?empresa_id=${empresaId}&temporada_id=${tempId}`);
            const json = await res.json();
            if (!json.value) {
                body.innerHTML = `<div class="alert alert-danger m-3">${json.message}</div>`;
                return;
            }
            _historialData = json.data || [];
            _historialCargado = true;
            renderHistorial();
        } catch (e) {
            body.innerHTML = '<div class="alert alert-danger m-3">Error de conexión.</div>';
        }
    }

    function renderHistorial() {
        var q = (document.getElementById('historialBuscador').value || '').toLowerCase().trim();
        var filtrados = _historialData.map(function(g) {
            var cuotas = (g.cuotas || []).map(function(c) {
                var comps = (c.comprobantes || []).filter(function(cp) {
                    if (!q) return true;
                    return (cp.numero_operacion || '').toLowerCase().includes(q);
                });
                return { cuota: c, comps: comps };
            });
            var hasMatch = q ? cuotas.some(function(x) { return x.comps.length > 0; }) : true;
            return { grupo: g, cuotas: cuotas, visible: hasMatch };
        });

        var body = document.getElementById('cpHistorialBody');
        var countEl = document.getElementById('historialCount');
        var visibleCount = filtrados.filter(function(f) { return f.visible; }).length;

        if (countEl) countEl.textContent = visibleCount + ' resultado' + (visibleCount !== 1 ? 's' : '');

        if (!_historialData.length) {
            body.innerHTML = '<div class="text-center py-5 text-muted"><i class="bx bx-receipt fs-1 d-block mb-2"></i>No hay pagos registrados para esta empresa y campaña.</div>';
            return;
        }

        if (!q && !_historialData.length) return;

        body.innerHTML = filtrados.map(function(f) {
            if (!f.visible) return '';
            var g = f.grupo;
            return '<div class="border rounded-3 mb-3 shadow-sm overflow-hidden" style="border-radius:10px!important">' +
                '<div class="d-flex align-items-center justify-content-between px-3 py-3" style="background:linear-gradient(135deg,#f8f9fa 0%,#fff 100%);border-bottom:1px solid #e9ecef">' +
                    '<div class="d-flex align-items-center gap-3">' +
                        '<div class="d-flex align-items-center justify-content-center rounded-circle" style="width:40px;height:40px;background:#7367f0;color:#fff;font-size:1.1rem">' +
                            '<i class="bx bx-package"></i>' +
                        '</div>' +
                        '<div>' +
                            '<div class="fw-semibold" style="font-size:.95rem">' + (g.coleccion_nombre || 'Colección') + '</div>' +
                            '<div class="small text-muted">' +
                                '<i class="bx bx-user me-1"></i>' + (g.vendedor_nombre || '—') +
                                (g.vendedor_cedula ? '<span class="ms-2">· ' + g.vendedor_cedula + '</span>' : '') +
                                (g.coleccion_tipo ? '<span class="ms-2 badge bg-light text-muted fw-normal">' + g.coleccion_tipo + '</span>' : '') +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                    _cpHistorialResumen(f.cuotas.map(function(x) { return x.cuota; })) +
                '</div>' +
                '<div class="p-0">' +
                    '<table class="table table-hover align-middle mb-0" style="font-size:.85rem">' +
                        '<thead class="table-light small text-muted text-uppercase" style="letter-spacing:.03rem;font-size:.7rem">' +
                            '<tr>' +
                                '<th style="width:60px" class="ps-3">#</th>' +
                                '<th style="width:110px">Pagado</th>' +
                                '<th>Monto</th>' +
                                '<th>Pagado</th>' +
                                '<th style="width:60px" class="text-center">Tiempo</th>' +
                                '<th>Comprobante(s)</th>' +
                            '</tr>' +
                        '</thead>' +
                        '<tbody>' +
                            f.cuotas.map(function(x) {
                                var c = x.cuota;
                                var comps = x.comps;
                                var at = c.pagado_a_tiempo == 1 ? '<span class="badge bg-soft-success text-success" style="font-size:.65rem"><i class="bx bx-check fs-6"></i></span>' : '';
                                return '<tr>' +
                                    '<td class="fw-semibold ps-3">' + (c.numero_cuota || '—') + '</td>' +
                                    '<td class="text-nowrap">' + (c.fecha_pago || '—') + '</td>' +
                                    '<td class="fw-semibold">$' + parseFloat(c.monto_a_pagar || 0).toFixed(2) + '</td>' +
                                    '<td class="fw-semibold text-success">$' + parseFloat(c.monto_pagado || 0).toFixed(2) + '</td>' +
                                    '<td class="text-center">' + at + '</td>' +
                                    '<td>' +
                                        '<div class="d-flex flex-wrap gap-1">' +
                                            (comps.length ? comps.map(_cpCompPill).join('') : '<span class="text-muted" style="font-size:.8rem">—</span>') +
                                        '</div>' +
                                    '</td>' +
                                '</tr>';
                            }).join('') +
                        '</tbody>' +
                    '</table>' +
                '</div>' +
            '</div>';
        }).join('') || '<div class="text-center py-5 text-muted"><i class="bx bx-search fs-1 d-block mb-2"></i>No se encontraron comprobantes con ese número de operación.</div>';
    }

    // Re-load historial when filters change (reset flag so data reloads next time tab opens)
    document.getElementById('empresa').addEventListener('change', () => _historialCargado = false);
    document.getElementById('campaniaSelect').addEventListener('change', () => _historialCargado = false);

    // Historial search filter
    document.getElementById('historialBuscador').addEventListener('input', function() {
        if (_historialCargado) renderHistorial();
    });

    // Delegated: toggle paid rows in deuda modal
    document.addEventListener('change', function(e) {
        if (e.target && e.target.id === 'cpTogglePagadas') {
            var show = e.target.checked;
            document.querySelectorAll('#cpDeudaTableBody tr[data-pagada]').forEach(function(tr) {
                tr.style.display = show ? '' : 'none';
            });
        }
    });
</script>