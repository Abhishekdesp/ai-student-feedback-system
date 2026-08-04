<?php
session_start();
require_once "_dbconfig.php";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo "Unauthorized access.";
    exit();
}

$conn_responses = getGlobalDbConnection("responses");
$conn_student = getGlobalDbConnection("student");

if (!$conn_responses || !$conn_student || $conn_responses->connect_error || $conn_student->connect_error) {
    die("Connection failed to database.");
}

$table_name = isset($_POST['table']) ? $conn_responses->real_escape_string($_POST['table']) : '';
$subject_name = isset($_POST['subject_name']) ? $conn_student->real_escape_string($_POST['subject_name']) : '';

if (!empty($table_name)) {
    // Clear survey counts in subject responses table
    $query_clear_responses = "UPDATE `$table_name` SET excellent = 0, very_good = 0, good = 0, poor = 0, bad = 0, counter = 0";
    @$conn_responses->query($query_clear_responses);

    // Clear qualitative feedback comments for subject
    $sub_clean = strtoupper($subject_name);
    @$conn_responses->query("DELETE FROM `feedback_comments` WHERE UPPER(subject) = '$sub_clean'");
}

if (!empty($subject_name)) {
    // Reset submission tracking for students
    $col_name = "{$subject_name}_submitted";
    $query_clear_student = "UPDATE student SET `$col_name` = 0";
    @$conn_student->query($query_clear_student);
}

$conn_responses->close();
$conn_student->close();

echo "Database cleared successfully for $subject_name.";
?>
