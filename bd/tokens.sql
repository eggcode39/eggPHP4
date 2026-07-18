-- Tabla de tokens de acceso para la API (app móvil).
-- Guardamos el sha256 del token (no el token en claro): si se filtra la BD,
-- los tokens no son utilizables. El token en claro solo lo tiene la app.
CREATE TABLE IF NOT EXISTS `tokens` (
  `id_token` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `token_hash` char(64) COLLATE utf8_unicode_ci NOT NULL,
  `token_creacion` datetime NOT NULL,
  `token_expira` datetime NOT NULL,
  PRIMARY KEY (`id_token`),
  UNIQUE KEY `uq_token_hash` (`token_hash`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `fk_tokens_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
