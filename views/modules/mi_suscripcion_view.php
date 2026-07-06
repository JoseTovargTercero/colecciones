<?php
require_once __DIR__ . '/../../models/SuscripcionModel.php';
$model = new SuscripcionModel();
$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    echo "<div class='alert alert-danger'>Sesión no válida.</div>";
    return;
}

$suscripcion = $model->activa($userId);
if (!$suscripcion) {
    // Si no está activa, busquemos si hay alguna pendiente o cancelada, o simplemente no tiene.
    $suscripcion = $model->pendiente($userId);
}

// Si no hay activa ni pendiente, obtenemos info general (probablemente trial vencido)
if (!$suscripcion) {
    $db = Database::getInstance();
    $sql = "SELECT s.*, p.precio_mensual, p.precio_anual 
            FROM suscripciones s
            JOIN configuracion_planes p ON p.id = s.plan_id
            WHERE s.usuario_id = ?
            ORDER BY s.id DESC LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('s', $userId);
    $stmt->execute();
    $suscripcion = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$hoy = new DateTime('today');
$fin = $suscripcion ? new DateTime($suscripcion['fecha_fin']) : new DateTime('today');
$dias_restantes = max(0, (int)$hoy->diff($fin)->days);

if ($suscripcion && $suscripcion['estatus'] === 'vencida') {
    $dias_restantes = 0;
}

$dias_totales = 30;
if ($suscripcion && $suscripcion['tipo_pago'] === 'anual') {
    $dias_totales = 365;
} elseif ($suscripcion && $suscripcion['tipo_pago'] === 'trial') {
    $dias_totales = 7;
}

$progreso = 0;
if ($dias_totales > 0) {
    $progreso = 100 - min(100, max(0, ($dias_restantes / $dias_totales) * 100));
}

$estado_color = '#10b981'; // success green
$estado_glow  = 'rgba(16, 185, 129, 0.4)';
$estado_texto = 'Activa';
$estado_icon  = 'mdi-check-decagram';
if ($suscripcion) {
    if ($suscripcion['estatus'] === 'pendiente') {
        $estado_color = '#f59e0b'; // warning amber
        $estado_glow  = 'rgba(245, 158, 11, 0.4)';
        $estado_texto = 'Pendiente por validación';
        $estado_icon  = 'mdi-clock-fast';
    } elseif ($suscripcion['estatus'] === 'vencida') {
        $estado_color = '#ef4444'; // danger red
        $estado_glow  = 'rgba(239, 68, 68, 0.4)';
        $estado_texto = 'Vencida';
        $estado_icon  = 'mdi-alert-octagon';
    } elseif ($suscripcion['estatus'] === 'cancelada') {
        $estado_color = '#64748b'; // secondary gray
        $estado_glow  = 'rgba(100, 116, 139, 0.4)';
        $estado_texto = 'Cancelada';
        $estado_icon  = 'mdi-close-circle-outline';
    }
}
?>

<style>
/* PREMIUM UI SCOPED STYLES */
.susc-wrapper {
    font-family: 'Inter', 'Outfit', sans-serif;
    color: #f8fafc;
    padding: 2rem 0;
}

.susc-card {
    background: linear-gradient(145deg, rgba(30, 41, 59, 0.8), rgba(15, 23, 42, 0.95));
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 24px;
    padding: 3rem;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255,255,255,0.02) inset;
    position: relative;
    overflow: hidden;
    transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    animation: fadeInSusc 0.6s ease-out forwards;
}

.susc-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 30px 60px -15px rgba(0, 0, 0, 0.6), 0 0 0 1px rgba(255,255,255,0.05) inset;
}

/* Background Glowing Orbs */
.susc-card::before, .susc-card::after {
    content: '';
    position: absolute;
    border-radius: 50%;
    filter: blur(60px);
    z-index: 0;
    opacity: 0.15;
    transition: all 1s ease;
}
.susc-card::before {
    width: 300px;
    height: 300px;
    background: #38bdf8;
    top: -100px;
    left: -100px;
}
.susc-card::after {
    width: 250px;
    height: 250px;
    background: #818cf8;
    bottom: -80px;
    right: -80px;
}
.susc-card:hover::before { opacity: 0.25; transform: scale(1.1); }
.susc-card:hover::after { opacity: 0.25; transform: scale(1.1); }

.susc-content {
    position: relative;
    z-index: 1;
}

.susc-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2.5rem;
}

