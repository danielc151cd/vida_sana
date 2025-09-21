<?php
session_start();
include("config/conexion.php");

// Traer sucursales para el <select>
$sql_suc = "SELECT id_sucursal, nombre_sucursal FROM sucursales ORDER BY nombre_sucursal ASC";
$res_suc = pg_query($conn, $sql_suc);
$sucursales = [];
while ($row = pg_fetch_assoc($res_suc)) {
    $sucursales[] = $row;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre      = trim($_POST['nombre']);
    $apellido    = trim($_POST['apellido']);
    $cedula      = trim($_POST['cedula']);
    $id_sucursal = intval($_POST['id_sucursal']);
    $clave       = md5(trim($_POST['clave']));
    $cargo       = "FARMACEUTICO"; // o el rol que quieras por defecto

    // 🔄 Sincronizar secuencia antes de insertar
    $sql_sync = "
    WITH max_ids AS (
        SELECT GREATEST(
            COALESCE((SELECT MAX(id_usuario) FROM usuarios), 0),
            COALESCE((SELECT MAX(id_empleado) FROM empleados), 0)
        ) AS max_id
    )
    SELECT setval('usuarios_seq', (max_id + 1), false) FROM max_ids;
    ";
    pg_query($conn, $sql_sync);

    // Insertar empleado y obtener id_empleado generado
    $sql_emp = "INSERT INTO empleados (cedula, nombre, apellido, cargo, id_sucursal, salario, estado_empleado)
                VALUES ($1, $2, $3, $4, $5, 20000, 'ACTIVO')
                RETURNING id_empleado";
    $res_emp = pg_query_params($conn, $sql_emp, array($cedula, $nombre, $apellido, $cargo, $id_sucursal));

    if ($res_emp) {
        $row_emp = pg_fetch_assoc($res_emp);
        $id_usuario = $row_emp['id_empleado'];

        // ⚡ El trigger ya creó en usuarios → solo actualizamos clave y perfil
        $sql_usr = "UPDATE usuarios
                    SET clave = $1, perfil = 'administrativo', estado = 'ACTIVO'
                    WHERE id_usuario = $2";
        $ok_usr = pg_query_params($conn, $sql_usr, array($clave, $id_usuario));

        if ($ok_usr) {
            $mensaje = "✅ Usuario registrado correctamente.<br>
                        ID Usuario: <strong>$id_usuario</strong><br>
                        Nombre: <strong>$nombre $apellido</strong><br>
                        Sucursal: <strong>" . htmlspecialchars($_POST['sucursal_nombre']) . "</strong><br>
                        Clave: <strong>(la que ingresaste)</strong>";
        } else {
            $error = "❌ Error al actualizar el usuario.";
        }
    } else {
        $error = "❌ Error al registrar en empleados.";
    }
}

include("includes/header.php");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Usuario - Sistema Farmacia</title>
    <link rel="stylesheet" href="css/registro-usuario-elegant.css">
</head>
<body>
    <div class="container">
        <h2>⚕️ Registrar Nuevo Usuario</h2>
        
        <form method="POST">
            <div class="form-group">
                <label for="nombre">Nombre:</label>
                <input type="text" id="nombre" name="nombre" required>
            </div>

            <div class="form-group">
                <label for="apellido">Apellido:</label>
                <input type="text" id="apellido" name="apellido" required>
            </div>

            <div class="form-group">
                <label for="cedula">Cédula:</label>
                <input type="text" id="cedula" name="cedula" required>
            </div>

            <div class="form-group">
                <label for="id_sucursal">Sucursal:</label>
                <select id="id_sucursal" name="id_sucursal" required onchange="document.getElementById('sucursal_nombre').value=this.options[this.selectedIndex].text">
                    <option value="">-- Seleccione una sucursal --</option>
                    <?php foreach ($sucursales as $s): ?>
                        <option value="<?php echo $s['id_sucursal']; ?>">
                            <?php echo htmlspecialchars($s['nombre_sucursal']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="sucursal_nombre" id="sucursal_nombre">
            </div>

            <div class="form-group">
                <label for="clave">Contraseña:</label>
                <input type="password" id="clave" name="clave" required>
            </div>

            <button type="submit" class="btn-submit">Registrar Usuario</button>
        </form>

        <?php if(isset($mensaje)): ?>
            <div class="message success"><?php echo $mensaje; ?></div>
        <?php endif; ?>

        <?php if(isset($error)): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
    </div>
</body>
</html>

<?php include("includes/footer.php"); ?>
