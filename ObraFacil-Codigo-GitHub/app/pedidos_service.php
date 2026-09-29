<?php
require_once __DIR__.'/integracoes_service.php';require_once __DIR__.'/estoque_service.php';
function reconciliarPagamento(array $payment): void {
 $c=integracoes();$ref=(string)($payment['external_reference']??'');
 if(!preg_match('/^[a-f0-9]{32}$/D',$ref))return;
 if((string)($payment['collector_id']??'')!==(string)$c['mp_collector_id']||($payment['currency_id']??'')!=='BRL')throw new RuntimeException('Conta ou moeda incompatível.');
 if(!array_key_exists('live_mode',$payment)||(bool)$payment['live_mode']=== (bool)$c['mp_test'])throw new RuntimeException('Ambiente de pagamento incompatível.');
 $pid=(string)($payment['id']??'');if(!ctype_digit($pid))throw new RuntimeException('Pagamento inválido.');
 db()->beginTransaction();
 try{
  $o=query('SELECT * FROM pedidos WHERE referencia=? FOR UPDATE',[$ref])->fetch();if(!$o){db()->rollBack();return;}
  if(centavos($payment['transaction_amount']??'0')!==centavos($o['total']))throw new RuntimeException('Valor do pagamento incompatível.');
  $status=(string)($payment['status']??'unknown');
  if($o['payment_id']&&$o['payment_id']!==$pid){if($status==='approved')query("UPDATE pedidos SET status='Revisar pagamentos duplicados' WHERE id=?",[$o['id']]);db()->commit();return;}
  if($o['status']==='Cancelado'){
   // Um pagamento tardio não deve criar venda com estoque já devolvido nem prender o webhook em erro.
   query("UPDATE pedidos SET payment_id=?,pagamento_status=?,meio_pagamento=?,financeiro_atualizado_em=NOW(),status=CASE WHEN ?='approved' THEN 'Revisar pagamento após cancelamento' ELSE status END WHERE id=?",[$pid,$status,(string)($payment['payment_method_id']??''),$status,$o['id']]);
   db()->commit();return;
  }
  if($status==='approved'&&!$o['pago_em']){
   $client=json_decode($o['cliente_snapshot'],true,512,JSON_THROW_ON_ERROR);
   foreach(query('SELECT * FROM pedido_itens WHERE pedido_id=? ORDER BY ferramenta_id',[$o['id']])->fetchAll() as $t){
    query('INSERT INTO vendas(ferramenta_id,usuario_id,produto_nome,cliente,quantidade,preco_unitario,total,chave) VALUES(?,?,?,?,?,?,CAST(? AS DECIMAL(10,2))*?,?)',[$t['ferramenta_id'],$o['usuario_id'],$t['nome'],$client['nome'],$t['quantidade'],$t['preco'],$t['preco'],$t['quantidade'],hash('sha256','pedido:'.$o['id'].':'.$t['id'])]);
    $vid=(int)db()->lastInsertId();query('UPDATE pedido_itens SET venda_id=? WHERE id=?',[$vid,$t['id']]);
   }
   query("UPDATE pedidos SET pago_em=NOW(),payment_id=?,status='Pago' WHERE id=?",[$pid,$o['id']]);
  }
  $refunded=(float)($payment['transaction_amount_refunded']??0)>0;
  if($refunded||in_array($status,['refunded','charged_back'],true))query("UPDATE pedidos SET status='Revisar estorno ou contestação' WHERE id=?",[$o['id']]);
  query('UPDATE pedidos SET pagamento_status=?,meio_pagamento=?,financeiro_atualizado_em=NOW() WHERE id=?',[$status,(string)($payment['payment_method_id']??''),$o['id']]);
  db()->commit();
 }catch(Throwable $ex){if(db()->inTransaction())db()->rollBack();throw $ex;}
}
function sincronizarPedido(int $id): void {
 $o=pedidoPermitido($id);
 if($o['payment_id']){reconciliarPagamento(apiJson('mp','GET','/v1/payments/'.rawurlencode($o['payment_id'])));return;}
 $data=apiJson('mp','GET','/v1/payments/search?'.http_build_query(['external_reference'=>$o['referencia'],'sort'=>'date_created','criteria'=>'desc','limit'=>100]));
 foreach($data['results']??[] as $item){if((string)($item['external_reference']??'')===$o['referencia'])reconciliarPagamento(apiJson('mp','GET','/v1/payments/'.rawurlencode((string)$item['id'])));}
}
function cancelarPedidoSemCheckout(int $id): void {
 requireAdmin();$lock='obra_checkout_'.$id;if(query('SELECT GET_LOCK(?,5)',[$lock])->fetchColumn()!=1)throw new RuntimeException('Tente novamente.');
 try{db()->beginTransaction();$o=pedidoPermitido($id,true);
  if($o['status']==='Cancelado'){db()->rollBack();return;}
  if($o['checkout_tentado_em']||$o['pago_em'])throw new RuntimeException('Este pedido já iniciou uma cobrança. Resolva pagamentos e estornos na conta Mercado Pago antes de qualquer devolução de estoque.');
  foreach(query('SELECT ferramenta_id,quantidade FROM pedido_itens WHERE pedido_id=? ORDER BY ferramenta_id',[$id])->fetchAll() as $t)movimentarEstoque((int)$t['ferramenta_id'],(int)$t['quantidade'],'Cancelamento pedido','pedido:'.$id,'Devolução de reserva anterior.');
  query("UPDATE pedidos SET status='Cancelado' WHERE id=?",[$id]);db()->commit();
 }catch(Throwable $ex){if(db()->inTransaction())db()->rollBack();throw $ex;}finally{query('SELECT RELEASE_LOCK(?)',[$lock]);}
}
