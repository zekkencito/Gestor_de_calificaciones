<?php
require_once "check_session.php";
include '../conection.php';

header('Content-Type: application/json');

try {
    if (!isset($_POST['studentId']) || empty($_POST['studentId'])) {
        throw new Exception('ID de estudiante no proporcionado');
    }

    $studentId = intval($_POST['studentId']);
    
    // Iniciar transacción
    $conexion->begin_transaction();

    try {
        // Primero obtenemos el ID del tutor y userInfo asociados al estudiante
        $stmt = $conexion->prepare("SELECT idTutor, idUserInfo FROM students WHERE idStudent = ?");
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $student = $result->fetch_assoc();

        if (!$student) {
            throw new Exception('Estudiante no encontrado');
        }

        $tutorId = $student['idTutor'];
        $userInfoId = $student['idUserInfo'];

        // Eliminar registros relacionados en orden para mantener la integridad referencial

        // 1. Verificar y eliminar registros de calificaciones si la tabla existe
        $checkTable = $conexion->query("SHOW TABLES LIKE 'gradesSubject'");
        if ($checkTable->num_rows > 0) {
            $stmt = $conexion->prepare("DELETE FROM gradesSubject WHERE idStudent = ?");
            $stmt->bind_param("i", $studentId);
            $stmt->execute();
        }

        // 2. Verificar y eliminar registros de conducta si la tabla existe
        $checkTable = $conexion->query("SHOW TABLES LIKE 'conductReports'");
        if ($checkTable->num_rows > 0) {
            $stmt = $conexion->prepare("DELETE FROM conductReports WHERE idStudent = ?");
            $stmt->bind_param("i", $studentId);
            $stmt->execute();
        }

        // 3. Verificar y eliminar registros de kardex si la tabla existe
        $checkTable = $conexion->query("SHOW TABLES LIKE 'kardex'");
        if ($checkTable->num_rows > 0) {
            $stmt = $conexion->prepare("DELETE FROM kardex WHERE idStudent = ?");
            $stmt->bind_param("i", $studentId);
            $stmt->execute();
        }

        // 4. Eliminar el estudiante
        $stmt = $conexion->prepare("DELETE FROM students WHERE idStudent = ?");
        $stmt->bind_param("i", $studentId);
        if (!$stmt->execute()) {
            throw new Exception('Error al eliminar el estudiante de la tabla students');
        }

        // 5. Eliminar el tutor si existe
        if ($tutorId) {
            $stmt = $conexion->prepare("DELETE FROM tutors WHERE idTutor = ?");
            $stmt->bind_param("i", $tutorId);
            if (!$stmt->execute()) {
                throw new Exception('Error al eliminar el tutor');
            }
        }

        // 6. Eliminar el usuario y su información si existe
        if ($userInfoId) {
            // Primero eliminar de la tabla users
            $stmt = $conexion->prepare("DELETE FROM users WHERE idUserInfo = ?");
            $stmt->bind_param("i", $userInfoId);
            $stmt->execute();

            // Luego eliminar de la tabla usersInfo
            $stmt = $conexion->prepare("DELETE FROM usersInfo WHERE idUserInfo = ?");
            $stmt->bind_param("i", $userInfoId);
            if (!$stmt->execute()) {
                throw new Exception('Error al eliminar la información del usuario');
            }
        }

        // Si todo salió bien, confirmar la transacción
        $conexion->commit();

        echo json_encode([
            'success' => true, 
            'message' => 'Estudiante y registros relacionados eliminados correctamente'
        ]);

    } catch (Exception $e) {
        // Si hay algún error, revertir la transacción
        $conexion->rollback();
        throw $e;
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conexion->close();
?>
