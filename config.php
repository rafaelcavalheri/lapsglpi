<?php
/**
 * Plugin LAPS-GLPI - Configuration
 * Plugin configuration file
 */

// LAPS server settings
define('LAPS_SERVER_URL', 'https://laps.mogimirim.sp.gov.br/');
define('LAPS_API_KEY', 'glpi-integration-key-2024'); // New UUID key generated

// Connection settings
define('LAPS_CONNECTION_TIMEOUT', 30); // Timeout in seconds
define('LAPS_CACHE_DURATION', 300);    // Cache duration in seconds (5 minutes)

// Security settings
define('LAPS_ENCRYPT_PASSWORDS', true);  // Encrypt passwords in database
define('LAPS_LOG_ACTIVITIES', true);     // Log activities
define('LAPS_MAX_LOG_ENTRIES', 1000);    // Maximum log entries per computer

// Interface settings
define('LAPS_SHOW_EXPIRY_WARNING', true);  // Show expiry warning
define('LAPS_EXPIRY_WARNING_DAYS', 7);     // Days before expiry to show warning

// API settings
define('LAPS_API_VERSION', 'v1');           // LAPS server API version
define('LAPS_API_TIMEOUT', 10);             // API specific timeout
define('LAPS_API_RETRY_ATTEMPTS', 3);       // Retry attempts on error

// Debug settings
define('LAPS_DEBUG_MODE', false);           // Debug mode (development only)
define('LAPS_LOG_LEVEL', 'INFO');           // Log level: DEBUG, INFO, WARNING, ERROR