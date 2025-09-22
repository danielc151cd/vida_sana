<?php
session_start();
include("config/conexion.php");

// Verificar sesión admin
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

// Filtros
$filtro_sucursal = $_GET['sucursal'] ?? '';
$filtro_producto = $_GET['producto'] ?? '';

// Procesar CRUD
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['agregar'])) {
        $id_sucursal = intval($_POST['id_sucursal']);
        $id_producto = intval($_POST['id_producto']);
        $cantidad_disponible = intval($_POST['cantidad_disponible']);
        $cantidad_minima = intval($_POST['cantidad_minima']);
        $cantidad_maxima = intval($_POST['cantidad_maxima']);
        $ubicacion = trim($_POST['ubicacion']);

        // 1. Crear lote automáticamente
        $sql_lote = "INSERT INTO lotes_productos 
            (numero_lote, id_producto, fecha_vencimiento, fecha_fabricacion, 
             cantidad_inicial, cantidad_actual, precio_compra, estado_lote)
            VALUES ($1, $2, $3, $4, $5, $5, $6, 'DISPONIBLE')
            RETURNING id_lote";

        $res_lote = pg_query_params($conn, $sql_lote, array(
            "LOT-" . uniqid(),
            $id_producto,
            date("Y-m-d", strtotime("+2 years")), // vencimiento 2 años
            date("Y-m-d"),                        // fabricación hoy
            $cantidad_disponible,
            10.00                                // precio ficticio
        ));

        if ($res_lote && pg_num_rows($res_lote) > 0) {
            $id_lote = pg_fetch_result($res_lote, 0, 0);

            // 2. Insertar en inventario
            $sql = "INSERT INTO inventario 
                (id_sucursal, id_producto, id_lote, cantidad_disponible, cantidad_minima, cantidad_maxima, ubicacion_fisica)
                VALUES ($1, $2, $3, $4, $5, $6, $7)";
            pg_query_params($conn, $sql, array(
                $id_sucursal, $id_producto, $id_lote,
                $cantidad_disponible, $cantidad_minima, $cantidad_maxima, $ubicacion
            ));
        }
    }

    if (isset($_POST['editar'])) {
        $id_inventario = intval($_POST['id_inventario']);
        $cantidad_disponible = intval($_POST['cantidad_disponible']);
        $cantidad_minima = intval($_POST['cantidad_minima']);
        $cantidad_maxima = intval($_POST['cantidad_maxima']);
        $ubicacion = trim($_POST['ubicacion']);

        $sql = "UPDATE inventario
                SET cantidad_disponible=$1, cantidad_minima=$2, cantidad_maxima=$3, ubicacion_fisica=$4
                WHERE id_inventario=$5";
        pg_query_params($conn, $sql, array($cantidad_disponible, $cantidad_minima, $cantidad_maxima, $ubicacion, $id_inventario));
    }

    if (isset($_POST['eliminar'])) {
        $id_inventario = intval($_POST['id_inventario']);
        $sql = "DELETE FROM inventario WHERE id_inventario=$1";
        @pg_query_params($conn, $sql, array($id_inventario));
    }
}

// Obtener sucursales y productos para selects
$sucursales = pg_query($conn, "SELECT id_sucursal, nombre_sucursal FROM sucursales ORDER BY nombre_sucursal");
$productos = pg_query($conn, "SELECT id_producto, nombre_producto FROM productos ORDER BY nombre_producto");

// Consultar inventario con filtros
$sql = "SELECT i.id_inventario, s.nombre_sucursal, p.nombre_producto, 
               i.cantidad_disponible, i.cantidad_minima, i.cantidad_maxima, i.ubicacion_fisica
        FROM inventario i
        JOIN sucursales s ON i.id_sucursal = s.id_sucursal
        JOIN productos p ON i.id_producto = p.id_producto
        WHERE ($1 = '' OR s.nombre_sucursal ILIKE '%' || $1 || '%')
          AND ($2 = '' OR p.nombre_producto ILIKE '%' || $2 || '%')
        ORDER BY s.nombre_sucursal, p.nombre_producto";
$result = pg_query_params($conn, $sql, array($filtro_sucursal, $filtro_producto));

