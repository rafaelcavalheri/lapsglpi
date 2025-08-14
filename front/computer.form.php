<?php
/**
 * Plugin LAPS-GLPI - Computer Form
 * Página para processar ações relacionadas aos computadores
 */

include ('../../../inc/includes.php');

Session::checkRight('computer', READ);

// Processar sincronização forçada
if (isset($_POST['sync_password']) && isset($_POST['computers_id'])) {
    
    if (!Session::haveRight('computer', UPDATE)) {
        Session::addMessageAfterRedirect(__('No permission to sync passwords', 'laps'), false, ERROR);
        Html::back();
    }
    
    $computer_id = intval($_POST['computers_id']);
    $computer_name = $_POST['computer_name'];
    
    if (empty($computer_name)) {
        Session::addMessageAfterRedirect(__('Computer name is required', 'laps'), false, ERROR);
        Html::back();
    }
    
    // Verificar se o plugin está ativo
    $config = new PluginLapsConfig();
    $settings = $config->getConfig();
    
    if (!$settings['is_active']) {
        Session::addMessageAfterRedirect(__('LAPS integration is not active', 'laps'), false, ERROR);
        Html::back();
    }
    
    // Forçar busca do servidor LAPS (ignorar cache)
    $lapsData = PluginLapsComputer::fetchFromLapsServer($computer_name, $settings);
    
    if ($lapsData['success']) {
        // Atualizar cache
        PluginLapsComputer::updateCache($computer_id, $computer_name, $lapsData['data']);
        
        Session::addMessageAfterRedirect(__('Password synchronized successfully', 'laps'), false, INFO);
        
        // Log da ação
        PluginLapsComputer::logAction($computer_id, $computer_name, 'manual_sync', 'Manual password synchronization by user ' . Session::getLoginUserID());
        
    } else {
        // Registrar erro no cache
        global $DB;
        
        $errorData = [
            'computers_id' => $computer_id,
            'computer_name' => $computer_name,
            'admin_password' => null,
            'password_expiry' => null,
            'last_sync' => date('Y-m-d H:i:s'),
            'sync_status' => 'error',
            'error_message' => $lapsData['message'],
            'date_mod' => date('Y-m-d H:i:s')
        ];
        
        // Verificar se já existe registro
        $query = "SELECT id FROM glpi_plugin_laps_passwords WHERE computers_id = '$computer_id'";
        $result = $DB->query($query);
        
        if ($DB->numrows($result) > 0) {
            // Atualizar
            $existing = $DB->fetchAssoc($result);
            $DB->updateOrDie('glpi_plugin_laps_passwords', $errorData, ['id' => $existing['id']]);
        } else {
            // Inserir
            $errorData['date_creation'] = date('Y-m-d H:i:s');
            $DB->insertOrDie('glpi_plugin_laps_passwords', $errorData);
        }
        
        Session::addMessageAfterRedirect(__('Error synchronizing password: ', 'laps') . $lapsData['message'], false, ERROR);
        
        // Log do erro
        PluginLapsComputer::logAction($computer_id, $computer_name, 'sync_error', 'Synchronization error: ' . $lapsData['message']);
    }
    
    Html::back();
}

// Processar limpeza de cache
if (isset($_POST['clear_cache']) && isset($_POST['computers_id'])) {
    
    if (!Session::haveRight('computer', UPDATE)) {
        Session::addMessageAfterRedirect(__('No permission to clear cache', 'laps'), false, ERROR);
        Html::back();
    }
    
    $computer_id = intval($_POST['computers_id']);
    $computer_name = $_POST['computer_name'];
    
    global $DB;
    
    // Remover do cache
    $query = "DELETE FROM glpi_plugin_laps_passwords WHERE computers_id = '$computer_id'";
    
    if ($DB->query($query)) {
        Session::addMessageAfterRedirect(__('Cache cleared successfully', 'laps'), false, INFO);
        
        // Log da ação
        PluginLapsComputer::logAction($computer_id, $computer_name, 'cache_clear', 'Cache cleared by user ' . Session::getLoginUserID());
    } else {
        Session::addMessageAfterRedirect(__('Error clearing cache', 'laps'), false, ERROR);
    }
    
    Html::back();
}

