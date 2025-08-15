<?php
/**
 * Teste de permissões para o plugin LAPS
 */

// Incluir arquivos do GLPI
require_once '/var/www/html/glpi/inc/includes.php';

echo "=== TESTE DE PERMISSÕES LAPS ===\n\n";

// Verificar se há sessão ativa
echo "1. Verificação de Sessão:\n";
echo "   - Session ID: " . (isset($_SESSION['glpiID']) ? $_SESSION['glpiID'] : 'Não definido') . "\n";
echo "   - User Name: " . (isset($_SESSION['glpiname']) ? $_SESSION['glpiname'] : 'Não definido') . "\n";
echo "   - Active Profile: " . (isset($_SESSION['glpiactiveprofile']['name']) ? $_SESSION['glpiactiveprofile']['name'] : 'Não definido') . "\n";

// Verificar permissões
echo "\n2. Verificação de Permissões:\n";
echo "   - Config READ: " . (Session::haveRight('config', READ) ? 'SIM' : 'NÃO') . "\n";
echo "   - Config UPDATE: " . (Session::haveRight('config', UPDATE) ? 'SIM' : 'NÃO') . "\n";
echo "   - Computer READ: " . (Session::haveRight('computer', READ) ? 'SIM' : 'NÃO') . "\n";
echo "   - Computer UPDATE: " . (Session::haveRight('computer', UPDATE) ? 'SIM' : 'NÃO') . "\n";

// Verificar se a classe existe
echo "\n3. Verificação de Classes:\n";
if (!class_exists('PluginLapsConfig')) {
    include_once '/var/www/html/glpi/plugins/lapsglpi/inc/config.class.php';
}
echo "   - PluginLapsConfig: " . (class_exists('PluginLapsConfig') ? 'CARREGADA' : 'NÃO ENCONTRADA') . "\n";

if (class_exists('PluginLapsConfig')) {
    echo "\n4. Teste dos Métodos da Classe:\n";
    echo "   - PluginLapsConfig::canView(): " . (PluginLapsConfig::canView() ? 'SIM' : 'NÃO') . "\n";
    echo "   - PluginLapsConfig::canUpdate(): " . (PluginLapsConfig::canUpdate() ? 'SIM' : 'NÃO') . "\n";
    echo "   - PluginLapsConfig::canCreate(): " . (PluginLapsConfig::canCreate() ? 'SIM' : 'NÃO') . "\n";
    
    // Testar instância
    try {
        $config = new PluginLapsConfig();
        echo "   - Instância criada: SIM\n";
        
        // Testar método showConfigForm
        echo "\n5. Teste do Formulário:\n";
        echo "   - Método canView() da instância: " . ($config->canView() ? 'SIM' : 'NÃO') . "\n";
        echo "   - Método canUpdate() da instância: " . ($config->canUpdate() ? 'SIM' : 'NÃO') . "\n";
        
        // Capturar saída do formulário
        ob_start();
        $result = $config->showConfigForm();
        $formOutput = ob_get_clean();
        
        echo "   - showConfigForm() retornou: " . ($result ? 'TRUE' : 'FALSE') . "\n";
        echo "   - Tamanho da saída HTML: " . strlen($formOutput) . " caracteres\n";
        
        if (strlen($formOutput) > 0) {
            echo "   - Primeiros 200 caracteres da saída:\n";
            echo "     " . substr($formOutput, 0, 200) . "...\n";
        } else {
            echo "   - PROBLEMA: Nenhuma saída HTML gerada!\n";
        }
        
    } catch (Exception $e) {
        echo "   - ERRO ao criar instância: " . $e->getMessage() . "\n";
    }
}

echo "\n=== FIM DO TESTE ===\n";
?>