<?php
/**
 * Teste de envio do formulário de configuração LAPS
 */

// Incluir arquivos do GLPI
require_once '/var/www/html/glpi/inc/includes.php';

echo "=== TESTE DE ENVIO DO FORMULÁRIO LAPS ===\n\n";

// Simular dados POST
$_POST = [
    'update' => '1',
    'laps_server_url' => 'https://laps.mogimirim.sp.gov.br/api.php',
    'laps_api_key' => '5deeb8a3-e591-4bd4-8bfb-f9d8b117844c',
    'connection_timeout' => '30',
    'cache_duration' => '300',
    'is_active' => '1'
];

echo "1. Dados POST simulados:\n";
print_r($_POST);

// Incluir classe do plugin
if (!class_exists('PluginLapsConfig')) {
    include_once '/var/www/html/glpi/plugins/lapsglpi/inc/config.class.php';
}

echo "\n2. Testando criação da instância:\n";
try {
    $config = new PluginLapsConfig();
    echo "   - Instância criada: SIM\n";
    
    // Obter configuração atual
    $currentConfig = $config->getConfig();
    echo "   - Configuração atual ID: " . $currentConfig['id'] . "\n";
    echo "   - URL atual: " . $currentConfig['laps_server_url'] . "\n";
    
    // Preparar dados para atualização
    $input = $_POST;
    
    echo "\n3. Testando atualização/criação:\n";
    
    if ($currentConfig['id'] > 0) {
        // Atualizar configuração existente
        $input['id'] = $currentConfig['id'];
        echo "   - Tentando atualizar configuração existente (ID: " . $currentConfig['id'] . ")\n";
        
        $result = $config->update($input);
        echo "   - Resultado da atualização: " . ($result ? 'SUCESSO' : 'FALHA') . "\n";
        
        if ($result) {
            // Verificar se foi salvo
            $newConfig = $config->getConfig();
            echo "   - Nova URL: " . $newConfig['laps_server_url'] . "\n";
            echo "   - Nova API Key: " . $newConfig['laps_api_key'] . "\n";
        }
    } else {
        // Criar nova configuração
        echo "   - Tentando criar nova configuração\n";
        
        $result = $config->add($input);
        echo "   - Resultado da criação: " . ($result ? 'SUCESSO (ID: ' . $result . ')' : 'FALHA') . "\n";
        
        if ($result) {
            // Verificar se foi salvo
            $newConfig = $config->getConfig();
            echo "   - Nova URL: " . $newConfig['laps_server_url'] . "\n";
            echo "   - Nova API Key: " . $newConfig['laps_api_key'] . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "   - ERRO: " . $e->getMessage() . "\n";
}

echo "\n=== FIM DO TESTE ===\n";
?>