<?php
session_start();
include("config/conexion.php");

if(!isset($_SESSION['usuario'])){
    header("Location: login.php");
    exit;
}

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=mis_transferencias.xls");
header("Pragma: no-cache");
header("Expires: 0");

$sql = "SELECT t.numero_transferencia, 
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
        ORDER BY fecha DESC";
$res = pg_query_params($conn, $sql, array($_SESSION['usuario']));

echo "Número\tProducto\tLote\tOrigen\tDestino\tCantidad\tFecha\n";
while ($row = pg_fetch_assoc($res)) {
    echo "{$row['numero_transferencia']}\t{$row['nombre_producto']}\t{$row['numero_lote']}\t{$row['origen']}\t{$row['destino']}\t{$row['cantidad_solicitada']}\t{$row['fecha']}\n";
}
?>
