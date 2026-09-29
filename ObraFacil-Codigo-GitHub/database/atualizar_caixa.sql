USE ferramentas;
CREATE TABLE IF NOT EXISTS comprovantes (
 id INT AUTO_INCREMENT PRIMARY KEY, usuario_id INT NOT NULL,
 vendedor_nome VARCHAR(120) NOT NULL, cliente_nome VARCHAR(150) NOT NULL,
 cliente_documento VARCHAR(18) NOT NULL DEFAULT '',loja_snapshot LONGTEXT NOT NULL,
 total DECIMAL(14,2) NOT NULL,forma_pagamento VARCHAR(30) NOT NULL,
 pagamento_status VARCHAR(40) NOT NULL,referencia_pagamento VARCHAR(120) NOT NULL DEFAULT '',
 pagamento_confirmado_em DATETIME NULL,pagamento_confirmado_por INT NULL,
 status ENUM('Concluída','Cancelada') NOT NULL DEFAULT 'Concluída',
 criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,chave CHAR(64) NOT NULL UNIQUE,
 cancelado_em DATETIME NULL,cancelado_por INT NULL,
 FOREIGN KEY(usuario_id) REFERENCES usuarios(id),
 FOREIGN KEY(pagamento_confirmado_por) REFERENCES usuarios(id),FOREIGN KEY(cancelado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS comprovante_itens (
 comprovante_id INT NOT NULL,venda_id INT NOT NULL UNIQUE,
 PRIMARY KEY(comprovante_id,venda_id),
 FOREIGN KEY(comprovante_id) REFERENCES comprovantes(id),FOREIGN KEY(venda_id) REFERENCES vendas(id)
) ENGINE=InnoDB;
