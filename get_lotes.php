<?php
include("config/conexion.php");

header('Content-Type: application/json');

if (!isset($_GET['producto']) || !isset($_GET['origen'])) {
    echo json_encode([]);
    exit;
}

$id_producto = intval($_GET['producto']);
$id_origen   = intval($_GET['origen']);

// Buscar lotes disponibles en inventario de esa sucursal
$sql = "SELECT l.id_lote, l.numero_lote, l.fecha_vencimiento, i.cantidad_disponible AS stock
        FROM inventario i
        JOIN lotes_productos l ON i.id_lote = l.id_lote
        WHERE i.id_producto = $1 AND i.id_sucursal = $2
          AND i.cantidad_disponible > 0
        ORDER BY l.fecha_vencimiento ASC";

$res = pg_query_params($conn, $sql, array($id_producto, $id_origen));

$lotes = [];
if ($res) {
    while ($row = pg_fetch_assoc($res)) {
        $lotes[] = [
            "id_lote" => $row["id_lote"],
            "numero_lote" => $row["numero_lote"],
            "fecha_vencimiento" => $row["fecha_vencimiento"],
            "stock" => $row["stock"]
        ];
    }
}

echo json_encode($lotes);
