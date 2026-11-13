<?php
/**
 * Helper para asegurar que la materia de Inglés esté asignada a cada grupo.
 * Requisito del sistema: Todo estudiante debe contar con la materia de Inglés
 * como objeto real en la base de datos (con o sin docente asignado inicialmente).
 */

require_once __DIR__ . '/conection.php';

if (!function_exists('getIdSubjectIngles')) {
    /**
     * Obtiene el idSubject de la materia Inglés dinámicamente.
     */
    function getIdSubjectIngles(mysqli $conexion): int {
        static $cachedId = null;
        if ($cachedId !== null) {
            return $cachedId;
        }
        $stmt = $conexion->prepare("SELECT idSubject FROM subjects WHERE name = 'Inglés' LIMIT 1");
        if ($stmt) {
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($res && !empty($res['idSubject'])) {
                $cachedId = (int)$res['idSubject'];
                return $cachedId;
            }
        }
        return 12; // ID por defecto en gestorcal.sql
    }
}

if (!function_exists('ensureEnglishAssignedToGroup')) {
    /**
     * Asegura que un grupo específico tenga asignada la materia de Inglés.
     * Si no existe ninguna asignación (con o sin docente), inserta un registro con idTeacher = NULL.
     */
    function ensureEnglishAssignedToGroup(mysqli $conexion, int $idGroup): bool {
        if ($idGroup <= 0) {
            return false;
        }
        $idIngles = getIdSubjectIngles($conexion);

        // Verificar si el grupo ya tiene la materia de Inglés asignada
        $stmtCheck = $conexion->prepare("SELECT idDFM FROM teacherGroupsSubjects WHERE idGroup = ? AND idSubject = ? LIMIT 1");
        if (!$stmtCheck) {
            return false;
        }
        $stmtCheck->bind_param("ii", $idGroup, $idIngles);
        $stmtCheck->execute();
        $exists = $stmtCheck->get_result()->fetch_assoc();
        $stmtCheck->close();

        if (!$exists) {
            $stmtInsert = $conexion->prepare("INSERT INTO teacherGroupsSubjects (idGroup, idTeacher, idSubject) VALUES (?, NULL, ?)");
            if ($stmtInsert) {
                $stmtInsert->bind_param("ii", $idGroup, $idIngles);
                $stmtInsert->execute();
                $stmtInsert->close();
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('ensureEnglishAssignedToAllGroups')) {
    /**
     * Asegura que todos los grupos existentes en la tabla `groups`
     * tengan asignada la materia de Inglés en `teacherGroupsSubjects`.
     * Retorna el número de grupos actualizados/insertados.
     */
    function ensureEnglishAssignedToAllGroups(mysqli $conexion): int {
        $idIngles = getIdSubjectIngles($conexion);

        $sql = "INSERT INTO teacherGroupsSubjects (idGroup, idTeacher, idSubject)
                SELECT g.idGroup, NULL, ?
                FROM groups g
                WHERE NOT EXISTS (
                    SELECT 1 FROM teacherGroupsSubjects tgs
                    WHERE tgs.idGroup = g.idGroup AND tgs.idSubject = ?
                )";
        $stmt = $conexion->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ii", $idIngles, $idIngles);
            $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();
            return $affected;
        }
        return 0;
    }
}
