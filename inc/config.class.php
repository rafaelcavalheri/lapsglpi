<?php
/**
 * Plugin LAPS-GLPI - Config Class
 * Classe para gerenciar configurações do plugin
 */

class PluginLapsConfig extends CommonDBTM {
    
    static $rightname = 'config';
    
    static function getTable($classname = null) {
        return 'glpi_plugin_laps_configs';
    }
    
    static function getTypeName($nb = 0) {
        return __('LAPS Configuration', 'laps');
    }
    
    static function canCreate() {
        return Session::haveRight(self::$rightname, UPDATE);
    }
    
    static function canView() {
        return Session::haveRight('config', READ);
    }
    
    static function canUpdate() {
        return Session::haveRight('config', UPDATE);
    }
    
    function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        if ($item->getType() == 'Config') {
            if (self::canView()) {
                return self::createTabEntry(self::getTypeName());
            }
        }
        return '';
    }
    
    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
        if ($item->getType() == 'Config') {
            $config = new self();
            $config->showConfigForm();
        }
        return true;
    }
    
    /**
     * Exibir formulário de configuração
     */
    function showConfigForm() {
        global $CFG_GLPI;
        
        if (!$this->canView()) {
            return false;
        }
        
        // Buscar configuração atual
        $config = $this->getConfig();
        
        echo "<form name='form' method='post' action='" . $CFG_GLPI['root_doc'] . "/plugins/lapsglpi/front/config.form.php'>";
        echo "<div class='center' id='tabsbody'>";
        echo "<table class='tab_cadre_fixe'>";
        
        echo "<tr class='headerRow'><th colspan='2'>" . __('LAPS Server Configuration', 'laps') . "</th></tr>";
        
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('LAPS Server URL', 'laps') . "</td>";
        echo "<td><input type='text' name='laps_server_url' value='" . Html::cleanInputText($config['laps_server_url'] ?? '') . "' size='50' placeholder='https://laps.mogimirim.sp.gov.br'></td>";
        echo "</tr>";
        
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('API Key', 'laps') . "</td>";
        $apiKeyValue = !empty($config['laps_api_key']) ? '••••••••••••••••' : '';
        echo "<td><input type='password' name='laps_api_key' value='" . $apiKeyValue . "' size='50' placeholder='Digite a chave da API do servidor LAPS'></td>";
        echo "</tr>";
        
        echo "<tr class='tab_bg_1'>";
        echo "<td colspan='2'><small>" . __('Use the API key generated in the LAPS server. Default key: 5deeb8a3-e591-4bd4-8bfb-f9d8b117844c', 'laps') . "</small></td>";
        echo "</tr>";
        
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Connection Timeout (seconds)', 'laps') . "</td>";
        echo "<td><input type='number' name='connection_timeout' value='" . $config['connection_timeout'] . "' min='5' max='300' /></td>";
        echo "</tr>";
        
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Cache Duration (seconds)', 'laps') . "</td>";
        echo "<td><input type='number' name='cache_duration' value='" . $config['cache_duration'] . "' min='60' max='3600' /></td>";
        echo "</tr>";
        
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Active', 'laps') . "</td>";
        echo "<td>";
        Dropdown::showYesNo('is_active', $config['is_active']);
        echo "</td>";
        echo "</tr>";
        
        if ($this->canUpdate()) {
            echo "<tr class='tab_bg_2'>";
            echo "<td class='center' colspan='2'>";
            echo "<input type='hidden' name='id' value='" . $config['id'] . "' />";
            echo "<input type='submit' name='update' value='" . _sx('button', 'Save') . "' class='submit' />";
            echo "&nbsp;&nbsp;";
            echo "<input type='button' id='test-connection-btn' value='" . __('Test Connection', 'laps') . "' class='submit' onclick='testLapsConnectionAjax()' />";
            echo "<span id='test-connection-result' style='margin-left: 10px;'></span>";
            echo "<div id='test-connection-loading' style='display: none; margin-top: 5px;'>";
            echo "<img src='" . $CFG_GLPI['root_doc'] . "/pics/loader.gif' alt='Loading...'> " . __('Testing connection...', 'laps');
            echo "</div>";
            echo "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        echo "</div>";
        Html::closeForm();
        
        return true;
    }
    
    /**
     * Obter configuração atual
     */
    function getConfig() {
        global $DB;
        
        $iterator = $DB->request([
            'FROM' => $this->getTable(),
            'ORDER' => 'id DESC',
            'LIMIT' => 1
        ]);
        
        if (count($iterator) > 0) {
            $config = $iterator->current();
            // Descriptografar API Key se existir e estiver criptografada
            if (!empty($config['laps_api_key'])) {
                // Verificar se a chave está criptografada (tenta descriptografar)
                try {
                    $decrypted = Toolbox::decrypt($config['laps_api_key'], GLPIKEY);
                    if ($decrypted !== false) {
                        $config['laps_api_key'] = $decrypted;
                    }
                } catch (Exception $e) {
                    // Se falhar na descriptografia, mantém a chave como está
                    // (pode ser que não esteja criptografada)
                }
            }
            return $config;
        }
        
        // Retornar configuração padrão se não existir
        return [
            'id' => 0,
            'laps_server_url' => 'https://laps.mogimirim.sp.gov.br/api.php',
            'laps_api_key' => '5deeb8a3-e591-4bd4-8bfb-f9d8b117844c',
            'connection_timeout' => 30,
            'cache_duration' => 300,
            'is_active' => 1
        ];
    }
    
    /**
     * Testa a conexão com o servidor LAPS
     */
    static function testConnection($config) {
        if (empty($config['laps_server_url']) || empty($config['laps_api_key'])) {
            return ['success' => false, 'message' => __('LAPS Server URL and API Key are required', 'laps')];
        }
        
        $url = rtrim($config['laps_server_url'], '/') . '/api.php';
        
        // Dados para testar a API
        $postData = [
            'action' => 'status',
            'api_key' => $config['laps_api_key']
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, intval($config['connection_timeout'] ?? 10));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'User-Agent: GLPI-LAPS-Plugin/1.0',
            'X-API-Key: ' . $config['laps_api_key']
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return ['success' => false, 'message' => sprintf(__('Connection error: %s', 'laps'), $error)];
        }
        
        if ($httpCode === 401) {
            return ['success' => false, 'message' => __('Invalid API Key', 'laps')];
        }
        
        if ($httpCode !== 200) {
            return ['success' => false, 'message' => sprintf(__('HTTP error: %d', 'laps'), $httpCode)];
        }
        
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['success' => false, 'message' => __('Invalid JSON response', 'laps')];
        }
        
        if (isset($data['error'])) {
            return ['success' => false, 'message' => $data['error']];
        }
        
        $message = __('Connection successful', 'laps');
        if (isset($data['version'])) {
            $message .= ' (LAPS v' . $data['version'] . ')';
        }
        
        return ['success' => true, 'message' => $message, 'data' => $data];
    }
}
?>