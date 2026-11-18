<?php
session_start();
$servidor   = "localhost";
$usuario_db = "root";
$pass_db    = "";
$base_datos = "taqueria_novillero";

$conexion = new mysqli($servidor, $usuario_db, $pass_db, $base_datos);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$mensaje = "";
$tipo_mensaje = ""; 

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cantidades'])) {
    $cantidades = $_POST['cantidades']; 
    $observaciones_post = isset($_POST['observaciones']) ? $_POST['observaciones'] : [];
    $mesa = isset($_POST['mesa']) ? intval($_POST['mesa']) : 0; 
    
    $error_stock = false;
    $productos_a_pedir = [];
    $total_pedido = 0;

    foreach ($cantidades as $id_prod => $cant) {
        $cant = intval($cant);
        if ($cant > 0) {
            $res_prod = $conexion->query("SELECT nombre, precio, existencias FROM productos WHERE id = $id_prod");
            if ($res_prod && $res_prod->num_rows > 0) {
                $prod = $res_prod->fetch_assoc();
                
                if ($cant > $prod['existencias']) {
                    $mensaje = "Error: No hay existencias suficientes de '" . $prod['nombre'] . "'. Disponibles: " . $prod['existencias'];
                    $tipo_mensaje = "error";
                    $error_stock = true;
                    break; 
                }
                
                $total_pedido += ($prod['precio'] * $cant);
                $productos_a_pedir[] = [
                    'id' => $id_prod,
                    'cantidad' => $cant,
                    'observacion' => isset($observaciones_post[$id_prod]) ? trim($observaciones_post[$id_prod]) : ''
                ];
            }
        }
    }

    if (!$error_stock && !empty($productos_a_pedir)) {
        $conexion->query("INSERT INTO pedidos_activos (total, mesa, estado) VALUES ($total_pedido, $mesa, 'en cocina')");
        $id_nuevo_pedido = $conexion->insert_id; 

        foreach ($productos_a_pedir as $item) {
            $id_p = $item['id'];
            $cant_p = $item['cantidad'];
            $obs_p = $conexion->real_escape_string($item['observacion']);

            $conexion->query("INSERT INTO detalle_pedidos (id_pedido, id_producto, cantidad, observacion) VALUES ($id_nuevo_pedido, $id_p, $cant_p, '$obs_p')");
            $conexion->query("UPDATE productos SET existencias = existencias - $cant_p WHERE id = $id_p");
        }

        $mensaje = "¡Pedido #" . $id_nuevo_pedido . " registrado con éxito! Total: $" . number_format($total_pedido, 2);
        $tipo_mensaje = "exito";
    } elseif (!$error_stock && empty($productos_a_pedir)) {
        $mensaje = "Por favor, ingresa una cantidad en al menos un producto.";
        $tipo_mensaje = "error";
    }
}

