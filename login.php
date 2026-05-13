<?php
session_start();
include("conexion.php");

// SI EL USUARIO YA ESTA LOGUEADO QUE SE VAYA AL INDEX DIRECTAMENTE PARA QUE NO VEA ESTO
if (isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

$error = "";

// MIRAR SI SE HA MANDADO EL FORMULARIO POR POST AL PULSAR EL BOTON
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = "Por favor, rellena todos los campos.";
    } else {
        // SACAR LOS DATOS DEL USUARIO DE LA BASE DE DATOS PARA COMPROBARLOS
        $stmt = $conn->prepare("SELECT id, username, password, rol FROM usuarios WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows === 1) {
            $usuario = $resultado->fetch_assoc();
            
            // COMPROBAR QUE LA CLAVE ES LA QUE CORRESPONDE CON LA ENCRIPTADA
            if (password_verify($password, $usuario['password'])) {
                
                // SI TODO ESTA CORRECTO GUARDAMOS SUS DATOS EN LA SESION
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['username'] = $usuario['username'];
                $_SESSION['rol'] = $usuario['rol'];
                
                // MANDAR AL USUARIO DIRECTAMENTE A LA CONTABILIDAD
                header("Location: index.php");
                exit;
            } else {
                $error = "Usuario o contraseña incorrectos.";
            }
        } else {
            $error = "Usuario o contraseña incorrectos.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Acceso - Droguería Valcárcel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <style>
        /* ESTILOS PARA PONER LA CAJA DEL LOGIN JUSTO EN EL MEDIO DE LA PANTALLA */
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        
        .login-container {
            width: 100%;
            max-width: 380px;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin: 20px;
            box-sizing: border-box;
        }

        .login-header {
            background-color: #f5e3a1;
            padding: 15px;
            font-size: 22px;
            font-weight: bold;
            text-align: center;
            border-radius: 8px;
            margin-bottom: 25px;
            color: #333;
        }

        .login-container label {
            font-weight: bold;
            display: block;
            margin-bottom: 8px;
            color: #444;
            font-size: 15px;
        }

        .login-container input {
            width: 100%;
            padding: 14px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 16px;
            box-sizing: border-box;
        }

        .login-container button {
            width: 100%;
            background-color: #4e3420;
            color: white;
            padding: 14px;
            font-size: 18px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.2s;
        }

        .login-container button:hover {
            background-color: #362315;
        }

        .error-msg {
            color: #856404;
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
            font-size: 14px;
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="login-header">Droguería Valcárcel</div>
    
    <?php if ($error !== ""): ?>
        <div class="error-msg"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <label>Usuario</label>
        <input type="text" name="username" required autofocus autocomplete="off">

        <label>Contraseña</label>
        <input type="password" name="password" required>

        <button type="submit">Entrar</button>
    </form>
</div>

</body>
</html>