<?php
    // Configurar zona horaria para México (GMT-6)
    date_default_timezone_set('America/Mexico_City');
    
    $servidor="db";
    $port="3306";
    $user="root";
    $password="root";
    $db="gestor_bd";

    $conexion= new mysqli($servidor,$user,$password,$db);
    if($conexion->connect_error){
        error_log("Error de conexión a la base de datos: " . $conexion->connect_error);
        die("Error al conectar con el servidor. Por favor, intente más tarde.");
    }
    $conexion->set_charset("utf8mb4");
    
    // Configurar zona horaria de MySQL para México (GMT-6)
    $conexion->query("SET time_zone = '-06:00'");
?>