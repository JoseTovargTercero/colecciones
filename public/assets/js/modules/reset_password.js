import { showErrorToast, Toast } from "../helpers/helpers.js";

document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("formResetPassword");

  if (form) {
    form.addEventListener("submit", function (e) {
      e.preventDefault();

      const btn = this.querySelector('button[type="submit"]');
      const originalHtml = btn.innerHTML;
      const newPass = this.new_password.value.trim();
      const confirmPass = this.confirm_password.value.trim();

      if (newPass.length < 6) {
        showErrorToast({ message: "La contraseña debe tener al menos 6 caracteres." });
        return;
      }

      if (newPass !== confirmPass) {
        showErrorToast({ message: "Las contraseñas no coinciden." });
        return;
      }

      btn.disabled = true;
      btn.innerHTML = `<div class="spinner-sm"></div><span>Restableciendo...</span>`;

      const params = new URLSearchParams(window.location.search);
      const token = params.get("token_reset");

      const payload = {
        new_password: newPass,
        token: token,
      };

      $.ajax({
        url: baseUrl + "api/recovery/update-password",
        method: "POST",
        contentType: "application/json",
        data: JSON.stringify(payload),
        success: function (response) {
          if (response.value) {
            Toast.fire({
              icon: "success",
              title: response.message,
            }).then(() => {
              window.location.href = baseUrl + "login";
            });
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
