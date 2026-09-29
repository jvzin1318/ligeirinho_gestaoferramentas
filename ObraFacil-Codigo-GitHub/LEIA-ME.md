# ObraFácil — caixa presencial de ferramentas

Projeto em desenvolvimento, em PHP 8.1+, MySQL, HTML, CSS e JavaScript. Acesso exclusivo de funcionários pelo navegador do computador da loja. Clientes não entram no sistema: o vendedor registra os itens solicitados no balcão. Não há entrega, API de CEP, frete nem criação de compras por clientes na internet.

**Projeto demonstrativo: cada venda é salva no banco e gera uma NOTA DE VENDA DEMONSTRATIVA, SEM VALOR FISCAL. Não é NF-e/NFC-e autorizada e não utiliza SEFAZ ou certificado. Pagamentos e compras podem ser simulados para apresentação.**

## Instalação nova no XAMPP

1. Extraia `ferramentas` em `C:\xampp\htdocs\` e inicie Apache e MySQL.
2. No phpMyAdmin, importe `database/banco.sql`.
3. Ajuste a conexão em `config.php`: host, porta, banco, usuário e senha do MySQL local.
4. Abra `http://localhost/ferramentas/` no próprio computador do servidor. Crie o primeiro gerente, escolhendo nome, cargo Gerente e senha de 4 números. Não existe senha padrão. O instalador fecha após a primeira conta.
5. Entre com esse nome e senha. Cadastre os funcionários em **Usuários**, atribuindo vendedor, estoquista ou gerente.
6. Abra **Abertura e fechamento → Abrir meu caixa** antes da primeira venda.
7. Em `loja.php`, informe os dados reais que devem aparecer no cabeçalho dos comprovantes. Isso não habilita emissão fiscal.

Requisitos: PHP 8.1+ em plataforma 64 bits, PDO MySQL, MySQL 8 ou MariaDB 10.4+, InnoDB e Apache com `.htaccess` habilitado. Não exponha XAMPP/phpMyAdmin na internet. O caixa, os cadastros e relatórios funcionam sem as APIs externas; a consulta opcional de CNPJ exige internet.

## Login por nome e senha de 4 números

- O cadastro tem três campos: Nome, Cargo e Senha. O login tem apenas Nome e Senha. Os cargos são Gerente, Vendedor e Estoquista. Não há usuário de acesso separado nem confirmação de senha no novo cadastro.
- A primeira conta precisa ter cargo Gerente para poder cadastrar os outros funcionários. A opção Gerente vem selecionada no primeiro acesso; o servidor recusa outro cargo para essa primeira conta. Os três cargos estão disponíveis no seletor.
- O nome informado é o nome salvo no cadastro. Espaços repetidos são normalizados. A comparação usa a collation do banco. Não se permite cadastrar duas contas com o mesmo nome, mesmo em cargos diferentes ou simultaneamente pela aplicação. Use, por exemplo, João e João Silva.
- O cargo é obtido automaticamente do cadastro após autenticar nome e senha; enviar um cargo adulterado no login não altera as permissões. Todas as operações continuam protegidas pelo cargo salvo no banco.
- A senha deve conter exatamente quatro algarismos de 0 a 9. Aceita zeros iniciais (ex.: 0042); letras, espaços, três ou cinco números são recusados pelo servidor. O armazenamento continua sendo hash com salt, usando password_hash e password_verify.
- Depois de 10 falhas por IP em 15 minutos, novas tentativas ficam bloqueadas nesse intervalo. A autenticação renova o identificador da sessão e o token CSRF.
- A coluna histórica usuarios.login permanece para compatibilidade, mas não é mais usada para entrar. Contas novas recebem nela um identificador interno aleatório. Não é preciso modificar o banco desta última versão nem recriar usuários.
- Quem já tinha conta com senha longa deve acessar http://localhost/ferramentas/atualizar_acesso.php no computador da instalação. Informe o nome salvo no cadastro, senha antiga e nova senha de quatro números. Sem a senha antiga não é possível converter o hash. Não há senha padrão nem redefinição pública.
- Se a base antiga já contém duas contas com o mesmo nome, o login recusa a ambiguidade. O administrador do banco deve diferenciar os nomes preservando seus IDs antes da atualização das senhas.
- No topo de todas as páginas autenticadas há uma barra fixa durante a rolagem com nome e cargo da sessão. Ela é ocultada na impressão para preservar o formato dos comprovantes e relatórios.
- O relatório de acessos registra as tentativas. A coluna histórica acessos.email guarda agora o nome informado. Fechar o navegador não registra a saída; use Sair.

## Permissões

