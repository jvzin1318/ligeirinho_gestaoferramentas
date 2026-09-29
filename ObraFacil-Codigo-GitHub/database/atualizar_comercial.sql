USE ferramentas;
CREATE TABLE IF NOT EXISTS fornecedores (
 id INT AUTO_INCREMENT PRIMARY KEY,nome VARCHAR(150) NOT NULL,cnpj VARCHAR(18) NULL UNIQUE,
 email VARCHAR(190) NOT NULL DEFAULT '',telefone VARCHAR(30) NOT NULL DEFAULT '',endereco VARCHAR(255) NOT NULL DEFAULT '',ativo TINYINT NOT NULL DEFAULT 1
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS ferramenta_comercial (
 ferramenta_id INT PRIMARY KEY,fornecedor_id INT NOT NULL,
 FOREIGN KEY(ferramenta_id) REFERENCES ferramentas(id) ON DELETE CASCADE,FOREIGN KEY(fornecedor_id) REFERENCES fornecedores(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS clientes (
 id INT AUTO_INCREMENT PRIMARY KEY,tipo ENUM('PF','PJ') NOT NULL,nome VARCHAR(150) NOT NULL,
 documento VARCHAR(18) NOT NULL UNIQUE,email VARCHAR(190) NOT NULL,telefone VARCHAR(30) NOT NULL,
 criado_por INT NOT NULL,FOREIGN KEY(criado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS pedidos (
 id INT AUTO_INCREMENT PRIMARY KEY,usuario_id INT NOT NULL,cliente_id INT NOT NULL,
 cliente_snapshot LONGTEXT NOT NULL,chave CHAR(64) NOT NULL UNIQUE,
 referencia CHAR(32) NOT NULL UNIQUE,subtotal DECIMAL(14,2) NOT NULL,total DECIMAL(14,2) NOT NULL,
 status VARCHAR(40) NOT NULL DEFAULT 'Aguardando pagamento',criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 preference_id VARCHAR(150) NULL,checkout_url TEXT NULL,checkout_tentado_em DATETIME NULL,
 payment_id VARCHAR(100) NULL UNIQUE,pagamento_status VARCHAR(50) NULL,meio_pagamento VARCHAR(50) NULL,
 pago_em DATETIME NULL,financeiro_atualizado_em DATETIME NULL,
 FOREIGN KEY(usuario_id) REFERENCES usuarios(id),FOREIGN KEY(cliente_id) REFERENCES clientes(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS pedido_itens (
 id INT AUTO_INCREMENT PRIMARY KEY,pedido_id INT NOT NULL,ferramenta_id INT NOT NULL,
 nome VARCHAR(150) NOT NULL,quantidade INT NOT NULL,preco DECIMAL(10,2) NOT NULL,venda_id INT NULL UNIQUE,
 FOREIGN KEY(pedido_id) REFERENCES pedidos(id),FOREIGN KEY(ferramenta_id) REFERENCES ferramentas(id),FOREIGN KEY(venda_id) REFERENCES vendas(id),
 UNIQUE KEY item_pedido(pedido_id,ferramenta_id)
) ENGINE=InnoDB;
