<?php
session_start();
include("config/conexion.php");

// --- Procesar acciones ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_usuario = intval($_POST['id_usuario']);

    // Cambiar estado
    if (isset($_POST['toggle_estado'])) {
        $nuevo_estado = $_POST['estado_actual'] === 'ACTIVO' ? 'INACTIVO' : 'ACTIVO';
        $sql = "UPDATE usuarios SET estado = $1 WHERE id_usuario = $2";
        pg_query_params($conn, $sql, array($nuevo_estado, $id_usuario));
    }

    // Cambiar contraseña
    if (isset($_POST['cambiar_clave'])) {
        $nueva_clave = md5($_POST['nueva_clave']); // 🔒 encriptar
        $sql = "UPDATE usuarios SET clave = $1 WHERE id_usuario = $2";
        pg_query_params($conn, $sql, array($nueva_clave, $id_usuario));
    }

    // Eliminar usuario (y su empleado asociado)
    if (isset($_POST['eliminar_usuario'])) {
        $sql = "DELETE FROM empleados WHERE id_empleado = $1";
        pg_query_params($conn, $sql, array($id_usuario));
        // 🔄 Trigger en empleados eliminará también al usuario
    }
}

// --- Consultar usuarios ---
$sql = "SELECT u.id_usuario, e.nombre, e.apellido, u.perfil, u.estado, s.nombre_sucursal
        FROM usuarios u
        JOIN empleados e ON u.id_usuario = e.id_empleado
        JOIN sucursales s ON e.id_sucursal = s.id_sucursal
        ORDER BY u.id_usuario ASC";
$result = pg_query($conn, $sql);

include("includes/header.php");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Usuarios - Farmacia</title>
    <!-- Cambiando el CSS al estilo de farmacia -->
    <link rel="stylesheet" href="css/usuarios-pharmacy-style.css">
</head>
<body>

<!-- Aplicando estructura con container y título estilo farmacia -->
<div class="container">
    <h1>⚕️ Lista de Usuarios - Sistema Farmacia</h1>

    <table>
        <thead>
            <tr>
                <th>ID Usuario</th>
                <th>Nombre</th>
                <th>Apellido</th>
                <th>Perfil</th>
                <th>Estado</th>
                <th>Sucursal</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = pg_fetch_assoc($result)) : ?>
                <tr>
                    <td><?php echo $row['id_usuario']; ?></td>
                    <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                    <td><?php echo htmlspecialchars($row['apellido']); ?></td>
                    <td><?php echo htmlspecialchars($row['perfil']); ?></td>
                    <!-- Aplicando clases de estado con colores -->
                    <td class="<?php echo $row['estado'] === 'ACTIVO' ? 'estado-activo' : 'estado-inactivo'; ?>">
                        <?php echo htmlspecialchars($row['estado']); ?>
                    </td>
                    <td><?php echo htmlspecialchars($row['nombre_sucursal']); ?></td>
                    <td>
                        <!-- Aplicando clases de botones con estilo farmacia -->
                        <!-- Activar/Desactivar -->
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="id_usuario" value="<?php echo $row['id_usuario']; ?>">
                            <input type="hidden" name="estado_actual" value="<?php echo $row['estado']; ?>">
                            <button type="submit" name="toggle_estado" class="btn btn-estado">
                                <?php echo $row['estado'] === 'ACTIVO' ? 'Desactivar' : 'Activar'; ?>
                            </button>
                        </form>

                        <!-- Cambiar contraseña -->
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="id_usuario" value="<?php echo $row['id_usuario']; ?>">
                            <input type="password" name="nueva_clave" placeholder="Nueva clave" required>
                            <button type="submit" name="cambiar_clave" class="btn btn-password">Cambiar Clave</button>
                        </form>

                        <!-- Eliminar usuario -->
                        <form method="POST" style="display:inline;" onsubmit="return confirm('¿Está seguro de eliminar este usuario?');">
                            <input type="hidden" name="id_usuario" value="<?php echo $row['id_usuario']; ?>">
                            <button type="submit" name="eliminar_usuario" class="btn btn-eliminar">Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <!-- Botón de regreso con estilo farmacia -->
    <div style="text-align: center; margin-top: 30px;">
        <a href="admin.php" class="btn btn-estado" style="text-decoration: none;">
            ← Regresar al Panel Admin
        </a>
    </div>
</div>

<?php include("includes/footer.php"); ?>

</body>
</html>
