import { showErrorToast, Toast } from '../helpers/helpers.js'

document.addEventListener('DOMContentLoaded', function () {
  const formLogin = document.getElementById('formLogin')

  if (formLogin) {
    formLogin.addEventListener('submit', function (e) {
      e.preventDefault()

      const btn = this.querySelector('button[type="submit"]')
      const originalHtml = btn.innerHTML

      btn.disabled = true
      btn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Ingresando...`

      const payload = {
        email: this.email.value.trim(),
        contrasena: this.contrasena.value.trim(),
      }

      $.ajax({
        url: baseUrl + 'api/system_users/login',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(payload),
        success: function (response) {
          if (response.value) {
            Toast.fire({ icon: 'success', title: response.message }).then(() => {
              window.location.href = baseUrl + 'perfil'
            })
          } else {
            showErrorToast(response)
          }
        },
        error: function (xhr) {
          showErrorToast(xhr.responseJSON)
        },
        complete: function () {
          btn.disabled = false
          btn.innerHTML = originalHtml
        },
      })
    })
  }
})
