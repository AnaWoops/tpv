<?php
include("seguridad.php");
include("conexion.php");

// SEGURIDAD PARA QUE SOLO EL JEFE PUEDA ENTRAR AQUI Y ECHAR A LOS EMPLEADOS SI INTENTAN COLARSE
if ($_SESSION['rol'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$mensaje = "";

// ESTA ES LA PARTE QUE CAMBIA LA CONTRASEÑA EN LA BASE DE DATOS CUANDO SE ENVIA EL FORMULARIO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = (int)$_POST['user_id'];
    $new_pass = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $new_pass, $user_id);
    
    if ($stmt->execute()) {
        $mensaje = "✅ Contraseña actualizada correctamente.";
    } else {
        $mensaje = "❌ Error al actualizar.";
    }
}

$usuarios = $conn->query("SELECT id, username, rol FROM usuarios");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Gestión de Usuarios - Droguería Valcárcel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <style>
        .user-card {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            border-left: 5px solid #f5e3a1;
        }
        .btn-save { 
            background-color: #4e3420; 
            color: white; 
            width: 100%; 
            margin-top: 10px; 
            padding: 12px;
            font-size: 16px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
        }
        .btn-save:hover {
            background-color: #362315;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <span>Gestión de Personal</span>
        <a href="index.php" style="margin-left:auto;"><button type="button">Volver</button></a>
    </div>

    <?php if ($mensaje !== ""): ?>
        <div style="margin: 20px 0; padding: 15px; background: #d4edda; color: #155724; border-radius: 8px; border: 1px solid #c3e6cb;">
            <?php echo $mensaje; ?>
        </div>
    <?php endif; ?>

    <h2>Lista de Usuarios</h2>

    <?php while($u = $usuarios->fetch_assoc()): ?>
        <div class="user-card">
            <strong>Usuario:</strong> <?php echo htmlspecialchars($u['username']); ?> <br>
            <strong>Cargo:</strong> <?php echo ($u['rol'] === 'admin') ? 'Administrador' : 'Empleado'; ?>
            
            <hr style="margin: 15px 0; border: 0; border-top: 1px solid #eee;">
            
            <form method="POST">
                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                <label style="font-size: 14px; font-weight: bold; display: block; margin-bottom: 8px; color: #444;">
                    Nueva contraseña para este usuario:
                </label>
                <input type="text" name="new_password" placeholder="Escribe la nueva clave..." required style="margin-bottom:5px;">
                <button type="submit" class="btn-save">Cambiar Contraseña</button>
            </form>
        </div>
    <?php endwhile; ?>
</div>
</body>
</html>