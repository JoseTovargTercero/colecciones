<?php

$userName = $_SESSION['nombre'] ?? 'Invitado';
$userType = $_SESSION['user_type'] ?? 'Usuario';
?>


<div class="navbar-custom">
    <ul class="list-unstyled topbar-menu float-end mb-0">

        <li class="notification-list" id="download-app-li" style="display: none;">
            <a class="nav-link" href="#" id="download-app-button" title="Descargar Aplicación">
                <i class="dripicons-download noti-icon"></i>
            </a>
        </li>
        <li class="dropdown notification-list">
            <a class="nav-link dropdown-toggle arrow-none" data-bs-toggle="dropdown" href="#" role="button"
                aria-haspopup="false" aria-expanded="false">
                <i class="dripicons-bell noti-icon"></i>
                <span class="badge noti-icon-badge" id="alert-count"></span>
            </a>
            <div class="dropdown-menu dropdown-menu-end dropdown-menu-animated dropdown-lg py-0"
                id="dropdown-container">
                <div class="p-2 border-top-0 border-start-0 border-end-0 border-dashed border">
                    <div class="row align-items-center">
                        <div class="col">
                            <h6 class="m-0 font-16 fw-semibold">
                                Notificaciones
                            </h6>
                        </div>
                        <div class="col-auto">
                            <a href="javascript:void(0);" id="clear-all-alerts"
                                class="color-5 text-decoration-underline">
                                <small>Marcar Como Leídas</small>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="p-2">
                    <div class="d-flex gap-1" id="notification-tabs" role="tablist">
                        <button class="btn btn-outline-primary btn-sm text-bold" id="all-tab"
                            type="button">Todas</button>
                        <button class="btn btn-outline-primary btn-sm text-bold active" id="unread-tab" type="button">No
                            leídas (<span id="unread-count">0</span>)</button>
                    </div>
                </div>

                <div class="px-1 overflow-auto" style="max-height: 300px;" data-simplebar="init"
                    id="alerts-scroll-container">
                    <div class="simplebar-content" style="padding: 0px 6px;" id="alerts-container">
                    </div>
                </div>

                <a href="notifications"
                    class="dropdown-item text-center color-3 notify-item border-top border-light py-2">
                    Ver notificaciones
                </a>
            </div>
        </li>

        <li class="dropdown notification-list">
            <a class="nav-link dropdown-toggle nav-user arrow-none me-0" data-bs-toggle="dropdown" href="#"
                role="button" aria-haspopup="false" aria-expanded="false">
                <span class="account-user-avatar">
                    <img src="<?= BASE_URL ?>public/assets/images/users/avatar-1.jpg" alt="user-image"
                        class="rounded-circle">
                </span>
                <span>
                    <span class="account-user-name"><?= $userName ?></span>
                    <span class="account-position"><?= $userType ?></span>
                </span>
            </a>
            <div class="dropdown-menu dropdown-menu-end dropdown-menu-animated topbar-dropdown-menu profile-dropdown">
                <div class=" dropdown-header noti-title">
                    <h6 class="text-overflow m-0">Bienvenido !</h6>
                </div>
                <a href="<?= BASE_URL ?>perfil" class="dropdown-item notify-item">
                    <i class="mdi mdi-account-circle me-1"></i>
                    <span>Mi perfil</span>
                </a>
                <a href="<?= BASE_URL ?>api/logout" class="dropdown-item notify-item">
                    <i class="mdi mdi-account-circle me-1"></i>
                    <span>Cerrar Sesión</span>
                </a>
            </div>
        </li>

    </ul>
    <button class="button-menu-mobile open-left">
        <i class="mdi mdi-menu"></i>
    </button>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        // 1. Detectar si es un dispositivo móvil
        const userAgent = navigator.userAgent;
        const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(userAgent);

        const downloadLi = document.getElementById('download-app-li');
        const downloadButton = document.getElementById('download-app-button');

        // 2. Si es móvil, mostrar el botón
        if (isMobile) {
            downloadLi.style.display = 'block'; // O 'list-item'

            // 3. Añadir el evento de clic
            downloadButton.addEventListener('click', function(e) {
                e.preventDefault(); // Evita que el enlace '#' navegue

                // 4. Preguntar al usuario con SweetAlert2
                Swal.fire({
                    title: '¿Descargar Aplicación?',
                    text: "Se descargará el archivo de instalación para Android.",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, descargar',
                    cancelButtonText: 'Cancelar',
                }).then((result) => {

                    // 5. Si el usuario confirma
                    if (result.isConfirmed) {

                        // --- ¡IMPORTANTE! ---
                        // 6. Coloca aquí la URL de tu archivo .apk en el servidor
                        const downloadUrl = '<?= BASE_URL ?>downloads/app.txt';

                        const isAndroid = /Android/i.test(userAgent);
                        const isIOS = /iPhone|iPad|iPod/i.test(userAgent);

                        // 7. Comprobar si es Android

                        // 8. Crear un enlace 'a' en memoria para forzar la descarga
                        // Esto funciona mejor que un simple window.location.href
                        const link = document.createElement('a');
                        link.href = downloadUrl;

                        // Opcional: puedes forzar el nombre del archivo
                        link.setAttribute('download', 'nombre-de-tu-app.apk');

                        // Añadir el enlace al cuerpo, simular clic y removerlo
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);


                    }
                });
            });
        }


        // Ocultar header al hacer scroll down y mostrar al hacer scroll up
        const navbar = document.querySelector('.navbar-custom');
        let lastScroll = 0;

        window.addEventListener('scroll', () => {
            const currentScroll = window.pageYOffset;

            // Siempre mostrar en scroll == 0
            if (currentScroll <= 0) {
                navbar.classList.remove('navbar-hidden');
                return;
            }

            // Si vamos hacia abajo → ocultar
            if (currentScroll > lastScroll) {
                navbar.classList.add('navbar-hidden');
            }
            // Si vamos hacia arriba → mostrar
            else {
                navbar.classList.remove('navbar-hidden');
            }

            lastScroll = currentScroll;
        });
    });
</script>