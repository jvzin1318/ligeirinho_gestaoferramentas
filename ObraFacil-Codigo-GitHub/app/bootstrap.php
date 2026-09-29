<?php
declare(strict_types=1);
date_default_timezone_set('America/Sao_Paulo');
ini_set('session.use_strict_mode', '1');
session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax','secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
session_start();
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; style-src 'self'; script-src 'self'; connect-src 'self' https://brasilapi.com.br; form-action 'self'; frame-ancestors 'none'; base-uri 'self'");
header('Cache-Control: no-store');
function db(): PDO {
 static $db;
 if (!$db) {
  $c=require __DIR__.'/../config.php';
  $db=new PDO("mysql:host={$c['host']};port={$c['port']};dbname={$c['database']};charset=utf8mb4",$c['username'],$c['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
  $db->exec("SET time_zone = '-03:00'");
 }
 return $db;
}
function query(string $sql,array $args=[]): PDOStatement { $s=db()->prepare($sql); $s->execute($args); return $s; }
function vendasLiquidasSql(): string { return '(SELECT v.*,COALESCE(d.quantidade_devolvida,0) quantidade_devolvida,v.quantidade-COALESCE(d.quantidade_devolvida,0) quantidade_liquida,v.total-COALESCE(d.total_devolvido,0) total_liquido FROM vendas v LEFT JOIN (SELECT venda_id,SUM(quantidade) quantidade_devolvida,SUM(total) total_devolvido FROM devolucao_itens GROUP BY venda_id) d ON d.venda_id=v.id)'; }
function e($s): string {return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');}
function stockStatus(int $quantity): array {
 if($quantity<=5)return ['label'=>'repor agora','class'=>'critical'];
 if($quantity<=10)return ['label'=>'precisa repor','class'=>'warn'];
 return ['label'=>'Disponível','class'=>''];
}
function stockBadge(int $quantity): string {
 $status=stockStatus($quantity);
 return '<span class="badge '.e($status['class']).'">'.e($status['label']).'</span>';
}
function money($s): string {return 'R$ '.number_format((float)$s,2,',','.');}
function go(string $path): void {header('Location: '.$path);exit;}
function token(): string {return $_SESSION['csrf'] ?? ($_SESSION['csrf']=bin2hex(random_bytes(32)));}
function csrf(): void {echo '<input type="hidden" name="csrf" value="'.e(token()).'">';}
function checkCsrf(): void {if (!hash_equals(token(),(string)($_POST['csrf']??''))) {http_response_code(403);exit('Formulário expirado. Volte e recarregue a página.');}}
require_once __DIR__.'/permissoes.php';
require_once __DIR__.'/autenticacao.php';
function admin(): bool {return perfil()==='gerente';}
function requireAdmin(): void {if(!admin()){http_response_code(403);exit('Acesso restrito ao gerente.');}}
function flash(string $s): void {$_SESSION['flash']=$s;}
function input(string $name,int $max=150): string {$s=trim((string)($_POST[$name]??''));if($s===''||strlen($s)>$max)throw new RuntimeException('Preencha corretamente o campo '.$name.'.');return $s;}
function integer(string $name,int $min=0,int $max=1000000): int {$v=filter_var($_POST[$name]??null,FILTER_VALIDATE_INT);if($v===false||$v===null||$v<$min||$v>$max)throw new RuntimeException('Valor inválido para '.$name.'.');return $v;}
function price(string $s): string {$s=str_replace(',','.',trim($s));if(!preg_match('/^\d{1,8}(\.\d{1,2})?$/D',$s))throw new RuntimeException('Informe um preço válido, com até duas casas decimais.');return $s;}
function page(string $title,bool $auth=true): void { ?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=e($title)?> • ObraFácil</title><link rel="stylesheet" href="assets/style.css"><?php if(($_GET['p']??'')==='comprovante'):?><link rel="stylesheet" href="assets/comprovante.css"><?php endif;?><script defer src="assets/app.js"></script><script defer src="assets/comercial.js"></script></head><body>
<?php if($auth):?><div class="session-bar" aria-label="Funcionário conectado"><span>Conectado como</span><strong><?=e($_SESSION['user']['nome'])?></strong><span class="session-role"><?=e(nomePerfil())?></span></div><?php endif;?>
<?php if($auth): ?><aside><a class="brand" href="index.php">OF<span>ObraFácil<small>Gestão de ferramentas</small></span></a><nav><?php foreach(['inicio'=>'Visão geral','turnos'=>'Abertura e fechamento','ferramentas'=>'Ferramentas','vendas'=>'Caixa presencial','pedidos'=>'Pedidos anteriores','clientes'=>'Clientes','fornecedores'=>'Fornecedores','compras'=>'Compras e entradas','movimentacoes'=>'Histórico de estoque','devolucoes'=>'Devoluções e trocas','rankings'=>'Rankings mensais','integracoes'=>'Integrações','relatorios'=>'Consultas', 'envios'=>admin()?'Relatórios recebidos':'Meus relatórios'] as $k=>$v):if(!pode($k)||($k==='pedidos'&&!query('SELECT id FROM pedidos LIMIT 1')->fetchColumn()))continue;?><a class="<?=($_GET['p']??paginaInicial())===$k?'active':''?>" href="index.php?p=<?=$k?>"><?=e($v)?></a><?php endforeach;?><?php if(admin()):?><a href="index.php?p=usuarios">Usuários</a><?php endif;?></nav><div class="account"><b><?=e($_SESSION['user']['nome'])?></b><small><?=e(nomePerfil())?></small>
<form method="post" action="index.php"><?php csrf();?><input type="hidden" name="action" value="logout"><button class="secondary">Sair</button>
</form></div></aside><?php endif;?>
<main class="<?=$auth?'':'guest'?>"><header><div><small class="eyebrow">OBRAFÁCIL / <?= $auth?'PAINEL DE GESTÃO':'ACESSO AO SISTEMA'?></small><h1><?=e($title)?></h1></div><?php if($auth):?><span class="date"><?=date('d/m/Y')?></span><?php endif;?></header>
<?php if(isset($_SESSION['flash'])):?><div class="notice" role="status"><?=e($_SESSION['flash'])?></div><?php unset($_SESSION['flash']);endif;
}
function endpage(): void {echo '</main></body></html>';}
function emptyRow(int $cols): void {echo '<tr><td class="empty" colspan="'.$cols.'">Nenhum registro encontrado.</td></tr>';}
