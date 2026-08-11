<?php
require_once "check_session.php";
include '../conection.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // ================= VALIDACIONES =================
        // Validar campos obligatorios del estudiante
        $requiredStudentFields = [
            'txtName' => 'Nombre',
            'txtLastnamePa' => 'Apellido Paterno',
            'txtGender' => 'Género',
            'txtCurp' => 'CURP',
            'txtGroup' => 'Grupo',
            'txtSchoolYear' => 'Año escolar'
        ];
        
        foreach ($requiredStudentFields as $field => $name) {
            if (empty($_POST[$field])) {
                throw new Exception("El campo {$name} del estudiante es requerido");
            }
        }

        // Validar campos obligatorios del tutor
        $requiredTutorFields = [
            'txtTutorName' => 'Nombre del tutor',
            'txtTutorLastnames' => 'Apellidos del tutor',
            'txtTutorPhone' => 'Teléfono del tutor'
        ];
        
        foreach ($requiredTutorFields as $field => $name) {
            if (empty($_POST[$field])) {
                throw new Exception("El campo {$name} es requerido");
            }
        }

        // ================= PROCESAMIENTO DE APELLIDOS =================
        // Procesar apellidos del tutor (dividir en paterno y materno)
        $tutorLastnames = explode(' ', trim($_POST['txtTutorLastnames']), 2);
        $tutorLastnamePa = $tutorLastnames[0];
        $tutorLastnameMa = isset($tutorLastnames[1]) ? $tutorLastnames[1] : '';

        // Validar que al menos el apellido paterno del tutor esté presente
        if (empty($tutorLastnamePa)) {
            throw new Exception("El apellido paterno del tutor es obligatorio");
        }

        // Obtener apellidos del estudiante (ya vienen separados)
        $studentLastnamePa = $_POST['txtLastnamePa'];
        $studentLastnameMa = $_POST['txtLastnameMa'] ?? ''; // El materno es opcional

        // ================= INICIAR TRANSACCIÓN =================
        $conexion->begin_transaction();

        /* ========== INSERCIÓN DEL TUTOR ========== */
        $sqlTutor = "INSERT INTO tutors (
            relative_, ine, ineDocument, 
            tutorLastnamePa, tutorLastnameMa, tutorName, 
            tutorPhone, tutorAddress, tutorNeighborhood, tutorEmail
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmtTutor = $conexion->prepare($sqlTutor);
        if (!$stmtTutor) {
            throw new Exception("Error al preparar consulta del tutor: " . $conexion->error);
        }

        $tutorData = [
            'relative' => $_POST['txtTutorRelative'] ?? '',
            'ine' => $_POST['txtTutorIne'] ?? '',
            'ineDocument' => $_POST['txtTutorIneDocument'] ?? '',
            'lastnamePa' => $tutorLastnamePa,
            'lastnameMa' => $tutorLastnameMa,
            'name' => $_POST['txtTutorName'],
            'phone' => $_POST['txtTutorPhone'],
            'address' => $_POST['txtTutorAddress'] ?? '',
            'neighborhood' => $_POST['txtTutorNeighborhood'] ?? '',
            'email' => $_POST['txtTutorEmail'] ?? ''
        ];

        $stmtTutor->bind_param(
            "ssssssssss",
            $tutorData['relative'],
            $tutorData['ine'],
            $tutorData['ineDocument'],
            $tutorData['lastnamePa'],
            $tutorData['lastnameMa'],
            $tutorData['name'],
            $tutorData['phone'],
            $tutorData['address'],
            $tutorData['neighborhood'],
            $tutorData['email']
        );
        
        if (!$stmtTutor->execute()) {
            throw new Exception("Error al registrar tutor: " . $stmtTutor->error);
        }
        $idTutor = $conexion->insert_id;

        /* ========== INSERCIÓN DEL ESTUDIANTE (usersInfo) ========== */
        $sqlUserInfo = "INSERT INTO usersInfo (
            names, lastnamePa, lastnameMa, phone, street, 
            gender, email, birthDate, number_, neighborhood, cp
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmtUserInfo = $conexion->prepare($sqlUserInfo);
        if (!$stmtUserInfo) {
            throw new Exception("Error al preparar consulta de userInfo: " . $conexion->error);
        }

        $studentData = [
            'name' => $_POST['txtName'],
            'lastnamePa' => $studentLastnamePa,
            'lastnameMa' => $studentLastnameMa,
            'phone' => $_POST['txtPhone'] ?? '',
            'address' => $_POST['txtAddress'] ?? '',
            'gender' => $_POST['txtGender'],
            'email' => $_POST['txtEmail'] ?? '',
            'birthDate' => !empty($_POST['txtBirthDate']) ? $_POST['txtBirthDate'] : null,
            'number' => $_POST['txtNumber'] ?? '',
            'neighborhood' => $_POST['txtNeighborhood'] ?? '',
            'cp' => $_POST['txtCP'] ?? ''
        ];

        $stmtUserInfo->bind_param(
            "sssssssssss", 
            $studentData['name'],
            $studentData['lastnamePa'],
            $studentData['lastnameMa'],
            $studentData['phone'],
            $studentData['address'],
            $studentData['gender'],
            $studentData['email'],
            $studentData['birthDate'],
            $studentData['number'],
            $studentData['neighborhood'],
            $studentData['cp']
        );
        
        if (!$stmtUserInfo->execute()) {
            throw new Exception("Error al insertar información del estudiante: " . $stmtUserInfo->error);
        }
        $idUserInfo = $conexion->insert_id;

        /* ========== INSERCIÓN DEL ESTUDIANTE (students) ========== */
        $sqlStudent = "INSERT INTO students (
            idUserInfo, idStudentStatus, idGroup, idSchoolYear, idTutor, curp
        ) VALUES (?, 1, ?, ?, ?, ?)";
        
        $stmtStudent = $conexion->prepare($sqlStudent);
        if (!$stmtStudent) {
            throw new Exception("Error al preparar consulta de estudiante: " . $conexion->error);
        }

        $stmtStudent->bind_param(
            "iiiis", 
            $idUserInfo,
            $_POST['txtGroup'],
            $_POST['txtSchoolYear'],
            $idTutor,
            $_POST['txtCurp']
        );
        
        if (!$stmtStudent->execute()) {
            throw new Exception("Error al registrar estudiante: " . $stmtStudent->error);
        }

        // ================= CONFIRMAR TRANSACCIÓN =================
        $conexion->commit();

        // Redireccionar con éxito
        header("Location: students.php?status=success");
        exit();

    } catch (Exception $e) {
        // Revertir en caso de error
        if (isset($conexion)) {
            $conexion->rollback();
        }
        
        // Registrar error para depuración
        error_log("Error en addStudent: " . $e->getMessage());
        
        // Redireccionar con error
        header("Location: students.php?status=error&message=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>