<?php
// A mesma política controla menus, páginas e operações POST.
function perfil(): string {
    $role = $_SESSION['user']['perfil'] ?? '';
    return $role === 'admin' ? 'gerente' : $role; // Compatibilidade com a versão anterior.
}
function pode(string $permission): bool {
    $rules = [
        'gerente' => ['turnos','open_cash','close_cash','movimentacoes','devolucoes','save_return','rankings','compras','save_purchase','cancel_purchase','comprovante','cancel_receipt','receive_boleto','pedidos','clientes','save_customer','sync_order','cancel_order','fornecedores','save_supplier','supplier_active','integracoes','envios', 'generate_report', 'send_report', 'gerar_estoque', 'gerar_vendas', 'inicio', 'ferramentas', 'vendas', 'relatorios', 'usuarios', 'save_product', 'delete_product', 'sale', 'cancel_sale', 'user', 'logout', 'report_vendas', 'report_estoque', 'report_acessos'],
        'vendedor' => ['turnos','open_cash','close_cash','comprovante','pedidos','clientes','save_customer','sync_order','envios', 'generate_report', 'send_report', 'gerar_vendas', 'vendas', 'sale', 'logout'],
        'estoquista' => ['movimentacoes','compras','save_purchase','fornecedores','save_supplier','envios', 'generate_report', 'send_report', 'gerar_estoque', 'ferramentas', 'relatorios', 'save_product', 'logout', 'report_estoque'],
    ];
    return in_array($permission, $rules[perfil()] ?? [], true);
}
function exigirPermissao(string $permission): void {
    if (!pode($permission)) {
        http_response_code(403);
        exit('Seu perfil não tem permissão para esta operação.');
    }
}
function paginaInicial(): string {
    return ['gerente'=>'inicio', 'vendedor'=>'vendas', 'estoquista'=>'ferramentas'][perfil()] ?? 'vendas';
}
function nomePerfil(): string {
    return ['gerente'=>'Gerente', 'vendedor'=>'Vendedor', 'estoquista'=>'Estoquista'][perfil()] ?? 'Sem acesso';
}