| Função | Gerente | Vendedor | Estoquista |
|---|---|---|---|
| Cadastrar funcionários | Sim | Não | Não |
| Vender no caixa | Sim | Sim | Não |
| Ver/reimprimir comprovantes | Todos | Somente os próprios | Não |
| Cancelar venda completa e baixar boleto | Sim | Não | Não |
| Clientes PF/PJ | Todos | Os que cadastrou | Não |
| Cadastrar/editar ferramentas e fornecedores | Sim | Não | Sim |
| Excluir ferramenta sem histórico | Sim | Não | Não |
| Relatórios de vendas e estoque | Todos | Vendas próprias | Estoque |
| Consultar logins | Sim | Não | Não |
| Rankings mensais e exportações | Sim | Não | Não |
| Registrar compras de fornecedores / entradas | Sim | Não | Sim |
| Cancelar compra de fornecedor | Sim | Não | Não |
| Histórico de movimentações do estoque | Sim | Não | Sim |
| Devoluções e trocas | Sim | Não | Não |
| Abrir e fechar caixa | Todos | Somente o próprio | Não |

## Venda no caixa e comprovante

1. O vendedor abre seu caixa em **Abertura e fechamento** e escolhe um cliente/empresa cadastrado ou **Consumidor final**.
2. Busca a ferramenta por ID no campo do caixa e informa as quantidades. Filtrar não remove itens já selecionados; **Mostrar todas** restaura a lista. Enter na busca apenas localiza/foca o item, sem concluir a venda. Uma venda admite até 200 tipos de itens. Preços e disponibilidade são conferidos no servidor.
3. Seleciona Pix, cartão de crédito ou boleto. Não há coleta de dados do cartão.
4. Para Pix/cartão, confere o recebimento no banco/maquininha e marca a confirmação. O sistema registra essa conferência manual; não comprova automaticamente o recebimento, não debita cartões e não gera QR Pix.
5. Para boleto, informa a referência do documento emitido no banco. A venda fica **A receber**, mesmo se a confirmação tiver sido marcada. O gerente registra a baixa somente após conferir o banco. Não há emissão de boleto bancário nesta tela.
6. **Concluir venda e gerar comprovante** grava cabeçalho, itens, valores e baixa de estoque em uma transação. Se qualquer gravação falhar, todas são desfeitas. Uma chave por formulário e bloqueio impedem vendas duplicadas por reenvio.
7. Abre o comprovante interno com número, data, funcionário, cliente, itens, quantidades, preços, total, meio e situação do pagamento. Use **Imprimir / salvar PDF**; a impressão é em A4 retrato pelo navegador. Não há impressão silenciosa ou integração com impressora fiscal/térmica.
8. Reimpressão disponível no histórico. Nome/preço dos itens e dados de loja/cliente/funcionário são preservados no momento da venda. A situação atual do pagamento e eventual cancelamento aparecem no documento.

O comprovante exibe **SEM VALOR FISCAL — NÃO É NOTA FISCAL**. Boleto pendente é marcado **PAGAMENTO PENDENTE**, sem quitação. Não existe chave fiscal inventada, QR SEFAZ ou número de NF gerado localmente.

O gerente cancela a venda completa pela tela do comprovante, com caixa aberto. Para pagamentos recebidos, precisa confirmar o estorno manual. Vendas vinculadas a devolução ou troca usam o fluxo de devolução para os itens restantes; não admitem cancelamento completo. O cancelamento marca todos os itens, devolve estoque uma única vez e preserva o histórico. Não executa estorno bancário. Não é permitido cancelar isoladamente um item vinculado ao comprovante. Registros anteriores ao caixa continuam consultáveis com comprovante histórico e pagamento não informado quando esse dado não existia.

Vendas a receber entram no total de vendas, não significam dinheiro já recebido. Relatórios mostram a forma e a situação do pagamento. Cada linha corresponde a um item, identificado pelo número do comprovante; não conte linhas como quantidade de atendimentos.

## Catálogo, estoque e relatórios

