<style>
    .deudas-header {
        margin-bottom: 1.5rem;
    }

    .deudas-header h4 {
        font-weight: 700;
        color: #1e1e3a;
        letter-spacing: -0.02em;
        margin-bottom: 0.25rem;
    }

    .deudas-header p {
        color: #8a8aa0;
        font-size: 0.875rem;
        margin-bottom: 0;
    }

    /* ===== Metric Cards ===== */
    .metric-card {
        position: relative;
        background: #fff;
        border: 1px solid #f0f0f5;
        border-radius: 12px;
        padding: 1.25rem 1.5rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        overflow: hidden;
    }

    .metric-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
    }

    .metric-card .metric-accent {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
    }

    .metric-card .metric-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        margin-bottom: 0.75rem;
    }

    .metric-card .metric-label {
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #8a8aa0;
        margin-bottom: 0.35rem;
    }

    .metric-card .metric-value {
        font-size: 1.6rem;
        font-weight: 800;
        letter-spacing: -0.02em;
        line-height: 1.2;
    }

    .metric-card .metric-sub {
        font-size: 0.75rem;
        color: #8a8aa0;
        margin-top: 0.2rem;
    }

    .metric-card .metric-sub.text-success {
        color: #71dd37;
    }

    .metric-card .metric-sub.text-warning {
        color: #ffab00;
    }

    .metric-primary .metric-accent {
        background: linear-gradient(90deg, #696cff, #8f93ff);
    }

    .metric-primary .metric-icon {
        background: rgba(105, 108, 255, 0.1);
        color: #696cff;
    }

    .metric-primary .metric-value {
        color: #1e1e3a;
    }

    .metric-success .metric-accent {
        background: linear-gradient(90deg, #71dd37, #94e86a);
    }

    .metric-success .metric-icon {
        background: rgba(113, 221, 55, 0.1);
        color: #71dd37;
    }

    .metric-warning .metric-accent {
        background: linear-gradient(90deg, #ffab00, #ffc44d);
    }

    .metric-warning .metric-icon {
        background: rgba(255, 171, 0, 0.1);
        color: #ffab00;
    }

    .metric-danger .metric-accent {
        background: linear-gradient(90deg, #ff3e1d, #ff6b4a);
    }

    .metric-danger .metric-icon {
        background: rgba(255, 62, 29, 0.1);
        color: #ff3e1d;
    }

    /* ===== Section Headers ===== */
    .section-header {
        display: flex;
        align-items: center;
        gap: 0.625rem;
        margin-bottom: 1rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid #f0f0f5;
    }

    .section-header h5 {
        font-weight: 700;
        font-size: 1rem;
        color: #1e1e3a;
        margin-bottom: 0;
    }

    .section-header .section-badge {
        font-size: 0.6875rem;
        font-weight: 600;
        padding: 0.15rem 0.6rem;
        border-radius: 20px;
        background: #f0f0ff;
        color: #696cff;
    }

    /* ===== Cuota Items ===== */
    .cuota-group {
        border: 1px solid #f0f0f5;
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 1rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
    }

    .cuota-group-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.875rem 1.25rem;
        background: #fafafe;
        border-bottom: 1px solid #f0f0f5;
        cursor: pointer;
        transition: background 0.15s;
    }

    .cuota-group-header:hover {
        background: #f5f5ff;
    }

    .cuota-group-header .empresa-info {
        display: flex;
        align-items: center;
        gap: 0.625rem;
    }

    .cuota-group-header .empresa-info i {
        font-size: 1.1rem;
        color: #696cff;
    }

    .cuota-group-header .empresa-info strong {
        font-size: 0.9rem;
        color: #1e1e3a;
    }

    .cuota-group-header .empresa-info small {
        font-size: 0.75rem;
        color: #8a8aa0;
    }

    .cuota-group-header .group-total {
        font-weight: 700;
        font-size: 0.9rem;
        color: #ff3e1d;
    }

    .cuota-group-header .chevron {
        font-size: 1.1rem;
        color: #8a8aa0;
        transition: transform 0.25s ease;
        margin-left: 0.75rem;
    }

    .cuota-group-header .chevron.open {
        transform: rotate(180deg);
    }

    .cuota-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.75rem 1.25rem;
        border-bottom: 1px solid #f5f5fa;
        transition: background 0.15s;
    }

    .cuota-row:last-child {
        border-bottom: none;
    }

    .cuota-row:hover {
        background: #fafafe;
    }

    .cuota-row .cuota-left {
        display: flex;
        align-items: center;
        gap: 1rem;
        flex: 1;
    }

    .cuota-row .cuota-num {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: #f0f0ff;
        color: #696cff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        font-weight: 700;
        flex-shrink: 0;
    }

    .cuota-row .cuota-meta {
        display: flex;
        flex-direction: column;
    }

    .cuota-row .cuota-meta .cuota-coleccion {
        font-size: 0.875rem;
        font-weight: 600;
        color: #1e1e3a;
    }

    .cuota-row .cuota-meta .cuota-fecha {
        font-size: 0.75rem;
        color: #8a8aa0;
    }

    .cuota-row .cuota-right {
        display: flex;
        align-items: center;
        gap: 1.25rem;
        flex-shrink: 0;
    }

    .cuota-row .cuota-monto {
        text-align: right;
    }

    .cuota-row .cuota-monto .monto-label {
        font-size: 0.6875rem;
        color: #8a8aa0;
    }

    .cuota-row .cuota-monto .monto-val {
        font-size: 0.9rem;
        font-weight: 700;
        color: #1e1e3a;
    }

    .cuota-row .cuota-monto .monto-pendiente {
        font-size: 0.75rem;
        color: #ff3e1d;
    }

    .badge-estatus {
        font-size: 0.6875rem;
        font-weight: 600;
        padding: 0.2rem 0.625rem;
        border-radius: 20px;
        text-transform: capitalize;
    }

    .badge-estatus.pendiente {
        background: #fff8e5;
        color: #b87c00;
    }

    .badge-estatus.vencido {
        background: #ffe8e5;
        color: #cc2e0f;
    }

    .badge-estatus.dentro_de_margen {
        background: #e5f0ff;
        color: #2a6fd4;
    }

    .badge-estatus.realizado {
        background: #e5f9e5;
        color: #1f8b1f;
    }

    /* ===== Pendiente Badge ===== */
    .pendiente-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: #fff3cd;
        color: #b87c00;
        font-size: 1rem;
        cursor: pointer;
        transition: background 0.15s, transform 0.15s;
        flex-shrink: 0;
    }

    .pendiente-badge:hover {
        background: #ffecb3;
        transform: scale(1.1);
    }

    /* ===== Comprobante Preview ===== */
    .comprobante-preview {
        max-width: 100%;
        max-height: 400px;
        border-radius: 10px;
        border: 1px solid #e0e0ea;
        cursor: pointer;
        transition: opacity 0.2s;
    }

    .comprobante-preview:hover {
        opacity: 0.85;
    }

    /* ===== Empty State ===== */
    .empty-state {
        text-align: center;
        padding: 3rem 1.5rem;
    }

    .empty-state .empty-icon {
        width: 80px;
        height: 80px;
        border-radius: 20px;
        background: linear-gradient(135deg, #e8ecff 0%, #f0f2ff 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.25rem;
        font-size: 2rem;
        color: #696cff;
    }

    .empty-state h6 {
        font-weight: 700;
        color: #1e1e3a;
        margin-bottom: 0.25rem;
    }

    .empty-state p {
        font-size: 0.875rem;
        color: #8a8aa0;
        max-width: 320px;
        margin: 0 auto;
    }

    /* ===== Error State ===== */
    .error-card {
        background: #fff;
        border: 1px solid #ffe8e5;
        border-radius: 12px;
        padding: 2rem;
        text-align: center;
    }

    .error-card i {
        font-size: 2.5rem;
        color: #ff3e1d;
        margin-bottom: 0.75rem;
    }

    .error-card h6 {
        font-weight: 700;
        color: #1e1e3a;
    }

    .error-card p {
        font-size: 0.875rem;
        color: #666680;
    }

    /* ===== Skeleton Loading ===== */
    @keyframes shimmer {
        0% {
            background-position: -200px 0;
        }

        100% {
            background-position: calc(200px + 100%) 0;
        }
    }

    .skeleton {
        background: linear-gradient(90deg, #f0f0f5 25%, #f8f8fe 50%, #f0f0f5 75%);
        background-size: 200px 100%;
        animation: shimmer 1.5s ease-in-out infinite;
        border-radius: 6px;
    }

    .skeleton-card {
        height: 120px;
        border-radius: 12px;
    }

    .skeleton-row {
        height: 56px;
        border-radius: 8px;
        margin-bottom: 0.5rem;
    }

    .skeleton-group {
        height: 48px;
        border-radius: 12px;
        margin-bottom: 0.75rem;
    }

    /* ===== Animations ===== */
    .fade-in-up {
        opacity: 0;
        transform: translateY(12px);
        animation: fadeInUp 0.4s ease forwards;
    }

    @keyframes fadeInUp {
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .fade-in-up:nth-child(2) {
        animation-delay: 0.06s;
    }

    .fade-in-up:nth-child(3) {
        animation-delay: 0.12s;
    }

    .fade-in-up:nth-child(4) {
        animation-delay: 0.18s;
    }
</style>

<div class="container-fluid">
    <div class="deudas-header">
        <h4><i class="bx bx-credit-card-front me-2" style="color:#696cff"></i>Tus Deudas</h4>
        <p>Resumen de tus cuotas pendientes y pagos registrados</p>
    </div>

    <!-- ===== Loading State ===== -->
    <div id="deudasLoading">
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="skeleton skeleton-card"></div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="skeleton skeleton-card"></div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="skeleton skeleton-card"></div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="skeleton skeleton-card"></div>
            </div>
        </div>
        <div class="skeleton skeleton-group mb-3"></div>
        <div class="skeleton skeleton-row"></div>
        <div class="skeleton skeleton-row"></div>
        <div class="skeleton skeleton-row"></div>
    </div>

    <!-- ===== Error State ===== -->
    <div id="deudasError" style="display:none"></div>

    <!-- ===== Content ===== -->
    <div id="deudasContent" style="display:none">


        <!-- Grupos por Gerente -->
        <div id="gruposContainer"></div>

    </div>
</div>

<!-- ===== Modal Cargar Pago ===== -->
<div class="modal fade" id="pagoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" id="pagoModalDialog">
        <div class="modal-content" style="border-radius:14px;border:none;box-shadow:0 16px 48px rgba(0,0,0,0.12)">
            <form id="pagoForm" enctype="multipart/form-data">
                <input type="hidden" name="empresa_id" id="pagoEmpresaId">
                <input type="hidden" name="temporada_id" id="pagoTemporadaId">
                <input type="hidden" name="vendedor_id" id="pagoVendedorId">
                <input type="hidden" name="cuota_id" id="pagoCuotaId">
                <div class="modal-header border-0 pb-0" style="padding:1.5rem 1.5rem 0">
                    <h5 class="modal-title fw-bold" style="color:#1e1e3a"><i class="bx bx-credit-card me-2" style="color:#696cff"></i>Cargar Pago</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding:1.25rem 1.5rem">
                    <div class="row">
                        <div class="col-md-12" id="pagoFormCol">
                            <p><strong>Vendedor:</strong> <span id="pagoVendedorLabel"></span></p>
                            <p><strong>Deuda total:</strong> <span id="pagoDeudaTotal">$0.00</span></p>
                            <hr>
                            <div class="mb-3">
                                <label for="pagoTipoPago" class="form-label" style="font-size:0.8rem;font-weight:600;color:#1e1e3a">Tipo de pago</label>
                                <select class="form-select" id="pagoTipoPago" name="tipo_pago" required style="border-color:#e0e0ea">
                                    <option value="">Seleccione...</option>
                                    <option value="abono">Abono</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="pagoMontoBs" class="form-label" style="font-size:0.8rem;font-weight:600;color:#1e1e3a">Monto pagado (BS)</label>
                                <input type="number" placeholder="Monto en Bolívares" step="0.01" class="form-control" id="pagoMontoBs" name="monto_bs" style="border-color:#e0e0ea">
                            </div>
                            <div class="mb-3">
                                <label for="pagoTasaBcv" class="form-label" style="font-size:0.8rem;font-weight:600;color:#1e1e3a">Tasa BCV (Bs./USD)</label>
                                <div class="input-group">
                                    <input type="number" step="0.01" class="form-control" id="pagoTasaBcv" name="tasa_dia" value="<?= htmlspecialchars($_SESSION['bcv_valor'] ?? '', ENT_QUOTES) ?>" readonly style="border-color:#e0e0ea">
                                    <button class="btn btn-outline-secondary" type="button" id="pagoBtnEditTasa" title="Editar tasa"><i class="bx bx-pencil"></i></button>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="pagoMonto" class="form-label" id="pagoMontoLabel" style="font-size:0.8rem;font-weight:600;color:#1e1e3a">Monto pagado (USD)</label>
                                <input type="number" placeholder="Monto en Dólares" step="0.01" class="form-control" id="pagoMonto" name="monto" required readonly style="border-color:#e0e0ea;font-weight:600">
                            </div>
                            <div class="mb-3">
                                <label for="pagoOperacion" class="form-label" style="font-size:0.8rem;font-weight:600;color:#1e1e3a">Número de operación</label>
                                <input type="text" class="form-control" id="pagoOperacion" name="numero_operacion" placeholder="Ej: 123456" style="border-color:#e0e0ea">
                            </div>
                            <div class="mb-3">
                                <label for="pagoFecha" class="form-label" style="font-size:0.8rem;font-weight:600;color:#1e1e3a">Fecha del pago</label>
                                <input type="date" class="form-control" id="pagoFecha" name="fecha_pago_comprobante" style="border-color:#e0e0ea">
                            </div>
                            <div class="mb-3">
                                <label for="pagoComprobante" class="form-label" style="font-size:0.8rem;font-weight:600;color:#1e1e3a">Comprobante (imagen)</label>
                                <input type="file" class="form-control" id="pagoComprobante" name="comprobante" accept="image/*" style="border-color:#e0e0ea;font-size:0.85rem">
                            </div>
                        </div>
                        <div class="col-md-7" id="pagoAbonoPreview" style="display:none">
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
                                    <tbody id="pagoAbonoPreviewBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0" style="padding:0 1.5rem 1.25rem">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius:10px;font-weight:600">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="pagoSubmitBtn" style="border-radius:10px;font-weight:600;padding:0.5rem 1.5rem">
                        <i class="bx bx-check-circle me-1"></i>Registrar pago
                    </button>
                </div>
                <div id="pagoFeedback" style="display:none;padding:0 1.5rem 1rem"></div>
            </form>
        </div>
    </div>
</div>

<!-- ===== Modal Ver Comprobante Pendiente ===== -->
<div class="modal fade" id="pendienteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius:14px;border:none;box-shadow:0 16px 48px rgba(0,0,0,0.12)">
            <div class="modal-header border-0 pb-0" style="padding:1.5rem 1.5rem 0">
                <h5 class="modal-title fw-bold" style="color:#1e1e3a"><i class="bx bx-time me-2" style="color:#ffab00"></i>Comprobante Pendiente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:1.25rem 1.5rem" id="pendienteModalBody">
                <div class="text-center py-4">
                    <div class="spinner-border spinner-border-sm text-primary"></div>
                    <p class="text-muted mt-2 mb-0">Cargando...</p>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0" style="padding:0 1.5rem 1.25rem">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius:10px;font-weight:600">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
    let _pagoCuotas = [];
    const _cuotaIdx = {};

    window.abrirPago = function(cuotaId) {
        const info = _cuotaIdx[cuotaId];
        if (!info) return;

        document.getElementById('pagoCuotaId').value = cuotaId;
        document.getElementById('pagoVendedorId').value = info.vendedor_id;
        document.getElementById('pagoEmpresaId').value = info.empresa_id;
        document.getElementById('pagoTemporadaId').value = info.temporada_id;
        document.getElementById('pagoVendedorLabel').textContent = info.vendedor_nombre;
        document.getElementById('pagoTipoPago').value = '';
        document.getElementById('pagoMonto').value = '';
        document.getElementById('pagoMontoBs').value = '';
        document.getElementById('pagoTasaBcv').value = '<?= htmlspecialchars($_SESSION['bcv_valor'] ?? '', ENT_QUOTES) ?>';
        document.getElementById('pagoTasaBcv').readOnly = true;
        document.getElementById('pagoOperacion').value = '';
        document.getElementById('pagoComprobante').value = '';
        document.getElementById('pagoFecha').value = '';
        document.getElementById('pagoAbonoPreview').style.display = 'none';
        document.getElementById('pagoFormCol').className = 'col-md-12';
        document.getElementById('pagoModalDialog').classList.remove('modal-xl');
        document.getElementById('pagoMontoLabel').textContent = 'Monto pagado (USD)';

        const BASE = '<?= BASE_URL ?>';
        fetch(BASE + 'api/cargar-pago/cuotas?empresa_id=' + info.empresa_id + '&temporada_id=' + info.temporada_id + '&vendedor_id=' + info.vendedor_id)
            .then(r => r.json())
            .then(json => {
                const cuotas = json.data?.cuotas || [];
                const deudaTotal = json.data?.deuda_total || 0;
                const deudaEfectiva = json.data?.deuda_efectiva ?? deudaTotal;
                _pagoCuotas = cuotas;
                const descuento = deudaTotal - deudaEfectiva;
                document.getElementById('pagoDeudaTotal').innerHTML = '$' + deudaEfectiva.toFixed(2) +
                    (descuento > 0 ? ' <small class="text-success fw-normal">(Desc. -$' + descuento.toFixed(2) + ')</small>' : '');

                new bootstrap.Modal(document.getElementById('pagoModal')).show();
            });
    };

    window.verPendiente = function(pendienteId, cuotaId) {
        const info = _cuotaIdx[cuotaId];
        const body = document.getElementById('pendienteModalBody');
        const BASE = '<?= BASE_URL ?>';

        if (!info || !info.pendiente_id) {
            body.innerHTML = `<div class="text-center py-4 text-muted"><i class="bx bx-error-circle" style="font-size:2rem"></i><p class="mt-2">Informaci\u00f3n no disponible.</p></div>`;
            new bootstrap.Modal(document.getElementById('pendienteModal')).show();
            return;
        }

        const imgUrl = info.pendiente_comprobante ? BASE + info.pendiente_comprobante : null;
        const monto = parseFloat(info.pendiente_monto || 0).toFixed(2);
        const operacion = info.pendiente_operacion || '—';
        const fecha = info.pendiente_fecha || '—';
        const creado = info.pendiente_created_at || '—';

        body.innerHTML = `
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="mb-3">
                        <span class="text-muted" style="font-size:0.75rem;font-weight:600;text-transform:uppercase;letter-spacing:0.03em">Monto</span>
                        <div style="font-size:1.3rem;font-weight:800;color:#1e1e3a">$${monto}</div>
                    </div>
                    <div class="mb-3">
                        <span class="text-muted" style="font-size:0.75rem;font-weight:600;text-transform:uppercase;letter-spacing:0.03em">N\u00famero de operaci\u00f3n</span>
                        <div style="font-size:0.95rem;font-weight:600;color:#1e1e3a;font-family:monospace">${operacion}</div>
                    </div>
                    <div class="mb-3">
                        <span class="text-muted" style="font-size:0.75rem;font-weight:600;text-transform:uppercase;letter-spacing:0.03em">Fecha del pago</span>
                        <div style="font-size:0.95rem;font-weight:600;color:#1e1e3a">${fecha}</div>
                    </div>
                    <div>
                        <span class="text-muted" style="font-size:0.75rem;font-weight:600;text-transform:uppercase;letter-spacing:0.03em">Solicitado el</span>
                        <div style="font-size:0.95rem;font-weight:600;color:#1e1e3a">${creado}</div>
                    </div>
                </div>
                <div class="col-md-6 d-flex align-items-center justify-content-center">
                    ${imgUrl ? `<img src="${imgUrl}" class="comprobante-preview" onclick="window.open(this.src)" alt="Comprobante">` : `<div class="text-muted text-center py-4"><i class="bx bx-image-alt" style="font-size:3rem"></i><p class="mt-2">Sin imagen</p></div>`}
                </div>
            </div>
            <div class="mt-3 pt-3 border-top d-flex align-items-center gap-2">
                <span class="badge bg-warning text-dark" style="font-size:0.75rem;padding:0.3rem 0.75rem"><i class="bx bx-time me-1"></i>Pendiente de aprobaci\u00f3n</span>
                <span class="text-muted" style="font-size:0.75rem">El gerente debe aprobar este comprobante para que se aplique el pago.</span>
            </div>
        `;
        new bootstrap.Modal(document.getElementById('pendienteModal')).show();
    };

    function actualizarUsdDesdeBs() {
        var bs = parseFloat(document.getElementById('pagoMontoBs').value) || 0;
        var tasa = parseFloat(document.getElementById('pagoTasaBcv').value) || 0;
        if (bs > 0 && tasa > 0) {
            document.getElementById('pagoMonto').value = (bs / tasa).toFixed(2);
        } else {
            document.getElementById('pagoMonto').value = '';
        }
        if (document.getElementById('pagoTipoPago').value === 'abono') {
            simularAbonoPreview(parseFloat(document.getElementById('pagoMonto').value) || 0);
        }
    }

    document.getElementById('pagoMontoBs').addEventListener('input', actualizarUsdDesdeBs);
    document.getElementById('pagoTasaBcv').addEventListener('input', actualizarUsdDesdeBs);

    document.getElementById('pagoBtnEditTasa')?.addEventListener('click', function() {
        var inp = document.getElementById('pagoTasaBcv');
        inp.readOnly = false;
        inp.focus();
    });
    document.getElementById('pagoTasaBcv')?.addEventListener('blur', function() {
        if (!this.value) this.readOnly = true;
    });

    document.getElementById('pagoTipoPago').addEventListener('change', function() {
        const val = this.value;
        const dialog = document.getElementById('pagoModalDialog');
        const formCol = document.getElementById('pagoFormCol');
        const preview = document.getElementById('pagoAbonoPreview');

        if (val === 'abono') {
            dialog.classList.add('modal-xl');
            formCol.className = 'col-md-5';
            preview.style.display = '';
            simularAbonoPreview(parseFloat(document.getElementById('pagoMonto').value));
        } else {
            dialog.classList.remove('modal-xl');
            formCol.className = 'col-md-12';
            preview.style.display = 'none';
        }
    });

    document.getElementById('pagoMonto').addEventListener('input', function() {
        if (document.getElementById('pagoTipoPago').value === 'abono') {
            simularAbonoPreview(parseFloat(this.value) || 0);
        }
    });

    document.getElementById('pagoForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('pagoSubmitBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Enviando...';

        const fb = document.getElementById('pagoFeedback');
        fb.style.display = 'none';

        const form = this;
        const fd = new FormData(form);

        try {


            const res = await fetch('<?= BASE_URL ?>api/cargar-pago-pendiente', {
                method: 'POST',
                body: fd
            });
            const json = await res.json();
            if (json.value) {
                bootstrap.Modal.getInstance(document.getElementById('pagoModal')).hide();
                Swal.fire({
                    icon: 'success',
                    title: 'Solicitud enviada',
                    text: 'Pendiente de aprobación.',
                    timer: 1500,
                    showConfirmButton: false
                });
                cargarDeudas();
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
            btn.innerHTML = '<i class="bx bx-check-circle me-1"></i>Registrar pago';
        }
    });

    function simularAbonoPreview(monto) {
        const tbody = document.getElementById('pagoAbonoPreviewBody');
        const preview = document.getElementById('pagoAbonoPreview');
        if (!monto || monto <= 0) {
            preview.style.display = 'none';
            return;
        }

        preview.style.display = '';
        tbody.innerHTML = '';

        const grupos = {};
        _pagoCuotas.filter(c => c.estatus_pago !== 'realizado').forEach(c => {
            const aid = c.asignacion_id;
            if (!grupos[aid]) grupos[aid] = {
                coleccion: c.coleccion_nombre || '',
                cuotas: []
            };
            grupos[aid].cuotas.push(c);
        });

        Object.values(grupos).forEach(g => g.cuotas.sort((a, b) => a.fecha_pago.localeCompare(b.fecha_pago)));
        const gruposOrd = Object.values(grupos).sort((a, b) => a.cuotas[0].fecha_pago.localeCompare(b.cuotas[0].fecha_pago));

        const lastByAsig = {};
        Object.values(grupos).forEach(g => {
            const cs = g.cuotas;
            lastByAsig[cs[0].asignacion_id] = cs[cs.length - 1];
        });

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
                    pendienteReal: pendienteReal,
                    descuento: descuento,
                    pendienteEf: pendienteEf,
                    pagado: 0
                };
                totalEfectivo += pendienteEf;
                const n = parseInt(c.numero_cuota) || 0;
                if (n > maxNum) maxNum = n;
            });
        });

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

        let rows = '';
        gruposOrd.forEach((g, gi) => {
            const coleccionLabel = g.coleccion || 'Colecci\u00f3n';
            rows += '<tr style="background:#f8f9fa;border-top:' + (gi > 0 ? '2px solid #dee2e6' : '0') + '">' +
                '<td colspan="4" class="py-1">' +
                '<span class="fw-semibold small"><i class="bx bx-package me-1 text-muted"></i>' + coleccionLabel + '</span>' +
                '</td></tr>';

            g.cuotas.forEach(c => {
                const p = cuotaMap[c.id];
                if (!p) return;
                let resultado, rowCls;
                if (p.pagado <= 0) {
                    resultado = '\u2014';
                    rowCls = 'text-muted';
                } else if (p.pagado >= p.pendienteEf) {
                    resultado = 'Pagado';
                    rowCls = 'table-success';
                } else {
                    resultado = 'Parcial';
                    rowCls = 'table-warning';
                }

                const pendStr = p.descuento > 0 ?
                    '<span class="fw-medium"><s>$' + p.pendienteReal.toFixed(2) + '</s></span><br><span class="text-primary fw-semibold">$' + p.pendienteEf.toFixed(2) + '</span>' :
                    '<span class="fw-medium">$' + p.pendienteReal.toFixed(2) + '</span>';
                const descStr = p.descuento > 0 ?
                    '<span class="badge bg-info text-dark" style="font-size:.7rem">-' + p.descuento.toFixed(2) + '</span>' :
                    '<span class="text-muted" style="font-size:.8rem">\u2014</span>';
                const badgeCls = resultado === 'Pagado' ? 'badge bg-success bg-gradient' : resultado === 'Parcial' ? 'badge bg-warning text-dark bg-gradient' : 'badge bg-light text-muted';
                rows += '<tr class="' + rowCls + '">' +
                    '<td class="py-1"><span class="fw-medium" style="font-size:.85rem">#' + c.numero_cuota + '</span><br><small class="text-muted">' + c.fecha_pago + '</small></td>' +
                    '<td class="py-1">' + pendStr + '</td>' +
                    '<td class="py-1 text-center">' + descStr + '</td>' +
                    '<td class="py-1"><span class="' + badgeCls + '" style="font-size:.75rem">' + resultado + '</span></td></tr>';
            });
        });
        tbody.innerHTML = rows;

        const hasDiscount = [..._pagoCuotas].some(c => {
            const g = parseFloat(c.ganancia_vendedor || 0);
            return c.estatus_pago !== 'realizado' && g > 0;
        });
        if (hasDiscount) {
            tbody.innerHTML += '<tr style="background:#f8f9fa;border-top:1px solid #dee2e6">' +
                '<td colspan="4" class="py-1 small text-muted">' +
                '<i class="bx bx-info-circle me-1"></i>Montos tachados incluyen descuento por ganancia del vendedor. El valor en <span class="text-primary fw-semibold">violeta</span> es el efectivo a pagar.' +
                '</td></tr>';
        }

        const aplicado = Math.min(parseFloat(monto) || 0, totalEfectivo);
        const restanteDeuda = Math.max(0, totalEfectivo - aplicado);
        tbody.innerHTML += '<tr style="border-top:2px solid #dee2e6;background:#fff">' +
            '<td class="py-2"><span class="fw-semibold">Total abonado</span></td>' +
            '<td class="py-2"></td>' +
            '<td class="py-2"></td>' +
            '<td class="py-2"><span class="fw-bold fs-6 text-primary">$' + aplicado.toFixed(2) + '</span></td></tr>' +
            '<tr style="background:#fff">' +
            '<td class="py-1 pb-2"><span class="text-muted">Restante por pagar</span></td>' +
            '<td class="py-1 pb-2"></td>' +
            '<td class="py-1 pb-2"></td>' +
            '<td class="py-1 pb-2"><span class="fw-semibold text-danger">$' + restanteDeuda.toFixed(2) + '</span></td></tr>';
    }

    async function cargarDeudas() {
        const loadingEl = document.getElementById('deudasLoading');
        const contentEl = document.getElementById('deudasContent');
        const errorEl = document.getElementById('deudasError');

        loadingEl.style.display = '';
        contentEl.style.display = 'none';
        errorEl.style.display = 'none';

        try {
            const res = await fetch('<?= BASE_URL ?>api/mis-deudas');
            const json = await res.json();

            loadingEl.style.display = 'none';

            if (!json.value) {
                errorEl.style.display = '';
                errorEl.innerHTML = `
                <div class="error-card">
                    <i class="bx bx-error-circle"></i>
                    <h6>No pudimos cargar tus deudas</h6>
                    <p>${json.message || 'Intenta de nuevo más tarde.'}</p>
                </div>`;
                return;
            }

            const d = json.data;
            contentEl.style.display = '';
            renderDeudas(d);

        } catch (e) {
            loadingEl.style.display = 'none';
            errorEl.style.display = '';
            errorEl.innerHTML = `
            <div class="error-card">
                <i class="bx bx-wifi-off"></i>
                <h6>Error de conexión</h6>
                <p>Verifica tu conexión e intenta de nuevo.</p>
            </div>`;
        }
    }

    document.addEventListener('DOMContentLoaded', cargarDeudas);

    function renderDeudas(d) {
        console.log(d)
        const grupos = d.grupos || [];

        if (grupos.length === 0) {
            document.getElementById('gruposContainer').innerHTML = `
            <div class="empty-state">
                <div class="empty-icon"><i class="bx bx-check-circle"></i></div>
                <h6>No tienes deudas pendientes</h6>
                <p>Todas tus cuotas están al día. Sigue así.</p>
            </div>`;
            return;
        }

        // ---- Render each gerente group ----
        const gc = document.getElementById('gruposContainer');
        gc.innerHTML = grupos.map((grp, gi) => {
            const r = grp.resumen;
            const cuotas = grp.cuotas || [];
            const comprobantes = grp.comprobantes || [];
            const premios = grp.premios || [];
            const gerGroupId = 'ger-' + gi;

            return `
        <!-- Grupo: ${escHtml(grp.gerente?.nombre || 'Sin gerente')} -->
        <div class="mb-4">
            <!-- Gerente Header (click to expand/collapse) -->
            <div class="d-flex align-items-center gap-3 p-3 rounded-3 mb-3 bg-primary-lighten" style="cursor:pointer" data-bs-toggle="collapse" data-bs-target="#${gerGroupId}" aria-expanded="false">
                <div class="bg-primary" style="width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.2rem;flex-shrink:0"><i class="bx bx-briefcase"></i></div>
                <div style="flex:1">
                    <div style="font-weight:600;font-size:0.8rem">Gerente / Administrador</div>
                    <div style="font-weight:700;font-size:0.95rem">${escHtml(grp.gerente?.nombre || '—')}</div>
                    <div style="font-size:0.8rem;">
                        <i class="bx bx-envelope me-1"></i>${escHtml(grp.gerente?.email || '—')}${grp.gerente?.telefono ? ' · <i class="bx bx-phone me-1"></i>' + escHtml(grp.gerente.telefono) : ''}
                    </div>
                </div>
                <div class="text-end" style="flex-shrink:0">
                    <div style="font-size:0.7rem;">Pendiente</div>
                    <div style="font-weight:800;font-size:1.1rem;color:#ff3e1d">$${Number(r.pendiente).toFixed(2)}</div>
                    <div style="font-size:0.7rem;"><i class="bx bx-chevron-down chevron"></i></div>
                </div>
            </div>

            <div class="collapse" id="${gerGroupId}">
                <!-- Summary Cards -->
                <div class="row g-3 mb-3 d-none">
                    <div class="col-4">
                        <div class="metric-card metric-primary" style="padding:0.75rem 1rem">
                            <div class="metric-accent"></div>
                            <div class="metric-label" style="font-size:0.65rem">Total</div>
                            <div class="metric-value" style="font-size:1.1rem">$${Number(r.total_deuda).toFixed(2)}</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="metric-card metric-success" style="padding:0.75rem 1rem">
                            <div class="metric-accent"></div>
                            <div class="metric-label" style="font-size:0.65rem">Pagado</div>
                            <div class="metric-value" style="font-size:1.1rem">$${Number(r.total_pagado).toFixed(2)}</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="metric-card ${r.pendiente_efectivo > 0 ? 'metric-danger' : 'metric-success'}" style="padding:0.75rem 1rem">
                            <div class="metric-accent"></div>
                            <div class="metric-label" style="font-size:0.65rem">Pend. Efectivo</div>
                            <div class="metric-value" style="font-size:1.1rem">$${Number(r.pendiente_efectivo).toFixed(2)}</div>
                        </div>
                    </div>
                </div>

                ${premios.length > 0 ? `
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="bx bx-gift" style="color:#a594f9;font-size:1rem"></i>
                    <span style="font-weight:600;font-size:0.85rem;color:#1e1e3a">Premios pendientes</span>
                    <span class="section-badge">${premios.length}</span>
                </div>
                <div class="row g-2 mb-3">
                    ${premios.map(p => `
                        <div class="col-sm-6 col-lg-4">
                            <div style="background:#fff;border:1px solid #f0f0f5;border-radius:10px;padding:0.75rem;box-shadow:0 1px 3px rgba(0,0,0,0.03)">
                                <div style="font-weight:600;font-size:0.8rem;color:#1e1e3a">${escHtml(p.nombre)}</div>
                                <div style="font-size:0.8rem;color:#666680">$${Number(p.valor).toFixed(2)}</div>
                                <span class="badge badge-estatus pendiente mt-1" style="font-size:0.65rem">${p.status}</span>
                            </div>
                        </div>
                    `).join('')}
                </div>` : ''}

                <!-- Cuotas por asignacion -->
                ${cuotas.length === 0 ? `
                <div style="text-align:center;padding:1.5rem;color:#8a8aa0;font-size:0.85rem">
                    <i class="bx bx-check-circle" style="font-size:1.5rem;color:#71dd37"></i>
                    <div class="mt-1">Sin cuotas pendientes con este gerente</div>
                </div>` : `
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bx bx-list-ul" style="color:#696cff;font-size:1rem"></i>
                        <span style="font-weight:600;font-size:0.85rem;color:#1e1e3a">Cuotas Pendientes</span>
                        <span class="section-badge">${cuotas.length}</span>
                    </div>
                </div>
                ${renderCuotasPorAsignacion(cuotas)}`}

                <!-- Comprobantes -->
                ${comprobantes.length > 0 ? `
                <div class="d-flex align-items-center gap-2 mt-3 mb-2">
                    <i class="bx bx-receipt" style="color:#71dd37;font-size:1rem"></i>
                    <span style="font-weight:600;font-size:0.85rem;color:#1e1e3a">Historial de Pagos</span>
                    <span class="section-badge">${comprobantes.length}</span>
                </div>
                <div style="border-radius:10px;overflow:hidden">
                    <table class="table table-sm mb-0" style="font-size:0.8rem">
                        <thead style="background:#fafafe">
                            <tr>
                                <th style="padding:0.5rem 0.75rem;font-weight:600;color:#1e1e3a"># Cuota</th>
                                <th style="padding:0.5rem 0.75rem;font-weight:600;color:#1e1e3a">Monto</th>
                                <th style="padding:0.5rem 0.75rem;font-weight:600;color:#1e1e3a">Operación</th>
                                <th style="padding:0.5rem 0.75rem;font-weight:600;color:#1e1e3a">Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${comprobantes.map(cp => `
                                <tr>
                                    <td style="padding:0.5rem 0.75rem;font-weight:500">Cuota #${cp.numero_cuota}</td>
                                    <td style="padding:0.5rem 0.75rem;font-weight:600;color:#71dd37">$${Number(cp.monto).toFixed(2)}</td>
                                    <td style="padding:0.5rem 0.75rem;color:#666680;font-family:monospace;font-size:0.75rem">${cp.numero_operacion || '—'}</td>
                                    <td style="padding:0.5rem 0.75rem;color:#666680">${cp.fecha_pago_comprobante || '—'}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>` : ''}
            </div>
        </div>`;
        }).join('');

        // Chevron rotation on gerente collapse
        document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(h => {
            h.addEventListener('click', () => {
                const chevron = h.querySelector('.chevron');
                if (chevron) chevron.classList.toggle('open');
            });
        });
    }

    function renderCuotasPorAsignacion(cuotas) {
        // Group by asignacion
        const groups = {};
        cuotas.forEach(c => {
            const key = c.asignacion_id;
            if (!groups[key]) groups[key] = {
                asignacion_id: c.asignacion_id,
                empresa_nombre: c.empresa_nombre,
                empresa_id: c.empresa_id,
                coleccion_nombre: c.coleccion_nombre,
                temporada_id: c.temporada_id,
                vendedor_nombre: c.vendedor_nombre || '',
                vendedor_id: c.vendedor_id || '',
                cuotas: [],
                total: 0
            };
            groups[key].cuotas.push(c);
            groups[key].total += parseFloat(c.monto_pendiente);

            _cuotaIdx[c.id] = {
                vendedor_id: c.vendedor_id || groups[key].vendedor_id,
                empresa_id: groups[key].empresa_id,
                temporada_id: groups[key].temporada_id,
                vendedor_nombre: groups[key].vendedor_nombre,
                pendiente_id: c.pendiente_id,
                pendiente_monto: c.pendiente_monto,
                pendiente_operacion: c.pendiente_operacion,
                pendiente_comprobante: c.pendiente_comprobante,
                pendiente_fecha: c.pendiente_fecha,
                pendiente_created_at: c.pendiente_created_at,
            };
        });

        return Object.values(groups).map((g, gi) => {
            const groupId = 'asig-' + gi + '-' + Math.random().toString(36).slice(2, 6);
            return `
        <div class="cuota-group mb-2">
            <div class="cuota-group-header" data-bs-toggle="collapse" data-bs-target="#${groupId}" aria-expanded="false">
                <div class="empresa-info">
                    <i class="bx bx-layer"></i>
                    <div>
                        <strong>${escHtml(g.coleccion_nombre)}</strong>
                        <small>${escHtml(g.empresa_nombre)} · ${g.cuotas.length} cuota(s)</small>
                    </div>
                </div>
                <div class="d-flex align-items-center">
                    <div class="group-total me-2">$${g.total.toFixed(2)}</div>
                    <i class="bx bx-chevron-down chevron"></i>
                </div>
            </div>
            <div class="collapse" id="${groupId}">
                ${g.cuotas.map(c => {
                    const est = c.estatus_pago || 'pendiente';
                    return `
                    <div class="cuota-row">
                        <div class="cuota-left">
                            <div class="cuota-num">${c.numero_cuota}</div>
                            <div class="cuota-meta">
                                <span class="cuota-coleccion">Cuota #${c.numero_cuota}</span>
                                <span class="cuota-fecha"><i class="bx bx-calendar me-1"></i>Vence: ${c.fecha_pago || '—'}</span>
                            </div>
                        </div>
                        <div class="cuota-right">
                            ${c.pendiente_id ? `<span class="badge bg-warning" onclick="window.verPendiente(${c.pendiente_id}, ${c.id})" title="Pendiente de aprobaci\u00f3n — haz clic para ver">
                                <i class="bx bx-time"></i> COMPROBANTE
                            </span>` : ''}
                            <div class="cuota-monto">
                                <div class="monto-label">Monto</div>
                                <div class="monto-val">$${Number(c.monto_a_pagar).toFixed(2)}</div>
                                <div class="monto-pendiente">Pend: $${Number(c.monto_pendiente).toFixed(2)}</div>
                            </div>
                            <span class="badge-estatus ${est}">${est.replace(/_/g, ' ')}</span>
                            <button class="btn btn-sm btn-outline-primary ms-2" style="border-radius:8px;font-size:0.7rem;font-weight:600" data-cuota-id="${c.id}" onclick="window.abrirPago(this.dataset.cuotaId)" title="Cargar pago">
                                <i class="bx bx-credit-card me-1"></i>Pagar
                            </button>
                        </div>
                    </div>`;
                }).join('')}
            </div>
        </div>`;
        }).join('');
    }

    function escHtml(s) {
        if (!s) return '';
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }
</script>