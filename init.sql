-- Arquivo de inicialização do banco de dados para testes do plugin LAPS-GLPI

-- Criar banco de dados se não existir
CREATE DATABASE IF NOT EXISTS glpi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Usar o banco de dados
USE glpi;

-- Inserir alguns computadores de teste (será executado após a instalação do GLPI)
-- Estes comandos serão executados apenas se as tabelas já existirem

-- Criar procedure para inserir dados de teste
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS InsertTestData()
BEGIN
    DECLARE table_exists INT DEFAULT 0;
    
    -- Verificar se a tabela glpi_computers existe
    SELECT COUNT(*) INTO table_exists 
    FROM information_schema.tables 
    WHERE table_schema = 'glpi' AND table_name = 'glpi_computers';
    
    IF table_exists > 0 THEN
        -- Inserir computadores de teste se não existirem
        INSERT IGNORE INTO glpi_computers (name, serial, otherserial, contact, contact_num, users_id_tech, groups_id_tech, comment, date_mod, date_creation, is_deleted, is_template, template_name, manufacturers_id, computermodels_id, computertypes_id, is_dynamic, uuid) VALUES
        ('DESKTOP-TEST01', 'SN001', '', 'Admin', '', 0, 0, 'Computador de teste 1', NOW(), NOW(), 0, 0, NULL, 0, 0, 0, 0, UUID()),
        ('LAPTOP-TEST02', 'SN002', '', 'User', '', 0, 0, 'Computador de teste 2', NOW(), NOW(), 0, 0, NULL, 0, 0, 0, 0, UUID()),
        ('SERVER-TEST03', 'SN003', '', 'IT', '', 0, 0, 'Servidor de teste', NOW(), NOW(), 0, 0, NULL, 0, 0, 0, 0, UUID());
        
        SELECT 'Dados de teste inseridos com sucesso!' as message;
    ELSE
        SELECT 'Tabela glpi_computers não encontrada. Execute após a instalação do GLPI.' as message;
    END IF;
END//
DELIMITER ;

-- Criar evento para executar a procedure após um tempo
-- (para dar tempo do GLPI ser instalado)
SET GLOBAL event_scheduler = ON;

CREATE EVENT IF NOT EXISTS insert_test_data_event
ON SCHEDULE AT CURRENT_TIMESTAMP + INTERVAL 2 MINUTE
DO
BEGIN
    CALL InsertTestData();
    DROP EVENT IF EXISTS insert_test_data_event;
END;

-- Mensagem de inicialização
SELECT 'Banco de dados inicializado para testes do plugin LAPS-GLPI' as status;