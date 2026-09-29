<?php
if($_SERVER['REQUEST_METHOD']!=='POST')return;
checkCsrf();
$action=(string)($_POST['action']??'');
// Autoriza antes de consultar ou alterar dados, inclusive em POST manual.
exigirPermissao($action);
try {
 if(in_array($action,['open_cash','close_cash'],true)){
  require_once __DIR__.'/turnos_service.php';
  if($action==='open_cash'){$id=abrirCaixa();flash('Caixa aberto.');}else{$id=integer('id',1);fecharCaixa($id);flash('Fechamento preservado.');}
  go('index.php?p=turnos&ver='.$id);
 }
 if($action==='save_return'){require_once __DIR__.'/devolucoes_service.php';$id=registrarDevolucao();flash('Devolução/troca registrada.');go('index.php?p=devolucoes&ver='.$id);}

 if(in_array($action,['save_purchase','cancel_purchase'],true)){
  require_once __DIR__.'/compras_service.php';
  if($action==='save_purchase'){
   $_SESSION['compra_form']=array_intersect_key($_POST,array_flip(['chave','fornecedor_id','quantidades','custos','data_compra','referencia']));
   $id=registrarCompra();flash('Compra #'.$id.' registrada e estoque atualizado.');
  }else{cancelarCompra(integer('id',1));flash('Compra cancelada; entrada desfeita no estoque.');}
  go('index.php?p=compras');
 }
 if(in_array($action,['save_supplier','supplier_active','save_customer','sync_order','cancel_order'],true))require __DIR__.'/comercial_actions.php';
 if(in_array($action,['generate_report','send_report'],true)){
  require_once __DIR__.'/envios_service.php';
  if($action==='generate_report'){
   $id=gerarRelatorio(input('tipo',30),input('referencia',10));
   flash('Relatório preservado. Confira os dados e clique em Enviar.');
  }else{
   $id=integer('id',1);enviarRelatorio($id);flash('Relatório enviado à gerência.');
  }
  go('index.php?p=envios&ver='.$id);
 }
 if($action==='logout'){
  if(isset($_SESSION['access_id']))query('UPDATE acessos SET saida=NOW() WHERE id=? AND saida IS NULL',[$_SESSION['access_id']]);
  $_SESSION=[];session_regenerate_id(true);flash('Você saiu do sistema.');go('index.php');
 }
 if($action==='save_product'){
  require_once __DIR__.'/comercial.php';require_once __DIR__.'/estoque_service.php';
  $id=integer('id');$supplier=integer('fornecedor_id',1);
  if(!query('SELECT id FROM fornecedores WHERE id=? AND ativo=1',[$supplier])->fetch())throw new RuntimeException('Selecione um fornecedor ativo.');
  $args=[input('nome'),input('marca',100),input('categoria',100),price((string)($_POST['preco']??'')),integer('estoque'),10];
  $estoqueNovo=$args[4];db()->beginTransaction();
  if($id){
   $old=query('SELECT * FROM ferramentas WHERE id=? FOR UPDATE',[$id])->fetch();if(!$old)throw new RuntimeException('Ferramenta não encontrada.');
   if((string)$old['estoque']!==(string)($_POST['estoque_original']??''))throw new RuntimeException('O estoque mudou. Abra a ferramenta novamente.');
   $args[4]=$old['estoque'];$args[]=$id;query('UPDATE ferramentas SET nome=?,marca=?,categoria=?,preco=?,estoque=?,minimo=? WHERE id=?',$args);
   if($estoqueNovo!==(int)$old['estoque'])movimentarEstoque($id,$estoqueNovo-(int)$old['estoque'],'Ajuste manual','ferramenta:'.$id,input('motivo_estoque',255));
  }else{$args[4]=0;query('INSERT INTO ferramentas(nome,marca,categoria,preco,estoque,minimo) VALUES(?,?,?,?,?,?)',$args);$id=(int)db()->lastInsertId();movimentarEstoque($id,$estoqueNovo,'Cadastro inicial','ferramenta:'.$id,'Estoque inicial informado no cadastro.');}
  query('INSERT INTO ferramenta_comercial(ferramenta_id,fornecedor_id) VALUES(?,?) ON DUPLICATE KEY UPDATE fornecedor_id=VALUES(fornecedor_id)',[$id,$supplier]);
  db()->commit();flash('Ferramenta e fornecedor salvos.');go('index.php?p=ferramentas');
 }
 if($action==='delete_product'){
  requireAdmin();$id=integer('id',1);
  require_once __DIR__.'/estoque_service.php';db()->beginTransaction();$f=query('SELECT estoque FROM ferramentas WHERE id=? FOR UPDATE',[$id])->fetch();if(!$f)throw new RuntimeException('Ferramenta não encontrada.');
  movimentarEstoque($id,-(int)$f['estoque'],'Exclusão do cadastro','ferramenta:'.$id,'Exclusão de ferramenta sem histórico de compras ou vendas.');
  query('DELETE FROM ferramentas WHERE id=?',[$id]);db()->commit();flash('Ferramenta excluída.');go('index.php?p=ferramentas');
 }
 if(in_array($action,['sale','cancel_receipt','receive_boleto'],true)){
  require_once __DIR__.'/caixa_service.php';
  if($action==='sale'){
   // Guarda apenas dados do carrinho; senha e confirmação de recebimento não são persistidas no rascunho.
   $_SESSION['caixa_form']=array_intersect_key($_POST,array_flip(['chave','cliente_id','quantidades','forma_pagamento','referencia_pagamento']));
   $id=registrarVendaPresencial();flash('Venda salva. Nota de venda demonstrativa disponível para impressão.');
  }else{
   $id=integer('id',1);
   if($action==='cancel_receipt'){cancelarVendaPresencial($id);flash('Venda cancelada e estoque devolvido. Confira o estorno bancário separadamente.');}
   else{confirmarBoleto($id);flash('Recebimento registrado pelo gerente.');}
  }
  go('index.php?p=comprovante&id='.$id);
 }
 if($action==='cancel_sale'){
  requireAdmin();require_once __DIR__.'/estoque_service.php';$id=integer('id',1);
  if(query('SELECT comprovante_id FROM comprovante_itens WHERE venda_id=?',[$id])->fetch())throw new RuntimeException('Cancele a venda completa pela tela do comprovante.');
  if(query('SELECT id FROM pedido_itens WHERE venda_id=?',[$id])->fetch())throw new RuntimeException('Venda vinculada a pagamento online: gerencie o estorno na conta Mercado Pago. Não é permitido cancelar apenas um item aqui.');
  db()->beginTransaction();
  $sale=query('SELECT * FROM vendas WHERE id=? FOR UPDATE',[$id])->fetch();
  if(!$sale||$sale['status']!=='Concluída')throw new RuntimeException('Venda não encontrada ou já cancelada.');
  query("UPDATE vendas SET status='Cancelada',cancelado_em=NOW(),cancelado_por=? WHERE id=?",[$_SESSION['user']['id'],$id]);
  movimentarEstoque((int)$sale['ferramenta_id'],(int)$sale['quantidade'],'Cancelamento legado','venda:'.$id,'Cancelamento de registro anterior.');db()->commit();flash('Venda cancelada. Estoque devolvido.');go('index.php?p=vendas');
 }
 if($action==='user'){
  requireAdmin();cadastrarConta((string)($_POST['nome']??''),(string)($_POST['perfil']??''),(string)($_POST['senha']??''));flash('Funcionário cadastrado. Informe o nome e a senha de 4 números escolhida a ele.');go('index.php?p=usuarios');
 }
 throw new RuntimeException('Ação inválida.');
}catch(Throwable $ex){
 if(db()->inTransaction())db()->rollBack();
 if($ex instanceof PDOException){error_log($ex->getMessage());$error=($ex->errorInfo[1]??0)==1451?'Esta ferramenta possui histórico de vendas ou compras e não pode ser excluída.':(($ex->errorInfo[1]??0)==1062?'Registro já cadastrado.':'Não foi possível salvar. Confira os dados e tente novamente.');}else $error=$ex->getMessage();
 flash($error);$dest=['save_return'=>'devolucoes','open_cash'=>'turnos','close_cash'=>'turnos','save_purchase'=>'compras','cancel_purchase'=>'compras','save_supplier'=>'fornecedores','supplier_active'=>'fornecedores','save_customer'=>'clientes','sync_order'=>'pedidos','cancel_order'=>'pedidos','generate_report'=>'envios','send_report'=>'envios','sale'=>'vendas','cancel_receipt'=>'vendas','receive_boleto'=>'vendas','cancel_sale'=>'vendas','user'=>'usuarios','save_product'=>'ferramentas','delete_product'=>'ferramentas'][$action]??'inicio';go('index.php?p='.$dest);
}
