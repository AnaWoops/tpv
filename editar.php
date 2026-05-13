<?php
include("conexion.php");
include("seguridad.php");

$hoy = date("Y-m-d");

// TRADUCIR LA FECHA AL ESPAÑOL PARA QUE SE VEA BIEN
$meses = [
    1 => "Enero", 2 => "Febrero", 3 => "Marzo",
    4 => "Abril", 5 => "Mayo", 6 => "Junio",
    7 => "Julio", 8 => "Agosto", 9 => "Septiembre",
    10 => "Octubre", 11 => "Noviembre", 12 => "Diciembre"
];

$dia_num = (int)date("d");
$mes_nombre = $meses[(int)date("m")];
$anio = date("Y");
$fecha_texto = "$dia_num de $mes_nombre de $anio";

// MIRAR SI EL DIA YA ESTA CERRADO PORQUE SI ES ASI NO SE PUEDE TOCAR NADA
$sql_cierre = "SELECT id FROM cierres 
               WHERE tipo = 'dia' 
               AND fecha_inicio = '$hoy'";
$resultado_cierre = $conn->query($sql_cierre);
$dia_cerrado = ($resultado_cierre->num_rows > 0);

// COMPROBAR QUE EL ID LLEGA BIEN Y NO ES CUALQUIER COSA
if (!isset($_GET['id']) && !isset($_POST['id'])) {
    header("Location: index.php?error=ID no válido");
    exit;
}

$id = $_GET['id'] ?? $_POST['id'];
$id = (int)$id;

// BUSCAR LOS DATOS DEL MOVIMIENTO QUE QUEREMOS EDITAR
$sql = "SELECT * FROM movimientos WHERE id = $id";
$resultado = $conn->query($sql);

if ($resultado->num_rows === 0) {
    header("Location: index.php?error=Movimiento no encontrado");
    exit;
}

$fila = $resultado->fetch_assoc();

// DESMONTAR EL CONCEPTO PARA SACAR EL PRECIO Y LA CANTIDAD POR SEPARADO
$concepto_db = $fila['concepto'];
$importe_db = (float)$fila['importe'];
$cantidad_ui = 1;
$precio_ui = $importe_db;
$concepto_ui = $concepto_db;

// MIRAMOS SI EL NOMBRE TRAE EL FORMATO DE CANTIDAD TIPO PRODUCTO X3
if (preg_match('/^(.*?)\s+x(\d+)$/', $concepto_db, $matches)) {
    $concepto_ui = $matches[1]; // SACAMOS SOLO EL NOMBRE
    $cantidad_ui = (int)$matches[2]; // SACAMOS LA CANTIDAD
    if ($cantidad_ui > 0) {
        $precio_ui = $importe_db / $cantidad_ui; // CALCULAMOS EL PRECIO DE CADA UNIDAD
    }
}

