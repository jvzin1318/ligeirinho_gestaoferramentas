<?php
require_once __DIR__.'/caixa_service.php';
function registrarDevolucao(): int {
 requireAdmin();$key=(string)($_POST['chave']??'');if(!preg_match('/^[a-f0-9]{64}$/D',$key))throw new RuntimeException('Reabra a devolução.');
 $lock='of_devolucao_'.substr($key,0,45);if(query('SELECT GET_LOCK(?,5)',[$lock])->fetchColumn()!=1)throw new RuntimeException('Devolução em processamento.');
 try {db()->beginTransaction();$old=query('SELECT id FROM devolucoes WHERE chave=?',[$key])->fetch();if($old){db()->rollBack();return (int)$old['id'];}
  $id=integer('comprovante_id',1);$c=comprovantePermitido($id,true);if($c['status']!=='Concluída')throw new RuntimeException('Venda cancelada não admite devolução.');
  $tipo=input('tipo',20);if(!in_array($tipo,['Devolução','Troca'],true))throw new RuntimeException('Tipo inválido.');$motivo=input('motivo',255);$ref=campoOpcional('referencia',120);
  $posted=$_POST['devolver']??[];$repor=$_POST['repor']??[];if(!is_array($posted)||!is_array($repor)||count($posted)>200)throw new RuntimeException('Itens inválidos.');
  // Leituras com bloqueio enxergam devoluções confirmadas mesmo após aguardar outra operação.
  $rows=query('SELECT v.* FROM vendas v JOIN comprovante_itens ci ON ci.venda_id=v.id WHERE ci.comprovante_id=? ORDER BY v.ferramenta_id FOR UPDATE',[$id])->fetchAll();$map=[];
  foreach($rows as $r){$used=0;foreach(query('SELECT quantidade FROM devolucao_itens WHERE venda_id=? FOR UPDATE',[$r['id']])->fetchAll() as $previous)$used+=(int)$previous['quantidade'];$r['quantidade_liquida']=(int)$r['quantidade']-$used;$map[(int)$r['id']]=$r;}
  $selected=[];$total=0;$locks=[];
  foreach($posted as $vid=>$q){if($q===''||$q==='0')continue;$qty=filter_var($q,FILTER_VALIDATE_INT);if(!ctype_digit((string)$vid)||$qty===false||$qty<1||!isset($map[(int)$vid])||$qty>(int)$map[(int)$vid]['quantidade_liquida'])throw new RuntimeException('Quantidade excede os itens ainda não devolvidos.');$r=$map[(int)$vid];$line=centavos($r['preco_unitario'])*$qty;$total+=$line;$selected[]=[$r,$qty,($repor[$vid]??'')==='1',$line];$locks[(int)$r['ferramenta_id']]=true;}
  if(!$selected)throw new RuntimeException('Selecione os itens devolvidos.');
  $paid=$c['pagamento_status']!=='A receber';if($tipo==='Troca'&&!$paid)throw new RuntimeException('Troca exige uma venda quitada. Para boleto pendente, registre devolução para abater o saldo e faça uma nova venda.');
  $cart=[];if($tipo==='Troca'){$cart=lerCarrinho();foreach($cart as $pid=>$qty)$locks[$pid]=true;}
  $cash=$paid?exigirCaixa():null;ksort($locks);foreach($locks as $pid=>$unused)query('SELECT id FROM ferramentas WHERE id=? FOR UPDATE',[$pid])->fetch();
  // Reposição ocorre na mesma transação; a troca pode reutilizar a unidade recebida.
  foreach($selected as [$r,$qty,$put,$line])movimentarEstoque((int)$r['ferramenta_id'],$put?$qty:0,$put?'Devolução':'Devolução sem reposição','venda:'.$id,$motivo);
  $items=$tipo==='Troca'?carregarCarrinho($cart,true):[];$newTotal=$items?totalCarrinho($items):0;$diff=$newTotal-$total;
  $forma='Abatimento boleto';if($paid){$forma=$diff===0?'Compensação':input('forma',30);if($diff!==0&&(!in_array($forma,['Pix','Cartão de crédito'],true)||($_POST['financeiro_conferido']??'')!=='1'))throw new RuntimeException('Escolha Pix/cartão e confirme o recebimento ou reembolso da diferença.');}
  query('INSERT INTO devolucoes(comprovante_id,usuario_id,tipo,motivo,total,total_novo,diferenca,forma,referencia,chave) VALUES(?,?,?,?,?,?,?,?,?,?)',[$id,$_SESSION['user']['id'],$tipo,$motivo,decimalValor($total),decimalValor($newTotal),valorAssinado($diff),$forma,$ref,$key]);$did=(int)db()->lastInsertId();
  foreach($selected as [$r,$qty,$put,$line])query('INSERT INTO devolucao_itens(devolucao_id,venda_id,quantidade,repor_estoque,preco_unitario,total) VALUES(?,?,?,?,?,?)',[$did,$r['id'],$qty,$put?1:0,$r['preco_unitario'],decimalValor($line)]);
  if($tipo==='Troca'){$new=gravarVenda($items,['nome'=>$c['cliente_nome'],'documento'=>$c['cliente_documento']],'Crédito de troca','Recebido (conferência manual)','Troca #'.$did,hash('sha256','troca:'.$key),(int)$cash['id'],false);query('UPDATE devolucoes SET novo_comprovante_id=? WHERE id=?',[$new,$did]);}
  if($paid&&$diff!==0)registrarFinanceiro((int)$cash['id'],$id,$tipo==='Troca'?'Diferença de troca':'Reembolso',$forma,$diff,'devolucao:'.$did,$ref);
  db()->commit();return $did;
 }catch(Throwable $e){if(db()->inTransaction())db()->rollBack();throw $e;}finally{query('SELECT RELEASE_LOCK(?)',[$lock]);}
}