.susc-badge-status {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1.25rem;
    border-radius: 50px;
    font-weight: 600;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255,255,255,0.1);
    box-shadow: 0 0 15px var(--status-glow);
    animation: pulseGlow 2.5s infinite alternate;
}

.susc-title-gradient {
    font-size: 2.8rem;
    font-weight: 800;
    margin: 0;
    line-height: 1.1;
    background: linear-gradient(135deg, #e0e7ff 0%, #818cf8 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    text-transform: uppercase;
    letter-spacing: -1px;
}

.susc-subtitle {
    color: #94a3b8;
    font-size: 1rem;
    font-weight: 500;
    margin-top: 0.5rem;
}

.susc-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
    margin-bottom: 3rem;
}

.susc-info-box {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: 16px;
    padding: 1.5rem;
    transition: all 0.3s ease;
}

.susc-info-box:hover {
    background: rgba(255, 255, 255, 0.05);
    border-color: rgba(255, 255, 255, 0.1);
    transform: translateY(-2px);
}

.susc-info-label {
    color: #64748b;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 600;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.4rem;
}

.susc-info-value {
    font-size: 1.4rem;
    font-weight: 700;
    color: #f1f5f9;
    margin: 0;
}

.susc-progress-container {
    margin-top: 1rem;
}

.susc-progress-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-bottom: 1rem;
}

.susc-days-left {
    font-size: 1.5rem;
    font-weight: 800;
    color: #ffffff;
}

.susc-days-left span {
    font-size: 0.9rem;
    color: #94a3b8;
    font-weight: 500;
}

.susc-progress-bar-bg {
    height: 12px;
    background: rgba(15, 23, 42, 0.6);
    border-radius: 10px;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.05);
    box-shadow: inset 0 2px 4px rgba(0,0,0,0.3);
}

.susc-progress-bar-fill {
    height: 100%;
    border-radius: 10px;
    position: relative;
    transition: width 1.5s cubic-bezier(0.1, 0.8, 0.3, 1);
}

