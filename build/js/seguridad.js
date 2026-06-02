// Asset de seguridad (M1 / CSP). Reemplaza los onclick="return confirm(...)" inline
// por un manejador delegado, para poder retirar 'unsafe-inline' de script-src.
// Se carga como archivo aparte (no entra en el bundle de Gulp). No usar eval().
(function () {
  'use strict';
  // Confirmación antes de enviar formularios de eliminación (botones con data-confirm).
  document.addEventListener('click', function (e) {
    var el = e.target.closest('[data-confirm]');
    if (!el) {
      return;
    }
    if (!window.confirm(el.getAttribute('data-confirm'))) {
      // El usuario canceló: se evita el envío del formulario.
      e.preventDefault();
      e.stopPropagation();
    }
  });
})();
