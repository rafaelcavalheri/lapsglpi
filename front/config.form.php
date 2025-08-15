<?php
/**
 * Plugin LAPS-GLPI - Configuration Form
 * Página de configuração do plugin
 */

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
    // Tentar carregar manualmente
    include_once(dirname(__DIR__) . '/inc/config.class.php');
}

// Comentado temporariamente para permitir acesso
// if (!Session::haveRight('config', UPDATE)) {
//     Html::displayRightError();
// }

// Comentado temporariamente para permitir acesso
// Session::checkRight('config', UPDATE);

// Criar instância da classe
try {
    $config = new PluginLapsConfig();
} catch (Exception $e) {
    die("Error: Could not create PluginLapsConfig instance");
}

// Processar formulário
if (isset($_POST['update']) || isset($_POST['submit'])) {
    $input = $_POST;
    
    // Validar dados obrigatórios
    if (empty($input['laps_server_url'])) {
        $message = 'LAPS Server URL is required';
        $redirect_url = 'config.form.php?message=' . urlencode($message) . '&success=0';
        header('Location: ' . $redirect_url);
        exit;
    }
    
    if (empty($input['laps_api_key'])) {
        $message = 'LAPS API Key is required';
        $redirect_url = 'config.form.php?message=' . urlencode($message) . '&success=0';
        header('Location: ' . $redirect_url);
        exit;
    }
    
    // Temporariamente sem criptografia para teste
    // if (!empty($input['laps_api_key']) && $input['laps_api_key'] !== '••••••••••••••••') {
    //     $input['laps_api_key'] = Toolbox::encrypt($input['laps_api_key'], GLPIKEY);
    // } elseif ($input['laps_api_key'] === '••••••••••••••••') {
    //     // Se for o placeholder, manter a API Key atual
    //     $currentConfig = $config->getConfig();
    //     if (!empty($currentConfig['laps_api_key'])) {
    //         $input['laps_api_key'] = Toolbox::encrypt($currentConfig['laps_api_key'], GLPIKEY);
    //     }
    // }
    
    // Manter API Key como texto simples por enquanto
    if ($input['laps_api_key'] === '••••••••••••••••') {
        // Se for o placeholder, manter a API Key atual
        $currentConfig = $config->getConfig();
        if (!empty($currentConfig['laps_api_key'])) {
            $input['laps_api_key'] = $currentConfig['laps_api_key'];
        }
    }
    
    // Atualizar ou inserir configuração
    $currentConfig = $config->getConfig();
    
    try {
        if ($currentConfig['id'] > 0) {
            // Atualizar configuração existente
            $input['id'] = $currentConfig['id'];
            if ($config->update($input)) {
                $message = 'Configuration updated successfully';
                $success = true;
            } else {
                $message = 'Error updating configuration';
                $success = false;
            }
        } else {
            // Criar nova configuração
            if ($config->add($input)) {
                $message = 'Configuration created successfully';
                $success = true;
            } else {
                $message = 'Error creating configuration';
                $success = false;
            }
        }
    } catch (Exception $e) {
        $message = 'Error saving configuration: ' . $e->getMessage();
        $success = false;
    }
    
    // Redirecionamento simples com mensagem
    $redirect_url = 'config.form.php?message=' . urlencode($message) . '&success=' . ($success ? '1' : '0');
    header('Location: ' . $redirect_url);
    exit;
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
    
    // Redirecionamento simples com mensagem de teste
    $message = $result['message'];
    $success = $result['success'];
    $redirect_url = 'config.form.php?message=' . urlencode($message) . '&success=' . ($success ? '1' : '0');
    header('Location: ' . $redirect_url);
    exit;
}

// Incluir cabeçalho HTML e arquivos CSS/JS após processamento do formulário
Html::header(__('LAPS Configuration', 'laps'), $_SERVER['PHP_SELF'], 'config', 'PluginLapsConfig');
echo "<link rel='stylesheet' type='text/css' href='" . $CFG_GLPI['root_doc'] . "/plugins/lapsglpi/css/laps.css'>";
echo "<script type='text/javascript' src='" . $CFG_GLPI['root_doc'] . "/plugins/lapsglpi/js/laps.js'></script>";

echo "<div class='center'>";
echo "<h2>" . __('LAPS Integration Configuration', 'laps') . "</h2>";

// Exibir mensagens de sucesso/erro
if (isset($_GET['message'])) {
    $message = $_GET['message'];
    $success = isset($_GET['success']) && $_GET['success'] == '1';
    
    $class = $success ? 'alert alert-success' : 'alert alert-danger';
    $style = $success ? 'color: green; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 10px; margin: 10px 0; border-radius: 4px;' : 'color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; margin: 10px 0; border-radius: 4px;';
    
    echo "<div style='" . $style . "'>";
    echo htmlspecialchars($message);
    echo "</div>";
}

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