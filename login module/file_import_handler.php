<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
use PhpOffice\PhpSpreadsheet\IOFactory;

// Server-side PHP script to handle file import
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if file was uploaded without errors
    if (isset($_FILES["file"]) && $_FILES["file"]["error"] == 0) {
        $faculty_name = $_POST["faculty_name"];
        $subject_name = $_POST["subject_name"];

        // Here, you can write code to handle the file import operation
        $file_name = $_FILES["file"]["name"];
        $file_tmp = $_FILES["file"]["tmp_name"];

        // Load PhpSpreadsheet library
        require 'vendor/autoload.php';

        // Load the uploaded Excel file
        $spreadsheet = IOFactory::load($file_tmp);

        // Get the first worksheet
        $worksheet = $spreadsheet->getActiveSheet();

        // Insert the faculty name into B column, 9th row
        $worksheet->setCellValue('B9', "Name of Staff: $faculty_name");

        // Connect to the faculty database
        $conn_faculty = new mysqli("localhost", "root", "", "faculty");

        // Check the connection
        if ($conn_faculty->connect_error) {
            die("Connection failed: " . $conn_faculty->connect_error);
        }

        // Prepare and execute query to fetch semester, scheme, and faculty name based on subject name
        $query = "SELECT name, semester, scheme FROM faculty WHERE subject = '$subject_name'";
        $result = $conn_faculty->query($query);

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $faculty_name = $row["name"];
            $semester = $row["semester"];
            $scheme = $row["scheme"];

            // Set values into specific cells
            $worksheet->setCellValue('B12', "Course: $subject_name");
            $worksheet->setCellValue('H12', "Class: CM $semester$scheme");

            // Ensure uploads directory exists
            if (!file_exists(__DIR__ . '/uploads')) {
                mkdir(__DIR__ . '/uploads', 0777, true);
            }

            // Save the modified Excel file with the subject name
            $file_path = __DIR__ . "/uploads/" . $subject_name . ".xlsx";

            // Fetch table name from the responses database based on subject name
            $table_name = $subject_name . "_responses";

            // Prepare and execute query to fetch questions, excellent, very_good, good, poor, and bad from the subjectname_Responses table
            $conn_responses = new mysqli("localhost", "root", "", "responses");
            $data_query = "SELECT questions, excellent, very_good, good, poor, bad, counter FROM $table_name";
            $data_result = $conn_responses->query($data_query);

            if ($data_result && $data_result->num_rows > 0) {
                $questions = [];
                $excellents = [];
                $veryGoods = [];
                $goods = [];
                $poors = [];
                $bads = [];
                $counterSum = 0;
                while ($row = $data_result->fetch_assoc()) {
                    $questions[] = $row["questions"];
                    $excellents[] = $row["excellent"];
                    $veryGoods[] = $row["very_good"];
                    $goods[] = $row["good"];
                    $poors[] = $row["poor"];
                    $bads[] = $row["bad"];
                    $counterSum += intval($row["counter"]);
                }

                // Write questions, excellent, very_good, good, poor, and bad into the Excel file
                $start_row = 15;
                foreach ($questions as $index => $question) {
                    if ($index < 10) { // Write only the first 10 questions
                        $cell_question = "C" . ($start_row + $index);
                        $cell_excellent = "F" . ($start_row + $index);
                        $cell_very_good = "G" . ($start_row + $index);
                        $cell_good = "H" . ($start_row + $index);
                        $cell_poor = "I" . ($start_row + $index);
                        $cell_bad = "J" . ($start_row + $index);
                        $worksheet->setCellValue($cell_question, $question);
                        $worksheet->setCellValue($cell_excellent, $excellents[$index]);
                        $worksheet->setCellValue($cell_very_good, $veryGoods[$index]);
                        $worksheet->setCellValue($cell_good, $goods[$index]);
                        $worksheet->setCellValue($cell_poor, $poors[$index]);
                        $worksheet->setCellValue($cell_bad, $bads[$index]);
                    }
                }

                // Write the counter sum into column E30
                $worksheet->setCellValue('E30', $counterSum);

                // Create the Excel file
                $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
                $writer->save($file_path);

                echo "File exported successfully.";
            } else {
                echo "No data found for the given subject.";
            }

            // Close the responses database connection
            $conn_responses->close();
        } else {
            echo "Scheme and semester not found for the given subject.";
        }

        // Close faculty database connection
        $conn_faculty->close();
    } else {
        // No file uploaded or error during upload
        echo "Error uploading file.";
    }
    exit();
}
?>
