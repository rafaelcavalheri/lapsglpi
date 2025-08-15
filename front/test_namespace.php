<?php
// Teste para verificar se o namespace está funcionando

// Incluir o GLPI
if (file_exists('../../../inc/includes.php')) {
    include ('../../../inc/includes.php');
} elseif (file_exists('/var/www/html/glpi/inc/includes.php')) {
    include ('/var/www/html/glpi/inc/includes.php');
} else {
    die('Erro: Não foi possível encontrar o arquivo includes.php do GLPI');
}

echo "<h2>Teste de Namespace</h2>";

// Verificar se o autoload do GLPI está funcionando
echo "<p>Verificando autoload...</p>";

// Tentar carregar o arquivo manualmente
$configFile = dirname(__DIR__) . '/src/Config.php';
echo "<p>Arquivo Config.php: $configFile</p>";

if (file_exists($configFile)) {
    echo "<p>Arquivo existe: <strong style='color: green;'>SIM</strong></p>";
    
    // Incluir o arquivo
    include_once($configFile);
    
    // Verificar se a classe com namespace existe
    if (class_exists('GlpiPlugin\\Laps\\Config')) {
        echo "<p>Classe GlpiPlugin\\Laps\\Config: <strong style='color: green;'>EXISTE</strong></p>";
        
        try {
            $config = new GlpiPlugin\Laps\Config();
            echo "<p>Instância criada: <strong style='color: green;'>OK</strong></p>";
            
            // Verificar métodos
            $methods = get_class_methods($config);
            echo "<p>Métodos disponíveis: " . implode(', ', array_slice($methods, 0, 10)) . "...</p>";
            
        } catch (Exception $e) {
            echo "<p>Erro ao criar instância: <strong style='color: red;'>" . $e->getMessage() . "</strong></p>";
        } catch (Error $e) {
            echo "<p>Erro fatal: <strong style='color: red;'>" . $e->getMessage() . "</strong></p>";
        }
    } else {
        echo "<p>Classe GlpiPlugin\\Laps\\Config: <strong style='color: red;'>NÃO EXISTE</strong></p>";
    }
    
    // Verificar se existe sem namespace
    if (class_exists('Config')) {
        echo "<p>Classe Config (sem namespace): <strong style='color: green;'>EXISTE</strong></p>";
    } else {
        echo "<p>Classe Config (sem namespace): <strong style='color: red;'>NÃO EXISTE</strong></p>";
    }
    
} else {
    echo "<p>Arquivo não existe: <strong style='color: red;'>NÃO</strong></p>";
}

// Verificar classes declaradas
echo "<hr><h3>Classes declaradas com 'Config':</h3>";
$classes = get_declared_classes();
$configClasses = array_filter($classes, function($class) {
    return strpos($class, 'Config') !== false;
});
echo "<pre>" . implode("\n", array_slice($configClasses, 0, 20)) . "</pre>";
?>