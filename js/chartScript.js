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
              backgroundColor: ['#1a7f4b', '#b91c1c'],
              borderWidth: 2,
              borderColor: '#ffffff',
              hoverOffset: 6
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: {
              padding: {
                top: 8,
                bottom: 4,
                left: 4,
                right: 4
              }
            },
            plugins: {
              legend: {
                display: true,
                position: 'bottom',
                labels: {
                  padding: 20,
                  usePointStyle: true,
                  pointStyle: 'circle',
                  font: {
                    family: "'League Spartan', sans-serif",
                    size: 13,
                    weight: '500'
                  },
                  color: '#40454f'
                }
              },
              tooltip: {
                enabled: true,
                backgroundColor: '#192E4E',
                titleFont: { family: "'League Spartan', sans-serif", weight: '600' },
                bodyFont: { family: "'League Spartan', sans-serif" },
                cornerRadius: 6,
                padding: 12
              }
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
