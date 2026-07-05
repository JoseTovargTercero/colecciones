import { showErrorToast } from "../helpers/helpers.js";

let currentUserData = {};

const cargarDatosDelPerfil = () => {
  $.ajax({ url: baseUrl + "api/perfil", method: "GET" })
    .then(function (perfilRes) {
      if (perfilRes.value && perfilRes.data) {
        currentUserData = perfilRes.data;

        const creado = new Date(
          currentUserData.created_at,
        ).toLocaleDateString();

        $("#perfil_nombre").text(currentUserData.nombre);
        $("#perfil_email").text(currentUserData.email);
        $("#perfil_nivel").text(
          currentUserData.nivel == 0 ? "Administrador" : "Usuario",
        );
        $("#perfil_estado").html(
          currentUserData.estado == 1
            ? '<span class="badge bg-success">Activo</span>'
            : '<span class="badge bg-danger">Inactivo</span>',
        );
        $("#perfil_creado").text(creado);
      } else {
        showErrorToast({
          message: "No se pudieron cargar los datos del perfil.",
        });
      }
    })
    .catch(function () {
      showErrorToast({ message: "Error al conectar con la API." });
    });
};

const cargarCounts = () => {
  const url = baseUrl.replace(/\/+$/, '') + '/api/tutorial/state';
  fetch(url)
    .then(r => r.json())
    .then(json => {
      if (!json.value || !json.data) {
        console.warn('perfil counts: API returned', json);
        return;
      }
      const counts = json.data.counts || {};
      const set = (id, val) => {
        const el = document.getElementById(id);
        if (el) el.textContent = val ?? '0';
      };
      set('countEmpresas', counts.empresas);
      set('countTemporadas', counts.temporadas);
      set('countColecciones', counts.colecciones_combos);
      set('countVendedores', counts.vendedores);
    })
    .catch(e => console.warn('perfil counts error:', e));
};

// --- Inicio de la ejecución ---
document.addEventListener("DOMContentLoaded", function () {
  const modalPerfil = new bootstrap.Modal(
    document.getElementById("modalPerfil"),
  );
  const formPerfil = document.getElementById("formPerfil");

  cargarDatosDelPerfil();
  cargarCounts();

  // 1. Botón "Editar Perfil"
  $("#btnEditarPerfil").on("click", function () {
    // Limpiar errores de validación anteriores
    window.limpiarErroresDelFormulario?.(formPerfil);

    // Rellenar el formulario con los datos actuales
    $("#user_id").val(currentUserData.user_id);
    $("#nombre").val(currentUserData.nombre);
    $("#email").val(currentUserData.email);

    // Guardar valor inicial para validación de duplicidad
    $("#email").attr("data-initial-value", currentUserData.email);

    // Limpiar campos de contraseña
    $("#contrasena_actual").val("");

    $("#contrasena").val("");
    $("#contrasena_confirm").val("");

    modalPerfil.show();
  });

  // 2. Envío del formulario de edición
  formPerfil.addEventListener("validation:success", function (e) {
    const formData = e.detail.datos;

    // Si la contraseña está vacía, no la enviamos
    if (!formData.contrasena) {
      delete formData.contrasena;
      delete formData.contrasena_confirm;
    }

    $.ajax({
      url: baseUrl + "api/perfil", // Hacemos POST al mismo endpoint
      method: "POST",
      contentType: "application/json",
      data: JSON.stringify(formData),
      success: function (response) {
        if (response.value) {
          modalPerfil.hide();
          Swal.fire({
            icon: "success",
            title: "¡Éxito!",
            text: response.message,
          });

          // Actualizar la vista en vivo sin recargar
          $("#perfil_nombre").text(response.data.nuevo_nombre);
          $("#perfil_email").text(formData.email); // El email ya lo tenemos

          // Actualizar los datos guardados
          currentUserData.nombre = response.data.nuevo_nombre;
          currentUserData.email = formData.email;
        } else {
          showErrorToast(response);
        }
      },
      error: function (xhr) {
        showErrorToast(xhr.responseJSON);
      },
    });
  });
});
