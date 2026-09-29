# ObraFácil — gestão e venda presencial de ferramentas

Sistema demonstrativo em **PHP 8.1+, MySQL/MariaDB, HTML, CSS e JavaScript** para uma loja de ferramentas. Gerente, vendedor e estoquista têm acesso por nome e PIN de quatro dígitos, com permissões diferentes. A senha é armazenada com `password_hash`; o sistema mantém histórico das tentativas de login.

## Funcionalidades

- Cadastro de ferramentas, fornecedores, clientes e funcionários; estoque com avisos de reposição em até 10 e até 5 unidades.
- Compras de fornecedores com vários itens, entradas e histórico de estoque.
- Caixa presencial, vendas de vários itens, registro de Pix, cartão e boleto; devoluções, trocas e cancelamentos.
- Comprovante demonstrativo de cada venda, relatórios de vendas/estoque enviados à gerência e rankings mensais.

**O comprovante não é nota fiscal eletrônica (NF-e/NFC-e) e não tem valor fiscal.** Pagamentos registrados na demonstração não confirmam cobrança bancária. Não use dados reais no ambiente gratuito de testes.

## Executar

1. Tenha PHP 8.1+ com PDO MySQL, MySQL 8 ou MariaDB 10.4+, e Apache com `.htaccess` habilitado.
2. Crie o banco e importe `database/banco.sql` localmente. Em hospedagem que já cria o banco, selecione-o no phpMyAdmin e importe `database/banco_hospedagem.sql`.
3. Copie `config.example.php` para `config.php` e preencha host, porta, nome do banco, usuário e senha. Na hospedagem, defina `install_key` como uma chave longa e aleatória.
4. Copie `loja.example.php` para `loja.php` e `integracoes.example.php` para `integracoes.php`. Os arquivos de configuração não são versionados.
5. Abra `/instalar.php` e crie o primeiro gerente. Depois, apague o valor de `install_key` em `config.php` e acesse `/index.php` para fazer login.
6. Abra um caixa antes de registrar a primeira venda. O projeto contém ferramentas e fornecedores fictícios para testar.

Para detalhes de permissões, períodos de relatórios, histórico e migrações, veja [LEIA-ME.md](LEIA-ME.md). A configuração do teste por navegador está em [DEPLOY-TESTE.txt](DEPLOY-TESTE.txt). Nunca publique o `config.php` preenchido, dados de clientes, senhas ou backups do banco.
