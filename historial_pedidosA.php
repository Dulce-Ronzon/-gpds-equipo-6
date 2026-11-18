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

// ACCIÓN: COBRAR PEDIDO (CON SOPORTE PARA PAGOS DIVIDIDOS / MIXTOS - HU12)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cobrar_pedido'])) {
    $id_pedido = intval($_POST['id_pedido_accion']); 
    $total = round(floatval($_POST['total_pedido']), 2);
    $monto_efectivo = isset($_POST['monto_efectivo']) ? round(floatval($_POST['monto_efectivo']), 2) : 0;
    $monto_digital = isset($_POST['monto_digital']) ? round(floatval($_POST['monto_digital']), 2) : 0;
    $suma_pagos = round($monto_efectivo + $monto_digital, 2);
    $usuario = $_SESSION['usuario'];

    // Criterio de aceptación HU12: Solo cerrar la cuenta cuando la suma cubra el total
    if ($suma_pagos < $total) {
        $faltante = number_format($total - $suma_pagos, 2);
        $mensaje = "❌ Error: La suma de los pagos ($$suma_pagos) no cubre el total de la orden ($$total). Faltan $$faltante.";
        $tipo_mensaje = "error";
    } else {
        $cambio = round($suma_pagos - $total, 2);
        $conexion->begin_transaction();
        try {
            // 1. EXTRAER PRODUCTOS ACTIVOS DE FORMA LIMPIA ANTES DE BORRARLOS
            $productos_texto = "";
            $sql_desglose = "SELECT dp.cantidad, prod.nombre 
                             FROM detalle_pedidos dp 
                             INNER JOIN productos prod ON dp.id_producto = prod.id 
                             WHERE dp.id_pedido = $id_pedido";
            $res_desglose = $conexion->query($sql_desglose);
            
            if ($res_desglose && $res_desglose->num_rows > 0) {
                $lineas = [];
                while ($item = $res_desglose->fetch_assoc()) {
                    $lineas[] = $item['cantidad'] . "x " . $item['nombre'];
                }
                $lineas[] = "[Pago -> Efectivo: $" . number_format($monto_efectivo, 2) . " | Digital: $" . number_format($monto_digital, 2) . "]";
                $productos_texto = implode("\n", $lineas);
            } else {
                $productos_texto = "Sin productos desglosados";
            }

            // 2. INSERTAR EN EL HISTORIAL INCLUYENDO LOS PRODUCTOS Y EL ESTADO 'COBRADO'
            $stmt = $conexion->prepare("INSERT INTO historial_ventas (id_pedido, total, cobrado_por, fecha_hora_cobro, estado, productos_vendidos) VALUES (?, ?, ?, NOW(), 'COBRADO', ?)");
            $stmt->bind_param("idss", $id_pedido, $total, $usuario, $productos_texto);
            $stmt->execute();

            // 3. LIMPIAR EL FLUJO ACTIVO
            $conexion->query("DELETE FROM detalle_pedidos WHERE id_pedido = $id_pedido");
            $conexion->query("DELETE FROM pedidos_activos WHERE id_pedido = $id_pedido");

            $conexion->commit();
            $extra_cambio = ($cambio > 0) ? " | Cambio entregado: $" . number_format($cambio, 2) : "";
            $mensaje = "💰 Pedido #$id_pedido cobrado con éxito (Efectivo: $" . number_format($monto_efectivo, 2) . " | Digital: $" . number_format($monto_digital, 2) . ")$extra_cambio.";
            $tipo_mensaje = "exito";
        } catch (Exception $e) {
            $conexion->rollback();
            $mensaje = "Error al procesar cobro: " . $e->getMessage();
            $tipo_mensaje = "error";
        }
    }
}

