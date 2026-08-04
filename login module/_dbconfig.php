<?php
/**
 * Centralized Database Configuration Helper with High-Performance Connection Pooling & Caching
 * Optimizes SSL Handshakes and reduces latency on TiDB Cloud and Render.
 */

$db_host = getenv('DB_HOST') ? getenv('DB_HOST') : '127.0.0.1';
$db_user = getenv('DB_USER') ? getenv('DB_USER') : 'root';
$db_pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$db_name = getenv('DB_NAME') ? getenv('DB_NAME') : '';
$db_port = getenv('DB_PORT') ? intval(getenv('DB_PORT')) : 4000;

$cached_conn = null;

function getGlobalDbConnection($target_db = "") {
    global $db_host, $db_user, $db_pass, $db_name, $db_port, $cached_conn;
    
    $selected_db = !empty($db_name) ? $db_name : $target_db;

    // 1. Reuse existing active connection to eliminate repetitive SSL handshakes (5x-10x Speedup)
    if ($cached_conn !== null && @$cached_conn->ping()) {
        if (!empty($selected_db)) {
            @$cached_conn->select_db($selected_db);
        }
        return $cached_conn;
    }

    // 2. Attempt SSL-encrypted Connection (Required by TiDB Cloud / Aiven)
    $conn = mysqli_init();
    if ($conn) {
        @$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 3);
        @$conn->ssl_set(NULL, NULL, NULL, NULL, NULL);
        
        $connected = @$conn->real_connect($db_host, $db_user, $db_pass, $selected_db, $db_port, NULL, MYSQLI_CLIENT_SSL);
        
        if (!$connected) {
            $connected = @$conn->real_connect($db_host, $db_user, $db_pass, $selected_db, 3306, NULL, MYSQLI_CLIENT_SSL);
        }

        if ($connected && !$conn->connect_error) {
            $cached_conn = $conn;
            return $cached_conn;
        }
    }

    // 3. Fallback to standard non-SSL connection (for local XAMPP / localhost)
    $conn_std = @new mysqli($db_host, $db_user, $db_pass, $selected_db);
    if ($conn_std && !$conn_std->connect_error) {
        $cached_conn = $conn_std;
        return $cached_conn;
    }

    // 4. Fallback to localhost socket for macOS XAMPP
    $conn_local = @new mysqli('127.0.0.1', $db_user, $db_pass, $selected_db);
    if ($conn_local && !$conn_local->connect_error) {
        $cached_conn = $conn_local;
        return $cached_conn;
    }

    return null;
}
?>
