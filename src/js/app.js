//Llamados a las funciones que se ejecutarán al inicio
document.addEventListener("DOMContentLoaded", function () {
  inicio();
  eventListeners();
  cambiarestiloAddquite();
  mostrarModales();
  actualizarIdprograma();
  closeModal();
  eliminarAlertas();
});

//Listeners
function eventListeners(e) {
  //SideBar
  const hamburger = document.querySelector("#toggle-btn");
  if (hamburger) {
    hamburger.addEventListener("click", function () {
      document.querySelector("#sidebar").classList.toggle("expand");
      ///MOSTRANDO CORRECTAMENTE LOS TOOLTIPS
      //Permite mostrar los Tooltips del SIDEBAR
      // Si no tiene la clase .expand, inicializar tooltips nuevamente
      var tooltipTriggerList = [].slice.call(
        document.querySelectorAll('[data-bs-toggle="tooltip"]')
      );
      var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
      });
      //Restricción que si tiene la clase EXPAND desactive los Tooltips
      if (!document.querySelector("#sidebar").classList.contains("expand")) {
        tooltipTriggerList.forEach((tooltipTriggerEl) => {
          new bootstrap.Tooltip(tooltipTriggerEl);
        });
      } else {
        tooltipList.forEach((tooltip) => tooltip.dispose());
        // Si no tiene la clase .expand, inicializar tooltips nuevamente
      }
    });
  }

  //Capturar el select de Programas para poder vincular los resultados
  const prgmtas = document.querySelector("select#programslist");
  if (prgmtas) {
    const optn = prgmtas.querySelector(" option[value='']");
    optn.disabled = true;
    prgmtas.addEventListener("change", actualizarIdprograma);
    prgmtas.addEventListener("change", redireccionarUrlPOA);
  }
  //Change en el select de Programas - Relación Fuente Financiamiento - Programas
  const prgms = document.querySelector("select#programs");
  if (prgms) {
    prgms.addEventListener("change", actualizarIdprograma);
    prgms.addEventListener("change", redireccionarUrl);
  }
  //Change de type del input de PASSWORD A TEXT - PERMITE VER LA CONTRASEÑA AL MOMENTO DE LOGUEARSE
  const passwordInput = document.querySelector("#password");
  const toggleButton = document.querySelector("#togglePassword");
  if (passwordInput && toggleButton) {
    // Cambiar el tipo de input al mantener presionado el botón
    toggleButton.addEventListener("mousedown", () => {
      passwordInput.type = "text";
      toggleButton.innerHTML = '<i class="bi bi-eye"></i>'; // Cambia el icono a cerrado
    });

    // Volver al tipo "password" al soltar el botón
    toggleButton.addEventListener("mouseup", () => {
      passwordInput.type = "password";
      toggleButton.innerHTML = '<i class="bi bi-eye-slash"></i>'; // Cambia el icono a cerrado
    });
  }

  //MODAL DE GUARDAR POA
  //Elección de guardar o no poa luego de crear el reporte en excel
  var modalpoa = document.getElementById("guardarModal");
  var guardarBtn = document.getElementById("guardarBtn");
  var cancelarBtn = document.getElementById("cancelarBtn");
  var descargarReporte = document.getElementById("descargarReporte");

  // Verificar si los elementos del modal existen
  // if (modal && guardarBtn && cancelarBtn && descargarReporte) {
  // Manejar el clic en el enlace de descarga
  if (descargarReporte) {
    descargarReporte.addEventListener("click", function (event) {
      event.preventDefault();
      window.open(this.href, "_blank");
      setTimeout(function () {
        var bootstrapModal = new bootstrap.Modal(modalpoa);
        bootstrapModal.show();
      }, 1000); // Esperar 1 segundo antes de mostrar el modal
      console.log(bootstrapModal);
    });
    console.log(modalpoa);
  }

  //Deberíamos de guardar los valores necesarios para la tabla POA
  if (guardarBtn) {
    // Manejar el clic en el botón "Sí"
    guardarBtn.addEventListener("click", function () {
      // Enviar el formulario para guardar el POA en la base de datos
      var form = document.createElement("form");
      form.method = "POST";
      form.action = "/guardarpoa"; // Cambia esto a la URL correcta para guardar el POA
      document.body.appendChild(form);
      form.submit();
    });
  }
  //Fin de la modificación
  if (cancelarBtn) {
    // Manejar el clic en el botón "No"
    cancelarBtn.addEventListener("click", function () {
      // Ocultar el modal y regresar a la vista anterior
      var bootstrapModal = bootstrap.Modal.getInstance(modal);
      bootstrapModal.hide();
      window.history.back();
    });
  }

  //FIN DE MODAL DE GUARDAR POA

  //Existe el combo SELECT cargo con el cargo_id=3 (COORDINADORES)
  const cargo = document.querySelector("#cargo");
  // Si existe el combo con id cargo, entonces se debe de mostrar el combo con id programas_coordinador que está por defecto como oculto
  if (cargo) {
    cargo.addEventListener("change", mostrarProgramasCoordinador);
  }
}

