
<?php
file_put_contents(__DIR__.'/debug_grades.txt', 'ENTRO AL SCRIPT'.PHP_EOL, FILE_APPEND);
// Mostrar todos los errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once "../conection.php";
require_once "check_session.php";

header('Content-Type: application/json');

// Guardar el input recibido para debug
file_put_contents(__DIR__.'/debug_grades.txt', file_get_contents('php://input').PHP_EOL, FILE_APPEND);

try {
    $data = json_decode(file_get_contents('php://input'), true);
    file_put_contents(__DIR__.'/debug_grades.txt', 'DATA RECIBIDA: '.print_r($data, true).PHP_EOL, FILE_APPEND);
    file_put_contents(__DIR__.'/debug_grades.txt', 'idSchoolYear recibido: '.print_r($data['idSchoolYear'], true).PHP_EOL, FILE_APPEND);
    
    if (!isset($data['idSubject'], $data['idSchoolYear'], $data['idSchoolQuarter'], $data['grades'])) {
        error_log('Error: Datos incompletos en saveGrades');
        throw new Exception('No se recibieron todos los datos necesarios. Por favor, verifique la información.');
    }
    if (empty($data['grades'])) {
        error_log('Error: No se recibieron calificaciones en saveGrades');
        throw new Exception('No se encontraron calificaciones para guardar.');
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
        file_put_contents(__DIR__.'/debug_grades.txt', 'SQL ERROR (prepare stmt): ' . $conexion->error . PHP_EOL, FILE_APPEND);
        error_log('Error al preparar statement en saveGrades: ' . $conexion->error);
        throw new Exception('Error al procesar las calificaciones. Por favor, intente nuevamente.');
    }

    // Preparar la consulta para insertar/actualizar promedio
    $stmtAvg = $conexion->prepare("INSERT INTO average (average, idStudent, idSubject, idSchoolYear, idSchoolQuarter) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE average = ?, idSubject = ?, idStudent = ?, idSchoolYear = ?, idSchoolQuarter = ?");

    if ($stmtAvg === false) {
        file_put_contents(__DIR__.'/debug_grades.txt', 'SQL ERROR (prepare stmtAvg): ' . $conexion->error . PHP_EOL, FILE_APPEND);
    } else {
        foreach ($data['grades'] as $studentGrade) {
            file_put_contents(__DIR__.'/debug_grades.txt', 'Student: '.print_r($studentGrade, true).PHP_EOL, FILE_APPEND);
            $idStudent = $studentGrade['idStudent'];
            $sum = 0;
            $sumPercent = 0;
            foreach ($studentGrade['grades'] as $criteriaKey => $gradeData) {
                if (!isset($gradeData['idEvalCriteria'])) {
                    file_put_contents(__DIR__.'/debug_grades.txt', 'FALTA idEvalCriteria en '.print_r($gradeData, true).PHP_EOL, FILE_APPEND);
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
                    file_put_contents(__DIR__.'/debug_grades.txt', 'SQL ERROR (execute stmt): ' . $stmt->error . PHP_EOL, FILE_APPEND);
                } else {
                    file_put_contents(__DIR__.'/debug_grades.txt', 'EJECUTADO OK stmt para idStudent='.$idStudent.', idEvalCriteria='.$idEvalCriteria.PHP_EOL, FILE_APPEND);
                }
            }
            // Guardar promedio solo si hay porcentajes válidos
            file_put_contents(__DIR__.'/debug_grades.txt', 'ANTES DE PROMEDIO idStudent='.$idStudent.PHP_EOL, FILE_APPEND);
            
            // Cálculo de promedio - redondeando hacia arriba
            if ($sumPercent > 0) {
                if ($sumPercent === 100) {
                    $average = ceil($sum * 10) / 10; // Si los porcentajes suman 100, redondear hacia arriba a 1 decimal
                } else {
                    $average = ceil(($sum / ($sumPercent / 100)) * 10) / 10; // Si no, normalizar y redondear hacia arriba
                }
                file_put_contents(__DIR__.'/debug_grades.txt', 'CÁLCULO DE PROMEDIO: suma='.$sum.', sumaPorcentaje='.$sumPercent.', promedio='.$average.PHP_EOL, FILE_APPEND);
            } else {
                $average = 0.0;
            }
            $stmtAvg->bind_param(
                "diiiidiiii",
                $average,              // insert
                $idStudent,            // insert
                $data['idSubject'],    // insert
                $data['idSchoolYear'], // insert
                $data['idSchoolQuarter'], // insert
                $average,              // update
                $data['idSubject'],    // update
                $idStudent,            // update
                $data['idSchoolYear'], // update
                $data['idSchoolQuarter'] // update
            );
            file_put_contents(__DIR__.'/debug_grades.txt', 'ANTES DE EXECUTE stmtAvg para idStudent='.$idStudent.' AVG='.$average.' idSubject='.$data['idSubject'].' idSchoolYear='.$data['idSchoolYear'].' idSchoolQuarter='.$data['idSchoolQuarter'].PHP_EOL, FILE_APPEND);
            if (!$stmtAvg->execute()) {
                file_put_contents(__DIR__.'/debug_grades.txt', 'SQL ERROR (execute stmtAvg): ' . $stmtAvg->error . PHP_EOL, FILE_APPEND);
            } else {
                file_put_contents(__DIR__.'/debug_grades.txt', 'EJECUTADO OK stmtAvg para idStudent='.$idStudent.PHP_EOL, FILE_APPEND);
            }
        }
    }
    file_put_contents(__DIR__.'/debug_grades.txt', 'ANTES DE COMMIT'.PHP_EOL, FILE_APPEND);
    $conexion->commit();
    file_put_contents(__DIR__.'/debug_grades.txt', 'COMMIT REALIZADO'.PHP_EOL, FILE_APPEND);
    file_put_contents(__DIR__.'/debug_grades.txt', 'ANTES DE ECHO SUCCESS'.PHP_EOL, FILE_APPEND);
    echo json_encode(['success' => true, 'message' => 'Calificaciones guardadas correctamente']);
    file_put_contents(__DIR__.'/debug_grades.txt', 'DESPUES DE ECHO SUCCESS'.PHP_EOL, FILE_APPEND);

} catch (Exception $e) {
    if ($conexion->connect_errno) {
        $conexion->rollback();
    }
    file_put_contents(__DIR__.'/debug_grades.txt', 'EXCEPTION: ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
    error_log('Error en saveGrades: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ocurrió un error al guardar las calificaciones. Por favor, intente nuevamente.']);
}
?> 