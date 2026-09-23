<?php
require_once "conection.php";
$res = $conexion->query("SELECT * FROM schoolYear");
echo "=== schoolYear ===\n";
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
$res = $conexion->query("SELECT * FROM schoolQuarter");
echo "\n=== schoolQuarter ===\n";
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
