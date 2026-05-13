<?php
include("conexion.php");
include("seguridad.php");

$inicio_mes = date("Y-m-01");
$fin_mes = date("Y-m-d");

$sql = "SELECT SUM(importe) as total FROM movimientos 
        WHERE fecha BETWEEN '$inicio_mes' AND '$fin_mes'";

$resultado = $conn->query($sql);
$fila = $resultado->fetch_assoc();

$total = $fila['total'] ?? 0;
?>

<h2>Total del mes</h2>
<p><?php echo number_format($total, 2); ?> €</p>

<a href="index.php">Volver</a>