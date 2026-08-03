<?php
/**
 * Centralized Database Configuration Helper
 * Supports Multi-DB (local XAMPP) and Single-DB (TiDB Cloud, Aiven, Railway) configurations automatically!
 */

$db_host = getenv('DB_HOST') ? getenv('DB_HOST') : '127.0.0.1';
$db_user = getenv('DB_USER') ? getenv('DB_USER') : 'root';
$db_pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$db_name = getenv('DB_NAME') ? getenv('DB_NAME') : '';

function getGlobalDbConnection($target_db = "") {
    global $db_host, $db_user, $db_pass, $db_name;
    
    // 1. If explicit DB_NAME environment variable is set (Single Cloud DB mode like TiDB/Railway)
    if (!empty($db_name)) {
        $conn = @new mysqli($db_host, $db_user, $db_pass, $db_name);
        if ($conn && !$conn->connect_error) {
            return $conn;
        }
    }

    // 2. Try target database (Multi-DB local XAMPP mode)
    $conn = @new mysqli($db_host, $db_user, $db_pass, $target_db);
    if ($conn && !$conn->connect_error) {
        return $conn;
    }

    // 3. Fallback: Connect to server without selecting DB, then select or use default
    $conn = @new mysqli($db_host, $db_user, $db_pass);
    if ($conn && !$conn->connect_error) {
        if (!empty($target_db)) {
            @$conn->select_db($target_db);
        }
        return $conn;
    }

    // 4. Fallback localhost socket for macOS XAMPP
    $conn = @new mysqli('localhost', $db_user, $db_pass, $target_db);
    return $conn;
}
?>
