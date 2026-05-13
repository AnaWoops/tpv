<?php

// FORZAR HTTPS PARA QUE NADIE ENTRE SIN EL CANDADITO DE SEGURIDAD
if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
    header("Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit;
}

// MIRAR SI LA SESION ESTA ARRANCADA Y SI NO LANZARLA
if (session_status() === PHP_SESSION_NONE) {
    session_start(); 
}

// CONFIGURAR LA HORA DE ESPAÑA PARA QUE LOS TICKETS NO SALGAN CON HORAS RARAS
date_default_timezone_set('Europe/Madrid');

// DATOS PARA LA CONEXION CON EL SERVIDOR
$host = "HOST";
$usuario = "USUARIO";

// CONTRASEÑA PARA ENTRAR EN LA BASE DE DATOS
$password = "CONTRASEÑA"; 

// ESTA ES LA BASE DE DATOS QUE SE USA NORMALMENTE
$base_datos = "NOMBRE BASE DE DATOS";

// SI EL USUARIO ES EL DE PRUEBAS CAMBIAMOS EL NOMBRE DE LA BASE DE DATOS
if (isset($_SESSION['username']) && $_SESSION['username'] === 'pruebas') {
    $base_datos = "NOMBRE BASE DE DATOS";
}

// DESACTIVAR LOS REPORTES DE MYSQLI PARA QUE NO SALTE EL ERROR 500 SI FALLA LA CLAVE
mysqli_report(MYSQLI_REPORT_OFF);

// INTENTAR CONECTAR CON EL SERVIDOR USANDO LOS DATOS DE ARRIBA
$conn = @new mysqli($host, $usuario, $password, $base_datos);

// COMPROBAR SI LA CONEXION HA IDO BIEN O SI HAY ALGUN ERROR
if ($conn->connect_error) {
    // SI DA ERROR SE CORTA TODO Y QUE NOS DIGA QUE HA PASADO
    die("Error de conexion: " . $conn->connect_error);
}