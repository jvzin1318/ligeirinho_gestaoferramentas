<?php
$type=(string)($_GET['tipo']??(admin()?'vendas':'estoque'));if(!in_array($type,['vendas','estoque','acessos'],true))$type='vendas';exigirPermissao('report_'.$type);
$from=(string)($_GET['de']??'');$to=(string)($_GET['ate']??'');$args=[];$where=[];$validation='';
foreach([$from,$to] as $date){if($date!==''&&(!preg_match('/^\d{4}-\d{2}-\d{2}$/D',$date)||!($d=DateTime::createFromFormat('!Y-m-d',$date))||$d->format('Y-m-d')!==$date))$validation='Informe datas válidas.';}
if(!$validation&&$from&&$to&&$from>$to)$validation='A data inicial deve ser anterior à data final.';
$columns=[];$rows=[];
if(!$validation){
 if($type!=='estoque'){
  $field=$type==='acessos'?'a.entrada':'v.criado_em';
  if($from){$where[]="$field >= ?";$args[]=$from.' 00:00:00';}
  if($to){$where[]="$field < DATE_ADD(?,INTERVAL 1 DAY)";$args[]=$to;}
 }
 $filter=$where?' WHERE '.implode(' AND ',$where):'';
 if($type==='acessos'){
  $columns=['ID','Usuário','Usuário informado','Resultado','IP','Entrada','Saída pelo botão'];
  $rows=query("SELECT a.id,COALESCE(u.nome,'Não identificado'),a.email,a.resultado,a.ip,a.entrada,COALESCE(a.saida,'Não registrada') FROM acessos a LEFT JOIN usuarios u ON u.id=a.usuario_id $filter ORDER BY a.id DESC",$args)->fetchAll(PDO::FETCH_NUM);
 }elseif($type==='estoque'){
  $columns=['ID','Ferramenta','Marca','Categoria','Preço (R$)','Estoque','Situação'];
  $rows=query('SELECT id,nome,marca,categoria,preco,estoque FROM ferramentas ORDER BY nome')->fetchAll(PDO::FETCH_NUM);
        foreach($rows as &$stockRow){$stockRow[]=stockStatus((int)$stockRow[5])['label'];}unset($stockRow);
 }else{
  $columns=['ID','Data','Ferramenta','Cliente','Quantidade','Preço unitário (R$)','Total (R$)','Status','Vendedor','Cancelado em','Cancelado por','Comprovante','Forma de pagamento','Situação do pagamento','Qtd. devolvida','Qtd. líquida','Valor líquido (R$)'];
  $rows=query("SELECT v.id,v.criado_em,v.produto_nome,v.cliente,v.quantidade,v.preco_unitario,v.total,v.status,u.nome,COALESCE(v.cancelado_em,''),COALESCE(c.nome,''),COALESCE(cp.id,'Registro antigo'),COALESCE(cp.forma_pagamento,'Não informada'),COALESCE(cp.pagamento_status,'Não informado'),v.quantidade_devolvida,v.quantidade_liquida,v.total_liquido FROM ".vendasLiquidasSql()." v JOIN usuarios u ON u.id=v.usuario_id LEFT JOIN usuarios c ON c.id=v.cancelado_por LEFT JOIN comprovante_itens ci ON ci.venda_id=v.id LEFT JOIN comprovantes cp ON cp.id=ci.comprovante_id $filter ORDER BY v.id DESC",$args)->fetchAll(PDO::FETCH_NUM);
 }
}
if(($_GET['exportar']??'')==='csv'&&!$validation){
 header('Content-Type: text/csv; charset=UTF-8');header('Content-Disposition: attachment; filename="relatorio-'.$type.'-'.date('Y-m-d').'.csv"');$out=fopen('php://output','w');fwrite($out,"\xEF\xBB\xBF");fputcsv($out,$columns,';', '"', '');
 foreach($rows as $r){$r=array_map(function($v){$v=(string)$v;return preg_match('/^[\s]*[=+@-]/u',$v)?"'".$v:$v;},$r);fputcsv($out,$r,';', '"', '');}fclose($out);exit;
}
page('Relatórios');?>
<?php if(admin()):?><p class="no-print"><a class="button secondary" href="index.php?p=rankings">Abrir rankings mensais da gerência</a></p><?php endif;?>
<section class="card no-print">
<form method="get" class="form-grid"><input type="hidden" name="p" value="relatorios">
<label>Relatório<select name="tipo"><?php foreach(['vendas'=>'Vendas','estoque'=>'Estoque atual','acessos'=>'Histórico de logins'] as $k=>$v):if(!pode('report_'.$k))continue;?><option value="<?=$k?>" <?=$type===$k?'selected':''?>><?=$v?></option><?php endforeach;?></select></label>
<label>De<input type="date" name="de" value="<?=e($from)?>"></label>
<label>Até<input type="date" name="ate" value="<?=e($to)?>"></label>
<div class="form-footer"><button>Gerar relatório</button><button class="secondary" name="exportar" value="csv">Exportar CSV</button><button type="button" class="secondary" id="print-report">Imprimir / salvar PDF</button></div>
</form><p>Datas em branco consultam todo o histórico. O estoque representa a situação atual e não usa o período.</p>
</section><?php if($validation):?><div class="notice"><?=e($validation)?></div><?php endif;?>
<section class="card"><div class="toolbar"><h2><?=e(['vendas'=>'Relatório de vendas','estoque'=>'Posição atual do estoque','acessos'=>'Histórico de logins'][$type])?></h2><span><?=count($rows)?> registros</span></div><p>Emitido em <?=date('d/m/Y H:i')?> • <?=$type==='estoque'?'Estoque atual':e(($from?:'Início do histórico').' até '.($to?:'hoje'))?></p><?php if($type==='acessos'):?><p>“Não registrada” significa que não houve saída pelo botão; não confirma que a sessão está ativa. Inclui tentativas recusadas e bloqueadas.</p><?php endif;?><div class="table-wrap"><table>
<thead><tr><?php foreach($columns as $col):?><th><?=e($col)?></th><?php endforeach;?></tr></thead>
<tbody><?php foreach($rows as $r):?><tr><?php foreach($r as $idx=>$cell):?><td><?php if(($columns[$idx]??'')==='Situação'):?><span class="badge <?=$cell==='repor agora'?'critical':($cell==='precisa repor'?'warn':'')?>"><?=e($cell)?></span><?php else:?><?=e($cell)?><?php endif;?></td><?php endforeach;?></tr><?php endforeach;if(!$rows)emptyRow(max(1,count($columns)));?></tbody></table></div>
</section><?php endpage();
