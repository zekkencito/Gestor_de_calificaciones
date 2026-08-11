<?php
require_once "check_session.php";
include '../conection.php';

$where = [];
$order = [];

if (!empty($_GET['schoolYear'])) {
    $where[] = "s.idSchoolYear = '" . intval($_GET['schoolYear']) . "'";
    $order[] = "(s.idSchoolYear = '" . intval($_GET['schoolYear']) . "') DESC";
}
if (!empty($_GET['grupo'])) {
    $where[] = "s.idGroup = '" . intval($_GET['grupo']) . "'";
    $order[] = "(s.idGroup = '" . intval($_GET['grupo']) . "') DESC";
}
if (!empty($_GET['alumno'])) {
    $alumno = $conexion->real_escape_string($_GET['alumno']);
    $where[] = "(ui.names LIKE '%$alumno%' OR ui.lastnamePa LIKE '%$alumno%' OR ui.lastnameMa LIKE '%$alumno%')";
    $order[] = "(ui.names LIKE '$alumno%' OR ui.lastnamePa LIKE '$alumno%' OR ui.lastnameMa LIKE '$alumno%') DESC";
}
$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";
$order_sql = $order ? "ORDER BY " . implode(", ", $order) : "";

$sql = "SELECT 
    s.idStudent, 
    s.idStudentStatus,
    ui.names, 
    ui.lastnamePa, 
    ui.lastnameMa, 
    ui.phone,
    ui.street,
    ui.gender,
    ui.email,
    s.curp,
    ss.nomenclature,
    ss.description as status,
    s.idGroup,
    CONCAT(g.grade, g.group_) as grupo,
    s.idSchoolYear,
    CONCAT(LEFT(sy.startDate, 4), '-', LEFT(sy.endDate, 4)) as schoolYear,
    t.idTutor,
    t.tutorName,
    t.tutorLastnamePa,
    t.tutorLastnameMa,
    t.tutorPhone,
    t.tutorEmail,
    t.tutorAddress,
    t.ine,
    t.relative_ as tutorRelationship,
    u.username,
    u.password
FROM students s
INNER JOIN usersInfo ui ON s.idUserInfo = ui.idUserInfo
LEFT JOIN users u ON ui.idUserInfo = u.idUserInfo
INNER JOIN studentStatus ss ON s.idStudentStatus = ss.idStudentStatus
LEFT JOIN groups g ON s.idGroup = g.idGroup
LEFT JOIN schoolYear sy ON s.idSchoolYear = sy.idSchoolYear
LEFT JOIN tutors t ON s.idTutor = t.idTutor
$where_sql $order_sql LIMIT 100";

