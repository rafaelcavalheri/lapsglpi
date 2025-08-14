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

use Config as GlpiConfig;
use CommonGLPI;
use Html;
use Session;
use Toolbox;

class Config extends GlpiConfig
{
    public static function getTypeName($nb = 0)
    {
        return __('LAPS Password Management', 'laps');
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        $tabName = '';
        if (!$withtemplate) {
            if ($item->getType() == 'Config') {
                $tabName = self::getTypeName();
            }
        }
        return $tabName;
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item->getType() == 'Config') {
            $config = new self();
            $config->showForm($item->getId());
        }
    }

    public function showForm($ID, $options = [])
    {
        global $CFG_GLPI;

        if (!isset($_SESSION) || !Session::haveRight('config', 'w')) {
            return false;
        }

        $config = self::getConfig();

        echo "<form name='form' action='" . $CFG_GLPI['root_doc'] . "/plugins/lapsglpi/front/config.form.php' method='post'>";
        echo "<div class='center' id='tabsbody'>";
        echo "<table class='tab_cadre_fixe'>";

        echo "<tr class='tab_bg_2'>";
        echo "<th colspan='2'>" . __('LAPS Configuration', 'laps') . "</th>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('LAPS Server URL', 'laps') . "</td>";
        echo "<td>";
        Html::autocompletionTextField($this, 'laps_server_url', [
            'value' => $config['laps_server_url'] ?? '',
            'size' => 50
        ]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('API Key', 'laps') . "</td>";
        echo "<td>";
        Html::autocompletionTextField($this, 'laps_api_key', [
            'value' => $config['laps_api_key'] ?? '',
            'size' => 50,
            'type' => 'password'
        ]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Connection Timeout (seconds)', 'laps') . "</td>";
        echo "<td>";
        Html::autocompletionTextField($this, 'laps_timeout', [
            'value' => $config['laps_timeout'] ?? 30,
            'size' => 10
        ]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Cache Duration (minutes)', 'laps') . "</td>";
        echo "<td>";
        Html::autocompletionTextField($this, 'laps_cache_duration', [
            'value' => $config['laps_cache_duration'] ?? 60,
            'size' => 10
        ]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Enable Plugin', 'laps') . "</td>";
        echo "<td>";
        Dropdown::showYesNo('laps_active', $config['laps_active'] ?? 1);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td class='center' colspan='2'>";
        echo "<input type='submit' name='update' value='" . _sx('button', 'Save') . "' class='submit'>";
        echo "&nbsp;&nbsp;";
        echo "<input type='submit' name='test' value='" . __('Test Connection', 'laps') . "' class='submit'>";
        echo "</td>";
        echo "</tr>";

        echo "</table>";
        echo "</div>";
        Html::closeForm();

        return true;
    }

    public static function getConfig()
    {
        global $DB;

        $config = [];
        $query = "SELECT * FROM glpi_plugin_laps_configs WHERE id = 1";
        $result = $DB->query($query);

        if ($result && $DB->numrows($result) > 0) {
            $config = $DB->fetchAssoc($result);
            
            // Decrypt API key if needed
            if (!empty($config['laps_api_key'])) {
                $config['laps_api_key'] = Toolbox::decrypt($config['laps_api_key'], GLPIKEY);
            }
        } else {
            // Default configuration
            $config = [
                'laps_server_url' => '',
                'laps_api_key' => '',
                'laps_timeout' => 30,
                'laps_cache_duration' => 60,
                'laps_active' => 1
            ];
        }

        return $config;
    }

    public static function testConnection()
    {
        $config = self::getConfig();
        
        if (empty($config['laps_server_url']) || empty($config['laps_api_key'])) {
            return [
                'success' => false,
                'message' => __('Server URL and API Key are required', 'laps')
            ];
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, rtrim($config['laps_server_url'], '/') . '/api/test');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $config['laps_timeout']);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $config['laps_api_key'],
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return [
                'success' => false,
                'message' => __('Connection error: ', 'laps') . $error
            ];
        }

        if ($httpCode === 200) {
            return [
                'success' => true,
                'message' => __('Connection successful', 'laps')
            ];
        } else {
            return [
                'success' => false,
                'message' => __('HTTP Error: ', 'laps') . $httpCode
            ];
        }
    }
}