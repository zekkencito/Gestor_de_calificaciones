// FILTRO VISUAL DE LA TABLA DE DOCENTES
function filtrarTablaDocentes() {
  const docente = document.getElementById('docente')?.value.trim().toLowerCase() || '';
  const filas = document.querySelectorAll('#teachersBody tr');

  filas.forEach(fila => {
    let mostrar = true;
    if (docente) {
      const celdas = fila.querySelectorAll('td');
      let textoFila = '';
      celdas.forEach(td => textoFila += td.textContent.toLowerCase() + ' ');
      if (!textoFila.includes(docente)) {
        mostrar = false;
      }
    }
    fila.style.display = mostrar ? '' : 'none';
  });
}

document.getElementById('docente')?.addEventListener('input', filtrarTablaDocentes);
document.addEventListener('DOMContentLoaded', filtrarTablaDocentes);