/* Animated striped gradient for progress */
.susc-progress-active {
    background: linear-gradient(90deg, #3b82f6, #6366f1, #8b5cf6);
    background-size: 200% 100%;
    animation: gradientMove 3s ease infinite;
    box-shadow: 0 0 10px rgba(99, 102, 241, 0.5);
}

.susc-progress-danger {
    background: linear-gradient(90deg, #ef4444, #f43f5e, #e11d48);
    background-size: 200% 100%;
    animation: gradientMove 3s ease infinite;
    box-shadow: 0 0 10px rgba(239, 68, 68, 0.5);
}

.susc-footer-msg {
    text-align: center;
    color: #94a3b8;
    font-size: 0.95rem;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px dashed rgba(255,255,255,0.1);
}

.susc-action-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: linear-gradient(135deg, #3b82f6 0%, #4f46e5 100%);
    color: #ffffff !important;
    font-weight: 600;
    font-size: 1rem;
    padding: 0.8rem 2rem;
    border-radius: 50px;
    text-decoration: none;
    border: none;
    box-shadow: 0 10px 20px -5px rgba(79, 70, 229, 0.4);
    transition: all 0.3s ease;
    overflow: hidden;
    position: relative;
    z-index: 1;
}

.susc-action-btn::before {
    content: '';
    position: absolute;
    top: 0; left: 0; width: 100%; height: 100%;
    background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
    z-index: -1;
    transition: opacity 0.3s ease;
    opacity: 0;
}

.susc-action-btn:hover {
    transform: translateY(-3px) scale(1.02);
    box-shadow: 0 15px 25px -5px rgba(79, 70, 229, 0.6);
}

.susc-action-btn:hover::before {
    opacity: 1;
}

.susc-action-btn i {
    transition: transform 0.3s ease;
}
.susc-action-btn:hover i {
    transform: rotate(15deg) scale(1.1);
}

@keyframes fadeInSusc {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes pulseGlow {
    from { box-shadow: 0 0 5px var(--status-glow); }
    to { box-shadow: 0 0 15px var(--status-glow); }
}

@keyframes gradientMove {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

@media (max-width: 768px) {
    .susc-grid { grid-template-columns: 1fr; gap: 1rem; }
    .susc-card { padding: 2rem; }
    .susc-header { flex-direction: column; gap: 1rem; }
    .susc-title-gradient { font-size: 2.2rem; }
}
</style>

<div class="container-fluid susc-wrapper">
    <div class="row">
        <div class="col-12 text-end mb-4">
            <a href="<?= BASE_URL ?>suscripcion/plan" class="susc-action-btn">
                <i class="mdi mdi-rocket-launch"></i> Mejorar o Renovar Plan
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-8 col-lg-10 mx-auto">
            
            <?php if ($suscripcion): ?>
            <div class="susc-card" style="--status-glow: <?= $estado_glow ?>;">
                <div class="susc-content">
                    
                    <div class="susc-header">
                        <div>
                            <p class="susc-subtitle">Plan Actual</p>
                            <h1 class="susc-title-gradient"><?= htmlspecialchars($suscripcion['tipo_pago'] ?? 'Desconocido') ?></h1>
                        </div>
                        <div class="susc-badge-status" style="color: <?= $estado_color ?>; border-color: <?= $estado_color ?>;">
                            <i class="mdi <?= $estado_icon ?> fs-4"></i> <?= $estado_texto ?>
                        </div>
                    </div>

                    <div class="susc-grid">
                        <div class="susc-info-box">
                            <div class="susc-info-label">
                                <i class="mdi mdi-calendar-check text-primary"></i> Fecha de Inicio
                            </div>
                            <div class="susc-info-value">
                                <?= date('d M, Y', strtotime($suscripcion['fecha_inicio'])) ?>
                            </div>
                        </div>
                        <div class="susc-info-box">
                            <div class="susc-info-label">
                                <i class="mdi mdi-calendar-clock text-<?= $dias_restantes <= 3 ? 'danger' : 'success' ?>"></i> Vencimiento
                            </div>
                            <div class="susc-info-value" style="<?= $dias_restantes <= 3 ? 'color: #f87171;' : '' ?>">
                                <?= date('d M, Y', strtotime($suscripcion['fecha_fin'])) ?>
                            </div>
                        </div>
                    </div>

                    <div class="susc-progress-container">
                        <div class="susc-progress-header">
                            <div>
                                <h6 class="text-uppercase text-muted mb-1" style="font-size:0.75rem; font-weight:700; letter-spacing:1px;">Progreso del Ciclo</h6>
                            </div>
                            <div class="susc-days-left">
                                <?= $dias_restantes ?> <span>días restantes</span>
                            </div>
                        </div>
                        
                        <div class="susc-progress-bar-bg">
                            <div class="susc-progress-bar-fill <?= $dias_restantes <= 3 ? 'susc-progress-danger' : 'susc-progress-active' ?>" 
                                 style="width: 0%;" 
                                 id="suscProgressBar"></div>
                        </div>
                        
                        <div class="susc-footer-msg">
                            <?php if ($suscripcion['estatus'] === 'activa'): ?>
                                <i class="mdi mdi-shield-check-outline text-success"></i> Tu suscripción se encuentra operando con normalidad. Finalizará el <strong><?= date('d \\d\\e F, Y', strtotime($suscripcion['fecha_fin'])) ?></strong>.
                            <?php elseif ($suscripcion['estatus'] === 'vencida'): ?>
                                <i class="mdi mdi-alert-circle-outline text-danger"></i> <strong>Tu cuenta ha expirado.</strong> Renueva ahora para no perder acceso a tus herramientas.
                            <?php elseif ($suscripcion['estatus'] === 'pendiente'): ?>
                                <i class="mdi mdi-clock-outline text-warning"></i> Estamos procesando tu pago. Pronto tendrás acceso total a todas las funciones.
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </div>
            
            <script>
                // Animate progress bar on load
                setTimeout(() => {
                    const bar = document.getElementById('suscProgressBar');
                    if(bar) bar.style.width = '<?= $progreso ?>%';
                }, 300);
            </script>

            <?php else: ?>
            <div class="susc-card" style="text-align: center; padding: 4rem 2rem;">
                <div class="susc-content">
                    <div style="background: rgba(245, 158, 11, 0.1); width: 100px; height: 100px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 2rem;">
                        <i class="mdi mdi-card-bulleted-off-outline" style="font-size: 3rem; color: #fbbf24;"></i>
                    </div>
                    <h2 class="susc-title-gradient mb-3" style="font-size: 2rem;">Sin Suscripción</h2>
                    <p style="color: #94a3b8; font-size: 1.1rem; max-width: 500px; margin: 0 auto 2.5rem;">
                        Aún no cuentas con un plan activo o periodo de prueba registrado en tu cuenta. Explora nuestros planes y potencia tu negocio hoy mismo.
                    </p>
                    <a href="<?= BASE_URL ?>suscripcion/plan" class="susc-action-btn">
                        <i class="mdi mdi-flash"></i> Ver Planes Disponibles
                    </a>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>