<?php
function tiposRanking(): array {return ['funcionarios'=>'Funcionários que mais venderam','fornecedores'=>'Fornecedores de quem mais compramos','clientes'=>'Clientes / empresas que mais compraram','ferramentas_mais'=>'Ferramentas mais vendidas','ferramentas_menos'=>'Ferramentas menos vendidas'];}
function periodoMensal(string $mes): array {
 if(!preg_match('/^[0-9]{4}-(0[1-9]|1[0-2])$/D',$mes)||$mes<'1900-01'||$mes>date('Y-m'))throw new RuntimeException('Selecione um mês válido, até o mês atual.');
 $d=DateTimeImmutable::createFromFormat('!Y-m-d',$mes.'-01');return [$d->format('Y-m-d'),$d->modify('+1 month')->format('Y-m-d')];
}
function valorComparavel($value): string {
 $p=explode('.',(string)$value,2);$i=ltrim($p[0],'0');$f=rtrim($p[1]??'','0');return ($i===''?'0':$i).($f!==''?'.'.$f:'');
}
function rankingMensal(string $tipo,string $mes): array {
 requireAdmin();if(!isset(tiposRanking()[$tipo]))throw new RuntimeException('Relatório inválido.');[$from,$until]=periodoMensal($mes);
 $joins=' LEFT JOIN comprovante_itens ci ON ci.venda_id=v.id LEFT JOIN comprovantes cp ON cp.id=ci.comprovante_id ';
 $valid=" v.status='Concluída' AND v.quantidade_liquida>0 AND (cp.id IS NULL OR cp.status='Concluída') AND COALESCE(cp.criado_em,v.criado_em)>=? AND COALESCE(cp.criado_em,v.criado_em)<? ";
 $note='Vendas canceladas são excluídas e devoluções são descontadas da venda original, inclusive quando registradas depois do mês consultado. O valor vendido inclui boletos a receber e não equivale ao caixa recebido. Empates têm a mesma posição.';$metric='total';
 if($tipo==='funcionarios'){
  $columns=['Posição','ID','Funcionário','Vendas / atendimentos','Peças vendidas','Total vendido (R$)'];
  $rows=query("SELECT u.id,u.nome,COUNT(DISTINCT CASE WHEN ci.comprovante_id IS NOT NULL THEN CONCAT('C',ci.comprovante_id) ELSE CONCAT('V',v.id) END) vendas,SUM(v.quantidade_liquida) quantidade,SUM(v.total_liquido) total FROM ".vendasLiquidasSql()." v JOIN usuarios u ON u.id=v.usuario_id $joins WHERE $valid GROUP BY u.id,u.nome ORDER BY total DESC,u.id",[$from,$until])->fetchAll();
  $note.=' Ordem pelo valor vendido. Cada comprovante conta um atendimento, mesmo com vários itens. Registros antigos sem comprovante contam individualmente. Inclui qualquer funcionário que realizou vendas, inclusive gerente.';
 }elseif($tipo==='fornecedores'){
  $columns=['Posição','ID','Fornecedor','Documentos de compra','Peças compradas','Total comprado (R$)'];
  $rows=query("SELECT s.id,s.nome,COUNT(DISTINCT COALESCE(CONCAT('D',i.documento_id),CONCAT('L',c.id))) lancamentos,SUM(c.quantidade) quantidade,SUM(c.total) total FROM compras_estoque c LEFT JOIN compras_documento_itens i ON i.compra_id=c.id JOIN fornecedores s ON s.id=c.fornecedor_id WHERE c.status='Registrada' AND c.data_compra>=? AND c.data_compra<? GROUP BY s.id,s.nome ORDER BY total DESC,s.id",[$from,$until])->fetchAll();
  $note='Ordem pelo custo total das compras recebidas, pela data da compra. Compras canceladas são excluídas. Um documento conta uma compra, mesmo com vários itens. Os lançamentos antigos foram preservados como documentos individuais. Vendas aos clientes, estoque inicial e ajustes manuais não entram neste ranking. Empates têm a mesma posição.';
 }elseif($tipo==='clientes'){
  $columns=['Posição','CPF / CNPJ','Cliente / empresa','Compras','Peças compradas','Total comprado (R$)'];
  // Uma linha por comprovante na subconsulta impede multiplicar seu total pelo número de itens.
  $rows=query("SELECT cp.cliente_documento documento,MAX(cp.cliente_nome) nome,COUNT(*) compras,SUM(q.quantidade) quantidade,SUM(q.total) total FROM comprovantes cp JOIN (SELECT ci.comprovante_id,SUM(v.quantidade_liquida) quantidade,SUM(v.total_liquido) total FROM comprovante_itens ci JOIN ".vendasLiquidasSql()." v ON v.id=ci.venda_id WHERE v.status='Concluída' AND v.quantidade_liquida>0 GROUP BY ci.comprovante_id) q ON q.comprovante_id=cp.id WHERE cp.status='Concluída' AND cp.cliente_documento<>'' AND cp.criado_em>=? AND cp.criado_em<? GROUP BY cp.cliente_documento ORDER BY total DESC,cp.cliente_documento",[$from,$until])->fetchAll();
  $note.=' Ordem pelo valor comprado. Compras são agrupadas pelo CPF/CNPJ preservado na venda, mesmo com variações de nome; é exibido um dos nomes registrados. Consumidor final sem documento e registros antigos sem CPF/CNPJ ficam fora do ranking: não são tratados como um único cliente.';
 }else{
  $columns=['Posição','ID','Ferramenta','Peças vendidas','Total vendido (R$)'];$metric='quantidade';$asc=$tipo==='ferramentas_menos';$order=$asc?'ASC':'DESC';$onlySold=$asc?'':' AND COALESCE(s.quantidade,0)>0';
  $rows=query("SELECT f.id,f.nome,COALESCE(s.quantidade,0) quantidade,COALESCE(s.total,0) total FROM ferramentas f LEFT JOIN (SELECT v.ferramenta_id,SUM(v.quantidade_liquida) quantidade,SUM(v.total_liquido) total FROM ".vendasLiquidasSql()." v $joins WHERE $valid GROUP BY v.ferramenta_id) s ON s.ferramenta_id=f.id WHERE f.criado_em<? $onlySold ORDER BY quantidade $order,f.id",[$from,$until,$until])->fetchAll();
  $note.=' Ordem pela quantidade de peças. O ranking de menos vendidas inclui ferramentas com zero vendas. Considera ferramentas ainda cadastradas e criadas antes do fim do mês selecionado.';
 }
 $result=[];$position=0;$last=null;
 foreach($rows as $r){$value=valorComparavel($r[$metric]);if($last===null||$value!==$last){$position++;$last=$value;}$result[]=array_merge([$position],array_values($r));}
 return ['columns'=>$columns,'rows'=>$result,'note'=>$note,'inicio'=>$from,'fim'=>(new DateTimeImmutable($until))->modify('-1 day')->format('Y-m-d')];
}
