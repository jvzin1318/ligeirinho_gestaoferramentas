<?php
$rows=query('SELECT id,nome,perfil FROM usuarios ORDER BY nome')->fetchAll();page('Usuários');?>
<section class="card"><h2>Cadastrar funcionário</h2><p>Você define o nome, o cargo e uma senha de exatamente 4 números. Use nomes diferentes para cada funcionário, por exemplo João e João Silva. A senha é salva protegida e não aparece na listagem.</p>
<form method="post" class="form-grid"><?php csrf();?><input type="hidden" name="action" value="user">
<?php camposAcesso('new-password','',true);?>
<div class="form-footer"><button>Cadastrar usuário</button></div>
</form><p>Vendedores registram vendas, consultam suas próprias vendas e enviam relatórios semanais e mensais à gerência. Estoquistas cadastram e atualizam ferramentas e estoque, com relatórios inicial, final e semanal enviados à gerência. Gerentes têm acesso geral, incluindo exclusões, cancelamentos, usuários e todos os relatórios.</p>
</section>
<section class="card"><div class="table-wrap"><table>
<thead><tr><th>Nome</th><th>Cargo</th></tr></thead>
<tbody><?php foreach($rows as $r):?><tr><td><?=e($r['nome'])?></td><td><?=e(cargosAcesso()[$r['perfil']]??$r['perfil'])?></td></tr><?php endforeach;?></tbody></table></div>
</section><?php endpage();
