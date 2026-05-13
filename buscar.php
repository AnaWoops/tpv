<?php
include("conexion.php");
include("seguridad.php");

// SEGURIDAD PARA QUE NO NOS METAN CODIGO RARO POR LA URL
$tipo_crudo = $_GET['tipo'] ?? null;
$tipos_permitidos = ['dia', 'semana', 'mes', 'estado', 'concepto'];
$tipo = in_array($tipo_crudo, $tipos_permitidos) ? $tipo_crudo : null;

if (!empty($_GET['fecha']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['fecha'])) {
    $_GET['fecha'] = date("Y-m-d");
}
if (!empty($_GET['mes'])) $_GET['mes'] = (int)$_GET['mes'];
if (!empty($_GET['anio'])) $_GET['anio'] = (int)$_GET['anio'];

if (!empty($_GET['estado'])) {
    $estados_permitidos = ['abierto', 'cerrado'];
    if (!in_array($_GET['estado'], $estados_permitidos)) {
        $_GET['estado'] = 'abierto';
    }
}

// SI BUSCAN UN DIA CONCRETO LOS MANDAMOS DIRECTOS AL INDEX PARA NO REPETIR CODIGO
if ($tipo === "dia" && !empty($_GET['fecha'])) {
    header("Location: index.php?fecha=" . urlencode($_GET['fecha']));
    exit;
}

// LISTA DE MESES PARA QUE LAS FECHAS SE VEAN BIEN EN ESPAÑOL
$meses = [
    1 => "Enero", 2 => "Febrero", 3 => "Marzo",
    4 => "Abril", 5 => "Mayo", 6 => "Junio",
    7 => "Julio", 8 => "Agosto", 9 => "Septiembre",
    10 => "Octubre", 11 => "Noviembre", 12 => "Diciembre"
];

function fechaEspanol($fecha_sql) {
    global $meses;
    $ts = strtotime($fecha_sql);
    return date("j", $ts) . " de " . $meses[(int)date("m", $ts)] . " de " . date("Y", $ts);
}

$resultado = null;
$titulo = "";
$total_general = 0;

// BUSQUEDA POR SEMANA
if ($tipo === "semana" && !empty($_GET['fecha'])) {
    $fecha = $_GET['fecha'];
    $inicio = date("Y-m-d", strtotime("monday this week", strtotime($fecha)));
    $fin = date("Y-m-d", strtotime("sunday this week", strtotime($fecha)));

    $sql = "SELECT fecha, SUM(importe) as total_dia FROM movimientos WHERE fecha BETWEEN '$inicio' AND '$fin' GROUP BY fecha ORDER BY fecha DESC";
    $resultado = $conn->query($sql);

    $titulo = "Semana del " . date("d/m/Y", strtotime($inicio)) . " al " . date("d/m/Y", strtotime($fin));
}

// BUSQUEDA POR MES COMPLETO
elseif ($tipo === "mes" && !empty($_GET['mes']) && !empty($_GET['anio'])) {
    $mes = str_pad($_GET['mes'], 2, "0", STR_PAD_LEFT);
    $anio = $_GET['anio'];

    $inicio = "$anio-$mes-01";
    $fin = date("Y-m-t", strtotime($inicio));

    $sql = "SELECT fecha, SUM(importe) as total_dia FROM movimientos WHERE fecha BETWEEN '$inicio' AND '$fin' GROUP BY fecha ORDER BY fecha DESC";
    $resultado = $conn->query($sql);

    $titulo = $meses[(int)$mes] . " de " . $anio;
}

// BUSQUEDA SEGUN SI EL DIA ESTA ABIERTO O CERRADO
elseif ($tipo === "estado" && !empty($_GET['estado'])) {
    $estado = $_GET['estado'];

    if ($estado === "cerrado") {
        $sql = "SELECT fecha_inicio as fecha, total as total_dia FROM cierres WHERE tipo = 'dia' ORDER BY fecha_inicio DESC";
        $resultado = $conn->query($sql);
        $titulo = "Días cerrados";
    } else {
        $sql = "SELECT fecha, SUM(importe) as total_dia FROM movimientos WHERE fecha NOT IN (SELECT fecha_inicio FROM cierres WHERE tipo='dia') GROUP BY fecha ORDER BY fecha DESC";
        $resultado = $conn->query($sql);
        $titulo = "Días abiertos";
    }
}

// BUSQUEDA POR TEXTO LIBRE EN EL CONCEPTO
elseif ($tipo === "concepto" && !empty($_GET['termino'])) {
    $termino = $conn->real_escape_string($_GET['termino']);
    
    $sql = "SELECT id, fecha, concepto, importe FROM movimientos WHERE concepto LIKE '%$termino%' ORDER BY fecha DESC, id DESC";
    $resultado = $conn->query($sql);
    
    $titulo = 'Resultados para: "' . htmlspecialchars($_GET['termino'], ENT_QUOTES, 'UTF-8') . '"';
}

