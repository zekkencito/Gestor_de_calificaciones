<?php
require_once "check_session.php";
include '../conection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $teacherId = $_POST['teacherId'];
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
    $status = $_POST['txtStatus'];
    $password = isset($_POST['txtPassword']) ? $_POST['txtPassword'] : '';

    try {
        // Iniciar transacción
        $conexion->begin_transaction();

        // Actualizar información del usuario
        $sqlUserInfo = "UPDATE usersInfo SET 
            names = ?, 
            lastnamePa = ?, 
            lastnameMa = ?, 
            gender = ?, 
            phone = ?, 
            email = ?, 
            street = ? 
            WHERE idUserInfo = (
                SELECT ui.idUserInfo 
                FROM teachers t 
                INNER JOIN users u ON t.idUser = u.idUser 
                INNER JOIN usersInfo ui ON u.idUserInfo = ui.idUserInfo 
                WHERE t.idTeacher = ?
            )";
        
        $stmtUserInfo = $conexion->prepare($sqlUserInfo);
        $stmtUserInfo->bind_param("sssssssi", $name, $lastnamePa, $lastnameMa, $gender, $phone, $email, $address, $teacherId);
        $stmtUserInfo->execute();

        // Actualizar información del profesor
        $sqlTeacher = "UPDATE teachers SET 
            profesionalID = ?, 
            ine = ?, 
            typeTeacher = ?, 
            idTeacherStatus = ? 
            WHERE idTeacher = ?";
        
        $stmtTeacher = $conexion->prepare($sqlTeacher);
        $stmtTeacher->bind_param("sssii", $profesionalID, $ine, $typeTeacher, $status, $teacherId);
        $stmtTeacher->execute();

        // Si se proporcionó una nueva contraseña, actualizarla
        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $sqlPassword = "UPDATE users SET 
                password = ?,
                raw_password = ?
                WHERE idUser = (
                    SELECT idUser 
                    FROM teachers 
                    WHERE idTeacher = ?
                )";
            $stmtPassword = $conexion->prepare($sqlPassword);
            $stmtPassword->bind_param("ssi", $hashedPassword, $password, $teacherId);
            $stmtPassword->execute();
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
