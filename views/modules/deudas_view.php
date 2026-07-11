<style>
    :root {
        --d-pending: #ffab00;
        --d-overdue: #ff3e1d;
        --d-margin: #03c3ec;
        --d-paid: #71dd37;
    }

    .card {
        box-shadow: 0 2px 6px 0 rgba(67, 89, 113, 0.12);
        border: none;
        border-radius: 0.5rem;
    }

    .card-header {
        background-color: transparent;
        border-bottom: 0;
        padding: 1.25rem 1.5rem 0;
    }

    .card-body {
        padding: 1.5rem;
    }

    .avatar {
        position: relative;
        width: 2.375rem;
        height: 2.375rem;
        cursor: pointer;
    }

    .avatar .avatar-initial {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        text-transform: uppercase;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        background-color: #e1e4e8;
        font-weight: 600;
    }

    .bg-label-primary {
        background-color: #e7e7ff !important;
        color: #696cff !important;
    }

    .bg-label-secondary {
        background-color: #ebeef0 !important;
        color: #8592a3 !important;
    }

    .bg-label-success {
        background-color: #e8fadf !important;
        color: #71dd37 !important;
    }

    .bg-label-info {
        background-color: #d7f5fc !important;
        color: #03c3ec !important;
    }

    .bg-label-warning {
        background-color: #fff2d6 !important;
        color: #ffab00 !important;
    }

    .bg-label-danger {
        background-color: #ffe0db !important;
        color: #ff3e1d !important;
    }

    .avatar-xl {
        width: 3.5rem;
        height: 3.5rem;
    }

    .avatar-xl .avatar-initial {
        font-size: 1.5rem;
    }

    .collapse-chevron {
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .collapse-chevron[aria-expanded="true"] {
        transform: rotate(180deg);
    }

    .cuota-collapse {
        transition: transform 0.25s ease;
    }

    .cuota-collapse.open {
        transform: rotate(180deg);
    }

    .status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
        flex-shrink: 0;
    }

    .status-dot.pendiente {
        background: var(--d-pending);
    }

    .status-dot.vencido {
        background: var(--d-overdue);
    }

    .status-dot.dentro_de_margen {
        background: var(--d-margin);
    }

    .status-dot.realizado {
        background: var(--d-paid);
    }

    .badge-estatus {
        font-size: 0.6875rem;
        font-weight: 600;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        text-transform: capitalize;
    }

    .badge-estatus.pendiente {
        background: #fff2d6;
        color: #b87c00;
    }

    .badge-estatus.vencido {
        background: #ffe0db;
        color: #cc2e0f;
    }

    .badge-estatus.dentro_de_margen {
        background: #d7f5fc;
        color: #026a82;
    }

    .badge-estatus.realizado {
        background: #e8fadf;
        color: #1f8b1f;
    }

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

    .empty-state-icon {
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

    .skeleton {
        background: linear-gradient(90deg, #f0f0f5 25%, #f8f8fe 50%, #f0f0f5 75%);
        background-size: 200px 100%;
        animation: shimmer 1.5s ease-in-out infinite;
        border-radius: 6px;
    }

    @keyframes shimmer {
        0% {
            background-position: -200px 0;
        }

        100% {
            background-position: calc(200px + 100%) 0;
        }
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
        height: 56px;
        border-radius: 12px;
        margin-bottom: 0.75rem;
    }

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

    .fade-in-up:nth-child(5) {
        animation-delay: 0.24s;
    }

    .hover-lift {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .hover-lift:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(67, 89, 113, 0.15);
    }

    .divider-text {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        color: #a1acb8;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .divider-text::before,
    .divider-text::after {
        content: '';
        flex: 1;
        height: 1px;
        background: #e9ecef;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h4 class="page-title mb-0">
                    <i class="bx bx-credit-card-front me-2" style="color:#696cff"></i>Tus Deudas
                </h4>
                <p class="text-muted small mb-0">Resumen de cuotas pendientes y pagos registrados</p>
            </div>
        </div>
    </div>

    <div id="deudasLoading">
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="skeleton skeleton-card" style="height:80px"></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="skeleton skeleton-card" style="height:80px"></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="skeleton skeleton-card" style="height:80px"></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="skeleton skeleton-card" style="height:80px"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-body">
                <div class="skeleton skeleton-group"></div>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <div class="skeleton skeleton-row"></div>
                <div class="skeleton skeleton-row"></div>
                <div class="skeleton skeleton-row"></div>
            </div>
        </div>
    </div>

    <div id="deudasError" style="display:none"></div>

    <div id="deudasContent" style="display:none">
        <div id="statsRow" class="row g-3 mb-4"></div>
        <div id="gruposContainer"></div>
    </div>
</div>

<div class="modal fade" id="pagoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" id="pagoModalDialog">
        <div class="modal-content">
            <form id="pagoForm" enctype="multipart/form-data">
                <input type="hidden" name="empresa_id" id="pagoEmpresaId">
                <input type="hidden" name="temporada_id" id="pagoTemporadaId">
                <input type="hidden" name="vendedor_id" id="pagoVendedorId">
                <input type="hidden" name="cuota_id" id="pagoCuotaId">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-credit-card me-2" style="color:#696cff"></i>Cargar Pago</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12" id="pagoFormCol">
                            <div class="d-flex align-items-center gap-3 mb-3 p-3 rounded-3" style="background:#f8f9fe">
                                <span class="avatar-initial rounded bg-label-primary" style="width:40px;height:40px;display:flex;align-items:center;justify-content:center">
                                    <i class="bx bx-user"></i>
                                </span>
                                <div>
                                    <div class="fw-semibold" style="color:#435971" id="pagoVendedorLabel"></div>
                                    <div class="text-muted small">Deuda total: <span id="pagoDeudaTotal" class="fw-semibold">$0.00</span></div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="pagoTipoPago" class="form-label">Tipo de pago</label>
                                <select class="form-select" id="pagoTipoPago" name="tipo_pago" required>
                                    <option value="">Seleccione...</option>
                                    <option value="abono">Abono</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="pagoMontoBs" class="form-label">Monto pagado (BS)</label>
                                <input type="number" placeholder="Monto en Bolívares" step="0.01" class="form-control" id="pagoMontoBs" name="monto_bs">
                            </div>

                            <div class="mb-3">
                                <label for="pagoTasaBcv" class="form-label">Tasa BCV (Bs./USD)</label>
                                <div class="input-group">
                                    <input type="number" step="0.01" class="form-control" id="pagoTasaBcv" name="tasa_dia" value="<?= htmlspecialchars($_SESSION['bcv_valor'] ?? '', ENT_QUOTES) ?>" readonly>
                                    <button class="btn btn-outline-secondary" type="button" id="pagoBtnEditTasa"><i class="bx bx-pencil"></i></button>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="pagoMonto" class="form-label" id="pagoMontoLabel">Monto pagado (USD)</label>
                                <input type="number" placeholder="Monto en Dólares" step="0.01" class="form-control" id="pagoMonto" name="monto" required readonly style="font-weight:600">
                            </div>

                            <div class="mb-3">
                                <label for="pagoOperacion" class="form-label">Número de operación</label>
                                <input type="text" class="form-control" id="pagoOperacion" name="numero_operacion" placeholder="Ej: 123456">
                            </div>

                            <div class="mb-3">
                                <label for="pagoFecha" class="form-label">Fecha del pago</label>
                                <input type="date" class="form-control" id="pagoFecha" name="fecha_pago_comprobante">
                            </div>

                            <div class="mb-3">
                                <label for="pagoComprobante" class="form-label">Comprobante (imagen)</label>
                                <input type="file" class="form-control" id="pagoComprobante" name="comprobante" accept="image/*" style="font-size:0.85rem">
                            </div>
                        </div>

                        <div class="col-md-7" id="pagoAbonoPreview" style="display:none">
                            <div class="divider-text mb-3">Distribucion del abono</div>
                            <div style="max-height:420px;overflow-y:auto;border:1px solid #e9ecef;border-radius:8px">
                                <table class="table table-sm table-borderless mb-0">
                                    <thead class="table-light" style="position:sticky;top:0;z-index:2;">
                                        <tr>
                                            <th style="width:35%">Cuota / Fecha</th>
                                            <th style="width:30%">Por pagar</th>
                                            <th style="width:15%">Desc.</th>
                                            <th style="width:20%">Aplicacion</th>
                                        </tr>
                                    </thead>
                                    <tbody id="pagoAbonoPreviewBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="pagoSubmitBtn">
                        <i class="bx bx-check-circle me-1"></i>Registrar pago
                    </button>
                </div>
                <div id="pagoFeedback" style="display:none;padding:0 1.5rem 1rem"></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="pendienteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bx bx-time me-2" style="color:#ffab00"></i>Comprobante Pendiente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="pendienteModalBody">
                <div class="text-center py-4">
                    <div class="spinner-border spinner-border-sm text-primary"></div>
                    <p class="text-muted mt-2 mb-0">Cargando...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
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
            body.innerHTML = `<div class="text-center py-4 text-muted"><i class="bx bx-error-circle" style="font-size:2rem"></i><p class="mt-2">Informacion no disponible.</p></div>`;
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
                        <div style="font-size:1.3rem;font-weight:800;color:#435971">$${monto}</div>
                    </div>
                    <div class="mb-3">
                        <span class="text-muted" style="font-size:0.75rem;font-weight:600;text-transform:uppercase;letter-spacing:0.03em">Numero de operacion</span>
                        <div style="font-size:0.95rem;font-weight:600;color:#435971;font-family:monospace">${operacion}</div>
                    </div>
                    <div class="mb-3">
                        <span class="text-muted" style="font-size:0.75rem;font-weight:600;text-transform:uppercase;letter-spacing:0.03em">Fecha del pago</span>
                        <div style="font-size:0.95rem;font-weight:600;color:#435971">${fecha}</div>
                    </div>
                    <div>
                        <span class="text-muted" style="font-size:0.75rem;font-weight:600;text-transform:uppercase;letter-spacing:0.03em">Solicitado el</span>
                        <div style="font-size:0.95rem;font-weight:600;color:#435971">${creado}</div>
                    </div>
                </div>
                <div class="col-md-6 d-flex align-items-center justify-content-center">
                    ${imgUrl ? `<img src="${imgUrl}" class="comprobante-preview" onclick="window.open(this.src)" alt="Comprobante">` : `<div class="text-muted text-center py-4"><i class="bx bx-image-alt" style="font-size:3rem"></i><p class="mt-2">Sin imagen</p></div>`}
                </div>
            </div>
            <div class="mt-3 pt-3 border-top d-flex align-items-center gap-2 flex-wrap">
                <span class="badge bg-label-warning" style="font-size:0.75rem;padding:0.35rem 0.75rem"><i class="bx bx-time me-1"></i>Pendiente de aprobacion</span>
                <span class="text-muted" style="font-size:0.75rem">El gerente debe aprobar este comprobante.</span>
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
                    text: 'Pendiente de aprobacion.',
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
            const coleccionLabel = g.coleccion || 'Coleccion';
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
                    '<span class="badge bg-label-info" style="font-size:.7rem">-' + p.descuento.toFixed(2) + '</span>' :
                    '<span class="text-muted" style="font-size:.8rem">\u2014</span>';
                const badgeCls = resultado === 'Pagado' ? 'badge bg-label-success' : resultado === 'Parcial' ? 'badge bg-label-warning' : 'badge bg-label-secondary';
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
                    <div class="card border border-danger">
                        <div class="card-body text-center py-5">
                            <i class="bx bx-error-circle" style="font-size:3rem;color:#ff3e1d"></i>
                            <h5 class="mt-3 fw-bold" style="color:#435971">No pudimos cargar tus deudas</h5>
                            <p class="text-muted mb-0">${json.message || 'Intenta de nuevo mas tarde.'}</p>
                        </div>
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
                <div class="card border border-danger">
                    <div class="card-body text-center py-5">
                        <i class="bx bx-wifi-off" style="font-size:3rem;color:#ff3e1d"></i>
                        <h5 class="mt-3 fw-bold" style="color:#435971">Error de conexion</h5>
                        <p class="text-muted mb-0">Verifica tu conexion e intenta de nuevo.</p>
                    </div>
                </div>`;
        }
    }

    document.addEventListener('DOMContentLoaded', cargarDeudas);

    function renderDeudas(d) {
        const grupos = d.grupos || [];

        renderStats(d);

        if (grupos.length === 0) {
            document.getElementById('gruposContainer').innerHTML = `
                <div class="card">
                    <div class="card-body text-center py-5">
                        <div class="empty-state-icon"><i class="bx bx-check-circle"></i></div>
                        <h5 class="fw-bold" style="color:#435971">No tienes deudas pendientes</h5>
                        <p class="text-muted mb-0">Todas tus cuotas estan al dia.</p>
                    </div>
                </div>`;
            return;
        }

        const gc = document.getElementById('gruposContainer');
        gc.innerHTML = grupos.map((grp, gi) => {
            const r = grp.resumen;
            const cuotas = grp.cuotas || [];
            const comprobantes = grp.comprobantes || [];
            const premios = grp.premios || [];
            const gerGroupId = 'ger-' + gi;

            const gerNombre = escHtml(grp.gerente?.nombre || 'Sin gerente');
            const gerEmail = escHtml(grp.gerente?.email || '—');
            const gerTelefono = grp.gerente?.telefono ? escHtml(grp.gerente.telefono) : null;

            const totalDeuda = Number(r.total_deuda).toFixed(2);
            const totalPagado = Number(r.total_pagado).toFixed(2);
            const pendienteEf = Number(r.pendiente_efectivo).toFixed(2);
            const pendienteDisp = Number(r.pendiente).toFixed(2);
            const pctPagado = totalDeuda > 0 ? Math.round((totalPagado / totalDeuda) * 100) : 0;

            return `
            <div class="card mb-4 fade-in-up">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3" style="cursor:pointer" data-bs-toggle="collapse" data-bs-target="#${gerGroupId}" aria-expanded="false">
                        <span class="avatar-initial rounded bg-label-primary" style="width:48px;height:48px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0">
                            <i class="bx bx-briefcase"></i>
                        </span>
                        <div class="flex-grow-1 min-width-0">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="fw-semibold" style="color:#435971;font-size:0.8rem">Gerente / Administrador</span>
                                ${r.pendiente_efectivo > 0 && r.pendiente_efectivo <= 50 ? '<span class="badge bg-label-warning" style="font-size:0.65rem"><i class="bx bx-time me-1"></i>Por vencer</span>' : ''}
                            </div>
                            <div class="fw-bold" style="color:#1e1e3a;font-size:1rem">${gerNombre}</div>
                            <div class="text-muted small text-truncate">
                                <i class="bx bx-envelope me-1"></i>${gerEmail}
                                ${gerTelefono ? '<span class="mx-1">·</span><i class="bx bx-phone me-1"></i>' + gerTelefono : ''}
                            </div>
                        </div>
                        <div class="text-end flex-shrink-0">
                            <div style="font-size:0.7rem;color:#8a8aa0">Pendiente</div>
                            <div class="fw-bold" style="font-size:1.25rem;color:#ff3e1d">$${pendienteDisp}</div>
                            <div style="font-size:0.8rem;margin-top:2px">
                                <i class="bx bx-chevron-down collapse-chevron" style="color:#a1acb8" aria-expanded="false"></i>
                            </div>
                        </div>
                    </div>

                    <div class="collapse" id="${gerGroupId}">
                        <div class="row g-3 mt-2 mb-3">
                            <div class="col-4">
                                <div class="rounded-3 p-3 h-100" style="background:#f8f9fe">
                                    <div class="text-muted small text-uppercase" style="font-size:0.7rem;letter-spacing:0.03em">Total</div>
                                    <div class="fw-bold fs-5 mt-1" style="color:#435971">$${totalDeuda}</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="rounded-3 p-3 h-100" style="background:#f1faf0">
                                    <div class="text-muted small text-uppercase" style="font-size:0.7rem;letter-spacing:0.03em">Pagado</div>
                                    <div class="fw-bold fs-5 mt-1" style="color:#1f8b1f">$${totalPagado}</div>
                                    <div style="margin-top:6px;height:3px;background:#e9ecef;border-radius:4px;overflow:hidden">
                                        <div style="height:100%;width:${pctPagado}%;background:#71dd37;border-radius:4px;transition:width 0.6s ease"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="rounded-3 p-3 h-100" style="background:${r.pendiente_efectivo > 0 ? '#fff5f3' : '#f1faf0'}">
                                    <div class="text-muted small text-uppercase" style="font-size:0.7rem;letter-spacing:0.03em">Pendiente</div>
                                    <div class="fw-bold fs-5 mt-1" style="color:${r.pendiente_efectivo > 0 ? '#ff3e1d' : '#1f8b1f'}">$${pendienteEf}</div>
                                </div>
                            </div>
                        </div>

                        ${premios.length > 0 ? `
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bx bx-gift" style="color:#a594f9;font-size:1rem"></i>
                            <span class="fw-semibold" style="font-size:0.85rem;color:#435971">Premios pendientes</span>
                            <span class="badge bg-label-primary rounded-pill" style="font-size:0.7rem">${premios.length}</span>
                        </div>
                        <div class="row g-2 mb-3">
                            ${premios.map(p => `
                                <div class="col-sm-6 col-lg-4">
                                    <div class="rounded-3 p-3" style="background:#fff;border:1px solid #f0f0f5">
                                        <div class="fw-semibold" style="font-size:0.8rem;color:#435971">${escHtml(p.nombre)}</div>
                                        <div style="font-size:0.8rem;color:#697a8d">$${Number(p.valor).toFixed(2)}</div>
                                        <span class="badge badge-estatus pendiente mt-1" style="font-size:0.65rem">${p.status}</span>
                                    </div>
                                </div>
                            `).join('')}
                        </div>` : ''}

                        ${cuotas.length === 0 ? `
                        <div class="text-center py-4">
                            <i class="bx bx-check-circle" style="font-size:1.5rem;color:#71dd37"></i>
                            <div class="text-muted mt-1" style="font-size:0.85rem">Sin cuotas pendientes con este gerente</div>
                        </div>` : `
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bx bx-list-ul" style="color:#696cff;font-size:1rem"></i>
                            <span class="fw-semibold" style="font-size:0.85rem;color:#435971">Cuotas Pendientes</span>
                            <span class="badge bg-label-primary rounded-pill" style="font-size:0.7rem">${cuotas.length}</span>
                        </div>
                        ${renderCuotasPorAsignacion(cuotas)}`}

                        ${comprobantes.length > 0 ? `
                        <div class="divider-text mt-4 mb-3">Historial de Pagos</div>
                        <div style="overflow-x:auto;border-radius:8px;border:1px solid #e9ecef">
                            <table class="table table-sm mb-0" style="font-size:0.8rem">
                                <thead style="background:#f8f9fe">
                                    <tr>
                                        <th style="padding:0.5rem 0.75rem;font-weight:600;color:#435971">Cuota</th>
                                        <th style="padding:0.5rem 0.75rem;font-weight:600;color:#435971">Monto</th>
                                        <th style="padding:0.5rem 0.75rem;font-weight:600;color:#435971">Operacion</th>
                                        <th style="padding:0.5rem 0.75rem;font-weight:600;color:#435971">Fecha</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${comprobantes.map(cp => `
                                        <tr>
                                            <td style="padding:0.5rem 0.75rem;font-weight:500">Cuota #${cp.numero_cuota}</td>
                                            <td style="padding:0.5rem 0.75rem;font-weight:600;color:#1f8b1f">$${Number(cp.monto).toFixed(2)}</td>
                                            <td style="padding:0.5rem 0.75rem;color:#697a8d;font-family:monospace;font-size:0.75rem">${cp.numero_operacion || '—'}</td>
                                            <td style="padding:0.5rem 0.75rem;color:#697a8d">${cp.fecha_pago_comprobante || '—'}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>` : ''}
                    </div>
                </div>
            </div>`;
        }).join('');

        document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(h => {
            h.addEventListener('click', () => {
                const chevron = h.querySelector('.collapse-chevron');
                if (chevron) {
                    const expanded = h.getAttribute('aria-expanded') === 'true';
                    chevron.setAttribute('aria-expanded', String(!expanded));
                }
            });
        });
    }

    function renderStats(d) {
        const statsEl = document.getElementById('statsRow');
        const grupos = d.grupos || [];

        let totalGanancia = 0,
            totalPendiente = 0,
            totalVencido = 0;

        grupos.forEach(g => {
            const r = g.resumen || {};
            totalGanancia += Number(r.total_ganancia_vendedor) || 0;
            totalPendiente += Number(r.pendiente_efectivo) || 0;
            (g.cuotas || []).forEach(c => {
                if (c.estatus_pago === 'vencido') totalVencido += Number(c.monto_pendiente) || 0;
            });
        });

        const stats = [{
                label: 'Ganancia',
                value: totalGanancia,
                icon: 'bx-trending-up',
                color: 'success',
                prefix: '$'
            },
            {
                label: 'Pendiente',
                value: totalPendiente,
                icon: 'bx-time',
                color: 'warning',
                prefix: '$'
            },
            {
                label: 'Vencido',
                value: totalVencido,
                icon: 'bx-error',
                color: 'danger',
                prefix: '$'
            },
        ];

        statsEl.innerHTML = stats.map((s, i) => `
            <div class="col-6 col-lg-4 fade-in-up">
                <div class="card h-100 hover-lift">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="avatar-initial rounded bg-label-${s.color}" style="width:42px;height:42px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0">
                            <i class="bx ${s.icon}"></i>
                        </span>
                        <div class="flex-grow-1 min-width-0">
                            <div class="text-muted small text-uppercase" style="font-size:0.7rem;letter-spacing:0.03em">${s.label}</div>
                            <div class="fw-bold" style="font-size:1.25rem;color:#435971">${s.prefix}${s.value.toFixed(2)}</div>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
    }

    function renderCuotasPorAsignacion(cuotas) {
        const groups = {};
        const lastByAsig = {};

        cuotas.forEach(c => {
            const key = c.empresa_id;
            if (!groups[key]) groups[key] = {
                empresa_nombre: c.empresa_nombre,
                empresa_id: c.empresa_id,
                colecciones: new Set(),
                cuotas: [],
                total: 0
            };
            groups[key].colecciones.add(c.coleccion_nombre);
            groups[key].cuotas.push(c);
            groups[key].total += parseFloat(c.monto_pendiente);

            if (!lastByAsig[c.asignacion_id] || (c.fecha_pago || '') > (lastByAsig[c.asignacion_id].fecha_pago || '')) {
                lastByAsig[c.asignacion_id] = c;
            }

            _cuotaIdx[c.id] = {
                vendedor_id: c.vendedor_id || '',
                empresa_id: c.empresa_id,
                temporada_id: c.temporada_id,
                vendedor_nombre: c.vendedor_nombre || '',
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
            const coleccionesStr = Array.from(g.colecciones).join(' | ');
            g.cuotas.sort((a, b) => {
                const na = parseInt(a.numero_cuota) || 0;
                const nb = parseInt(b.numero_cuota) || 0;
                return na - nb || (a.fecha_pago || '').localeCompare(b.fecha_pago || '');
            });
            return `
            <div class="rounded-3 mb-2" style="border:1px solid #e9ecef;overflow:hidden;background:#fff">
                <div class="d-flex align-items-center justify-content-between px-3 py-2" style="cursor:pointer;background:#f8f9fe;border-bottom:1px solid #e9ecef" data-bs-toggle="collapse" data-bs-target="#${groupId}" aria-expanded="false">
                    <div class="d-flex align-items-center gap-2 min-width-0">
                        <span class="avatar-initial rounded bg-label-primary" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;font-size:0.85rem;flex-shrink:0">
                            <i class="bx bx-buildings"></i>
                        </span>
                        <div class="min-width-0">
                            <div class="fw-semibold" style="font-size:0.85rem;color:#435971">${escHtml(coleccionesStr)}</div>
                            <div class="text-muted small">${escHtml(g.empresa_nombre)} · ${g.cuotas.length} cuota(s)</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                        <span class="fw-bold" style="color:#ff3e1d;font-size:0.9rem">$${g.total.toFixed(2)}</span>
                        <i class="bx bx-chevron-down cuota-collapse" style="color:#a1acb8;font-size:1.1rem"></i>
                    </div>
                </div>
                <div class="collapse" id="${groupId}">
                    ${(() => {
                        const statusOrder = ['vencido', 'pendiente', 'dentro_de_margen', 'realizado'];
                        const cuoGroups = {};
                        let groupIdx = 0;
                        g.cuotas.forEach(c => {
                            const n = c.numero_cuota || '0';
                            if (!cuoGroups[n]) cuoGroups[n] = { numero: n, cuotas: [], count: 0, totalMonto: 0, totalPend: 0, totalDesc: 0, earliestFecha: null, hasPendiente: false, worstIdx: 3, firstPendiente: null };
                            const gr = cuoGroups[n];
                            gr.count++;
                            gr.cuotas.push(c);
                            gr.totalMonto += Number(c.monto_a_pagar || 0);
                            gr.totalPend += Number(c.monto_pendiente || 0);
                            const esUltima = lastByAsig[c.asignacion_id]?.id === c.id;
                            const gan = parseFloat(c.ganancia_vendedor || 0);
                            const dcto = esUltima && gan > 0 ? Math.min(gan, parseFloat(c.monto_pendiente || 0)) : 0;
                            gr.totalDesc += dcto;
                            if (!gr.earliestFecha || (c.fecha_pago || '') < gr.earliestFecha) gr.earliestFecha = c.fecha_pago || '—';
                            if (c.pendiente_id) { gr.hasPendiente = true; if (!gr.firstPendiente) gr.firstPendiente = { id: c.pendiente_id, cuotaId: c.id }; }
                            const si = statusOrder.indexOf(c.estatus_pago || 'pendiente');
                            if (si < gr.worstIdx) gr.worstIdx = si;
                        });
                        const nums = Object.keys(cuoGroups).sort((a, b) => parseInt(a) - parseInt(b));
                        return nums.map((num, idx) => {
                            const gr = cuoGroups[num];
                            const est = statusOrder[gr.worstIdx];
                            const badgeLabel = est.replace(/_/g, ' ');
                            const montoEf = gr.totalMonto - gr.totalDesc;
                            const pendEf = gr.totalPend - gr.totalDesc;
                            const tieneDesc = gr.totalDesc > 0;
                            const pendIdData = gr.firstPendiente;
                            return `
                        <div class="d-flex align-items-center justify-content-between px-3 py-2" style="border-bottom:1px solid #f0f0f5;transition:background 0.15s">
                            <div class="d-flex align-items-center gap-3 flex-grow-1 min-width-0">
                                <span class="avatar-initial rounded bg-label-${est === 'realizado' ? 'success' : est === 'vencido' ? 'danger' : est === 'dentro_de_margen' ? 'info' : 'warning'}" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;font-size:0.75rem;font-weight:700;flex-shrink:0">
                                    ${num}
                                </span>
                                <div class="min-width-0">
                                    <div class="fw-semibold" style="font-size:0.85rem;color:#435971">Cuota #${num} ${gr.count > 1 ? `<span class="text-muted fw-normal" style="font-size:0.75rem">x ${gr.count}</span>` : ''}</div>
                                    <div class="d-flex align-items-center gap-2" style="font-size:0.75rem;color:#697a8d">
                                        <i class="bx bx-calendar"></i>
                                        <span>Vence: ${gr.earliestFecha}</span>
                                        <span class="status-dot ${est}"></span>
                                        <span class="badge badge-estatus ${est}">${badgeLabel}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-3 flex-shrink-0">
                                ${gr.hasPendiente && pendIdData ? `<span class="badge badge-estatus pendiente" style="cursor:pointer;font-size:0.65rem;padding:0.25rem 0.65rem;display:inline-flex;align-items:center;gap:0.3rem" onclick="window.verPendiente(${pendIdData.id}, ${pendIdData.cuotaId})" title="Pendiente de aprobacion">
                                    <i class="bx bx-time"></i>Pago Pendiente
                                </span>` : `<span style="display:inline-flex;align-items:center;width:82px;justify-content:center"></span>`}
                                <div class="text-end">
                                    ${tieneDesc ? `
                                    <div class="fw-bold" style="font-size:0.9rem;color:#435971">
                                        <span class="text-decoration-line-through text-muted" style="font-size:0.8rem">$${gr.totalMonto.toFixed(2)}</span>
                                        <span class="ms-1">$${montoEf.toFixed(2)}</span>
                                        <span class="badge bg-label-info" style="font-size:0.6rem;vertical-align:middle">-$${gr.totalDesc.toFixed(2)}</span>
                                    </div>
                                    <div style="font-size:0.7rem;color:#ff3e1d">
                                        Pend: <span class="text-decoration-line-through text-muted">$${gr.totalPend.toFixed(2)}</span>
                                        <span class="ms-1">$${pendEf.toFixed(2)}</span>
                                    </div>` : `
                                    <div class="fw-bold" style="font-size:0.9rem;color:#435971">$${gr.totalMonto.toFixed(2)}</div>
                                    <div style="font-size:0.7rem;color:#ff3e1d">Pend: $${gr.totalPend.toFixed(2)}</div>`}
                                </div>
                                <button class="btn btn-sm ${idx === 0 && gr.hasPendiente ? 'btn-outline-secondary' : 'btn-primary'}" style="border-radius:8px;font-size:0.7rem;font-weight:600;padding:0.3rem 0.75rem;${idx !== 0 ? 'visibility:hidden;pointer-events:none' : ''}" ${idx === 0 ? `data-cuota-id="${gr.cuotas[0].id}" onclick="window.abrirPago(this.dataset.cuotaId)"` : ''} ${idx === 0 && gr.hasPendiente ? 'disabled' : ''}>
                                    ${idx === 0 && gr.hasPendiente ? '<i class="bx bx-time me-1"></i>Pendiente' : '<i class="bx bx-credit-card me-1"></i>Pagar'}
                                </button>
                            </div>
                        </div>`;
                        }).join('');
                    })()}
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