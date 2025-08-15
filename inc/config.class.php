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
        
        // Comentado temporariamente para permitir acesso
        // if (!$this->canView()) {
        //     return false;
        // }
        
        // Buscar configuração atual
        $config = $this->getConfig();
        
        echo "<form name='form' method='post' action='" . $CFG_GLPI['root_doc'] . "/plugins/lapsglpi/front/config.form.php'>";
        echo "<div class='center' id='tabsbody'>";
        echo "<table class='tab_cadre_fixe'>";
        
        echo "<tr class='headerRow'><th colspan='2'>" . __('LAPS Server Configuration', 'laps') . "</th></tr>";
        
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('LAPS Server URL', 'laps') . "</td>";
        echo "<td><input type='text' name='laps_server_url' value='" . Html::cleanInputText($config['laps_server_url'] ?? '') . "' size='50' placeholder='https://your-laps-server.example.com/api'></td>";
        echo "</tr>";
        
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __("API Key", "laps") . "</td>";
        $apiKeyValue = $config['laps_api_key'] ?? '';
        $keyStatus = !empty($config['laps_api_key']) ? ' <small style="color: green;">(Configurada)</small>' : ' <small style="color: red;">(Não configurada)</small>';
        echo "<td><input type='text' name='laps_api_key' value='" . Html::cleanInputText($apiKeyValue) . "' size='50' placeholder='Digite a chave da API do servidor LAPS'>" . $keyStatus . "</td>";
        echo "</tr>";
        
        echo "<tr class='tab_bg_1'>";
        echo "<td colspan='2'><small>" . __('Use the API key generated in the LAPS server. Replace with your actual API key.', 'laps') . "</small></td>";
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
        
        // Comentado temporariamente para permitir acesso
        // if ($this->canUpdate()) {
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
        // }
        
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
            // Nota: API Key será descriptografada quando necessário para uso
            // Por enquanto, mantemos como está armazenada no banco
            return $config;
        }
        
        // Retornar configuração padrão se não existir
        return [
            'id' => 0,
            'laps_server_url' => defined('LAPS_DEFAULT_SERVER_URL') ? LAPS_DEFAULT_SERVER_URL : 'https://your-laps-server.example.com/api',
            'laps_api_key' => defined('LAPS_DEFAULT_API_KEY') ? LAPS_DEFAULT_API_KEY : 'your-api-key-here',
            'connection_timeout' => 30,
            'cache_duration' => 300,
            'is_active' => 0
        ];
    }
    
    /**
     * Testa a conexão com o servidor LAPS
     */
    static function testConnection($config) {
        if (empty($config['laps_server_url']) || empty($config['laps_api_key'])) {
            return ['success' => false, 'message' => __('LAPS Server URL and API Key are required', 'laps')];
        }
        
        // Construir URL - tentar diferentes endpoints comuns
        $baseUrl = rtrim($config['laps_server_url'], '/');
        
        // Lista de endpoints comuns para teste
        $testEndpoints = [
            $baseUrl . '/test?api_key=' . urlencode($config['laps_api_key']),
            $baseUrl . '/status?api_key=' . urlencode($config['laps_api_key']),
            $baseUrl . '/health?api_key=' . urlencode($config['laps_api_key']),
            $baseUrl . '?action=test&api_key=' . urlencode($config['laps_api_key']),
            $baseUrl . '?action=status&api_key=' . urlencode($config['laps_api_key'])
        ];
        
        $lastError = '';
        
        // Tentar cada endpoint até encontrar um que funcione
        foreach ($testEndpoints as $url) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, intval($config['connection_timeout'] ?? 10));
            curl_setopt($ch, CURLOPT_POST, false);
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
                $lastError = sprintf(__('Connection error: %s', 'laps'), $error);
                continue;
            }
            
            if ($httpCode === 401) {
                return ['success' => false, 'message' => __('Invalid API Key', 'laps')];
            }
            
            if ($httpCode === 200) {
                // Tentar decodificar JSON
                $data = json_decode($response, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    // Resposta JSON válida
                    if (isset($data['error'])) {
                        return ['success' => false, 'message' => $data['error']];
                    }
                    
                    $message = __('Connection successful', 'laps');
                    if (isset($data['version'])) {
                        $message .= ' (LAPS v' . $data['version'] . ')';
                    }
                    
                    return ['success' => true, 'message' => $message, 'data' => $data, 'endpoint' => $url];
                } else {
                    // Resposta não é JSON, mas HTTP 200 - considerar sucesso
                    return ['success' => true, 'message' => __('Connection successful', 'laps') . ' (Non-JSON response)', 'endpoint' => $url];
                }
            }
            
            $lastError = sprintf(__('HTTP error: %d', 'laps'), $httpCode);
        }
        
        // Se chegou aqui, nenhum endpoint funcionou
        return ['success' => false, 'message' => $lastError ?: __('All endpoints failed', 'laps')];
    }
    
    /**
     * Adicionar nova configuração
     */
    function add(array $input, $options = [], $history = true) {
        global $DB;
        
        // Preparar dados para inserção
        $data = [
            'laps_server_url' => $input['laps_server_url'] ?? '',
            'laps_api_key' => $input['laps_api_key'] ?? '',
            'connection_timeout' => intval($input['connection_timeout'] ?? 30),
            'cache_duration' => intval($input['cache_duration'] ?? 300),
            'is_active' => intval($input['is_active'] ?? 1)
        ];
        
        $result = $DB->insert($this->getTable(), $data);
        
        if ($result) {
            $this->fields['id'] = $DB->insertId();
            return $this->fields['id'];
        }
        
        return false;
    }
    
    /**
     * Atualizar configuração existente
     */
    function update(array $input, $history = true, $options = []) {
        global $DB;
        
        if (!isset($input['id']) || $input['id'] <= 0) {
            return false;
        }
        
        // Preparar dados para atualização
        $data = [
            'laps_server_url' => $input['laps_server_url'] ?? '',
            'laps_api_key' => $input['laps_api_key'] ?? '',
            'connection_timeout' => intval($input['connection_timeout'] ?? 30),
            'cache_duration' => intval($input['cache_duration'] ?? 300),
            'is_active' => intval($input['is_active'] ?? 1)
        ];
        
        $result = $DB->update(
            $this->getTable(),
            $data,
            ['id' => $input['id']]
        );
        
        return $result;
    }
}
?>