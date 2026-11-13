<?php
/**
 * Script de migración CLI para asegurar que todos los grupos existentes
 * tengan asignada la materia de Inglés en teacherGroupsSubjects.
 */

require_once dirname(__DIR__) . '/conection.php';
require_once dirname(__DIR__) . '/ensure_english.php';

echo "=== Migración: Asignación automática de Inglés a todos los grupos ===\n";

try {
    $idIngles = getIdSubjectIngles($conexion);
    echo "ID de materia Inglés detectado: $idIngles\n";

    $afectados = ensureEnglishAssignedToAllGroups($conexion);
    echo "Grupos actualizados con la materia de Inglés: $afectados\n";

    // Resumen actual
    $res = $conexion->query("
        SELECT g.idGroup, g.grade, g.group_, tgs.idTeacher, ui.names as teacherName
        FROM groups g
        JOIN teacherGroupsSubjects tgs ON g.idGroup = tgs.idGroup AND tgs.idSubject = $idIngles
        LEFT JOIN teachers t ON tgs.idTeacher = t.idTeacher
        LEFT JOIN usersInfo ui ON t.idUserInfo = ui.idUserInfo
        ORDER BY g.grade, g.group_
    ");

    echo "\nEstado actual de grupos con Inglés:\n";
    while ($row = $res->fetch_assoc()) {
        $docente = $row['teacherName'] ? $row['teacherName'] : '(Sin docente asignado aún)';
        echo "- {$row['grade']}° {$row['group_']} (idGroup: {$row['idGroup']}): {$docente}\n";
    }

    echo "\n¡Migración completada con éxito!\n";
} catch (Exception $e) {
    echo "Error en la migración: " . $e->getMessage() . "\n";
    exit(1);
}
