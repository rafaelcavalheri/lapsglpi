<?php

/**
 * -------------------------------------------------------------------------
 * LAPS plugin for GLPI
 *
 * @copyright Copyright (C) 2024-2025 Rafael Cavalheri and contributors.
 * @license   https://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0-or-later
 * @link      https://github.com/pluginsGLPI/laps
 *
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of LAPS plugin for GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Laps;

use Migration;
use DB;
use Session;

class Install
{
    private Migration $migration;

    public function __construct(Migration $migration)
    {
        $this->migration = $migration;
    }

    /**
     * Detect current plugin version
     *
     * @return string
     */
    public static function detectVersion(): string
    {
        global $DB;

        if (!$DB->tableExists('glpi_plugins')) {
            return '0.0.0';
        }

        $query = "SELECT version FROM glpi_plugins WHERE directory = 'lapsglpi'";
        $result = $DB->query($query);

        if ($result && $DB->numrows($result) > 0) {
            $data = $DB->fetchAssoc($result);
            return $data['version'];
        }

        return '0.0.0';
    }

    /**
     * Install plugin
     *
     * @param array $args
     * @return boolean
     */
    public function install(array $args = []): bool
    {
        $this->migration->displayTitle(__('Installing LAPS plugin'));

        // Create configuration table
        if (!$this->createConfigTable()) {
            return false;
        }

        // Create passwords cache table
        if (!$this->createPasswordsTable()) {
            return false;
        }

        // Create logs table
        if (!$this->createLogsTable()) {
            return false;
        }

        // Insert default configuration
        $this->insertDefaultConfig();

        $this->migration->executeMigration();

        return true;
    }

    /**
     * Upgrade plugin
     *
     * @param string $current_version
     * @param array $args
     * @return boolean
     */
    public function upgrade(string $current_version, array $args = []): bool
    {
        $this->migration->displayTitle(sprintf(__('Upgrading LAPS plugin from %s'), $current_version));

        // Handle upgrades from version 1.x to 2.x
        if (version_compare($current_version, '2.0.0', '<')) {
            $this->upgradeFrom1x();
        }

        $this->migration->executeMigration();

        return true;
    }

    /**
     * Create configuration table
     *
     * @return boolean
     */
    private function createConfigTable(): bool
    {
        global $DB;

        $table = 'glpi_plugin_laps_configs';

        if (!$DB->tableExists($table)) {
            $query = "CREATE TABLE `$table` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `laps_server_url` varchar(255) DEFAULT NULL,
                `laps_api_key` text DEFAULT NULL,
                `laps_timeout` int(11) DEFAULT 30,
                `laps_cache_duration` int(11) DEFAULT 60,
                `laps_active` tinyint(1) DEFAULT 1,
                `date_creation` datetime DEFAULT NULL,
                `date_mod` datetime DEFAULT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

            return $DB->query($query);
        }

        return true;
    }

    /**
     * Create passwords cache table
     *
     * @return boolean
     */
    private function createPasswordsTable(): bool
    {
        global $DB;

        $table = 'glpi_plugin_laps_passwords';

        if (!$DB->tableExists($table)) {
            $query = "CREATE TABLE `$table` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `computer_id` int(11) NOT NULL,
                `password` text NOT NULL,
                `expiration_date` datetime DEFAULT NULL,
                `last_update` datetime NOT NULL,
                `date_creation` datetime DEFAULT NULL,
                `date_mod` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `computer_id` (`computer_id`),
                KEY `last_update` (`last_update`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

            return $DB->query($query);
        }

        return true;
    }

    /**
     * Create logs table
     *
     * @return boolean
     */
    private function createLogsTable(): bool
    {
        global $DB;

        $table = 'glpi_plugin_laps_logs';

        if (!$DB->tableExists($table)) {
            $query = "CREATE TABLE `$table` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `computer_id` int(11) NOT NULL,
                `user_id` int(11) NOT NULL,
                `action` varchar(50) NOT NULL,
                `message` text DEFAULT NULL,
                `date_creation` datetime NOT NULL,
                PRIMARY KEY (`id`),
                KEY `computer_id` (`computer_id`),
                KEY `user_id` (`user_id`),
                KEY `date_creation` (`date_creation`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

            return $DB->query($query);
        }

        return true;
    }

    /**
     * Insert default configuration
     *
     * @return void
     */
    private function insertDefaultConfig(): void
    {
        global $DB;

        $table = 'glpi_plugin_laps_configs';
        $count_query = "SELECT COUNT(*) as count FROM `$table`";
        $result = $DB->query($count_query);
        $count_row = $DB->fetchAssoc($result);

        if ($count_row['count'] == 0) {
            $insert_query = "INSERT INTO `$table` 
                            (`laps_server_url`, `laps_api_key`, `laps_timeout`, `laps_cache_duration`, `laps_active`, `date_creation`) 
                            VALUES ('', '', 30, 60, 1, NOW())";
            $DB->query($insert_query);
        }
    }

    /**
     * Upgrade from version 1.x
     *
     * @return void
     */
    private function upgradeFrom1x(): void
    {
        global $DB;

        $table = 'glpi_plugin_laps_configs';

        // Check if old structure exists
        $result = $DB->query("SHOW COLUMNS FROM `$table` LIKE 'laps_username'");
        if ($DB->numrows($result) > 0) {
            // Migrate from old structure
            $this->migration->addField($table, 'laps_api_key', 'text');
            $this->migration->dropField($table, 'laps_username');
            $this->migration->dropField($table, 'laps_password');
            $this->migration->changeField($table, 'connection_timeout', 'laps_timeout', 'int(11) DEFAULT 30');
            $this->migration->changeField($table, 'cache_duration', 'laps_cache_duration', 'int(11) DEFAULT 60');
            $this->migration->changeField($table, 'is_active', 'laps_active', 'tinyint(1) DEFAULT 1');
        }

        // Update passwords table structure if needed
        $passwords_table = 'glpi_plugin_laps_passwords';
        if ($DB->tableExists($passwords_table)) {
            $result = $DB->query("SHOW COLUMNS FROM `$passwords_table` LIKE 'computers_id'");
            if ($DB->numrows($result) > 0) {
                $this->migration->changeField($passwords_table, 'computers_id', 'computer_id', 'int(11) NOT NULL');
                $this->migration->dropField($passwords_table, 'computer_name');
                $this->migration->dropField($passwords_table, 'admin_password');
                $this->migration->dropField($passwords_table, 'password_expiry');
                $this->migration->dropField($passwords_table, 'last_sync');
                $this->migration->dropField($passwords_table, 'sync_status');
                $this->migration->dropField($passwords_table, 'error_message');
                $this->migration->addField($passwords_table, 'password', 'text NOT NULL');
                $this->migration->addField($passwords_table, 'expiration_date', 'datetime DEFAULT NULL');
                $this->migration->addField($passwords_table, 'last_update', 'datetime NOT NULL');
            }
        }

        // Update logs table structure if needed
        $logs_table = 'glpi_plugin_laps_logs';
        if ($DB->tableExists($logs_table)) {
            $result = $DB->query("SHOW COLUMNS FROM `$logs_table` LIKE 'computers_id'");
            if ($DB->numrows($result) > 0) {
                $this->migration->changeField($logs_table, 'computers_id', 'computer_id', 'int(11) NOT NULL');
                $this->migration->dropField($logs_table, 'computer_name');
                $this->migration->dropField($logs_table, 'details');
                $this->migration->addField($logs_table, 'message', 'text DEFAULT NULL');
            }
        }
    }
}