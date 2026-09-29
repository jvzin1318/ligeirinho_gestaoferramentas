<?php
require __DIR__.'/app/bootstrap.php';
if(!in_array($_SERVER['REMOTE_ADDR']??'', ['127.0.0.1','::1'],true)){http_response_code(403);exit('Atualize sua conta pelo localhost no computador da instalação.');}
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 checkCsrf();$nome=(string)($_POST['nome']??'');$antiga=(string)($_POST['antiga']??'');$nova=(string)($_POST['senha']??'');$ip=substr($_SERVER['REMOTE_ADDR']??'',0,45);
 try {
  $attempts=query("SELECT COUNT(*) FROM acessos WHERE ip=? AND resultado<>'Sucesso' AND entrada>DATE_SUB(NOW(),INTERVAL 15 MINUTE)",[$ip])->fetchColumn();
  $user=null;try{$user=contaPorNome($nome);}catch(RuntimeException $ignored){}
  $hash=$user['senha']??'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
  $valid=$antiga!==''&&strlen($antiga)<=72&&!str_contains($antiga,"\0")&&password_verify($antiga,$hash);
  if($attempts>=10||!$user||!$valid){
   query('INSERT INTO acessos (usuario_id,email,resultado,ip) VALUES (?,?,?,?)',[$user?$user['id']:null,substr($nome,0,190),$attempts>=10?'Bloqueado':'Recusado',$ip]);
   throw new RuntimeException($attempts>=10?'Aguarde 15 minutos sem novas tentativas.':'Nome ou senha antiga incorretos.');
  }
  $newHash=hashNovaSenha($nova);
  $changed=query('UPDATE usuarios SET senha=? WHERE id=? AND senha=?',[$newHash,$user['id'],$user['senha']]);
  if($changed->rowCount()!==1)throw new RuntimeException('A conta foi alterada. Tente novamente.');
  flash('Senha atualizada. Entre com seu nome e a nova senha de 4 números.');go('index.php');
 } catch(Throwable $ex){$error=$ex instanceof PDOException?'Confira a conexão e as tabelas do banco.':$ex->getMessage();}
}
page('Atualizar conta anterior',false);?>
<section class="card"><p>Use o nome salvo no cadastro. Confirme a senha antiga para escolher uma nova senha de 4 números. Seus registros serão preservados.</p>
<?php if($error):?><p class="notice"><?=e($error)?></p><?php endif;?>
<form method="post" class="stack"><?php csrf();camposAcesso('new-password');?><label>Senha antiga<input name="antiga" type="password" maxlength="72" required autocomplete="current-password"></label><button>Atualizar senha</button></form><p><a href="index.php">Voltar ao login</a></p></section><?php endpage();
