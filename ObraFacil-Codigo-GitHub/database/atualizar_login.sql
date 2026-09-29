USE ferramentas;
-- Executar uma vez em instalações anteriores. Não altera os hashes existentes.
-- Se login já existir, não reexecute este ALTER; instalações novas usam banco.sql.
ALTER TABLE usuarios ADD COLUMN login VARCHAR(190) NULL UNIQUE;
UPDATE usuarios SET login=LOWER(email) WHERE login IS NULL;
ALTER TABLE usuarios MODIFY login VARCHAR(190) NOT NULL;
ALTER TABLE usuarios MODIFY email VARCHAR(190) NULL DEFAULT NULL;
-- Funcionários antigos entram com o antigo e-mail no campo Usuário e a mesma senha.
