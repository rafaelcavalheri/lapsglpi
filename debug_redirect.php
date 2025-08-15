<?php
/**
 * Debug do redirecionamento
 */

// Capturar todos os outputs
ob_start();

// Tentar incluir o GLPI com diferentes caminhos
if (file_exists('../../../inc/includes.php')) {
    include ('../../../inc/includes.php');
} elseif (file_exists('/var/www/html/glpi/inc/includes.php')) {
    include ('/var/www/html/glpi/inc/includes.php');
} else {
    die('Erro: Não foi possível encontrar o arquivo includes.php do GLPI');
}

// Verificar se as classes do plugin estão carregadas
if (!class_exists('PluginLapsConfig')) {
    include_once(dirname(__DIR__) . '/inc/config.class.php');
}

echo "DEBUG: Iniciando processamento do formulário\n";

// Simular POST
$_POST = [
    'update' => '1',
    'laps_server_url' => 'http://test.local',
    'laps_api_key' => 'test123',
    'connection_timeout' => '30',
    'sync_interval' => '60'
];

echo "DEBUG: POST data configurado\n";

if (isset($_POST['update']) || isset($_POST['submit'])) {
    echo "DEBUG: Entrando no bloco de processamento\n";
    
    $input = $_POST;
    
    // Validar dados obrigatórios
    if (empty($input['laps_server_url'])) {
        echo "DEBUG: URL vazia, redirecionando com erro\n";
        $message = 'LAPS Server URL is required';
        $redirect_url = 'config.form.php?message=' . urlencode($message) . '&success=0';
        
        // Limpar buffer antes do redirecionamento
        $output = ob_get_clean();
        echo "OUTPUT CAPTURADO ANTES DO REDIRECT: " . $output . "\n";
        
        header('Location: ' . $redirect_url);
        exit;
    }
    
    if (empty($input['laps_api_key'])) {
        echo "DEBUG: API Key vazia, redirecionando com erro\n";
        $message = 'LAPS API Key is required';
        $redirect_url = 'config.form.php?message=' . urlencode($message) . '&success=0';
        
        // Limpar buffer antes do redirecionamento
        $output = ob_get_clean();
        echo "OUTPUT CAPTURADO ANTES DO REDIRECT: " . $output . "\n";
        
        header('Location: ' . $redirect_url);
        exit;
    }
    
    echo "DEBUG: Validações passaram, criando config\n";
    
    $config = new PluginLapsConfig();
    
    // Manter API Key como texto simples por enquanto
    if ($input['laps_api_key'] === '••••••••••••••••') {
        echo "DEBUG: API Key é placeholder\n";
        $currentConfig = $config->getConfig();
        if (!empty($currentConfig['laps_api_key'])) {
            $input['laps_api_key'] = $currentConfig['laps_api_key'];
        }
    }
    
    echo "DEBUG: Obtendo configuração atual\n";
    $currentConfig = $config->getConfig();
    
    try {
        if ($currentConfig['id'] > 0) {
            echo "DEBUG: Atualizando configuração existente (ID: " . $currentConfig['id'] . ")\n";
            $input['id'] = $currentConfig['id'];
            if ($config->update($input)) {
                $message = 'Configuration updated successfully';
                $success = true;
                echo "DEBUG: Update bem-sucedido\n";
            } else {
                $message = 'Error updating configuration';
                $success = false;
                echo "DEBUG: Erro no update\n";
            }
        } else {
            echo "DEBUG: Criando nova configuração\n";
            if ($config->add($input)) {
                $message = 'Configuration created successfully';
                $success = true;
                echo "DEBUG: Add bem-sucedido\n";
            } else {
                $message = 'Error creating configuration';
                $success = false;
                echo "DEBUG: Erro no add\n";
            }
        }
    } catch (Exception $e) {
        $message = 'Error saving configuration: ' . $e->getMessage();
        $success = false;
        echo "DEBUG: Exceção: " . $e->getMessage() . "\n";
    }
    
    echo "DEBUG: Preparando redirecionamento\n";
    echo "DEBUG: Mensagem: " . $message . "\n";
    echo "DEBUG: Sucesso: " . ($success ? 'true' : 'false') . "\n";
    
    // Redirecionamento simples com mensagem
    $redirect_url = 'config.form.php?message=' . urlencode($message) . '&success=' . ($success ? '1' : '0');
    echo "DEBUG: URL de redirecionamento: " . $redirect_url . "\n";
    
    // Capturar todo o output antes do redirecionamento
    $output = ob_get_clean();
    echo "\n=== OUTPUT CAPTURADO ANTES DO REDIRECT ===\n";
    echo $output;
    echo "\n=== FIM DO OUTPUT ===\n";
    
    echo "\nTentando redirecionamento agora...\n";
    
    // Verificar se headers já foram enviados
    if (headers_sent($file, $line)) {
        echo "ERRO: Headers já foram enviados em $file na linha $line\n";
        echo "Não é possível fazer redirecionamento!\n";
    } else {
        echo "Headers ainda não foram enviados, fazendo redirecionamento...\n";
        header('Location: ' . $redirect_url);
        exit;
    }
}

echo "DEBUG: Não entrou no bloco de processamento\n";
?>