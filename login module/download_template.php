<?php
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=sample_students.csv');

$output = fopen('php://output', 'w');
fputcsv($output, array('id', 'sname', 'year', 'password'));
fputcsv($output, array('101', 'Aarav Sharma', 'Third', 'Pass@123'));
fputcsv($output, array('102', 'Priya Patel', 'Second', 'Pass@456'));
fputcsv($output, array('103', 'Rohan Verma', 'First', 'Pass@789'));
fclose($output);
exit();
?>
