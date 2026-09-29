<?php
require_once __DIR__.'/periodos.php';
function autorizarTipo(string $tipo): void {
    if (!isset(tiposRelatorio()[$tipo])) throw new RuntimeException('Tipo de relatório inválido.');
    exigirPermissao(str_starts_with($tipo,'estoque_')?'gerar_estoque':'gerar_vendas');
}
function gerarRelatorio(string $tipo,string $referencia): int {
    autorizarTipo($tipo);
    [$start,$end]=periodoRelatorio($tipo,$referencia);
    $user=$_SESSION['user']['id'];
    $existing=query('SELECT id FROM relatorios_enviados WHERE usuario_id=? AND tipo=? AND periodo_inicio=? AND periodo_fim=?',[$user,$tipo,$start,$end])->fetchColumn();
    if($existing) return (int)$existing;
    $today=date('Y-m-d');
    if(str_starts_with($tipo,'estoque_')) {
        if($end!==$today) throw new RuntimeException('O estoque deve ser registrado na data de fechamento: '.$end.'. Não é possível reconstruir estoque passado nesta versão.');
        $columns=['ID','Ferramenta','Marca','Categoria','Preço (R$)','Estoque','Situação'];
        $rows=query('SELECT id,nome,marca,categoria,preco,estoque FROM ferramentas ORDER BY nome')->fetchAll(PDO::FETCH_NUM);
        foreach($rows as &$stockRow){$stockRow[]=stockStatus((int)$stockRow[5])['label'];}unset($stockRow);
        $note='Posição do estoque no momento da geração. Não representa movimentações da semana. Faça a abertura antes das operações e o fechamento depois das operações do dia.';
    } else {
        if($end>$today) throw new RuntimeException('Este período ainda não terminou. Gere a partir de '.$end.'.');
        $filter=admin()?'':' AND v.usuario_id=?';
        $args=[$start,$end];if(!admin())$args[]=$user;
        $columns=['Venda','Data','Ferramenta','Cliente','Quantidade','Preço unitário (R$)','Total (R$)','Status','Vendedor','Comprovante','Forma de pagamento','Situação do pagamento','Qtd. devolvida','Qtd. líquida','Valor líquido (R$)'];
        $rows=query("SELECT v.id,v.criado_em,v.produto_nome,v.cliente,v.quantidade,v.preco_unitario,v.total,v.status,u.nome,COALESCE(cp.id,'Registro antigo'),COALESCE(cp.forma_pagamento,'Não informada'),COALESCE(cp.pagamento_status,'Não informado'),v.quantidade_devolvida,v.quantidade_liquida,v.total_liquido FROM ".vendasLiquidasSql()." v JOIN usuarios u ON u.id=v.usuario_id LEFT JOIN comprovante_itens ci ON ci.venda_id=v.id LEFT JOIN comprovantes cp ON cp.id=ci.comprovante_id WHERE v.criado_em>=? AND v.criado_em<DATE_ADD(?,INTERVAL 1 DAY) $filter ORDER BY v.id",$args)->fetchAll(PDO::FETCH_NUM);
        $note=admin()?'Vendas de todos os vendedores.':'Somente as vendas do responsável por este relatório.';
        $note.=' Vendas com boleto a receber não representam dinheiro já recebido. Valores e status registrados no momento da geração; cancelamentos e devoluções posteriores não alteram esta cópia. As colunas líquidas descontam as devoluções já registradas; cancelamentos continuam identificados pelo status.';
    }
    $payload=json_encode(['columns'=>$columns,'rows'=>$rows,'note'=>$note,'responsavel'=>$_SESSION['user']['nome']],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
    // Um relatório por responsável/tipo/período; cliques repetidos reutilizam a cópia original.
    query('INSERT INTO relatorios_enviados (usuario_id,tipo,periodo_inicio,periodo_fim,conteudo) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)',[$user,$tipo,$start,$end,$payload]);
    return (int)db()->lastInsertId();
}
function enviarRelatorio(int $id): void {
    // Somente o autor encaminha; o gerente vê cópias de terceiros após o envio.
    query('UPDATE relatorios_enviados SET enviado_em=NOW() WHERE id=? AND usuario_id=? AND enviado_em IS NULL',[$id,$_SESSION['user']['id']]);
    $sent=query('SELECT enviado_em FROM relatorios_enviados WHERE id=? AND usuario_id=?',[$id,$_SESSION['user']['id']])->fetchColumn();
    if(!$sent)throw new RuntimeException('Relatório não encontrado para envio.');
}
