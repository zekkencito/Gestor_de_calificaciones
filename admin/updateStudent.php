<?php
require_once "check_session.php";
include '../conection.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $studentId = $_POST['studentId'];

        // Verificar si el estudiante existe
        $sqlCheckStudent = "SELECT idStudent, idTutor FROM students WHERE idStudent = ?";
        $stmtCheckStudent = $conexion->prepare($sqlCheckStudent);
        $stmtCheckStudent->bind_param("i", $studentId);
        $stmtCheckStudent->execute();
        $result = $stmtCheckStudent->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("El estudiante no existe");
        }
        $studentRow = $result->fetch_assoc();
        $idTutor = $studentRow['idTutor'];

        // Iniciar transacción
        $conexion->autocommit(FALSE);

        // 1. Obtener idUserInfo
        $sqlGetIds = "SELECT idUserInfo FROM students WHERE idStudent = ?";
        $stmtGetIds = $conexion->prepare($sqlGetIds);
        $stmtGetIds->bind_param("i", $studentId);
        $stmtGetIds->execute();
        $result = $stmtGetIds->get_result();
        $ids = $result->fetch_assoc();

        if (!$ids) {
            throw new Exception("No se encontraron los IDs asociados");
        }

        $idUserInfo = $ids['idUserInfo'];

        // 2. Actualizar usersInfo
        $sqlUpdateUserInfo = "UPDATE usersInfo SET 
            names = ?,
            lastnamePa = ?,
            lastnameMa = ?,
            phone = ?,
            street = ?,
            gender = ?,
            email = ?
            WHERE idUserInfo = ?";
        
        $stmtUpdateUserInfo = $conexion->prepare($sqlUpdateUserInfo);
        $stmtUpdateUserInfo->bind_param("sssssssi",
            $_POST['txtName'],
            $_POST['txtLastnamePa'],
            $_POST['txtLastnameMa'],
            $_POST['txtPhone'],
            $_POST['txtAddress'],
            $_POST['txtGender'],
            $_POST['txtEmail'],
            $idUserInfo
        );

        if (!$stmtUpdateUserInfo->execute()) {
            throw new Exception("Error al actualizar información del usuario: " . $conexion->error);
        }

        // 3. Actualizar users si se proporcionó nueva contraseña
        if (!empty($_POST['txtPassword'])) {
            $password = password_hash($_POST['txtPassword'], PASSWORD_DEFAULT);
            $sqlUpdateUser = "UPDATE users SET password = ? WHERE idUserInfo = ?";
            $stmtUpdateUser = $conexion->prepare($sqlUpdateUser);
            $stmtUpdateUser->bind_param("si", $password, $idUserInfo);
            
            if (!$stmtUpdateUser->execute()) {
                throw new Exception("Error al actualizar contraseña: " . $conexion->error);
            }
        }

        // 4. Actualizar students
        $sqlUpdateStudent = "UPDATE students SET 
            idStudentStatus = ?,
            schoolNum = ?,
            idGroup = ?,
            idSchoolYear = ?,
            curp = ?
            WHERE idStudent = ?";
            
        $stmtUpdateStudent = $conexion->prepare($sqlUpdateStudent);
        $schoolNum = isset($_POST['txtSchoolNum']) ? $_POST['txtSchoolNum'] : null;
        $idGroup = isset($_POST['txtGroup']) ? $_POST['txtGroup'] : null;
        $idSchoolYear = isset($_POST['txtSchoolYear']) ? $_POST['txtSchoolYear'] : null;
        $curp = isset($_POST['txtCurp']) ? $_POST['txtCurp'] : null;
        
        $stmtUpdateStudent->bind_param("iiissi",
            $_POST['txtStatus'],
            $schoolNum,
            $idGroup,
            $idSchoolYear,
            $curp,
            $studentId
        );

        if (!$stmtUpdateStudent->execute()) {
            throw new Exception("Error al actualizar estudiante: " . $conexion->error);
        }

        // 5. Actualizar tutor (si existe)
        if ($idTutor) {
            $sqlUpdateTutor = "UPDATE tutors SET 
                relative_ = ?,
                ine = ?,
                tutorLastnamePa = ?,
                tutorLastnameMa = ?,
                tutorName = ?,
                tutorPhone = ?,
                tutorAddress = ?,
                tutorEmail = ?
                WHERE idTutor = ?";
            $stmtUpdateTutor = $conexion->prepare($sqlUpdateTutor);
            $stmtUpdateTutor->bind_param("ssssssssi",
                $_POST['txtTutorRelative'],
                $_POST['txtTutorIne'],
                $_POST['txtTutorLastnamePa'],
                $_POST['txtTutorLastnameMa'],
                $_POST['txtTutorName'],
                $_POST['txtTutorPhone'],
                $_POST['txtTutorAddress'],
                $_POST['txtTutorEmail'],
                $idTutor
            );
            if (!$stmtUpdateTutor->execute()) {
                throw new Exception("Error al actualizar tutor: " . $conexion->error);
            }
        }

        // Confirmar transacción
        if (!$conexion->commit()) {
            throw new Exception("Error al confirmar los cambios: " . $conexion->error);
        }

        $conexion->autocommit(TRUE);
        echo json_encode(['success' => true, 'message' => 'Alumno actualizado correctamente']);
        exit();

    } catch (Exception $e) {
        $conexion->rollback();
        $conexion->autocommit(TRUE);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit();
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}
?>
