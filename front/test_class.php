<?php
// Teste para verificar se a classe PluginLapsglpiConfig está sendo carregada

// Incluir o GLPI
if (file_exists('../../../inc/includes.php')) {
    include ('../../../inc/includes.php');
} elseif (file_exists('/var/www/html/glpi/inc/includes.php')) {
    include ('/var/www/html/glpi/inc/includes.php');
} else {
    die('Erro: Não foi possível encontrar o arquivo includes.php do GLPI');
}

echo "<h2>Teste de Classes</h2>";

// Verificar se a classe PluginLapsglpiConfig existe
echo "<p>Verificando PluginLapsglpiConfig: ";
if (class_exists('PluginLapsglpiConfig')) {
    echo "<strong style='color: green;'>EXISTE</strong></p>";
    
    try {
        $config = new PluginLapsglpiConfig();
        echo "<p>Instância criada com sucesso: <strong style='color: green;'>OK</strong></p>";
        
        // Verificar métodos disponíveis
        $methods = get_class_methods($config);
        echo "<p>Métodos disponíveis: " . implode(', ', $methods) . "</p>";
        
    } catch (Exception $e) {
        echo "<p>Erro ao criar instância: <strong style='color: red;'>" . $e->getMessage() . "</strong></p>";
    }
} else {
    echo "<strong style='color: red;'>NÃO EXISTE</strong></p>";
    
    // Tentar carregar manualmente
    echo "<p>Tentando carregar manualmente...</p>";
    $configFile = dirname(__DIR__) . '/src/Config.php';
    echo "<p>Arquivo: $configFile</p>";
    
    if (file_exists($configFile)) {
        echo "<p>Arquivo existe: <strong style='color: green;'>SIM</strong></p>";
        include_once($configFile);
        
        if (class_exists('PluginLapsglpiConfig')) {
            echo "<p>Classe carregada após include: <strong style='color: green;'>SIM</strong></p>";
        } else {
            echo "<p>Classe ainda não existe após include: <strong style='color: red;'>NÃO</strong></p>";
        }
    } else {
        echo "<p>Arquivo não existe: <strong style='color: red;'>NÃO</strong></p>";
    }
}

// Verificar também a classe PluginLapsConfig
echo "<hr><p>Verificando PluginLapsConfig: ";
if (class_exists('PluginLapsConfig')) {
    echo "<strong style='color: green;'>EXISTE</strong></p>";
} else {
    echo "<strong style='color: red;'>NÃO EXISTE</strong></p>";
}
?>