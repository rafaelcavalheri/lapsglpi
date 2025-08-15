<?php
/**
 * Script de teste para debug do formulário de configuração
 */

include ('../../../inc/includes.php');

// Verificar se as classes do plugin estão carregadas
if (!class_exists('PluginLapsConfig')) {
    include_once(dirname(__DIR__) . '/inc/config.class.php');
}

echo "<h2>Debug do Formulário LAPS</h2>";

// Simular dados POST
$_POST = [
    'update' => '1',
    'laps_server_url' => 'http://test-server.local',
    'laps_api_key' => 'test-api-key-123',
    'connection_timeout' => '30',
    'sync_interval' => '60'
];

echo "<h3>1. Dados POST simulados:</h3>";
echo "<pre>" . print_r($_POST, true) . "</pre>";

try {
    echo "<h3>2. Criando instância PluginLapsConfig...</h3>";
    $config = new PluginLapsConfig();
    echo "✓ Instância criada com sucesso<br>";
    
    echo "<h3>3. Processando formulário...</h3>";
    
    // Processar formulário
    if (isset($_POST['update']) || isset($_POST['submit'])) {
        $input = $_POST;
        
        echo "✓ POST data recebido<br>";
        
        // Validar dados obrigatórios
        if (empty($input['laps_server_url'])) {
            echo "❌ ERRO: LAPS Server URL é obrigatório<br>";
            $message = 'LAPS Server URL is required';
            $redirect_url = 'config.form.php?message=' . urlencode($message) . '&success=0';
            echo "Redirecionaria para: " . $redirect_url . "<br>";
        } else {
            echo "✓ LAPS Server URL válido: " . $input['laps_server_url'] . "<br>";
        }
        
        if (empty($input['laps_api_key'])) {
            echo "❌ ERRO: LAPS API Key é obrigatório<br>";
            $message = 'LAPS API Key is required';
            $redirect_url = 'config.form.php?message=' . urlencode($message) . '&success=0';
            echo "Redirecionaria para: " . $redirect_url . "<br>";
        } else {
            echo "✓ LAPS API Key válido<br>";
        }
        
        // Processar API Key
        if ($input['laps_api_key'] === '••••••••••••••••') {
            echo "✓ API Key é placeholder, mantendo atual<br>";
            $currentConfig = $config->getConfig();
            if (!empty($currentConfig['laps_api_key'])) {
                $input['laps_api_key'] = $currentConfig['laps_api_key'];
            }
        }
        
        echo "<h3>4. Tentando salvar configuração...</h3>";
        
        // Obter configuração atual
        $currentConfig = $config->getConfig();
        echo "✓ Configuração atual obtida (ID: " . $currentConfig['id'] . ")<br>";
        
        try {
            if ($currentConfig['id'] > 0) {
                // Atualizar configuração existente
                $input['id'] = $currentConfig['id'];
                echo "Tentando atualizar configuração existente...<br>";
                if ($config->update($input)) {
                    $message = 'Configuration updated successfully';
                    $success = true;
                    echo "✓ Configuração atualizada com sucesso<br>";
                } else {
                    $message = 'Error updating configuration';
                    $success = false;
                    echo "❌ Erro ao atualizar configuração<br>";
                }
            } else {
                // Criar nova configuração
                echo "Tentando criar nova configuração...<br>";
                if ($config->add($input)) {
                    $message = 'Configuration created successfully';
                    $success = true;
                    echo "✓ Configuração criada com sucesso<br>";
                } else {
                    $message = 'Error creating configuration';
                    $success = false;
                    echo "❌ Erro ao criar configuração<br>";
                }
            }
        } catch (Exception $e) {
            $message = 'Error saving configuration: ' . $e->getMessage();
            $success = false;
            echo "❌ Exceção ao salvar: " . $e->getMessage() . "<br>";
        }
        
        echo "<h3>5. Resultado final:</h3>";
        echo "Mensagem: " . $message . "<br>";
        echo "Sucesso: " . ($success ? 'SIM' : 'NÃO') . "<br>";
        
        // Redirecionamento
        $redirect_url = 'config.form.php?message=' . urlencode($message) . '&success=' . ($success ? '1' : '0');
        echo "<h3>6. Redirecionamento:</h3>";
        echo "URL: " . $redirect_url . "<br>";
        echo "<p><strong>Em produção, redirecionaria agora com header('Location: ...')</strong></p>";
    }
    
} catch (Exception $e) {
    echo "❌ ERRO CRÍTICO: " . $e->getMessage() . "<br>";
    echo "Stack trace:<br><pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h3>7. Teste concluído</h3>";
?>