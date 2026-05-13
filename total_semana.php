<?php
include("conexion.php");
include("seguridad.php");

$inicio_semana = date("Y-m-d", strtotime("monday this week"));
$fin_semana = date("Y-m-d");

$sql = "SELECT SUM(importe) as total FROM movimientos 
        WHERE fecha BETWEEN '$inicio_semana' AND '$fin_semana'";

$resultado = $conn->query($sql);
$fila = $resultado->fetch_assoc();

$total = $fila['total'] ?? 0;
?>

<h2>Total de la semana</h2>
<p><?php echo number_format($total, 2); ?> €</p>

<a href="index.php">Volver</a>