$productos_menu = $conexion->query("SELECT id, nombre, precio FROM productos");
$lista_productos = [];
if ($productos_menu && $productos_menu->num_rows > 0) {
    while ($r = $productos_menu->fetch_assoc()) {
        $lista_productos[] = $r;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Orden - Taquería Novillero</title>
    <style>
        body { background-color: #f4ece1; font-family: 'Courier New', Courier, monospace; color: #2c1d11; margin: 0; padding: 20px; display: flex; flex-direction: column; align-items: center; }
        .container { width: 100%; max-width: 750px; background-color: #ffffff; padding: 25px; border-radius: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); border: 2px solid #3d2514; }
        h2 { color: #3d2514; text-transform: uppercase; letter-spacing: 1px; border-bottom: 3px double #d32f2f; padding-bottom: 10px; margin-top: 0; margin-bottom: 15px; }
        .mensaje-alerta { padding: 12px; border-radius: 12px; font-weight: bold; margin-bottom: 20px; font-size: 14px; }
        .mensaje-exito { background-color: #edf7ed; color: #1e4620; border-left: 6px solid #4caf50; }
        .mensaje-error { background-color: #fdf2f2; color: #b71c1c; border-left: 6px solid #d32f2f; }
        .leyenda-atajos { background-color: #fcf8f2; border: 1px solid #d7ccc8; border-left: 4px solid #3d2514; border-radius: 10px; padding: 10px 14px; margin-bottom: 18px; font-size: 12px; }
        .leyenda-titulo { font-weight: bold; text-transform: uppercase; color: #3d2514; margin-bottom: 6px; display: flex; justify-content: space-between; align-items: center; }
        .leyenda-grid { display: flex; flex-wrap: wrap; gap: 6px 12px; align-items: center; }
        .item-atajo { display: inline-flex; align-items: center; gap: 4px; color: #5c4033; }
        kbd { background-color: #2c1d11; color: #ffffff; padding: 2px 6px; border-radius: 4px; font-size: 11px; font-weight: bold; font-family: inherit; box-shadow: 0 2px 0 #d32f2f; }
        .seccion-mesa { background-color: #fcf8f2; padding: 15px; border-radius: 12px; border: 1px dashed #3d2514; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px; }
        .seccion-mesa label { font-weight: bold; text-transform: uppercase; font-size: 14px; }
        .total-vivo { font-size: 16px; font-weight: bold; color: #b71c1c; background-color: #ffffff; padding: 6px 12px; border-radius: 8px; border: 1px solid #3d2514; }
        table { width: 100%; border-collapse: separate; border-spacing: 0; margin-bottom: 20px; border-radius: 15px; overflow: hidden; border: 1px solid #3d2514; }
        th { background-color: #2c1d11; color: #ffffff; padding: 12px; text-transform: uppercase; font-size: 13px; letter-spacing: 1px; }
        td { padding: 12px; background-color: #ffffff; border-bottom: 1px solid #f4ece1; color: #2c1d11; font-size: 14px; transition: background-color 0.25s ease; }
        tr:last-child td { border-bottom: none; }
        tr.fila-activa td { background-color: #fff3cd !important; }
        input[type="number"] { padding: 6px 10px; border: 2px solid #3d2514; background-color: #ffffff; color: #2c1d11; font-family: inherit; font-size: 15px; border-radius: 8px; width: 65px; font-weight: bold; text-align: center; }
        .input-obs { width: 100%; padding: 5px 8px; margin-top: 6px; border: 1px dashed #3d2514; border-radius: 6px; font-family: inherit; font-size: 12px; background: #fcf8f2; box-sizing: border-box; }
        .btn-rojo { width: 100%; padding: 12px; background-color: #d32f2f; color: #ffffff; border: none; font-weight: bold; font-size: 16px; text-transform: uppercase; cursor: pointer; border-radius: 12px; box-shadow: 0 4px 0 #991b1b; letter-spacing: 1px; }
        .btn-rojo:hover { background-color: #b71c1c; }
        .enlace-volver { display: inline-block; margin-top: 25px; color: #3d2514; font-weight: bold; text-decoration: none; border-bottom: 2px dashed #3d2514; padding-bottom: 2px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Registrar Nuevo Pedido</h2>
        <?php if (!empty($mensaje)): ?>
            <div class="mensaje-alerta <?php echo ($tipo_mensaje == 'error') ? 'mensaje-error' : 'mensaje-exito'; ?>"><?php echo $mensaje; ?></div>
        <?php endif; ?>
        <?php if (!empty($lista_productos)): ?>
            <div class="leyenda-atajos">
                <div class="leyenda-titulo">
                    <span>⚡ Comandos Rápidos de Teclado</span>
                    <span style="font-weight: normal; font-size: 11px;">[Shift + Tecla] resta 1 | <kbd>Alt+M</kbd> Mesa | <kbd>F12</kbd> Guardar</span>
                </div>
                <div class="leyenda-grid">
                    <?php $i=1; foreach ($lista_productos as $p): if($i>10) break; ?>
                        <span class="item-atajo"><kbd>F<?php echo $i; ?></kbd> <?php echo htmlspecialchars($p['nombre']); ?></span>
                    <?php $i++; endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        <form id="form-pedido" action="hacer_pedido.php" method="POST">
            <div class="seccion-mesa">
                <div>
                    <label for="mesa">Asignar Número de Mesa:</label>
                    <input type="number" id="mesa" name="mesa" min="1" max="99" value="1" required style="width: 80px;">
                </div>
                <div class="total-vivo">Total Estimado: $<span id="total-estimado">0.00</span></div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th style="text-align: center; width: 60px;">Atajo</th>
                        <th style="text-align: left;">Producto y Notas / Observaciones</th>
                        <th style="text-align: right; width: 90px;">Precio</th>
                        <th style="text-align: center; width: 120px;">Cantidad</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($lista_productos)): $contador = 1; foreach ($lista_productos as $row): $tecla = ($contador <= 10) ? "F".$contador : ""; ?>
                        <tr id="fila-prod-<?php echo $row['id']; ?>">
                            <td style="text-align: center;"><?php echo $tecla ? "<kbd>$tecla</kbd>" : "—"; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($row['nombre']); ?></strong>
                                <input type="text" name="observaciones[<?php echo $row['id']; ?>]" class="input-obs" placeholder="Nota: sin cebolla, bien dorado...">
                            </td>
                            <td style="text-align: right; font-weight: bold;">$<?php echo number_format($row['precio'], 2); ?></td>
                            <td style="text-align: center;">
                                <input type="number" class="input-cantidad" data-tecla="<?php echo $tecla; ?>" data-precio="<?php echo $row['precio']; ?>" data-fila="fila-prod-<?php echo $row['id']; ?>" name="cantidades[<?php echo $row['id']; ?>]" min="0" value="0">
                            </td>
                        </tr>
                    <?php $contador++; endforeach; endif; ?>
                </tbody>
            </table>
            <button type="submit" class="btn-rojo">Guardar Pedido de la Mesa [F12]</button>
        </form>
        <div style="text-align: center;"><a href="panel_trabajador.php" class="enlace-volver">← Volver al Panel Operativo</a></div>
    </div>
    <script>
        function actualizarTotalVivo() {
            let total = 0;
            document.querySelectorAll('.input-cantidad').forEach(i => {
                let c = parseInt(i.value) || 0;
                let p = parseFloat(i.getAttribute('data-precio')) || 0;
                if(c > 0) total += c * p;
            });
            document.getElementById('total-estimado').textContent = total.toFixed(2);
        }
        document.querySelectorAll('.input-cantidad').forEach(i => i.addEventListener('input', actualizarTotalVivo));
        document.addEventListener('keydown', function(e) {
            if (e.altKey && (e.key === 'm' || e.key === 'M')) { e.preventDefault(); document.getElementById('mesa').focus(); return; }
            if (e.key === 'F12') { e.preventDefault(); document.getElementById('form-pedido').submit(); return; }
            let obj = document.querySelector(`.input-cantidad[data-tecla="${e.key}"]`);
            if (obj) {
                e.preventDefault();
                let val = parseInt(obj.value) || 0;
                obj.value = e.shiftKey ? Math.max(0, val - 1) : val + 1;
                actualizarTotalVivo();
                let f = document.getElementById(obj.getAttribute('data-fila'));
                if(f) { f.classList.remove('fila-activa'); void f.offsetWidth; f.classList.add('fila-activa'); setTimeout(() => f.classList.remove('fila-activa'), 300); }
            }
        });
    </script>
</body>
</html>
<?php $conexion->close(); ?>