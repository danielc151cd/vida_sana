<?php
session_start();
include("config/conexion.php");

if(!isset($_SESSION['usuario'])){
    header("Location: login.php");
    exit;
}

// Traer sucursales y productos
$sucursales = pg_query($conn, "SELECT id_sucursal, nombre_sucursal FROM sucursales ORDER BY nombre_sucursal ASC");
$productos  = pg_query($conn, "SELECT id_producto, nombre_producto FROM productos ORDER BY nombre_producto ASC");

// Guardar transferencia con la función
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_producto = intval($_POST['producto']);
    $id_origen   = intval($_POST['origen']);
    $id_destino  = intval($_POST['destino']);
    $cantidad    = intval($_POST['cantidad']);
    $empleado    = $_SESSION['usuario']; // id_usuario logueado

    // 👉 Llamar a la función fn_realizar_transferencia
    $sql = "SELECT fn_realizar_transferencia($1, $2, $3, $4, $5)";
    $res = pg_query_params($conn, $sql, array($id_producto, $id_origen, $id_destino, $cantidad, $empleado));

    if ($res) {
        $resultado = pg_fetch_result($res, 0, 0);
        if ($resultado == 1) {
            $mensaje = "✅ Transferencia registrada correctamente.";
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

include("includes/header.php");
?>

<h2>Transferencias</h2>
<form method="POST">
    <label>Producto:</label>
    <select name="producto" required>
        <option value="">-- Seleccione Producto --</option>
        <?php while($p = pg_fetch_assoc($productos)): ?>
            <option value="<?php echo $p['id_producto']; ?>">
                <?php echo htmlspecialchars($p['nombre_producto']); ?>
            </option>
        <?php endwhile; ?>
    </select>
    <br><br>

    <label>Origen:</label>
    <select name="origen" required>
        <option value="">-- Seleccione Origen --</option>
        <?php while($s = pg_fetch_assoc($sucursales)): ?>
            <option value="<?php echo $s['id_sucursal']; ?>">
                <?php echo htmlspecialchars($s['nombre_sucursal']); ?>
            </option>
        <?php endwhile; ?>
    </select>
    <br><br>

    <label>Destino:</label>
    <select name="destino" required>
        <option value="">-- Seleccione Destino --</option>
        <?php
        pg_result_seek($sucursales, 0);
        while($s = pg_fetch_assoc($sucursales)): ?>
            <option value="<?php echo $s['id_sucursal']; ?>">
                <?php echo htmlspecialchars($s['nombre_sucursal']); ?>
            </option>
        <?php endwhile; ?>
    </select>
    <br><br>

    <label>Cantidad:</label>
    <input type="number" name="cantidad" min="1" required>
    <br><br>

    <button type="submit">Guardar Transferencia</button>
</form>

<?php if(isset($mensaje)): ?>
    <p style="color: <?php echo $tipo_mensaje=='success'?'green':'red'; ?>">
        <?php echo $mensaje; ?>
    </p>
<?php endif; ?>

<?php include("includes/footer.php"); ?>
