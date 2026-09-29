USE ferramentas;
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
