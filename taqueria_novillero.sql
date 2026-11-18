-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 18-11-2026 a las 03:52:18
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `taqueria_novillero`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_pedidos`
--

CREATE TABLE `detalle_pedidos` (
  `id_detalle` int(11) NOT NULL,
  `id_pedido` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalle_pedidos`
--

INSERT INTO `detalle_pedidos` (`id_detalle`, `id_pedido`, `id_producto`, `cantidad`) VALUES
(67, 46, 1, 2),
(68, 47, 19, 3),
(69, 47, 25, 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_ventas`
--

CREATE TABLE `historial_ventas` (
  `id_venta` int(11) NOT NULL,
  `id_pedido` int(11) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `cobrado_por` varchar(100) NOT NULL,
  `fecha_hora_cobro` datetime NOT NULL,
  `estado` enum('COBRADO','CANCELADO') NOT NULL DEFAULT 'COBRADO',
  `productos_vendidos` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `historial_ventas`
--

INSERT INTO `historial_ventas` (`id_venta`, `id_pedido`, `total`, `cobrado_por`, `fecha_hora_cobro`, `estado`, `productos_vendidos`) VALUES
(2, 41, 54.00, 'admin1', '2026-06-16 02:20:27', 'COBRADO', '3x Taco de Pastor'),
(3, 42, 72.00, 'Beyker', '2026-06-16 02:24:53', 'COBRADO', '4x Taco de Pastor'),
(4, 43, 156.00, 'Beyker', '2026-06-16 02:25:14', 'CANCELADO', '6x Taco de perro'),
(5, 44, 162.00, 'admin1', '2026-06-16 11:23:30', 'COBRADO', '9x Taco de Pastor'),
(6, 45, 180.00, 'admin1', '2026-06-16 11:24:06', 'CANCELADO', '10x Taco de Pastor');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos_activos`
--

CREATE TABLE `pedidos_activos` (
  `id_pedido` int(11) NOT NULL,
  `mesa` int(11) NOT NULL DEFAULT 0,
  `total` decimal(30,2) NOT NULL,
  `fecha_hora` timestamp NOT NULL DEFAULT current_timestamp(),
  `estado` varchar(20) NOT NULL DEFAULT 'en cocina'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pedidos_activos`
--

INSERT INTO `pedidos_activos` (`id_pedido`, `mesa`, `total`, `fecha_hora`, `estado`) VALUES
(46, 1, 36.00, '2026-06-16 18:05:27', 'en cocina'),
(47, 3, 159.00, '2026-06-16 18:05:59', 'en cocina');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `categoria` enum('taco','agua fresca','refresco','postre') NOT NULL,
  `precio` decimal(6,2) NOT NULL,
  `existencias` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `nombre`, `categoria`, `precio`, `existencias`) VALUES
(1, 'Taco de Pastor', 'taco', 18.00, 14),
(2, 'Taco de Suadero', 'taco', 18.00, 114),
(3, 'Taco de perro', 'taco', 26.00, 992),
(4, 'Taco de Longaniza', 'taco', 18.00, 59),
(5, 'Taco de Campechano', 'taco', 80.00, 89),
(6, 'Taco de Tripa', 'taco', 22.00, 42),
(8, 'Taco de Lengua', 'taco', 25.00, 100),
(9, 'Taco de Gringa (Al Pastor con Queso)', 'taco', 35.00, 61),
(10, 'Taco de Volcán de Bistec', 'taco', 38.00, 45),
(11, 'Agua de Horchata Grande', 'agua fresca', 25.00, 30),
(12, 'Agua de Jamaica Grande', 'agua fresca', 25.00, 24),
(13, 'Agua de Tamarindo Grande', 'agua fresca', 25.00, 20),
(14, 'Agua de Limón con Chía Grande', 'agua fresca', 25.00, 14),
(15, 'Agua de Guayaba Grande', 'agua fresca', 25.00, 12),
(16, 'Coca-Cola Original 600ml', 'refresco', 22.00, 41),
(17, 'Coca-Cola Sin Azúcar 600ml', 'refresco', 22.00, 24),
(18, 'Sidral Mundet 600ml', 'refresco', 20.00, 28),
(19, 'Jarrito de Mandarina 600ml', 'refresco', 18.00, 33),
(20, 'Agua Mineral Topo Chico', 'refresco', 25.00, 16),
(21, 'Flan Napolitano', 'postre', 30.00, 14),
(22, 'Jericalla', 'postre', 25.00, 20),
(23, 'Pay de Limón', 'postre', 31.00, 28),
(24, 'Arroz con Leche', 'postre', 70.00, 0),
(25, 'Fresas con Crema', 'postre', 35.00, 5),
(26, 'Agua de mazapan', 'agua fresca', 45.00, 0),
(35, 'Taco de pollo', 'taco', 9.00, 0),
(36, 'pay', 'postre', 60.00, 0),
(37, 'Agua de kiwi', 'agua fresca', 30.00, 0),
(38, 'Doctor pepper', 'refresco', 20.00, 0),
(39, 'taco de cecina', 'taco', 14.00, 0),
(40, 'tacos de panza', 'taco', 50.00, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `rol` enum('administrador','trabajador') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `usuario`, `contrasena`, `rol`) VALUES
(2, 'empleado1', 'tacos123', 'trabajador'),
(3, 'trabajador2', 'tacos321', 'trabajador'),
(6, 'admin1', 'admin123', 'administrador'),
(7, 'DUEÑO', 'novillero1', 'administrador'),
(10, 'Angel Arriola', '123', 'administrador'),
(11, 'Duche rob', '123', 'administrador'),
(12, 'Beyker', '123', 'trabajador');

--
-- Disparadores `usuarios`
--
DELIMITER $$
CREATE TRIGGER `bd_proteger_dueno` BEFORE DELETE ON `usuarios` FOR EACH ROW BEGIN
    -- Si el ID que intentan borrar es el 7, frenamos la operación en seco
    IF OLD.id = 7 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'ERROR: El perfil del Dueño (ID 7) está blindado y no puede ser eliminado.';
    END IF;
END
$$
DELIMITER ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `detalle_pedidos`
--
ALTER TABLE `detalle_pedidos`
  ADD PRIMARY KEY (`id_detalle`),
  ADD KEY `detalle_pedidos_ibfk_1` (`id_pedido`);

--
-- Indices de la tabla `historial_ventas`
--
ALTER TABLE `historial_ventas`
  ADD PRIMARY KEY (`id_venta`);

--
-- Indices de la tabla `pedidos_activos`
--
ALTER TABLE `pedidos_activos`
  ADD PRIMARY KEY (`id_pedido`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `detalle_pedidos`
--
ALTER TABLE `detalle_pedidos`
  MODIFY `id_detalle` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT de la tabla `historial_ventas`
--
ALTER TABLE `historial_ventas`
  MODIFY `id_venta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `pedidos_activos`
--
ALTER TABLE `pedidos_activos`
  MODIFY `id_pedido` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `detalle_pedidos`
--
ALTER TABLE `detalle_pedidos`
  ADD CONSTRAINT `detalle_pedidos_ibfk_1` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos_activos` (`id_pedido`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
