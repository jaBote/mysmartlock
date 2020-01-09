-- phpMyAdmin SQL Dump
-- version 4.6.6deb4
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 28-01-2019 a las 23:50:42
-- Versión del servidor: 10.1.37-MariaDB-0+deb9u1
-- Versión de PHP: 7.0.33-0+deb9u1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `smartlock`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `commands`
--

CREATE TABLE `commands` (
  `lockid` bigint(20) NOT NULL,
  `action` enum('o','p','m') NOT NULL DEFAULT 'm' COMMENT '''o''pen remotely, ''p''icture, ''m''isc''. Logs don''t need raspi',
  `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `locks`
--

CREATE TABLE `locks` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(32) NOT NULL,
  `admin_mail` varchar(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `locks`
--

INSERT INTO `locks` (`id`, `code`, `admin_mail`) VALUES
(1, 'c81e72', 'micorreoadministrativo@gmail.com');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `logs`
--

CREATE TABLE `logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `lockid` bigint(20) DEFAULT NULL,
  `lockcode` varchar(32) DEFAULT NULL,
  `origin` enum('w','l','o') NOT NULL DEFAULT 'o' COMMENT '''w''eb, ''l''ock, ''o''ther',
  `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `log` text NOT NULL,
  `pic_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pics`
--

CREATE TABLE `pics` (
  `pic_id` bigint(20) UNSIGNED NOT NULL,
  `lock_id` bigint(20) NOT NULL,
  `pic_data` mediumblob NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `userid` bigint(20) UNSIGNED NOT NULL,
  `lockid` bigint(20) UNSIGNED NOT NULL,
  `username` varchar(32) NOT NULL,
  `pass` varchar(32) NOT NULL,
  `type` enum('u','a','s') NOT NULL DEFAULT 'u' COMMENT '''u''ser, ''a''dmin, ''s''ystem',
  `expiry` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`userid`, `lockid`, `username`, `pass`, `type`, `expiry`) VALUES
(1, 1, 'admin', 'admin', 'a', NULL),
(2, 1, 'user', 'user', 'u', NULL),
(3, 1, 'caducado', 'caducado', 'u', '2019-01-16 00:00:00');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `locks`
--
ALTER TABLE `locks`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `FK_PIC_ID` (`pic_id`);

--
-- Indices de la tabla `pics`
--
ALTER TABLE `pics`
  ADD PRIMARY KEY (`pic_id`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`userid`),
  ADD UNIQUE KEY `lockid` (`lockid`,`username`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `locks`
--
ALTER TABLE `locks`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT de la tabla `logs`
--
ALTER TABLE `logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT de la tabla `pics`
--
ALTER TABLE `pics`
  MODIFY `pic_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `userid` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `logs`
--
ALTER TABLE `logs`
  ADD CONSTRAINT `FK_PIC_ID` FOREIGN KEY (`pic_id`) REFERENCES `pics` (`pic_id`) ON DELETE NO ACTION ON UPDATE CASCADE;

--
-- Filtros para la tabla `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `FK_KEY_ID` FOREIGN KEY (`lockid`) REFERENCES `locks` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
