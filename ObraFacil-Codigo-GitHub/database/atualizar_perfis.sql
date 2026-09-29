-- APENAS para quem já importou a primeira versão. Faça backup antes.
-- Pode executar novamente; não apaga usuários, produtos, vendas ou acessos.
USE ferramentas;
ALTER TABLE usuarios MODIFY perfil ENUM('admin','gerente','vendedor','estoquista') NOT NULL DEFAULT 'vendedor';
UPDATE usuarios SET perfil='gerente' WHERE perfil='admin';
ALTER TABLE usuarios MODIFY perfil ENUM('gerente','vendedor','estoquista') NOT NULL DEFAULT 'vendedor';
