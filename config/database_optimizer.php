<?php
// Database Query Optimizer
class DatabaseOptimizer {
    private $db;
    private $query_cache = [];
    private $cache_ttl = 300; // 5 minutes
    
    public function __construct($db) {
        $this->db = $db;
        $this->optimizeConnection();
    }
    
    /**
     * Optimize database connection settings
     */
    private function optimizeConnection() {
        try {
            // Disable emulation for better performance
            $this->db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            
            // Set fetch mode to associative for consistency
            $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // Enable persistent connections if supported
            $this->db->setAttribute(PDO::ATTR_PERSISTENT, true);
            
            // Set error mode to exceptions for better error handling
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
        } catch (Exception $e) {
            error_log("Database optimization warning: " . $e->getMessage());
        }
    }
    
    /**
     * Execute cached query
     */
    public function cachedQuery($query, $params = [], $cache_key = null) {
        $cache_key = $cache_key ?: md5($query . serialize($params));
        $cache_file = __DIR__ . '/../cache/query_' . $cache_key . '.json';
        
        // Check cache
        if (file_exists($cache_file)) {
            $cache_data = json_decode(file_get_contents($cache_file), true);
            if ($cache_data && (time() - $cache_data['timestamp']) < $this->cache_ttl) {
                return $cache_data['data'];
            }
        }
        
        // Execute query
        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $data = $stmt->fetchAll();
            
            // Cache result
            $cache_data = [
                'data' => $data,
                'timestamp' => time()
            ];
            
            $cache_dir = dirname($cache_file);
            if (!file_exists($cache_dir)) {
                mkdir($cache_dir, 0755, true);
            }
            
            file_put_contents($cache_file, json_encode($cache_data), LOCK_EX);
            
            return $data;
            
        } catch (Exception $e) {
            error_log("Cached query error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get user data with minimal queries
     */
    public function getUserData($user_id) {
        $cache_key = "user_data_$user_id";
        return $this->cachedQuery(
            "SELECT id, username, full_name, email, role, department FROM users WHERE id = ?",
            [$user_id],
            $cache_key
        )[0] ?? null;
    }
    
    /**
     * Get all staff and admin users in one query
     */
    public function getStaffAdminUsers() {
        return $this->cachedQuery(
            "SELECT id, email, full_name, role FROM users WHERE role IN ('staff', 'admin') AND email IS NOT NULL AND email != '' ORDER BY role, full_name",
            [],
            'staff_admin_users'
        );
    }
    
    /**
     * Get category data with cache
     */
    public function getCategories() {
        return $this->cachedQuery(
            "SELECT id, name, description FROM categories ORDER BY name",
            [],
            'categories'
        );
    }
    
    /**
     * Optimized request creation with minimal queries
     */
    public function createRequestOptimized($data) {
        $start_time = microtime(true);
        
        try {
            // Start transaction for atomicity
            $this->db->beginTransaction();
            
            // Insert request
            $query = "INSERT INTO service_requests 
                     (user_id, category_id, title, description, priority, status, created_at, updated_at) 
                     VALUES (?, ?, ?, ?, ?, 'open', NOW(), NOW())";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $data['user_id'],
                $data['category_id'],
                $data['title'],
                $data['description'],
                $data['priority']
            ]);
            
            $request_id = $this->db->lastInsertId();
            
            // Commit transaction
            $this->db->commit();
            
            $execution_time = round((microtime(true) - $start_time) * 1000, 2);
            error_log("Request creation completed in {$execution_time}ms (ID: {$request_id})");
            
            return $request_id;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Request creation failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Batch insert with transaction
     */
    public function batchInsert($table, $data, $columns) {
        if (empty($data)) {
            return;
        }
        
        $start_time = microtime(true);
        
        try {
            $this->db->beginTransaction();
            
            $values = [];
            $params = [];
            
            foreach ($data as $row) {
                $placeholders = [];
                foreach ($columns as $column) {
                    $placeholders[] = '?';
                    $params[] = $row[$column] ?? null;
                }
                $values[] = '(' . implode(',', $placeholders) . ')';
            }
            
            $query = "INSERT INTO $table (" . implode(',', $columns) . ") VALUES " . implode(',', $values);
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            
            $this->db->commit();
            
            $execution_time = round((microtime(true) - $start_time) * 1000, 2);
            error_log("Batch insert into $table: " . count($data) . " rows in {$execution_time}ms");
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Batch insert failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Clean old cache files
     */
    public function cleanCache() {
        $cache_dir = __DIR__ . '/../cache';
        if (!file_exists($cache_dir)) {
            return;
        }
        
        $files = glob($cache_dir . '/query_*.json');
        $current_time = time();
        
        foreach ($files as $file) {
            if (is_file($file) && ($current_time - filemtime($file)) > $this->cache_ttl) {
                unlink($file);
            }
        }
    }
}

// Optimized Database Helper
class OptimizedDatabaseHelper {
    private $optimizer;
    private static $instance = null;
    
    public static function getInstance($db) {
        if (self::$instance === null) {
            self::$instance = new self($db);
        }
        return self::$instance;
    }
    
    private function __construct($db) {
        $this->optimizer = new DatabaseOptimizer($db);
    }
    
    public function getOptimizer() {
        return $this->optimizer;
    }
    
    /**
     * Quick user lookup with cache
     */
    public function getUser($user_id) {
        return $this->optimizer->getUserData($user_id);
    }
    
    /**
     * Get notification targets efficiently
     */
    public function getNotificationTargets($roles = ['staff', 'admin']) {
        $placeholders = str_repeat('?,', count($roles) - 1) . '?';
        return $this->optimizer->cachedQuery(
            "SELECT id, email, full_name, role FROM users WHERE role IN ($placeholders) AND email IS NOT NULL AND email != ''",
            $roles,
            'notification_targets_' . implode('_', $roles)
        );
    }
    
    /**
     * Optimized request statistics
     */
    public function getRequestStats($user_id = null, $role = null) {
        $cache_key = "request_stats_{$user_id}_{$role}";
        
        $query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
                    SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed
                 FROM service_requests";
        
        $params = [];
        
        if ($role === 'user' && $user_id) {
            $query .= " WHERE user_id = ?";
            $params[] = $user_id;
        }
        
        return $this->optimizer->cachedQuery($query, $params, $cache_key)[0] ?? [];
    }
}
?>
