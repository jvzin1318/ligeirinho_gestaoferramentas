<?php
$term=substr(trim((string)($_GET['q']??'')),0,150);$rows=query('SELECT * FROM ferramentas WHERE nome LIKE ? OR marca LIKE ? OR categoria LIKE ? ORDER BY nome',array_fill(0,3,'%'.$term.'%'))->fetchAll();
$suppliers=query('SELECT * FROM fornecedores WHERE ativo=1 ORDER BY nome')->fetchAll();
$commercial=query('SELECT c.*,s.nome fornecedor FROM ferramenta_comercial c JOIN fornecedores s ON s.id=c.fornecedor_id')->fetchAll(PDO::FETCH_UNIQUE);
$edit=null;if(pode('save_product')&&isset($_GET['editar']))$edit=query('SELECT * FROM ferramentas WHERE id=?',[(int)$_GET['editar']])->fetch();
$pack=$edit?($commercial[$edit['id']]??[]):[];
page('Ferramentas');?>
<div class="toolbar"><p>Consulte preços, acompanhe o estoque e organize o catálogo.</p><?php if(pode('save_product')):?><a class="button" href="#formulario">+ Cadastrar ferramenta</a><?php endif;?></div>
<div class="catalog-meta"><span><?=count($rows)?> ferramentas encontradas</span><span>6–10 un.: precisa repor · 0–5 un.: repor agora</span></div>
<section class="card">
<form class="search" method="get"><input type="hidden" name="p" value="ferramentas">
<label>Buscar ferramenta<input name="q" value="<?=e($term)?>" placeholder="Nome, marca ou categoria"></label><button>Buscar</button><a href="index.php?p=ferramentas">Limpar</a>
</form><div class="table-wrap"><table>
<thead><tr><th>Ferramenta</th><th>Marca / categoria</th><th>Preço</th><th>Estoque</th><th>Situação</th><?php if(pode('save_product')):?><th>Ações</th><?php endif;?></tr></thead>
<tbody><?php foreach($rows as $r):?><tr><td><b><?=e($r['nome'])?></b><small>#<?=$r['id']?> · <?=e($commercial[$r['id']]['fornecedor']??'Fornecedor pendente')?></small></td><td><?=e($r['marca'])?><small><?=e($r['categoria'])?></small></td><td><?=money($r['preco'])?></td><td><?=$r['estoque']?> un.</td><td><?=stockBadge((int)$r['estoque'])?></td><?php if(pode('save_product')):?><td><div class="row-actions"><a href="index.php?p=ferramentas&editar=<?=$r['id']?>#formulario">Editar</a>
<?php if(pode('delete_product')):?>
<form method="post" data-confirm="Excluir esta ferramenta?"><?php csrf();?><input type="hidden" name="action" value="delete_product"><input type="hidden" name="id" value="<?=$r['id']?>"><button class="danger">Excluir</button>
</form><?php endif;?></div></td><?php endif;?></tr><?php endforeach;if(!$rows)emptyRow(pode('save_product')?6:5);?></tbody></table></div>
</section>
<?php if(pode('save_product')):?>
<section class="card" id="formulario"><h2><?=$edit?'Editar ferramenta':'Cadastrar ferramenta'?></h2>
<form method="post" class="form-grid"><?php csrf();?><input type="hidden" name="action" value="save_product"><input type="hidden" name="id" value="<?=$edit['id']??0?>"><input type="hidden" name="estoque_original" value="<?=$edit['estoque']??0?>">
<label>Nome<input name="nome" maxlength="150" required value="<?=e($edit['nome']??'')?>"></label>
<label>Marca<input name="marca" maxlength="100" required value="<?=e($edit['marca']??'')?>"></label>
<label>Categoria<input name="categoria" maxlength="100" placeholder="Ex.: Ferramentas elétricas" required value="<?=e($edit['categoria']??'')?>"></label>
<label>Preço de venda (R$)<input name="preco" inputmode="decimal" placeholder="0,00" required value="<?=e($edit['preco']??'')?>"></label>
<label>Estoque atual<input type="number" name="estoque" min="0" max="1000000" required value="<?=$edit['estoque']??0?>"></label>
<label>Motivo do ajuste de estoque<input name="motivo_estoque" maxlength="255" placeholder="Obrigatório quando alterar o estoque"></label>
<label>Fornecedor<select name="fornecedor_id" required><option value="">Selecione</option><?php foreach($suppliers as $supplier):?><option value="<?=$supplier['id']?>" <?=($pack['fornecedor_id']??0)==$supplier['id']?'selected':''?>><?=e($supplier['nome'])?></option><?php endforeach;?></select></label>
<p>O estoque exibido é o disponível. Para registrar reposição comprada do fornecedor, use Compras e entradas; alterar o estoque aqui é apenas um ajuste e não alimenta o ranking de fornecedores.</p><p>Alertas automáticos: até 10 unidades, amarelo; até 5 unidades, vermelho.</p>
<div class="form-footer"><button>Salvar ferramenta</button><?php if($edit):?><a href="index.php?p=ferramentas">Cancelar edição</a><?php endif;?></div>
</form>
</section><?php endif;endpage();
