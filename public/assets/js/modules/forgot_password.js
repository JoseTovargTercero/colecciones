import { showErrorToast, Toast } from "../helpers/helpers.js";

document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("formForgotPassword");

  if (form) {
    form.addEventListener("submit", function (e) {
      e.preventDefault();

      const btn = this.querySelector('button[type="submit"]');
      const originalHtml = btn.innerHTML;

      btn.disabled = true;
      btn.innerHTML = `<div class="spinner-sm"></div><span>Enviando...</span>`;

      const payload = {
        email: this.email.value.trim(),
      };

      $.ajax({
        url: baseUrl + "api/recovery/verify-email",
        method: "POST",
        contentType: "application/json",
        data: JSON.stringify(payload),
        success: function (response) {
          if (response.value) {
            document.getElementById("formForgotPassword").classList.add("hidden");
            document.getElementById("successState").classList.remove("hidden");
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
