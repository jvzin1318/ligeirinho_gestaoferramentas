<?php
require_once __DIR__.'/caixa_service.php';$legacy=false;
try{
 if(isset($_GET['venda'])){
  $vid=(int)$_GET['venda'];$r=query('SELECT v.*,u.nome vendedor FROM vendas v JOIN usuarios u ON u.id=v.usuario_id WHERE v.id=? AND (?=1 OR v.usuario_id=?)',[$vid,admin()?1:0,$_SESSION['user']['id']])->fetch();
  if(!$r)throw new RuntimeException('Não encontrado.');
  $linked=query('SELECT comprovante_id FROM comprovante_itens WHERE venda_id=?',[$vid])->fetchColumn();if($linked)go('index.php?p=comprovante&id='.$linked);
  $legacy=true;$items=[$r];$c=['id'=>$vid,'cliente_nome'=>$r['cliente'],'cliente_documento'=>'','vendedor_nome'=>$r['vendedor'],'total'=>$r['total'],'criado_em'=>$r['criado_em'],'status'=>$r['status'],'forma_pagamento'=>'Não informada no registro antigo','pagamento_status'=>'Não informado','referencia_pagamento'=>'','cancelado_em'=>$r['cancelado_em']];
  $loja=['nome'=>'ObraFácil — registro histórico','documento'=>'','endereco'=>'','telefone'=>''];
 }else{
  $c=comprovantePermitido((int)($_GET['id']??0));$loja=json_decode($c['loja_snapshot'],true,512,JSON_THROW_ON_ERROR);
  $items=query('SELECT v.* FROM vendas v JOIN comprovante_itens ci ON ci.venda_id=v.id WHERE ci.comprovante_id=? ORDER BY v.id',[$c['id']])->fetchAll();
 }
}catch(Throwable $ex){http_response_code(404);page('Comprovante não encontrado');echo '<p>Confira o número e sua permissão de acesso.</p>';endpage();return;}
$returns=$legacy?[]:query('SELECT * FROM devolucoes WHERE comprovante_id=? OR novo_comprovante_id=? ORDER BY id',[$c['id'],$c['id']])->fetchAll();$liquido=$legacy?$c['total']:query("SELECT COALESCE(SUM(v.total_liquido),0) FROM ".vendasLiquidasSql()." v JOIN comprovante_itens ci ON ci.venda_id=v.id WHERE ci.comprovante_id=?",[$c['id']])->fetchColumn();
page('Comprovante de venda');?>
<div class="toolbar no-print"><a href="index.php?p=vendas">Voltar ao caixa</a><button id="print-report" type="button">Imprimir / salvar PDF</button></div>
<article class="card receipt">
<div class="receipt-heading"><h2><?=e($loja['nome'])?></h2><?php foreach(['documento','endereco','telefone'] as $field):if(!empty($loja[$field])):?><p><?=e($loja[$field])?></p><?php endif;endforeach;?><h3>NOTA DE VENDA DEMONSTRATIVA</h3><strong>SEM VALOR FISCAL — NÃO É NOTA FISCAL</strong></div>
<p><b><?=$legacy?'Registro antigo':'Venda presencial'?> #<?=str_pad((string)$c['id'],6,'0',STR_PAD_LEFT)?></b> · <?=e(date('d/m/Y H:i:s',strtotime($c['criado_em'])))?></p>
<?php if($c['status']==='Cancelada'):?><p class="receipt-cancelled"><b>VENDA CANCELADA</b> · <?=e($c['cancelado_em'])?>. Este documento não confirma uma venda vigente.</p><?php endif;?>
<p>Funcionário: <?=e($c['vendedor_nome'])?><br>Cliente: <?=e($c['cliente_nome'])?><?php if($c['cliente_documento']!==''):?><br>CPF/CNPJ: <?=e($c['cliente_documento'])?><?php endif;?></p>
<div class="table-wrap"><table><thead><tr><th>Item</th><th>Qtd.</th><th>Unitário</th><th>Total</th></tr></thead><tbody><?php foreach($items as $t):?><tr><td><?=e($t['produto_nome'])?></td><td><?=$t['quantidade']?></td><td><?=money($t['preco_unitario'])?></td><td><?=money($t['total'])?></td></tr><?php endforeach;?></tbody></table></div>
<p class="receipt-total"><b>Total da venda: <?=money($c['total'])?></b></p>
<p>Forma: <?=e($c['forma_pagamento'])?><br>Situação do pagamento: <b><?=e($c['pagamento_status'])?></b><?php if($c['referencia_pagamento']!==''):?><br>Referência: <?=e($c['referencia_pagamento'])?><?php endif;?><?php if(!empty($c['pagamento_confirmado_em'])):?><br>Recebimento registrado em: <?=e($c['pagamento_confirmado_em'])?><?php endif;?></p>
<?php if($c['pagamento_status']==='A receber'):?><p><b><?=centavos($liquido)>0?'PAGAMENTO PENDENTE':'SEM SALDO A RECEBER APÓS DEVOLUÇÃO'?>.</b> Saldo: <?=money($liquido)?>. Confira a baixa/adequação do boleto no banco.</p><?php endif;?>
<?php if($c['status']==='Cancelada'):?><p>O cancelamento neste sistema devolve estoque. Eventual estorno bancário deve ser conferido separadamente.</p><?php endif;?>
<?php if($returns):?><p><b>Valor dos itens não devolvidos: <?=money($liquido)?></b></p><?php foreach($returns as $d):?><p><?=e($d['tipo'])?> #<?=$d['id']?> · Venda original #<?=$d['comprovante_id']?> · Novos itens #<?=e($d['novo_comprovante_id']??'—')?> · Devolvido: <?=money($d['total'])?> · <?=e($d['motivo'])?></p><?php endforeach;endif;?>
<p class="receipt-footer">Documento demonstrativo do projeto para conferência dos itens e valores. Sem validade fiscal. Reimpressão em <?=date('d/m/Y H:i:s')?>.</p>
</article>
<?php if(!$legacy&&admin()&&$c['status']==='Concluída'):?><section class="card no-print"><h2>Gerenciar venda</h2><p><a class="button secondary" href="index.php?p=devolucoes&venda=<?=$c['id']?>">Registrar devolução / troca</a></p><div class="form-footer">
<?php if($c['forma_pagamento']==='Boleto'&&$c['pagamento_status']==='A receber'):?><form method="post" data-confirm="O recebimento deste boleto foi conferido no banco? Esta ação apenas registra a baixa."><?php csrf();?><input type="hidden" name="id" value="<?=$c['id']?>"><button name="action" value="receive_boleto">Registrar recebimento do boleto</button></form><?php endif;?>
<form method="post" data-confirm="Cancelar toda a venda e devolver os itens ao estoque? Confira o estorno bancário separadamente."><?php csrf();?><input type="hidden" name="id" value="<?=$c['id']?>"><label class="check-label"><input type="checkbox" name="estorno_conferido" value="1"> Conferi o estorno do valor recebido</label><button class="danger" name="action" value="cancel_receipt">Cancelar venda completa</button></form></div></section><?php endif;endpage();
