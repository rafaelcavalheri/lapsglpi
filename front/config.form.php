<?php
/**
 * Plugin LAPS-GLPI - Configuration Form
 * Página de configuração do plugin
 */

include ('../../../inc/includes.php');

// Verificar se as classes do plugin estão carregadas
if (!class_exists('PluginLapsConfig')) {
    // Tentar carregar manualmente
    include_once(dirname(__DIR__) . '/inc/config.class.php');
}

// Debug: verificar se chegou até aqui
if (isset($_GET['debug'])) {
    echo "<h3>Debug Information:</h3>";
    echo "<p>GLPI loaded: " . (defined('GLPI_VERSION') ? 'Yes (' . GLPI_VERSION . ')' : 'No') . "</p>";
    echo "<p>Plugin classes loaded: " . (class_exists('PluginLapsConfig') ? 'Yes' : 'No') . "</p>";
    echo "<p>Session active: " . (isset($_SESSION['glpiID']) ? 'Yes' : 'No') . "</p>";
    echo "<p>Config right: " . (Session::haveRight('config', UPDATE) ? 'Yes' : 'No') . "</p>";
    if (!Session::haveRight('config', UPDATE)) {
        echo "<p style='color: red;'>User does not have config UPDATE rights!</p>";
        exit;
    }
}

Session::checkRight('config', UPDATE);

// Debug: verificar se passou da verificação de direitos
if (isset($_GET['debug'])) {
    echo "<p>Passed rights check</p>";
}

Html::header(__('LAPS Configuration', 'laps'), $_SERVER['PHP_SELF'], 'config', 'PluginLapsConfig');

// Incluir arquivos CSS e JS do plugin no início
echo "<link rel='stylesheet' type='text/css' href='" . $CFG_GLPI['root_doc'] . "/plugins/lapsglpi/css/laps.css'>";
echo "<script type='text/javascript' src='" . $CFG_GLPI['root_doc'] . "/plugins/lapsglpi/js/laps.js'></script>";

// Debug: tentar criar instância da classe
if (isset($_GET['debug'])) {
    echo "<p>Attempting to create PluginLapsConfig instance...</p>";
}

try {
    $config = new PluginLapsConfig();
    if (isset($_GET['debug'])) {
        echo "<p>PluginLapsConfig instance created successfully</p>";
    }
} catch (Exception $e) {
    if (isset($_GET['debug'])) {
        echo "<p style='color: red;'>Error creating PluginLapsConfig: " . $e->getMessage() . "</p>";
    }
    die("Error: Could not create PluginLapsConfig instance");
}

// Processar formulário
if (isset($_POST['update'])) {
    $input = $_POST;
    
    // Validar dados obrigatórios
    if (empty($input['laps_server_url'])) {
        Session::addMessageAfterRedirect(__('LAPS Server URL is required', 'laps'), false, ERROR);
        Html::back();
    }
    
    if (empty($input['laps_api_key'])) {
        Session::addMessageAfterRedirect(__('LAPS API Key is required', 'laps'), false, ERROR);
        Html::back();
    }
    
    // Criptografar API Key (apenas se não for o placeholder e não estiver vazia)
    if (!empty($input['laps_api_key']) && $input['laps_api_key'] !== '••••••••••••••••') {
        $input['laps_api_key'] = Toolbox::encrypt($input['laps_api_key'], GLPIKEY);
    } elseif ($input['laps_api_key'] === '••••••••••••••••') {
        // Se for o placeholder, manter a API Key atual
        $currentConfig = $config->getConfig();
        if (!empty($currentConfig['laps_api_key'])) {
            $input['laps_api_key'] = Toolbox::encrypt($currentConfig['laps_api_key'], GLPIKEY);
        }
    }
    
    // Atualizar ou inserir configuração
    $currentConfig = $config->getConfig();
    
    if ($currentConfig['id'] > 0) {
        // Atualizar configuração existente
        $input['id'] = $currentConfig['id'];
        if ($config->update($input)) {
            Session::addMessageAfterRedirect(__('Configuration updated successfully', 'laps'), false, INFO);
        } else {
            Session::addMessageAfterRedirect(__('Error updating configuration', 'laps'), false, ERROR);
        }
    } else {
        // Criar nova configuração
        if ($config->add($input)) {
            Session::addMessageAfterRedirect(__('Configuration created successfully', 'laps'), false, INFO);
        } else {
            Session::addMessageAfterRedirect(__('Error creating configuration', 'laps'), false, ERROR);
        }
    }
    
    Html::redirect($_SERVER['PHP_SELF']);
}

// Testar conexão via AJAX
if (isset($_POST['test_connection_ajax'])) {
    header('Content-Type: application/json');
    
    $config = [];
    $config['laps_server_url'] = $_POST['laps_server_url'] ?? '';
    $config['laps_api_key'] = $_POST['laps_api_key'] ?? '';
    $config['connection_timeout'] = $_POST['connection_timeout'] ?? 30;
    
    $result = PluginLapsConfig::testConnection($config);
    
    echo json_encode($result);
    exit;
}

