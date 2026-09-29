<?php
requireAdmin();require_once __DIR__.'/rankings_service.php';
$type=(string)($_GET['tipo']??'funcionarios');$month=(string)($_GET['mes']??date('Y-m'));$result=null;$error='';
try{$result=rankingMensal($type,$month);}catch(RuntimeException $ex){$error=$ex instanceof PDOException?'Não foi possível consultar. Confira as migrações do banco.':$ex->getMessage();}
if(($_GET['exportar']??'')==='csv'&&$result){
 header('Content-Type: text/csv; charset=UTF-8');header('Content-Disposition: attachment; filename="ranking-'.$type.'-'.$month.'.csv"');$out=fopen('php://output','w');fwrite($out,"\xEF\xBB\xBF");fputcsv($out,$result['columns'],';','"','');
 foreach($result['rows'] as $row){$row=array_map(fn($v)=>preg_match('/^[\s]*[=+@-]/u',(string)$v)?"'".$v:(string)$v,$row);fputcsv($out,$row,';','"','');}fclose($out);exit;
}
page('Rankings mensais da gerência');?>
<section class="card no-print"><form method="get" class="form-grid"><input type="hidden" name="p" value="rankings"><label>Relatório<select name="tipo"><?php foreach(tiposRanking() as $key=>$label):?><option value="<?=$key?>" <?=$type===$key?'selected':''?>><?=e($label)?></option><?php endforeach;?></select></label>
<label>Mês e ano<input type="month" name="mes" value="<?=e($month)?>" min="1900-01" max="<?=date('Y-m')?>" required></label>
<div class="form-footer"><button>Gerar relatório</button><button class="secondary" name="exportar" value="csv">Exportar CSV</button><button type="button" class="secondary" id="print-report">Imprimir / salvar PDF</button></div></form>
<p>Funcionários, fornecedores e clientes são ordenados por valor em reais. Ferramentas são ordenadas por quantidade vendida. Todos os rankings são exclusivos do gerente.</p></section>
<?php if($error):?><p class="notice"><?=e($error)?></p><?php endif;?>
<?php if($result):?><section class="card"><h2><?=e(tiposRanking()[$type])?></h2><p>Período: <?=e(date('d/m/Y',strtotime($result['inicio'])))?> a <?=e(date('d/m/Y',strtotime($result['fim'])))?>. <?=$month===date('Y-m')?'Mês em andamento: resultados parciais.':''?> Gerado em <?=date('d/m/Y H:i')?>.</p><p><?=e($result['note'])?></p>
<div class="table-wrap"><table><thead><tr><?php foreach($result['columns'] as $c):?><th><?=e($c)?></th><?php endforeach;?></tr></thead><tbody><?php foreach($result['rows'] as $r):?><tr><?php foreach($r as $i=>$cell):?><td><?=str_contains($result['columns'][$i],'(R$)')?e(money($cell)):e($cell)?></td><?php endforeach;?></tr><?php endforeach;if(!$result['rows'])emptyRow(count($result['columns']));?></tbody></table></div>
<?php if(!$result['rows']):?><p>Não há movimentações elegíveis neste mês. Registre vendas ou compras para alimentar o relatório.</p><?php endif;?></section><?php endif;endpage();
