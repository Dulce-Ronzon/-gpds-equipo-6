control_asistencia
<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

$conexion = new mysqli("localhost", "root", "", "taqueria_novillero");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$mensaje = "";
$tipo_mensaje = "";

// Obtener el ID del usuario en sesión actual
$usuario_actual = $_SESSION['usuario'];
$res_user = $conexion->query("SELECT id FROM usuarios WHERE usuario = '$usuario_actual'");
$datos_user = $res_user->fetch_assoc();
$id_usuario_actual = $datos_user['id'];

// PROCESAR MARCAJE DE ENTRADA / SALIDA
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion'])) {
    $accion = $_POST['accion']; // 'ENTRADA' o 'SALIDA'
    
    // Verificar el último registro de este usuario para aplicar las reglas de negocio
    $sql_ultimo = "SELECT tipo FROM asistencia WHERE id_usuario = $id_usuario_actual ORDER BY fecha_hora DESC LIMIT 1";
    $res_ultimo = $conexion->query($sql_ultimo);
    $ultimo_registro = ($res_ultimo && $res_ultimo->num_rows > 0) ? $res_ultimo->fetch_assoc()['tipo'] : null;

    if ($accion === 'ENTRADA') {
        if ($ultimo_registro === 'ENTRADA') {
            $mensaje = "Error: Ya tienes una entrada activa. Debes registrar tu salida antes de marcar otra entrada.";
            $tipo_mensaje = "error";
        } else {
            $conexion->query("INSERT INTO asistencia (id_usuario, tipo, fecha_hora) VALUES ($id_usuario_actual, 'ENTRADA', NOW())");
            $mensaje = "✅ ¡Entrada registrada con éxito a las " . date('H:i:s') . "!";
            $tipo_mensaje = "exito";
        }
    } elseif ($accion === 'SALIDA') {
        if ($ultimo_registro !== 'ENTRADA') {
            $mensaje = "Error: No puedes registrar una salida sin haber registrado previamente una entrada.";
            $tipo_mensaje = "error";
        } else {
            $conexion->query("INSERT INTO asistencia (id_usuario, tipo, fecha_hora) VALUES ($id_usuario_actual, 'SALIDA', NOW())");
            $mensaje = "👋 ¡Salida registrada con éxito. Buen descanso!";
            $tipo_mensaje = "exito";
        }
    }
}

// Consultar el historial reciente de asistencias para visualización
$sql_historial = "SELECT a.id, u.usuario, a.tipo, a.fecha_hora 
                  FROM asistencia a 
                  INNER JOIN usuarios u ON a.id_usuario = u.id 
                  ORDER BY a.fecha_hora DESC LIMIT 15";
