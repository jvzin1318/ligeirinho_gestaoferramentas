<?php
$rows=query('SELECT * FROM fornecedores ORDER BY ativo DESC,nome')->fetchAll();
$edit=isset($_GET['editar'])?query('SELECT * FROM fornecedores WHERE id=?',[(int)$_GET['editar']])->fetch():null;
page('Fornecedores');?>
<section class="card"><h2><?=$edit?'Editar fornecedor':'Cadastrar fornecedor'?></h2>
<form method="post" class="form-grid" data-company-form><?php csrf();?><input type="hidden" name="action" value="save_supplier"><input type="hidden" name="id" value="<?=$edit['id']??0?>">
<label>CNPJ (opcional)<input name="cnpj" data-cnpj value="<?=e($edit['cnpj']??'')?>" maxlength="18" placeholder="00.000.000/0000-00"></label><div class="company-action"><button type="button" class="secondary" data-cnpj-search>Consultar CNPJ</button></div><p data-cnpj-status role="status" aria-live="polite">A consulta preenche os dados disponíveis.</p>
<label>Nome / razão social<input name="nome" data-company-name required maxlength="150" value="<?=e($edit['nome']??'')?>"></label>
<label>E-mail<input type="email" name="email" data-company-email maxlength="190" value="<?=e($edit['email']??'')?>"></label>
<label>Telefone<input name="telefone" data-company-phone maxlength="30" value="<?=e($edit['telefone']??'')?>"></label>
<label>Endereço<input name="endereco" data-company-address maxlength="255" value="<?=e($edit['endereco']??'')?>"></label><div class="form-footer"><button>Salvar fornecedor</button><a href="index.php?p=fornecedores">Limpar</a></div></form></section>
<section class="card"><div class="table-wrap"><table><thead><tr><th>Fornecedor</th><th>CNPJ</th><th>Contato</th><th>Status</th><th>Ações</th></tr></thead><tbody>
<?php foreach($rows as $r):?><tr><td><?=e($r['nome'])?><small><?=e($r['endereco'])?></small></td><td><?=e($r['cnpj']??'Não informado')?></td><td><?=e($r['email'])?><small><?=e($r['telefone'])?></small></td><td><?=$r['ativo']?'Ativo':'Inativo'?></td><td><a href="index.php?p=fornecedores&editar=<?=$r['id']?>">Editar</a><?php if(admin()):?><form method="post"><?php csrf();?><input type="hidden" name="action" value="supplier_active"><input type="hidden" name="id" value="<?=$r['id']?>"><input type="hidden" name="ativo" value="<?=$r['ativo']?0:1?>"><button class="secondary"><?=$r['ativo']?'Inativar':'Ativar'?></button></form><?php endif;?></td></tr><?php endforeach;if(!$rows)emptyRow(5);?></tbody></table></div></section><?php endpage();
