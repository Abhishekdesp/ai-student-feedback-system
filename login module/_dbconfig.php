<?php
/**
 * Centralized Database Configuration Helper
 * Configures DB host, username, password, and port for Local XAMPP and Live Hosting Servers (InfinityFree, Hostinger, cPanel, etc.)
 */

// Host config (Default fallback: 127.0.0.1 for local XAMPP, change to live DB host when deploying)
$db_host = getenv('DB_HOST') ? getenv('DB_HOST') : '127.0.0.1';
$db_user = getenv('DB_USER') ? getenv('DB_USER') : 'root';
$db_pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';

function getGlobalDbConnection($dbname = "") {
    global $db_host, $db_user, $db_pass;
    
    $conn = @new mysqli($db_host, $db_user, $db_pass, $dbname);
    if ($conn->connect_error) {
        // Fallback try localhost socket if TCP 127.0.0.1 fails
        $conn = @new mysqli('localhost', $db_user, $db_pass, $dbname);
    }
    return $conn;
}
?>
