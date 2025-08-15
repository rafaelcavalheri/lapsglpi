<?php
/**
 * Teste do formulário com sessão simulada
 */

// Incluir arquivos do GLPI
require_once '/var/www/html/glpi/inc/includes.php';

// Simular uma sessão de administrador
$_SESSION['glpiID'] = 2; // ID do usuário glpi (admin)
$_SESSION['glpiname'] = 'glpi';
$_SESSION['glpiactiveentities'] = [0];
$_SESSION['glpiactiveentities_string'] = "'0'";
$_SESSION['glpiroot'] = '/var/www/html/glpi';
$_SESSION['glpi_use_mode'] = Session::NORMAL_MODE;
$_SESSION['glpiprofiles'] = [
    4 => [
        'id' => 4,
        'name' => 'Super-Admin',
        'config' => 'w'
    ]
];
$_SESSION['glpiactiveprofile'] = [
    'id' => 4,
    'name' => 'Super-Admin',
    'config' => 'w'
];

// Simular dados POST
$_POST = [
    'laps_server_url' => 'http://test.com',
    'laps_api_key' => 'test123',
    'connection_timeout' => 30,
    'cache_duration' => 3600,
    'is_active' => 1,
    'submit' => 'Salvar'
];

// Simular parâmetro debug
$_GET['debug'] = '1';

echo "Teste com sessão simulada iniciado...\n";
echo "Dados POST: \n";
print_r($_POST);

// Verificar se a classe existe
if (!class_exists('PluginLapsConfig')) {
    include_once '/var/www/html/glpi/plugins/lapsglpi/inc/config.class.php';
}

if (class_exists('PluginLapsConfig')) {
    echo "\nClasse PluginLapsConfig encontrada\n";
    
    try {
        $config = new PluginLapsConfig();
        echo "Instância criada com sucesso\n";
        
        // Verificar se já existe configuração
        $currentConfig = $config->getConfig();
        echo "Configuração atual: \n";
        print_r($currentConfig);
        
        if ($currentConfig['id'] > 0) {
            // Atualizar
            $_POST['id'] = $currentConfig['id'];
            $result = $config->update($_POST);
            echo "\nResultado da atualização: " . ($result ? 'Sucesso' : 'Falha') . "\n";
        } else {
            // Adicionar
            $result = $config->add($_POST);
            echo "\nResultado da adição: " . ($result ? "Sucesso (ID: $result)" : 'Falha') . "\n";
        }
        
    } catch (Exception $e) {
        echo "\nErro: " . $e->getMessage() . "\n";
    }
    
} else {
    echo "\nERRO: Classe PluginLapsConfig não encontrada\n";
}

echo "\nTeste finalizado.\n";
?>