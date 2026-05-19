<?php
include("conexion.php");
include("seguridad.php");

// COGEMOS LOS IDS QUE NOS LLEGAN POR LA URL
$ids_raw = $_GET['ids'] ?? '';

// REVISAMOS QUE NO METAN NADA RARO QUE NO SEAN NUMEROS O COMAS
if (!preg_match('/^[0-9,]+$/', $ids_raw)) {
    die("IDs no válidos.");
}

// BUSCAMOS LOS DATOS DE LOS MOVIMIENTOS ELEGIDOS EN LA BASE DE DATOS
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
        /* ESTILOS PARA QUE EL TICKET SE VEA BIEN EN LA PANTALLA ANTES DE IMPRIMIR */
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        
        .ticket-preview-container {
            background: white;
            max-width: 400px; 
            margin: 0 auto;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .acciones-preview {
            max-width: 400px;
            margin: 20px auto;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .btn-imprimir {
            background-color: #4e3420;
            color: white;
            border: none;
            padding: 15px;
            font-size: 18px;
            font-weight: bold;
            border-radius: 8px;
            cursor: pointer;
            text-align: center;
        }

        .btn-volver {
            background-color: #e9ecef;
            color: #333;
            border: none;
            padding: 12px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 8px;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
        }

        /* ESTO ES PARA DARLE FORMA DE TICKET DE TODA LA VIDA EN EL MONITOR */
        .ticket-content {
            font-family: monospace;
            font-size: 16px;
        }
        .ticket-content h1 { font-size: 22px; margin: 5px 0; text-align: center; }
        .ticket-content p { margin: 2px 0; text-align: center; color: #666; }
        .divider { border-bottom: 1px dashed #ccc; margin: 15px 0; }
        
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px 0; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .total-row { font-weight: bold; font-size: 20px; border-top: 2px solid #333; }

        /* ESTOS SON LOS AJUSTES PARA QUE LA IMPRESORA TERMICA NO HAGA TONTERIAS */
        @media print {
            @page { margin: 0; }
            body {
                background-color: white;
                padding: 0;
                margin: 0;
            }
            .ticket-preview-container {
                box-shadow: none;
                border-radius: 0;
                padding: 10px;
                width: 80mm; 
                max-width: 100%;
                margin: 0 auto;
            }
            .ticket-content {
                font-size: 14px; 
                color: #000;
            }
            .ticket-content p { color: #000; }
            .divider { border-bottom: 1px dashed #000; }
            .total-row { border-top: 1px solid #000; font-size: 16px; }
            
            /* QUITAMOS LOS BOTONES PORQUE NO QUEREMOS QUE SALGAN IMPRESOS */
            .acciones-preview { display: none; }
        }
    </style>
</head>
<body>

    <div class="ticket-preview-container">
        <div class="ticket-content">
            <h1>DROGUERÍA VALCÁRCEL</h1>
            <p>Ticket de caja</p>
            <p><?php echo date("d/m/Y H:i"); ?></p>
            
            <div class="divider"></div>

            <table>
                <thead>
                    <tr>
                        <th class="text-left" style="width: 12%; padding-right: 15px;">Cant.</th>
                        <th style="text-align: left;">Descripción</th>
                        <th style="text-align: right; width: 22%;">Precio</th>
                        <th style="text-align: right; width: 25%;">Importe</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($resultado && $resultado->num_rows > 0): 
                        while($fila = $resultado->fetch_assoc()): 
                            
                            $importe_db = (float)$fila['importe'];
                            $concepto_db = $fila['concepto'];
                            
                            // SI NO HAY NADA RARO PONEMOS 1 UNIDAD POR DEFECTO
                            $cant = 1;
                            $precio = $importe_db;
                            $concepto_ticket = $concepto_db;

                            // SI EL NOMBRE TRAE EL FORMATO X2 O X3 SACAMOS EL PRECIO DE CADA UNIDAD
                            if (preg_match('/^(.*?)\s+x(\d+)$/', $concepto_db, $matches)) {
                                $concepto_ticket = $matches[1];
                                $cant = (int)$matches[2];
                                if ($cant > 0) {
                                    $precio = $importe_db / $cant;
                                }
                            }

                            $total_ticket += $importe_db;
                            
                            // SI SOLO ES UNA UNIDAD NO HACE FALTA PONER EL PRECIO SUELTO
                            $precio_mostrar = ($cant > 1) ? number_format($precio, 2, ',', '.') : '';
                    ?>
                        <tr>
                            <td class="text-left" style="padding-right: 15px;"><?php echo $cant; ?></td>
                            <td><?php echo htmlspecialchars($concepto_ticket, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="text-right"><?php echo $precio_mostrar; ?></td>
                            <td class="text-right"><?php echo number_format($importe_db, 2, ',', '.'); ?>€</td>
                        </tr>
                    <?php 
                        endwhile;
                    endif; 
                    ?>
                </tbody>
            </table>

            <div class="divider"></div>

            <table>
                <tr class="total-row">
                    <td>TOTAL</td>
                    <td class="text-right"><?php echo number_format($total_ticket, 2, ',', '.'); ?>€</td>
                </tr>
            </table>

            <div>
                <p style="text-align: center; margin-top: 25px; font-size: 15px;">Gracias por su visita</p>
                <p style="text-align: center; font-size: 12px;">IVA Incluido</p>
            </div>
        </div>
    </div>

    <div class="acciones-preview">
        <button class="btn-imprimir" onclick="imprimirTicketDinamico()">🖨️ Imprimir Ticket</button>
        <a href="index.php" class="btn-volver">⬅ Volver al inicio</a>
    </div>

    </div> <script src="scripts.js"></script>
    
</body>
</html>