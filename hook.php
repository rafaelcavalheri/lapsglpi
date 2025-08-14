<?php
/**
 * -------------------------------------------------------------------------
 * LAPS plugin for GLPI
 *
 * @copyright Copyright (C) 2024-2025 Rafael Cavalheri and contributors.
 * @license   https://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0-or-later
 * @link      https://github.com/pluginsGLPI/laps
 * -------------------------------------------------------------------------
 */

// Prevent direct access
if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

// Register autoloader for installation process
spl_autoload_register('plugin_lapsglpi_autoload');

/**
 * Plugin install process
 *
 * @param array $args supported arguments for upgrade process
 * @return boolean
 */
function plugin_lapsglpi_install(array $args = []): bool
{
    try {
        // Initialize migration
        $migration = new Migration(PLUGIN_LAPSGLPI_VERSION);
        $migration->displayMessage('Installing LAPS plugin');
        
        // Create installer instance
        $installer = new PluginLapsglpiInstall();
        
        // Check if already installed
        if ($installer->isInstalled() && !isset($args['force'])) {
            $migration->displayMessage('LAPS plugin is already installed');
            return true;
        }
        
        // Perform installation
        $result = $installer->install($migration);
        
        if ($result) {
            $migration->displayMessage('LAPS plugin installed successfully');
        } else {
            $migration->displayMessage('LAPS plugin installation failed', 'error');
        }
        
        return $result;
        
    } catch (Exception $e) {
        Toolbox::logError('LAPS Plugin Install Error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Plugin uninstall process
 *
 * @return boolean
 */
function plugin_lapsglpi_uninstall(): bool
{
    try {
        // Create installer instance
        $installer = new PluginLapsglpiInstall();
        
        // Perform uninstallation
        $result = $installer->uninstall();
        
        if ($result) {
            Toolbox::logInfo('LAPS plugin uninstalled successfully');
        } else {
            Toolbox::logError('LAPS plugin uninstallation failed');
        }
        
        return $result;
        
    } catch (Exception $e) {
        Toolbox::logError('LAPS Plugin Uninstall Error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Plugin update process
 *
 * @param array $args supported arguments for upgrade process
 * @return boolean
 */
function plugin_lapsglpi_update(array $args = []): bool
{
    try {
        // Create installer instance
        $installer = new PluginLapsglpiInstall();
        
        // Get current version
        $current_version = $installer->getCurrentVersion();
        
        // Check if update is needed
        if (version_compare($current_version, PLUGIN_LAPSGLPI_VERSION, '>=')) {
            return true; // Already up to date
        }
        
        // Initialize migration
        $migration = new Migration(PLUGIN_LAPSGLPI_VERSION);
        $migration->displayMessage(sprintf('Updating LAPS plugin from %s to %s', $current_version, PLUGIN_LAPSGLPI_VERSION));
        
        // Perform update
        $result = $installer->update($migration, $current_version);
        
        if ($result) {
            $migration->displayMessage('LAPS plugin updated successfully');
        } else {
            $migration->displayMessage('LAPS plugin update failed', 'error');
        }
        
        return $result;
        
    } catch (Exception $e) {
        Toolbox::logError('LAPS Plugin Update Error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get dropdown values for plugin
 *
 * @return array
 */
function plugin_lapsglpi_getDropdown(): array
{
    return [
        'PluginLapsConfig' => 'glpi_plugin_laps_configs'
    ];
}

/**
 * Define database relations
 *
 * @return array
 */
function plugin_lapsglpi_getDatabaseRelations(): array
{
    return [
        'glpi_computers' => [
            'glpi_plugin_laps_passwords' => 'computers_id'
        ]
    ];
}

/**
 * Get additional search options for items
 *
 * @param string $itemtype
 * @return array
 */
function plugin_lapsglpi_getAddSearchOptions(string $itemtype): array
{
    $options = [];
    
    if ($itemtype === 'Computer') {
        $options[5150] = [
            'table'         => 'glpi_plugin_laps_passwords',
            'field'         => 'last_updated',
            'name'          => __('LAPS Last Updated', 'laps'),
            'datatype'      => 'datetime',
            'massiveaction' => false,
            'joinparams'    => [
                'jointype' => 'LEFT JOIN'
            ]
        ];
        
        $options[5151] = [
            'table'         => 'glpi_plugin_laps_passwords',
            'field'         => 'expiration_date',
            'name'          => __('LAPS Password Expiration', 'laps'),
            'datatype'      => 'datetime',
            'massiveaction' => false,
            'joinparams'    => [
                'jointype' => 'LEFT JOIN'
            ]
        ];
    }
    
    return $options;
}