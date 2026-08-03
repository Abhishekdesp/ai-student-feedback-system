<?php
// Connect to the database
$conn_responses = new mysqli("localhost", "root", "", "responses");
$conn_student = new mysqli("localhost", "root", "", "student");

// Check the connection
if ($conn_responses->connect_error || $conn_student->connect_error) {
    die("Connection failed: " . $conn_responses->connect_error);
}

// Get POST data
$table_name = $_POST['table'];
$subject_name = $_POST['subject_name'];

// Clear specific columns in SubjectName_responses table
$query_clear_responses = "UPDATE $table_name SET excellent = '', very_good = '', good = '', poor = '', bad = '', counter = 0";
$conn_responses->query($query_clear_responses);

// Clear _submitted column in student table
$query_clear_student = "UPDATE student SET $subject_name" . "_submitted = ''";
$conn_student->query($query_clear_student);

// Close connections
$conn_responses->close();
$conn_student->close();

echo "Database cleared successfully.";
?>
