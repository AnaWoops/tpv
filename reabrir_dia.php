<?php
include("conexion.php");
include("seguridad.php");

$hoy = date("Y-m-d");

// Borrar cierre del día
$sql = "DELETE FROM cierres 
        WHERE tipo = 'dia' 
        AND fecha_inicio = '$hoy'";

if ($conn->query($sql) === TRUE) {
    header("Location: index.php");
    exit;
} else {
    echo "Error: " . $conn->error;
}
?>