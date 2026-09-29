USE ferramentas;
-- Nomes fictícios; configure dados reais antes de usar comercialmente.
INSERT INTO fornecedores(nome) SELECT 'Distribuidora ObraMax (exemplo)' WHERE NOT EXISTS(SELECT 1 FROM fornecedores WHERE nome='Distribuidora ObraMax (exemplo)');
INSERT INTO fornecedores(nome) SELECT 'Central Elétrica (exemplo)' WHERE NOT EXISTS(SELECT 1 FROM fornecedores WHERE nome='Central Elétrica (exemplo)');
INSERT INTO fornecedores(nome) SELECT 'Casa da Medição (exemplo)' WHERE NOT EXISTS(SELECT 1 FROM fornecedores WHERE nome='Casa da Medição (exemplo)');
INSERT INTO ferramenta_comercial (ferramenta_id,fornecedor_id)
SELECT f.id, (SELECT id FROM fornecedores WHERE nome=CASE WHEN f.categoria='Elétricas' THEN 'Central Elétrica (exemplo)' WHEN f.categoria='Medição' THEN 'Casa da Medição (exemplo)' ELSE 'Distribuidora ObraMax (exemplo)' END LIMIT 1)
FROM ferramentas f WHERE f.marca IN ('ObraMax','FerroPro','Voltix','Construforce','Precisum') AND NOT EXISTS(SELECT 1 FROM ferramenta_comercial c WHERE c.ferramenta_id=f.id);
