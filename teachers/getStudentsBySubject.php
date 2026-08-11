<?php
require_once "../conection.php";
require_once "check_session.php";

header('Content-Type: application/json');

if (!isset($_GET['idSubject']) || empty($_GET['idSubject'])) {
    echo json_encode(['success' => false, 'error' => 'No subject specified']);
    exit;
}

$idSubject = intval($_GET['idSubject']);
$idSchoolYear = isset($_GET['idSchoolYear']) ? intval($_GET['idSchoolYear']) : 0;
$idSchoolQuarter = isset($_GET['idSchoolQuarter']) ? intval($_GET['idSchoolQuarter']) : 0;

// Obtener el ID del maestro desde la sesión para filtrar solo sus materias
$user_id = $_SESSION['user_id'];
$sqlTeacher = "SELECT idTeacher FROM teachers WHERE idUser = ?";
$stmtTeacher = $conexion->prepare($sqlTeacher);
$stmtTeacher->bind_param("i", $user_id);
$stmtTeacher->execute();
$resTeacher = $stmtTeacher->get_result();
$rowTeacher = $resTeacher->fetch_assoc();
$teacher_id = $rowTeacher ? $rowTeacher['idTeacher'] : null;

if (!$teacher_id) {
    echo json_encode(['success' => false, 'error' => 'Teacher not found']);
    exit;
}

// Obtener alumnos con sus calificaciones para la materia, año y trimestre específicos
$query = "SELECT DISTINCT s.idStudent, ui.lastnamePa, ui.lastnameMa, ui.names, g.grade, g.group_, a.average
          FROM students s
          JOIN usersInfo ui ON s.idUserInfo = ui.idUserInfo
          JOIN groups g ON s.idGroup = g.idGroup
          JOIN teacherGroupsSubjects tgs ON tgs.idGroup = g.idGroup
          LEFT JOIN average a ON a.idStudent = s.idStudent 
              AND a.idSubject = tgs.idSubject
              AND a.idSchoolYear = ?
              AND a.idSchoolQuarter = ?
          WHERE tgs.idSubject = ? 
          AND tgs.idTeacher = ?";

$params = [$idSchoolYear, $idSchoolQuarter, $idSubject, $teacher_id];
$types = "iiii";

if ($idSchoolYear > 0) {
    $query .= " AND s.idSchoolYear = ?";
    $params[] = $idSchoolYear;
    $types .= "i";
}

$query .= " ORDER BY ui.lastnamePa, ui.lastnameMa, ui.names";

$stmt = $conexion->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}

// DEBUG: imprime los alumnos enviados en JSON
file_put_contents(__DIR__.'/debug_students_ajax.txt', json_encode(['params'=>[$idSubject, $idSchoolYear, $idSchoolQuarter], 'students'=>$students], JSON_PRETTY_PRINT)."\n", FILE_APPEND);

echo json_encode(['success' => true, 'students' => $students]);
