<?php
require __DIR__.'/app/bootstrap.php';
require __DIR__.'/app/pedidos_service.php';
header('Content-Type: application/json');
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);exit('{}');}
$c=integracoes();if(!$c['mp_webhook_secret']||!$c['mp_token']||!$c['mp_collector_id']){http_response_code(503);exit('{}');}
// PHP converte data.id da query string para data_id.
$id=(string)($_GET['data_id']??'');$request=(string)($_SERVER['HTTP_X_REQUEST_ID']??'');$signature=(string)($_SERVER['HTTP_X_SIGNATURE']??'');
$parts=[];foreach(explode(',',$signature) as $part){$pair=explode('=',trim($part),2);if(count($pair)===2)$parts[$pair[0]]=$pair[1];}
$ts=$parts['ts']??'';$v1=$parts['v1']??'';
if(!ctype_digit($id)||!ctype_digit($ts)||!$request||!preg_match('/^[a-f0-9]{64}$/D',$v1)){http_response_code(401);exit('{}');}
$manifest='id:'.strtolower($id).';request-id:'.$request.';ts:'.$ts.';';
if(!hash_equals(hash_hmac('sha256',$manifest,$c['mp_webhook_secret']),$v1)){http_response_code(401);exit('{}');}
try{
 // Consulta autenticada ao provedor: nunca confia no status do corpo ou na URL de retorno.
 reconciliarPagamento(apiJson('mp','GET','/v1/payments/'.rawurlencode($id)));
 echo '{"ok":true}';
}catch(Throwable $ex){error_log('Falha ao reconciliar notificação Mercado Pago.');http_response_code(500);echo '{"ok":false}';}