- 15 ferramentas e três fornecedores fictícios para demonstração. Corrija preços, estoque e dados reais antes do uso.
- Cada ferramenta é vinculada a fornecedor. Estoque 0–5: vermelho **repor agora**; 6–10: amarelo **precisa repor**; acima de 10: disponível. O campo legado `minimo` não altera essa regra.
- Gerente consulta todo o histórico de vendas, estoque atual e logins, com filtro de datas, CSV e impressão. CSV protege textos interpretáveis como fórmulas.
- Vendedor gera relatórios de vendas próprias, semanal e mensal. Semanas são segunda a domingo, incluindo semanas que cruzam mês/ano; não se limitam a quatro por mês.
- Estoquista gera estoque inicial no dia 01, final no último dia real do mês e semanal no domingo. A captura é da posição atual: deve ocorrer antes das operações para abertura e depois das operações para fechamento. Não reconstrói posições passadas e não há agendamento automático.
- Fluxo: **Meus relatórios → selecionar tipo/data → Gerar e visualizar → Enviar**. O envio é interno para **Relatórios recebidos** da gerência.
- Uma cópia por usuário, tipo e período. O conteúdo é congelado na geração; alterações posteriores nas vendas não mudam cópias já geradas. Rascunhos são privados do autor. Enviar duas vezes não duplica.
- Cadastros PF/PJ suportam CPF e CNPJ; e-mail/telefone são opcionais. BrasilAPI consulta CNPJ numérico; CNPJ alfanumérico é validado e pode ser preenchido manualmente.

## Atualização de versão anterior

Faça backup do banco e preserve `config.php`, `loja.php` e eventuais configurações privadas. Instalação nova importa somente `banco.sql`. Para uma base anterior, aplique apenas as migrações ainda não aplicadas:

1. `atualizar_perfis.sql` e `atualizar_relatorios.sql`, se ainda necessários.
2. `atualizar_comercial.sql` e `fornecedores_exemplo.sql`, caso ainda não tenha os cadastros comerciais.
3. `atualizar_login.sql`, **uma única vez**, se `usuarios.login` ainda não existir. Na atualização para nome/cargo/PIN, use atualizar_acesso.php com a senha antiga para definir os quatro números. Nenhum hash é convertido automaticamente.
4. `atualizar_caixa.sql` cria cabeçalhos e vínculos dos comprovantes sem apagar vendas antigas.
5. `atualizar_compras.sql` cria o histórico de compras dos fornecedores e entradas de estoque.
6. **Nesta atualização:** pare os lançamentos, faça backup e importe `atualizar_operacoes.sql` no banco ferramentas. Se já usa a versão de login por nome e PIN, esta é a única migração nova. Ela cria oito tabelas, calcula vendas líquidas diretamente nas consultas e preserva os dados e vincula cada compra antiga a um documento individual.
7. Substitua os arquivos preservando `config.php` e `loja.php`; saia das sessões e entre novamente. Cada funcionário abre seu caixa para as novas operações.

Importar `banco.sql` não atualiza colunas de tabelas existentes. Migrações não apagam tabelas antigas de entrega; o aplicativo não as utiliza. Relatórios já enviados permanecem intactos.

Os arquivos Mercado Pago, webhook e configuração foram mantidos exclusivamente para conciliar cobranças de versões anteriores. A criação de pedidos/links online foi removida do fluxo e das permissões. **Pedidos anteriores** aparece no menu apenas quando existem esses registros. As vendas novas do caixa não precisam de Mercado Pago, domínio público ou webhook. Credenciais antigas, se necessárias para conciliação, são configuradas no servidor; nunca no navegador.

## Rankings mensais exclusivos do gerente

Abra **Rankings mensais**, escolha mês/ano e relatório. Há exportação CSV e impressão / salvar PDF. O gerente acessa página, consulta e exportações; vendedores e estoquistas não acessam, inclusive por URL direta. O mês atual aparece como parcial. Relatórios mensais são consultas do banco e refletem cancelamentos posteriores; as cópias já enviadas pelos funcionários permanecem congeladas.

| Ranking | Critério principal | Informações adicionais |
|---|---|---|
| Funcionários que mais venderam | Valor vendido em R$ | Atendimentos e peças; qualquer funcionário que vendeu, inclusive gerente |
| Fornecedores de quem mais compramos | Custo total das compras recebidas | Documentos e unidades compradas |
| Clientes / empresas que mais compraram | Valor comprado em R$ | CPF/CNPJ, número de compras e peças |
| Ferramentas mais vendidas | Quantidade de peças vendidas | Faturamento por ferramenta |
| Ferramentas menos vendidas | Quantidade crescente, incluindo zero | Ferramentas ainda cadastradas antes do fim do mês |

