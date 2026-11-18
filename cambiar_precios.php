<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

$servidor   = "localhost";
$usuario_db = "root";
$pass_db    = "";
$base_datos = "taqueria_novillero";

$conexion = new mysqli($servidor, $usuario_db, $pass_db, $base_datos);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$usuario_actual = $_SESSION['usuario'];

$stmt_rol = $conexion->prepare("SELECT rol FROM usuarios WHERE usuario = ?");
$stmt_rol->bind_param("s", $usuario_actual);
$stmt_rol->execute();
$res_rol = $stmt_rol->get_result();
$datos_rol = $res_rol->fetch_assoc();

if (!$datos_rol || $datos_rol['rol'] !== 'administrador') {
    header("Location: panel_trabajador.php");
    exit();
}

$mensaje = "";

$conexion->query("CREATE TABLE IF NOT EXISTS historial_precios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_producto INT NOT NULL,
    precio_anterior DECIMAL(10,2) NOT NULL,
    precio_nuevo DECIMAL(10,2) NOT NULL,
    usuario_modifico VARCHAR(50) NOT NULL,
    fecha_cambio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_producto) REFERENCES productos(id) ON DELETE CASCADE
)");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion_agregar'])) {
    $nombre_nuevo = $conexion->real_escape_string($_POST['nuevo_nombre']);
    $categoria_nueva = $conexion->real_escape_string($_POST['nuevo_categoria']);
    $precio_nuevo = floatval($_POST['nuevo_precio']);

    if (!empty($nombre_nuevo) && !empty($categoria_nueva) && $precio_nuevo > 0) {
        $sql_insert = "INSERT INTO productos (nombre, categoria, precio) VALUES ('$nombre_nuevo', '$categoria_nueva', $precio_nuevo)";
        if ($conexion->query($sql_insert)) {
            $mensaje = "¡Producto agregado con éxito al menú!";
        } else {
            $mensaje = "Error al agregar el producto: " . $conexion->error;
        }
    } else {
        $mensaje = "Por favor, rellene todos los campos correctamente.";
    }
}

if (isset($_GET['eliminar'])) {
    $id_eliminar = intval($_GET['eliminar']);
    $sql_delete = "DELETE FROM productos WHERE id = $id_eliminar";
    if ($conexion->query($sql_delete)) {
        $mensaje = "¡Producto eliminado correctamente del menú!";
    } else {
        $mensaje = "Error al eliminar el producto.";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['productos_edit'])) {
    $productos_edit = $_POST['productos_edit']; 
    $cambios_realizados = 0;

    foreach ($productos_edit as $id_prod => $datos) {
        $id_prod = intval($id_prod);
        $nombre_mod = $conexion->real_escape_string($datos['nombre']);
        $categoria_mod = $conexion->real_escape_string($datos['categoria']);
        $precio_mod = floatval($datos['precio']);
        
        if (!empty($nombre_mod) && !empty($categoria_mod) && $precio_mod > 0) {
            $res_ant = $conexion->query("SELECT precio FROM productos WHERE id = $id_prod");
            if ($res_ant && $res_ant->num_rows > 0) {
                $row_ant = $res_ant->fetch_assoc();
                $precio_anterior = floatval($row_ant['precio']);

                $sql_update = "UPDATE productos SET nombre = '$nombre_mod', categoria = '$categoria_mod', precio = $precio_mod WHERE id = $id_prod";
                if ($conexion->query($sql_update)) {
                    $cambios_realizados++;

                    if ($precio_anterior != $precio_mod) {
                        $stmt_log = $conexion->prepare("INSERT INTO historial_precios (id_producto, precio_anterior, precio_nuevo, usuario_modifico, fecha_cambio) VALUES (?, ?, ?, ?, NOW())");
                        $stmt_log->bind_param("idds", $id_prod, $precio_anterior, $precio_mod, $usuario_actual);
                        $stmt_log->execute();
                    }
                }
            }
        }
    }

    if ($cambios_realizados > 0) {
        $mensaje = "¡Menú, precios y bitácora de auditoría actualizados con éxito!";
    }
}

$sql_productos = "SELECT id, nombre, categoria, precio FROM productos ORDER BY categoria, nombre";
$resultado = $conexion->query($sql_productos);

$sql_auditoria = "SELECT h.id, p.nombre AS producto, h.precio_anterior, h.precio_nuevo, h.usuario_modifico, h.fecha_cambio 
                  FROM historial_precios h 
                  INNER JOIN productos p ON h.id_producto = p.id 
                  ORDER BY h.fecha_cambio DESC";
