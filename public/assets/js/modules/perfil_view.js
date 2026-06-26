import { showErrorToast } from '../helpers/helpers.js'

// Variable global para almacenar los datos del usuario actual
let currentUserData = {}

/**
 * Renderiza los permisos del usuario en un acordeón (solo lectura).
 * @param {Array} todosLosMenus - Lista de todos los menús del sistema.
 * @param {Array} permisosUsuarioUrls - Lista de URLs (strings) a las que el usuario tiene acceso.
 */
const renderizarPermisosAcordeon = (todosLosMenus, permisosUsuarioUrls) => {
  const contenedorAcordeon = $('#accordionPermisos')
  contenedorAcordeon.html('') // Limpiar el spinner

  if (permisosUsuarioUrls.includes('*')) {
    // Caso Administrador
    const adminMessage = `<div class="alert alert-info" role="alert"><i class="mdi mdi-account-star me-2"></i>Como <strong>Administrador</strong>, tienes acceso a todos los módulos.</div>`
    contenedorAcordeon.html(adminMessage)
    return
  }

  const permisosAsignados = new Set(permisosUsuarioUrls)
  const menusPorCategoria = todosLosMenus.reduce((acc, menu) => {
    const categoria = menu.categoria || 'Sin Categoría'
    if (!acc[categoria]) acc[categoria] = []
    acc[categoria].push(menu)
    return acc
  }, {})

  let index = 0
  Object.keys(menusPorCategoria).forEach((categoria) => {
    // Filtrar solo los menús a los que el usuario tiene permiso
    const menusPermitidosEnCategoria = menusPorCategoria[categoria].filter(
      (menu) => permisosAsignados.has(menu.url)
    )

    // Si no tiene ningún permiso en esta categoría, no mostrar el acordeón
    if (menusPermitidosEnCategoria.length === 0) {
      return
    }

    const collapseId = `collapse-${index}`
    const headerId = `header-${index}`
    const isFirst = index === 0
    const linkClass = isFirst ? '' : 'collapsed'
    const expandedState = isFirst ? 'true' : 'false'
    const collapseClass = isFirst ? 'show' : ''

    // Crear la lista de permisos (solo texto)
    const itemsHtml = menusPermitidosEnCategoria
      .map(
        (menu) =>
          `<li class="list-group-item border-0 py-1">${menu.nombre}</li>`
      )
      .join('')

    const itemHtml = `
      <div class="card mb-0">
          <div class="card-header" id="${headerId}">
              <h5 class="m-0">
                  <a class="custom-accordion-title ${linkClass} d-block py-1"
                      data-bs-toggle="collapse" href="#${collapseId}"
                      aria-expanded="${expandedState}" aria-controls="${collapseId}">
                      ${categoria.toLocaleUpperCase()}
                      <i class="mdi mdi-chevron-down accordion-arrow"></i>
                  </a>
              </h5>
          </div>
          <div id="${collapseId}" class="collapse ${collapseClass}"
              aria-labelledby="${headerId}" data-bs-parent="#accordionPermisos">
              <div class="card-body">
                  <ul class="list-group">
                      ${itemsHtml}
                  </ul>
              </div>
          </div>
      </div>`
    contenedorAcordeon.append(itemHtml)
    index++
  })

  if (index === 0) {
    contenedorAcordeon.html(
      '<p class="text-center text-muted">No tienes permisos de menú asignados.</p>'
    )
  }
}

/**
 * Carga los datos del perfil del usuario (info y permisos) y todos los menús.
 */
const cargarDatosDelPerfil = () => {
  Promise.all([
    // 1. Obtener los datos del perfil del usuario (incluye su lista de permisos)
    $.ajax({ url: baseUrl + 'api/perfil', method: 'GET' }),
    // 2. Obtener la lista completa de menús para categorizarlos
    $.ajax({ url: baseUrl + 'api/menus', method: 'GET' }),
  ])
    .then(function (responses) {
      // --- Respuesta 1: Datos del Perfil ---
      const perfilRes = responses[0]
      if (perfilRes.value && perfilRes.data) {
        currentUserData = perfilRes.data // Guardar datos para el modal

        // Formatear fechas
        const creado = new Date(currentUserData.created_at).toLocaleDateString()

        // Poblar la tarjeta de perfil
        $('#perfil_nombre').text(currentUserData.nombre)
        $('#perfil_email').text(currentUserData.email)
        $('#perfil_nivel').text(
          currentUserData.nivel == 0 ? 'Administrador' : 'Usuario'
        )
        $('#perfil_estado').html(
          currentUserData.estado == 1
            ? '<span class="badge bg-success">Activo</span>'
            : '<span class="badge bg-danger">Inactivo</span>'
        )
        $('#perfil_creado').text(creado)

        // --- Respuesta 2: Todos los Menús ---
        const menusRes = responses[1]
        const todosLosMenus = menusRes.data || []

        // Renderizar el acordeón de permisos
        renderizarPermisosAcordeon(todosLosMenus, currentUserData.permisos)
      } else {
        showErrorToast({
          message: 'No se pudieron cargar los datos del perfil.',
        })
      }
    })
    .catch(function (error) {
      showErrorToast({ message: 'Error al conectar con la API.' })
      $('#accordionPermisos').html(
        '<p class="text-danger text-center">Error al cargar los permisos.</p>'
      )
    })
}

// --- Inicio de la ejecución ---
document.addEventListener('DOMContentLoaded', function () {
  const modalPerfil = new bootstrap.Modal(
    document.getElementById('modalPerfil')
  )
  const formPerfil = document.getElementById('formPerfil')

  // Cargar datos en cuanto la página esté lista
  cargarDatosDelPerfil()

  // 1. Botón "Editar Perfil"
  $('#btnEditarPerfil').on('click', function () {
    // Limpiar errores de validación anteriores
    window.limpiarErroresDelFormulario?.(formPerfil)

    // Rellenar el formulario con los datos actuales
    $('#user_id').val(currentUserData.user_id)
    $('#nombre').val(currentUserData.nombre)
    $('#email').val(currentUserData.email)

    // Guardar valor inicial para validación de duplicidad
    $('#email').attr('data-initial-value', currentUserData.email)

    // Limpiar campos de contraseña
    $('#contrasena_actual').val('')

    $('#contrasena').val('')
    $('#contrasena_confirm').val('')

    modalPerfil.show()
  })

  // 2. Envío del formulario de edición
  formPerfil.addEventListener('validation:success', function (e) {
    const formData = e.detail.datos

    // Si la contraseña está vacía, no la enviamos
    if (!formData.contrasena) {
      delete formData.contrasena
      delete formData.contrasena_confirm
    }

    $.ajax({
      url: baseUrl + 'api/perfil', // Hacemos POST al mismo endpoint
      method: 'POST',
      contentType: 'application/json',
      data: JSON.stringify(formData),
      success: function (response) {
        if (response.value) {
          modalPerfil.hide()
          Swal.fire({
            icon: 'success',
            title: '¡Éxito!',
            text: response.message,
          })

          // Actualizar la vista en vivo sin recargar
          $('#perfil_nombre').text(response.data.nuevo_nombre)
          $('#perfil_email').text(formData.email) // El email ya lo tenemos

          // Actualizar los datos guardados
          currentUserData.nombre = response.data.nuevo_nombre
          currentUserData.email = formData.email
        } else {
          showErrorToast(response)
        }
      },
      error: function (xhr) {
        showErrorToast(xhr.responseJSON)
      },
    })
  })
})
