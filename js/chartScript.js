function cargarAprobadosPorcentaje(tipo) {
  let url = '';
  // Detecta desde dónde se llama el dashboard y usa rutas absolutas relativas
  if (tipo === 'admin') {
    url = window.location.pathname.includes('/admin/') ? 'getAprobadosPorcentajeGrupos.php' : '../admin/getAprobadosPorcentajeGrupos.php';
  } else {
    url = window.location.pathname.includes('/teachers/') ? 'getAprobadosPorcentajeAlumnos.php' : '../teachers/getAprobadosPorcentajeAlumnos.php';
  }
  fetch(url)
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        const ctx2 = document.getElementById('chartCategorias');
        new Chart(ctx2, {
          type: 'pie',
          data: {
            labels: ['Aprobados', 'No Aprobados'],
            datasets: [{
              label: tipo === 'admin' ? 'Porcentaje de grupos' : 'Porcentaje de alumnos',
              data: [data.porcentaje, 100 - data.porcentaje],
              backgroundColor: ['#2ecc71', '#e74c3c'],
              borderWidth: 1
            }]
          },
          options: {
            plugins: {
              legend: { display: true },
              tooltip: { enabled: true }
            }
          }
        });
      }
    });
}

document.addEventListener('DOMContentLoaded', function () {
  // Detecta si es dashboard admin o teacher
  if (window.location.pathname.includes('/admin/')) {
    cargarAprobadosPorcentaje('admin');
  } else {
    cargarAprobadosPorcentaje('teacher');
  }
  'use strict';
  
  // Selecciona todos los formularios con la clase 'needs-validation'
  var forms = document.querySelectorAll('.needs-validation');
  
  // Aplica validación personalizada a cada formulario
  Array.prototype.slice.call(forms).forEach(function (form) {
      form.addEventListener('submit', function (event) {
          if (!form.checkValidity()) {
              event.preventDefault(); // Detiene el envío
              event.stopPropagation(); // Detiene propagación de eventos
          }
          form.classList.add('was-validated'); // Aplica estilos de validación Bootstrap
      }, false);
  });
});
