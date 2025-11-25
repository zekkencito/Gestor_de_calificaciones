<?php
require_once "check_session.php";
include '../conection.php';
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idTeacher = isset($_POST['idTeacher']) ? intval($_POST['idTeacher']) : null;
    $idGroup = isset($_POST['idGroup']) ? intval($_POST['idGroup']) : null;
    $idSubject = isset($_POST['idSubject']) ? intval($_POST['idSubject']) : null;
    $idSchoolYear = isset($_POST['idSchoolYear']) ? intval($_POST['idSchoolYear']) : null;
    if (!$idTeacher || !$idGroup || !$idSubject || !$idSchoolYear) {
        echo json_encode(['success' => false, 'message' => 'Faltan datos para eliminar la asignación.']);
        exit;
    }
    $conexion->begin_transaction();
    try {
        // Eliminar de teacherGroupsSubjects
        $sql1 = "DELETE FROM teacherGroupsSubjects WHERE idTeacher=? AND idGroup=? AND idSubject=?";
        $stmt1 = $conexion->prepare($sql1);
        $stmt1->bind_param('iii', $idTeacher, $idGroup, $idSubject);
        $stmt1->execute();
        
        // Verificar si se eliminó algo
        if ($stmt1->affected_rows > 0) {
            // Intentar limpiar teacherSubject (opcional)
            try {
                $sqlCheck = "SELECT COUNT(*) as total FROM teacherGroupsSubjects WHERE idTeacher=? AND idSubject=?";
                $stmtCheck = $conexion->prepare($sqlCheck);
                $stmtCheck->bind_param('ii', $idTeacher, $idSubject);
                $stmtCheck->execute();
                $result = $stmtCheck->get_result()->fetch_assoc();
                
                if ($result['total'] == 0) {
                    $sql2 = "DELETE FROM teacherSubject WHERE idTeacher=? AND idSubject=? AND idSchoolYear=?";
                    $stmt2 = $conexion->prepare($sql2);
                    $stmt2->bind_param('iii', $idTeacher, $idSubject, $idSchoolYear);
                    $stmt2->execute();
                }
            } catch (Exception $e) {
                // Si falla la limpieza de teacherSubject (por ejemplo, por FK de calificaciones),
                // ignoramos el error y permitimos que se complete la eliminación de la asignación.
                // El registro en teacherSubject quedará huérfano de grupos pero es inofensivo.
            }
            
            $conexion->commit();
            echo json_encode(['success' => true]);
        } else {
            // No se encontró la asignación
            $conexion->rollback();
            echo json_encode(['success' => false, 'message' => 'No se encontró la asignación para eliminar.']);
        }
    } catch(Exception $e) {
        $conexion->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>
