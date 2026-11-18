<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    exit();
}

$conexion = new mysqli("localhost", "root", "", "taqueria_novillero");
if ($conexion->connect_error) { 
    exit(); 
}

$sql = "SELECT pa.id_pedido, pa.mesa, pa.total, pa.estado,
        GROUP_CONCAT(
            CONCAT(
                dp.cantidad, 'x ', prod.nombre, 
                IF(dp.observacion IS NOT NULL AND dp.observacion != '', CONCAT(' <small style=\"color:#d32f2f; font-weight:bold;\">(', dp.observacion, ')</small>'), '')
            ) SEPARATOR '<br>'
        ) AS productos_detalle
        FROM pedidos_activos pa
        INNER JOIN detalle_pedidos dp ON pa.id_pedido = dp.id_pedido
        INNER JOIN productos prod ON dp.id_producto = prod.id
        GROUP BY pa.id_pedido, pa.mesa, pa.total, pa.estado
        ORDER BY pa.id_pedido DESC";

$resultado = $conexion->query($sql);
$pedidos = [];

if ($resultado && $resultado->num_rows > 0) {
    while ($fila = $resultado->fetch_assoc()) {
        $pedidos[] = $fila;
    }
}

header('Content-Type: application/json');
echo json_encode($pedidos);
$conexion->close();
?>