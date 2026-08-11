//EDITAR
var botonesEditar = document.getElementsByClassName("btnEditar")
for(var i=0; i<botonesEditar.length; i++){
  botonesEditar[i].onclick=(evt)=>{
    var btn = evt.target.closest(".btnEditar");
    var id=btn.getAttribute("data-id")
    document.getElementById("txtIdEdit").value=id
    var nombre=btn.getAttribute("data-nombre")
    document.getElementById("txtNombreEdit").value=nombre
    var correo=btn.getAttribute("data-correo")
    document.getElementById("txtCorreoEdit").value=correo
    var biografia=btn.getAttribute("data-biografia")
    document.getElementById("txtBiografiaEdit").value=biografia
    var redes=btn.getAttribute("data-redes")
    document.getElementById("txtRedesEdit").value=redes
  }
}

// FILTRO VISUAL DE LA TABLA DE ALUMNOS
function filtrarTablaAlumnos() {
  const alumno = document.getElementById('alumno')?.value.trim().toLowerCase() || '';
  const schoolYear = document.getElementById('schoolYear')?.value || '';
  const grupo = document.getElementById('grupo')?.value || '';
  const filas = document.querySelectorAll('#alumnosBody tr');

  filas.forEach(fila => {
    let mostrar = true;
    // Filtro por año escolar
    if (schoolYear && fila.getAttribute('data-schoolyear') !== schoolYear) {
      mostrar = false;
    }
    // Filtro por grupo
    if (mostrar && grupo && fila.getAttribute('data-grupo') !== grupo) {
      mostrar = false;
    }
    // Filtro por nombre
    if (mostrar && alumno) {
      const celdas = fila.querySelectorAll('td');
      let textoFila = '';
      celdas.forEach(td => textoFila += td.textContent.toLowerCase() + ' ');
      if (!textoFila.includes(alumno)) {
        mostrar = false;
      }
    }
    fila.style.display = mostrar ? '' : 'none';
  });
}

document.getElementById('alumno')?.addEventListener('input', filtrarTablaAlumnos);
document.getElementById('schoolYear')?.addEventListener('change', filtrarTablaAlumnos);
document.getElementById('grupo')?.addEventListener('change', filtrarTablaAlumnos);
document.addEventListener('DOMContentLoaded', filtrarTablaAlumnos);
