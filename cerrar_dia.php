<?php
include("conexion.php");
include("seguridad.php");

// SEGURIDAD PARA QUE NO NOS METAN FECHAS RARAS O BASURA POR EL POST
$fecha_cruda = $_POST['fecha'] ?? date("Y-m-d");

if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_cruda)) {
    $fecha_a_cerrar = $fecha_cruda;
} else {
    // SI LA FECHA ES MALA FORZAMOS EL CIERRE A DIA DE HOY PARA NO LIARLA
    $fecha_a_cerrar = date("Y-m-d");
}

// MIRAR SI ESE DIA YA ESTA CERRADO EN LA BASE DE DATOS
$sql_check = "SELECT id FROM cierres 
              WHERE tipo = 'dia' 
              AND fecha_inicio = '$fecha_a_cerrar'";

$resultado_check = $conn->query($sql_check);

if ($resultado_check->num_rows > 0) {
    echo "⚠️ El día ya está cerrado.";
    exit;
}

// SUMAR TODO EL DINERO QUE SE HA MOVIDO EN ESE DIA CONCRETO
$sql_total = "SELECT SUM(importe) as total FROM movimientos WHERE fecha = '$fecha_a_cerrar'";
$resultado_total = $conn->query($sql_total);
$fila = $resultado_total->fetch_assoc();
$total = $fila['total'] ?? 0;

// METER EL CIERRE EN LA TABLA CON EL TOTAL CALCULADO
$sql_insert = "INSERT INTO cierres (tipo, fecha_inicio, fecha_fin, total)
               VALUES ('dia', '$fecha_a_cerrar', '$fecha_a_cerrar', '$total')";

if ($conn->query($sql_insert) === TRUE) {
    // VOLVER AL INDEX MANDANDO LA FECHA PARA QUE SIGA TODO EN SU SITIO
    header("Location: index.php?fecha=" . $fecha_a_cerrar);
    exit;
} else {
    echo "Error: " . $conn->error;
}
?>