$result = $conexion->query($sql);
if (!$result) {
    echo "<tr><td colspan='9'>Error en la consulta: " . $conexion->error . "</td></tr>";
    exit;
}
while ($row = $result->fetch_assoc()) {
    echo "<tr data-schoolyear='" . htmlspecialchars($row['idSchoolYear']) . "' data-grupo='" . htmlspecialchars($row['idGroup']) . "'>";
    echo "<td>" . htmlspecialchars($row['idStudent']) . "</td>";
    echo "<td>" . htmlspecialchars($row['lastnamePa']) . "</td>";
    echo "<td>" . htmlspecialchars($row['lastnameMa']) . "</td>";
    echo "<td>" . htmlspecialchars($row['names']) . "</td>";
    echo "<td>" . htmlspecialchars($row['grupo']) . "</td>";
    echo "<td>" . htmlspecialchars($row['schoolYear']) . "</td>";
    echo "<td>";
    // Estado con badge de color
    if ($row['nomenclature'] == 'AC') {
        echo '<span class="badge bg-success">' . htmlspecialchars($row['status']) . '</span>';
    } elseif ($row['nomenclature'] == 'BA') {
        echo '<span class="badge bg-danger">' . htmlspecialchars($row['status']) . '</span>';
    } elseif ($row['nomenclature'] == 'RE') {
        echo '<span class="badge bg-warning">' . htmlspecialchars($row['status']) . '</span>';
    } elseif ($row['nomenclature'] == 'EG') {
        echo '<span class="badge bg-primary">' . htmlspecialchars($row['status']) . '</span>';
    } elseif ($row['nomenclature'] == 'IN') {
        echo '<span class="badge bg-secondary">' . htmlspecialchars($row['status']) . '</span>';
    } elseif ($row['nomenclature'] == 'TR') {
        echo '<span class="badge bg-info">' . htmlspecialchars($row['status']) . '</span>';
    } elseif ($row['nomenclature'] == 'RC') {
        echo '<span class="badge bg-dark">' . htmlspecialchars($row['status']) . '</span>';
    } elseif ($row['nomenclature'] == 'EX') {
        echo '<span class="badge bg-light">' . htmlspecialchars($row['status']) . '</span>';
    }
    echo "</td>";
    // Botón Boleta
    echo '<td><button id="botonVer" data-bs-toggle="modal" data-bs-target="#modalCamposFormativos"><i class="bi bi-file-earmark-text-fill"></i></button></td>';
    // Botón Ver (con todos los data-attributes)
    echo '<td>';
    echo '<button class="botonVer btn-ver" id="botonVer"'
        . ' data-id="' . (isset($row['idStudent']) ? htmlspecialchars($row['idStudent']) : '') . '"'
        . ' data-nombres="' . (isset($row['names']) ? htmlspecialchars($row['names']) : '') . '"'
        . ' data-paterno="' . (isset($row['lastnamePa']) ? htmlspecialchars($row['lastnamePa']) : '') . '"'
        . ' data-materno="' . (isset($row['lastnameMa']) ? htmlspecialchars($row['lastnameMa']) : '') . '"'
        . ' data-status="' . (isset($row['idStudentStatus']) ? htmlspecialchars($row['idStudentStatus']) : '1') . '"'
        . ' data-grupo="' . (isset($row['idGroup']) ? htmlspecialchars($row['idGroup']) : '') . '"'
        . ' data-schoolyear="' . (isset($row['idSchoolYear']) ? htmlspecialchars($row['idSchoolYear']) : '') . '"'
        . ' data-telefono="' . (isset($row['phone']) ? htmlspecialchars($row['phone']) : '') . '"'
        . ' data-tutornombres="' . (isset($row['tutorName']) ? htmlspecialchars($row['tutorName']) : '') . '"'
        . ' data-tutorpaterno="' . (isset($row['tutorLastnamePa']) ? htmlspecialchars($row['tutorLastnamePa']) : '') . '"'
        . ' data-tutormaterno="' . (isset($row['tutorLastnameMa']) ? htmlspecialchars($row['tutorLastnameMa']) : '') . '"'
        . ' data-tutortelefono="' . (isset($row['tutorPhone']) ? htmlspecialchars($row['tutorPhone']) : '') . '"'
        . ' data-tutorine="' . (isset($row['ine']) ? htmlspecialchars($row['ine']) : '') . '"'
        . ' data-tutoremail="' . (isset($row['tutorEmail']) ? htmlspecialchars($row['tutorEmail']) : '') . '"'
        . ' data-tutordireccion="' . (isset($row['tutorAddress']) ? htmlspecialchars($row['tutorAddress']) : '') . '"'
        . ' data-tutorparentesco="' . (isset($row['tutorRelationship']) ? htmlspecialchars($row['tutorRelationship']) : '') . '"'
        . ' data-genero="' . (isset($row['gender']) ? htmlspecialchars($row['gender']) : '') . '"'
        . ' data-direccion="' . (isset($row['street']) ? htmlspecialchars($row['street']) : '') . '"'
        . ' data-username="' . (isset($row['username']) ? htmlspecialchars($row['username']) : '') . '"'
        . ' data-email="' . (isset($row['email']) ? htmlspecialchars($row['email']) : '') . '"'
        . ' data-curp="' . (isset($row['curp']) ? htmlspecialchars($row['curp']) : '') . '"'
        . '>';
    echo '<i class="bi bi-person-fill"></i>';
    echo '</button>';
    echo '</td>';
    echo "</tr>";
}
