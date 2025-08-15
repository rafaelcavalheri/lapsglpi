-- Plugin LAPS-GLPI - Installation SQL
-- Arquivo SQL para instalação manual das tabelas (se necessário)

-- Tabela de configurações do plugin
CREATE TABLE IF NOT EXISTS `glpi_plugin_laps_configs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `laps_server_url` varchar(255) NOT NULL DEFAULT '',
  `laps_api_key` varchar(255) NOT NULL DEFAULT '',
  `connection_timeout` int(11) NOT NULL DEFAULT 30,
  `cache_duration` int(11) NOT NULL DEFAULT 300,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `date_creation` datetime DEFAULT NULL,
  `date_mod` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Tabela de cache de senhas LAPS
CREATE TABLE IF NOT EXISTS `glpi_plugin_laps_passwords` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `computers_id` int(10) unsigned NOT NULL,
  `computer_name` varchar(255) NOT NULL,
  `admin_password` text,
  `password_expiry` datetime DEFAULT NULL,
  `last_sync` datetime DEFAULT NULL,
  `sync_status` varchar(50) DEFAULT 'pending',
  `error_message` text,
  `date_creation` datetime DEFAULT NULL,
  `date_mod` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `computers_id` (`computers_id`),
  KEY `computer_name` (`computer_name`),
  KEY `sync_status` (`sync_status`),
  KEY `last_sync` (`last_sync`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Tabela de logs de atividades
CREATE TABLE IF NOT EXISTS `glpi_plugin_laps_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `computers_id` int(10) unsigned NOT NULL,
  `computer_name` varchar(255) NOT NULL,
  `action` varchar(100) NOT NULL,
  `user_id` int(11) NOT NULL,
  `details` text,
  `date_creation` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `computers_id` (`computers_id`),
  KEY `action` (`action`),
  KEY `user_id` (`user_id`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Inserir configuração padrão
INSERT INTO `glpi_plugin_laps_configs` (
  `laps_server_url`,
  `laps_api_key`,
  `connection_timeout`,
  `cache_duration`,
  `is_active`,
  `date_creation`
) VALUES (
  'https://laps.mogimirim.sp.gov.br/api.php',
  '5deeb8a3-e591-4bd4-8bfb-f9d8b117844c',
  30,
  300,
  1,
  NOW()
) ON DUPLICATE KEY UPDATE
  `date_mod` = NOW();

-- Índices adicionais para performance
ALTER TABLE `glpi_plugin_laps_passwords` 
ADD INDEX `idx_sync_status_date` (`sync_status`, `last_sync`),
ADD INDEX `idx_computer_status` (`computer_name`, `sync_status`);

ALTER TABLE `glpi_plugin_laps_logs`
ADD INDEX `idx_computer_action_date` (`computers_id`, `action`, `date_creation`),
ADD INDEX `idx_user_date` (`user_id`, `date_creation`);

-- Comentários nas tabelas
ALTER TABLE `glpi_plugin_laps_configs` 
COMMENT = 'Configurações do plugin LAPS-GLPI';

ALTER TABLE `glpi_plugin_laps_passwords` 
COMMENT = 'Cache de senhas LAPS dos computadores';

ALTER TABLE `glpi_plugin_laps_logs` 
COMMENT = 'Logs de atividades do plugin LAPS';

-- Comentários nas colunas
ALTER TABLE `glpi_plugin_laps_configs`
MODIFY COLUMN `laps_server_url` varchar(255) NOT NULL DEFAULT '' COMMENT 'URL do servidor LAPS',
MODIFY COLUMN `laps_api_key` varchar(255) NOT NULL DEFAULT '' COMMENT 'Chave da API LAPS (criptografada)',
MODIFY COLUMN `connection_timeout` int(11) NOT NULL DEFAULT 30 COMMENT 'Timeout em segundos',
MODIFY COLUMN `cache_duration` int(11) NOT NULL DEFAULT 300 COMMENT 'Duração do cache em segundos',
MODIFY COLUMN `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Plugin ativo (0=não, 1=sim)';

ALTER TABLE `glpi_plugin_laps_passwords`
MODIFY COLUMN `computers_id` int(11) NOT NULL COMMENT 'ID do computador no GLPI',
MODIFY COLUMN `computer_name` varchar(255) NOT NULL COMMENT 'Nome do computador',
MODIFY COLUMN `admin_password` text COMMENT 'Senha do administrador local (criptografada)',
MODIFY COLUMN `password_expiry` datetime DEFAULT NULL COMMENT 'Data de expiração da senha',
MODIFY COLUMN `last_sync` datetime DEFAULT NULL COMMENT 'Última sincronização',
MODIFY COLUMN `sync_status` varchar(50) DEFAULT 'pending' COMMENT 'Status da sincronização',
MODIFY COLUMN `error_message` text COMMENT 'Mensagem de erro (se houver)';

ALTER TABLE `glpi_plugin_laps_logs`
MODIFY COLUMN `computers_id` int(11) NOT NULL COMMENT 'ID do computador no GLPI',
MODIFY COLUMN `computer_name` varchar(255) NOT NULL COMMENT 'Nome do computador',
MODIFY COLUMN `action` varchar(100) NOT NULL COMMENT 'Ação realizada',
MODIFY COLUMN `user_id` int(11) NOT NULL COMMENT 'ID do usuário que realizou a ação',
MODIFY COLUMN `details` text COMMENT 'Detalhes da ação';

-- Verificar se as tabelas foram criadas corretamente
SELECT 
    TABLE_NAME,
    TABLE_COMMENT,
    CREATE_TIME
FROM 
    information_schema.TABLES 
WHERE 
    TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME LIKE 'glpi_plugin_laps_%'
ORDER BY 
    TABLE_NAME;