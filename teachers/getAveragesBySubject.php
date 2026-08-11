<?php
session_start();
require_once '../conection.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit;
}

// Habilitar CORS si es necesario
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$idSubject = isset($_GET['idSubject']) ? intval($_GET['idSubject']) : 0;
$idSchoolYear = isset($_GET['idSchoolYear']) ? intval($_GET['idSchoolYear']) : 0;
$idSchoolQuarter = isset($_GET['idSchoolQuarter']) ? intval($_GET['idSchoolQuarter']) : 0;
$idStudent = isset($_GET['idStudent']) ? intval($_GET['idStudent']) : 0;

if ($idSchoolYear && $idSchoolQuarter && $idStudent) {
    // Obtener el ID del maestro desde la sesión
    $idUser = $_SESSION['user_id'];
    $sqlTeacher = "SELECT idTeacher FROM teachers WHERE idUser = ?";
    $stmtTeacher = $conexion->prepare($sqlTeacher);
    $stmtTeacher->bind_param("i", $idUser);
    $stmtTeacher->execute();
    $resultTeacher = $stmtTeacher->get_result();
    $teacherRow = $resultTeacher->fetch_assoc();
    $idTeacher = $teacherRow['idTeacher'];
    
    // Consulta principal para obtener las calificaciones
    $sql = "SELECT DISTINCT 
                s.idStudent, 
                ui.lastnamePa, 
                ui.lastnameMa, 
                ui.names, 
                g.grade, 
                g.group_, 
                a.average,
                sub.name as subject_name
            FROM students s
            JOIN usersInfo ui ON s.idUserInfo = ui.idUserInfo
            JOIN groups g ON s.idGroup = g.idGroup
            JOIN teacherGroupsSubjects tgs ON g.idGroup = tgs.idGroup
            JOIN subjects sub ON tgs.idSubject = sub.idSubject
            LEFT JOIN average a ON a.idStudent = s.idStudent 
                AND a.idSubject = tgs.idSubject
                AND a.idSchoolYear = ?
                AND a.idSchoolQuarter = ?
            WHERE tgs.idTeacher = ?
            AND s.idSchoolYear = ?
            AND s.idStudent = ?";

    if ($idSubject > 0) {
        $sql .= " AND tgs.idSubject = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("iiiiii", $idSchoolYear, $idSchoolQuarter, $idTeacher, $idSchoolYear, $idStudent, $idSubject);
    } else {
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("iiiii", $idSchoolYear, $idSchoolQuarter, $idTeacher, $idSchoolYear, $idStudent);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $students = [];
    
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    
    echo json_encode(['success' => true, 'students' => $students]);
} else {
    echo json_encode(['success' => false, 'message' => 'Parámetros incompletos: año escolar, trimestre y estudiante son requeridos']);
}
?>