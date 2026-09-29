<?php
function movimentarEstoque(int $id,int $delta,string $tipo,string $ref,string $motivo): void {
 if(!db()->inTransaction())throw new LogicException('Movimentação precisa de transação.');
 $f=query('SELECT id,nome,estoque FROM ferramentas WHERE id=? FOR UPDATE',[$id])->fetch();
 if(!$f)throw new RuntimeException('Ferramenta não encontrada.');
 $before=(int)$f['estoque'];$after=$before+$delta;
 if($after<0||$after>1000000)throw new RuntimeException('Movimentação deixa o estoque de '.$f['nome'].' fora do limite de 0 a 1.000.000.');
 if(trim($motivo)==='')throw new RuntimeException('Informe o motivo da movimentação.');
 query('UPDATE ferramentas SET estoque=? WHERE id=?',[$after,$id]);
 query('INSERT INTO movimentacoes_estoque(ferramenta_id,codigo_ferramenta,ferramenta_nome,usuario_id,tipo,quantidade,saldo_anterior,saldo_posterior,referencia,motivo) VALUES(?,?,?,?,?,?,?,?,?,?)',[$id,$id,$f['nome'],$_SESSION['user']['id']??null,$tipo,$delta,$before,$after,$ref,$motivo]);
}
function filtrarDatas(string $from,string $to): void {
 foreach([$from,$to] as $v){if($v==='')continue;$d=DateTimeImmutable::createFromFormat('!Y-m-d',$v);if(!$d||$d->format('Y-m-d')!==$v)throw new RuntimeException('Informe datas válidas.');}
 if($from!==''&&$to!==''&&$from>$to)throw new RuntimeException('Período inválido.');
}
