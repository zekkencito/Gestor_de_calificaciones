<?php
require_once "check_session.php";
include '../conection.php';

if (isset($_GET['id'])) {
    $teacherId = $_GET['id'];

    try {
        // Verificar si el profesor existe
        $sqlCheckTeacher = "SELECT idTeacher FROM teachers WHERE idTeacher = ?";
        $stmtCheckTeacher = $conexion->prepare($sqlCheckTeacher);
        $stmtCheckTeacher->bind_param("i", $teacherId);
        $stmtCheckTeacher->execute();
        $result = $stmtCheckTeacher->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("El docente no existe");
        }

        // Iniciar transacción
        $conexion->autocommit(FALSE);

        // 1. Obtener idUser e idUserInfo antes de eliminar el profesor
        $sqlGetIds = "SELECT idUser, idUserInfo FROM teachers WHERE idTeacher = ?";
        $stmtGetIds = $conexion->prepare($sqlGetIds);
        $stmtGetIds->bind_param("i", $teacherId);
        $stmtGetIds->execute();
        $result = $stmtGetIds->get_result();
        $ids = $result->fetch_assoc();

        if (!$ids) {
            throw new Exception("No se encontraron los IDs asociados");
        }

        $idUser = $ids['idUser'];
        $idUserInfo = $ids['idUserInfo'];

        // 2. Eliminar asignaciones de grupos y materias
        $sqlDeleteAssignments = "DELETE FROM teacherGroupsSubjects WHERE idTeacher = ?";
        $stmtDeleteAssignments = $conexion->prepare($sqlDeleteAssignments);
        $stmtDeleteAssignments->bind_param("i", $teacherId);
        if (!$stmtDeleteAssignments->execute()) {
            throw new Exception("Error al eliminar asignaciones de grupos: " . $conexion->error);
        }

        // 3. Eliminar asignaciones de materias
        $sqlDeleteTeacherSubject = "DELETE FROM teacherSubject WHERE idTeacher = ?";
        $stmtDeleteTeacherSubject = $conexion->prepare($sqlDeleteTeacherSubject);
        $stmtDeleteTeacherSubject->bind_param("i", $teacherId);
        if (!$stmtDeleteTeacherSubject->execute()) {
            throw new Exception("Error al eliminar asignaciones de materias: " . $conexion->error);
        }

        // 4. Eliminar el profesor
        $sqlDeleteTeacher = "DELETE FROM teachers WHERE idTeacher = ?";
        $stmtDeleteTeacher = $conexion->prepare($sqlDeleteTeacher);
        $stmtDeleteTeacher->bind_param("i", $teacherId);
        if (!$stmtDeleteTeacher->execute()) {
            throw new Exception("Error al eliminar profesor: " . $conexion->error);
        }

        // 5. Eliminar el usuario
        $sqlDeleteUser = "DELETE FROM users WHERE idUser = ?";
        $stmtDeleteUser = $conexion->prepare($sqlDeleteUser);
        $stmtDeleteUser->bind_param("i", $idUser);
        if (!$stmtDeleteUser->execute()) {
            throw new Exception("Error al eliminar usuario: " . $conexion->error);
        }

        // 6. Eliminar la información del usuario
        $sqlDeleteUserInfo = "DELETE FROM usersInfo WHERE idUserInfo = ?";
        $stmtDeleteUserInfo = $conexion->prepare($sqlDeleteUserInfo);
        $stmtDeleteUserInfo->bind_param("i", $idUserInfo);
        if (!$stmtDeleteUserInfo->execute()) {
            throw new Exception("Error al eliminar información del usuario: " . $conexion->error);
        }

        // Confirmar transacción
        if (!$conexion->commit()) {
            throw new Exception("Error al confirmar los cambios: " . $conexion->error);
        }
        $conexion->autocommit(TRUE);
        header("Location: teachers.php?status=3"); // 3 = eliminación exitosa
        exit();

    } catch (Exception $e) {
        // Si hay error, revertir cambios
        $conexion->rollback();
        $conexion->autocommit(TRUE);
        header("Location: teachers.php?status=error&message=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    header("Location: teachers.php?status=error&message=No%20se%20proporcionó%20ID");
    exit();
}
?>