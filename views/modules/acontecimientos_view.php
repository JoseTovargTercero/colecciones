<?php
// views/modules/acontecimientos_view.php
?>
<div class="container-fluid">
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <?php if (tienePermiso('acontecimientos/crear')): ?>
                    <a href="<?= BASE_URL ?>acontecimientos/crear" class="btn btn-primary btn-sm">
                        <i class="mdi mdi-plus-circle me-1"></i> Crear Acontecimiento
                    </a>
                    <?php endif; ?>
                </div>
                <h4 class="page-title">Muro de Acontecimientos</h4>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row">
        <!-- Left Sidebar (Optional info or filters) -->
        <div class="col-xxl-3 col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">Información</h5>
                    <p class="text-muted">Bienvenido al muro de acontecimientos. Aquí podrás visualizar los eventos importantes de tu producción.</p>
                    <hr>
                    <?php if (tienePermiso('acontecimientos/crear')): ?>
                    <a href="<?= BASE_URL ?>acontecimientos/crear" class="btn btn-primary btn-sm w-100">
                        <i class="mdi mdi-plus-circle me-1"></i> Registrar Nuevo Evento
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Center Feed -->
        <div class="col-xxl-6 col-lg-8">
            <!-- Social Feed Container -->
            <div id="social-feed-container">
                <!-- Events will be loaded here dynamically -->
                <div class="text-center mt-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Sidebar -->
        <!-- <div class="col-xxl-3 col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">Actividad Reciente</h5>
                    <div class="inbox-widget">
                        <p class="text-muted text-center">No hay actividad reciente.</p>
                    </div>
                </div>
            </div>
        </div> -->
    </div>
</div>

<style>
/* Custom animations for acontecimiento cards */
@keyframes pulse {
  0%, 100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.02);
  }
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

@keyframes fadeOut {
  from {
    opacity: 1;
  }
  to {
    opacity: 0;
  }
}

@keyframes fadeInScale {
  from {
    opacity: 0.5;
    transform: scale(0.95);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

@keyframes fadeOutScale {
  from {
    opacity: 1;
    transform: scale(1);
  }
  to {
    opacity: 0.7;
    transform: scale(0.98);
  }
}

/* Smooth transitions for card elements */
.acontecimiento-card {
  transition: all 0.3s ease-in-out;
}

.acontecimiento-card:hover {
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
}

.status-badge {
  transition: all 0.3s ease-in-out;
}

.action-buttons-container {
  transition: opacity 0.3s ease-in-out;
}

/* Border animations */
.border-warning {
  border: 2px solid #ffc107 !important;
  transition: border 0.3s ease-in-out;
}

.border-success {
  border: 2px solid #28a745 !important;
  transition: border 0.3s ease-in-out;
}
</style>

<script>
    const baseUrl = "<?= BASE_URL ?>";
</script>

<!-- Module JS -->
<script type="module" src="<?= BASE_URL ?>public/assets/js/modules/acontecimientos_view.js"></script>