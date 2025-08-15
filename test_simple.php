<?php
// Teste simples para verificar se o plugin está funcionando
echo "Teste iniciado...\n";

// Simular dados POST
$_POST = array(
    'laps_server_url' => 'http://test.com',
    'laps_api_key' => 'test123',
    'connection_timeout' => 30,
    'cache_duration' => 3600,
    'is_active' => 1,
    'submit' => 'Salvar'
);

// Simular parâmetro debug
$_GET['debug'] = '1';

echo "Dados POST simulados:\n";
print_r($_POST);

echo "\nTentando incluir arquivos do GLPI...\n";

// Tentar incluir o arquivo principal do GLPI
if (file_exists('/var/www/html/glpi/inc/includes.php')) {
    echo "Arquivo includes.php encontrado\n";
    require_once '/var/www/html/glpi/inc/includes.php';
    echo "Arquivo includes.php incluído com sucesso\n";
} else {
    echo "ERRO: Arquivo includes.php não encontrado\n";
    exit(1);
}

echo "\nTentando carregar classe PluginLapsConfig...\n";

// Verificar se a classe existe
if (class_exists('PluginLapsConfig')) {
    echo "Classe PluginLapsConfig encontrada\n";
    
    $config = new PluginLapsConfig();
    echo "Instância da classe criada\n";
    
    // Tentar adicionar configuração
    echo "\nTentando adicionar configuração...\n";
    $result = $config->add($_POST);
    
    if ($result) {
        echo "Configuração adicionada com sucesso! ID: $result\n";
    } else {
        echo "ERRO: Falha ao adicionar configuração\n";
    }
    
} else {
    echo "ERRO: Classe PluginLapsConfig não encontrada\n";
}

echo "\nTeste finalizado.\n";
?>