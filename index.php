<?php
include("seguridad.php");
include("conexion.php");

// =========================================================
// 🛡️ BLINDAJE DE SEGURIDAD EXTREMA
// =========================================================
$fecha_cruda = $_GET['fecha'] ?? date("Y-m-d");
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_cruda)) {
    $fecha = $fecha_cruda;
} else {
    $fecha = date("Y-m-d");
}

$accion_cruda = $_GET['accion'] ?? 'dia';
$acciones_permitidas = ['dia', 'semana', 'mes'];
$accion = in_array($accion_cruda, $acciones_permitidas) ? $accion_cruda : 'dia';
// =========================================================

// 👉 Fecha bonita en español
$meses = [
    1 => "Enero", 2 => "Febrero", 3 => "Marzo",
    4 => "Abril", 5 => "Mayo", 6 => "Junio",
    7 => "Julio", 8 => "Agosto", 9 => "Septiembre",
    10 => "Octubre", 11 => "Noviembre", 12 => "Diciembre"
];

$dia_num = (int)date("d", strtotime($fecha));
$mes_nombre = $meses[(int)date("m", strtotime($fecha))];
$anio = date("Y", strtotime($fecha));
$fecha_texto = "$dia_num de $mes_nombre de $anio";

$total = null;

// 🔒 Comprobar si el día está cerrado
$sql_cierre = "SELECT id FROM cierres 
               WHERE tipo = 'dia' 
               AND fecha_inicio = '$fecha'";

$resultado_cierre = $conn->query($sql_cierre);
$dia_cerrado = ($resultado_cierre->num_rows > 0);

// Obtener movimientos
$sql = "SELECT * FROM movimientos WHERE fecha = '$fecha' ORDER BY id DESC";
$resultado = $conn->query($sql);

// 🧠 CÁLCULO DE PRECIOS AUTOMÁTICOS
// Buscamos todos los movimientos para aprender los precios unitarios
$sql_historial = "SELECT concepto, importe FROM movimientos WHERE concepto != '' ORDER BY id ASC";
$res_hist = $conn->query($sql_historial);
$precios_auto = [];

if ($res_hist) {
    while($row = $res_hist->fetch_assoc()) {
        $concepto_raw = trim($row['concepto']);
        $importe_total = floatval($row['importe']);
        
        // Si el concepto termina en " x5" (espacio, x, número)
        if (preg_match('/(.*)\s+x\s*(\d+)$/i', $concepto_raw, $matches)) {
            $nombre_limpio = trim($matches[1]);
            $cantidad = intval($matches[2]);
            $precio_unitario = ($cantidad > 0) ? ($importe_total / $cantidad) : $importe_total;
        } else {
            $nombre_limpio = $concepto_raw;
            $precio_unitario = $importe_total;
        }
        
        // Guardamos el último precio conocido para ese nombre limpio
        $precios_auto[$nombre_limpio] = number_format($precio_unitario, 2, '.', '');
    }
}
// Ordenamos alfabéticamente para el datalist
$conceptos_para_lista = array_keys($precios_auto);
sort($conceptos_para_lista);

// 🔥 TOTALES
$total_dia = $conn->query("SELECT SUM(importe) total FROM movimientos WHERE fecha='$fecha'")->fetch_assoc()['total'] ?? 0;

$inicio_semana = date("Y-m-d", strtotime("monday this week", strtotime($fecha)));
$total_semana = $conn->query("SELECT SUM(importe) total FROM movimientos WHERE fecha BETWEEN '$inicio_semana' AND '$fecha'")->fetch_assoc()['total'] ?? 0;

$inicio_mes = date("Y-m-01", strtotime($fecha));
$total_mes = $conn->query("SELECT SUM(importe) total FROM movimientos WHERE fecha BETWEEN '$inicio_mes' AND '$fecha'")->fetch_assoc()['total'] ?? 0;

// Totales escritorio (NO TOCAR)
if ($accion === "dia") {
    $sql_total = "SELECT SUM(importe) as total FROM movimientos WHERE fecha = '$fecha'";
}
elseif ($accion === "semana") {
    $sql_total = "SELECT SUM(importe) as total FROM movimientos 
                  WHERE fecha BETWEEN '$inicio_semana' AND '$fecha'";
}
elseif ($accion === "mes") {
    $sql_total = "SELECT SUM(importe) as total FROM movimientos 
                  WHERE fecha BETWEEN '$inicio_mes' AND '$fecha'";
}

