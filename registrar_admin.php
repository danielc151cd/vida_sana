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

// Guardar transferencia
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_producto = intval($_POST['producto']);
    $id_origen   = intval($_POST['origen']);
    $id_destino  = intval($_POST['destino']);
    $id_lote     = intval($_POST['lote']);
    $cantidad    = intval($_POST['cantidad']);
    $empleado    = $_SESSION['usuario'];

    // Crear transferencia
    $numero_transferencia = "TR-" . rand(1000,9999);
    $sql_transf = "INSERT INTO transferencias(numero_transferencia, id_sucursal_origen, id_sucursal_destino, id_empleado_autoriza, motivo)
                   VALUES ($1, $2, $3, $4, 'WEB Transferencia')
                   RETURNING id_transferencia";
    $res_transf = pg_query_params($conn, $sql_transf, array($numero_transferencia, $id_origen, $id_destino, $empleado));
    $id_transferencia = pg_fetch_result($res_transf, 0, 0);

    // Insertar detalle con lote elegido
    $sql_det = "INSERT INTO detalle_transferencias(id_transferencia, id_producto, id_lote, cantidad_solicitada)
                VALUES ($1, $2, $3, $4)";
    pg_query_params($conn, $sql_det, array($id_transferencia, $id_producto, $id_lote, $cantidad));

    $mensaje = "✅ Transferencia registrada correctamente (N° $numero_transferencia)";
}

include("includes/header.php");
?>

<h2>Transferencias</h2>
<form method="POST">
    <label>Producto:</label>
    <select name="producto" id="producto" required>
        <option value="">-- Seleccione Producto --</option>
        <?php while($p = pg_fetch_assoc($productos)): ?>
            <option value="<?php echo $p['id_producto']; ?>">
                <?php echo htmlspecialchars($p['nombre_producto']); ?>
            </option>
        <?php endwhile; ?>
    </select>
    <br><br>

    <label>Origen:</label>
    <select name="origen" id="origen" required>
        <option value="">-- Seleccione Sucursal Origen --</option>
        <?php while($s = pg_fetch_assoc($sucursales)): ?>
            <option value="<?php echo $s['id_sucursal']; ?>">
                <?php echo htmlspecialchars($s['nombre_sucursal']); ?>
            </option>
        <?php endwhile; ?>
    </select>
    <br><br>

    <label>Destino:</label>
    <select name="destino" required>
        <option value="">-- Seleccione Sucursal Destino --</option>
        <?php
        pg_result_seek($sucursales, 0); // reiniciar cursor
        while($s = pg_fetch_assoc($sucursales)): ?>
            <option value="<?php echo $s['id_sucursal']; ?>">
                <?php echo htmlspecialchars($s['nombre_sucursal']); ?>
            </option>
        <?php endwhile; ?>
    </select>
    <br><br>

    <!-- Lotes dinámicos -->
    <label>Lote disponible:</label>
    <select name="lote" id="lote" required>
        <option value="">-- Seleccione primero producto y origen --</option>
    </select>
    <br><br>

    <label>Cantidad:</label>
    <input type="number" name="cantidad" min="1" required><br><br>

    <button type="submit">Guardar</button>
</form>

<?php if(isset($mensaje)) echo "<p style='color:green'>$mensaje</p>"; ?>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const producto = document.getElementById("producto");
    const origen   = document.getElementById("origen");
    const loteSel  = document.getElementById("lote");

    function cargarLotes() {
        if(producto.value && origen.value) {
            fetch("get_lotes.php?producto=" + producto.value + "&origen=" + origen.value)
                .then(res => res.json())
                .then(data => {
                    loteSel.innerHTML = "";
                    if(data.length > 0) {
                        data.forEach(lote => {
                            let opt = document.createElement("option");
                            opt.value = lote.id_lote;
                            opt.textContent = "Lote " + lote.numero_lote + " | Vence: " + lote.fecha_vencimiento + " | Stock: " + lote.stock;
                            loteSel.appendChild(opt);
                        });
                    } else {
                        let opt = document.createElement("option");
                        opt.textContent = "❌ No hay lotes disponibles";
                        opt.value = "";
                        loteSel.appendChild(opt);
                    }
                });
        }
    }

    producto.addEventListener("change", cargarLotes);
    origen.addEventListener("change", cargarLotes);
});
</script>

<?php include("includes/footer.php"); ?>
