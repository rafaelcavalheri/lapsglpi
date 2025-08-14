<?php
/**
 * Plugin LAPS-GLPI - Computer Class
 * Classe para gerenciar senhas LAPS dos computadores
 */

class PluginLapsComputer extends CommonGLPI {
    
    static $rightname = 'computer';
    
    static function getTypeName($nb = 0) {
        return __('LAPS Password', 'laps');
    }
    
    static function canCreate() {
        return Session::haveRight(self::$rightname, UPDATE);
    }
    
    static function canView() {
        return Session::haveRight(self::$rightname, READ);
    }
    
    static function canUpdate() {
        return Session::haveRight(self::$rightname, UPDATE);
    }
    
    static function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        if ($item->getType() == 'Computer') {
            if (self::canView()) {
                return self::createTabEntry(__('LAPS Password', 'laps'));
            }
        }
        return '';
    }
    
    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
        if ($item->getType() == 'Computer') {
            self::showForComputer($item);
        }
        return true;
    }
    

    
    /**
     * Exibir informações LAPS para um computador
     */
    static function showForComputer(Computer $computer) {
        global $CFG_GLPI;
        
        if (!self::canView()) {
            return false;
        }
        
        $computer_id = $computer->getID();
        $computer_name = $computer->fields['name'];
        
        // Verificar se o plugin está ativo
        $config = new PluginLapsConfig();
        $settings = $config->getConfig();
        
        if (!$settings['is_active']) {
            echo "<div class='center'>";
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='headerRow'><th>" . __('LAPS Integration', 'laps') . "</th></tr>";
            echo "<tr class='tab_bg_1'><td class='center'>" . __('LAPS integration is not active', 'laps') . "</td></tr>";
            echo "</table>";
            echo "</div>";
            return false;
        }
        
        echo "<div class='center'>";
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr class='headerRow'><th colspan='2'>" . __('LAPS Password Information', 'laps') . "</th></tr>";
        
        echo "<tr class='tab_bg_1'>";
        echo "<td><strong>" . __('Computer Name', 'laps') . ":</strong></td>";
        echo "<td>" . Html::cleanInputText($computer_name) . "</td>";
        echo "</tr>";
        
        echo "<tr class='tab_bg_1'>";
        echo "<td><strong>" . __('Administrator Password', 'laps') . ":</strong></td>";
        echo "<td>";
        if (Session::haveRight('computer', READ)) {
            echo "<div id='laps-password-container'>";
            echo "<button type='button' id='laps-show-password-btn' onclick='getLapsPassword(" . $computer_id . ", \"" . addslashes($computer_name) . "\")' class='btn btn-primary'>" . __('View Password', 'laps') . "</button>";
            echo "<div id='laps-loading' style='display: none; margin-top: 10px;'>";
            echo "<img src='" . $CFG_GLPI['root_doc'] . "/pics/loader.gif' alt='Loading...'> " . __('Loading...', 'laps');
            echo "</div>";
            echo "<div id='laps-password-result' style='display: none; margin-top: 10px;'></div>";
            echo "</div>";
        } else {
            echo "<em>" . __('No permission to view password', 'laps') . "</em>";
        }
        echo "</td>";
         echo "</tr>";
        
        // Botão para forçar sincronização
        if (Session::haveRight('computer', UPDATE)) {
            echo "<tr class='tab_bg_2'>";
            echo "<td colspan='2' class='center'>";
            echo "<form method='post' action='" . $CFG_GLPI['root_doc'] . "/plugins/lapsglpi/front/computer.form.php'>";
            echo "<input type='hidden' name='computers_id' value='" . $computer_id . "' />";
            echo "<input type='hidden' name='computer_name' value='" . Html::cleanInputText($computer_name) . "' />";
            echo "<input type='submit' name='sync_password' value='" . __('Force Sync', 'laps') . "' class='submit' />";
            echo "</form>";
            echo "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        echo "</div>";
        
        // Incluir arquivos CSS e JS do plugin
         echo "<link rel='stylesheet' type='text/css' href='" . $CFG_GLPI['root_doc'] . "/plugins/lapsglpi/css/laps.css'>";
         echo "<script type='text/javascript' src='" . $CFG_GLPI['root_doc'] . "/plugins/lapsglpi/js/laps.js'></script>";
          
          return true;
    }
    
    /**
     * Obter senha LAPS do computador
     */
    static function getLapsPassword($computer_id, $computer_name) {
        global $DB;
        
        // Verificar cache primeiro
        $query = "SELECT * FROM glpi_plugin_laps_passwords WHERE computers_id = '$computer_id'";
        $result = $DB->query($query);
        
        $config = new PluginLapsConfig();
        $settings = $config->getConfig();
        
        if ($DB->numrows($result) > 0) {
            $cached = $DB->fetchAssoc($result);
            
            // Verificar se o cache ainda é válido
            $cacheTime = strtotime($cached['last_sync']);
            $now = time();
            
            if (($now - $cacheTime) < $settings['cache_duration']) {
                return [
                    'success' => true,
                    'data' => $cached,
                    'from_cache' => true
                ];
            }
        }
        
        // Buscar do servidor LAPS
        $lapsData = self::fetchFromLapsServer($computer_name, $settings);
        
        if ($lapsData['success']) {
            // Atualizar cache
            self::updateCache($computer_id, $computer_name, $lapsData['data']);
            
            return [
                'success' => true,
                'data' => $lapsData['data'],
                'from_cache' => false
            ];
        }
        
        return $lapsData;
    }
    
    /**
     * Buscar dados do servidor LAPS
     */
    static function fetchFromLapsServer($computer_name, $settings) {
        if (empty($settings['laps_server_url']) || empty($settings['laps_api_key'])) {
            return ['success' => false, 'message' => __('LAPS server not configured', 'laps')];
        }
        
        $url = rtrim($settings['laps_server_url'], '/') . '/api.php';
        
        // Dados para a requisição
        $postData = [
            'action' => 'get_password',
            'computer' => $computer_name,
            'api_key' => $settings['laps_api_key']
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $settings['connection_timeout']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'User-Agent: GLPI-LAPS-Plugin/1.0',
            'X-API-Key: ' . $settings['laps_api_key']
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return ['success' => false, 'message' => __('Connection error: ', 'laps') . $error];
        }
        
        if ($httpCode !== 200) {
            return ['success' => false, 'message' => __('HTTP Error: ', 'laps') . $httpCode];
        }
        
        $data = json_decode($response, true);
        
        if (!$data) {
            return ['success' => false, 'message' => __('Invalid response from LAPS server', 'laps')];
        }
        
        // Verificar se há erro na resposta da API
        if (isset($data['error'])) {
            return ['success' => false, 'message' => $data['error']];
        }
        
        // Adaptar resposta da API para o formato esperado pelo plugin
        if (isset($data['password'])) {
            return [
                'success' => true,
                'data' => [
                    'computer_name' => $computer_name,
                    'admin_password' => $data['password'],
                    'password_expiry' => $data['expiration_timestamp'] ?? null,
                    'last_sync' => date('Y-m-d H:i:s'),
                    'sync_status' => 'success',
                    'error_message' => null
                ]
            ];
        }
        
        return ['success' => false, 'message' => __('No password found', 'laps')];
    }
    
    /**
     * Atualizar cache de senha
     */
    static function updateCache($computer_id, $computer_name, $data) {
        global $DB;
        
        $data['computers_id'] = $computer_id;
        $data['date_mod'] = date('Y-m-d H:i:s');
        
        // Verificar se já existe registro
        $query = "SELECT id FROM glpi_plugin_laps_passwords WHERE computers_id = '$computer_id'";
        $result = $DB->query($query);
        
        if ($DB->numrows($result) > 0) {
            // Atualizar
            $existing = $DB->fetchAssoc($result);
            $DB->updateOrDie('glpi_plugin_laps_passwords', $data, ['id' => $existing['id']]);
        } else {
            // Inserir
            $data['date_creation'] = date('Y-m-d H:i:s');
            $DB->insertOrDie('glpi_plugin_laps_passwords', $data);
        }
        
        // Log da ação
        self::logAction($computer_id, $computer_name, 'password_sync', 'Password synchronized successfully');
    }
    
    /**
     * Registrar ação no log
     */
    static function logAction($computer_id, $computer_name, $action, $details = '') {
        global $DB;
        
        $data = [
            'computers_id' => $computer_id,
            'computer_name' => $computer_name,
            'action' => $action,
            'user_id' => Session::getLoginUserID(),
            'details' => $details,
            'date_creation' => date('Y-m-d H:i:s')
        ];
        
        $DB->insertOrDie('glpi_plugin_laps_logs', $data);
    }
}
?>