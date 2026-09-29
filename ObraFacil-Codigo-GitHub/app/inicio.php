<?php
$stats=query("SELECT COUNT(*) produtos,COALESCE(SUM(estoque),0) unidades,COALESCE(SUM(estoque<=10),0) baixo FROM ferramentas")->fetch();
$revenue=query("SELECT COALESCE(SUM(total_liquido),0) FROM ".vendasLiquidasSql()." v WHERE v.status='Concluída' AND v.criado_em>=CURDATE()")->fetchColumn();
$low=query('SELECT * FROM ferramentas WHERE estoque<=10 ORDER BY estoque,nome')->fetchAll();
page('Visão geral');?><div class="toolbar"><p>Acompanhe a operação da sua loja.</p><a class="button" href="index.php?p=vendas">+ Registrar venda</a></div><div class="stats"><article><small>Vendas de hoje (inclui a receber)</small><strong><?=money($revenue)?></strong></article><article><small>Ferramentas cadastradas</small><strong><?=$stats['produtos']?></strong></article><article><small>Unidades em estoque</small><strong><?=$stats['unidades']?></strong></article><article class="accent"><small>Estoque em atenção</small><strong><?=$stats['baixo']?></strong></article></div>
<section class="card"><div class="toolbar"><h2>Reposição de estoque</h2><a href="index.php?p=ferramentas">Ver ferramentas →</a></div><div class="table-wrap"><table>
<thead><tr><th>Ferramenta</th><th>Marca</th><th>Estoque</th><th>Situação</th></tr></thead>
<tbody><?php foreach($low as $r):?><tr><td><?=e($r['nome'])?></td><td><?=e($r['marca'])?></td><td><?=$r['estoque']?> un.</td><td><?=stockBadge((int)$r['estoque'])?></td></tr><?php endforeach;if(!$low):?><tr><td colspan="4" class="empty"><?=$stats['produtos']?'Nenhuma ferramenta precisa de reposição.':'Cadastre sua primeira ferramenta para começar.'?></td></tr><?php endif;?></tbody></table></div>
</section><?php endpage();
