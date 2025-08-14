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

use CommonGLPI;
use Computer as GlpiComputer;
use Html;
use Session;
use Toolbox;

// GLPI constants should be available from core

class Computer extends CommonGLPI
{
    public static function getTypeName($nb = 0)
    {
        return __('LAPS Password', 'laps');
    }

    public function canUpdate()
    {
        return isset($_SESSION) && Session::haveRight('computer', 'w');
    }

    public static function canView()
    {
        return isset($_SESSION) && Session::haveRight('computer', 'r');
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item->getType() == 'Computer' && self::canView()) {
            return self::getTypeName();
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item->getType() == 'Computer') {
            self::showForComputer($item);
        }
        return true;
    }

    public static function showForComputer(GlpiComputer $computer)
    {
        global $CFG_GLPI;

        $config = Config::getConfig();
        
        if (!$config['laps_active']) {
            echo "<div class='center'>";
            echo "<p>" . __('LAPS plugin is not active', 'laps') . "</p>";
            echo "</div>";
            return;
        }

        echo "<div class='center'>";
        echo "<h2>" . __('LAPS Password for', 'laps') . " " . $computer->getName() . "</h2>";
        
        echo "<div id='laps-password-section'>";
        echo "<button id='show-password-btn' class='submit' onclick='showLapsPassword(" . $computer->getID() . ")'>";
        echo __('Show Administrator Password', 'laps');
        echo "</button>";
        
        echo "<div id='laps-loader' style='display:none; margin: 10px;'>";
        echo "<img src='" . $CFG_GLPI['root_doc'] . "/pics/loader.gif' alt='Loading...'>";
        echo " " . __('Loading password...', 'laps');
        echo "</div>";
        
        echo "<div id='laps-result' style='margin: 10px;'></div>";
        echo "</div>";
        
        echo "<hr>";
        
        echo "<div id='laps-sync-section'>";
        echo "<h3>" . __('Force Password Sync', 'laps') . "</h3>";
        echo "<form method='post' action='" . $CFG_GLPI['root_doc'] . "/plugins/lapsglpi/front/computer.form.php'>";
        echo "<input type='hidden' name='computer_id' value='" . $computer->getID() . "'>";
        echo "<input type='submit' name='force_sync' value='" . __('Force Sync', 'laps') . "' class='submit'>";
        echo "</form>";
        echo "</div>";
        
        echo "</div>";
        
        // Include CSS and JS
        echo "<link rel='stylesheet' type='text/css' href='" . $CFG_GLPI['root_doc'] . "/plugins/lapsglpi/css/laps.css'>";
        echo "<script type='text/javascript' src='" . $CFG_GLPI['root_doc'] . "/plugins/lapsglpi/js/laps.js'></script>";
    }

    public static function getLapsPassword($computer_id)
    {
        global $DB;
        
        $config = Config::getConfig();
        
        if (!$config['laps_active']) {
            return [
                'success' => false,
                'message' => __('LAPS plugin is not active', 'laps')
            ];
        }
        
        // Check cache first
        $query = "SELECT * FROM glpi_plugin_laps_passwords WHERE computer_id = " . intval($computer_id);
        $result = $DB->query($query);
        
        if ($result && $DB->numrows($result) > 0) {
            $cache = $DB->fetchAssoc($result);
            $cache_time = strtotime($cache['last_update']);
            $cache_duration = $config['laps_cache_duration'] * 60; // Convert to seconds
            
            if ((time() - $cache_time) < $cache_duration) {
                // Cache is still valid
                return [
                    'success' => true,
                    'password' => Toolbox::decrypt($cache['password'], GLPIKEY),
                    'expiration' => $cache['expiration_date'],
                    'from_cache' => true
                ];
            }
        }
        
        // Cache is invalid or doesn't exist, fetch from LAPS server
        return self::fetchFromLapsServer($computer_id);
    }

    private static function fetchFromLapsServer($computer_id)
    {
        global $DB;
        
        $config = Config::getConfig();
        $computer = new GlpiComputer();
        
        if (!$computer->getFromDB($computer_id)) {
            return [
                'success' => false,
                'message' => __('Computer not found', 'laps')
            ];
        }
        
        $ch = curl_init();
        $url = rtrim($config['laps_server_url'], '/') . '/api/password';
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $config['laps_timeout']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'computer_name' => $computer->getName()
        ]));
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
            self::logAction($computer_id, 'error', 'cURL Error: ' . $error);
            return [
                'success' => false,
                'message' => __('Connection error: ', 'laps') . $error
            ];
        }
        
        if ($httpCode !== 200) {
            self::logAction($computer_id, 'error', 'HTTP Error: ' . $httpCode);
            return [
                'success' => false,
                'message' => __('HTTP Error: ', 'laps') . $httpCode
            ];
        }
        
        $data = json_decode($response, true);
        
        if (!$data || !isset($data['password'])) {
            self::logAction($computer_id, 'error', 'Invalid response from LAPS server');
            return [
                'success' => false,
                'message' => __('Invalid response from LAPS server', 'laps')
            ];
        }
        
        // Adapt response to expected format
        $password_data = [
            'password' => $data['password'],
            'expiration' => $data['expiration'] ?? null
        ];
        
        // Update cache
        self::updateCache($computer_id, $password_data['password'], $password_data['expiration']);
        
        // Log successful action
        self::logAction($computer_id, 'success', 'Password retrieved successfully');
        
        return [
            'success' => true,
            'password' => $password_data['password'],
            'expiration' => $password_data['expiration'],
            'from_cache' => false
        ];
    }

    private static function updateCache($computer_id, $password, $expiration)
    {
        global $DB;
        
        $encrypted_password = Toolbox::encrypt($password, GLPIKEY);
        $now = date('Y-m-d H:i:s');
        
        $query = "SELECT id FROM glpi_plugin_laps_passwords WHERE computer_id = " . intval($computer_id);
        $result = $DB->query($query);
        
        if ($result && $DB->numrows($result) > 0) {
            // Update existing record
            $update_query = "UPDATE glpi_plugin_laps_passwords SET 
                           password = '" . $DB->escape($encrypted_password) . "',
                           expiration_date = " . ($expiration ? "'" . $DB->escape($expiration) . "'" : "NULL") . ",
                           last_update = '" . $now . "'
                           WHERE computer_id = " . intval($computer_id);
            $DB->query($update_query);
        } else {
            // Insert new record
            $insert_query = "INSERT INTO glpi_plugin_laps_passwords 
                           (computer_id, password, expiration_date, last_update) VALUES (
                           " . intval($computer_id) . ",
                           '" . $DB->escape($encrypted_password) . "',
                           " . ($expiration ? "'" . $DB->escape($expiration) . "'" : "NULL") . ",
                           '" . $now . "'
                           )";
            $DB->query($insert_query);
        }
    }

    private static function logAction($computer_id, $action, $message)
    {
        global $DB;
        
        $user_id = Session::getLoginUserID();
        $now = date('Y-m-d H:i:s');
        
        $query = "INSERT INTO glpi_plugin_laps_logs 
                 (computer_id, user_id, action, message, date_creation) VALUES (
                 " . intval($computer_id) . ",
                 " . intval($user_id) . ",
                 '" . $DB->escape($action) . "',
                 '" . $DB->escape($message) . "',
                 '" . $now . "'
                 )";
        $DB->query($query);
    }
}