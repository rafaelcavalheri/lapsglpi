<?php
/**
 * LAPS-GLPI Plugin Installation Class
 * 
 * @author Rafael Cavalheri
 * @license GPL-2.0-or-later
 */

class PluginLapsglpiInstall
{
    /**
     * Check if plugin is already installed
     */
    public function isInstalled(): bool
    {
        global $DB;
        
        return $DB->tableExists('glpi_plugin_laps_configs');
    }
    
    /**
     * Install the plugin
     */
    public function install(Migration $migration): bool
    {
        global $DB;
        
        // Create configuration table
        if (!$DB->tableExists('glpi_plugin_laps_configs')) {
            $query = "CREATE TABLE `glpi_plugin_laps_configs` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `laps_server_url` varchar(255) DEFAULT NULL,
                `laps_api_key` text DEFAULT NULL,
                `connection_timeout` int(11) DEFAULT 30,
                `cache_duration` int(11) DEFAULT 300,
                `is_active` tinyint(1) DEFAULT 1,
                `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
                `date_mod` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $DB->queryOrDie($query, "Error creating glpi_plugin_laps_configs table");
        }
        
        // Create password cache table
        if (!$DB->tableExists('glpi_plugin_laps_passwords')) {
            $query = "CREATE TABLE `glpi_plugin_laps_passwords` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `computers_id` int(11) NOT NULL,
                `password_hash` varchar(255) DEFAULT NULL,
                `expiration_date` datetime DEFAULT NULL,
                `last_updated` datetime DEFAULT NULL,
                `cache_timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `computers_id` (`computers_id`),
                KEY `expiration_date` (`expiration_date`),
                CONSTRAINT `fk_laps_passwords_computers` FOREIGN KEY (`computers_id`) REFERENCES `glpi_computers` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $DB->queryOrDie($query, "Error creating glpi_plugin_laps_passwords table");
        }
        
        // Create audit log table
        if (!$DB->tableExists('glpi_plugin_laps_audit')) {
            $query = "CREATE TABLE `glpi_plugin_laps_audit` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `event_type` varchar(50) NOT NULL,
                `users_id` int(11) NOT NULL DEFAULT 0,
                `user_name` varchar(255) DEFAULT NULL,
                `user_ip` varchar(45) DEFAULT NULL,
                `user_agent` text DEFAULT NULL,
                `computers_id` int(11) DEFAULT 0,
                `success` tinyint(1) DEFAULT 1,
                `event_data` text DEFAULT NULL,
                `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `event_type` (`event_type`),
                KEY `users_id` (`users_id`),
                KEY `computers_id` (`computers_id`),
                KEY `date_creation` (`date_creation`),
                KEY `success` (`success`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $DB->queryOrDie($query, "Error creating glpi_plugin_laps_audit table");
        }
        
        // Insert default configuration
        $this->insertDefaultConfig();
        
        // Create necessary directories
        $this->createDirectories();
        
        return true;
    }
    
    /**
     * Update the plugin
     */
    public function update(Migration $migration, string $current_version): bool
    {
        global $DB;
        
        // Version-specific updates
        if (version_compare($current_version, '2.0.0', '<')) {
            // Update from version < 2.0.0
            $this->updateTo200($migration);
        }
        
        return true;
    }
    
    /**
     * Uninstall the plugin
     */
    public function uninstall(): bool
    {
        global $DB;
        
        // Drop tables
        $tables = [
            'glpi_plugin_laps_configs',
            'glpi_plugin_laps_passwords',
            'glpi_plugin_laps_audit'
        ];
        
        foreach ($tables as $table) {
            if ($DB->tableExists($table)) {
                $DB->queryOrDie("DROP TABLE `$table`", "Error dropping table $table");
            }
        }
        
        // Clean up any remaining data
        $this->cleanupData();
        
        return true;
    }
    
    /**
     * Insert default configuration
     */
    private function insertDefaultConfig(): void
    {
        global $DB;
        
        $config = [
            'laps_server_url' => 'https://laps.mogimirim.sp.gov.br/api.php',
            'laps_api_key' => Toolbox::encrypt('5deeb8a3-e591-4bd4-8bfb-f9d8b117844c', GLPIKEY),
            'connection_timeout' => 30,
            'cache_duration' => 300,
            'is_active' => 1
        ];
        
        $DB->insertOrDie('glpi_plugin_laps_configs', $config, "Error inserting default configuration");
    }
    
    /**
     * Create necessary directories
     */
    private function createDirectories(): void
    {
        $directories = [
            GLPI_PLUGIN_DOC_DIR . '/lapsglpi',
            GLPI_PLUGIN_DOC_DIR . '/lapsglpi/logs'
        ];
        
        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }
    
    /**
     * Update to version 2.0.0
     */
    private function updateTo200(Migration $migration): void
    {
        global $DB;
        
        // Add new columns if they don't exist
        if (!$DB->fieldExists('glpi_plugin_laps_configs', 'date_creation')) {
            $migration->addField('glpi_plugin_laps_configs', 'date_creation', 'timestamp', [
                'null' => true,
                'default' => 'CURRENT_TIMESTAMP'
            ]);
        }
        
        if (!$DB->fieldExists('glpi_plugin_laps_configs', 'date_mod')) {
            $migration->addField('glpi_plugin_laps_configs', 'date_mod', 'timestamp', [
                'null' => true,
                'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
            ]);
        }
        
        // Encrypt existing API keys
        $this->encryptExistingApiKeys();
        
        $migration->executeMigration();
    }
    
    /**
     * Encrypt existing API keys
     */
    private function encryptExistingApiKeys(): void
    {
        global $DB;
        
        $iterator = $DB->request([
            'FROM' => 'glpi_plugin_laps_configs',
            'WHERE' => [
                'laps_api_key' => ['!=', '']
            ]
        ]);
        
        foreach ($iterator as $config) {
            // Check if already encrypted
            try {
                $decrypted = Toolbox::decrypt($config['laps_api_key'], GLPIKEY);
                if ($decrypted === false) {
                    // Not encrypted, encrypt it
                    $encrypted = Toolbox::encrypt($config['laps_api_key'], GLPIKEY);
                    $DB->update('glpi_plugin_laps_configs', [
                        'laps_api_key' => $encrypted
                    ], [
                        'id' => $config['id']
                    ]);
                }
            } catch (Exception $e) {
                // Error decrypting, assume it's not encrypted
                $encrypted = Toolbox::encrypt($config['laps_api_key'], GLPIKEY);
                $DB->update('glpi_plugin_laps_configs', [
                    'laps_api_key' => $encrypted
                ], [
                    'id' => $config['id']
                ]);
            }
        }
    }
    
    /**
     * Clean up remaining data
     */
    private function cleanupData(): void
    {
        // Clean up any cached files
        $cache_dir = GLPI_PLUGIN_DOC_DIR . '/lapsglpi';
        if (is_dir($cache_dir)) {
            $this->removeDirectory($cache_dir);
        }
    }
    
    /**
     * Remove directory recursively
     */
    private function removeDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        
        return rmdir($dir);
    }
    
    /**
     * Get current plugin version from database
     */
    public function getCurrentVersion(): string
    {
        global $DB;
        
        $iterator = $DB->request([
            'FROM' => 'glpi_plugins',
            'WHERE' => ['directory' => 'lapsglpi']
        ]);
        
        if (count($iterator) > 0) {
            return $iterator->current()['version'];
        }
        
        return '0.0.0';
    }
}