// Processar visualização de logs
if (isset($_GET['show_logs']) && isset($_GET['computers_id'])) {
    
    if (!Session::haveRight('computer', READ)) {
        Session::addMessageAfterRedirect(__('No permission to view logs', 'laps'), false, ERROR);
        Html::back();
    }
    
    $computer_id = intval($_GET['computers_id']);
    
    Html::header(__('LAPS Logs', 'laps'), $_SERVER['PHP_SELF'], 'assets', 'computer');
    
    echo "<div class='center'>";
    echo "<h2>" . __('LAPS Activity Logs', 'laps') . "</h2>";
    
    global $DB;
    
    $query = "SELECT l.*, u.name as user_name 
              FROM glpi_plugin_laps_logs l 
              LEFT JOIN glpi_users u ON l.user_id = u.id 
              WHERE l.computers_id = '$computer_id' 
              ORDER BY l.date_creation DESC 
              LIMIT 50";
    
    $result = $DB->query($query);
    
    if ($DB->numrows($result) > 0) {
        echo "<table class='tab_cadre_fixehov'>";
        echo "<tr class='headerRow'>";
        echo "<th>" . __('Date', 'laps') . "</th>";
        echo "<th>" . __('Action', 'laps') . "</th>";
        echo "<th>" . __('User', 'laps') . "</th>";
        echo "<th>" . __('Details', 'laps') . "</th>";
        echo "</tr>";
        
        while ($log = $DB->fetchAssoc($result)) {
            echo "<tr class='tab_bg_1'>";
            echo "<td>" . Html::convDateTime($log['date_creation']) . "</td>";
            echo "<td>";
            
            switch ($log['action']) {
                case 'password_sync':
                    echo "<span style='color: green;'>" . __('Password Sync', 'laps') . "</span>";
                    break;
                case 'manual_sync':
                    echo "<span style='color: blue;'>" . __('Manual Sync', 'laps') . "</span>";
                    break;
                case 'sync_error':
                    echo "<span style='color: red;'>" . __('Sync Error', 'laps') . "</span>";
                    break;
                case 'cache_clear':
                    echo "<span style='color: orange;'>" . __('Cache Clear', 'laps') . "</span>";
                    break;
                default:
                    echo Html::cleanInputText($log['action']);
            }
            
            echo "</td>";
            echo "<td>" . ($log['user_name'] ? Html::cleanInputText($log['user_name']) : __('System', 'laps')) . "</td>";
            echo "<td>" . Html::cleanInputText($log['details']) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p class='center'>" . __('No logs found for this computer', 'laps') . "</p>";
    }
    
    echo "<div class='center' style='margin-top: 20px;'>";
    echo "<a href='javascript:history.back()' class='btn btn-secondary'>" . __('Back', 'laps') . "</a>";
    echo "</div>";
    
    echo "</div>";
    
    Html::footer();
    exit;
}

// Processar busca de senha via AJAX
if (isset($_POST['get_password']) && isset($_POST['computers_id'])) {
    
    if (!Session::haveRight('computer', READ)) {
        echo json_encode(['success' => false, 'message' => __('No permission to view password', 'laps')]);
        exit;
    }
    
    $computer_id = intval($_POST['computers_id']);
    $computer_name = $_POST['computer_name'];
    
    if (empty($computer_name)) {
        echo json_encode(['success' => false, 'message' => __('Computer name is required', 'laps')]);
        exit;
    }
    
    // Verificar se o plugin está ativo
    $config = new PluginLapsConfig();
    $settings = $config->getConfig();
    
    if (!$settings['is_active']) {
        echo json_encode(['success' => false, 'message' => __('LAPS integration is not active', 'laps')]);
        exit;
    }
    
    // Buscar senha do servidor LAPS
    $lapsData = PluginLapsComputer::fetchFromLapsServer($computer_name, $settings);
    
    if ($lapsData['success']) {
        // Atualizar cache
        PluginLapsComputer::updateCache($computer_id, $computer_name, $lapsData['data']);
        
        // Log da ação
        PluginLapsComputer::logAction($computer_id, $computer_name, 'password_view', 'Password viewed by user ' . Session::getLoginUserID());
        
        echo json_encode([
            'success' => true,
            'data' => [
                'password' => $lapsData['data']['admin_password'],
                'expiry' => $lapsData['data']['password_expiry'],
                'status' => $lapsData['data']['sync_status'] ?? 'success'
            ]
        ]);
        
    } else {
        echo json_encode(['success' => false, 'message' => $lapsData['message']]);
    }
    
    exit;
}

// Se chegou até aqui sem processar nada, redirecionar
Html::back();
?>