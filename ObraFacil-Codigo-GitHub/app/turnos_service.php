<?php
require_once __DIR__.'/comercial.php';
function valorAssinado(int $c): string {return ($c<0?'-':'').decimalValor(abs($c));}
function centavosAssinados(string $v): int {$neg=str_starts_with($v,'-');return ($neg?-1:1)*centavos($neg?substr($v,1):$v);}
function caixaAtual(bool $lock=false): ?array {
 $c=query('SELECT * FROM sessoes_caixa WHERE usuario_aberto=?'.($lock?' FOR UPDATE':''),[$_SESSION['user']['id']])->fetch();return $c?:null;
}
function exigirCaixa(): array {
 if(!db()->inTransaction())throw new LogicException('Use uma transação.');
 $c=caixaAtual(true);if(!$c)throw new RuntimeException('Abra seu caixa em Abertura e fechamento antes de registrar esta operação.');return $c;
}
function abrirCaixa(): int {
 exigirPermissao('open_cash');db()->beginTransaction();
 try {query('SELECT id FROM usuarios WHERE id=? FOR UPDATE',[$_SESSION['user']['id']])->fetch();
  $c=caixaAtual(true);if($c){db()->rollBack();return (int)$c['id'];}
  query('INSERT INTO sessoes_caixa(usuario_id,usuario_aberto) VALUES(?,?)',[$_SESSION['user']['id'],$_SESSION['user']['id']]);$id=(int)db()->lastInsertId();db()->commit();return $id;
 }catch(Throwable $e){if(db()->inTransaction())db()->rollBack();throw $e;}
}
function registrarFinanceiro(int $caixa,int $comprovante,string $tipo,string $forma,int $cents,string $key,string $ref=''): void {
 if(!db()->inTransaction())throw new LogicException('Financeiro precisa de transação.');
 if(!in_array($forma,['Pix','Cartão de crédito','Boleto'],true)||abs($cents)>99999999999999)throw new RuntimeException('Movimento financeiro inválido.');
 if(!$cents)return;
 query('INSERT INTO caixa_movimentos(sessao_id,comprovante_id,usuario_id,tipo,forma,valor,chave,referencia) VALUES(?,?,?,?,?,?,?,?)',[$caixa,$comprovante,$_SESSION['user']['id'],$tipo,$forma,valorAssinado($cents),$key,$ref]);
}
function resumoCaixa(int $id): array {
 $forms=['Pix'=>0,'Cartão de crédito'=>0,'Boleto'=>0];
 foreach(query('SELECT forma,valor FROM caixa_movimentos WHERE sessao_id=? ORDER BY id',[$id])->fetchAll() as $r)$forms[$r['forma']]+=centavosAssinados($r['valor']);
 $sales=query("SELECT cp.id,cp.pagamento_status,COALESCE(SUM(v.total_liquido),0) total FROM caixa_vendas cv JOIN comprovantes cp ON cp.id=cv.comprovante_id JOIN comprovante_itens ci ON ci.comprovante_id=cp.id JOIN ".vendasLiquidasSql()." v ON v.id=ci.venda_id WHERE cv.sessao_id=? AND cp.status='Concluída' GROUP BY cp.id,cp.pagamento_status",[$id])->fetchAll();
 $pending=0;$total=0;foreach($sales as $r){$n=centavos($r['total']);$total+=$n;if($r['pagamento_status']==='A receber')$pending+=$n;}
 return ['formas'=>$forms,'recebido_liquido'=>array_sum($forms),'vendas_liquidas'=>$total,'a_receber'=>$pending,'gerado_em'=>date('Y-m-d H:i:s')];
}
function fecharCaixa(int $id): void {
 exigirPermissao('close_cash');db()->beginTransaction();
 try {
  $c=query('SELECT * FROM sessoes_caixa WHERE id=? FOR UPDATE',[$id])->fetch();
  if(!$c||(!admin()&&(int)$c['usuario_id']!==(int)$_SESSION['user']['id']))throw new RuntimeException('Caixa não encontrado.');
  if($c['fechado_em']){db()->rollBack();return;}
  $resumo=resumoCaixa($id);$conferido=[];$diff=false;
  foreach(['pix'=>'Pix','cartao'=>'Cartão de crédito','boleto'=>'Boleto'] as $key=>$forma){$s=str_replace(',','.',trim((string)($_POST[$key]??'')));if(!preg_match('/^-?[0-9]{1,12}(\.[0-9]{1,2})?$/D',$s))throw new RuntimeException('Informe o valor líquido conferido de '.$forma.'.');$n=centavosAssinados($s);$conferido[$forma]=$n;if($n!==$resumo['formas'][$forma])$diff=true;}
  $obs=campoOpcional('observacao',255);if($diff&&$obs==='')throw new RuntimeException('Explique a diferença entre valores registrados e conferidos.');
  query('UPDATE sessoes_caixa SET usuario_aberto=NULL,fechado_em=NOW(),fechado_por=?,resumo=?,conferido=?,observacao=? WHERE id=?',[$_SESSION['user']['id'],json_encode($resumo,JSON_THROW_ON_ERROR),json_encode($conferido,JSON_THROW_ON_ERROR),$obs,$id]);db()->commit();
 }catch(Throwable $e){if(db()->inTransaction())db()->rollBack();throw $e;}
}
