<?php
/**
 * Teste simples para identificar o problema
 */

echo "<h2>Teste Simples - Debug LAPS</h2>";

// Verificar se conseguimos incluir o GLPI
try {
    echo "1. Tentando incluir GLPI...<br>";
    include ('../../../inc/includes.php');
    echo "✓ GLPI incluído com sucesso<br>";
} catch (Exception $e) {
    echo "❌ Erro ao incluir GLPI: " . $e->getMessage() . "<br>";
    echo "2. Tentando caminho alternativo...<br>";
    try {
        include ('/var/www/html/glpi/inc/includes.php');
        echo "✓ GLPI incluído com caminho alternativo<br>";
    } catch (Exception $e2) {
        echo "❌ Erro com caminho alternativo: " . $e2->getMessage() . "<br>";
        exit;
    }
}

// Verificar se a classe existe
try {
    echo "2. Verificando classe PluginLapsConfig...<br>";
    if (!class_exists('PluginLapsConfig')) {
        include_once(dirname(__DIR__) . '/inc/config.class.php');
    }
    echo "✓ Classe PluginLapsConfig disponível<br>";
} catch (Exception $e) {
    echo "❌ Erro com classe: " . $e->getMessage() . "<br>";
    exit;
}

// Testar instanciação
try {
    echo "3. Criando instância...<br>";
    $config = new PluginLapsConfig();
    echo "✓ Instância criada<br>";
} catch (Exception $e) {
    echo "❌ Erro ao criar instância: " . $e->getMessage() . "<br>";
    exit;
}

// Testar getConfig
try {
    echo "4. Testando getConfig()...<br>";
    $currentConfig = $config->getConfig();
    echo "✓ getConfig() executado<br>";
    echo "ID atual: " . (isset($currentConfig['id']) ? $currentConfig['id'] : 'não definido') . "<br>";
} catch (Exception $e) {
    echo "❌ Erro em getConfig(): " . $e->getMessage() . "<br>";
    exit;
}

// Testar dados de entrada
echo "5. Testando dados de entrada...<br>";
$input = [
    'laps_server_url' => 'http://test.local',
    'laps_api_key' => 'test123',
    'connection_timeout' => 30,
    'sync_interval' => 60
];
echo "✓ Dados preparados<br>";

// Testar update/add
try {
    echo "6. Testando operação de salvamento...<br>";
    
    if (isset($currentConfig['id']) && $currentConfig['id'] > 0) {
        echo "Tentando UPDATE...<br>";
        $input['id'] = $currentConfig['id'];
        $result = $config->update($input);
        echo "Resultado UPDATE: " . ($result ? 'SUCESSO' : 'FALHA') . "<br>";
    } else {
        echo "Tentando ADD...<br>";
        $result = $config->add($input);
        echo "Resultado ADD: " . ($result ? 'SUCESSO' : 'FALHA') . "<br>";
    }
    
    if ($result) {
        echo "✓ Operação concluída com sucesso<br>";
    } else {
        echo "❌ Operação falhou<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Erro na operação: " . $e->getMessage() . "<br>";
    echo "Stack trace:<br><pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h3>Teste concluído!</h3>";
?>