// CALCULAR EL TOTAL DE DINERO PARA LAS SEMANAS O LOS MESES
if ($resultado && ($tipo === 'semana' || $tipo === 'mes')) {
    while($fila = $resultado->fetch_assoc()) {
        $total_general += $fila['total_dia'];
    }
    $resultado->data_seek(0); 
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Buscar</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background-color: #f5f5f5; margin: 0; }
        
        .container {
            width: 800px; margin: 30px auto; background: white; 
            padding: 25px; box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .header {
            background-color: #f5e3a1; padding: 15px; font-size: 18px;
            font-weight: bold; display: flex; justify-content: space-between;
            align-items: center;
        }

        .volver { text-decoration: none; font-weight: bold; color: black; }

        .filtros { margin-top: 20px; }
        .filtros select, .filtros input {
            width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ccc;
        }

        button.btn-buscar {
            background-color: #f5e3a1; border: none; padding: 10px; 
            font-weight: bold; cursor: pointer; width: 100%; font-size: 16px;
        }

        .titulo-seccion { margin-top: 25px; font-weight: bold; font-size: 20px; padding-bottom: 10px; border-bottom: 2px solid #eee; }

        /* ESTILO PARA LA TABLA EN EL ORDENADOR */
        table.tabla-escritorio { width: 100%; margin-top: 15px; border-collapse: collapse; }
        table.tabla-escritorio th { background: #f5e3a1; padding: 10px; text-align: left; }
        table.tabla-escritorio td { padding: 10px; border-bottom: 1px solid #eee; }
        .importe { text-align: right; }
        
        .fila-enlace { display: block; text-decoration: none; color: black; font-weight: bold; }
        .fila-enlace:hover { color: #555; }

        .btn-cerrar {
            background: #f5e3a1; border: none; padding: 6px 12px;
            font-weight: bold; cursor: pointer; border-radius: 6px;
        }
        .btn-cerrar:hover { background: #e8d38c; }

        .lista-movil, .movil-resumen { display: none; }
        .total-escritorio { font-size: 18px; font-weight: bold; text-align: right; margin-top: 15px; }

        /* AJUSTES PARA QUE EN EL MOVIL SE VEA TODO BIEN */
        @media (max-width: 768px) {
            .container { width: 100%; margin: 0; padding: 15px; box-shadow: none; }
            .header { font-size: 16px; flex-direction: row; gap: 10px; }

            .tabla-escritorio, .total-escritorio { display: none !important; }
            .movil-resumen { display: block; margin: 15px 0 20px 0; }
            .lista-movil { display: block; margin-top: 15px; }

            .slide {
                background: #f5e3a1; border-radius: 15px; padding: 20px;
                text-align: center; display: block;
            }
            .slide .titulo { font-size: 18px; font-weight: bold; margin-bottom: 10px; }
            .slide .total { font-size: 32px; font-weight: bold; margin: 15px 0; }

            .mov-item {
                background: white; border-radius: 10px; padding: 15px;
                margin-bottom: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.08);
                display: flex; justify-content: space-between; align-items: center;
                border-bottom: 1px solid #eee;
            }

            .enlace-movil {
                text-decoration: none; color: black; flex-grow: 1; 
                display: flex; flex-direction: column; gap: 5px;
                padding-right: 15px;
            }
            .enlace-movil:active { opacity: 0.6; }

            .mov-fecha { font-size: 14px; color: #666; }
            .mov-contenido { display: flex; justify-content: space-between; font-size: 16px; font-weight: bold; }
            .mov-importe { white-space: nowrap; }

            .mov-item form { margin: 0; padding: 0; background: transparent; box-shadow: none; }
            .btn-cerrar { padding: 10px 14px; font-size: 14px; }
        }
    </style>
</head>

<body>

<div class="container">

    <div class="header">
        <span>Consultas</span>
        <a href="index.php" class="volver">⬅ Volver</a>
    </div>

    <form method="GET" class="filtros">
        <select name="tipo" onchange="this.form.submit()">
            <option value="">Selecciona tipo</option>
            <option value="dia" <?php if($tipo==='dia') echo 'selected'; ?>>Día concreto</option>
            <option value="semana" <?php if($tipo==='semana') echo 'selected'; ?>>Semana</option>
            <option value="mes" <?php if($tipo==='mes') echo 'selected'; ?>>Mes</option>
            <option value="estado" <?php if($tipo==='estado') echo 'selected'; ?>>Estado (Abiertos/Cerrados)</option>
            <option value="concepto" <?php if($tipo==='concepto') echo 'selected'; ?>>Por Concepto</option>
        </select>

        <?php if ($tipo === "dia" || $tipo === "semana"): ?>
            <input type="date" name="fecha" required>
        <?php endif; ?>

        <?php if ($tipo === "mes"): ?>
            <input type="number" name="mes" placeholder="Mes (1-12)" required min="1" max="12">
            <input type="number" name="anio" placeholder="Año" required>
        <?php endif; ?>

        <?php if ($tipo === "estado"): ?>
            <select name="estado">
                <option value="abierto" <?php if(isset($_GET['estado']) && $_GET['estado']=='abierto') echo 'selected'; ?>>Abiertos</option>
                <option value="cerrado" <?php if(isset($_GET['estado']) && $_GET['estado']=='cerrado') echo 'selected'; ?>>Cerrados</option>
            </select>
        <?php endif; ?>

        <?php if ($tipo === "concepto"): ?>
            <input type="text" name="termino" placeholder="Escribe el concepto (ej: Pan)..." required value="<?php echo htmlspecialchars($_GET['termino'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        <?php endif; ?>

        <?php if ($tipo): ?>
            <button type="submit" class="btn-buscar">Buscar</button>
        <?php endif; ?>
    </form>

    <?php if ($resultado && $resultado->num_rows > 0): ?>

        <div class="titulo-seccion"><?php echo $titulo; ?></div>

        <?php if ($tipo === 'semana' || $tipo === 'mes'): ?>
            <div class="movil-resumen">
                <div class="slide">
                    <div class="titulo"><?php echo $titulo; ?></div>
                    <div class="total"><?php echo number_format($total_general, 2, ',', '.'); ?> €</div>
                </div>
            </div>
        <?php endif; ?>

        <table class="tabla-escritorio">
            <tr>
                <th>Fecha</th>
                
                <?php if ($tipo === 'concepto'): ?>
                    <th>Concepto</th>
                    <th class="importe">Importe</th>
                <?php else: ?>
                    <th class="importe">Total del Día</th>
                <?php endif; ?>
                
                <?php if ($tipo === 'estado' && $estado === 'abierto'): ?>
                    <th style="text-align: right;">Acciones</th>
                <?php endif; ?>
            </tr>

            <?php 
            $resultado->data_seek(0);
            while($fila = $resultado->fetch_assoc()): 
                $fecha_esp = fechaEspanol($fila['fecha']);
            ?>
                <tr>
                    <td>
                        <a href="index.php?fecha=<?php echo $fila['fecha']; ?>" class="fila-enlace">
                            <?php echo $fecha_esp; ?>
                        </a>
                    </td>

                    <?php if ($tipo === 'concepto'): ?>
                        <td>
                            <a href="index.php?fecha=<?php echo $fila['fecha']; ?>" class="fila-enlace" style="font-weight:normal;">
                                <?php echo htmlspecialchars($fila['concepto'], ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                        </td>
                        <td class="importe">
                            <a href="index.php?fecha=<?php echo $fila['fecha']; ?>" class="fila-enlace" style="font-weight:normal;">
                                <?php echo number_format($fila['importe'], 2, ',', '.'); ?> €
                            </a>
                        </td>
                    <?php else: ?>
                        <td class="importe">
                            <a href="index.php?fecha=<?php echo $fila['fecha']; ?>" class="fila-enlace" style="font-weight:normal;">
                                <?php echo number_format($fila['total_dia'], 2, ',', '.'); ?> €
                            </a>
                        </td>
                    <?php endif; ?>

                    <?php if ($tipo === 'estado' && $estado === 'abierto'): ?>
                        <td style="text-align: right;">
                            <form action="cerrar_dia.php" method="POST" style="margin:0; display:inline-block;">
                                <input type="hidden" name="fecha" value="<?php echo $fila['fecha']; ?>">
                                <button type="submit" class="btn-cerrar">Cerrar día</button>
                            </form>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endwhile; ?>
        </table>

        <?php if ($tipo === 'semana' || $tipo === 'mes'): ?>
            <div class="total-escritorio">
                Total: <?php echo number_format($total_general, 2, ',', '.'); ?> €
            </div>
        <?php endif; ?>

        <div class="lista-movil">
            <?php 
            $resultado->data_seek(0);
            while($fila = $resultado->fetch_assoc()): 
                $fecha_esp = fechaEspanol($fila['fecha']);
            ?>
                <div class="mov-item">
                    
                    <?php if ($tipo === 'concepto'): ?>
                        <a href="index.php?fecha=<?php echo $fila['fecha']; ?>" class="enlace-movil">
                            <div class="mov-fecha"><?php echo $fecha_esp; ?></div>
                            <div class="mov-contenido">
                                <div><?php echo htmlspecialchars($fila['concepto'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="mov-importe"><?php echo number_format($fila['importe'], 2, ',', '.'); ?> €</div>
                            </div>
                        </a>
                    <?php else: ?>
                        <a href="index.php?fecha=<?php echo $fila['fecha']; ?>" class="enlace-movil" style="flex-direction: row;">
                            <div class="mov-contenido"><?php echo $fecha_esp; ?></div>
                            <div class="mov-importe"><?php echo number_format($fila['total_dia'], 2, ',', '.'); ?> €</div>
                        </a>
                    <?php endif; ?>

                    <?php if ($tipo === 'estado' && $estado === 'abierto'): ?>
                        <form action="cerrar_dia.php" method="POST">
                            <input type="hidden" name="fecha" value="<?php echo $fila['fecha']; ?>">
                            <button type="submit" class="btn-cerrar">Cerrar</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        </div>

    <?php elseif ($tipo): ?>
        <div style="margin-top: 30px; text-align: center; color: #777; font-size: 16px;">
            No se encontraron resultados para esta búsqueda.
        </div>
    <?php endif; ?>

</div>

</body>
</html>