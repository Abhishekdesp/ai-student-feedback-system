<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';
require_once "_dbconfig.php";

use PhpOffice\PhpSpreadsheet\IOFactory;

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo "Unauthorized access.";
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["file"]) && $_FILES["file"]["error"] == UPLOAD_ERR_OK) {

    $faculty_name = isset($_POST['faculty_name']) ? $_POST['faculty_name'] : '';
    $subject_name = isset($_POST['subject_name']) ? $_POST['subject_name'] : '';

    $file_tmp = $_FILES["file"]["tmp_name"];

    try {
        // Load the uploaded Excel file
        $spreadsheet = IOFactory::load($file_tmp);
        $worksheet = $spreadsheet->getActiveSheet();

        // Insert the faculty name into B column, 9th row
        $worksheet->setCellValue('B9', "Name of Staff: $faculty_name");

        // Connect to the faculty database
        $conn_faculty = getGlobalDbConnection("faculty");

        if (!$conn_faculty || $conn_faculty->connect_error) {
            die("Connection failed to faculty database.");
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
            $table_name = strtolower($subject_name) . "_responses";

            // Prepare and execute query to fetch questions, excellent, very_good, good, poor, and bad from the subjectname_Responses table
            $conn_responses = getGlobalDbConnection("responses");
            $data_query = "SELECT questions, excellent, very_good, good, poor, bad, counter FROM `$table_name`";
            $data_result = $conn_responses->query($data_query);

            if ($data_result && $data_result->num_rows > 0) {
                $questions = [];
                $excellents = [];
                $veryGoods = [];
                $goods = [];
                $poors = [];
                $bads = [];
                $counters = [];

                while ($data_row = $data_result->fetch_assoc()) {
                    $questions[] = $data_row["questions"];
                    $excellents[] = $data_row["excellent"];
                    $veryGoods[] = $data_row["very_good"];
                    $goods[] = $data_row["good"];
                    $poors[] = $data_row["poor"];
                    $bads[] = $data_row["bad"];
                    $counters[] = $data_row["counter"];
                }

                // Insert fetched data into cells
                for ($i = 0; $i < count($questions); $i++) {
                    $row_number = 17 + $i;
                    $worksheet->setCellValue('B' . $row_number, $questions[$i]);
                    $worksheet->setCellValue('C' . $row_number, $excellents[$i]);
                    $worksheet->setCellValue('D' . $row_number, $veryGoods[$i]);
                    $worksheet->setCellValue('E' . $row_number, $goods[$i]);
                    $worksheet->setCellValue('F' . $row_number, $poors[$i]);
                    $worksheet->setCellValue('G' . $row_number, $bads[$i]);
                }

                // Insert counter value into cell C10
                if (!empty($counters)) {
                    $worksheet->setCellValue('C10', $counters[0]);
                }

                $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
                $writer->save($file_path);

                // Set headers to trigger file download
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment;filename="' . $subject_name . '.xlsx"');
                header('Cache-Control: max-age=0');

                // Output the Excel file directly to the browser
                readfile($file_path);

                // Delete the file after sending it
                unlink($file_path);
                exit;
            } else {
                echo "No data found in database table $table_name.";
            }
            $conn_responses->close();
        } else {
            echo "No faculty record found for subject $subject_name.";
        }

        $conn_faculty->close();
    } catch (Exception $e) {
        echo "Error loading spreadsheet: " . $e->getMessage();
    }
} else {
    echo "No file uploaded or error uploading file.";
}
?>
