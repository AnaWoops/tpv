<?php
include("conexion.php");
include("seguridad.php");

$hoy = date("Y-m-d");

// MIRAMOS SI EL DIA YA ESTA CERRADO PORQUE SI ES ASI NO SE PUEDE GUARDAR NADA
$sql_cierre = "SELECT id FROM cierres WHERE tipo = 'dia' AND fecha_inicio = '$hoy'";
$resultado_cierre = $conn->query($sql_cierre);
if ($resultado_cierre->num_rows > 0) {
    header("Location: index.php?error=No se puede guardar, día cerrado");
    exit;
}

// PILLAMOS LOS DATOS QUE VIENEN DEL FORMULARIO Y LES QUITAMOS LOS ESPACIOS
$concepto_base = trim($_POST['concepto'] ?? "");
$precio_raw = trim($_POST['precio'] ?? "");
$cantidad_raw = trim($_POST['cantidad'] ?? "1");

// SI NO HAN ESCRITO NADA EN EL CONCEPTO LE PONEMOS VARIOS POR DEFECTO
if ($concepto_base === "") {
    $concepto_base = "Varios";
}

// EL PRECIO ES OBLIGATORIO SI NO ESTO NO TIENE SENTIDO
if ($precio_raw === "") {
    header("Location: index.php?error=El precio es obligatorio");
    exit;
}

// CAMBIAMOS LAS COMAS POR PUNTOS PARA QUE LA BASE DE DATOS LO ENTIENDA BIEN
$precio_normalizado = str_replace(',', '.', $precio_raw);
if (!is_numeric($precio_normalizado)) {
    header("Location: index.php?error=Formato de precio no válido");
    exit;
}
$precio = (float)$precio_normalizado;

// NO QUEREMOS NUMEROS NEGATIVOS EN EL PRECIO QUE LUEGO NO CUADRA LA CAJA
if ($precio < 0) {
    header("Location: index.php?error=El precio no puede ser negativo");
    exit;
}

// REVISAMOS QUE LA CANTIDAD SEA UN NUMERO VALIDO Y SI NO LE PONEMOS UNO
if (!is_numeric($cantidad_raw) || (int)$cantidad_raw < 1) {
    $cantidad = 1;
} else {
    $cantidad = (int)$cantidad_raw;
}

// CALCULAMOS EL DINERO TOTAL MULTIPLICANDO EL PRECIO POR LAS UNIDADES
$importe_total = $precio * $cantidad;

// SI HAY MAS DE UNA UNIDAD LO PONEMOS EN EL NOMBRE PARA QUE SE VEA EN EL TICKET
if ($cantidad > 1) {
    $concepto_final = $concepto_base . " x" . $cantidad;
} else {
    $concepto_final = $concepto_base;
}

// METEMOS EL NUEVO MOVIMIENTO EN LA TABLA USANDO SENTENCIAS PREPARADAS POR SEGURIDAD
$stmt = $conn->prepare("INSERT INTO movimientos (fecha, concepto, importe) VALUES (?, ?, ?)");
$stmt->bind_param("ssd", $hoy, $concepto_final, $importe_total);

if ($stmt->execute()) {
    // SI TODO HA IDO BIEN VOLVEMOS AL INDEX CON EL MENSAJE DE EXITO
    header("Location: index.php?ok=Movimiento guardado correctamente");
    exit;
} else {
    // SI FALLA ALGO MANDAMOS EL ERROR PARA SABER QUE PASA
    header("Location: index.php?error=Error al guardar");
    exit;
}
?>