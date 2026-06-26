import { showErrorToast, Toast } from '../helpers/helpers.js'

document.addEventListener('DOMContentLoaded', function () {
  // Configuración de Toast con SweetAlert2

  // Manejo del formulario de login
  const formLogin = document.getElementById('formLogin')

  if (formLogin) {
    formLogin.addEventListener('submit', function (e) {
      e.preventDefault() // Evitamos el envío tradicional del formulario

      const loginButton = this.querySelector('button[type="submit"]')
      const originalButtonHtml = loginButton.innerHTML

      // Deshabilitar botón y mostrar un spinner para feedback visual
      loginButton.disabled = true
      loginButton.innerHTML = `
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                Ingresando...
            `

      const formData = {
        email: this.email.value,
        contrasena: this.password.value, // El endpoint espera "contrasena"
      }

      $.ajax({
        url: baseUrl + 'api/system_users/login',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(formData),
        success: function (response) {
          if (response.value) {
            // Si el login es exitoso
            Toast.fire({
              icon: 'success',
              title: response.message,
            }).then(() => {
              // Redirigir al dashboard o a la página principal
              window.location.href = `${baseUrl}perfil` // Cambia '/dashboard' por tu ruta deseada
            })
          } else {
            // Si el backend devuelve un error conocido (value: false)
            showErrorToast(response)
          }
        },
        error: function (xhr) {
          // Si hay un error de red o un error HTTP (400, 401, 500)
          showErrorToast(xhr.responseJSON)
        },
        complete: function () {
          // Volver a habilitar el botón y restaurar su contenido original
          loginButton.disabled = false
          loginButton.innerHTML = originalButtonHtml
        },
      })
    })
  }

  // 1. Mostrar modal de "Olvidaste tu contraseña"
  const linkForgot = document.getElementById('link-forgot-password')
  const recoverModalEl = document.getElementById('recoverPasswordModal')
  let recoverModal
  if (recoverModalEl) {
    recoverModal = new bootstrap.Modal(recoverModalEl)
  }

  if (linkForgot && recoverModal) {
    linkForgot.addEventListener('click', function (e) {
      e.preventDefault()
      recoverModal.show()
    })
  }

  // 2. Si viene token_reset en la URL, abrir modal de cambio de contraseña
  const tokenReset = window.getQueryParam('token_reset') // Usa la función global
  const changeModalEl = document.getElementById('changePasswordModal')
  let changeModal
  if (changeModalEl) {
    changeModal = new bootstrap.Modal(changeModalEl)
  }

  if (tokenReset && changeModal) {
    window._resetToken = tokenReset // guardar token globalmente
    changeModal.show()
  }

  // 3. Enviar correo de recuperación
  const recoverForm = document.getElementById('recoverPasswordForm')
  if (recoverForm) {
    recoverForm.addEventListener('submit', function (e) {
      e.preventDefault()
      const emailInput = document.getElementById('recovery-email')
      const email = emailInput.value.trim()

      if (!email) {
        showErrorToast({ message: 'Por favor, ingresa un correo electrónico.' })
        return
      }

      const submitButton = recoverForm.querySelector('button[type="submit"]')
      submitButton.disabled = true

      const formData = new URLSearchParams()
      formData.append('email', email)

      fetch(window.baseUrl + 'recovery/verify-email', {
        // Usa la variable global
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: formData.toString(),
      })
        .then((res) => res.json())
        .then((response) => {
          if (response.value) {
            showSuccessToast(response)
            if (recoverModal) recoverModal.hide()
          } else {
            showErrorToast(response)
          }
        })
        .catch((err) => {
          console.error(err)
          showErrorToast({
            message: 'Error al procesar la solicitud. Inténtalo nuevamente.',
          })
        })
        .finally(() => {
          submitButton.disabled = false
        })
    })
  }

  // 4. Actualizar contraseña con token_reset
  const changeForm = document.getElementById('changePasswordForm')
  if (changeForm) {
    changeForm.addEventListener('submit', function (e) {
      e.preventDefault()
      const passInput = document.getElementById('new_password_reset')
      const newPassword = passInput.value.trim()

      if (newPassword.length < 6) {
        showErrorToast({
          message: 'La contraseña debe tener al menos 6 caracteres.',
        })
        return
      }

      const token = window._resetToken || window.getQueryParam('token_reset') // Usa la función global
      if (!token) {
        showErrorToast({
          message:
            'No se encontró el token de recuperación. Vuelve a usar el enlace enviado a tu correo.',
        })
        return
      }

      const submitButton = changeForm.querySelector('button[type="submit"]')
      submitButton.disabled = true

      const formData = new URLSearchParams()
      formData.append('new_password', newPassword)
      formData.append('token', token)

      fetch(window.baseUrl + 'recovery/update-password', {
        // Usa la variable global
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: formData.toString(),
      })
        .then((res) => res.json())
        .then((response) => {
          if (response.value) {
            showSuccessToast(response)
            window._resetToken = null
            if (changeModal) changeModal.hide()

            // Redirigir al login limpio después de 2 segundos para ver el toast
            setTimeout(() => {
              const url = new URL(window.location.href)
              url.searchParams.delete('token_reset')
              window.location.href = url.toString() // Recargar la página
            }, 2000)
          } else {
            showErrorToast(response)
          }
        })
        .catch((err) => {
          console.error(err)
          showErrorToast({
            message: 'Error al actualizar la contraseña. Inténtalo nuevamente.',
          })
        })
        .finally(() => {
          submitButton.disabled = false
        })
    })
  }
})
