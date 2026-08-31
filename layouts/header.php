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

$stmt = $conexion->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
?>
<!-- Design System -->
<link rel="stylesheet" href="../css/design-system.css">
<link rel="stylesheet" href="../css/components.css">
<link rel="stylesheet" href="../css/layout.css">
<link rel="icon" href="../img/logo.ico">
<header class="ds-header">
    <!-- Hamburger toggle (mobile only) -->
    <button class="ds-header__toggle" id="ds-sidebar-toggle" aria-label="Abrir menú">
        <i class="bi bi-list"></i>
    </button>
    <!-- Left: School name -->
    <div class="ds-header__title">                    
        <h5>Escuela Gregorio Torres Quintero No. 2308</h5>
    </div>
    <!-- Right: User + Logout -->
    <div class="ds-header__user-area">
        <span class="ds-header__user-name">
            <?php
                if ($user_data) {
                    echo htmlspecialchars($user_data['names'] . ' ' . $user_data['lastnamePa']);
                } else {
                    echo "Administrador";
                }
            ?>
        </span>
        <a href="../admin/php/logout.php" class="ds-header__logout" title="Cerrar Sesión">
            <i class="bi bi-box-arrow-right"></i>
        </a>
    </div>
</header>
