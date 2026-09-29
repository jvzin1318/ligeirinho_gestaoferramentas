<?php
require_once __DIR__.'/pedidos_service.php';
if($action==='save_supplier'){
 $id=integer('id');$cnpj=campoOpcional('cnpj',18);$cnpj=$cnpj!==''?validarDocumento($cnpj,'PJ'):null;
 $email=campoOpcional('email',190);if($email!==''&&!filter_var($email,FILTER_VALIDATE_EMAIL))throw new RuntimeException('E-mail inválido.');
 $args=[input('nome'),$cnpj,$email,campoOpcional('telefone',30),campoOpcional('endereco',255)];
 if($id){$args[]=$id;query('UPDATE fornecedores SET nome=?,cnpj=?,email=?,telefone=?,endereco=? WHERE id=?',$args);}else query('INSERT INTO fornecedores(nome,cnpj,email,telefone,endereco) VALUES(?,?,?,?,?)',$args);
 flash('Fornecedor salvo.');go('index.php?p=fornecedores');
}
if($action==='supplier_active'){requireAdmin();query('UPDATE fornecedores SET ativo=? WHERE id=?',[integer('ativo',0,1),integer('id',1)]);flash('Situação do fornecedor atualizada.');go('index.php?p=fornecedores');}
if($action==='save_customer'){
 $id=integer('id');if($id)clientePermitido($id);
 $type=input('tipo',2);if(!in_array($type,['PF','PJ'],true))throw new RuntimeException('Tipo de cliente inválido.');
 $doc=validarDocumento(input('documento',18),$type);$email=campoOpcional('email',190);if($email!==''&&!filter_var($email,FILTER_VALIDATE_EMAIL))throw new RuntimeException('E-mail inválido.');
 $args=[$type,input('nome'),$doc,$email,campoOpcional('telefone',30)];
 if($id){$args[]=$id;query('UPDATE clientes SET tipo=?,nome=?,documento=?,email=?,telefone=? WHERE id=?',$args);}else{$args[]=$_SESSION['user']['id'];query('INSERT INTO clientes(tipo,nome,documento,email,telefone,criado_por) VALUES(?,?,?,?,?,?)',$args);}
 flash('Cliente salvo.');go('index.php?p=clientes');
}
if(in_array($action,['sync_order','cancel_order'],true)){
 $id=integer('id',1);
 if($action==='sync_order'){sincronizarPedido($id);flash('Situação consultada no Mercado Pago.');}
 if($action==='cancel_order'){cancelarPedidoSemCheckout($id);flash('Pedido cancelado e reserva devolvida.');}
 go('index.php?p=pedidos&ver='.$id);
}
