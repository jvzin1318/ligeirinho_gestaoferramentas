<?php
require_once __DIR__.'/comercial.php';
function integracoes(): array {static $c;if(!$c)$c=require __DIR__.'/../integracoes.php';return $c;}
function apiJson(string $service,string $method,string $path,?array $body=null,array $extra=[]): array {
 $c=integracoes();if($service!=='mp')throw new RuntimeException('Integração não disponível.');$token=$c['mp_token'];
 if(!$token)throw new RuntimeException('Mercado Pago ainda não configurado. Peça ao gerente para configurar a integração.');
 if(!function_exists('curl_init'))throw new RuntimeException('Habilite a extensão cURL do PHP.');
 $base='https://api.mercadopago.com';
 $headers=['Authorization: Bearer '.$token,'Accept: application/json','Content-Type: application/json'];
 $ch=curl_init($base.$path);curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_HTTPHEADER=>array_merge($headers,$extra),CURLOPT_CONNECTTIMEOUT=>8,CURLOPT_TIMEOUT=>25,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2]);
 if($body!==null)curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($body,JSON_THROW_ON_ERROR));
 $raw=curl_exec($ch);$http=curl_getinfo($ch,CURLINFO_RESPONSE_CODE);curl_close($ch);
 if($raw===false||$http<200||$http>=300)throw new RuntimeException('O serviço externo não concluiu a operação. Confira a configuração e tente consultar o status antes de repetir uma cobrança.');
 $data=json_decode($raw,true,512,JSON_THROW_ON_ERROR);if(!is_array($data))throw new RuntimeException('Resposta inválida do serviço externo.');return $data;
}
function checkoutUrlPermitida(string $url): bool {
 $p=parse_url($url);return ($p['scheme']??'')==='https'&&in_array($p['host']??'',['www.mercadopago.com.br','sandbox.mercadopago.com.br'],true);
}