include("includes/header.php");
?>
<div class="container">
    <h2>Gestión de Inventario por Sucursal</h2>

    <!-- Filtros -->
    <form method="GET" class="filter-form">
        <div class="form-row">
            <div class="form-group">
                <label for="sucursal">Sucursal:</label>
                <input type="text" id="sucursal" name="sucursal" value="<?php echo htmlspecialchars($filtro_sucursal); ?>" placeholder="Buscar por sucursal...">
            </div>
            <div class="form-group">
                <label for="producto">Producto:</label>
                <input type="text" id="producto" name="producto" value="<?php echo htmlspecialchars($filtro_producto); ?>" placeholder="Buscar por producto...">
            </div>
            <div class="form-group">
                <button type="submit" class="btn-primary">🔍 Buscar</button>
            </div>
        </div>
    </form>

    <!-- Tabla Inventario -->
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Sucursal</th>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Mínima</th>
                    <th>Máxima</th>
                    <th>Ubicación</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = pg_fetch_assoc($result)) : ?>
                    <tr>
                        <form method="POST">
                            <td><?php echo htmlspecialchars($row['nombre_sucursal']); ?></td>
                            <td><?php echo htmlspecialchars($row['nombre_producto']); ?></td>
                            <td><input type="number" name="cantidad_disponible" value="<?php echo $row['cantidad_disponible']; ?>"></td>
                            <td><input type="number" name="cantidad_minima" value="<?php echo $row['cantidad_minima']; ?>"></td>
                            <td><input type="number" name="cantidad_maxima" value="<?php echo $row['cantidad_maxima']; ?>"></td>
                            <td><input type="text" name="ubicacion" value="<?php echo htmlspecialchars($row['ubicacion_fisica']); ?>"></td>
                            <td>
                                <input type="hidden" name="id_inventario" value="<?php echo $row['id_inventario']; ?>">
                                <button type="submit" name="editar" class="btn-primary">💾 Guardar</button>
                                <button type="submit" name="eliminar" class="btn-danger" onclick="return confirm('¿Seguro que deseas eliminar este registro?');">🗑️ Eliminar</button>
                            </td>
                        </form>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Agregar producto al inventario -->
    <div class="add-form">
        <h3>➕ Agregar Producto al Inventario</h3>
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label for="id_sucursal">Sucursal:</label>
                    <select name="id_sucursal" id="id_sucursal" required>
                        <option value="">Seleccionar sucursal...</option>
                        <?php
                        pg_result_seek($sucursales, 0);
                        while ($s = pg_fetch_assoc($sucursales)) : ?>
                            <option value="<?php echo $s['id_sucursal']; ?>"><?php echo htmlspecialchars($s['nombre_sucursal']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="id_producto">Producto:</label>
                    <select name="id_producto" id="id_producto" required>
                        <option value="">Seleccionar producto...</option>
                        <?php
                        pg_result_seek($productos, 0);
                        while ($p = pg_fetch_assoc($productos)) : ?>
                            <option value="<?php echo $p['id_producto']; ?>"><?php echo htmlspecialchars($p['nombre_producto']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="cantidad_disponible">Cantidad:</label>
                    <input type="number" name="cantidad_disponible" id="cantidad_disponible" required min="0" placeholder="0">
                </div>
                
                <div class="form-group">
                    <label for="cantidad_minima">Cantidad Mínima:</label>
                    <input type="number" name="cantidad_minima" id="cantidad_minima" required min="0" placeholder="0">
                </div>
                
                <div class="form-group">
                    <label for="cantidad_maxima">Cantidad Máxima:</label>
                    <input type="number" name="cantidad_maxima" id="cantidad_maxima" required min="0" placeholder="0">
                </div>
                
                <div class="form-group">
                    <label for="ubicacion">Ubicación:</label>
                    <input type="text" name="ubicacion" id="ubicacion" required placeholder="Ej: Pasillo A, Estante 3">
                </div>
            </div>

            <div class="form-row">
                <button type="submit" name="agregar" class="btn-primary">✅ Agregar al Inventario</button>
            </div>
        </form>
    </div>

    <!-- Botón regresar -->
    <a href="admin.php">
        <button class="btn-back">⬅️ Regresar al Panel Admin</button>
    </a>
</div>

<?php include("includes/footer.php"); ?>
