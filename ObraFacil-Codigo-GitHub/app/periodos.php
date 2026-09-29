<?php
function tiposRelatorio(): array {
    return [
        'estoque_inicial'=>'Estoque inicial do mês',
        'estoque_final'=>'Estoque final do mês',
        'estoque_semanal'=>'Estoque semanal',
        'vendas_semanal'=>'Vendas semanais',
        'vendas_mensal'=>'Vendas mensais',
    ];
}
function periodoRelatorio(string $tipo, string $referencia): array {
    $date=DateTimeImmutable::createFromFormat('!Y-m-d', $referencia);
    if (!$date || $date->format('Y-m-d')!==$referencia || $date->format('Y')<'1000' || $date->format('Y')>'9998') {
        throw new RuntimeException('Informe uma data válida.');
    }
    if (!isset(tiposRelatorio()[$tipo])) throw new RuntimeException('Tipo de relatório inválido.');
    if (str_ends_with($tipo, '_semanal')) {
        $start=$date->modify('-'.((int)$date->format('N')-1).' days');
        $end=$start->modify('+6 days');
    } else {
        $start=$date->modify('first day of this month');
        $end=$date->modify('last day of this month');
        if ($tipo==='estoque_inicial') $end=$start;
        if ($tipo==='estoque_final') $start=$end;
    }
    return [$start->format('Y-m-d'), $end->format('Y-m-d')];
}
