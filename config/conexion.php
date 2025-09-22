<?php
// config/conexion.php

$host = "turntable.proxy.rlwy.net";
$port = "13368";
$dbname = "railway";
$user = "postgres";
$password = "TshCxgYlhUPevmTNrlUkFTHsXuDMeoWV";

$conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");

if (!$conn) {
    die("❌ Error al conectar a la base de datos: " . pg_last_error());
}
?>