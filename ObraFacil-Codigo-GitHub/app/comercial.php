<?php
function centavos($value): int {
 $s=(string)$value;if(!preg_match('/^([0-9]{1,12})(?:\.([0-9]{1,2}))?$/D',$s,$m))throw new RuntimeException('Valor monetário inválido.');
 return (int)$m[1]*100+(int)str_pad($m[2]??'',2,'0');
}
function decimalValor(int $c): string {return intdiv($c,100).'.'.str_pad((string)($c%100),2,'0',STR_PAD_LEFT);}
function validarDocumento(string $doc,string $tipo): string {
 $v=strtoupper(preg_replace('/[.\/\-\s]/','',$doc));
 if($tipo==='PJ') {
  // Suporta CNPJ numérico e alfanumérico (12 posições + 2 dígitos).
  if(!preg_match('/^[A-Z0-9]{12}[0-9]{2}$/D',$v)||preg_match('/^(.)\1+$/D',$v))throw new RuntimeException('CNPJ inválido.');
  $nums=array_map(fn($c)=>ord($c)-48,str_split(substr($v,0,12)));
  foreach([[5,4,3,2,9,8,7,6,5,4,3,2],[6,5,4,3,2,9,8,7,6,5,4,3,2]] as $weights){$sum=0;foreach($weights as $i=>$w)$sum+=$nums[$i]*$w;$rem=$sum%11;$nums[]=$rem<2?0:11-$rem;}
  if($nums[12]!=(int)$v[12]||$nums[13]!=(int)$v[13])throw new RuntimeException('CNPJ inválido.');
 }else{
  if(!preg_match('/^[0-9]{11}$/D',$v)||preg_match('/^(.)\1+$/D',$v))throw new RuntimeException('CPF inválido.');
  for($n=9;$n<11;$n++){$sum=0;for($i=0;$i<$n;$i++)$sum+=(int)$v[$i]*($n+1-$i);$d=($sum*10)%11;if($d===10)$d=0;if($d!==(int)$v[$n])throw new RuntimeException('CPF inválido.');}
 }
 return $v;
}
function campoOpcional(string $name,int $max): string {$v=trim((string)($_POST[$name]??''));if(strlen($v)>$max)throw new RuntimeException('Campo '.$name.' muito longo.');return $v;}
function clientePermitido(int $id): array {
 $c=query('SELECT * FROM clientes WHERE id=? AND (?=1 OR criado_por=?)',[$id,admin()?1:0,$_SESSION['user']['id']])->fetch();
 if(!$c)throw new RuntimeException('Cliente não encontrado.');return $c;
}
function pedidoPermitido(int $id,bool $lock=false): array {
 $o=query('SELECT * FROM pedidos WHERE id=? AND (?=1 OR usuario_id=?)'.($lock?' FOR UPDATE':''),[$id,admin()?1:0,$_SESSION['user']['id']])->fetch();
 if(!$o)throw new RuntimeException('Pedido não encontrado.');return $o;
}
function lerCarrinho(): array {
 $posted=$_POST['quantidades']??[];if(!is_array($posted)||count($posted)>200)throw new RuntimeException('Carrinho inválido (máximo 200 tipos de item).');
 $cart=[];foreach($posted as $id=>$q){if($q===''||$q==='0')continue;$qty=filter_var($q,FILTER_VALIDATE_INT);if(!ctype_digit((string)$id)||$qty===false||$qty<1||$qty>1000000)throw new RuntimeException('Quantidade inválida.');$cart[(int)$id]=$qty;}
 if(!$cart)throw new RuntimeException('Informe a quantidade de pelo menos uma ferramenta.');ksort($cart);return $cart;
}
function carregarCarrinho(array $cart,bool $lock=false): array {
 $items=[];foreach($cart as $id=>$qty){
  $t=query('SELECT f.*,c.fornecedor_id FROM ferramentas f LEFT JOIN ferramenta_comercial c ON c.ferramenta_id=f.id WHERE f.id=?'.($lock?' FOR UPDATE':''),[$id])->fetch();
  if(!$t||$t['estoque']<$qty)throw new RuntimeException('Estoque insuficiente para um dos itens.');
  if(!$t['fornecedor_id'])throw new RuntimeException('Vincule um fornecedor à ferramenta '.$t['nome'].'.');
  if(centavos($t['preco'])<=0)throw new RuntimeException('O preço deve ser maior que zero.');
  $t['quantidade']=$qty;$items[]=$t;
 }return $items;
}
