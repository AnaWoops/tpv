<?php
// Comprobamos si la sesión ya está iniciada antes de intentar abrirla
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si no hay un usuario registrado en la sesión, lo echamos al login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}
?>