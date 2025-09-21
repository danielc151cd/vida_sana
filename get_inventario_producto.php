<?php
include("config/conexion.php");

header('Content-Type: application/json');

if (!isset($_GET['producto'])) {
    echo json_encode([]);
    exit;
}

$id_producto = intval($_GET['producto']);

// Inventario general de ese producto en todas las sucursales
$sql = "SELECT s.nombre_sucursal, l.numero_lote, l.fecha_vencimiento, i.cantidad_disponible AS stock
        FROM inventario i
        JOIN lotes_productos l ON i.id_lote = l.id_lote
        JOIN sucursales s ON i.id_sucursal = s.id_sucursal
        WHERE i.id_producto = $1
        ORDER BY s.nombre_sucursal, l.fecha_vencimiento ASC";

$res = pg_query_params($conn, $sql, array($id_producto));

$inventario = [];
if ($res) {
    while ($row = pg_fetch_assoc($res)) {
        $inventario[] = [
            "nombre_sucursal" => $row["nombre_sucursal"],
            "numero_lote" => $row["numero_lote"],
            "fecha_vencimiento" => $row["fecha_vencimiento"],
            "stock" => $row["stock"]
        ];
    }
}

echo json_encode($inventario);