// ACCIÓN: CANCELAR PEDIDO
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cancelar_pedido'])) {
    $id_pedido = intval($_POST['id_pedido_accion']); 
    $total = floatval($_POST['total_pedido']);
    $usuario = $_SESSION['usuario'];

    $conexion->begin_transaction();
    try {
        $productos_texto = "";
        $sql_desglose = "SELECT dp.cantidad, prod.nombre 
                         FROM detalle_pedidos dp 
                         INNER JOIN productos prod ON dp.id_producto = prod.id 
                         WHERE dp.id_pedido = $id_pedido";
        $res_desglose = $conexion->query($sql_desglose);
        
        if ($res_desglose && $res_desglose->num_rows > 0) {
            $lineas = [];
            while ($item = $res_desglose->fetch_assoc()) {
                $lineas[] = $item['cantidad'] . "x " . $item['nombre'];
            }
            $productos_texto = implode("\n", $lineas);
        } else {
            $productos_texto = "Sin productos desglosados";
        }

        $stmt = $conexion->prepare("INSERT INTO historial_ventas (id_pedido, total, cobrado_por, fecha_hora_cobro, estado, productos_vendidos) VALUES (?, ?, ?, NOW(), 'CANCELADO', ?)");
        $stmt->bind_param("idss", $id_pedido, $total, $usuario, $productos_texto);
        $stmt->execute();

        $conexion->query("DELETE FROM detalle_pedidos WHERE id_pedido = $id_pedido");
        $conexion->query("DELETE FROM pedidos_activos WHERE id_pedido = $id_pedido");

        $conexion->commit();
        $mensaje = "❌ Pedido #$id_pedido cancelado.";
        $tipo_mensaje = "error";
    } catch (Exception $e) {
        $conexion->rollback();
        $mensaje = "Error al cancelar pedido: " . $e->getMessage();
        $tipo_mensaje = "error";
    }
}

// CONSULTA GENERAL: Monitoreo en tiempo real de pedidos activos
$sql = "SELECT pa.id_pedido, pa.mesa, pa.total, pa.estado,
        GROUP_CONCAT(CONCAT(dp.cantidad, 'x ', prod.nombre) SEPARATOR '<br>') AS productos_detalle
        FROM pedidos_activos pa
        INNER JOIN detalle_pedidos dp ON pa.id_pedido = dp.id_pedido
        INNER JOIN productos prod ON dp.id_producto = prod.id
        GROUP BY pa.id_pedido, pa.mesa, pa.total, pa.estado
        ORDER BY pa.id_pedido DESC";

