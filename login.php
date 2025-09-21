<?php
session_start();
include("config/conexion.php");

// Traer sucursales para el select
$sql_suc = "SELECT id_sucursal, nombre_sucursal FROM sucursales ORDER BY nombre_sucursal ASC";
$res_suc = pg_query($conn, $sql_suc);
$sucursales = [];
while ($row = pg_fetch_assoc($res_suc)) {
    $sucursales[] = $row;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario   = trim($_POST['usuario']);     
    $clave     = md5(trim($_POST['clave']));  
    $id_sucursal = intval($_POST['id_sucursal']);

    $sql = "SELECT u.id_usuario, u.perfil, e.nombre, s.nombre_sucursal
            FROM usuarios u
            JOIN empleados e ON u.id_usuario = e.id_empleado
            JOIN sucursales s ON e.id_sucursal = s.id_sucursal
            WHERE (u.id_usuario::text = $1 OR LOWER(e.nombre) = LOWER($1))
              AND u.clave = $2
              AND s.id_sucursal = $3
              AND u.estado = 'ACTIVO'";

    $result = pg_query_params($conn, $sql, array($usuario, $clave, $id_sucursal));

    if ($result && pg_num_rows($result) > 0) {
        $row = pg_fetch_assoc($result);

        $_SESSION['usuario']  = $row['id_usuario'];
        $_SESSION['sucursal'] = $row['nombre_sucursal'];
        $_SESSION['nombre']   = $row['nombre'];
        $_SESSION['perfil']   = $row['perfil'];

        if ($row['perfil'] === 'ADMIN') {
            header("Location: admin.php");
        } else {
            header("Location: transferencia.php");
        }
        exit;
    } else {
        $error = "Usuario, clave o sucursal incorrectos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmacia - Sistema de Login</title>
    <link rel="stylesheet" href="css/pharmacy-modern.css">
</head>
<body>
    <div class="login-container">
        <div class="pharmacy-header">
            <div class="pharmacy-icon"></div>
            <h2>Farmacia Login</h2>
            <p class="subtitle">Acceso al Sistema de Gestión</p>
        </div>

        <form method="POST" id="loginForm">
            <div class="form-group">
                <label for="usuario">Usuario o Nombre</label>
                <input type="text" id="usuario" name="usuario" required>
            </div>

            <div class="form-group">
                <label for="clave">Contraseña</label>
                <input type="password" id="clave" name="clave" required>
            </div>

            <div class="form-group">
                <label for="id_sucursal">Sucursal</label>
                <select id="id_sucursal" name="id_sucursal" required>
                    <option value="">-- Seleccione una sucursal --</option>
                    <?php foreach ($sucursales as $s): ?>
                        <option value="<?php echo $s['id_sucursal']; ?>">
                            <?php echo htmlspecialchars($s['nombre_sucursal']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn-login" id="loginBtn">
                Ingresar al Sistema
            </button>
        </form>

        <?php if(isset($error)): ?>
            <div class="error-message">
                ❌ <?php echo $error; ?>
            </div>
        <?php endif; ?>
--------------------------------------------------------------------------------------------------------------
        <!--<div style="text-align: center; margin-top: 20px;">
            <a href="registrar_admin.php" class="register-link">
                ➕ Registrar Administrador
            </a>
        </div> -->
    </div>

    <script>
        // Agregar efecto de carga al botón
        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.getElementById('loginBtn');
            btn.classList.add('loading');
            btn.disabled = true;
        });

        // Animación para los inputs
        document.querySelectorAll('input, select').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('focused');
            });
        });
    </script>
</body>
</html>
