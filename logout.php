<?php
session_start();
session_destroy(); // Destruimos todas las variables de sesión
header("Location: login.php"); // Redirigimos al login
exit;
?>