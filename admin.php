<?php
session_start();

// Solo admins pueden entrar
if (!isset($_SESSION['usuario']) || $_SESSION['perfil'] !== 'ADMIN') {
    header("Location: login.php");
    exit;
}

include("includes/header.php");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Farmacia</title>
    <link rel="stylesheet" href="css/admin-panel-elegant.css">
</head>
<body>
    <div class="container">
        <h2>Panel de Administración</h2>
        
        <div class="welcome-message">
            <p>Bienvenido, <strong><?php echo $_SESSION['nombre']; ?></strong></p>
            <p>Sucursal: <strong><?php echo $_SESSION['sucursal']; ?></strong></p>
        </div>

        <ul class="admin-menu">
            <li><a href="registrar_usuario.php">➕ Registrar nuevo usuario</a></li>
            <li><a href="listar_usuarios.php">👥 Listar usuarios</a></li>
            <li><a href="transferencia_admin.php">📦 Gestionar Sucursales</a></li>
            <li><a href="transferencia.php">📦 Gestionar transferencias</a></li>
            <li><a href="ver_inventario.php">📦 Inventario</a></li>
            <li><a href="logout.php">🚪 Cerrar sesión</a></li>
        </ul>
    </div>
</body>
</html>

<?php include("includes/footer.php"); ?>
