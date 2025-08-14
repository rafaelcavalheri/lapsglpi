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

use DB;

class Uninstall
{
    /**
     * Uninstall plugin
     *
     * @return boolean
     */
    public function uninstall(): bool
    {
        global $DB;

        // Drop plugin tables
        $tables = [
            'glpi_plugin_laps_configs',
            'glpi_plugin_laps_passwords',
            'glpi_plugin_laps_logs'
        ];

        foreach ($tables as $table) {
            if ($DB->tableExists($table)) {
                $query = "DROP TABLE `$table`";
                if (!$DB->query($query)) {
                    return false;
                }
            }
        }

        // Clean up any remaining plugin data
        $this->cleanupPluginData();

        return true;
    }

    /**
     * Clean up plugin data
     *
     * @return void
     */
    private function cleanupPluginData(): void
    {
        global $DB;

        // Remove plugin configuration from glpi_configs if exists
        $DB->query("DELETE FROM glpi_configs WHERE context = 'plugin:laps'");

        // Remove any plugin-specific rights if they were created
        $rights_to_remove = ['plugin_laps_computer', 'plugin_laps_config'];
        foreach ($rights_to_remove as $right) {
            $DB->query("DELETE FROM glpi_profilerights WHERE name = '$right'");
        }

        // Remove plugin from glpi_plugins table (this is usually handled by GLPI core)
        // but we can ensure it's clean
        $DB->query("DELETE FROM glpi_plugins WHERE directory = 'lapsglpi'");
    }
}