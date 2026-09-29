<?php
require_once __DIR__.'/comercial.php';require_once __DIR__.'/estoque_service.php';
function dataCompra(string $value): string {
 $d=DateTimeImmutable::createFromFormat('!Y-m-d',$value);
 if(!$d||$d->format('Y-m-d')!==$value||$value>date('Y-m-d')||$value<'1900-01-01')throw new RuntimeException('Informe uma data de compra válida, até hoje.');return $value;
}
function registrarCompra(): int {
 exigirPermissao('save_purchase');$key=(string)($_POST['chave']??'');
 if(!preg_match('/^[a-f0-9]{64}$/D',$key))throw new RuntimeException('Reabra o formulário de compra.');
 $lock='of_compra_'.substr($key,0,50);if(query('SELECT GET_LOCK(?,5)',[$lock])->fetchColumn()!=1)throw new RuntimeException('Compra em processamento.');
 try{
  db()->beginTransaction();$old=query('SELECT id,usuario_id FROM compras_documentos WHERE chave=?',[$key])->fetch();
  if($old){if((int)$old['usuario_id']!==(int)$_SESSION['user']['id'])throw new RuntimeException('Reabra o formulário.');db()->rollBack();return (int)$old['id'];}
  $sid=integer('fornecedor_id',1);$date=dataCompra(input('data_compra',10));$ref=campoOpcional('referencia',120);$cart=lerCarrinho();$costs=$_POST['custos']??[];if(!is_array($costs))throw new RuntimeException('Custos inválidos.');
  $supplier=query('SELECT * FROM fornecedores WHERE id=? AND ativo=1',[$sid])->fetch();if(!$supplier)throw new RuntimeException('Selecione um fornecedor ativo.');
  $items=[];$total=0;
  foreach($cart as $id=>$qty){$t=query('SELECT f.*,fc.fornecedor_id FROM ferramentas f JOIN ferramenta_comercial fc ON fc.ferramenta_id=f.id WHERE f.id=? FOR UPDATE',[$id])->fetch();
   if(!$t||(int)$t['fornecedor_id']!==$sid)throw new RuntimeException('Todos os itens devem estar vinculados ao fornecedor selecionado.');
   $cost=centavos(price((string)($costs[$id]??'')));if($cost<=0||$qty>intdiv(99999999999999,$cost))throw new RuntimeException('Custo inválido.');$line=$cost*$qty;if($total>99999999999999-$line)throw new RuntimeException('Compra acima do limite.');$total+=$line;
   if($qty>1000000-(int)$t['estoque'])throw new RuntimeException('Entrada acima do limite de estoque.');$items[]=[$t,$qty,$cost,$line];
  }
  query('INSERT INTO compras_documentos(fornecedor_id,fornecedor_nome,usuario_id,data_compra,referencia,total,chave) VALUES(?,?,?,?,?,?,?)',[$sid,$supplier['nome'],$_SESSION['user']['id'],$date,$ref,decimalValor($total),$key]);$doc=(int)db()->lastInsertId();
  foreach($items as [$t,$qty,$cost,$line]){
   query('INSERT INTO compras_estoque(ferramenta_id,fornecedor_id,ferramenta_nome,fornecedor_nome,usuario_id,quantidade,custo_unitario,total,data_compra,referencia,chave) VALUES(?,?,?,?,?,?,?,?,?,?,?)',[$t['id'],$sid,$t['nome'],$supplier['nome'],$_SESSION['user']['id'],$qty,decimalValor($cost),decimalValor($line),$date,$ref,hash('sha256',$key.':'.$t['id'])]);$pid=(int)db()->lastInsertId();
   query('INSERT INTO compras_documento_itens(documento_id,compra_id) VALUES(?,?)',[$doc,$pid]);movimentarEstoque((int)$t['id'],$qty,'Compra recebida','compra:'.$doc,'Recebimento do documento '.$ref.'.');
  }
  db()->commit();unset($_SESSION['compra_form']);return $doc;
 }catch(Throwable $ex){if(db()->inTransaction())db()->rollBack();throw $ex;}finally{query('SELECT RELEASE_LOCK(?)',[$lock]);}
}
function cancelarCompra(int $id): void {
 requireAdmin();$motivo=input('motivo',255);db()->beginTransaction();
 try{
  $p=query('SELECT * FROM compras_documentos WHERE id=? FOR UPDATE',[$id])->fetch();if(!$p)throw new RuntimeException('Compra não encontrada.');if($p['status']==='Cancelada'){db()->rollBack();return;}
  $rows=query('SELECT c.* FROM compras_estoque c JOIN compras_documento_itens i ON i.compra_id=c.id WHERE i.documento_id=? ORDER BY c.ferramenta_id FOR UPDATE',[$id])->fetchAll();
  foreach($rows as $r){if($r['status']!=='Registrada')throw new RuntimeException('Itens divergentes. Revise o documento.');movimentarEstoque((int)$r['ferramenta_id'],-(int)$r['quantidade'],'Cancelamento compra','compra:'.$id,$motivo);query("UPDATE compras_estoque SET status='Cancelada',cancelado_em=NOW(),cancelado_por=? WHERE id=?",[$_SESSION['user']['id'],$r['id']]);}
  query("UPDATE compras_documentos SET status='Cancelada',cancelado_em=NOW(),cancelado_por=? WHERE id=?",[$_SESSION['user']['id'],$id]);db()->commit();
 }catch(Throwable $ex){if(db()->inTransaction())db()->rollBack();throw $ex;}
}