$resultado_historial = $conexion->query($sql_historial);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control de Asistencia - Taquería Novillero</title>
    <style>
        body { background-color: #f4ece1; font-family: 'Courier New', Courier, monospace; color: #2c1d11; margin: 0; padding: 20px; display: flex; flex-direction: column; align-items: center; }
        .container { width: 100%; max-width: 800px; background-color: #ffffff; padding: 30px; border-radius: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); border: 2px solid #3d2514; }
        h2 { color: #3d2514; text-transform: uppercase; text-align: center; border-bottom: 3px double #d32f2f; padding-bottom: 10px; margin-top: 0; }
        .sub-titulo { text-align: center; font-size: 13px; font-style: italic; color: #b71c1c; font-weight: bold; margin-bottom: 25px; text-transform: uppercase; }
        
        .panel-marcaje { background-color: #fcf8f2; border: 1px dashed #3d2514; padding: 25px; border-radius: 15px; text-align: center; margin-bottom: 25px; }
        .btn-accion { padding: 15px 30px; font-size: 16px; font-weight: bold; font-family: inherit; border: none; border-radius: 12px; cursor: pointer; text-transform: uppercase; color: white; box-shadow: 0 4px 0 rgba(0,0,0,0.2); transition: transform 0.1s; margin: 0 10px; }
        .btn-entrada { background-color: #4caf50; box-shadow: 0 4px 0 #2e7d32; }
        .btn-salida { background-color: #d32f2f; box-shadow: 0 4px 0 #991b1b; }
        .btn-accion:active { transform: translateY(4px); box-shadow: none; }

        .alerta { padding: 12px; border-radius: 10px; font-weight: bold; margin-bottom: 20px; text-align: center; font-size: 14px; }
        .exito { background-color: #edf7ed; color: #1e4620; border-left: 6px solid #4caf50; }
        .error { background-color: #fdf2f2; color: #b71c1c; border-left: 6px solid #d32f2f; }

        table { width: 100%; border-collapse: separate; border-spacing: 0; border: 1px solid #3d2514; border-radius: 15px; overflow: hidden; margin-top: 15px; }
        th { background-color: #2c1d11; color: #ffffff; padding: 12px; font-size: 12px; text-transform: uppercase; }
        td { padding: 10px; background-color: #ffffff; border-bottom: 1px solid #f4ece1; font-size: 13px; text-align: center; }
        tr:last-child td { border-bottom: none; }
        
        .badge-entrada { background-color: #edf7ed; color: #2e7d32; padding: 4px 8px; border-radius: 6px; font-weight: bold; font-size: 11px; border: 1px solid #a5d6a7; }
        .badge-salida { background-color: #ffebee; color: #c62828; padding: 4px 8px; border-radius: 6px; font-weight: bold; font-size: 11px; border: 1px solid #ef9a9a; }

        .btn-volver { display: block; width: 220px; margin: 25px auto 0 auto; padding: 12px; background-color: #2c1d11; color: #ffffff; text-decoration: none; text-align: center; font-weight: bold; text-transform: uppercase; font-size: 13px; border-radius: 12px; box-shadow: 0 4px 0 #1c1007; }
        .btn-volver:hover { background-color: #d32f2f; }
    </style>
</head>
<body>

<div class="container">
    <h2>Control de Asistencia del Personal</h2>
    <p class="sub-titulo">HU01 (ATN-16): Registro de entrada y salida</p>

    <?php if (!empty($mensaje)): ?>
        <div class="alerta <?php echo $tipo_mensaje; ?>"><?php echo $mensaje; ?></div>
    <?php endif; ?>

    <div class="panel-marcaje">
        <p style="margin-top: 0; font-weight: bold; font-size: 15px; color: #3d2514;">
            Usuario en turno: <span style="color: #d32f2f;"><?php echo htmlspecialchars($usuario_actual); ?></span>
        </p>
        <form method="POST" style="display: inline-block;">
            <button type="submit" name="accion" value="ENTRADA" class="btn-accion btn-entrada">📥 Marcar Entrada</button>
            <button type="submit" name="accion" value="SALIDA" class="btn-accion btn-salida">📤 Marcar Salida</button>
        </form>
    </div>

    <h3 style="color: #3d2514; text-transform: uppercase; font-size: 14px; border-left: 4px solid #d32f2f; padding-left: 8px;">
        Historial Reciente de Asistencias
    </h3>
    <table>
        <thead>
            <tr>
                <th>ID Registro</th>
                <th>Colaborador</th>
                <th>Tipo de Movimiento</th>
                <th>Fecha y Hora</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($resultado_historial && $resultado_historial->num_rows > 0): ?>
                <?php while ($row = $resultado_historial->fetch_assoc()): ?>
                    <tr>
                        <td><strong>#<?php echo $row['id']; ?></strong></td>
                        <td style="font-weight: bold; text-align: left; padding-left: 20px;"><?php echo htmlspecialchars($row['usuario']); ?></td>
                        <td>
                            <span class="<?php echo ($row['tipo'] === 'ENTRADA') ? 'badge-entrada' : 'badge-salida'; ?>">
                                <?php echo ($row['tipo'] === 'ENTRADA') ? '📥 ENTRADA' : '📤 SALIDA'; ?>
                            </span>
                        </td>
                        <td><?php echo $row['fecha_hora']; ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" style="font-style: italic; color: #777; padding: 20px;">No hay registros de asistencia guardados todavía.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <a href="panel_trabajador.php" class="btn-volver">← Volver al Panel</a>
</div>

</body>
</html>
<?php $conexion->close(); ?>