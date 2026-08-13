//Llamados a las funciones que se ejecutarán al inicio
document.addEventListener("DOMContentLoaded", function () {
  eventListeners();
  mostrarModales();
  actualizarIdprograma();
  closeModal();
  eliminarAlertas();
  marcarSidebarActivo();
  prepararSidebarTooltips();
});

// Marca el enlace del sidebar de la página actual (estado activo) y abre su
// submenú padre si corresponde. Sirve para los 3 layouts (comparten clases).
function marcarSidebarActivo() {
  const path = window.location.pathname;
  const links = document.querySelectorAll("#sidebar a.sidebar-link[href]");
  let best = null;
  let bestLen = -1;

  links.forEach((a) => {
    const href = a.getAttribute("href");
    if (!href || href === "#" || href === "/logout") return;
    // Coincidencia exacta o por prefijo de ruta (p. ej. /programa/admin)
    if (path === href || path.startsWith(href + "/")) {
      if (href.length > bestLen) { best = a; bestLen = href.length; }
    }
  });

  // Respaldo: coincidir por el primer segmento de la ruta (/programa/...)
  if (!best) {
    const seg = "/" + path.split("/")[1];
    if (seg.length > 1) {
      links.forEach((a) => {
        const href = a.getAttribute("href");
        if (!href || href === "#") return;
        if (href.indexOf(seg) === 0 && href.length > bestLen) { best = a; bestLen = href.length; }
      });
    }
  }

  if (!best) return;
  best.classList.add("active");

  // Si el enlace activo vive en un submenú, resaltar su ícono padre.
  // El submenú inline solo se abre si el sidebar está expandido; en modo
  // colapsado los submenús se muestran como flyout al pasar el mouse, así
  // que no forzamos su apertura (evita un panel pegado y "raro").
  const dropdown = best.closest("ul.sidebar-dropdown");
  if (dropdown) {
    const toggler = document.querySelector('[data-bs-target="#' + dropdown.id + '"]');
    if (toggler) toggler.classList.add("active-parent");
    const sidebar = document.querySelector("#sidebar");
    if (sidebar && sidebar.classList.contains("expand")) {
      dropdown.classList.add("show");
      if (toggler) {
        toggler.classList.remove("collapsed");
        toggler.setAttribute("aria-expanded", "true");
      }
    }
  }
}

// Instancias de tooltip vivas del sidebar (para poder descartarlas al expandir).
var sidebarTooltips = [];

// Prepara los tooltips del sidebar: enlaces hoja de primer nivel (y "Cerrar
// sesión") reciben un tooltip con su nombre; los ítems con submenú reciben una
// cabecera con el nombre de la sección dentro de su flyout. Cubre los 3 layouts.
function prepararSidebarTooltips() {
  const sidebar = document.querySelector("#sidebar");
  if (!sidebar) return;

  // Enlaces hoja de primer nivel + cerrar sesión → tooltip con su nombre.
  const hojas = sidebar.querySelectorAll(
    ".sidebar-nav > .sidebar-item > a.sidebar-link:not(.has-dropdown), .sidebar-footer > a.sidebar-link"
  );
  hojas.forEach((a) => {
    const span = a.querySelector("span");
    if (span && !a.getAttribute("data-bs-title")) {
      a.setAttribute("data-bs-title", span.textContent.trim());
    }
    a.setAttribute("data-bs-toggle", "tooltip");
    a.setAttribute("data-bs-placement", "right");
  });

  // Ítems con submenú → cabecera con el nombre de la sección dentro del flyout.
  const padres = sidebar.querySelectorAll(".sidebar-nav > .sidebar-item > a.has-dropdown");
  padres.forEach((a) => {
    const dropdown = a.parentElement.querySelector(".sidebar-dropdown");
    const span = a.querySelector("span");
    if (dropdown && span && !dropdown.querySelector(".sidebar-flyout-title")) {
      const titulo = document.createElement("li");
      titulo.className = "sidebar-flyout-title";
      titulo.textContent = span.textContent.trim();
      dropdown.insertBefore(titulo, dropdown.firstChild);
    }
  });

  sincronizarSidebarTooltips();
}

// Activa los tooltips solo cuando el sidebar está colapsado; los descarta al
// expandir (ahí las etiquetas ya son visibles y el tooltip sobra).
function sincronizarSidebarTooltips() {
  const sidebar = document.querySelector("#sidebar");
  if (!sidebar || typeof bootstrap === "undefined") return;
  const colapsado = !sidebar.classList.contains("expand");

  if (colapsado && sidebarTooltips.length === 0) {
    sidebar.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
      sidebarTooltips.push(new bootstrap.Tooltip(el));
    });
  } else if (!colapsado && sidebarTooltips.length > 0) {
    sidebarTooltips.forEach((t) => t.dispose());
    sidebarTooltips = [];
  }
}

//Listeners
function eventListeners(e) {
  //SideBar
  const hamburger = document.querySelector("#toggle-btn");
  if (hamburger) {
    hamburger.addEventListener("click", function () {
      document.querySelector("#sidebar").classList.toggle("expand");
      // Los tooltips solo aplican cuando el sidebar está colapsado.
      sincronizarSidebarTooltips();
    });
  }

  //Capturar el select de Programas para poder vincular los resultados
  const prgmtas = document.querySelector("select#programslist");
  if (prgmtas) {
    const optn = prgmtas.querySelector("option[value='']");
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

  // El modal "Guardar POA" se retiró el 2026-08-13 (ver views/reporte/poa.php).
  // De paso desaparece el manejador que interceptaba el clic de #descargarReporte
  // en las CINCO pantallas de reporte: hacía preventDefault + window.open y luego
  // intentaba abrir un modal que solo existía en una de ellas. El enlace ya lleva
  // target="_blank", así que el comportamiento nativo es el mismo, sin errores.

  //Existe el combo SELECT cargo con el cargo_id=3 (COORDINADORES)
  const cargo = document.querySelector("#cargo");
  // Si existe el combo con id cargo, entonces se debe de mostrar el combo con id programas_coordinador que está por defecto como oculto
  if (cargo) {
    cargo.addEventListener("change", mostrarProgramasCoordinador);
  }

  //Cambiar las fechas del DATE(límites de los reportes)
  // Establecer las fechas por defecto
  const fechaInicio = document.getElementById("fecha_inicio");
  const fechaFin = document.getElementById("fecha_fin");

  if (fechaInicio && fechaFin) {
    const today = new Date();
    const currentYear = today.getFullYear();

    // Primer día del año actual
    const firstDayOfYear = new Date(currentYear, 0, 1)
      .toISOString()
      .split("T")[0];
    // Fecha actual
    const todayDate = today.toISOString().split("T")[0];

    fechaInicio.value = firstDayOfYear;
    fechaFin.value = todayDate;
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

// (Retirado) inicio() y cambiarestiloAddquite() gestionaban los estados neón de
// las cards de vínculo fuente↔programa. Ahora el estado (vinculada/disponible) se
// renderiza server-side en views/dfinanciamiento/crear.php con clases Bootstrap
// tematizadas (badge/border-success, btn-primary/btn-outline-danger).

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
  const programasCoordinadorlabel = document.querySelector(
    "#programas_coordinador_label"
  );
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
  // Seleccionar las alertas transitorias (flash). Las marcadas como
  // .alert-persistente son banners de contenido (p. ej. la observación del
  // POA de Indicadores) y deben permanecer visibles hasta cambiar de vista.
  const alerts = document.querySelectorAll(".alert:not(.alert-persistente)");

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
