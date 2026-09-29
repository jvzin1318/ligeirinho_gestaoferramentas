<?php
require __DIR__.'/app/bootstrap.php';
// Instalação inicial é local por padrão. Para o teste hospedado, use uma chave longa configurada em config.php.
$instalacaoLocal=in_array($_SERVER['REMOTE_ADDR']??'', ['127.0.0.1','::1'],true);
$configInstalacao=require __DIR__.'/config.php';
$chaveInstalacao=(string)($configInstalacao['install_key']??'');
if(!$instalacaoLocal && ($chaveInstalacao==='' || $chaveInstalacao==='TROQUE_POR_UMA_CHAVE_LONGA_ALEATORIA')){
 http_response_code(403);exit('Configure uma chave de instalação longa em config.php antes de iniciar.');
}
try {
 if(query('SELECT COUNT(*) FROM usuarios')->fetchColumn()>0)go('index.php');
 if($_SERVER['REQUEST_METHOD']==='POST'){
  checkCsrf();
  if(!$instalacaoLocal && !hash_equals($chaveInstalacao,(string)($_POST['chave_instalacao']??''))){http_response_code(403);exit('Chave de instalação incorreta.');}
  $nome=nomeAcesso((string)($_POST['nome']??''));$cargo=cargoAcesso((string)($_POST['perfil']??''));if($cargo!=='gerente')throw new RuntimeException('A primeira conta precisa ser Gerente para cadastrar os demais funcionários.');
  db()->beginTransaction();
  // Serializa a criação inicial entre duas abas do instalador.
  if(query("SELECT GET_LOCK('obrafacil_instalacao', 5)")->fetchColumn()!=1)throw new RuntimeException('Instalação em andamento. Tente novamente.');
  try {
   if(query('SELECT COUNT(*) FROM usuarios')->fetchColumn()>0)throw new RuntimeException('O gerente já foi criado.');
   cadastrarConta($nome,$cargo,(string)($_POST['senha']??''));
   db()->commit();
  } finally {query("SELECT RELEASE_LOCK('obrafacil_instalacao')");}
  flash('Gerente criado. Entre com seu nome e senha de 4 números.');go('index.php');
 }
} catch(Throwable $ex){if(isset($ex)&&$ex instanceof PDOException){$error='Importe database/banco_hospedagem.sql no phpMyAdmin e confira config.php.';}else{$error=$ex->getMessage();}if(isset($ex)&&isset($_SESSION)&&isset($error)){try{if(db()->inTransaction())db()->rollBack();}catch(Throwable $ignored){}}}
page('Primeiro acesso',false);?><section class="card"><p>Crie a primeira conta com o cargo Gerente. Depois, o gerente cadastra os demais cargos em Usuários.</p><?php if(isset($error)):?><div class="notice"><?=e($error)?></div><?php endif;?><form method="post" class="stack"><?php csrf();camposAcesso('new-password','gerente',true);if(!$instalacaoLocal):?><label>Chave de instalação<input type="password" name="chave_instalacao" required autocomplete="off"></label><?php endif;?><button>Criar gerente</button></form></section><?php endpage();
