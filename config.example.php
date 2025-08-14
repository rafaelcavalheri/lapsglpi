<?php
/**
 * Plugin LAPS-GLPI - Configuração de Exemplo
 * 
 * Este arquivo contém as configurações padrão para o plugin LAPS-GLPI.
 * Copie este arquivo para config.php e ajuste as configurações conforme necessário.
 * 
 * @author Rafael Cavalheri
 * @version 1.0.0
 */

// Configurações do servidor LAPS
$laps_config = [
    // URL do servidor LAPS (obrigatório)
    'laps_server_url' => 'https://laps.mogimirim.sp.gov.br/api.php',
    
    // Chave da API do servidor LAPS (obrigatório)
    // Esta chave deve ser gerada no servidor LAPS
    'laps_api_key' => 'glpi-laps-integration-2025',
    
    // Timeout da conexão em segundos (opcional, padrão: 30)
    'connection_timeout' => 30,
    
    // Duração do cache em segundos (opcional, padrão: 300 = 5 minutos)
    'cache_duration' => 300,
    
    // Ativar/desativar o plugin (opcional, padrão: true)
    'is_active' => true,
    
    // Configurações de segurança (opcional)
    'security' => [
        // Permitir apenas usuários com permissão de leitura em computadores
        'require_computer_read' => true,
        
        // Permitir apenas usuários com permissão de atualização para sincronizar
        'require_computer_update' => true,
        
        // Log de todas as visualizações de senha
        'log_password_views' => true,
        
        // Criptografar senhas no cache (recomendado)
        'encrypt_passwords' => true,
    ],
    
    // Configurações de notificação (opcional)
    'notifications' => [
        // Notificar quando uma senha expirar
        'password_expiry' => false,
        
        // Notificar quando houver erro de sincronização
        'sync_errors' => false,
        
        // Email para notificações (se habilitado)
        'email' => 'admin@example.com',
    ],
    
    // Configurações de debug (opcional, apenas para desenvolvimento)
    'debug' => [
        // Habilitar logs detalhados
        'enabled' => false,
        
        // Nível de log (1=erros, 2=avisos, 3=info, 4=debug)
        'level' => 1,
        
        // Arquivo de log (relativo ao diretório do plugin)
        'log_file' => 'debug.log',
    ]
];

// Configurações de exemplo para diferentes ambientes
$environment_configs = [
    'development' => [
        'laps_server_url' => 'http://localhost:8080/api.php',
        'laps_api_key' => 'dev-key-123',
        'connection_timeout' => 10,
        'cache_duration' => 60,
        'debug' => ['enabled' => true, 'level' => 4]
    ],
    
    'staging' => [
        'laps_server_url' => 'https://laps-staging.example.com/api.php',
        'laps_api_key' => 'staging-key-456',
        'connection_timeout' => 30,
        'cache_duration' => 300,
        'debug' => ['enabled' => true, 'level' => 2]
    ],
    
    'production' => [
        'laps_server_url' => 'https://laps.mogimirim.sp.gov.br/api.php',
        'laps_api_key' => 'glpi-laps-integration-2025',
        'connection_timeout' => 30,
        'cache_duration' => 300,
        'debug' => ['enabled' => false, 'level' => 1],
        'security' => [
            'require_computer_read' => true,
            'require_computer_update' => true,
            'log_password_views' => true,
            'encrypt_passwords' => true,
        ]
    ]
];

// Para usar uma configuração específica, descomente a linha abaixo:
// $laps_config = $environment_configs['production'];

// Exemplo de como usar este arquivo:
/*
1. Copie este arquivo para config.php
2. Ajuste as configurações conforme seu ambiente
3. Certifique-se de que o arquivo config.php não está versionado no git
4. O plugin irá carregar automaticamente estas configurações

Exemplo de uso:
$config = include 'config.php';
$server_url = $config['laps_server_url'];
$api_key = $config['laps_api_key'];
*/

// Retornar a configuração
return $laps_config;
?>