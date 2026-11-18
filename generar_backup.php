<?php
session_start();

// 1. Seguridad: Validar que el administrador haya iniciado sesión
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

// 2. Configuración de la base de datos (Entorno local XAMPP / MariaDB)
$host = "localhost";
$usuario = "root";
$password = "";
$base_datos = "taqueria_novillero";

$conn = new mysqli($host, $usuario, $password, $base_datos);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("Error de conexión a la base de datos: " . $conn->connect_error);
}

// 3. Encabezado del archivo SQL
$contenido_sql = "-- Respaldo de Base de Datos: `$base_datos`\n";
$contenido_sql .= "-- Generado por: " . $_SESSION['usuario'] . "\n";
$contenido_sql .= "-- Fecha de generación: " . date("Y-m-d H:i:s") . "\n\n";
$contenido_sql .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
$contenido_sql .= "START TRANSACTION;\n";
$contenido_sql .= "SET time_zone = \"+00:00\";\n";
$contenido_sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

// 4. Obtener todas las tablas de taqueria_novillero
$tablas = [];
$resultado = $conn->query("SHOW TABLES");
while ($fila = $resultado->fetch_row()) {
    $tablas[] = $fila[0];
}

// 5. Extraer estructura (CREATE TABLE) y datos (INSERT INTO) de cada tabla
foreach ($tablas as $tabla) {
    $contenido_sql .= "-- --------------------------------------------------------\n";
    $contenido_sql .= "-- Estructura de tabla para `$tabla`\n";
    $contenido_sql .= "-- --------------------------------------------------------\n\n";
    
    $contenido_sql .= "DROP TABLE IF EXISTS `$tabla`;\n";
    $crear_tabla = $conn->query("SHOW CREATE TABLE `$tabla`")->fetch_row();
    $contenido_sql .= $crear_tabla[1] . ";\n\n";

    // Obtener registros actualizados
    $datos = $conn->query("SELECT * FROM `$tabla`");
    $num_columnas = $datos->field_count;

    if ($datos->num_rows > 0) {
        $contenido_sql .= "-- Volcado de datos para la tabla `$tabla`\n\n";
        while ($fila = $datos->fetch_row()) {
            $contenido_sql .= "INSERT INTO `$tabla` VALUES(";
            for ($i = 0; $i < $num_columnas; $i++) {
                if (isset($fila[$i])) {
                    // Escapar caracteres especiales y saltos de línea
                    $valor = $conn->real_escape_string($fila[$i]);
                    $contenido_sql .= "'" . $valor . "'";
                } else {
                    $contenido_sql .= "NULL";
                }
                if ($i < ($num_columnas - 1)) {
                    $contenido_sql .= ", ";
                }
            }
            $contenido_sql .= ");\n";
        }
        $contenido_sql .= "\n";
    }
}

// 6. Extraer Disparadores (Triggers) como `bd_proteger_dueno`
$triggers = $conn->query("SHOW TRIGGERS");
if ($triggers && $triggers->num_rows > 0) {
    $contenido_sql .= "-- --------------------------------------------------------\n";
    $contenido_sql .= "-- Disparadores (Triggers)\n";
    $contenido_sql .= "-- --------------------------------------------------------\n\n";
    
    while ($trig = $triggers->fetch_assoc()) {
        $nombre_trigger = $trig['Trigger'];
        $contenido_sql .= "DROP TRIGGER IF EXISTS `$nombre_trigger`;\n";
        $contenido_sql .= "DELIMITER $$\n";
        $contenido_sql .= "CREATE TRIGGER `$nombre_trigger` {$trig['Timing']} {$trig['Event']} ON `{$trig['Table']}` FOR EACH ROW {$trig['Statement']}\n";
        $contenido_sql .= "$$\n";
        $contenido_sql .= "DELIMITER ;\n\n";
    }
}

$contenido_sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
$contenido_sql .= "COMMIT;\n";

$conn->close();

// 7. Forzar la descarga automática del archivo .sql
$nombre_archivo = "backup_taqueria_novillero_" . date("Y-m-d_H-i-s") . ".sql";

header('Content-Type: application/sql; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"');
header('Content-Length: ' . strlen($contenido_sql));
header('Pragma: no-cache');
header('Expires: 0');

echo $contenido_sql;
exit();
?>