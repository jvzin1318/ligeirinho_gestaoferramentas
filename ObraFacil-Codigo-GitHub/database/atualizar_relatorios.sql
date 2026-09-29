USE ferramentas;
CREATE TABLE IF NOT EXISTS relatorios_enviados (
 id INT AUTO_INCREMENT PRIMARY KEY,
 usuario_id INT NOT NULL,
 tipo VARCHAR(30) NOT NULL,
 periodo_inicio DATE NOT NULL,
 periodo_fim DATE NOT NULL,
 gerado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 enviado_em DATETIME NULL,
 conteudo LONGTEXT NOT NULL,
 UNIQUE KEY unico_periodo (usuario_id,tipo,periodo_inicio,periodo_fim),
 FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
 INDEX (enviado_em)
) ENGINE=InnoDB;
