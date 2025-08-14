<?php
/**
 * LAPS-GLPI Plugin Audit Class
 * 
 * @author Rafael Cavalheri
 * @license GPL-2.0-or-later
 */

class PluginLapsAudit extends CommonDBTM
{
    static $rightname = 'config';
    
    // Audit event types
    const EVENT_PASSWORD_VIEW = 'password_view';
    const EVENT_PASSWORD_COPY = 'password_copy';
    const EVENT_CONFIG_UPDATE = 'config_update';
    const EVENT_CONNECTION_TEST = 'connection_test';
    const EVENT_CACHE_CLEAR = 'cache_clear';
    const EVENT_LOGIN_ATTEMPT = 'login_attempt';
    
    static function getTable($classname = null) {
        return 'glpi_plugin_laps_audit';
    }
    
    static function getTypeName($nb = 0) {
        return __('LAPS Audit Log', 'laps');
    }
    
    /**
     * Log an audit event
     *
     * @param string $event_type Type of event
     * @param array $data Additional data to log
     * @param int $computers_id Computer ID (if applicable)
     * @param bool $success Whether the action was successful
     * @return bool
     */
    static function logEvent(string $event_type, array $data = [], int $computers_id = 0, bool $success = true): bool
    {
        global $DB;
        
        // Get current user info
        $user_id = Session::getLoginUserID();
        $user_name = Session::getLoginUserName();
        $user_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        // Prepare audit data
        $audit_data = [
            'event_type' => $event_type,
            'users_id' => $user_id,
            'user_name' => $user_name,
            'user_ip' => $user_ip,
            'user_agent' => $user_agent,
            'computers_id' => $computers_id,
            'success' => $success ? 1 : 0,
            'event_data' => json_encode($data),
            'date_creation' => date('Y-m-d H:i:s')
        ];
        
        try {
            return $DB->insert('glpi_plugin_laps_audit', $audit_data);
        } catch (Exception $e) {
            // Log to system log if database insert fails
            Toolbox::logError('LAPS Audit Log Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log password view event
     *
     * @param int $computers_id
     * @param bool $success
     * @return bool
     */
    static function logPasswordView(int $computers_id, bool $success = true): bool
    {
        return self::logEvent(
            self::EVENT_PASSWORD_VIEW,
            ['computer_id' => $computers_id],
            $computers_id,
            $success
        );
    }
    
    /**
     * Log password copy event
     *
     * @param int $computers_id
     * @return bool
     */
    static function logPasswordCopy(int $computers_id): bool
    {
        return self::logEvent(
            self::EVENT_PASSWORD_COPY,
            ['computer_id' => $computers_id],
            $computers_id,
            true
        );
    }
    
    /**
     * Log configuration update event
     *
     * @param array $changes
     * @param bool $success
     * @return bool
     */
    static function logConfigUpdate(array $changes, bool $success = true): bool
    {
        // Remove sensitive data from changes
        $safe_changes = $changes;
        if (isset($safe_changes['laps_api_key'])) {
            $safe_changes['laps_api_key'] = '[REDACTED]';
        }
        
        return self::logEvent(
            self::EVENT_CONFIG_UPDATE,
            ['changes' => $safe_changes],
            0,
            $success
        );
    }
    
    /**
     * Log connection test event
     *
     * @param string $server_url
     * @param bool $success
     * @param string $error_message
     * @return bool
     */
    static function logConnectionTest(string $server_url, bool $success, string $error_message = ''): bool
    {
        return self::logEvent(
            self::EVENT_CONNECTION_TEST,
            [
                'server_url' => $server_url,
                'error_message' => $error_message
            ],
            0,
            $success
        );
    }
    
    /**
     * Get audit logs with filters
     *
     * @param array $filters
     * @param int $limit
     * @param int $offset
     * @return array
     */
    static function getAuditLogs(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        global $DB;
        
        $where = [];
        
        // Apply filters
        if (!empty($filters['event_type'])) {
            $where['event_type'] = $filters['event_type'];
        }
        
        if (!empty($filters['users_id'])) {
            $where['users_id'] = $filters['users_id'];
        }
        
        if (!empty($filters['computers_id'])) {
            $where['computers_id'] = $filters['computers_id'];
        }
        
        if (!empty($filters['date_from'])) {
            $where[] = ['date_creation' => ['>=', $filters['date_from']]];
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = ['date_creation' => ['<=', $filters['date_to']]];
        }
        
        if (isset($filters['success'])) {
            $where['success'] = $filters['success'] ? 1 : 0;
        }
        
        $iterator = $DB->request([
            'FROM' => 'glpi_plugin_laps_audit',
            'WHERE' => $where,
            'ORDER' => 'date_creation DESC',
            'LIMIT' => $limit,
            'START' => $offset
        ]);
        
        $logs = [];
        foreach ($iterator as $log) {
            $log['event_data'] = json_decode($log['event_data'], true);
            $logs[] = $log;
        }
        
        return $logs;
    }
    
    /**
     * Get audit statistics
     *
     * @param array $filters
     * @return array
     */
    static function getAuditStats(array $filters = []): array
    {
        global $DB;
        
        $where = [];
        
        // Apply date filters
        if (!empty($filters['date_from'])) {
            $where[] = ['date_creation' => ['>=', $filters['date_from']]];
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = ['date_creation' => ['<=', $filters['date_to']]];
        }
        
        // Get event type statistics
        $event_stats = [];
        $iterator = $DB->request([
            'SELECT' => ['event_type', 'COUNT(*) as count'],
            'FROM' => 'glpi_plugin_laps_audit',
            'WHERE' => $where,
            'GROUPBY' => 'event_type'
        ]);
        
        foreach ($iterator as $row) {
            $event_stats[$row['event_type']] = $row['count'];
        }
        
        // Get user statistics
        $user_stats = [];
        $iterator = $DB->request([
            'SELECT' => ['user_name', 'COUNT(*) as count'],
            'FROM' => 'glpi_plugin_laps_audit',
            'WHERE' => $where,
            'GROUPBY' => 'user_name',
            'ORDER' => 'count DESC',
            'LIMIT' => 10
        ]);
        
        foreach ($iterator as $row) {
            $user_stats[$row['user_name']] = $row['count'];
        }
        
        // Get success/failure statistics
        $success_stats = [];
        $iterator = $DB->request([
            'SELECT' => ['success', 'COUNT(*) as count'],
            'FROM' => 'glpi_plugin_laps_audit',
            'WHERE' => $where,
            'GROUPBY' => 'success'
        ]);
        
        foreach ($iterator as $row) {
            $success_stats[$row['success'] ? 'success' : 'failure'] = $row['count'];
        }
        
        return [
            'event_types' => $event_stats,
            'top_users' => $user_stats,
            'success_failure' => $success_stats
        ];
    }
    
    /**
     * Clean old audit logs
     *
     * @param int $days_to_keep
     * @return int Number of deleted records
     */
    static function cleanOldLogs(int $days_to_keep = 90): int
    {
        global $DB;
        
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days_to_keep} days"));
        
        $result = $DB->delete('glpi_plugin_laps_audit', [
            'date_creation' => ['<', $cutoff_date]
        ]);
        
        return $DB->affectedRows();
    }
    
    /**
     * Export audit logs to CSV
     *
     * @param array $filters
     * @return string CSV content
     */
    static function exportToCsv(array $filters = []): string
    {
        $logs = self::getAuditLogs($filters, 10000); // Get up to 10k records
        
        $csv = "Date,Event Type,User,IP Address,Computer ID,Success,Details\n";
        
        foreach ($logs as $log) {
            $details = is_array($log['event_data']) ? json_encode($log['event_data']) : '';
            $csv .= sprintf(
                "%s,%s,%s,%s,%d,%s,\"%s\"\n",
                $log['date_creation'],
                $log['event_type'],
                $log['user_name'],
                $log['user_ip'],
                $log['computers_id'],
                $log['success'] ? 'Yes' : 'No',
                str_replace('"', '""', $details)
            );
        }
        
        return $csv;
    }
}