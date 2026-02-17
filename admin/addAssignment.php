<?php
require_once "check_session.php";
include '../conection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idGroup = isset($_POST['grupo']) ? intval($_POST['grupo']) : 0;
    $idSubject = isset($_POST['materia']) ? intval($_POST['materia']) : 0;
    $idTeacher = isset($_POST['docente']) ? intval($_POST['docente']) : 0;
    
    // Validar que los campos requeridos tengan valores válidos (mayores que 0)
    if ($idGroup <= 0 || $idSubject <= 0 || $idTeacher <= 0) {
        header("Location: assignments.php?status=error&message=" . urlencode('Faltan datos para la asignación. Por favor complete todos los campos.'));
        exit;
    }
    
    // Obtener automáticamente el ciclo escolar del año actual
    $currentYear = date('Y');
    $sqlGetYear = "SELECT idSchoolYear FROM schoolYear WHERE YEAR(startDate) = ? OR YEAR(endDate) = ? LIMIT 1";
    $stmtGetYear = $conexion->prepare($sqlGetYear);
    if (!$stmtGetYear) {
        header("Location: assignments.php?status=error&message=" . urlencode('Error al preparar consulta: ' . $conexion->error));
        exit;
    }
    $stmtGetYear->bind_param('ii', $currentYear, $currentYear);
    $stmtGetYear->execute();
    $resultYear = $stmtGetYear->get_result();
    
    if ($resultYear->num_rows === 0) {
        header("Location: assignments.php?status=error&message=" . urlencode('No existe un ciclo escolar para el año actual. Por favor, crea uno primero.'));
        exit;
    }
    
    $idSchoolYear = $resultYear->fetch_assoc()['idSchoolYear'];
    
    // Verificar si ya existe una asignación para este grupo, materia y ciclo escolar
    $checkSql = "SELECT tgs.idTeacher, tgs.idGroup, tgs.idSubject, ts.idSchoolYear,
                CONCAT(ui.names, ' ', ui.lastnamePa, ' ', ui.lastnameMa) as nombreDocente,
                sy.startDate as cicloInicio
                FROM teacherGroupsSubjects tgs
                INNER JOIN teacherSubject ts ON tgs.idTeacher = ts.idTeacher 
                    AND tgs.idSubject = ts.idSubject
                INNER JOIN schoolYear sy ON ts.idSchoolYear = sy.idSchoolYear
                INNER JOIN teachers t ON tgs.idTeacher = t.idTeacher
                INNER JOIN users u ON t.idUser = u.idUser
                INNER JOIN usersInfo ui ON u.idUserInfo = ui.idUserInfo
                WHERE tgs.idGroup = ? 
                AND tgs.idSubject = ?
                AND ts.idSchoolYear = ?";
    
    $checkStmt = $conexion->prepare($checkSql);
    $checkStmt->bind_param('iii', $idGroup, $idSubject, $idSchoolYear);
    $checkStmt->execute();
    $existingAssignment = $checkStmt->get_result()->fetch_assoc();
    
    if ($existingAssignment) {
        // Si ya existe una asignación para este grupo y materia
        $docenteAsignado = htmlspecialchars($existingAssignment['nombreDocente']);
        $cicloInicio = date('Y', strtotime($existingAssignment['cicloInicio']));
        $mensajeError = "No se puede realizar la asignación. El grupo y materia seleccionados ya están asignados al docente: $docenteAsignado para el ciclo escolar $cicloInicio";
        header("Location: assignments.php?status=error&message=" . urlencode($mensajeError));
        exit;
    }

    $conexion->begin_transaction();
    try {
        // Insertar en teacherSubject
        $sql1 = "INSERT INTO teacherSubject (idTeacher, idSubject, idSchoolYear) VALUES (?, ?, ?)";
        $stmt1 = $conexion->prepare($sql1);
        $stmt1->bind_param('iii', $idTeacher, $idSubject, $idSchoolYear);
        $stmt1->execute();

        // Insertar en teacherGroupsSubjects
        $sql2 = "INSERT INTO teacherGroupsSubjects (idTeacher, idGroup, idSubject) VALUES (?, ?, ?)";
        $stmt2 = $conexion->prepare($sql2);
        $stmt2->bind_param('iii', $idTeacher, $idGroup, $idSubject);
        $stmt2->execute();

        $conexion->commit();
        header("Location: assignments.php?status=success");
        exit();
    } catch (Exception $e) {
        $conexion->rollback();
        header("Location: assignments.php?status=error&message=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    header("Location: assignments.php?status=error&message=" . urlencode('Método no permitido.'));
    exit();
}
?>
