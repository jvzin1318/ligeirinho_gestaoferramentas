<?php
function cargosAcesso(): array {
 return ['gerente'=>'Gerente','vendedor'=>'Vendedor','estoquista'=>'Estoquista'];
}
function nomeAcesso(string $nome): string {
 $nome=preg_replace('/\s+/u',' ',trim($nome));
 if($nome===null||$nome===''||strlen($nome)>120)throw new RuntimeException('Informe um nome de até 120 bytes.');
 return $nome;
}
function cargoAcesso(string $cargo): string {
 if(!isset(cargosAcesso()[$cargo]))throw new RuntimeException('Selecione um cargo válido.');
 return $cargo;
}
function senhaQuatroDigitos(string $senha): bool {return preg_match('/^[0-9]{4}$/D',$senha)===1;}
function hashNovaSenha(string $senha): string {
 if(!senhaQuatroDigitos($senha))throw new RuntimeException('A senha deve conter exatamente 4 números, de 0 a 9.');
 return password_hash($senha,PASSWORD_DEFAULT);
}
function conferirSenha(string $senha,string $hash): bool {
 return senhaQuatroDigitos($senha)&&password_verify($senha,$hash);
}
function contaPorNome(string $nome): ?array {
 $nome=nomeAcesso($nome);
 $rows=query('SELECT * FROM usuarios WHERE nome=? LIMIT 2',[$nome])->fetchAll();
 // Nunca escolhe arbitrariamente entre contas antigas com nomes iguais.
 return count($rows)===1?$rows[0]:null;
}
function cadastrarConta(string $nome,string $cargo,string $senha): void {
 $nome=nomeAcesso($nome);$cargo=cargoAcesso($cargo);$hash=hashNovaSenha($senha);
 if(query("SELECT GET_LOCK('obrafacil_cadastro_conta',5)")->fetchColumn()!=1)throw new RuntimeException('Outro cadastro está em andamento. Tente novamente.');
 try {
  if(query('SELECT id FROM usuarios WHERE nome=? LIMIT 1',[$nome])->fetch())throw new RuntimeException('Já existe uma conta com esse nome. Acrescente um sobrenome para diferenciar o funcionário.');
  // Coluna histórica mantida para compatibilidade. Não é mais usada no login.
  query('INSERT INTO usuarios (nome,login,senha,perfil) VALUES (?,?,?,?)',[$nome,'conta_'.bin2hex(random_bytes(16)),$hash,$cargo]);
 } finally {query("SELECT RELEASE_LOCK('obrafacil_cadastro_conta')");}
}
function camposAcesso(string $autocomplete='current-password',string $cargo='',bool $mostrarCargo=false): void { ?>
<label>Nome<input name="nome" required maxlength="120" autocomplete="username" placeholder="Nome cadastrado"></label>
<?php if($mostrarCargo):?><label>Cargo<select name="perfil" required><option value="">Selecione o cargo</option><?php foreach(cargosAcesso() as $key=>$label):?><option value="<?=e($key)?>" <?=$cargo===$key?'selected':''?>><?=e($label)?></option><?php endforeach;?></select></label><?php endif;?>
<label>Senha de 4 dígitos<input type="password" name="senha" required minlength="4" maxlength="4" pattern="[0-9]{4}" inputmode="numeric" autocomplete="<?=e($autocomplete)?>" title="Digite exatamente 4 números" placeholder="4 números"></label>
<?php }
