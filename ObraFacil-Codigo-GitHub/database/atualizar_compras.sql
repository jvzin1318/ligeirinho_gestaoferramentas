USE ferramentas;
CREATE TABLE IF NOT EXISTS compras_estoque (
 id INT AUTO_INCREMENT PRIMARY KEY,ferramenta_id INT NOT NULL,fornecedor_id INT NOT NULL,
 ferramenta_nome VARCHAR(150) NOT NULL,fornecedor_nome VARCHAR(150) NOT NULL,
 usuario_id INT NOT NULL,quantidade INT NOT NULL,custo_unitario DECIMAL(10,2) NOT NULL,
 total DECIMAL(14,2) NOT NULL,data_compra DATE NOT NULL,referencia VARCHAR(120) NOT NULL DEFAULT '',
 chave CHAR(64) NOT NULL UNIQUE,status ENUM('Registrada','Cancelada') NOT NULL DEFAULT 'Registrada',
 criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,cancelado_em DATETIME NULL,cancelado_por INT NULL,
 FOREIGN KEY(ferramenta_id) REFERENCES ferramentas(id),FOREIGN KEY(fornecedor_id) REFERENCES fornecedores(id),
 FOREIGN KEY(usuario_id) REFERENCES usuarios(id),FOREIGN KEY(cancelado_por) REFERENCES usuarios(id),
 INDEX compras_periodo(data_compra,status),CHECK(quantidade>0),CHECK(custo_unitario>0)
) ENGINE=InnoDB;
