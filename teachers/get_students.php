<?php
require_once "check_session.php";
require_once "../conection.php";

header('Content-Type: application/json');

$grupo = isset($_GET['grupo']) ? intval($_GET['grupo']) : 0;
$schoolYear = isset($_GET['schoolYear']) ? intval($_GET['schoolYear']) : 0;

try {
    $students = [];
    if ($grupo && $schoolYear) {
        $query = "SELECT s.idStudent, s.schoolNum, ui.lastnamePa, ui.lastnameMa, ui.names, g.grade, g.group_, 
                s.idStudentStatus, s.curp, t.tutorName, t.tutorLastnamePa, t.tutorLastnameMa, 
                t.tutorPhone, t.tutorAddress, t.tutorEmail, t.ine as tutorIne,
                st.nomenclature, st.description
                FROM students s
                JOIN usersInfo ui ON s.idUserInfo = ui.idUserInfo
                JOIN groups g ON s.idGroup = g.idGroup
                LEFT JOIN tutors t ON s.idTutor = t.idTutor
                LEFT JOIN studentStatus st ON s.idStudentStatus = st.idStudentStatus
                WHERE s.idGroup = ? AND s.idSchoolYear = ?
                ORDER BY ui.lastnamePa, ui.lastnameMa, ui.names";

        $stmt = $conexion->prepare($query);
        $stmt->bind_param("ii", $grupo, $schoolYear);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
        $stmt->close();
    }

    echo json_encode([
        'success' => true,
        'students' => $students
    ]);

} catch (Exception $e) {
    error_log("Error en get_students.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener los estudiantes'
    ]);
}
?> 