// Testar conexão
if (isset($_POST['test_connection'])) {
    $input = $_POST;
    
    // Preparar configuração para teste
    $testConfig = [
        'laps_server_url' => $input['laps_server_url'],
        'laps_api_key' => $input['laps_api_key'],
        'connection_timeout' => $input['connection_timeout']
    ];
    
    $result = PluginLapsConfig::testConnection($testConfig);
    
    if ($result['success']) {
        Session::addMessageAfterRedirect($result['message'], false, INFO);
    } else {
        Session::addMessageAfterRedirect($result['message'], false, ERROR);
    }
    
    Html::redirect($_SERVER['PHP_SELF']);
}

echo "<div class='center'>";
echo "<h2>" . __('LAPS Integration Configuration', 'laps') . "</h2>";

// Exibir informações do plugin
echo "<div class='spaced'>";
echo "<table class='tab_cadre_fixe'>";
echo "<tr class='headerRow'><th colspan='2'>" . __('Plugin Information', 'laps') . "</th></tr>";
echo "<tr class='tab_bg_1'>";
echo "<td><strong>" . __('Plugin Name', 'laps') . ":</strong></td>";
echo "<td>LAPS Integration</td>";
echo "</tr>";
echo "<tr class='tab_bg_1'>";
echo "<td><strong>" . __('Version', 'laps') . ":</strong></td>";
echo "<td>" . PLUGIN_LAPSGLPI_VERSION . "</td>";
echo "</tr>";
echo "<tr class='tab_bg_1'>";
echo "<td><strong>" . __('Author', 'laps') . ":</strong></td>";
echo "<td>Rafael Cavalheri</td>";
echo "</tr>";
echo "<tr class='tab_bg_1'>";
echo "<td><strong>" . __('Description', 'laps') . ":</strong></td>";
echo "<td>" . __('Integration between GLPI and LAPS (Local Administrator Password Solution) to display local administrator passwords for computers.', 'laps') . "</td>";
echo "</tr>";
echo "</table>";
echo "</div>";

// Exibir formulário de configuração
$config->showConfigForm();

// Exibir estatísticas
echo "<div class='spaced'>";
echo "<table class='tab_cadre_fixe'>";
echo "<tr class='headerRow'><th colspan='2'>" . __('Statistics', 'laps') . "</th></tr>";

// Contar computadores com senhas LAPS
global $DB;
$query = "SELECT COUNT(*) as total FROM glpi_plugin_laps_passwords";
$result = $DB->query($query);
$total = $DB->fetchAssoc($result)['total'];

echo "<tr class='tab_bg_1'>";
echo "<td><strong>" . __('Computers with LAPS passwords', 'laps') . ":</strong></td>";
echo "<td>" . $total . "</td>";
echo "</tr>";

// Contar sincronizações bem-sucedidas
$query = "SELECT COUNT(*) as success FROM glpi_plugin_laps_passwords WHERE sync_status = 'success'";
$result = $DB->query($query);
$success = $DB->fetchAssoc($result)['success'];

echo "<tr class='tab_bg_1'>";
echo "<td><strong>" . __('Successful synchronizations', 'laps') . ":</strong></td>";
echo "<td>" . $success . "</td>";
echo "</tr>";

// Contar erros
$query = "SELECT COUNT(*) as errors FROM glpi_plugin_laps_passwords WHERE sync_status = 'error'";
$result = $DB->query($query);
$errors = $DB->fetchAssoc($result)['errors'];

echo "<tr class='tab_bg_1'>";
echo "<td><strong>" . __('Synchronization errors', 'laps') . ":</strong></td>";
echo "<td><span style='color: red;'>" . $errors . "</span></td>";
echo "</tr>";

// Última sincronização
$query = "SELECT MAX(last_sync) as last_sync FROM glpi_plugin_laps_passwords";
$result = $DB->query($query);
$lastSync = $DB->fetchAssoc($result)['last_sync'];

echo "<tr class='tab_bg_1'>";
echo "<td><strong>" . __('Last synchronization', 'laps') . ":</strong></td>";
echo "<td>" . ($lastSync ? Html::convDateTime($lastSync) : __('Never', 'laps')) . "</td>";
echo "</tr>";

echo "</table>";
echo "</div>";

echo "</div>";

// Inicializar o plugin LAPS quando a página carregar
echo "<script type='text/javascript'>";
echo "document.addEventListener('DOMContentLoaded', function() {";
echo "    if (typeof LAPS !== 'undefined') {";
echo "        LAPS.init();";
echo "    }";
echo "});";
echo "</script>";

Html::footer();
?>