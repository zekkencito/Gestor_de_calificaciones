<?php
require_once "check_session.php";
include '../conection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener datos del formulario
    $name = $_POST['txtName'];
    $lastnamePa = $_POST['txtLastnamePa'];
    $lastnameMa = $_POST['txtLastnameMa'];
    $gender = $_POST['txtGender'];
    $typeTeacher = $_POST['txtTypeTeacher'];
    $ine = $_POST['txtIne'];
    $profesionalID = $_POST['txtProfesional'];
    $phone = $_POST['txtPhone'];
    $email = $_POST['txtEmail'];
    $address = $_POST['txtAddress'];
    $grupos = isset($_POST['grupos']) ? $_POST['grupos'] : [];
    $materias = isset($_POST['materias']) ? $_POST['materias'] : [];

    // Verificar si ya existe un maestro con los mismos datos
    $checkSql = "SELECT t.idTeacher 
                FROM teachers t 
                INNER JOIN users u ON t.idUser = u.idUser 
                INNER JOIN usersInfo ui ON u.idUserInfo = ui.idUserInfo 
                WHERE ui.names = ? 
                AND ui.lastnamePa = ? 
                AND ui.lastnameMa = ? 
                AND t.ine = ? 
                AND t.profesionalID = ?";
    $stmtCheck = $conexion->prepare($checkSql);
    $stmtCheck->bind_param("sssss", $name, $lastnamePa, $lastnameMa, $ine, $profesionalID);
    $stmtCheck->execute();
    $result = $stmtCheck->get_result();

    if ($result->num_rows > 0) {
        header("Location: teachers.php?status=duplicate");
        exit();
    }

    // Generar nombre de usuario y contraseña
    $username = strtolower(explode(' ', $name)[0]) . rand(10, 99);
    $rawPassword = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 6);
    $password = password_hash($rawPassword, PASSWORD_DEFAULT);

    try {
        // Iniciar transacción
        $conexion->begin_transaction();

        // 1. Insertar en usersInfo
        $sqlUserInfo = "INSERT INTO usersInfo (names, lastnamePa, lastnameMa, gender, phone, email, street) 
                       VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmtUserInfo = $conexion->prepare($sqlUserInfo);
        $stmtUserInfo->bind_param("sssssss", $name, $lastnamePa, $lastnameMa, $gender, $phone, $email, $address);
        $stmtUserInfo->execute();
        $idUserInfo = $conexion->insert_id;

        // 2. Insertar en users (marcando que necesita cambio de contraseña)
        $sqlUser = "INSERT INTO users (username, password, raw_password, idRole, idUserInfo, password_changed) 
                   VALUES (?, ?, ?, (SELECT idRole FROM roles WHERE level_ = ?), ?, 0)";
        $stmtUser = $conexion->prepare($sqlUser);
        $stmtUser->bind_param("ssssi", $username, $password, $rawPassword, $typeTeacher, $idUserInfo);
        $stmtUser->execute();
        $idUser = $conexion->insert_id;

        // 3. Insertar en teachers (con estado activo por defecto)
        $sqlTeacher = "INSERT INTO teachers (profesionalID, ine, typeTeacher, idTeacherStatus, idUserInfo, idUser) 
                      VALUES (?, ?, ?, 1, ?, ?)";
        $stmtTeacher = $conexion->prepare($sqlTeacher);
        $stmtTeacher->bind_param("sssii", $profesionalID, $ine, $typeTeacher, $idUserInfo, $idUser);
        $stmtTeacher->execute();
        $idTeacher = $conexion->insert_id;

        // 4. Insertar asignaciones de grupos y materias
        if (!empty($grupos) && !empty($materias)) {
            $sqlAssignment = "INSERT INTO teacherGroupsSubjects (idTeacher, idGroup, idSubject) VALUES (?, ?, ?)";
            $stmtAssignment = $conexion->prepare($sqlAssignment);
            
            foreach ($grupos as $groupId) {
                foreach ($materias as $subjectId) {
                    $stmtAssignment->bind_param("iii", $idTeacher, $groupId, $subjectId);
                    $stmtAssignment->execute();
                }
            }
        }

        // Confirmar transacción
        $conexion->commit();
        header("Location: teachers.php?status=success");
        exit();

    } catch (Exception $e) {
        // Si hay error, revertir cambios
        $conexion->rollback();
        header("Location: teachers.php?status=error");
        exit();
    }
}
?>
