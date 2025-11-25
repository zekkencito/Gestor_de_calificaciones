<?php
require_once "check_session.php";
include '../conection.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idTeacher = isset($_POST['docente']) ? intval($_POST['docente']) : null;
    $idGroup = isset($_POST['grupo']) ? intval($_POST['grupo']) : null;
    $idSubject = isset($_POST['materia']) ? intval($_POST['materia']) : null;
    $idSchoolYear = isset($_POST['ciclo']) ? intval($_POST['ciclo']) : null;
    $oldTeacher = isset($_POST['old_docente']) ? intval($_POST['old_docente']) : null;
    $oldGroup = isset($_POST['old_grupo']) ? intval($_POST['old_grupo']) : null;
    $oldSubject = isset($_POST['old_materia']) ? intval($_POST['old_materia']) : null;
    
    if (!$idTeacher || !$idGroup || !$idSubject || !$idSchoolYear || !$oldTeacher || !$oldGroup || !$oldSubject) {
        echo json_encode(['success' => false, 'message' => 'Faltan datos para actualizar la asignación.']);
        exit;
    }
    $conexion->begin_transaction();
    try {
        // 1. Actualizar la asignación específica (teacherGroupsSubjects)
        // Esto cambia qué materia enseña el maestro a este grupo específico
        $sql1 = "UPDATE teacherGroupsSubjects SET idTeacher=?, idGroup=?, idSubject=? WHERE idTeacher=? AND idGroup=? AND idSubject=?";
        $stmt1 = $conexion->prepare($sql1);
        $stmt1->bind_param('iiiiii', $idTeacher, $idGroup, $idSubject, $oldTeacher, $oldGroup, $oldSubject);
        $stmt1->execute();

        // 2. Asegurar que exista la relación teacherSubject para el nuevo par Maestro-Materia-Ciclo
        // No usamos UPDATE porque eso cambiaría el ciclo para TODOS los grupos que toman esta materia con este maestro
        $sqlCheck = "SELECT idTeacherSubject FROM teacherSubject WHERE idTeacher=? AND idSubject=? AND idSchoolYear=?";
        $stmtCheck = $conexion->prepare($sqlCheck);
        $stmtCheck->bind_param('iii', $idTeacher, $idSubject, $idSchoolYear);
        $stmtCheck->execute();
        $resultCheck = $stmtCheck->get_result();

        if ($resultCheck->num_rows === 0) {
            // Si no existe, la creamos
            $sqlInsert = "INSERT INTO teacherSubject (idTeacher, idSubject, idSchoolYear) VALUES (?, ?, ?)";
            $stmtInsert = $conexion->prepare($sqlInsert);
            $stmtInsert->bind_param('iii', $idTeacher, $idSubject, $idSchoolYear);
            $stmtInsert->execute();
        }

        // 3. (Opcional) Limpieza: Verificar si la antigua relación teacherSubject ya no se usa
        // Si cambiamos de materia o maestro, la antigua combinación podría quedar huérfana
        if ($oldTeacher != $idTeacher || $oldSubject != $idSubject) {
            $sqlCount = "SELECT COUNT(*) as total FROM teacherGroupsSubjects WHERE idTeacher=? AND idSubject=?";
            $stmtCount = $conexion->prepare($sqlCount);
            $stmtCount->bind_param('ii', $oldTeacher, $oldSubject);
            $stmtCount->execute();
            $resCount = $stmtCount->get_result()->fetch_assoc();

            if ($resCount['total'] == 0) {
                // Ya nadie usa esta combinación, podemos borrarla de teacherSubject para mantener limpia la BD
                // Nota: Esto asume que teacherSubject solo sirve para asignaciones activas. 
                // Si se usa para historial, mejor no borrar. Por seguridad, comentamos el borrado automático o lo dejamos solo si estamos seguros.
                // $sqlDelete = "DELETE FROM teacherSubject WHERE idTeacher=? AND idSubject=?";
                // ...
            }
        }

        $conexion->commit();
        echo json_encode(['success' => true]);
    } catch(Exception $e) {
        $conexion->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>
