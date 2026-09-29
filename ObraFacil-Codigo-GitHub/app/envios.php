<?php
require_once __DIR__.'/envios_service.php';
$types=tiposRelatorio();
$view=null;$data=null;
if(isset($_GET['ver'])) {
    // O gerente só acessa relatórios enviados por terceiros; cada autor acessa seus próprios rascunhos.
    $view=query('SELECT r.*,u.nome FROM relatorios_enviados r JOIN usuarios u ON u.id=r.usuario_id WHERE r.id=? AND (r.usuario_id=? OR (?=1 AND r.enviado_em IS NOT NULL))',[(int)$_GET['ver'],$_SESSION['user']['id'],admin()?1:0])->fetch();
    if(!$view){http_response_code(404);page('Relatório não encontrado');endpage();return;}
    $data=json_decode($view['conteudo'],true,512,JSON_THROW_ON_ERROR);
    if(($_GET['exportar']??'')==='csv') {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="relatorio-'.$view['id'].'.csv"');
        $out=fopen('php://output','w');fwrite($out,"\xEF\xBB\xBF");
        $csv=function(array $row)use($out):void{
            $row=array_map(function($v){$s=(string)$v;return preg_match('/^[\s]*[=+@-]/u',$s)?"'".$s:$s;},$row);
            fputcsv($out,$row,';','"','');
        };
        $csv(['Relatório',$types[$view['tipo']]]);
        $csv(['Responsável',$data['responsavel']]);
        $csv(['Período',$view['periodo_inicio'],$view['periodo_fim']]);
        $csv(['Gerado em',$view['gerado_em'],'Enviado em',$view['enviado_em']??'Não enviado']);
        $csv([$data['note']]);$csv([]);$csv($data['columns']);foreach($data['rows'] as $row)$csv($row);
        fclose($out);exit;
    }
}
$mine=query('SELECT * FROM relatorios_enviados WHERE usuario_id=? ORDER BY gerado_em DESC',[$_SESSION['user']['id']])->fetchAll();
$received=admin()?query('SELECT r.*,u.nome FROM relatorios_enviados r JOIN usuarios u ON u.id=r.usuario_id WHERE r.enviado_em IS NOT NULL ORDER BY r.enviado_em DESC')->fetchAll():[];
page(admin()?'Relatórios recebidos':'Meus relatórios');
if($view):?>
<section class="card">
<div class="toolbar"><h2><?=e($types[$view['tipo']])?></h2><span class="badge"><?=$view['enviado_em']?'Enviado':'Pronto para enviar'?></span></div>
<p>Responsável: <?=e($data['responsavel'])?> • Período: <?=e($view['periodo_inicio'])?> a <?=e($view['periodo_fim'])?></p>
<p>Gerado em <?=e($view['gerado_em'])?> • Enviado em <?=e($view['enviado_em']??'Ainda não enviado')?></p>
<p><?=e($data['note'])?></p>
<div class="table-wrap"><table><thead><tr><?php foreach($data['columns'] as $col):?><th><?=e($col)?></th><?php endforeach;?></tr></thead><tbody>
<?php foreach($data['rows'] as $row):?><tr><?php foreach($row as $idx=>$cell):?><td><?php if(($data['columns'][$idx]??'')==='Situação'):?><span class="badge <?=$cell==='repor agora'?'critical':($cell==='precisa repor'?'warn':'')?>"><?=e($cell)?></span><?php else:?><?=e($cell)?><?php endif;?></td><?php endforeach;?></tr><?php endforeach;if(!$data['rows'])emptyRow(count($data['columns']));?>
</tbody></table></div>
<div class="form-footer no-print">
<a class="button secondary" href="index.php?p=envios&ver=<?=$view['id']?>&exportar=csv">Exportar CSV</a>
<button type="button" class="secondary" id="print-report">Imprimir / salvar PDF</button>
<?php if(!$view['enviado_em'] && (int)$view['usuario_id']===(int)$_SESSION['user']['id']):?>
<form method="post"><?php csrf();?><input type="hidden" name="action" value="send_report"><input type="hidden" name="id" value="<?=$view['id']?>"><button>Enviar</button></form>
<?php endif;?><a href="index.php?p=envios">Voltar</a></div></section>
<?php endif;?>
<div class="no-print">
<section class="card"><h2>Gerar relatório</h2>
<form method="post" class="form-grid"><?php csrf();?><input type="hidden" name="action" value="generate_report">
<label>Tipo<select name="tipo" required><?php foreach($types as $key=>$label):if(!pode(str_starts_with($key,'estoque_')?'gerar_estoque':'gerar_vendas'))continue;?><option value="<?=e($key)?>"><?=e($label)?></option><?php endforeach;?></select></label>
<label>Data de referência<input type="date" name="referencia" required value="<?=date('Y-m-d')?>" max="<?=date('Y-m-d')?>"></label>
<div class="form-footer"><button>Gerar e visualizar</button></div></form>
<p>A semana vai de segunda a domingo, inclusive quando atravessa o mês. A data de referência seleciona a semana ou o mês.</p>
<p>Estoque inicial: gerar no dia 01, antes das operações. Estoque final: no último dia do mês, depois das operações. Estoque semanal: no domingo, após as operações. Vendas: gerar no fechamento do domingo ou do mês, ou posteriormente.</p>
<p>Gere após concluir os lançamentos: a cópia fica preservada desde a geração. Depois confira e clique em <b>Enviar</b>. Não há envio automático.</p>
</section>
<?php if(admin()):?><section class="card"><h2>Recebidos pela gerência</h2><p>Os relatórios enviados ficam disponíveis a todos os usuários com perfil Gerente.</p><div class="table-wrap"><table><thead><tr><th>Relatório</th><th>Responsável</th><th>Período</th><th>Enviado em</th><th>Ação</th></tr></thead><tbody>
<?php foreach($received as $r):?><tr><td><?=e($types[$r['tipo']])?></td><td><?=e($r['nome'])?></td><td><?=e($r['periodo_inicio'])?> a <?=e($r['periodo_fim'])?></td><td><?=e($r['enviado_em'])?></td><td><a href="index.php?p=envios&ver=<?=$r['id']?>">Abrir</a></td></tr><?php endforeach;if(!$received)emptyRow(5);?>
</tbody></table></div></section><?php endif;?>
<section class="card"><h2>Meus relatórios gerados</h2><div class="table-wrap"><table><thead><tr><th>Tipo</th><th>Período</th><th>Status</th><th>Ação</th></tr></thead><tbody>
<?php foreach($mine as $r):?><tr><td><?=e($types[$r['tipo']])?></td><td><?=e($r['periodo_inicio'])?> a <?=e($r['periodo_fim'])?></td><td><?=$r['enviado_em']?'Enviado':'Pronto para enviar'?></td><td><a href="index.php?p=envios&ver=<?=$r['id']?>">Abrir<?=$r['enviado_em']?'':' e enviar'?></a></td></tr><?php endforeach;if(!$mine)emptyRow(4);?>
</tbody></table></div></section></div><?php endpage();
