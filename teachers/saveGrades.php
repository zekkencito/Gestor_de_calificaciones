<?php
require_once dirname(__DIR__) . '/enforce_post.php';
require_once "../conection.php";
require_once "check_session.php";
header('Content-Type: application/json');
$inTransaction = false;
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
    $stmtQuarter = $conexion->prepare("SELECT idSchoolQuarter, name FROM schoolQuarter WHERE idSchoolYear = ? AND CURDATE() BETWEEN startDate AND endDate ORDER BY idSchoolQuarter DESC LIMIT 1");
    $stmtQuarter->bind_param("i", $data['idSchoolYear']);
    $stmtQuarter->execute();
    $activeQuarter = $stmtQuarter->get_result()->fetch_assoc();
    if (!$activeQuarter) {
        throw new Exception('No hay un bimestre vigente para capturar calificaciones en este momento.');
    }
    if ((int) $data['idSchoolQuarter'] !== (int) $activeQuarter['idSchoolQuarter']) {
        throw new Exception('El bimestre vigente cambió. Recarga la página antes de guardar.');
    }
    $data['idSchoolQuarter'] = (int) $activeQuarter['idSchoolQuarter'];
    $stmtQuarter->close();

    // Validar que se hayan asignado porcentajes a los criterios y que las calificaciones estén en el rango 6-10
    $hasValidPercentage = false;
    foreach ($data['grades'] as $studentGrade) {
        if (isset($studentGrade['grades']) && is_array($studentGrade['grades'])) {
            foreach ($studentGrade['grades'] as $criteriaKey => $gradeData) {
                $pct = isset($gradeData['percentage']) && is_numeric($gradeData['percentage']) ? floatval($gradeData['percentage']) : 0.0;
                if ($pct > 0) {
                    $hasValidPercentage = true;
                }
                if (isset($gradeData['grade']) && trim((string) $gradeData['grade']) !== '') {
                    $gradeValue = floatval($gradeData['grade']);
                    if ($gradeValue < 6 || $gradeValue > 10) {
                        throw new Exception('Calificación inválida: ' . $gradeData['grade'] . '. Las calificaciones deben estar entre 6 y 10.');
                    }
                    if ($pct <= 0) {
                        throw new Exception('No se puede guardar una calificación en un criterio sin porcentaje asignado.');
                    }
                }
            }
        }
    }
    if (!$hasValidPercentage) {
        throw new Exception('Debes asignar los porcentajes a los criterios de evaluación antes de guardar calificaciones.');
    }

    $conexion->begin_transaction();
    $inTransaction = true;
    // Obtener el nombre del trimestre
    $quarter = $activeQuarter['name'];
    // Preparar la consulta para insertar/actualizar calificaciones
    $stmt = $conexion->prepare("INSERT INTO gradesSubject (grade, evalDate, idStudent, idSubject, idEvalCriteria, idSchoolYear, idSchoolQuarter, quarter, status) VALUES (?, CURRENT_DATE(), ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE grade = ?, evalDate = CURRENT_DATE(), quarter = ?, status = ?");
    if ($stmt === false) {
        error_log('Error al preparar statement en saveGrades: ' . $conexion->error);
        throw new Exception('Error al procesar las calificaciones. Por favor, intente nuevamente.');
    }
    // Preparar la consulta para insertar/actualizar promedio
    $stmtAvg = $conexion->prepare("INSERT INTO average (average, idStudent, idSubject, idSchoolYear, idSchoolQuarter) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE average = ?");
    if ($stmtAvg !== false) {
        foreach ($data['grades'] as $studentGrade) {
            $idStudent = intval($studentGrade['idStudent']);
            $sum = 0.0;
            $sumPercent = 0.0;
            $hasAnyGrade = false;
            if (isset($studentGrade['grades']) && is_array($studentGrade['grades'])) {
                foreach ($studentGrade['grades'] as $criteriaKey => $gradeData) {
                    if (empty($gradeData['idEvalCriteria'])) {
                        continue; // Saltar si no hay ID de criterio
                    }
                    $rawGrade = isset($gradeData['grade']) ? trim((string) $gradeData['grade']) : '';
                    $grade = ($rawGrade === '') ? null : floatval($rawGrade);
                    $idEvalCriteria = intval($gradeData['idEvalCriteria']);
                    $percentage = isset($gradeData['percentage']) && is_numeric($gradeData['percentage']) ? floatval($gradeData['percentage']) : 0.0;
                    $status = 1;
                    // Cálculo para promedio ponderado (consistente con gradesSubject.php)
                    if ($percentage > 0) {
                        $sumPercent += $percentage;
                        if ($grade !== null) {
                            $hasAnyGrade = true;
                            $sum += $grade * ($percentage / 100);
                        }
                    }
                    $stmt->bind_param(
                        "diiiiisidsi",
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
                        throw new Exception('No se pudo guardar una calificación: ' . $stmt->error);
                    }
                }
            }
            // Guardar promedio solo si hay porcentajes válidos y al menos una calificación capturada
            if ($sumPercent > 0 && $hasAnyGrade) {
                if (abs($sumPercent - 100.0) < 0.001) {
                    $average = ceil($sum * 10 - 1e-9) / 10; // Si los porcentajes suman 100, redondear hacia arriba a 1 decimal
                }
                else {
                    $average = ceil(($sum / ($sumPercent / 100)) * 10 - 1e-9) / 10; // Si no, normalizar y redondear hacia arriba
                }
            }
            else {
                $average = null;
            }
            $stmtAvg->bind_param(
                "diiiid",
                $average, // insert
                $idStudent, // insert
                $data['idSubject'], // insert
                $data['idSchoolYear'], // insert
                $data['idSchoolQuarter'], // insert
                $average // update
            );
            if (!$stmtAvg->execute()) {
                throw new Exception('No se pudo guardar un promedio: ' . $stmtAvg->error);
            }
        }
    } else {
        throw new Exception('No se pudo preparar el promedio de las calificaciones.');
    }
    $conexion->commit();
    echo json_encode(['success' => true, 'message' => 'Calificaciones guardadas correctamente']);
}
catch (Exception $e) {
    if ($inTransaction) {
        $conexion->rollback();
    }
    error_log('Error en saveGrades: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>