if (isset($sql_total)) {
    $resultado_total = $conn->query($sql_total);
    $fila = $resultado_total->fetch_assoc();
    $total = $fila['total'] ?? 0;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Contabilidad</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <style>
        .header-btns {
            display: flex;
            gap: 8px;
            margin-left: auto;
        }
        
        /* 🔪 ELIMINAMOS EL SUBRAYADO POR DEFECTO DE LOS ENLACES EN LOS BOTONES */
        .header-btns a {
            text-decoration: none !important;
        }
        
        .btn-logout {
            background-color: #f8d7da !important;
            color: #721c24 !important;
            border: 1px solid #f5c6cb !important;
            padding: 8px 12px !important;
            font-size: 18px !important;
            line-height: 1 !important;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
        }

        .btn-admin-nav {
            background-color: #e2e3e5 !important;
            color: #383d41 !important;
            border: 1px solid #d6d8db !important;
            padding: 8px 12px !important;
            border-radius: 6px;
            font-size: 16px !important;
            cursor: pointer;
        }

        /* 🔪 ELIMINADOR DE LUPAS REBELDES */
        .btn-logout::after,
        .btn-admin-nav::after {
            content: none !important;
            display: none !important;
        }

        /* 📱 MÓVIL: Dejar solo la lupa en el botón Buscar */
        @media (max-width: 768px) {
            .texto-buscar {
                display: none;
            }
            .header a[href="buscar.php"] button::after {
                content: "🔍" !important; 
            }
            .header a[href="buscar.php"] button {
                padding: 8px 12px !important; 
                display: flex;
                align-items: center;
                justify-content: center;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <div class="header">
        <a href="index.php" style="text-decoration: none; color: inherit;">
            <span>Droguería Valcárcel</span>
        </a>
        <div class="header-btns">
            <?php if ($_SESSION['rol'] === 'admin'): ?>
                <a href="usuarios.php" title="Gestión de Usuarios"><button type="button" class="btn-admin-nav">👥</button></a>
            <?php endif; ?>
            <a href="buscar.php"><button type="button"><span class="texto-buscar">Buscar</span></button></a>
            <a href="logout.php" title="Cerrar sesión"><button type="button" class="btn-logout"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle;"><path d="M18.36 6.64a9 9 0 1 1-12.73 0"></path><line x1="12" y1="2" x2="12" y2="12"></line></svg></button></a>
        </div>
    </div>

    <div class="barra-acciones-secundaria" style="justify-content: space-between; align-items: center;">
        
        <div id="reloj-vivo" style="color: #4e3420; font-weight: bold; background-color: #f5e3a1; padding: 8px 15px; border-radius: 8px; border: 1px solid #e8d38c; font-size: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            Cargando...
        </div>

        <?php if (!$dia_cerrado): ?>
            <button type="button" id="btn-modo-ticket" class="btn-ticket" onclick="toggleModoTicket()">Ticket</button>
        <?php endif; ?>
    </div>

    <?php if ($dia_cerrado): ?>
        <div class="cerrado">🔒 Día cerrado</div>
    <?php endif; ?>

    <?php if (isset($_GET['ok'])): ?>
        <div style="color: green;"><?php echo htmlspecialchars($_GET['ok'], ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div style="color: red;"><?php echo htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>


    <div class="movil-resumen">

        <div class="slide active">
            <div class="titulo">Día</div>
            <div class="fecha"><?php echo $fecha_texto; ?></div>
            <div class="total"><?php echo number_format($total_dia,2,',','.'); ?> €</div>

            <form action="pdf.php" method="GET">
                <input type="hidden" name="tipo" value="dia">
                <button type="submit">Imprimir</button>
            </form>
        </div>

        <div class="slide">
            <div class="titulo">Semana</div>
            <div class="fecha"><?php echo date("d/m", strtotime($inicio_semana)); ?></div>
            <div class="total"><?php echo number_format($total_semana,2,',','.'); ?> €</div>

            <form action="pdf.php" method="GET">
                <input type="hidden" name="tipo" value="semana">
                <button type="submit">Imprimir</button>
            </form>
        </div>

        <div class="slide">
            <div class="titulo"><?php echo $mes_nombre; ?></div>
            <div class="fecha"><?php echo $anio; ?></div>
            <div class="total"><?php echo number_format($total_mes,2,',','.'); ?> €</div>

            <form action="pdf.php" method="GET">
                <input type="hidden" name="tipo" value="mes">
                <button type="submit">Imprimir</button>
            </form>
        </div>

    </div>

    <h2>Añadir movimiento</h2>

    <form action="guardar.php" method="POST">
        <input type="text" id="input-concepto" name="concepto" placeholder="Concepto" list="lista-conceptos" autocomplete="off" oninput="comprobarPrecio(this.value)" <?php if($dia_cerrado) echo 'disabled'; ?>>
        
        <datalist id="lista-conceptos">
            <?php 
            foreach ($conceptos_para_lista as $c) {
                echo '<option value="' . htmlspecialchars($c, ENT_QUOTES, 'UTF-8') . '">';
            }
            ?>
        </datalist>

        <input type="number" id="input-precio" name="precio" step="0.01" placeholder="Precio Unidad" required <?php if($dia_cerrado) echo 'disabled'; ?>>
        
        <div class="form-row-inline">
            <input type="number" name="cantidad" min="1" step="1" placeholder="Cantidad" <?php if($dia_cerrado) echo 'disabled'; ?>>
            <button type="submit" <?php if($dia_cerrado) echo 'disabled'; ?>>Guardar</button>
        </div>
    </form>

    <h2 class="toggle-movimientos">Movimientos del día</h2>

    <div class="ticket-ui-block" style="display: none; text-align: center; margin-bottom: 20px;">
        <button type="button" class="btn-generar-ticket" onclick="generarTicket()">Generar ticket</button>
    </div>

    <div class="movimientos-movil">
        <?php
        $resultado->data_seek(0);
        while($fila = $resultado->fetch_assoc()):
        ?>
            <div class="mov-item" style="display: flex; align-items: center;">
                <div class="ticket-ui-inline" style="display: none; margin-right: 15px;">
                    <input type="checkbox" class="check-ticket" value="<?php echo (int)$fila['id']; ?>" style="transform: scale(1.5);">
                </div>
                <div style="flex-grow: 1;">
                    <?php if ($accion !== "dia"): ?>
                        <div class="mov-fecha">
                            <?php echo date("d/m/Y", strtotime($fila['fecha'])); ?>
                        </div>
                    <?php endif; ?>
                    <div class="mov-linea">
                        <div class="mov-concepto">
                            <?php echo htmlspecialchars($fila['concepto'], ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                        <div class="mov-importe">
                            <?php echo number_format($fila['importe'], 2, ',', '.'); ?> €
                        </div>
                    </div>
                    <?php if (!$dia_cerrado): ?>
                    <div class="mov-acciones">
                        <a href="editar.php?id=<?php echo (int)$fila['id']; ?>">Editar</a>
                        <a href="#" onclick="abrirModalBorrar(
                            <?php echo (int)$fila['id']; ?>,
                            '<?php echo htmlspecialchars($fila['concepto'], ENT_QUOTES, 'UTF-8'); ?>',
                            '<?php echo number_format($fila['importe'], 2, ',', '.'); ?>'
                        ); return false;">Eliminar</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>
    </div>

    <table>
        <tr>
            <th class="ticket-ui-table" style="display: none; text-align: center; width: 50px;">Sel.</th>
            <th>Fecha</th>
            <th>Concepto</th>
            <th class="importe">Importe</th>
            <?php if (!$dia_cerrado): ?>
                <th class="acciones-header">Acciones</th>
            <?php endif; ?>
        </tr>
        <?php
        $resultado->data_seek(0);
        while($fila = $resultado->fetch_assoc()):
        ?>
            <tr>
                <td class="ticket-ui-table" style="display: none; text-align: center;">
                    <input type="checkbox" class="check-ticket" value="<?php echo (int)$fila['id']; ?>" style="transform: scale(1.3); cursor: pointer;">
                </td>
                <td><?php echo date("d/m/Y", strtotime($fila['fecha'])); ?></td>
                <td><?php echo htmlspecialchars($fila['concepto'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td class="importe"><?php echo number_format($fila['importe'], 2, ',', '.'); ?> €</td>
                <?php if (!$dia_cerrado): ?>
                <td class="acciones-col">
                    <a href="editar.php?id=<?php echo (int)$fila['id']; ?>">✏️</a>
                    <a href="#" onclick="abrirModalBorrar(
                        <?php echo (int)$fila['id']; ?>,
                        '<?php echo htmlspecialchars($fila['concepto'], ENT_QUOTES, 'UTF-8'); ?>',
                        '<?php echo number_format($fila['importe'], 2, ',', '.'); ?>'
                    ); return false;">🗑</a>
                </td>
                <?php endif; ?>
            </tr>
        <?php endwhile; ?>
    </table>

    <div class="botones-top">
        <a href="index.php?accion=dia"><button type="button">Total Día</button></a>
        <a href="index.php?accion=semana"><button type="button">Total Semana</button></a>
        <a href="index.php?accion=mes"><button type="button">Total Mes</button></a>
    </div>

    <?php if ($total !== null): ?>
        <div class="total">
            Total: <?php echo number_format($total, 2, ',', '.'); ?> €
        </div>
    <?php endif; ?>

    <div class="acciones">
        <?php if (!$dia_cerrado): ?>
            <button onclick="abrirModal()">Cerrar día</button>
        <?php else: ?>
            <form action="reabrir_dia.php" method="POST">
                <button type="submit">Reabrir día</button>
            </form>
        <?php endif; ?>
        <form action="pdf.php" method="GET">
            <input type="hidden" name="tipo" value="<?php echo htmlspecialchars($accion, ENT_QUOTES, 'UTF-8'); ?>">
            <button type="submit">Imprimir datos</button>
        </form>
    </div>

</div>

<div class="modal-bg" id="modal">
    <div class="modal">
        <div class="modal-header">Confirmar cierre</div>
        <div class="modal-body">¿Seguro que quieres cerrar el día <?php echo $fecha_texto; ?>?</div>
        <div class="modal-footer">
            <button class="btn-cancel" onclick="cerrarModal()">Cancelar</button>
            <form action="cerrar_dia.php" method="POST">
                <input type="hidden" name="fecha" value="<?php echo $fecha; ?>">
                <button class="btn-confirm">Cerrar día</button>
            </form>
        </div>
    </div>
</div>

<div class="modal-bg" id="modal-borrar">
    <div class="modal">
        <div class="modal-header">Confirmar eliminación</div>
        <div class="modal-body" id="texto-borrar"></div>
        <div class="modal-footer">
            <button class="btn-cancel" onclick="cerrarModalBorrar()">Cancelar</button>
            <a id="btn-confirm-borrar" class="btn-confirm">Eliminar</a>
        </div>
    </div>
</div>

<script src="scripts.js"></script>

<script>
    // Pasamos el mapa de precios de PHP a JavaScript de forma segura (con BLINDAJE JSON)
    const mapaPrecios = <?php echo json_encode($precios_auto, JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE | JSON_FORCE_OBJECT) ?: '{}'; ?>;

    function comprobarPrecio(valor) {
        const inputPrecio = document.getElementById('input-precio');
        // Si el producto existe en nuestra base de datos, rellenamos el precio
        if (mapaPrecios[valor]) {
            inputPrecio.value = mapaPrecios[valor];
        }
    }
</script>

<script>
    function actualizarReloj() {
        const ahora = new Date();
        const dia = String(ahora.getDate()).padStart(2, '0');
        const mes = String(ahora.getMonth() + 1).padStart(2, '0');
        const anio = ahora.getFullYear();
        const horas = String(ahora.getHours()).padStart(2, '0');
        const minutos = String(ahora.getMinutes()).padStart(2, '0');
        const textoReloj = dia + '/' + mes + '/' + anio + ' &nbsp;|&nbsp; ' + horas + ':' + minutos;
        const relojEl = document.getElementById('reloj-vivo');
        if (relojEl) { relojEl.innerHTML = textoReloj; }
    }
    setInterval(actualizarReloj, 1000);
    actualizarReloj();
</script>

</body>
</html>