<?php
header('Access-Control-Allow-Origin: *');
$request = $_GET['request'] ?? '';
if (!$request) die();

// Leemos la llave privada
$privateKey = file_get_contents('llave.pem');

// Firmamos la orden que manda QZ Tray
openssl_sign($request, $signature, $privateKey, "sha512");

// Se la devolvemos aprobada
echo base64_encode($signature);
?>