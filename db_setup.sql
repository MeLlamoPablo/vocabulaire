-- 1. Create a database called 'vocabulaire', without quotes
-- 2. Copy and paste this entire file into your 'Import' tab in PhpMyAdmin
-- 3. Configure 'connect.php'

-- phpMyAdmin SQL Dump
-- version 4.2.11
-- http://www.phpmyadmin.net
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 04-04-2015 a las 12:21:31
-- Versión del servidor: 5.6.21
-- Versión de PHP: 5.6.3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Base de datos: `vocabulaire`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `examenes`
--

CREATE TABLE IF NOT EXISTS `examenes` (
`id` int(11) NOT NULL,
  `nombre` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `activa` int(1) NOT NULL DEFAULT '0',
  `preguntas` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `preguntas`
--

CREATE TABLE IF NOT EXISTS `preguntas` (
`id` int(11) NOT NULL,
  `num` int(11) NOT NULL,
  `examen` int(11) NOT NULL,
  `esp` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `fra` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `modo` int(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `examenes`
--
ALTER TABLE `examenes`
 ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `preguntas`
--
ALTER TABLE `preguntas`
 ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `examenes`
--
ALTER TABLE `examenes`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT de la tabla `preguntas`
--
ALTER TABLE `preguntas`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;