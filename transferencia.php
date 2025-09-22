<?php
session_start();
include("config/conexion.php");

if(!isset($_SESSION['usuario'])){
    header("Location: login.php");
    exit;
}

// 🔎 Obtener la sucursal del empleado logueado
$sql_suc_emp = "SELECT s.id_sucursal, s.nombre_sucursal
                FROM empleados e
                JOIN sucursales s ON e.id_sucursal = s.id_sucursal
                WHERE e.id_empleado = $1";
$res_suc_emp = pg_query_params($conn, $sql_suc_emp, array($_SESSION['usuario']));
$empleado_sucursal = pg_fetch_assoc($res_suc_emp);
$_SESSION['sucursal'] = $empleado_sucursal['id_sucursal'];

// Productos
$productos_res = pg_query($conn, "SELECT id_producto, nombre_producto FROM productos ORDER BY nombre_producto ASC");
$productos = [];
while($row = pg_fetch_assoc($productos_res)){
    $productos[] = $row;
}

// Sucursales destino (todas menos la propia)
$sql_suc_dest = "SELECT id_sucursal, nombre_sucursal 
                 FROM sucursales 
                 WHERE id_sucursal <> $1 
                 ORDER BY nombre_sucursal ASC";
$res_suc_dest = pg_query_params($conn, $sql_suc_dest, array($_SESSION['sucursal']));
$sucursales_dest = [];
while($row = pg_fetch_assoc($res_suc_dest)){
    $sucursales_dest[] = $row;
}

// Guardar transferencia con la función SQL
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_producto = intval($_POST['producto']);
    $id_origen   = $_SESSION['sucursal'];
    $id_destino  = intval($_POST['destino']);
    $id_lote     = intval($_POST['lote']);
    $cantidad    = intval($_POST['cantidad']);
    $empleado    = $_SESSION['usuario'];

    $sql = "SELECT fn_realizar_transferencia($1,$2,$3,$4,$5)";
    $res = pg_query_params($conn, $sql, array($id_producto, $id_origen, $id_destino, $cantidad, $empleado));

    if ($res) {
        $resultado = pg_fetch_result($res, 0, 0);
        if ($resultado == 1) {
            $mensaje = "✅ Transferencia registrada correctamente";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "❌ No hay stock suficiente en la sucursal de origen.";
            $tipo_mensaje = "error";
        }
    } else {
        $mensaje = "❌ Error al ejecutar la transferencia.";
        $tipo_mensaje = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transferencias - Sistema Farmacia</title>
    <link rel="stylesheet" href="css/transferencias-elegant.css">
</head>
<body>
<div class="container">
    <h2>Transferencias</h2>
    
    <form method="POST">
        <div class="form-group">
            <label>Producto:</label>
            <select name="producto" id="producto" required>
                <option value="">-- Seleccione Producto --</option>
                <?php foreach($productos as $p): ?>
                    <option value="<?php echo $p['id_producto']; ?>">
                        <?php echo htmlspecialchars($p['nombre_producto']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Origen (mi sucursal):</label>
            <input type="text" value="<?php echo htmlspecialchars($empleado_sucursal['nombre_sucursal']); ?>" disabled>
            <input type="hidden" name="origen" value="<?php echo $empleado_sucursal['id_sucursal']; ?>">
        </div>

        <div class="form-group">
            <label>Destino:</label>
            <select name="destino" required>
                <option value="">-- Seleccione Sucursal Destino --</option>
                <?php foreach($sucursales_dest as $s): ?>
                    <option value="<?php echo $s['id_sucursal']; ?>">
                        <?php echo htmlspecialchars($s['nombre_sucursal']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Cantidad:</label>
            <input type="number" name="cantidad" id="cantidad" min="1" required>
        </div>

        <button type="submit" class="btn-submit">Guardar Transferencia</button>
    </form>

    <?php if(isset($mensaje)): ?>
        <div class="message <?php echo $tipo_mensaje; ?>">
            <?php echo $mensaje; ?>
        </div>
    <?php endif; ?>

    <hr>
    <h3>📋 Mis Transferencias Realizadas</h3>
    <?php
    $sql_hist = "SELECT t.numero_transferencia, 
                        p.nombre_producto, 
                        s1.nombre_sucursal AS origen, 
                        s2.nombre_sucursal AS destino,
                        d.cantidad_solicitada,
                        t.fecha_solicitud AS fecha
                 FROM transferencias t
                 JOIN detalle_transferencias d ON t.id_transferencia = d.id_transferencia
                 JOIN productos p ON d.id_producto = p.id_producto
                 JOIN sucursales s1 ON t.id_sucursal_origen = s1.id_sucursal
                 JOIN sucursales s2 ON t.id_sucursal_destino = s2.id_sucursal
                 WHERE t.id_empleado_autoriza = $1
                 ORDER BY fecha DESC
                 LIMIT 20";
    $res_hist = pg_query_params($conn, $sql_hist, array($_SESSION['usuario']));

    if ($res_hist && pg_num_rows($res_hist) > 0) {
        echo "<table border='1' cellpadding='5'>
                <tr>
                    <th>Número</th>
                    <th>Producto</th>
                    <th>Origen</th>
                    <th>Destino</th>
                    <th>Cantidad</th>
                    <th>Fecha</th>
                </tr>";
        while ($row = pg_fetch_assoc($res_hist)) {
            echo "<tr>
                    <td>{$row['numero_transferencia']}</td>
                    <td>{$row['nombre_producto']}</td>
                    <td>{$row['origen']}</td>
                    <td>{$row['destino']}</td>
                    <td>{$row['cantidad_solicitada']}</td>
                    <td>{$row['fecha']}</td>
                  </tr>";
        }
        echo "</table>";
    } else {
        echo "<p>❌ No has realizado transferencias aún.</p>";
    }
    ?>
</div>
</body>
</html>
