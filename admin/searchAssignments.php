<?php
require_once "check_session.php";
require_once '../conection.php';

header('Content-Type: text/html; charset=utf-8');

$where = '';
$params = [];
$types = '';

if (isset($_POST['buscar']) && isset($_POST['valor'])) {
    $buscar = $_POST['buscar'];
    $valor = $_POST['valor'];
    if ($buscar === 'grupo') {
        $where = " AND g.idGroup = ?";
        $params[] = intval($valor);
        $types .= "i";
    } else if ($buscar === 'maestro') {
        $where = " AND t.idTeacher = ?";
        $params[] = intval($valor);
        $types .= "i";
    } else if ($buscar === 'materia') {
        $where = " AND sub.idSubject = ?";
        $params[] = intval($valor);
        $types .= "i";
    }
}

$sql = "SELECT 
    syear.idSchoolYear, 
    LEFT(syear.startDate, 4) AS ciclo,
    g.idGroup, 
    CONCAT(g.grade, g.group_) as grupo, 
    GROUP_CONCAT(DISTINCT sub.idSubject ORDER BY sub.name SEPARATOR ',') as idSubjects,
    GROUP_CONCAT(DISTINCT sub.name ORDER BY sub.name SEPARATOR '|||') as materias,
    ui.lastnamePa, 
    ui.lastnameMa, 
    ui.names,
    t.idTeacher,
    MAX(ts.idTeacherSubject) as ultimaAsignacion
FROM teacherGroupsSubjects tgs
INNER JOIN groups g ON tgs.idGroup = g.idGroup
INNER JOIN subjects sub ON tgs.idSubject = sub.idSubject
INNER JOIN teachers t ON tgs.idTeacher = t.idTeacher
INNER JOIN users u ON t.idUser = u.idUser
INNER JOIN usersInfo ui ON u.idUserInfo = ui.idUserInfo
INNER JOIN teacherSubject ts ON ts.idTeacher = tgs.idTeacher AND ts.idSubject = tgs.idSubject
INNER JOIN schoolYear syear ON ts.idSchoolYear = syear.idSchoolYear
WHERE 1 $where
GROUP BY syear.idSchoolYear, g.idGroup, t.idTeacher, g.grade, g.group_, ui.lastnamePa, ui.lastnameMa, ui.names
ORDER BY ultimaAsignacion DESC";

$stmt = $conexion->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $uid = $row['idGroup'] . '-' . $row['idTeacher'] . '-' . $row['idSchoolYear'];
        $materias = explode('|||', $row['materias']);
        $idSubjects = explode(',', $row['idSubjects']);
        
        echo '<tr class="align-middle" ';
        echo 'data-idgrupo="' . (isset($row['idGroup']) ? htmlspecialchars($row['idGroup']) : '') . '" ';
        echo 'data-idsubject="' . (isset($idSubjects[0]) ? htmlspecialchars($idSubjects[0]) : '') . '" ';
        echo 'data-idsubjects="' . (isset($row['idSubjects']) ? htmlspecialchars($row['idSubjects']) : '') . '" ';
        echo 'data-idteacher="' . (isset($row['idTeacher']) ? htmlspecialchars($row['idTeacher']) : '') . '" ';
        echo 'data-idyear="' . (isset($row['idSchoolYear']) ? htmlspecialchars($row['idSchoolYear']) : '') . '">';
        echo '<td class="text-center">' . (isset($row['ciclo']) ? htmlspecialchars($row['ciclo']) : '') . '</td>';
        echo '<td class="text-center"><span class="badge bg-primary">' . (isset($row['grupo']) ? htmlspecialchars($row['grupo']) : '') . '</span></td>';
        
        // Mostrar materias como badges
        echo '<td class="text-center">';
        foreach ($materias as $materia) {
            echo '<span class="badge bg-info text-dark me-1 mb-1">' . (isset($materia) ? htmlspecialchars($materia) : '') . '</span>';
        }
        echo '</td>';
        
        echo '<td class="text-center">' . (isset($row['lastnamePa']) ? htmlspecialchars($row['lastnamePa']) : '') . '</td>';
        echo '<td class="text-center">' . (isset($row['lastnameMa']) ? htmlspecialchars($row['lastnameMa']) : '') . '</td>';
        echo '<td class="text-center fw-semibold">' . (isset($row['names']) ? htmlspecialchars($row['names']) : '') . '</td>';
        echo '<td class="text-center">';
        echo '<button class="btn btn-sm text-warning botonVerEdit me-2" data-bs-toggle="modal" data-bs-target="#editModal" data-uid="' . $uid . '" title="Editar asignación" style="border: none; background: none;">';
        echo '<i class="bi bi-pencil-fill fs-5"></i>';
        echo '</button>';
        echo '<button class="btn btn-sm text-danger botonVerDelete" data-bs-toggle="modal" data-bs-target="#deleteModal" data-uid="' . $uid . '" title="Eliminar asignación" style="border: none; background: none;">';
        echo '<i class="bi bi-trash-fill fs-5"></i>';
        echo '</button>';
        echo '</td>';
        echo '</tr>';
    }
} else {
    echo '<tr><td colspan="7" class="text-center text-muted py-4">';
    echo '<i class="bi bi-info-circle me-2"></i>';
    echo 'No hay asignaciones registradas.';
    echo '</td></tr>';
}
?>