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

// Guardar transferencia
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_producto = intval($_POST['producto']);
    $id_origen   = $_SESSION['sucursal'];
    $id_destino  = intval($_POST['destino']);
    $id_lote     = intval($_POST['lote']);
    $cantidad    = intval($_POST['cantidad']);
    $empleado    = $_SESSION['usuario'];

    $numero_transferencia = "TR-" . rand(1000,9999);

    // Insertar transferencia
    $sql_transf = "INSERT INTO transferencias(numero_transferencia, id_sucursal_origen, id_sucursal_destino, id_empleado_autoriza, motivo)
                   VALUES ($1, $2, $3, $4, 'WEB Transferencia')
                   RETURNING id_transferencia";
    $res_transf = pg_query_params($conn, $sql_transf, array($numero_transferencia, $id_origen, $id_destino, $empleado));
    $id_transferencia = pg_fetch_result($res_transf, 0, 0);

    // Insertar detalle
    $sql_det = "INSERT INTO detalle_transferencias(id_transferencia, id_producto, id_lote, cantidad_solicitada)
                VALUES ($1, $2, $3, $4)";
    pg_query_params($conn, $sql_det, array($id_transferencia, $id_producto, $id_lote, $cantidad));

    // Actualizar inventario origen
    $sql_origen = "UPDATE inventario
                   SET cantidad_disponible = cantidad_disponible - $1
                   WHERE id_sucursal = $2 AND id_producto = $3 AND id_lote = $4";
    pg_query_params($conn, $sql_origen, array($cantidad, $id_origen, $id_producto, $id_lote));

    // Actualizar inventario destino
    $sql_check_dest = "SELECT id_inventario FROM inventario
                       WHERE id_sucursal = $1 AND id_producto = $2 AND id_lote = $3";
    $res_check = pg_query_params($conn, $sql_check_dest, array($id_destino, $id_producto, $id_lote));

    if (pg_num_rows($res_check) > 0) {
        $sql_dest = "UPDATE inventario
                     SET cantidad_disponible = cantidad_disponible + $1
                     WHERE id_sucursal = $2 AND id_producto = $3 AND id_lote = $4";
        pg_query_params($conn, $sql_dest, array($cantidad, $id_destino, $id_producto, $id_lote));
    } else {
        $sql_dest = "INSERT INTO inventario(id_sucursal, id_producto, id_lote, cantidad_disponible, cantidad_minima, cantidad_maxima, ubicacion_fisica)
                     VALUES ($1, $2, $3, $4, 5, 500, 'AUTO-TR')";
        pg_query_params($conn, $sql_dest, array($id_destino, $id_producto, $id_lote, $cantidad));
    }

    $mensaje = "✅ Transferencia registrada correctamente (N° $numero_transferencia)";
    $tipo_mensaje = "success";
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
            <label>Lote disponible:</label>
            <select name="lote" id="lote" required>
                <option value="">-- Seleccione producto --</option>
            </select>
        </div>

        <div class="form-group">
            <label>Cantidad:</label>
            <input type="number" name="cantidad" id="cantidad" min="1" required>
            <p id="stock_info" style="font-weight:bold; color:blue;">Stock disponible: -</p>
        </div>

        <button type="submit" class="btn-submit">Guardar Transferencia</button>
    </form>

    <?php if(isset($mensaje)): ?>
        <div class="message <?php echo $tipo_mensaje; ?>">
            <?php echo $mensaje; ?>
        </div>
    <?php endif; ?>

    <hr>
    <h3>📊 Inventario general del producto seleccionado</h3>
    <div id="tabla_inventario">
        <p>Seleccione un producto para ver su inventario.</p>
    </div>

    <hr>
    <h3>📋 Mis Transferencias Realizadas</h3>
    <?php
    $sql_hist = "SELECT t.numero_transferencia, 
                        p.nombre_producto, 
                        l.numero_lote,
                        s1.nombre_sucursal AS origen, 
                        s2.nombre_sucursal AS destino,
                        d.cantidad_solicitada,
                        t.fecha_solicitud AS fecha
                 FROM transferencias t
                 JOIN detalle_transferencias d ON t.id_transferencia = d.id_transferencia
                 JOIN productos p ON d.id_producto = p.id_producto
                 JOIN lotes_productos l ON d.id_lote = l.id_lote
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
                    <th>Lote</th>
                    <th>Origen</th>
                    <th>Destino</th>
                    <th>Cantidad</th>
                    <th>Fecha</th>
                </tr>";
        while ($row = pg_fetch_assoc($res_hist)) {
            echo "<tr>
                    <td>{$row['numero_transferencia']}</td>
                    <td>{$row['nombre_producto']}</td>
                    <td>{$row['numero_lote']}</td>
                    <td>{$row['origen']}</td>
                    <td>{$row['destino']}</td>
                    <td>{$row['cantidad_solicitada']}</td>
                    <td>{$row['fecha']}</td>
                  </tr>";
        }
        echo "</table>";
        echo "<br><button onclick=\"window.location.href='export_transferencias_empleado.php'\">⬇ Exportar a Excel</button>";
    } else {
        echo "<p>❌ No has realizado transferencias aún.</p>";
    }
    ?>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const producto = document.getElementById("producto");
    const origen   = document.querySelector("input[name='origen']").value;
    const loteSel  = document.getElementById("lote");
    const cantidad = document.getElementById("cantidad");
    const stockInfo = document.getElementById("stock_info");
    const tablaInv = document.getElementById("tabla_inventario");

    function cargarLotes() {
        if(producto.value && origen) {
            fetch("get_lotes.php?producto=" + producto.value + "&origen=" + origen)
                .then(res => res.json())
                .then(data => {
                    loteSel.innerHTML = "";
                    if(data.length > 0) {
                        data.forEach(lote => {
                            let opt = document.createElement("option");
                            opt.value = lote.id_lote;
                            opt.textContent = "Lote " + lote.numero_lote +
                                              " | Vence: " + lote.fecha_vencimiento +
                                              " | Stock: " + lote.stock;
                            opt.dataset.stock = lote.stock;
                            opt.style.color = (parseInt(lote.stock) <= 5) ? "red" : "green";
                            loteSel.appendChild(opt);
                        });
                        loteSel.selectedIndex = 0;
                        actualizarCantidad();
                    } else {
                        let opt = document.createElement("option");
                        opt.textContent = "❌ No hay lotes disponibles";
                        opt.value = "";
                        loteSel.appendChild(opt);
                        cantidad.value = "";
                        cantidad.max = "";
                        stockInfo.textContent = "Stock disponible: 0";
                        stockInfo.style.color = "red";
                    }
                });
        }
    }

    function actualizarCantidad() {
        let stock = loteSel.options[loteSel.selectedIndex]?.dataset.stock;
        if (stock) {
            cantidad.max = stock;
            cantidad.value = 1;
            stockInfo.textContent = "Stock disponible: " + stock;
            stockInfo.style.color = (parseInt(stock) <= 5) ? "red" : "blue";
            cantidad.disabled = (parseInt(stock) === 0);
        } else {
            cantidad.max = "";
            cantidad.value = "";
            stockInfo.textContent = "Stock disponible: -";
            stockInfo.style.color = "blue";
        }
    }

    function cargarInventarioGeneral() {
        if(producto.value) {
            fetch("get_inventario_producto.php?producto=" + producto.value)
                .then(res => res.json())
                .then(data => {
                    if(data.length > 0) {
                        let tabla = "<table border='1' cellpadding='5'><tr><th>Sucursal</th><th>Lote</th><th>Vencimiento</th><th>Stock</th></tr>";
                        data.forEach(item => {
                            let color = (parseInt(item.stock) <= 5) ? "red" : "black";
                            tabla += `<tr>
                                <td>${item.nombre_sucursal}</td>
                                <td>${item.numero_lote}</td>
                                <td>${item.fecha_vencimiento}</td>
                                <td style="color:${color}; font-weight:bold;">${item.stock}</td>
                            </tr>`;
                        });
                        tabla += "</table>";
                        tablaInv.innerHTML = tabla;
                    } else {
                        tablaInv.innerHTML = "<p>❌ No hay inventario registrado para este producto.</p>";
                    }
                });
        }
    }

    producto.addEventListener("change", () => {
        cargarLotes();
        cargarInventarioGeneral();
    });
    loteSel.addEventListener("change", actualizarCantidad);
});
</script>
</body>
</html>
