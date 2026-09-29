CREATE DATABASE IF NOT EXISTS ferramentas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ferramentas;
CREATE TABLE IF NOT EXISTS usuarios (
 id INT AUTO_INCREMENT PRIMARY KEY, nome VARCHAR(120) NOT NULL,
 email VARCHAR(190) NULL UNIQUE, login VARCHAR(190) NOT NULL UNIQUE, senha VARCHAR(255) NOT NULL,
 perfil ENUM('gerente','vendedor','estoquista') NOT NULL DEFAULT 'vendedor', criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS ferramentas (
 id INT AUTO_INCREMENT PRIMARY KEY, nome VARCHAR(150) NOT NULL, marca VARCHAR(100) NOT NULL,
 categoria VARCHAR(100) NOT NULL, preco DECIMAL(10,2) NOT NULL,
 estoque INT NOT NULL DEFAULT 0, minimo INT NOT NULL DEFAULT 5,
 criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CHECK (preco >= 0), CHECK (estoque >= 0), CHECK (minimo >= 0)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS vendas (
 id INT AUTO_INCREMENT PRIMARY KEY, ferramenta_id INT NOT NULL, usuario_id INT NOT NULL,
 produto_nome VARCHAR(150) NOT NULL, cliente VARCHAR(150) NOT NULL,
 quantidade INT NOT NULL, preco_unitario DECIMAL(10,2) NOT NULL, total DECIMAL(14,2) NOT NULL,
 status ENUM('Concluída','Cancelada') NOT NULL DEFAULT 'Concluída',
 chave CHAR(64) NOT NULL UNIQUE, criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 cancelado_em DATETIME NULL, cancelado_por INT NULL,
 FOREIGN KEY (ferramenta_id) REFERENCES ferramentas(id), FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
 FOREIGN KEY (cancelado_por) REFERENCES usuarios(id), INDEX (criado_em)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS acessos (
 id INT AUTO_INCREMENT PRIMARY KEY, usuario_id INT NULL, email VARCHAR(190) NOT NULL,
 resultado ENUM('Sucesso','Recusado','Bloqueado') NOT NULL, ip VARCHAR(45) NOT NULL,
 entrada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, saida DATETIME NULL,
 FOREIGN KEY (usuario_id) REFERENCES usuarios(id), INDEX (entrada), INDEX (ip, entrada)
) ENGINE=InnoDB;
-- O primeiro gerente é criado em instalar.php, sem senha padrão.

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

-- Dados fictícios para demonstração; preços não são cotações de mercado.
-- Importação repetida não altera estoque/preço nem duplica nomes já existentes.
INSERT INTO ferramentas (nome,marca,categoria,preco,estoque,minimo) SELECT 'Martelo de unha 27 mm','ObraMax','Manuais',39.90,24,5 WHERE NOT EXISTS (SELECT 1 FROM ferramentas WHERE nome='Martelo de unha 27 mm');
INSERT INTO ferramentas (nome,marca,categoria,preco,estoque,minimo) SELECT 'Alicate universal 8 pol.','FerroPro','Manuais',49.90,18,5 WHERE NOT EXISTS (SELECT 1 FROM ferramentas WHERE nome='Alicate universal 8 pol.');
INSERT INTO ferramentas (nome,marca,categoria,preco,estoque,minimo) SELECT 'Jogo de chaves de fenda 6 peças','ObraMax','Manuais',59.90,15,4 WHERE NOT EXISTS (SELECT 1 FROM ferramentas WHERE nome='Jogo de chaves de fenda 6 peças');
INSERT INTO ferramentas (nome,marca,categoria,preco,estoque,minimo) SELECT 'Chave inglesa 10 pol.','FerroPro','Manuais',69.90,12,3 WHERE NOT EXISTS (SELECT 1 FROM ferramentas WHERE nome='Chave inglesa 10 pol.');
INSERT INTO ferramentas (nome,marca,categoria,preco,estoque,minimo) SELECT 'Furadeira de impacto 650 W','Voltix','Elétricas',249.90,8,2 WHERE NOT EXISTS (SELECT 1 FROM ferramentas WHERE nome='Furadeira de impacto 650 W');
INSERT INTO ferramentas (nome,marca,categoria,preco,estoque,minimo) SELECT 'Parafusadeira 12 V','Voltix','Elétricas',329.90,10,3 WHERE NOT EXISTS (SELECT 1 FROM ferramentas WHERE nome='Parafusadeira 12 V');
INSERT INTO ferramentas (nome,marca,categoria,preco,estoque,minimo) SELECT 'Serra circular 1200 W','Construforce','Elétricas',459.90,5,2 WHERE NOT EXISTS (SELECT 1 FROM ferramentas WHERE nome='Serra circular 1200 W');
INSERT INTO ferramentas (nome,marca,categoria,preco,estoque,minimo) SELECT 'Esmerilhadeira 750 W','Construforce','Elétricas',219.90,3,3 WHERE NOT EXISTS (SELECT 1 FROM ferramentas WHERE nome='Esmerilhadeira 750 W');
INSERT INTO ferramentas (nome,marca,categoria,preco,estoque,minimo) SELECT 'Trena de aço 5 m','Precisum','Medição',24.90,30,8 WHERE NOT EXISTS (SELECT 1 FROM ferramentas WHERE nome='Trena de aço 5 m');
INSERT INTO ferramentas (nome,marca,categoria,preco,estoque,minimo) SELECT 'Nível de alumínio 60 cm','Precisum','Medição',44.90,14,4 WHERE NOT EXISTS (SELECT 1 FROM ferramentas WHERE nome='Nível de alumínio 60 cm');
INSERT INTO ferramentas (nome,marca,categoria,preco,estoque,minimo) SELECT 'Esquadro metálico 30 cm','Precisum','Medição',29.90,20,5 WHERE NOT EXISTS (SELECT 1 FROM ferramentas WHERE nome='Esquadro metálico 30 cm');
INSERT INTO ferramentas (nome,marca,categoria,preco,estoque,minimo) SELECT 'Colher de pedreiro 8 pol.','ObraMax','Alvenaria',22.90,25,6 WHERE NOT EXISTS (SELECT 1 FROM ferramentas WHERE nome='Colher de pedreiro 8 pol.');
INSERT INTO ferramentas (nome,marca,categoria,preco,estoque,minimo) SELECT 'Desempenadeira de aço lisa','ObraMax','Alvenaria',34.90,16,4 WHERE NOT EXISTS (SELECT 1 FROM ferramentas WHERE nome='Desempenadeira de aço lisa');
INSERT INTO ferramentas (nome,marca,categoria,preco,estoque,minimo) SELECT 'Serrote profissional 20 pol.','FerroPro','Corte',54.90,4,5 WHERE NOT EXISTS (SELECT 1 FROM ferramentas WHERE nome='Serrote profissional 20 pol.');
INSERT INTO ferramentas (nome,marca,categoria,preco,estoque,minimo) SELECT 'Cortador de piso 60 cm','Construforce','Corte',189.90,6,2 WHERE NOT EXISTS (SELECT 1 FROM ferramentas WHERE nome='Cortador de piso 60 cm');

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

INSERT INTO fornecedores(nome) SELECT 'Distribuidora ObraMax (exemplo)' WHERE NOT EXISTS(SELECT 1 FROM fornecedores WHERE nome='Distribuidora ObraMax (exemplo)');
INSERT INTO fornecedores(nome) SELECT 'Central Elétrica (exemplo)' WHERE NOT EXISTS(SELECT 1 FROM fornecedores WHERE nome='Central Elétrica (exemplo)');
INSERT INTO fornecedores(nome) SELECT 'Casa da Medição (exemplo)' WHERE NOT EXISTS(SELECT 1 FROM fornecedores WHERE nome='Casa da Medição (exemplo)');
INSERT INTO ferramenta_comercial (ferramenta_id,fornecedor_id)
SELECT f.id, (SELECT id FROM fornecedores WHERE nome=CASE WHEN f.categoria='Elétricas' THEN 'Central Elétrica (exemplo)' WHEN f.categoria='Medição' THEN 'Casa da Medição (exemplo)' ELSE 'Distribuidora ObraMax (exemplo)' END LIMIT 1)
FROM ferramentas f WHERE f.marca IN ('ObraMax','FerroPro','Voltix','Construforce','Precisum') AND NOT EXISTS(SELECT 1 FROM ferramenta_comercial c WHERE c.ferramenta_id=f.id);

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

-- Faça backup e execute com o sistema parado, no banco ferramentas.
-- Pode ser reexecutado: os registros antigos não são apagados.
CREATE TABLE IF NOT EXISTS movimentacoes_estoque (
 id BIGINT AUTO_INCREMENT PRIMARY KEY,ferramenta_id INT NULL,codigo_ferramenta INT NOT NULL,ferramenta_nome VARCHAR(150) NOT NULL,
 usuario_id INT NULL,tipo VARCHAR(40) NOT NULL,quantidade INT NOT NULL,
 saldo_anterior INT NOT NULL,saldo_posterior INT NOT NULL,referencia VARCHAR(120) NOT NULL,
 motivo VARCHAR(255) NOT NULL,criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(ferramenta_id) REFERENCES ferramentas(id) ON DELETE SET NULL,FOREIGN KEY(usuario_id) REFERENCES usuarios(id),
 INDEX estoque_periodo(ferramenta_id,criado_em),CHECK(saldo_anterior>=0),CHECK(saldo_posterior>=0)
) ENGINE=InnoDB;
INSERT INTO movimentacoes_estoque(ferramenta_id,codigo_ferramenta,ferramenta_nome,tipo,quantidade,saldo_anterior,saldo_posterior,referencia,motivo)
SELECT f.id,f.id,f.nome,'Saldo de implantação',f.estoque,0,f.estoque,'implantacao','Saldo existente na instalação do histórico; não reconstrói operações anteriores.' FROM ferramentas f WHERE NOT EXISTS(SELECT 1 FROM movimentacoes_estoque m WHERE m.ferramenta_id=f.id);

CREATE TABLE IF NOT EXISTS compras_documentos (
 id INT AUTO_INCREMENT PRIMARY KEY,fornecedor_id INT NOT NULL,fornecedor_nome VARCHAR(150) NOT NULL,usuario_id INT NOT NULL,
 data_compra DATE NOT NULL,referencia VARCHAR(120) NOT NULL DEFAULT '',total DECIMAL(14,2) NOT NULL,
 chave VARCHAR(64) NOT NULL UNIQUE,status VARCHAR(20) NOT NULL DEFAULT 'Registrada',
 criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,cancelado_em DATETIME NULL,cancelado_por INT NULL,
 FOREIGN KEY(fornecedor_id) REFERENCES fornecedores(id),FOREIGN KEY(usuario_id) REFERENCES usuarios(id),
 FOREIGN KEY(cancelado_por) REFERENCES usuarios(id),INDEX compra_data(data_compra)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS compras_documento_itens (
 documento_id INT NOT NULL,compra_id INT NOT NULL UNIQUE,PRIMARY KEY(documento_id,compra_id),
 FOREIGN KEY(documento_id) REFERENCES compras_documentos(id),FOREIGN KEY(compra_id) REFERENCES compras_estoque(id)
) ENGINE=InnoDB;
INSERT INTO compras_documentos(fornecedor_id,fornecedor_nome,usuario_id,data_compra,referencia,total,chave,status,criado_em,cancelado_em,cancelado_por)
SELECT c.fornecedor_id,c.fornecedor_nome,c.usuario_id,c.data_compra,c.referencia,c.total,CONCAT('legado:',c.id),c.status,c.criado_em,c.cancelado_em,c.cancelado_por FROM compras_estoque c WHERE NOT EXISTS(SELECT 1 FROM compras_documento_itens i WHERE i.compra_id=c.id) AND NOT EXISTS(SELECT 1 FROM compras_documentos d WHERE d.chave=CONCAT('legado:',c.id));
INSERT INTO compras_documento_itens(documento_id,compra_id)
SELECT d.id,c.id FROM compras_estoque c JOIN compras_documentos d ON d.chave=CONCAT('legado:',c.id) WHERE NOT EXISTS(SELECT 1 FROM compras_documento_itens i WHERE i.compra_id=c.id);

CREATE TABLE IF NOT EXISTS sessoes_caixa (
 id INT AUTO_INCREMENT PRIMARY KEY,usuario_id INT NOT NULL,usuario_aberto INT NULL UNIQUE,
 aberto_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,fechado_em DATETIME NULL,fechado_por INT NULL,
 resumo LONGTEXT NULL,conferido LONGTEXT NULL,observacao VARCHAR(255) NOT NULL DEFAULT '',
 FOREIGN KEY(usuario_id) REFERENCES usuarios(id),FOREIGN KEY(usuario_aberto) REFERENCES usuarios(id),FOREIGN KEY(fechado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS caixa_vendas (
 comprovante_id INT PRIMARY KEY,sessao_id INT NOT NULL,
 FOREIGN KEY(comprovante_id) REFERENCES comprovantes(id),FOREIGN KEY(sessao_id) REFERENCES sessoes_caixa(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS caixa_movimentos (
 id BIGINT AUTO_INCREMENT PRIMARY KEY,sessao_id INT NOT NULL,comprovante_id INT NOT NULL,usuario_id INT NOT NULL,
 tipo VARCHAR(40) NOT NULL,forma VARCHAR(30) NOT NULL,valor DECIMAL(14,2) NOT NULL,chave VARCHAR(100) NOT NULL UNIQUE,
 referencia VARCHAR(120) NOT NULL DEFAULT '',criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(sessao_id) REFERENCES sessoes_caixa(id),FOREIGN KEY(comprovante_id) REFERENCES comprovantes(id),FOREIGN KEY(usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS devolucoes (
 id INT AUTO_INCREMENT PRIMARY KEY,comprovante_id INT NOT NULL,novo_comprovante_id INT NULL UNIQUE,usuario_id INT NOT NULL,
 tipo VARCHAR(20) NOT NULL,motivo VARCHAR(255) NOT NULL,total DECIMAL(14,2) NOT NULL,total_novo DECIMAL(14,2) NOT NULL DEFAULT 0,
 diferenca DECIMAL(14,2) NOT NULL,forma VARCHAR(30) NOT NULL,referencia VARCHAR(120) NOT NULL DEFAULT '',
 chave CHAR(64) NOT NULL UNIQUE,criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(comprovante_id) REFERENCES comprovantes(id),FOREIGN KEY(novo_comprovante_id) REFERENCES comprovantes(id),FOREIGN KEY(usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS devolucao_itens (
 id INT AUTO_INCREMENT PRIMARY KEY,devolucao_id INT NOT NULL,venda_id INT NOT NULL,quantidade INT NOT NULL,repor_estoque TINYINT NOT NULL,
 preco_unitario DECIMAL(10,2) NOT NULL,total DECIMAL(14,2) NOT NULL,
 FOREIGN KEY(devolucao_id) REFERENCES devolucoes(id),FOREIGN KEY(venda_id) REFERENCES vendas(id),
 UNIQUE KEY item_devolvido(devolucao_id,venda_id),CHECK(quantidade>0)
) ENGINE=InnoDB;