$resultado_auditoria = $conexion->query($sql_auditoria);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Menú y Auditoría - Taquería Novillero</title>
    <style>
        body { background-color: #f4ece1; font-family: 'Courier New', Courier, monospace; color: #2c1d11; margin: 0; padding: 20px; display: flex; flex-direction: column; align-items: center; }
        .container { width: 100%; max-width: 950px; background-color: #ffffff; padding: 25px; border-radius: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); border: 2px solid #d32f2f; }
        h2 { color: #3d2514; text-transform: uppercase; letter-spacing: 1px; border-bottom: 3px double #d32f2f; padding-bottom: 10px; margin-top: 0; margin-bottom: 5px; }
        .sub-titulo { margin-top: 5px; margin-bottom: 25px; font-size: 14px; font-style: italic; color: #b71c1c; font-weight: bold; }
        .mensaje-alerta { background-color: #edf7ed; color: #1e4620; padding: 12px; border-radius: 12px; font-weight: bold; margin-bottom: 25px; border-left: 6px solid #4caf50; font-size: 14px; }
        .form-agregar { background-color: #fcf8f2; border: 1px dashed #3d2514; padding: 15px; border-radius: 12px; margin-bottom: 25px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
        .form-agregar h3 { margin: 0; width: 100%; font-size: 14px; text-transform: uppercase; color: #3d2514; }
        .input-inline { font-family: inherit; padding: 6px; border: 1px solid #3d2514; border-radius: 8px; color: #2c1d11; font-size: 13px; }
        table { width: 100%; border-collapse: separate; border-spacing: 0; margin-bottom: 25px; border-radius: 15px; overflow: hidden; border: 1px solid #3d2514; }
        th { background-color: #2c1d11; color: #ffffff; padding: 12px; text-transform: uppercase; font-size: 12px; letter-spacing: 1px; }
        td { padding: 12px; background-color: #ffffff; border-bottom: 1px solid #f4ece1; color: #2c1d11; font-size: 14px; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        .input-tabla { font-family: inherit; padding: 5px; border: 1px solid #3d2514; border-radius: 6px; color: #2c1d11; font-weight: bold; background-color: #fcf8f2; font-size: 13px; box-sizing: border-box; width: 100%; }
        .input-precio { text-align: right; width: 80px; }
        .input-tabla:focus { background-color: #ffffff; outline: 2px solid #d32f2f; }
        .btn-eliminar { background-color: #b71c1c; color: white; border: none; padding: 5px 10px; border-radius: 6px; cursor: pointer; font-family: inherit; font-weight: bold; text-decoration: none; font-size: 12px; box-shadow: 0 2px 0 #7f1212; }
        .btn-eliminar:hover { background-color: #d32f2f; }
        .acciones-box { display: flex; justify-content: center; gap: 20px; margin-top: 25px; }
        .btn-guardar { padding: 12px 24px; font-weight: bold; font-family: inherit; background-color: #d32f2f; color: #ffffff; border: none; cursor: pointer; text-transform: uppercase; font-size: 13px; border-radius: 12px; transition: background 0.2s; box-shadow: 0 4px 0 #991b1b; }
        .btn-guardar:hover { background-color: #b71c1c; }
        .btn-volver { display: block; width: 220px; padding: 12px; background-color: #2c1d11; color: #ffffff; text-decoration: none; text-align: center; font-weight: bold; text-transform: uppercase; font-size: 13px; border-radius: 12px; transition: background 0.2s; box-shadow: 0 4px 0 #1c1007; }
        .btn-volver:hover { background-color: #3d2514; }
        .seccion-auditoria { margin-top: 40px; border-top: 3px double #d32f2f; padding-top: 20px; }
        .precio-viejo { color: #777; text-decoration: line-through; }
        .precio-nuevo { color: #2e7d32; font-weight: bold; }
    </style>
</head>
<body>

    <div class="container">
        <h2>Panel de Administración: Gestionar Menú y Precios</h2>
        <p class="sub-titulo">HU03 (ATN-3): Alta, baja, modificación y auditoría de cambios en la carta</p>

        <?php if (!empty($mensaje)): ?>
            <div class="mensaje-alerta">
                ⚠️ <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <form action="cambiar_precios.php" method="POST" class="form-agregar">
            <h3>+ AGREGAR NUEVO PRODUCTO AL MENÚ</h3>
            <input type="text" name="nuevo_nombre" placeholder="Nombre del platillo/bebida" class="input-inline" style="flex: 2;" required>
            
            <select name="nuevo_categoria" class="input-inline" style="flex: 1;" required>
                <option value="" disabled selected>Categoría...</option>
                <option value="taco">TACO</option>
                <option value="agua fresca">AGUA FRESCA</option>
                <option value="refresco">REFRESCO</option>
                <option value="postre">POSTRE</option>
            </select>

            <input type="number" name="nuevo_precio" placeholder="Precio ($)" step="0.01" min="0.01" class="input-inline" style="width: 100px;" required>
            
            <button type="submit" name="accion_agregar" class="btn-guardar" style="padding: 6px 15px; box-shadow: 0 2px 0 #991b1b; font-size: 11px;">Añadir</button>
        </form>

        <form action="cambiar_precios.php" method="POST">
            <table>
                <thead>
                    <tr>
                        <th style="text-align: center; width: 160px;">Categoría</th>
                        <th style="text-align: left;">Nombre del Producto</th>
                        <th style="text-align: center; width: 130px;">Precio ($)</th>
                        <th style="text-align: center; width: 100px;">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($resultado && $resultado->num_rows > 0): ?>
                        <?php while ($row = $resultado->fetch_assoc()): ?>
                            <tr>
                                <td style="text-align: center; font-weight: bold;">
                                    <?php echo htmlspecialchars($row['categoria']); ?>
                                    <input type="hidden" name="productos_edit[<?php echo $row['id']; ?>][categoria]" value="<?php echo htmlspecialchars($row['categoria']); ?>">
                                </td>
                                
                                <td>
                                    <input type="text" name="productos_edit[<?php echo $row['id']; ?>][nombre]" value="<?php echo htmlspecialchars($row['nombre']); ?>" class="input-tabla" required>
                                </td>
                                
                                <td style="text-align: center;">
                                    <span style="color: #3d2514; font-weight: bold; margin-right: 2px;">$</span>
                                    <input type="number" name="productos_edit[<?php echo $row['id']; ?>][precio]" step="0.01" min="0.01" value="<?php echo $row['precio']; ?>" class="input-tabla input-precio" required>
                                </td>
                                
                                <td style="text-align: center;">
                                    <a href="cambiar_precios.php?eliminar=<?php echo $row['id']; ?>" class="btn-eliminar" onclick="return confirm('¿Seguro que deseas eliminar este producto del menú?');">
                                        Eliminar
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; font-style: italic; padding: 20px;">No hay productos registrados en el menú.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="acciones-box">
                <a href="panel_admin.php" class="btn-volver">← Cancelar y Volver</a>
                <button type="submit" class="btn-guardar">Guardar Todos los Cambios</button>
            </div>
        </form>

        <div class="seccion-auditoria">
            <h3 style="color: #3d2514; text-transform: uppercase; font-size: 15px; border-left: 5px solid #d32f2f; padding-left: 10px; margin-bottom: 15px;">
                Bitácora de Auditoría de Precios
            </h3>
            <table>
                <thead>
                    <tr>
                        <th style="text-align: center; width: 60px;">ID</th>
                        <th style="text-align: left;">Producto Modificado</th>
                        <th style="text-align: center; width: 130px;">Precio Anterior</th>
                        <th style="text-align: center; width: 130px;">Nuevo Precio</th>
                        <th style="text-align: center; width: 150px;">Modificado Por</th>
                        <th style="text-align: center; width: 160px;">Fecha y Hora</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($resultado_auditoria && $resultado_auditoria->num_rows > 0): ?>
                        <?php while ($aud = $resultado_auditoria->fetch_assoc()): ?>
                            <tr>
                                <td style="text-align: center;"><strong>#<?php echo $aud['id']; ?></strong></td>
                                <td style="font-weight: bold;"><?php echo htmlspecialchars($aud['producto']); ?></td>
                                <td style="text-align: center;" class="precio-viejo">$<?php echo number_format($aud['precio_anterior'], 2); ?></td>
                                <td style="text-align: center;" class="precio-nuevo">$<?php echo number_format($aud['precio_nuevo'], 2); ?></td>
                                <td style="text-align: center; font-weight: bold; color: #b71c1c;"><?php echo htmlspecialchars($aud['usuario_modifico']); ?></td>
                                <td style="text-align: center; font-size: 12px;"><?php echo $aud['fecha_cambio']; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; font-style: italic; padding: 20px; color: #777;">Aún no hay registros en la bitácora de auditoría de precios.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>
<?php $conexion->close(); ?>