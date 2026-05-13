<?php
require 'vendor/autoload.php';
use Dompdf\Dompdf;

include("conexion.php");
include("seguridad.php");

// SEGURIDAD PARA QUE NO METAN TEXTOS RAROS POR LA URL
$tipo_crudo = $_GET['tipo'] ?? 'dia';
$tipos_permitidos = ['dia', 'semana', 'mes'];
$tipo = in_array($tipo_crudo, $tipos_permitidos) ? $tipo_crudo : 'dia';

$hoy = date("Y-m-d");

$meses = [
    1 => "Enero", 2 => "Febrero", 3 => "Marzo",
    4 => "Abril", 5 => "Mayo", 6 => "Junio",
    7 => "Julio", 8 => "Agosto", 9 => "Septiembre",
    10 => "Octubre", 11 => "Noviembre", 12 => "Diciembre"
];

$mostrar_fecha = false;

// RESUMEN DEL DIA ACTUAL
if ($tipo === 'dia') {

    $titulo = "Resumen del día " . date("d/m/Y");

    $sql = "SELECT * FROM movimientos 
            WHERE fecha = '$hoy' 
            ORDER BY id DESC";

    $sql_total = "SELECT SUM(importe) as total 
                  FROM movimientos 
                  WHERE fecha = '$hoy'";
}

// RESUMEN DE LA SEMANA CALCULANDO DESDE EL LUNES
elseif ($tipo === 'semana') {

    $inicio = date("Y-m-d", strtotime("monday this week"));
    $fin = $hoy;

    $dia_inicio = (int)date("d", strtotime($inicio));
    $dia_fin = (int)date("d", strtotime($fin));
    $mes_nombre = $meses[(int)date("m", strtotime($inicio))];
    $anio = date("Y");

    $titulo = "Resumen semanal";
    $sub1 = "$mes_nombre $anio";
    $sub2 = "Semana del $dia_inicio al $dia_fin";

    $sql = "SELECT * FROM movimientos 
            WHERE fecha BETWEEN '$inicio' AND '$fin' 
            ORDER BY fecha ASC, id ASC";

    $sql_total = "SELECT SUM(importe) as total 
                  FROM movimientos 
                  WHERE fecha BETWEEN '$inicio' AND '$fin'";

    $mostrar_fecha = true;
}

// RESUMEN DEL MES DESDE EL PRIMER DIA HASTA HOY
elseif ($tipo === 'mes') {

    $inicio = date("Y-m-01");
    $fin = $hoy;

    $mes_nombre = $meses[(int)date("m")];
    $anio = date("Y");

    $titulo = "Resumen mensual";
    $sub1 = "$mes_nombre $anio";

    $sql = "SELECT * FROM movimientos 
            WHERE fecha BETWEEN '$inicio' AND '$fin' 
            ORDER BY fecha ASC, id ASC";

    $sql_total = "SELECT SUM(importe) as total 
                  FROM movimientos 
                  WHERE fecha BETWEEN '$inicio' AND '$fin'";

    $mostrar_fecha = true;
}

// EJECUTAR LAS CONSULTAS Y SACAR EL TOTAL
$resultado = $conn->query($sql);
$resultado_total = $conn->query($sql_total);
$fila_total = $resultado_total->fetch_assoc();
$total = $fila_total['total'] ?? 0;

// DISEÑO DEL PDF CON CSS Y HTML
$html = "
<style>
    body {
        font-family: Arial, sans-serif;
        color: #333;
        margin: 0;
    }

    .header {
        background-color: #f5e3a1;
        padding: 15px 25px;
    }

    .titulo {
        font-size: 18px;
        font-weight: bold;
        margin-bottom: 5px;
    }

    .sub {
        font-size: 13px;
        color: #555;
    }

    .container {
        padding: 25px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }

    th {
        background-color: #f5e3a1;
        text-align: left;
        padding: 10px;
        font-size: 13px;
    }

    td {
        padding: 8px;
        border-bottom: 1px solid #eee;
        font-size: 13px;
    }

    .importe {
        text-align: right;
    }

    .fecha-grupo {
        background-color: #faf3d0;
        font-weight: bold;
        padding: 6px;
    }

    .total-row td {
        border-top: 2px solid #000;
        font-weight: bold;
        padding-top: 12px;
    }

    .total-label {
        text-align: right;
    }

    .total-value {
        text-align: right;
        font-size: 16px;
        background-color: #f5e3a1;
        padding: 8px;
    }
</style>

<div class='header'>
    <div class='titulo'>$titulo</div>
";

if (!empty($sub1)) {
    $html .= "<div class='sub'>" . htmlspecialchars($sub1, ENT_QUOTES, 'UTF-8') . "</div>";
}
if (!empty($sub2)) {
    $html .= "<div class='sub'>" . htmlspecialchars($sub2, ENT_QUOTES, 'UTF-8') . "</div>";
}

$html .= "</div><div class='container'>";

$html .= "<table><tr>";

if ($mostrar_fecha) {
    $html .= "<th>Fecha</th>";
}

$html .= "
    <th>Concepto</th>
    <th class='importe'>Importe</th>
</tr>
";

// AGRUPAR POR FECHAS SI ESTAMOS EN VISTA SEMANAL O MENSUAL
$fecha_actual = null;

while ($fila = $resultado->fetch_assoc()) {

    $fecha_fila = $fila['fecha'];

    // SI LA FECHA CAMBIA PONEMOS UNA FILA DE SEPARACION CON EL NUEVO DIA
    if ($mostrar_fecha && $fecha_actual !== $fecha_fila) {

        $fecha_actual = $fecha_fila;

        $html .= "
        <tr>
            <td colspan='3' class='fecha-grupo'>" . date("d/m/Y", strtotime($fecha_actual)) . "</td>
        </tr>";
    }

    $html .= "<tr>";

    if ($mostrar_fecha) {
        $html .= "<td></td>"; 
    }

    $html .= "<td>" . htmlspecialchars($fila['concepto'], ENT_QUOTES, 'UTF-8') . "</td>";
    $html .= "<td class='importe'>" . number_format($fila['importe'], 2, ',', '.') . " €</td>";

    $html .= "</tr>";
}

// PONER EL TOTAL FINAL DE TODA LA TABLA
$colspan = $mostrar_fecha ? 2 : 1;

$html .= "
<tr class='total-row'>
    <td colspan='$colspan' class='total-label'>Total</td>
    <td class='total-value'>" . number_format($total, 2, ',', '.') . " €</td>
</tr>
</table>
</div>
";

// GENERAR EL PDF Y FORZAR LA DESCARGA
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4');
$dompdf->render();

$dompdf->stream("resumen_$tipo.pdf", ["Attachment" => true]);
?>