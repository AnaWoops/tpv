<?php
include("conexion.php");
include("seguridad.php");

$hoy = date("Y-m-d");

$sql = "SELECT SUM(importe) as total FROM movimientos WHERE fecha = '$hoy'";
$resultado = $conn->query($sql);
$fila = $resultado->fetch_assoc();

$total = $fila['total'] ?? 0;
?>

<h2>Total del día</h2>
<p><?php echo number_format($total, 2); ?> €</p>

<a href="index.php">Volver</a>