<?php
require_once dirname(__DIR__) . '/enforce_post.php';
require_once "check_session.php";
require_once '../conection.php';
header('Content-Type: application/json');

/* HTTP Method Enforcement centralizado via enforce_post.php */

$input = json_decode(file_get_contents('php://input'), true);

$idTeacherGroup = isset($input['idTeacherGroup']) ? intval($input['idTeacherGroup']) : 0;
$idTeacher = isset($input['idTeacher']) ? intval($input['idTeacher']) : 0;
$idGroup = isset($input['idGrupo']) ? intval($input['idGrupo']) : 0;
$idSchoolYear = isset($input['idSchoolYear']) ? intval($input['idSchoolYear']) : 0;
$idSubject = isset($input['idSubject']) ? intval($input['idSubject']) : 0;

if ($idSubject <= 0) {
    echo json_encode(['success' => false, 'message' => 'Seleccione una materia.']);
    exit;
}

$currentYear = date('Y');
$stmtYear = $conexion->prepare("SELECT idSchoolYear FROM schoolYear WHERE YEAR(startDate) = ? OR YEAR(endDate) = ? LIMIT 1");
$stmtYear->bind_param('ii', $currentYear, $currentYear);
$stmtYear->execute();
$resultYear = $stmtYear->get_result();

if ($resultYear->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'No existe un ciclo escolar para el año actual.']);
    exit;
}
$idSchoolYearCurrent = $resultYear->fetch_assoc()['idSchoolYear'];

// Resolve teacher+group from teacherGroup or from direct params
if ($idTeacherGroup > 0) {
    $stmtTG = $conexion->prepare("SELECT idTeacher, idGroup, idSchoolYear FROM teacherGroup WHERE idTeacherGroup = ?");
    $stmtTG->bind_param('i', $idTeacherGroup);
    $stmtTG->execute();
    $tg = $stmtTG->get_result()->fetch_assoc();
    if (!$tg) {
        echo json_encode(['success' => false, 'message' => 'Relación docente-grupo no encontrada.']);
        exit;
    }
    $idTeacher = intval($tg['idTeacher']);
    $idGroup = intval($tg['idGroup']);
    $idSchoolYear = intval($tg['idSchoolYear']);
} else {
    if ($idTeacher <= 0 || $idGroup <= 0) {
        echo json_encode(['success' => false, 'message' => 'Faltan datos requeridos.']);
        exit;
    }
    if ($idSchoolYear <= 0) {
        $idSchoolYear = $idSchoolYearCurrent;
    }
}

// Check duplicate in teacherGroupsSubjects
$stmtDup = $conexion->prepare("SELECT idDFM FROM teacherGroupsSubjects WHERE idTeacher=? AND idGroup=? AND idSubject=?");
$stmtDup->bind_param('iii', $idTeacher, $idGroup, $idSubject);
$stmtDup->execute();
if ($stmtDup->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Esta materia ya está asignada a este docente en este grupo.']);
    exit;
}

$conexion->begin_transaction();
try {
    // Ensure teacherGroup exists (create if missing — for old assignments)
    if ($idTeacherGroup <= 0) {
        $stmtCheckTG = $conexion->prepare("SELECT idTeacherGroup FROM teacherGroup WHERE idTeacher=? AND idGroup=? AND idSchoolYear=?");
        $stmtCheckTG->bind_param('iii', $idTeacher, $idGroup, $idSchoolYear);
        $stmtCheckTG->execute();
        $existingTG = $stmtCheckTG->get_result()->fetch_assoc();
        if ($existingTG) {
            $idTeacherGroup = intval($existingTG['idTeacherGroup']);
        } else {
            $stmtInsTG = $conexion->prepare("INSERT INTO teacherGroup (idTeacher, idGroup, idSchoolYear) VALUES (?, ?, ?)");
            $stmtInsTG->bind_param('iii', $idTeacher, $idGroup, $idSchoolYear);
            $stmtInsTG->execute();
            $idTeacherGroup = $conexion->insert_id;
        }
    }

    // 1. Insert teacherGroupsSubjects
    $stmtTGS = $conexion->prepare("INSERT INTO teacherGroupsSubjects (idTeacher, idGroup, idSubject) VALUES (?, ?, ?)");
    $stmtTGS->bind_param('iii', $idTeacher, $idGroup, $idSubject);
    $stmtTGS->execute();

    // 2. Ensure teacherSubject exists
    $stmtCheckTS = $conexion->prepare("SELECT idTeacherSubject FROM teacherSubject WHERE idTeacher=? AND idSubject=? AND idSchoolYear=?");
    $stmtCheckTS->bind_param('iii', $idTeacher, $idSubject, $idSchoolYear);
    $stmtCheckTS->execute();
    if ($stmtCheckTS->get_result()->num_rows === 0) {
        $stmtInsTS = $conexion->prepare("INSERT INTO teacherSubject (idTeacher, idSubject, idSchoolYear) VALUES (?, ?, ?)");
        $stmtInsTS->bind_param('iii', $idTeacher, $idSubject, $idSchoolYear);
        $stmtInsTS->execute();
    }

    $conexion->commit();
    echo json_encode(['success' => true, 'message' => 'Materia añadida correctamente.']);
} catch (Exception $e) {
    $conexion->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
