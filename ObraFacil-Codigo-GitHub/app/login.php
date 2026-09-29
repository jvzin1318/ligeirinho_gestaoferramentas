<?php
if($_SERVER['REQUEST_METHOD']==='POST'){
 checkCsrf();$nome=trim((string)($_POST['nome']??''));$password=(string)($_POST['senha']??'');$ip=substr($_SERVER['REMOTE_ADDR']??'',0,45);
 $attempts=query("SELECT COUNT(*) FROM acessos WHERE ip=? AND resultado<>'Sucesso' AND entrada>DATE_SUB(NOW(),INTERVAL 15 MINUTE)",[$ip])->fetchColumn();
 $user=null;try{$user=contaPorNome($nome);}catch(RuntimeException $ignored){}
 // Hash de comparação para não pular o custo da verificação quando o usuário não existe.
 $hash=$user['senha']??'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
 $valid=conferirSenha($password,$hash);$ok=$attempts<10&&$user&&$valid;
 query('INSERT INTO acessos (usuario_id,email,resultado,ip) VALUES (?,?,?,?)',[$user?$user['id']:null,substr($nome,0,190),$attempts>=10?'Bloqueado':($ok?'Sucesso':'Recusado'),$ip]);
 if($ok){
  $access=db()->lastInsertId();
  if(password_needs_rehash($user['senha'],PASSWORD_DEFAULT))query('UPDATE usuarios SET senha=? WHERE id=?',[password_hash($password,PASSWORD_DEFAULT),$user['id']]);
  session_regenerate_id(true);$_SESSION['csrf']=bin2hex(random_bytes(32));$_SESSION['user']=['id'=>$user['id'],'nome'=>$user['nome'],'perfil'=>$user['perfil']];$_SESSION['access_id']=$access;go('index.php');
 }
 flash($attempts>=10?'Muitas tentativas. Aguarde 15 minutos sem tentar e entre novamente.':'Nome ou senha incorretos.');go('index.php');
}
page('Acesso dos funcionários',false);?>
<section class="card"><div class="login-brand">OF <span>ObraFácil</span></div><p>Informe seu nome e digite a senha de 4 números cadastrada pelo gerente.</p>
<form method="post" class="stack"><?php csrf();camposAcesso();?><button>Entrar</button></form>
<details><summary>Conta criada na versão anterior?</summary><p>Se sua senha antiga tem mais de 4 caracteres, <a href="atualizar_acesso.php">atualize sua senha</a> no computador da instalação.</p></details>
</section><?php endpage();
