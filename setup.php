<?php
/**
 * LAPS-GLPI Plugin Setup
 * 
 * @author Rafael Cavalheri
 * @license GPL-2.0-or-later
 * @link https://github.com/pluginsGLPI/laps
 */

// Prevent direct access
if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

// Plugin version and compatibility
define('PLUGIN_LAPSGLPI_VERSION', '2.0.0');
define('PLUGIN_LAPSGLPI_SCHEMA_VERSION', '2.0.0');
define('PLUGIN_LAPSGLPI_MIN_GLPI_VERSION', '9.5.0');
define('PLUGIN_LAPSGLPI_MAX_GLPI_VERSION', '10.1.0');
define('PLUGIN_LAPSGLPI_IS_OFFICIAL_RELEASE', true);

// Plugin root directory
define('LAPSGLPI_ROOTDOC', Plugin::getWebDir('lapsglpi'));
define('LAPSGLPI_PLUGIN_DIR', Plugin::getPhpDir('lapsglpi'));

/**
 * Plugin initialization function
 */
function plugin_init_lapsglpi()
{
    global $CFG_GLPI, $PLUGIN_HOOKS;
    
    // CSRF compliance
    $PLUGIN_HOOKS['csrf_compliant']['lapsglpi'] = true;
    
    // Check if plugin is active
    if (!class_exists('Plugin') || !Plugin::isPluginActive('lapsglpi')) {
        return;
    }
    
    // Register autoloader
    spl_autoload_register('plugin_lapsglpi_autoload');
    
    // Register classes
    plugin_lapsglpi_registerClasses();
    
    // Setup hooks
    plugin_lapsglpi_setupHooks();
    
    // Add CSS and JS
    plugin_lapsglpi_addAssets();
}

/**
 * Plugin version information
 */
function plugin_version_lapsglpi()
{
    return [
        'name'           => 'LAPS Password Management',
        'version'        => PLUGIN_LAPSGLPI_VERSION,
        'author'         => 'Rafael Cavalheri',
        'license'        => 'GPL-2.0-or-later',
        'homepage'       => 'https://github.com/pluginsGLPI/laps',
        'requirements'   => [
            'glpi' => [
                'min' => PLUGIN_LAPSGLPI_MIN_GLPI_VERSION,
                'max' => PLUGIN_LAPSGLPI_MAX_GLPI_VERSION
            ],
            'php' => [
                'min' => '7.4.0'
            ]
        ]
    ];
}

/**
 * Check plugin prerequisites
 */
function plugin_lapsglpi_check_prerequisites()
{
    // Check PHP version
    if (version_compare(PHP_VERSION, '7.4.0', 'lt')) {
        echo 'This plugin requires PHP >= 7.4.0<br>';
        return false;
    }
    
    // Check required PHP extensions
    $required_extensions = ['curl', 'json', 'openssl'];
    foreach ($required_extensions as $extension) {
        if (!extension_loaded($extension)) {
            echo "This plugin requires the PHP {$extension} extension.<br>";
            return false;
        }
    }
    
    // Check GLPI version
    if (defined('GLPI_VERSION')) {
        if (version_compare(GLPI_VERSION, PLUGIN_LAPSGLPI_MIN_GLPI_VERSION, 'lt')) {
            echo "This plugin requires GLPI >= " . PLUGIN_LAPSGLPI_MIN_GLPI_VERSION . "<br>";
            return false;
        }
        
        if (version_compare(GLPI_VERSION, PLUGIN_LAPSGLPI_MAX_GLPI_VERSION, 'ge')) {
            echo "This plugin is not compatible with GLPI >= " . PLUGIN_LAPSGLPI_MAX_GLPI_VERSION . "<br>";
            return false;
        }
    }
    
    return true;
}

/**
 * Check plugin configuration
 */
function plugin_lapsglpi_check_config()
{
    return true;
}

/**
 * Get plugin friendly name
 */
function plugin_lapsglpi_getFriendlyName(): string
{
    return 'LAPS Password Management';
}

/**
 * Plugin autoloader
 */
function plugin_lapsglpi_autoload($classname)
{
    if (strpos($classname, 'PluginLaps') === 0) {
        $filename = LAPSGLPI_PLUGIN_DIR . '/inc/' . strtolower(str_replace('PluginLaps', '', $classname)) . '.class.php';
        if (file_exists($filename)) {
            include_once $filename;
            return true;
        }
    }
    return false;
}

/**
 * Register plugin classes
 */
function plugin_lapsglpi_registerClasses()
{
    Plugin::registerClass('PluginLapsConfig', [
        'addtabon' => 'Config'
    ]);
    
    Plugin::registerClass('PluginLapsComputer', [
        'addtabon' => 'Computer'
    ]);
}

/**
 * Setup plugin hooks
 */
function plugin_lapsglpi_setupHooks()
{
    global $PLUGIN_HOOKS;
    
    // Configuration page
    $PLUGIN_HOOKS['config_page']['lapsglpi'] = 'front/config.form.php';
    
    // Menu items
    $PLUGIN_HOOKS['menu_toadd']['lapsglpi'] = [
        'admin' => 'PluginLapsConfig'
    ];
    
    // Security hooks
    $PLUGIN_HOOKS['item_purge']['lapsglpi'] = [
        'Computer' => 'plugin_lapsglpi_item_purge'
    ];
}

/**
 * Add CSS and JavaScript assets
 */
function plugin_lapsglpi_addAssets()
{
    global $PLUGIN_HOOKS;
    
    // Add CSS
    $PLUGIN_HOOKS['add_css']['lapsglpi'] = 'css/laps.css';
    
    // Add JavaScript
    $PLUGIN_HOOKS['add_javascript']['lapsglpi'] = 'js/laps.js';
}

/**
 * Handle item purge
 */
function plugin_lapsglpi_item_purge($item)
{
    if ($item instanceof Computer) {
        // Clear any cached LAPS data for this computer
        // This could be implemented later if needed
    }
}