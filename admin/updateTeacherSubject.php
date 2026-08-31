<?php
require_once dirname(__DIR__) . '/enforce_post.php';
require_once 'check_session.php';
header('Content-Type: application/json');

require_once '../conection.php';

/* HTTP Method Enforcement centralizado via enforce_post.php */

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos no válidos']);
    exit;
}

$idDFM = isset($input['idDFM']) ? intval($input['idDFM']) : 0;
$idSubjectNew = isset($input['idSubjectNew']) ? intval($input['idSubjectNew']) : 0;
$idSubjectOld = isset($input['idSubjectOld']) ? intval($input['idSubjectOld']) : 0;
$idTeacher = isset($input['idTeacher']) ? intval($input['idTeacher']) : 0;

if ($idDFM <= 0 || $idSubjectNew <= 0 || $idTeacher <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Faltan datos requeridos']);
    exit;
}

try {
    $conexion->begin_transaction();

    // 1. Get current row from teacherGroupsSubjects
    $stmt = $conexion->prepare("SELECT idTeacher, idGroup, idSubject FROM teacherGroupsSubjects WHERE idDFM = ?");
    $stmt->bind_param("i", $idDFM);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        $conexion->rollback();
        echo json_encode(['success' => false, 'message' => 'Registro no encontrado']);
        exit;
    }

    $idGroup = $row['idGroup'];
    $currentIdSubject = intval($row['idSubject']);

    // If same subject, nothing to do
    if ($currentIdSubject === $idSubjectNew) {
        $conexion->rollback();
        echo json_encode(['success' => false, 'message' => 'La materia seleccionada es la misma que la actual']);
        exit;
    }

    // 2. Check for duplicate: same teacher + same group + same new subject already exists
    $stmt = $conexion->prepare("SELECT idDFM FROM teacherGroupsSubjects WHERE idTeacher = ? AND idGroup = ? AND idSubject = ?");
    $stmt->bind_param("iii", $idTeacher, $idGroup, $idSubjectNew);
    $stmt->execute();
    $dup = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($dup) {
        $conexion->rollback();
        echo json_encode(['success' => false, 'message' => 'El docente ya tiene esta materia asignada en ese grupo']);
        exit;
    }

    // 3. Update teacherGroupsSubjects: change the subject
    $stmt = $conexion->prepare("UPDATE teacherGroupsSubjects SET idSubject = ? WHERE idDFM = ?");
    $stmt->bind_param("ii", $idSubjectNew, $idDFM);
    $stmt->execute();
    $stmt->close();

    // 4. Sync teacherSubject table
    //    Find the teacherSubject row for the old subject (for this school year)
    //    If the teacher already has a row for the new subject, just delete the old one
    //    If not, update the old row to point to the new subject
    $idSchoolYear = null;

    // Get current school year from teacherGroupsSubjects context
    $stmt = $conexion->prepare("SELECT ts.idTeacherSubject, ts.idSubject, ts.idSchoolYear 
        FROM teacherSubject ts 
        WHERE ts.idTeacher = ? 
        ORDER BY ts.idTeacherSubject DESC");
    $stmt->bind_param("i", $idTeacher);
    $stmt->execute();
    $tsRows = $stmt->get_result();
    $stmt->close();

    $oldTSId = null;
    $newTSExists = false;
    $schoolYear = null;

    while ($tsRow = $tsRows->fetch_assoc()) {
        if ($schoolYear === null) {
            $schoolYear = $tsRow['idSchoolYear'];
        }
        if (intval($tsRow['idSubject']) === $currentIdSubject && $oldTSId === null) {
            $oldTSId = intval($tsRow['idTeacherSubject']);
        }
        if (intval($tsRow['idSubject']) === $idSubjectNew) {
            $newTSExists = true;
        }
    }

    if ($newTSExists) {
        // Teacher already has a teacherSubject row for the new subject
        // Delete the old subject row
        if ($oldTSId) {
            $stmt = $conexion->prepare("DELETE FROM teacherSubject WHERE idTeacherSubject = ?");
            $stmt->bind_param("i", $oldTSId);
            $stmt->execute();
            $stmt->close();
        }
    } else {
        // Update the old row to point to the new subject
        if ($oldTSId) {
            $stmt = $conexion->prepare("UPDATE teacherSubject SET idSubject = ? WHERE idTeacherSubject = ?");
            $stmt->bind_param("ii", $idSubjectNew, $oldTSId);
            $stmt->execute();
            $stmt->close();
        }
    }

    $conexion->commit();
    echo json_encode(['success' => true, 'message' => 'Materia actualizada correctamente']);

} catch (Exception $e) {
    $conexion->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al actualizar: ' . $e->getMessage()]);
}
