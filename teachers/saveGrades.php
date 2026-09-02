<?php
require_once dirname(__DIR__) . '/enforce_post.php';
require_once "../conection.php";
require_once "check_session.php";
header('Content-Type: application/json');
try {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['idSubject'], $data['idSchoolYear'], $data['idSchoolQuarter'], $data['grades'])) {
        error_log('Error: Datos incompletos en saveGrades');
        throw new Exception('No se recibieron todos los datos necesarios. Por favor, verifique la información.');
    }
    if (empty($data['grades'])) {
        error_log('Error: No se recibieron calificaciones en saveGrades');
        throw new Exception('No se encontraron calificaciones para guardar.');
    }
    // Validar que todas las calificaciones estén en el rango 6-10
    foreach ($data['grades'] as $studentGrade) {
        if (isset($studentGrade['grades']) && is_array($studentGrade['grades'])) {
            foreach ($studentGrade['grades'] as $criteriaKey => $gradeData) {
                if (isset($gradeData['grade']) && $gradeData['grade'] !== '' && $gradeData['grade'] !== null) {
                    $gradeValue = floatval($gradeData['grade']);
                    if ($gradeValue < 6 || $gradeValue > 10) {
                        throw new Exception('Calificación inválida: ' . $gradeData['grade'] . '. Las calificaciones deben estar entre 6 y 10.');
                    }
                }
            }
        }
    }
    $conexion->begin_transaction();
    // Obtener el nombre del trimestre
    $stmtQuarter = $conexion->prepare("SELECT name FROM schoolQuarter WHERE idSchoolQuarter = ?");
    $stmtQuarter->bind_param("i", $data['idSchoolQuarter']);
    $stmtQuarter->execute();
    $resQuarter = $stmtQuarter->get_result()->fetch_assoc();
    $quarter = $resQuarter ? $resQuarter['name'] : null;
    // Preparar la consulta para insertar/actualizar calificaciones
    $stmt = $conexion->prepare("INSERT INTO gradesSubject (grade, evalDate, idStudent, idSubject, idEvalCriteria, idSchoolYear, idSchoolQuarter, quarter, status) VALUES (?, CURRENT_DATE(), ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE grade = ?, evalDate = CURRENT_DATE(), quarter = ?, status = ?");
    if ($stmt === false) {
        error_log('Error al preparar statement en saveGrades: ' . $conexion->error);
        throw new Exception('Error al procesar las calificaciones. Por favor, intente nuevamente.');
    }
    // Preparar la consulta para insertar/actualizar promedio
    $stmtAvg = $conexion->prepare("INSERT INTO average (average, idStudent, idSubject, idSchoolYear, idSchoolQuarter) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE average = ?, idSubject = ?, idStudent = ?, idSchoolYear = ?, idSchoolQuarter = ?");
    if ($stmtAvg !== false) {
        foreach ($data['grades'] as $studentGrade) {
            $idStudent = $studentGrade['idStudent'];
            $sum = 0;
            $sumPercent = 0;
            foreach ($studentGrade['grades'] as $criteriaKey => $gradeData) {
                if (!isset($gradeData['idEvalCriteria'])) {
                    continue; // Saltar si no hay ID de criterio
                }
                $grade = $gradeData['grade'] === '' ? null : $gradeData['grade'];
                $idEvalCriteria = $gradeData['idEvalCriteria'];
                $percentage = isset($gradeData['percentage']) ? $gradeData['percentage'] : 0;
                $status = 1;
                // Calculo para promedio ponderado
                // Si el grado no está establecido (vacío), pero el porcentaje sí, considerar el grado como 0
                if (is_numeric($percentage)) {
                    $sumPercent += floatval($percentage);
                    if (is_numeric($grade)) {
                        $sum += floatval($grade) * (floatval($percentage) / 100);
                    } // Si el grado no es numérico, no suma al promedio pero sí cuenta el porcentaje
                }
                $stmt->bind_param(
                    "diiiiisdsis",
                    $grade,
                    $idStudent,
                    $data['idSubject'],
                    $idEvalCriteria,
                    $data['idSchoolYear'],
                    $data['idSchoolQuarter'],
                    $quarter,
                    $status,
                    $grade,
                    $quarter,
                    $status
                );
                if (!$stmt->execute()) {
                    error_log('SQL ERROR (execute stmt): ' . $stmt->error);
                }
            }
            // Guardar promedio solo si hay porcentajes válidos
            // Cálculo de promedio - redondeando hacia arriba
            if ($sumPercent > 0) {
                if ($sumPercent === 100) {
                    $average = ceil($sum * 10) / 10; // Si los porcentajes suman 100, redondear hacia arriba a 1 decimal
                }
                else {
                    $average = ceil(($sum / ($sumPercent / 100)) * 10) / 10; // Si no, normalizar y redondear hacia arriba
                }
            }
            else {
                $average = 0.0;
            }
            $stmtAvg->bind_param(
                "diiiidiiii",
                $average, // insert
                $idStudent, // insert
                $data['idSubject'], // insert
                $data['idSchoolYear'], // insert
                $data['idSchoolQuarter'], // insert
                $average, // update
                $data['idSubject'], // update
                $idStudent, // update
                $data['idSchoolYear'], // update
                $data['idSchoolQuarter'] // update
            );
            if (!$stmtAvg->execute()) {
                error_log('SQL ERROR (execute stmtAvg): ' . $stmtAvg->error);
            }
        }
    }
    $conexion->commit();
    echo json_encode(['success' => true, 'message' => 'Calificaciones guardadas correctamente']);
}
catch (Exception $e) {
    if ($conexion->connect_errno) {
        $conexion->rollback();
    }
    error_log('Error en saveGrades: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ocurrió un error al guardar las calificaciones. Por favor, intente nuevamente.']);
}
?> 