- Não apaga vendas: cancelamentos ficam no histórico, mas são excluídos dos rankings. Devoluções são descontadas do valor e das quantidades da venda original, inclusive em consultas ao mês dessa venda. Trocas geram nova venda atribuída ao gerente que realizou a operação; os itens devolvidos deixam de contar para o vendedor original. Um comprovante totalmente devolvido deixa de contar como atendimento no ranking líquido.
- Empates no critério principal recebem a mesma posição. São exibidos todos os participantes elegíveis, sem um limite artificial de dez resultados.
- Um comprovante com vários itens conta um atendimento; seu valor não é repetido por item no ranking de clientes.
- Clientes são agrupados pelo CPF/CNPJ salvo no comprovante. Compras anônimas como Consumidor final e registros antigos sem documento não entram no ranking de clientes. Para identificar o cliente, cadastre e selecione-o na venda.
- Os rankings de valor vendido incluem boleto a receber. Não representam necessariamente dinheiro recebido.
- Funcionários e fornecedores são agrupados por ID; trocar o fornecedor atual de uma ferramenta não reatribui compras antigas.
- Relatórios de ferramentas usam o catálogo ainda existente e a data de cadastro para excluir itens criados após o mês consultado. Produtos excluídos sem movimento não têm histórico de meses sem vendas.

## Compras e entradas de fornecedores

Gerente ou estoquista abre **Compras e entradas**, escolhe um fornecedor ativo e informa quantidades e custos unitários de várias ferramentas vinculadas a ele, além da data e do número da nota/documento opcional. Todos os itens são gravados juntos em `compras_documentos`, ligados às linhas existentes de `compras_estoque` por `compras_documento_itens`. O preço de compra não altera automaticamente o preço de venda. Cada documento é de um único fornecedor; use outra compra para outro fornecedor.

A gravação do documento, itens, saldo e histórico é atômica: falha em qualquer item desfaz tudo. Reenvio do mesmo formulário não duplica a compra. O histórico mostra documentos e permite abrir seus itens. O ranking conta um documento como uma compra, sem multiplicar o valor do cabeçalho pelas linhas.

O gerente pode cancelar todo o documento, informando o motivo. O cancelamento exige estoque disponível para desfazer todos os itens; se um deles não tiver unidades suficientes, nada é cancelado. O histórico é preservado. Compras não representam pagamentos ao fornecedor e não movimentam o caixa de recebimentos dos clientes.

## Histórico de movimentações do estoque

**Histórico de estoque** está disponível para gerente e estoquista, com busca por ID, período, CSV e impressão. Exibe as últimas 500 linhas do filtro, com data, ferramenta, responsável, tipo, variação, saldos anterior/posterior, referência e motivo.

Vendas, compras, cancelamentos, devoluções, cadastro inicial, exclusão de ferramenta sem vendas/compras e ajustes manuais registram suas movimentações na mesma transação do saldo. Editar o estoque exige motivo. O histórico conserva nome e código da ferramenta mesmo se um cadastro sem vínculos de compras/vendas for excluído. A exclusão registra a saída do saldo restante.

A migração cria um **Saldo de implantação**, com a quantidade disponível naquele momento. Não inventa movimentações antigas. Registros de compra antigos não geram nova entrada ao serem vinculados aos documentos. Os relatórios de estoque inicial/semanal/final continuam sendo capturas manuais nas datas definidas; esta entrega não implementa reconstrução de estoque passado.

## Devoluções e trocas

Somente o gerente acessa **Devoluções e trocas**. Busque o número do comprovante ou abra a venda e clique **Registrar devolução / troca**. Informe motivo e quantidade de cada item recebido, limitada ao que ainda não foi devolvido. Marque se a unidade pode voltar ao estoque disponível; desmarque em caso de avaria. Avarias ficam registradas na devolução, sem aumentar o saldo vendável.

Para uma devolução de venda paga, escolha Pix/cartão para o reembolso, confirme a conferência financeira e mantenha seu caixa aberto. Para boleto ainda pendente, a devolução abate o valor a receber; a emissão/adequação do boleto no banco é externa. A baixa de boleto posterior usa apenas o valor dos itens restantes.

Na **Troca**, selecione também os novos itens. O sistema preserva os itens e preços originais devolvidos, utiliza os preços atuais dos novos itens e gera um novo comprovante vinculado. A diferença é novos itens menos devolvidos: positiva exige receber, negativa exige reembolsar, zero apenas compensa. Somente a diferença entra nos movimentos financeiros. Troca exige venda quitada; para boleto pendente, registre devolução e uma venda nova. Não há estorno ou cobrança bancária automática.

O registro é atômico e não duplica por reenvio. É possível devolver posteriormente itens de uma venda gerada por troca, respeitando o saldo ainda não devolvido. Não é possível cancelar toda uma venda já vinculada a troca/devolução nem apagar uma devolução finalizada. Há documento demonstrativo para imprimir; as vendas originais permanecem consultáveis. Registros antigos sem cabeçalho de comprovante continuam no fluxo histórico, sem esta devolução por comprovante.

## Abertura e fechamento de caixa

