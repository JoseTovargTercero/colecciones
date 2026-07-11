import { showErrorToast, Toast } from "../helpers/helpers.js";

document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("formRegister");
  const phoneInput = document.getElementById("telefono");

  if (phoneInput) {
    phoneInput.addEventListener("input", function () {
      // 1. Primero, eliminamos todo lo que no sea dígito
      let cleanedValue = this.value.replace(/\D/g, "");

      // 2. Comprobamos si el primer dígito es '0'
      if (this.value.startsWith("0")) {
        // Mostramos el toast
        showErrorToast({ message: "El teléfono no puede comenzar con 0." });

        // Limpiamos el valor para eliminar el cero
        this.value = cleanedValue.replace(/^0+/, "");
      } else {
        // Si no empieza por 0, simplemente actualizamos el valor limpio
        this.value = cleanedValue;
      }
    });
  }

  if (form) {
    form.addEventListener("submit", function (e) {
      e.preventDefault();

      const nombre = this.nombre.value.trim();
      const email = this.email.value.trim();
      const telefono = this.telefono.value.trim();
      const tipo_usuario = this.tipo_usuario.value;
      const contrasena = this.contrasena.value;
      const confirmacion = this.contrasena_confirm.value;

      if (!nombre || !email || !contrasena || !tipo_usuario) {
        showErrorToast({ message: "Completa todos los campos obligatorios." });
        return;
      }

      if (contrasena.length < 6) {
        showErrorToast({
          message: "La contraseña debe tener al menos 6 caracteres.",
        });
        return;
      }

      if (contrasena !== confirmacion) {
        showErrorToast({ message: "Las contraseñas no coinciden." });
        return;
      }

      if (telefono) {
        if (!/^\d{10}$/.test(telefono)) {
          showErrorToast({
            message: "El teléfono debe tener exactamente 10 dígitos.",
          });
          return;
        }
        if (telefono[0] === "0") {
          showErrorToast({ message: "El teléfono no puede comenzar con 0." });
          return;
        }
      }
      const telefonoCompleto = telefono ? "+58" + telefono : null;

      const btn = this.querySelector('button[type="submit"]');
      const originalHtml = btn.innerHTML;

      btn.disabled = true;
      btn.innerHTML = `<div class="spinner-sm"></div><span>Creando cuenta...</span>`;

      const payload = {
        nombre: nombre,
        email: email,
        contrasena: contrasena,
        telefono: telefonoCompleto,
        tipo_usuario: tipo_usuario,
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