$resultado = $conexion->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caja (Admin) - Taquería Novillero</title>
    <style>
        body { 
            background-color: #f4ece1; 
            font-family: 'Courier New', Courier, monospace; 
            color: #2c1d11; 
            padding: 20px; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
        }
        .container { 
            width: 100%; 
            max-width: 950px; 
            background-color: #ffffff; 
            padding: 25px; 
            border-radius: 20px; 
            border: 2px solid #3d2514; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        h2 { 
            border-bottom: 3px double #d32f2f; 
            padding-bottom: 10px; 
            text-transform: uppercase; 
            text-align: center; 
            margin-top: 0;
        }
        .alerta { 
            padding: 12px; 
            margin-bottom: 20px; 
            font-weight: bold; 
            border-radius: 8px; 
            text-align: center; 
        }
        .exito { 
            background-color: #edf7ed; 
            color: #1e4620; 
            border-left: 6px solid #4caf50; 
        }
        .error { 
            background-color: #fdf2f2; 
            color: #b71c1c; 
            border-left: 6px solid #d32f2f; 
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 10px; 
        }
        th { 
            background-color: #2c1d11; 
            color: white; 
            padding: 12px; 
            text-transform: uppercase; 
            font-size: 12px;
        }
        td { 
            padding: 12px; 
            border-bottom: 1px solid #f4ece1; 
            text-align: center; 
            font-size: 14px;
        }
        .badge-cocina { 
            background-color: #fff3cd; 
            color: #856404; 
            padding: 5px 10px; 
            border-radius: 5px; 
            font-weight: bold; 
            font-size: 11px; 
            display: inline-block;
        }
        .badge-servido { 
            background-color: #edf7ed; 
            color: #1e4620; 
            padding: 5px 10px; 
            border-radius: 5px; 
            font-weight: bold; 
            font-size: 11px; 
            display: inline-block;
        }
        .btn-cobrar { 
            background-color: #4caf50; 
            color: white; 
            border: none; 
            padding: 8px 14px; 
            font-weight: bold; 
            border-radius: 5px; 
            cursor: pointer; 
            font-family: inherit;
        }
        .btn-cancelar { 
            background-color: #d32f2f; 
            color: white; 
            border: none; 
            padding: 8px 14px; 
            font-weight: bold; 
            border-radius: 5px; 
            cursor: pointer; 
            font-family: inherit;
        }
        .btn-cobrar:hover { background-color: #43a047; }
        .btn-cancelar:hover { background-color: #b71c1c; }
        .btn-volver { 
            display: block; 
            width: 250px; 
            margin: 20px auto 0 auto; 
            text-align: center; 
            color: white; 
            text-decoration: none; 
            background: #3d2514; 
            padding: 10px; 
            border-radius: 10px; 
            font-weight: bold;
        }
        .btn-volver:hover { background-color: #d32f2f; }

        /* HU12: Estilos del Modal de Pago Dividido / Mixto */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(44, 29, 17, 0.75);
            z-index: 999;
            justify-content: center;
            align-items: center;
        }
        .modal-overlay.activo {
            display: flex;
        }
        .modal-caja {
            background: #ffffff;
            border: 3px solid #3d2514;
            border-radius: 18px;
            padding: 25px;
            width: 90%;
            max-width: 440px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.3);
        }
        .modal-caja h3 {
            margin-top: 0;
            color: #3d2514;
            border-bottom: 3px double #d32f2f;
            padding-bottom: 8px;
            text-transform: uppercase;
            text-align: center;
        }
        .info-total-modal {
            background: #fcf8f2;
            border: 1px dashed #3d2514;
            padding: 10px;
            border-radius: 10px;
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .botones-rapidos {
            display: flex;
            gap: 6px;
            margin-bottom: 15px;
        }
        .btn-rapido {
            flex: 1;
            background: #f4ece1;
            border: 1px solid #3d2514;
            color: #2c1d11;
            padding: 6px;
            font-family: inherit;
            font-size: 11px;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
        }
        .btn-rapido:hover {
            background: #3d2514;
            color: #fff;
        }
        .grupo-pago {
            margin-bottom: 12px;
        }
        .grupo-pago label {
            display: block;
            font-weight: bold;
            font-size: 13px;
            margin-bottom: 4px;
            text-transform: uppercase;
        }
        .grupo-pago input {
            width: 100%;
            box-sizing: border-box;
            padding: 8px 10px;
            border: 2px solid #3d2514;
            border-radius: 8px;
            font-family: inherit;
            font-size: 15px;
            font-weight: bold;
        }
        .estado-validacion {
            padding: 10px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: bold;
            text-align: center;
            margin: 15px 0;
        }
        .estado-incompleto {
            background-color: #fdf2f2;
            color: #b71c1c;
            border: 1px solid #d32f2f;
        }
        .estado-completo {
            background-color: #edf7ed;
            color: #1e4620;
            border: 1px solid #4caf50;
        }
        .acciones-modal {
            display: flex;
            gap: 10px;
        }
        .btn-confirmar-pago {
            flex: 2;
            background-color: #4caf50;
            color: white;
            border: none;
            padding: 10px;
            font-family: inherit;
            font-weight: bold;
            font-size: 13px;
            border-radius: 8px;
            cursor: pointer;
            text-transform: uppercase;
        }
        .btn-confirmar-pago:disabled {
            background-color: #cccccc;
            color: #666666;
            cursor: not-allowed;
        }
        .btn-cerrar-modal {
            flex: 1;
            background-color: #2c1d11;
            color: white;
            border: none;
            padding: 10px;
            font-family: inherit;
            font-weight: bold;
            font-size: 13px;
            border-radius: 8px;
            cursor: pointer;
            text-transform: uppercase;
        }
    </style>
</head>
<body>

    <div class="container">
        <h2>Monitoreo de Caja (Admin)</h2>

        <?php if (!empty($mensaje)): ?>
            <div class="alerta <?php echo ($tipo_mensaje == 'exito') ? 'exito' : 'error'; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>Pedido</th>
                    <th>Mesa</th>
                    <th>Productos</th>
                    <th>Total</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($resultado && $resultado->num_rows > 0): ?>
                    <?php while ($fila = $resultado->fetch_assoc()): ?>
                        <tr>
                            <td><strong>#<?php echo $fila['id_pedido']; ?></strong></td>
                            <td><span style="background:#d32f2f; color:white; padding:4px 8px; border-radius:4px; font-weight: bold;">MESA <?php echo $fila['mesa']; ?></span></td>
                            <td style="text-align: left; line-height: 1.4;"><?php echo $fila['productos_detalle']; ?></td>
                            <td style="font-weight: bold; color: #b71c1c;">$<?php echo number_format($fila['total'], 2); ?></td>
                            <td>
                                <?php if ($fila['estado'] == 'en cocina'): ?>
                                    <span class="badge-cocina">⏳ EN COCINA</span>
                                <?php else: ?>
                                    <span class="badge-servido">🍳 SERVIDO</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 8px; justify-content: center;">
                                    <button type="button" class="btn-cobrar" onclick="abrirModalCobro(<?php echo $fila['id_pedido']; ?>, <?php echo $fila['total']; ?>, <?php echo $fila['mesa']; ?>)">
                                        Cobrar
                                    </button>

                                    <form method="POST" action="" onsubmit="return confirm('¿Está seguro de cancelar el pedido #<?php echo $fila['id_pedido']; ?>?');" style="margin:0;">
                                        <input type="hidden" name="id_pedido_accion" value="<?php echo $fila['id_pedido']; ?>">
                                        <input type="hidden" name="total_pedido" value="<?php echo $fila['total']; ?>">
                                        <button type="submit" name="cancelar_pedido" class="btn-cancelar">Cancelar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="font-style: italic; color: #777; padding: 20px;">No hay pedidos activos en este momento.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <a href="panel_admin.php" class="btn-volver">← Volver al Panel Admin</a>
    </div>

    <!-- HU12: Modal de Pagos Divididos o Mixtos -->
    <div id="modalCobro" class="modal-overlay">
        <div class="modal-caja">
            <h3 id="tituloModal">Cobrar Cuenta</h3>
            <div class="info-total-modal">
                Total de la Orden: $<span id="spanTotalOrden">0.00</span>
            </div>

            <div class="botones-rapidos">
                <button type="button" class="btn-rapido" onclick="asignarPagoRapido('efectivo')">💵 100% Efectivo</button>
                <button type="button" class="btn-rapido" onclick="asignarPagoRapido('digital')">💳 100% Digital</button>
                <button type="button" class="btn-rapido" onclick="asignarPagoRapido('mitad')">⚖️ 50% / 50%</button>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="id_pedido_accion" id="modalIdPedido">
                <input type="hidden" name="total_pedido" id="modalTotalPedido">

                <div class="grupo-pago">
                    <label for="inputEfectivo">💵 Monto en Efectivo ($):</label>
                    <input type="number" step="0.01" min="0" name="monto_efectivo" id="inputEfectivo" value="0.00" required>
                </div>

                <div class="grupo-pago">
                    <label for="inputDigital">💳 Monto Tarjeta / Transferencia ($):</label>
                    <input type="number" step="0.01" min="0" name="monto_digital" id="inputDigital" value="0.00" required>
                </div>

                <div id="estadoSuma" class="estado-validacion estado-incompleto">
                    Faltan por cubrir: $0.00
                </div>

                <div class="acciones-modal">
                    <button type="button" class="btn-cerrar-modal" onclick="cerrarModalCobro()">Volver</button>
                    <button type="submit" name="cobrar_pedido" id="btnConfirmarCobro" class="btn-confirmar-pago" disabled>
                        Confirmar y Cerrar Cuenta
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let modalAbierto = false;
        let totalActualOrden = 0;

        function abrirModalCobro(idPedido, total, mesa) {
            modalAbierto = true;
            totalActualOrden = parseFloat(total);

            document.getElementById('tituloModal').textContent = `Cobrar Pedido #${idPedido} (Mesa ${mesa})`;
            document.getElementById('spanTotalOrden').textContent = totalActualOrden.toFixed(2);
            document.getElementById('modalIdPedido').value = idPedido;
            document.getElementById('modalTotalPedido').value = totalActualOrden.toFixed(2);

            document.getElementById('inputEfectivo').value = totalActualOrden.toFixed(2);
            document.getElementById('inputDigital').value = "0.00";

            validarSumaPagos();
            document.getElementById('modalCobro').classList.add('activo');
            document.getElementById('inputEfectivo').focus();
            document.getElementById('inputEfectivo').select();
        }

        function cerrarModalCobro() {
            modalAbierto = false;
            document.getElementById('modalCobro').classList.remove('activo');
        }

        function asignarPagoRapido(tipo) {
            const inEfec = document.getElementById('inputEfectivo');
            const inDig = document.getElementById('inputDigital');

            if (tipo === 'efectivo') {
                inEfec.value = totalActualOrden.toFixed(2);
                inDig.value = "0.00";
            } else if (tipo === 'digital') {
                inEfec.value = "0.00";
                inDig.value = totalActualOrden.toFixed(2);
            } else if (tipo === 'mitad') {
                let mitad = Math.floor((totalActualOrden / 2) * 100) / 100;
                let resto = (totalActualOrden - mitad).toFixed(2);
                inEfec.value = mitad.toFixed(2);
                inDig.value = resto;
            }
            validarSumaPagos();
        }

        function validarSumaPagos() {
            let efectivo = parseFloat(document.getElementById('inputEfectivo').value) || 0;
            let digital = parseFloat(document.getElementById('inputDigital').value) || 0;
            let suma = Math.round((efectivo + digital) * 100) / 100;
            let total = Math.round(totalActualOrden * 100) / 100;
            let diferencia = Math.round((suma - total) * 100) / 100;

            const divEstado = document.getElementById('estadoSuma');
            const btnConfirmar = document.getElementById('btnConfirmarCobro');

            if (suma >= total && total > 0 && efectivo >= 0 && digital >= 0) {
                divEstado.className = 'estado-validacion estado-completo';
                if (diferencia > 0) {
                    divEstado.textContent = `✔ Total cubierto (Suma: $${suma.toFixed(2)}) | Cambio: $${diferencia.toFixed(2)}`;
                } else {
                    divEstado.textContent = `✔ Monto exacto cubierto ($${suma.toFixed(2)})`;
                }
                btnConfirmar.disabled = false;
            } else {
                let faltante = Math.abs(diferencia).toFixed(2);
                divEstado.className = 'estado-validacion estado-incompleto';
                divEstado.textContent = `⚠️ Suma actual: $${suma.toFixed(2)} | Faltan: $${faltante}`;
                btnConfirmar.disabled = true;
            }
        }

        document.getElementById('inputEfectivo').addEventListener('input', validarSumaPagos);
        document.getElementById('inputDigital').addEventListener('input', validarSumaPagos);

        setInterval(function() {
            if (!modalAbierto) {
                location.reload();
            }
        }, 5000);
    </script>
</body>
</html>
<?php $conexion->close(); ?>