<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1" style="color: #4a5568;"><i class="bx bx-gift text-primary me-2"></i>Preferencias de Premios</h4>
            <p class="text-muted mb-0 small">Listado de premios pendientes por entregar a los vendedores.</p>
        </div>
    </div>

    <!-- Filtros y Búsqueda -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
        <div class="card-body p-3">
            <div class="row g-3 align-items-center">
                <div class="col-md-4">
                    <div class="input-group input-group-merge">
                        <span class="input-group-text bg-light border-0"><i class="bx bx-search text-muted"></i></span>
                        <input type="text" class="form-control bg-light border-0" id="ppSearchInput" placeholder="Buscar por nombre, cédula o premio...">
                    </div>
                </div>
                <div class="col-md-8 text-end">
                    <button class="btn btn-primary shadow-sm" onclick="cargarTabla()" style="border-radius: 8px;">
                        <i class="bx bx-refresh me-1"></i>Actualizar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla -->
    <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="ppTable">
                <thead class="bg-light text-muted" style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">
                    <tr>
                        <th class="ps-4 border-0">Vendedor</th>
                        <th class="border-0">Empresa / Temp</th>
                        <th class="border-0">Premio</th>
                        <th class="border-0 text-center">Estatus</th>
                        <th class="border-0">Fecha Solicitud</th>
                        <th class="pe-4 border-0 text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody id="ppTableBody" class="border-top-0">
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status"></div>
                            <div class="text-muted mt-2">Cargando datos...</div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    const BASE = '<?= BASE_URL ?>';
    let _allData = [];

    document.addEventListener('DOMContentLoaded', () => {
        cargarTabla();

        const cedula = new URLSearchParams(window.location.search).get('cedula');
        if (cedula) {
            document.getElementById('ppSearchInput').value = cedula;
        }

        document.getElementById('ppSearchInput').addEventListener('input', function(e) {
            renderTabla(e.target.value);
        });
    });

    async function cargarTabla() {
        const tbody = document.getElementById('ppTableBody');
        tbody.innerHTML = `<tr><td colspan="6" class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><div class="text-muted mt-2">Cargando datos...</div></td></tr>`;

        try {
            const res = await fetch(BASE + 'api/preferencias-premios');
            const json = await res.json();
            if (json.value) {
                _allData = json.data || [];
                renderTabla(document.getElementById('ppSearchInput').value);
            } else {
                tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-4">${json.message}</td></tr>`;
            }
        } catch (error) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-4">Error de conexión.</td></tr>`;
        }
    }

    function renderTabla(filtro = '') {
        const tbody = document.getElementById('ppTableBody');
        const search = filtro.toLowerCase().trim();

        let filtrados = _allData;
        if (search) {
            filtrados = _allData.filter(r =>
                (r.vendedor_nombres || '').toLowerCase().includes(search) ||
                (r.vendedor_cedula || '').toLowerCase().includes(search) ||
                (r.premio_nombre || '').toLowerCase().includes(search) ||
                (r.empresa_nombre || '').toLowerCase().includes(search)
            );
        }

        if (filtrados.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4"><i class="bx bx-info-circle fs-4 mb-2 d-block"></i>No se encontraron premios pendientes.</td></tr>`;
            return;
        }

        tbody.innerHTML = filtrados.map(r => {
            const fecha = r.fecha_solicitud ? r.fecha_solicitud.slice(0, 10) : '—';
            const nombreVendedor = `${r.vendedor_nombres || ''}`.trim();
            const valor = parseFloat(r.premio_valor || 0).toFixed(2);

            return `
                <tr>
                    <td class="ps-4">
                        <div class="d-flex align-items-center">
                            <div class="avatar-sm bg-soft-primary rounded-circle text-primary d-flex justify-content-center align-items-center me-3" style="width: 40px; height: 40px;">
                                <i class="bx bx-user fs-5"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-semibold">${nombreVendedor || 'Desconocido'}</h6>
                                <small class="text-muted">CI: ${r.vendedor_cedula || '—'}</small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="fw-medium">${r.empresa_nombre || '—'}</div>
                        <small class="text-muted">${r.temporada_nombre || '—'}</small>
                    </td>
                    <td>
                        <div class="fw-medium" style="color: #a594f9;"><i class="bx bx-gift me-1"></i>${r.premio_nombre || '—'}</div>
                        <small class="text-muted">$${valor}</small>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-${r.status == 'completado' ? 'info' : 'warning'} text-white text-uppercase shadow-sm" style="font-size: 0.75rem;">${r.status}</span>
                    </td>
                    <td class="text-muted">${fecha}</td>
                    <td class="pe-4 text-end">
                        <button class="btn btn-sm btn-success shadow-sm" onclick="entregarPremio(${r.id})" title="Marcar como entregado" style="border-radius: 6px;">
                            <i class="bx bx-check-circle me-1"></i>Entredo
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function entregarPremio(id) {
        Swal.fire({
            title: '¿Confirmar entrega?',
            text: "El premio se marcará como entregado y ya no aparecerá en esta lista.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, entregar',
            cancelButtonText: 'Cancelar'
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    const res = await fetch(BASE + 'api/preferencias-premios/' + id + '/entregar', {
                        method: 'POST'
                    });
                    const json = await res.json();
                    if (json.value) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Entregado!',
                            text: json.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                        cargarTabla();
                    } else {
                        Swal.fire('Error', json.message, 'error');
                    }
                } catch (error) {
                    Swal.fire('Error', 'Problema de conexión.', 'error');
                }
            }
        });
    }
</script>

<style>
    .bg-soft-primary {
        background-color: rgba(115, 103, 240, 0.1) !important;
    }

    .text-primary {
        color: #7367f0 !important;
    }
</style>