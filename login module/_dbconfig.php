<?php
/**
 * Centralized Database Configuration Helper
 * Supports SSL Transport required by TiDB Cloud, Aiven, and Railway Cloud Databases!
 */

$db_host = getenv('DB_HOST') ? getenv('DB_HOST') : '127.0.0.1';
$db_user = getenv('DB_USER') ? getenv('DB_USER') : 'root';
$db_pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$db_name = getenv('DB_NAME') ? getenv('DB_NAME') : '';
$db_port = getenv('DB_PORT') ? intval(getenv('DB_PORT')) : 4000;

function getGlobalDbConnection($target_db = "") {
    global $db_host, $db_user, $db_pass, $db_name, $db_port;
    
    $selected_db = !empty($db_name) ? $db_name : $target_db;

    // 1. Attempt SSL-encrypted Connection (Required by TiDB Cloud / Aiven)
    $conn = mysqli_init();
    if ($conn) {
        @$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);
        @$conn->ssl_set(NULL, NULL, NULL, NULL, NULL);
        
        // Try SSL connection on specified port (e.g. 4000 or 3306)
        $connected = @$conn->real_connect($db_host, $db_user, $db_pass, $selected_db, $db_port, NULL, MYSQLI_CLIENT_SSL);
        
        if (!$connected) {
            // Try standard MySQL port 3306 with SSL
            $connected = @$conn->real_connect($db_host, $db_user, $db_pass, $selected_db, 3306, NULL, MYSQLI_CLIENT_SSL);
        }

        if ($connected && !$conn->connect_error) {
            return $conn;
        }
    }

    // 2. Fallback to standard non-SSL connection (for local XAMPP / localhost)
    $conn_std = @new mysqli($db_host, $db_user, $db_pass, $selected_db);
    if ($conn_std && !$conn_std->connect_error) {
        return $conn_std;
    }

    // 3. Fallback to localhost socket for macOS XAMPP
    return @new mysqli('127.0.0.1', $db_user, $db_pass, $selected_db);
}
?>