function cambiarIdProgramasenCards(e) {
  const idpcards = document.querySelectorAll("input.idprogram");
  const valor = e.target.value;

  if (valor !== 0) {
    idpcards.forEach((idpcard) => {
      idpcard.value = valor;
    });
  }
}

function cambiarestiloAddquite(e) {
  const crds = document.querySelectorAll("div.card");
  const resuls = document.querySelectorAll("input.idquery");
  crds.forEach((crd) => {
    resuls.forEach((resul) => {
      if (crd.classList.contains(resul.value)) {
        crd.classList.remove("financia-dslc");
        crd.classList.add("financia-slc");
      }
    });
  });
  //Asignando el verdadero valor que debe de tener los submit de cada card
  crds.forEach((crd) => {
    const btn = crd.querySelector("input.addff");
    if (crd.classList.contains("financia-slc")) {
      btn.value = "Quitar";
    }
  });
}

function inicio(e) {
  //Limpiando datos antes de iniciar
  const elmnts = document.querySelectorAll("input.idprogram");
  const elmnts2 = document.querySelectorAll("input.idff");
  const crds = document.querySelectorAll("div.card");

  crds.forEach((crd) => {
    crd.classList.add("financia-dslc");
  });
  elmnts.forEach((elmnt) => {
    elmnt.value = "0";
  });
  elmnts2.forEach((elmnt) => {
    elmnt.value = "0";
  });
}

function mostrarModales() {
  const modals = document.querySelectorAll("div.modal");
  modals.forEach((modal) => {
    if (!modal.classList.contains("oculto")) {
      var modales = new bootstrap.Modal(modal);
      modales.show();
    }
  });
}

function redireccionarUrl() {
  const prgms = document.querySelector("select#programs");
  window.location.href = "/dfinanciamiento/crear?programa_id=" + prgms.value;
}
function redireccionarUrlPOA() {
  const prgms = document.querySelector("select#programslist");

  window.location.href = "/resultado/admin?programa_id=" + prgms.value;
}
function actualizarIdprograma(e) {
  const parms = new URLSearchParams(window.location.search);
  const programaid = parms.get("programa_id");
  const pgms = document.querySelectorAll("input.idprogram");
  pgms.forEach((pgm) => {
    pgm.value = programaid;
  });
}
function actualizarIdprogramaPOA(e) {
  const parms = new URLSearchParams(window.location.search);
  const programaid = parms.get("programa_id");
}

function closeModal() {
  var errorModal = document.getElementById("errorModal");
  if (errorModal) {
    errorModal.style.display = "none";
    var backdrop = document.querySelector(".modal-backdrop");
    if (backdrop) {
      backdrop.remove();
    }
  }
}

function mostrarProgramasCoordinador(e) {
  const programasCoordinadorlabel = document.querySelector("#programas_coordinador_label");
  const programasCoordinador = document.querySelector("#programas_coordinador");
  const programasCoordinadorhelp = document.querySelector(
    "#programas_coordinador_help"
  );

  if (e.target.value == 3) {
    programasCoordinador.classList.remove("oculto");
    programasCoordinadorlabel.classList.remove("oculto");
    programasCoordinadorhelp.classList.remove("oculto");
  } else {
    programasCoordinador.classList.add("oculto");
    programasCoordinadorlabel.classList.add("oculto");
    programasCoordinadorhelp.classList.add("oculto");
    programasCoordinador.value = 0;
  }
}

//Función para desaparecer las alertas del CRUD en los admin
function eliminarAlertas() {
  // Seleccionar todas las alertas
  const alerts = document.querySelectorAll(".alert");

  // Configurar el timeout para cada alerta encontrada
  alerts.forEach((alert) => {
    setTimeout(() => {
      // Agregar efecto de desvanecimiento
      alert.style.transition = "opacity 0.5s ease-out";
      alert.style.opacity = "0";

      // Eliminar el elemento después de la transición
      setTimeout(() => {
        alert.remove();
      }, 500); // Medio segundo para el fade
    }, 3000); // 3 segundos antes de empezar a desaparecer
  });
}
