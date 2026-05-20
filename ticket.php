<?php
include("conexion.php");
include("seguridad.php");

$ids_raw = $_GET['ids'] ?? '';

if (!preg_match('/^[0-9,]+$/', $ids_raw)) {
    die("IDs no válidos.");
}

$sql = "SELECT * FROM movimientos WHERE id IN ($ids_raw) ORDER BY id ASC";
$resultado = $conn->query($sql);

$total_ticket = 0;
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vista Previa del Ticket</title>
    <style>
        /* CSS AJUSTADO AL MILÍMETRO */
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
            color: #000000;
        }

        .ticket-preview-container {
            background: white;
            width: 576px; 
            margin: 0 auto;
            padding: 20px 25px;
            box-sizing: border-box;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            -webkit-font-smoothing: none;
            font-smooth: never;

            /* TRUCO VISUAL: Renderiza el ticket al 65% en pantalla para que no sea un mamotreto */
            zoom: 0.65;
        }

        /* Más espacio entre la fecha y la tabla */
        .header { 
            text-align: center; 
            margin-bottom: 45px; 
        }
        
        /* Más espacio entre la tienda y Ticket de caja */
        .header h1 { 
            font-size: 48px; 
            font-weight: 900; 
            margin: 0 0 40px 0; 
            color: #000000; 
            letter-spacing: -1px; 
        }
        
        .header p { 
            margin: 5px 0; 
            font-size: 24px; 
            font-weight: 500; 
            color: #000000; 
        }

        /* LÍNEA CONTINUA AÑADIDA AQUÍ */
        .divider-solid { 
            border-bottom: 2px solid #000000; 
            margin-bottom: 15px; 
        }

        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        
        th { 
            text-align: left; 
            padding: 10px 0; 
            border-bottom: 2px dashed #000000; 
            font-size: 24px; 
            font-weight: bold; 
            color: #000000; 
        }
        
        /* Menos interlineado entre los productos (bajado a 5px) */
        td { 
            padding: 5px 0; 
            font-size: 24px; 
            font-weight: normal; 
            color: #000000; 
            border-bottom: none; 
        }

        .text-right { text-align: right; }
        .text-left { text-align: left; }

        .total-row td { 
            font-size: 32px; 
            font-weight: 900; 
            border-top: 3px solid #000000; 
            padding-top: 15px; 
        }

        .footer { text-align: center; margin-top: 30px; }
        .footer p.gracias { 
            margin: 5px 0; 
            font-size: 24px; 
            font-weight: normal; 
            color: #000000; 
        }
        .footer p.iva { 
            margin: 5px 0; 
            font-size: 18px; 
            font-weight: normal; 
            color: #000000; 
        }

        /* Los botones se mantienen a escala normal 100% para que sean fáciles de pulsar */
        .acciones-preview { width: 400px; margin: 20px auto; display: flex; flex-direction: column; gap: 10px; }
        .btn-imprimir { background-color: #4e3420; color: white; border: none; padding: 20px; font-size: 22px; font-weight: bold; border-radius: 8px; cursor: pointer; text-align: center; transition: background 0.3s; }
        .btn-imprimir:hover { background-color: #3b2718; }
        .btn-volver { background-color: #e9ecef; color: #333; border: none; padding: 15px; font-size: 20px; font-weight: bold; border-radius: 8px; cursor: pointer; text-align: center; text-decoration: none; }

        @media print { .acciones-preview { display: none; } }
    </style>
</head>
<body>

<div class="ticket-preview-container">
    <div class="ticket-content">
        
        <div class="header">
            <h1>DROGUERÍA VALCÁRCEL</h1>
            <p>Ticket de caja</p>
            <p><?php echo date("d/m/Y H:i"); ?></p>
        </div>
        
        <div class="divider-solid"></div>
        
        <table>
            <thead>
                <tr>
                    <th class="text-left" style="width: 15%;">Cant.</th>
                    <th style="text-align: left; width: 45%;">Descripción</th>
                    <th class="text-right" style="width: 20%;">Precio</th>
                    <th class="text-right" style="width: 20%;">Importe</th>
                </tr>
            </thead>
            <tbody>
            <?php
            if ($resultado && $resultado->num_rows > 0):
                while ($fila = $resultado->fetch_assoc()):
                    $importe_db = (float)$fila['importe'];
                    $concepto_db = $fila['concepto'];
                    $cant = 1;
                    $precio = $importe_db;
                    $concepto_ticket = $concepto_db;

                    if (preg_match('/^(.*?)\s+x(\d+)$/', $concepto_db, $matches)) {
                        $concepto_ticket = $matches[1];
                        $cant = (int)$matches[2];
                        if ($cant > 0) {
                            $precio = $importe_db / $cant;
                        }
                    }

                    $total_ticket += $importe_db;
                    $precio_mostrar = ($cant > 1) ? number_format($precio, 2, ',', '.') : '';
            ?>
                <tr>
                    <td class="text-left">
                        <?php echo $cant; ?>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($concepto_ticket, ENT_QUOTES, 'UTF-8'); ?>
                    </td>
                    <td class="text-right">
                        <?php echo $precio_mostrar; ?>
                    </td>
                    <td class="text-right">
                        <?php echo number_format($importe_db, 2, ',', '.'); ?>€
                    </td>
                </tr>
            <?php
                endwhile;
            endif;
            ?>
            </tbody>
        </table>

        <table style="margin-top: 15px;">
            <tr class="total-row">
                <td>TOTAL</td>
                <td class="text-right">
                    <?php echo number_format($total_ticket, 2, ',', '.'); ?>€
                </td>
            </tr>
        </table>

        <div class="footer">
            <p class="gracias">Gracias por su visita</p>
            <p class="iva">IVA Incluido</p>
        </div>

    </div>
</div>

<div class="acciones-preview">
    <button class="btn-imprimir" onclick="prepararYImprimir()">
        🖨️ Imprimir Ticket
    </button>
    <a href="index.php" class="btn-volver">⬅ Volver al inicio</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/qz-tray/qz-tray.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="scripts.js"></script>

<script>
    function prepararYImprimir() {
        // 1. Damos un aviso visual para que sepas que está trabajando
        var btn = document.querySelector('.btn-imprimir');
        btn.innerHTML = '⏳ Procesando imagen...';
        btn.style.backgroundColor = '#666';

        // 2. Quitamos el zoom temporalmente para que html2canvas capture a los 576px reales
        var ticket = document.querySelector('.ticket-preview-container');
        ticket.style.zoom = '1';

        // 3. Dejamos un brevísimo instante (150ms) para que Chrome aplique el tamaño real antes de la foto
        setTimeout(function() {
            // Llama a tu función intacta de scripts.js
            imprimirTicketHTML();
        }, 150);
    }
</script>

</body>
</html>