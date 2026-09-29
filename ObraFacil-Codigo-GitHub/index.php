<?php
require __DIR__.'/app/bootstrap.php';
try {db();$count=query('SELECT COUNT(*) FROM usuarios')->fetchColumn();}catch(Throwable $ex){error_log($ex->getMessage());http_response_code(503);page('Configure o banco de dados',false);echo '<section class="card"><p>Inicie o MySQL, importe <b>database/banco.sql</b> no phpMyAdmin e ajuste a conexão em <b>config.php</b>.</p><a class="button" href="index.php">Tentar novamente</a></section>';endpage();exit;}
if(!$count)go('instalar.php');
if(!isset($_SESSION['user'])){require __DIR__.'/app/login.php';exit;}
// Recarrega o perfil para aplicar alterações mesmo em sessões já abertas.
$current=query('SELECT id,nome,perfil FROM usuarios WHERE id=?',[$_SESSION['user']['id']])->fetch();
if(!$current){$_SESSION=[];session_regenerate_id(true);go('index.php');}
$_SESSION['user']=$current;
try {query('SELECT id FROM compras_documentos LIMIT 1');query('SELECT id FROM sessoes_caixa LIMIT 1');}catch(PDOException $ex){http_response_code(503);page('Atualize o banco de dados');echo '<section class="card"><p>Faça backup e, com o sistema parado, importe <b>database/atualizar_operacoes.sql</b> no banco ferramentas pelo phpMyAdmin. Depois recarregue esta página.</p></section>';endpage();exit;}
require __DIR__.'/app/actions.php';
$p=(string)($_GET['p']??paginaInicial());
$pages=['turnos','movimentacoes','devolucoes','rankings','compras','comprovante','pedidos','clientes','fornecedores','integracoes','envios','inicio','ferramentas','vendas','relatorios','usuarios'];
if(!in_array($p,$pages,true)){http_response_code(404);page('Página não encontrada');endpage();exit;}
exigirPermissao($p);
require __DIR__.'/app/'.$p.'.php';