// SI HEMOS DADO AL BOTON DE GUARDAR ACTUALIZAMOS LOS DATOS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($dia_cerrado) {
        header("Location: index.php?error=No se puede editar, día cerrado");
        exit;
    }

    $concepto_base = trim($_POST['concepto'] ?? "");
    $precio_raw = trim($_POST['precio'] ?? "");
    $cantidad_raw = trim($_POST['cantidad'] ?? "1");

    if ($concepto_base === "") {
        $concepto_base = "Varios";
    }

    $precio_normalizado = str_replace(',', '.', $precio_raw);
    if (!is_numeric($precio_normalizado)) {
        header("Location: index.php?error=Formato de precio no válido");
        exit;
    }
    $precio = (float)$precio_normalizado;

    if (!is_numeric($cantidad_raw) || (int)$cantidad_raw < 1) {
        $cantidad = 1;
    } else {
        $cantidad = (int)$cantidad_raw;
    }

    // SACAR EL TOTAL NUEVO MULTIPLICANDO EL PRECIO POR LA CANTIDAD
    $importe_total = $precio * $cantidad;

    // PONER EL NUMERO DE UNIDADES EN EL NOMBRE SI HAY MAS DE UNA
    if ($cantidad > 1) {
        $concepto_final = $concepto_base . " x" . $cantidad;
    } else {
        $concepto_final = $concepto_base;
    }

    $stmt = $conn->prepare("UPDATE movimientos SET concepto = ?, importe = ? WHERE id = ?");
    $stmt->bind_param("sdi", $concepto_final, $importe_total, $id);

    if ($stmt->execute()) {
        header("Location: index.php?ok=Movimiento actualizado");
        exit;
    } else {
        header("Location: index.php?error=Error al actualizar");
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Editar movimiento</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
        }

        .container {
            width: 800px;
            margin: 30px auto;
            background: white;
            padding: 25px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .header {
            background-color: #f5e3a1;
            padding: 15px;
            font-size: 18px;
            font-weight: bold;
        }

        .cerrado {
            margin-top: 10px;
            color: red;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
        }

        button {
            background-color: #f5e3a1;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            font-weight: bold;
        }

        button:hover {
            background-color: #e8d38c;
        }

        button:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        /* AJUSTES PARA QUE LA CANTIDAD Y EL BOTON SALGAN EN LA MISMA LINEA */
        .form-row-inline {
            display: flex;
            gap: 10px;
            align-items: stretch;
        }
        .form-row-inline input {
            flex: 1;
            margin-bottom: 0; 
        }
        .form-row-inline button {
            flex: 1;
            margin-top: 5px;
        }

        /* ESTILOS PARA QUE EN EL MOVIL SE VEA TODO GRANDE Y FACIL DE DARLE */
        @media (max-width: 768px) {

            .container {
                width: 100%;
                margin: 0;
                padding: 15px;
                box-shadow: none;
            }

            .header {
                text-align: center;
                font-size: 18px;
                padding: 18px;
            }

            h2 {
                font-size: 20px;
                margin-bottom: 15px;
            }

            form {
                background: white;
                padding: 20px;
                border-radius: 12px;
                box-shadow: 0 3px 10px rgba(0,0,0,0.1);
                box-sizing: border-box; 
            }

            input {
                font-size: 18px;
                padding: 14px;
                margin-bottom: 18px;
                border-radius: 6px;
            }

            button {
                width: 100%;
                font-size: 18px;
                padding: 14px;
                margin-top: 5px;
                border-radius: 8px;
            }

            a {
                display: block;
                margin-top: 10px;
            }

            a button {
                width: 100%;
            }

            .cerrado {
                font-size: 16px;
                padding: 10px;
                border-radius: 6px;
                background: #fff3cd;
                color: #856404;
                border: 1px solid #ffeeba;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <div class="header">Editar movimiento</div>

    <?php if ($dia_cerrado): ?>
        <div class="cerrado">🔒 Día cerrado (no se puede editar)</div>
    <?php endif; ?>

    <h2>Editar datos</h2>

    <form method="POST">
        <input type="hidden" name="id" value="<?php echo $id; ?>">

        <input type="text" 
               name="concepto" 
               value="<?php echo htmlspecialchars($concepto_ui, ENT_QUOTES, 'UTF-8'); ?>"
               <?php if($dia_cerrado) echo 'disabled'; ?>>

        <input type="number" 
               name="precio" 
               step="0.01"
               value="<?php echo $precio_ui; ?>"
               required
               <?php if($dia_cerrado) echo 'disabled'; ?>>

        <div class="form-row-inline">
            <input type="number" 
                   name="cantidad" 
                   min="1" 
                   step="1"
                   placeholder="Cantidad"
                   value="<?php echo $cantidad_ui > 1 ? $cantidad_ui : ''; ?>"
                   <?php if($dia_cerrado) echo 'disabled'; ?>>

            <button type="submit" <?php if($dia_cerrado) echo 'disabled'; ?>>
                Guardar cambios
            </button>
        </div>
    </form>

    <br>

    <a href="index.php">
        <button type="button">Volver</button>
    </a>

</div>

</body>
</html>