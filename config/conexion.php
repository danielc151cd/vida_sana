<?php
// config/conexion.php
$host = "localhost";
$port = "5432";
$dbname = "vida_sana_db";
$user = "postgres";   // tu usuario
$password = "root"; // tu clave

$conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");

if(!$conn){
    die("❌ Error al conectar a la base de datos: " . pg_last_error());
}
?>
