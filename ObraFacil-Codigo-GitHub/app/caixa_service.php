<?php
require_once __DIR__.'/comercial.php';
require_once __DIR__.'/estoque_service.php';
require_once __DIR__.'/turnos_service.php';
function pagamentoPresencial(string $forma,string $referencia,bool $conferido): string {
 if(!in_array($forma,['Pix','Cartão de crédito','Boleto'],true))throw new RuntimeException('Escolha a forma de pagamento.');
 if($forma==='Boleto'){
  if($referencia==='')throw new RuntimeException('Informe a referência do boleto já emitido pelo banco.');
  return 'A receber';
 }
 if(!$conferido)throw new RuntimeException('Confira o recebimento no banco ou na maquininha e marque a confirmação.');
 return 'Recebido (conferência manual)';
}
function totalCarrinho(array $items): int {
 $total=0;$max=99999999999999;
 foreach($items as $item){$unit=centavos($item['preco']);$qty=(int)$item['quantidade'];
  if($unit<=0||$qty<1||$qty>intdiv($max,$unit))throw new RuntimeException('Valor ou quantidade do item inválidos.');
  $line=$unit*$qty;if($total>$max-$line)throw new RuntimeException('Total da venda acima do limite permitido.');$total+=$line;
 }
 if(!$total)throw new RuntimeException('Adicione pelo menos um item.');return $total;
}
function registrarVendaPresencial(): int {
 exigirPermissao('sale');
 $key=(string)($_POST['chave']??'');if(!preg_match('/^[a-f0-9]{64}$/D',$key))throw new RuntimeException('Reabra o formulário da venda.');
 // O mesmo formulário não pode baixar o estoque duas vezes, nem em requisições simultâneas.
 $lock='of_caixa_'.substr($key,0,50);if(query('SELECT GET_LOCK(?,5)',[$lock])->fetchColumn()!=1)throw new RuntimeException('Venda em processamento. Consulte o histórico antes de repetir.');
 try{
  db()->beginTransaction();
  $existing=query('SELECT id,usuario_id FROM comprovantes WHERE chave=?',[$key])->fetch();
  if($existing){if((int)$existing['usuario_id']!==(int)$_SESSION['user']['id'])throw new RuntimeException('Reabra o formulário.');db()->rollBack();return (int)$existing['id'];}
  $caixa=exigirCaixa();$cart=lerCarrinho();$cid=integer('cliente_id');
  $client=$cid?clientePermitido($cid):['nome'=>'Consumidor final','documento'=>''];
  $forma=input('forma_pagamento',30);$ref=campoOpcional('referencia_pagamento',120);
  $pay=pagamentoPresencial($forma,$ref,($_POST['pagamento_conferido']??'')==='1');
  $items=carregarCarrinho($cart,true);$total=decimalValor(totalCarrinho($items));
  $id=gravarVenda($items,$client,$forma,$pay,$ref,$key,(int)$caixa['id']);
  db()->commit();unset($_SESSION['caixa_form']);return $id;
 }catch(Throwable $ex){if(db()->inTransaction())db()->rollBack();throw $ex;}finally{query('SELECT RELEASE_LOCK(?)',[$lock]);}
}
function comprovantePermitido(int $id,bool $lock=false): array {
 exigirPermissao('comprovante');
 $c=query('SELECT * FROM comprovantes WHERE id=? AND (?=1 OR usuario_id=?)'.($lock?' FOR UPDATE':''),[$id,admin()?1:0,$_SESSION['user']['id']])->fetch();
 if(!$c)throw new RuntimeException('Comprovante não encontrado.');return $c;
}
function cancelarVendaPresencial(int $id): void {
 requireAdmin();db()->beginTransaction();
 try{
  $c=comprovantePermitido($id,true);if($c['status']==='Cancelada'){db()->rollBack();return;}
  if(query('SELECT id FROM devolucoes WHERE comprovante_id=? OR novo_comprovante_id=? LIMIT 1',[$id,$id])->fetch())throw new RuntimeException('Venda vinculada a devolução/troca: use Devoluções para devolver somente os itens restantes.');
  $caixa=exigirCaixa();
  if($c['pagamento_status']!=='A receber'&&($_POST['estorno_conferido']??'')!=='1')throw new RuntimeException('Confirme o estorno antes de cancelar uma venda recebida.');
  $rows=query('SELECT v.* FROM vendas v JOIN comprovante_itens ci ON ci.venda_id=v.id WHERE ci.comprovante_id=? ORDER BY v.ferramenta_id FOR UPDATE',[$id])->fetchAll();
  foreach($rows as $r){if($r['status']!=='Concluída')throw new RuntimeException('Item já alterado: revise a venda.');
   query("UPDATE vendas SET status='Cancelada',cancelado_em=NOW(),cancelado_por=? WHERE id=?",[$_SESSION['user']['id'],$r['id']]);
   movimentarEstoque((int)$r['ferramenta_id'],(int)$r['quantidade'],'Cancelamento venda','venda:'.$id,'Cancelamento completo pelo gerente.');
  }
  if($c['pagamento_status']!=='A receber')registrarFinanceiro((int)$caixa['id'],$id,'Estorno cancelamento',$c['forma_pagamento'],-centavos($c['total']),'cancelamento:'.$id);
  query("UPDATE comprovantes SET status='Cancelada',cancelado_em=NOW(),cancelado_por=? WHERE id=?",[$_SESSION['user']['id'],$id]);db()->commit();
 }catch(Throwable $ex){if(db()->inTransaction())db()->rollBack();throw $ex;}
}
function confirmarBoleto(int $id): void {
 requireAdmin();db()->beginTransaction();
 try{
  $c=comprovantePermitido($id,true);
  if($c['status']!=='Concluída'||$c['forma_pagamento']!=='Boleto')throw new RuntimeException('Este comprovante não admite recebimento de boleto.');
  if($c['pagamento_status']==='A receber'){
   $caixa=exigirCaixa();$total=query("SELECT COALESCE(SUM(v.total_liquido),0) FROM ".vendasLiquidasSql()." v JOIN comprovante_itens ci ON ci.venda_id=v.id WHERE ci.comprovante_id=?",[$id])->fetchColumn();
   if(centavos($total)===0)throw new RuntimeException('Todos os itens foram devolvidos; não há valor a receber.');
   registrarFinanceiro((int)$caixa['id'],$id,'Recebimento boleto','Boleto',centavos($total),'boleto:'.$id);
   query("UPDATE comprovantes SET pagamento_status='Recebido (conferência manual)',pagamento_confirmado_em=NOW(),pagamento_confirmado_por=? WHERE id=?",[$_SESSION['user']['id'],$id]);}
  db()->commit();
 }catch(Throwable $ex){if(db()->inTransaction())db()->rollBack();throw $ex;}
}
function gravarVenda(array $items,array $client,string $forma,string $pay,string $ref,string $key,int $caixa,bool $financeiro=true): int {
 if(!db()->inTransaction())throw new LogicException('Venda precisa de transação.');
 $total=decimalValor(totalCarrinho($items));
  $loja=require __DIR__.'/../loja.php';
  query('INSERT INTO comprovantes(usuario_id,vendedor_nome,cliente_nome,cliente_documento,loja_snapshot,total,forma_pagamento,pagamento_status,referencia_pagamento,chave,pagamento_confirmado_em,pagamento_confirmado_por) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)',[$_SESSION['user']['id'],$_SESSION['user']['nome'],$client['nome'],$client['documento'],json_encode($loja,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),$total,$forma,$pay,$ref,$key,$forma==='Boleto'?null:date('Y-m-d H:i:s'),$forma==='Boleto'?null:$_SESSION['user']['id']]);
  $id=(int)db()->lastInsertId();
  foreach($items as $t){
   query('INSERT INTO vendas(ferramenta_id,usuario_id,produto_nome,cliente,quantidade,preco_unitario,total,chave) VALUES(?,?,?,?,?,?,?,?)',[$t['id'],$_SESSION['user']['id'],$t['nome'],$client['nome'],$t['quantidade'],$t['preco'],decimalValor(centavos($t['preco'])*$t['quantidade']),hash('sha256','caixa:'.$key.':'.$t['id'])]);
   $vid=(int)db()->lastInsertId();query('INSERT INTO comprovante_itens(comprovante_id,venda_id) VALUES(?,?)',[$id,$vid]);
   movimentarEstoque((int)$t['id'],-(int)$t['quantidade'],'Venda','venda:'.$id,'Saída pelo caixa presencial.');
  }
  query('INSERT INTO caixa_vendas(comprovante_id,sessao_id) VALUES(?,?)',[$id,$caixa]);
 if($financeiro&&$pay!=='A receber')registrarFinanceiro($caixa,$id,'Venda',$forma,centavos($total),'venda:'.$id,$ref);
 return $id;
}
