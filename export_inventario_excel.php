<?php
include("config/conexion.php");

if (!isset($_GET['producto'])) {
    die("❌ No se especificó producto.");
}

$id_producto = intval($_GET['producto']);

// Obtener datos de inventario
$sql = "SELECT s.nombre_sucursal, l.numero_lote, l.fecha_vencimiento, i.cantidad_disponible AS stock
        FROM inventario i
        JOIN lotes_productos l ON i.id_lote = l.id_lote
        JOIN sucursales s ON i.id_sucursal = s.id_sucursal
        WHERE i.id_producto=$1
        ORDER BY s.nombre_sucursal, l.fecha_vencimiento ASC";
$res = pg_query_params($conn, $sql, array($id_producto));

// Configuración de cabeceras para Excel
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=inventario_producto_$id_producto.xls");
header("Pragma: no-cache");
header("Expires: 0");

// Imprimir cabecera de tabla
echo "Sucursal\tLote\tFecha Vencimiento\tStock\n";

// Imprimir filas
if ($res) {
    while ($row = pg_fetch_assoc($res)) {
        echo $row['nombre_sucursal'] . "\t" .
             $row['numero_lote'] . "\t" .
             $row['fecha_vencimiento'] . "\t" .
             $row['stock'] . "\n";
    }
} else {
    echo "❌ Error al consultar inventario\n";
}
?>
