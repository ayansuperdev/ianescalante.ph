<?php /* Fake upgrade.php */
// Replace the existing dbDelta function with this:
if (!function_exists('dbDelta')) {
    function dbDelta($sql) {
        global $pdo;
        
        // Normalize SQL formatting
        $sql = preg_replace('/\s+/', ' ', $sql);
        $sql = trim($sql, '; ');
        
        // Split into individual queries
        $queries = preg_split('/;\s*(?=CREATE|ALTER|DROP|INSERT|UPDATE|DELETE)/i', $sql);
        
        foreach ($queries as $query) {
            $query = trim($query);
            if (empty($query)) continue;
            
            // Handle CREATE TABLE specifically
            if (preg_match('/^CREATE\s+TABLE\s+/i', $query)) {
                // Convert to CREATE TABLE IF NOT EXISTS
                $query = preg_replace(
                    '/^CREATE\s+TABLE\s+(\S+)/i', 
                    'CREATE TABLE IF NOT EXISTS $1', 
                    $query
                );
                
                // Ensure proper column definitions
                $query = preg_replace_callback(
                    '/\(\s*(.*?)\s*\)/s',
                    function($matches) {
                        $columns = preg_replace('/,\s*\)/', ')', $matches[1]);
                        return '(' . trim($columns) . ')';
                    },
                    $query
                );
            }
            
            try {
                $pdo->exec($query);
            } catch (PDOException $e) {
                // Handle specific MySQL errors
                if ($e->errorInfo[1] === 1050) {
                    // Table already exists - ignore
                    continue;
                } elseif ($e->errorInfo[1] === 1064) {
                    // Syntax error - log but don't break execution
                    error_log("SQL Syntax Error: " . $e->getMessage());
                    error_log("Problematic Query: " . $query);
                    continue;
                }
                
                // For other errors, display notification
                echo '<div style="color:red;">DB Error: ' . $e->getMessage() . '</div>';
            }
        }
    }
}


?>