<?php
// config/conexion.php
// ✅ Conexión PostgreSQL en Railway usando variables de entorno

$host = getenv("PGHOST") ?: "postgres.railway.internal";
$port = getenv("PGPORT") ?: "5432";
$dbname = getenv("PGDATABASE") ?: "railway";
$user = getenv("PGUSER") ?: "postgres";
$password = getenv("PGPASSWORD") ?: "TshCxgYlhUPevmTNrlUkFTHsXuDMeoWV"; // ⚠️ cámbialo en Railway Variables

$conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");

if (!$conn) {
    die("❌ Error al conectar a la base de datos: " . pg_last_error());
}
?>
