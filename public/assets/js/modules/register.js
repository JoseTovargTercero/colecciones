import { showErrorToast, Toast } from "../helpers/helpers.js";

document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("formRegister");

  if (form) {
    form.addEventListener("submit", function (e) {
      e.preventDefault();

      const nombre = this.nombre.value.trim();
      const email = this.email.value.trim();
      const telefono = this.telefono.value.trim();
      const contrasena = this.contrasena.value;
      const confirmacion = this.contrasena_confirm.value;

      if (!nombre || !email || !contrasena) {
        showErrorToast({ message: "Completa todos los campos obligatorios." });
        return;
      }

      if (contrasena.length < 6) {
        showErrorToast({ message: "La contraseña debe tener al menos 6 caracteres." });
        return;
      }

      if (contrasena !== confirmacion) {
        showErrorToast({ message: "Las contraseñas no coinciden." });
        return;
      }

      const btn = this.querySelector('button[type="submit"]');
      const originalHtml = btn.innerHTML;

      btn.disabled = true;
      btn.innerHTML = `<div class="spinner-sm"></div><span>Creando cuenta...</span>`;

      const payload = {
        nombre: nombre,
        email: email,
        contrasena: contrasena,
        telefono: telefono || null,
        nivel: 2,
      };

      $.ajax({
        url: baseUrl + "api/system_users",
        method: "POST",
        contentType: "application/json",
        data: JSON.stringify(payload),
        success: function (response) {
          if (response.value) {
            Toast.fire({ icon: "success", title: response.message }).then(
              () => {
                window.location.href = baseUrl + "login";
              },
            );
          } else {
            showErrorToast(response);
          }
        },
        error: function (xhr) {
          showErrorToast(xhr.responseJSON);
        },
        complete: function () {
          btn.disabled = false;
          btn.innerHTML = originalHtml;
        },
      });
    });
  }
});
