<?php
require_once __DIR__.'/pedidos_service.php';
$view=null;if(isset($_GET['ver'])){try{$view=pedidoPermitido((int)$_GET['ver']);}catch(Throwable $ex){http_response_code(404);exit('Pedido não encontrado.');}}
$rows=query('SELECT * FROM pedidos WHERE ?=1 OR usuario_id=? ORDER BY id DESC LIMIT 100',[admin()?1:0,$_SESSION['user']['id']])->fetchAll();
page('Pedidos anteriores');echo '<p>Histórico da versão anterior. Novas vendas são registradas no <a href="index.php?p=vendas">caixa presencial</a>.</p>';
if($view):$client=json_decode($view['cliente_snapshot'],true);?>
<section class="card"><h2>Pedido #<?=$view['id']?> — <?=e($view['status'])?></h2><p><?=e($client['nome'])?> · <?=$client['tipo']==='PJ'?'Empresa':'Pessoa física'?> · <?=e($client['documento'])?></p><p>Criado em <?=e($view['criado_em'])?> · Pagamento: <?=e($view['pagamento_status']??'Não confirmado')?> · Meio: <?=e($view['meio_pagamento']??'Ainda não escolhido')?></p>
<div class="table-wrap"><table><thead><tr><th>Ferramenta</th><th>Quantidade</th><th>Preço unitário</th></tr></thead><tbody><?php foreach(query('SELECT * FROM pedido_itens WHERE pedido_id=?',[$view['id']])->fetchAll() as $item):?><tr><td><?=e($item['nome'])?></td><td><?=$item['quantidade']?></td><td><?=money($item['preco'])?></td></tr><?php endforeach;?></tbody></table></div>
<p>Total do pedido: <?=money($view['total'])?></p>
<div class="form-footer">
<?php if($view['status']!=='Cancelado'):?><form method="post"><?php csrf();?><input type="hidden" name="id" value="<?=$view['id']?>"><button class="secondary" name="action" value="sync_order">Consultar pagamento</button></form><?php endif;?>
<?php if(admin()&&!$view['checkout_tentado_em']&&$view['status']!=='Cancelado'):?><form method="post" data-confirm="Cancelar este pedido e devolver a reserva de estoque?"><?php csrf();?><input type="hidden" name="id" value="<?=$view['id']?>"><button class="danger" name="action" value="cancel_order">Cancelar pedido</button></form><?php endif;?></div>
<p>O pedido reserva estoque. Só a confirmação do provedor registra a venda nos relatórios. Estornos e contestações exigem revisão do gerente.</p></section>
<?php endif;?>
<section class="card"><h2><?=admin()?'Últimos pedidos':'Meus últimos pedidos'?></h2><div class="table-wrap"><table><thead><tr><th>Pedido</th><th>Cliente</th><th>Total</th><th>Status</th><th>Ação</th></tr></thead><tbody><?php foreach($rows as $r):$client=json_decode($r['cliente_snapshot'],true);?><tr><td>#<?=$r['id']?><small><?=e($r['criado_em'])?></small></td><td><?=e($client['nome'])?></td><td><?=money($r['total'])?></td><td><?=e($r['status'])?></td><td><a href="index.php?p=pedidos&ver=<?=$r['id']?>">Abrir</a></td></tr><?php endforeach;if(!$rows)emptyRow(5);?></tbody></table></div></section><?php endpage();
