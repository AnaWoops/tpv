<?php
include("conexion.php");
include("seguridad.php");

// VALIDAR QUE EL ID QUE LLEGA SEA CORRECTO Y NUMERICO
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php?error=ID inválido");
    exit;
}

$id = intval($_GET['id']);

// COMPROBAR QUE EL MOVIMIENTO EXISTE DE VERDAD EN LA BASE DE DATOS
$stmt = $conn->prepare("SELECT id, fecha FROM movimientos WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    header("Location: index.php?error=Movimiento no encontrado");
    exit;
}

$fila = $resultado->fetch_assoc();
$fecha_movimiento = $fila['fecha'];

// COMPROBAR QUE EL DIA NO ESTE CERRADO PORQUE SI NO NO SE TOCA NADA
$stmt_cierre = $conn->prepare("SELECT id FROM cierres WHERE tipo = 'dia' AND fecha_inicio = ?");
$stmt_cierre->bind_param("s", $fecha_movimiento);
$stmt_cierre->execute();
$res_cierre = $stmt_cierre->get_result();

if ($res_cierre->num_rows > 0) {
    header("Location: index.php?error=No se puede borrar: día cerrado");
    exit;
}

// BORRAR EL MOVIMIENTO DE FORMA DEFINITIVA
$stmt_delete = $conn->prepare("DELETE FROM movimientos WHERE id = ?");
$stmt_delete->bind_param("i", $id);

if ($stmt_delete->execute()) {
    header("Location: index.php?ok=Movimiento eliminado");
    exit;
} else {
    header("Location: index.php?error=Error al borrar");
    exit;
}
?>