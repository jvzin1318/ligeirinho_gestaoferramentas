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