Gerente e vendedor abrem seu próprio caixa em **Abertura e fechamento**; cada funcionário pode ter um aberto. O vendedor consulta/fecha somente o próprio; gerente pode conferir/fechar qualquer um. O sistema mantém Pix, cartão e boleto, com saldo operacional inicial zero. Não há dinheiro físico, fundo de troco ou sangria nesta versão.

Vendas exigem caixa aberto. Recebimentos, reembolsos, diferenças de troca e estornos de cancelamentos entram no caixa aberto do funcionário que registrou a operação. Boleto pendente conta nas vendas e no saldo a receber, mas não nos recebimentos. Boletos pagos posteriormente entram no caixa de quem registrar a baixa. Vendas anteriores à migração não são creditadas artificialmente em novos caixas; um reembolso atual de uma venda antiga aparecerá como saída atual.

Ao fechar, informe o valor líquido conferido de Pix, cartão e boleto. Valores negativos são aceitos se houve mais reembolso que recebimento. Se houver diferença em relação ao sistema, informe uma observação. O fechamento preserva o resumo e os valores conferidos; operações posteriores não o reescrevem. Pode ser impresso/salvo em PDF. Os novos lançamentos precisam de novo caixa aberto. Reenvio do fechamento não altera o original.

## Comprovante do projeto

A nota de venda demonstrativa conserva a identificação **SEM VALOR FISCAL — NÃO É NOTA FISCAL**. O número é interno, sem chave fiscal, QR fiscal, protocolo ou tributos inventados. Não depende de cadastro de uma empresa real para demonstrar a venda e os relatórios.

## Validação e limites

Os 34 arquivos PHP passaram por análise sintática em PHP 8.3 e os JavaScripts por `node --check`. Foram executados testes das funções de senha, permissões, totalização, comprovantes, reenvio, falha de estoque, rollback após falha no segundo item, cancelamento idempotente, boleto pendente/recebido e escape HTML. Também foram testados compras e entradas, cancelamento de compra, rollback, agrupamentos mensais, empates, limites de mês, zero vendas, cliente identificado/anônimo, contagem por comprovante e restrição dos rankings ao gerente.

Os testes de serviço usaram SQLite em memória com adaptação limitada de SQL; NÃO validam instalação, SQL ou concorrência real de MySQL/MariaDB. Testar no XAMPP antes do uso: criação de gerente/funcionário; combinação incorreta de login/senha; venda com vários itens; concorrência entre dois caixas; indisponibilidade de estoque; cancelamento e reimpressão; bloqueio das URLs por cargo; PDF/impresso no equipamento da loja. Não houve emissão fiscal ou cobrança real, conforme o escopo demonstrativo definido. A prévia HTTPS anterior é um protótipo antigo e não executa esta versão local.

## Validação desta alteração de acesso

Os 35 arquivos PHP passaram por análise sintática em PHP 8.3 e os JavaScripts por node --check. Testes executando o código PHP real, com SQLite e adaptação limitada de SQL, passaram para: senha de exatamente quatro números, zeros iniciais, rejeição de formatos inválidos, hash com salt, cadastro dos três cargos, rejeição de nomes duplicados em qualquer cargo, autenticação por nome/senha, cargo da sessão obtido do banco mesmo se enviado outro no formulário, permissões de vendedor e estoquista, bloqueio após repetidas falhas e atualização de senha antiga com prova da senha anterior. Isso não valida concorrência, a renderização no navegador ou instalação real no MySQL/MariaDB. Faça o primeiro acesso e confira as telas no XAMPP. O banco e suas chaves foram preservados.

## Validação desta entrega de operações

Os 41 arquivos PHP passaram por análise sintática em PHP 8.3. Testes com execução do PHP real e SQLite (adaptação limitada de SQL) cobriram compras com vários itens, reenvio, rollback após falha no segundo item, cancelamento completo, histórico de ajustes, devolução parcial, avaria sem reposição, troca com diferenças positivas/negativas, impedimento de devolução excedente, confirmação financeira, abertura/fechamento, valores a receber e baixa de boleto, preservação do fechamento, permissões, rankings líquidos e por documento. A migração foi exercitada no adaptador, incluindo repetição e preservação de compras antigas. As páginas foram renderizadas em HTML pelo PHP, e a busca por ID, preservação de carrinho, filtro de fornecedor e seleção de troca foram verificados em DOM simulado.

Não foi executado um servidor MySQL/MariaDB nem um navegador gráfico completo nesta entrega. O adaptador não valida bloqueios/concorrência real de MySQL. Valide no XAMPP antes da apresentação, especialmente importação da migração, dois caixas simultâneos e impressão. Não há emissão fiscal nem cobrança bancária real.
