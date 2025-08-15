<?php
/**
 * LAPS Plugin Debug Test
 * Execute este arquivo para testar se o plugin está funcionando corretamente
 */

// Simular ambiente GLPI básico
define('GLPI_ROOT', dirname(__DIR__, 3));
define('PLUGIN_LAPSGLPI_VERSION', '2.0.0');
define('PLUGIN_LAPSGLPI_SCHEMA_VERSION', '2.0.0');
define('PLUGIN_LAPSGLPI_MIN_GLPI_VERSION', '9.5.0');
define('PLUGIN_LAPSGLPI_MAX_GLPI_VERSION', '10.1.0');
define('PLUGIN_LAPSGLPI_IS_OFFICIAL_RELEASE', true);

echo "<h1>LAPS Plugin Debug Test</h1>";

// Teste 1: Verificar se os arquivos principais existem
echo "<h2>1. Verificando arquivos principais:</h2>";
$files_to_check = [
    'setup.php',
    'hook.php',
    'inc/config.class.php',
    'inc/computer.class.php',
    'inc/install.class.php'
];

foreach ($files_to_check as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        echo "✓ {$file} - OK<br>";
    } else {
        echo "✗ {$file} - MISSING<br>";
    }
}

// Teste 2: Verificar sintaxe dos arquivos PHP
echo "<h2>2. Verificando sintaxe dos arquivos:</h2>";
foreach ($files_to_check as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        $output = [];
        $return_var = 0;
        exec("php -l \"$path\"", $output, $return_var);
        
        if ($return_var === 0) {
            echo "✓ {$file} - Sintaxe OK<br>";
        } else {
            echo "✗ {$file} - ERRO DE SINTAXE:<br>";
            echo "<pre>" . implode("\n", $output) . "</pre>";
        }
    }
}

// Teste 3: Verificar funções principais
echo "<h2>3. Verificando funções principais:</h2>";

// Incluir setup.php para testar funções
if (file_exists(__DIR__ . '/setup.php')) {
    include_once __DIR__ . '/setup.php';
    
    $functions_to_check = [
        'plugin_version_lapsglpi',
        'plugin_lapsglpi_check_prerequisites',
        'plugin_lapsglpi_check_config',
        'plugin_init_lapsglpi',
        'plugin_lapsglpi_autoload'
    ];
    
    foreach ($functions_to_check as $function) {
        if (function_exists($function)) {
            echo "✓ {$function}() - OK<br>";
        } else {
            echo "✗ {$function}() - MISSING<br>";
        }
    }
} else {
    echo "✗ Não foi possível carregar setup.php<br>";
}

// Teste 4: Verificar hook.php
echo "<h2>4. Verificando hook.php:</h2>";
if (file_exists(__DIR__ . '/hook.php')) {
    include_once __DIR__ . '/hook.php';
    
    $hook_functions = [
        'plugin_lapsglpi_install',
        'plugin_lapsglpi_uninstall',
        'plugin_lapsglpi_getAddSearchOptions'
    ];
    
    foreach ($hook_functions as $function) {
        if (function_exists($function)) {
            echo "✓ {$function}() - OK<br>";
        } else {
            echo "✗ {$function}() - MISSING<br>";
        }
    }
} else {
    echo "✗ Não foi possível carregar hook.php<br>";
}

echo "<h2>Teste concluído!</h2>";
echo "<p>Se houver erros acima, eles podem estar causando o problema no GLPI.</p>";
?>