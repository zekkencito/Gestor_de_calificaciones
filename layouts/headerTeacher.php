<?php
// Asegurarnos de que tenemos la conexión a la base de datos
if (!isset($conexion)) {
    require_once "../conection.php";
}

// Obtener la información del usuario
$user_id = $_SESSION['user_id'];
$query = "SELECT ui.names, ui.lastnamePa 
          FROM users u 
          JOIN usersInfo ui ON u.idUserInfo = ui.idUserInfo 
          WHERE u.idUser = ?";

$user_data = null;
$stmt = $conexion->prepare($query);
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result) {
            $user_data = $result->fetch_assoc();
        }
    } else {
        error_log("Error al consultar el encabezado del docente: " . $stmt->error);
    }
} else {
    error_log("Error al preparar el encabezado del docente: " . $conexion->error);
}
?>
<link rel="icon" href="../img/logo.ico">
<header class="p-4" style="background-color: #192E4E; display: flex; justify-content: space-between; align-items: center; box-sizing: border-box; min-height: 90px;">
    <div>                    
        <h5 style="margin: 0; color: white; padding: 0 20px;">Escuela Gregorio Torres Quintero No. 2308</h5>
    </div>                    
    <div style="display: flex; align-items: center; padding-right: 20px; z-index: 1001;">
        <div class="dropdown">
            <button style="color: white; text-decoration: none; background: none; border: none; cursor: pointer; font-size: 1rem;" class="dropdown-toggle" id="userDropdown" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <?php
                if ($user_data) {
                    echo htmlspecialchars($user_data['names'] . ' ' . $user_data['lastnamePa']);
                } else {
                    echo "Docente"; 
                }
            ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                <li>
                    <a href="../admin/php/logout.php" class="dropdown-item">
                        <i class="bi bi-box-arrow-left"></i>&nbsp;&nbsp;Cerrar Sesión</a>
                </li>
            </ul>
        </div>
    </div>
</header>