// Este script sincroniza la fecha límite entre localStorage y el backend para admins y maestros
function syncFechaLimiteWithServer() {
    const fechaLimite = localStorage.getItem('fechaLimiteCalificaciones');
    if (fechaLimite) {
        fetch('get_fecha_limite.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'fechaLimite=' + encodeURIComponent(fechaLimite)
        });
    }
}
document.addEventListener('DOMContentLoaded', syncFechaLimiteWithServer);
