<?php
session_start();
require_once "_dbconfig.php";

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Check if all radio buttons are checked
    $allChecked = true;
    if (isset($_POST['response']) && is_array($_POST['response'])) {
        foreach ($_POST['response'] as $response) {
            if (empty($response)) {
                $allChecked = false;
                break;
            }
        }
    } else {
        $allChecked = false;
    }

    if ($allChecked) {
        // Connect to the database
        $conn = getGlobalDbConnection("responses");

        if (!$conn || $conn->connect_error) {
            die("Connection failed to responses database.");
        }

        // Process and insert responses into the database
        foreach ($_POST['response'] as $questionId => $response) {
            $questionId = intval($questionId);
            $response = intval($response);

            switch ($response) {
                case 5: $column = 'excellent'; break;
                case 4: $column = 'very_good'; break;
                case 3: $column = 'good'; break;
                case 2: $column = 'poor'; break;
                case 1: $column = 'bad'; break;
                default: continue 2;
            }

            $subject = isset($_SESSION['selected_subject']) ? strtolower($_SESSION['selected_subject']) : 'ajp';
            $tbl = "{$subject}_responses";
            $sql = "UPDATE `$tbl` SET `$column` = `$column` + 1 WHERE id = $questionId";
            $conn->query($sql);
        }

        $conn->close();

        // Redirect back to dashboard after submitting
        header("Location: dashboard.php");
        exit();
    } else {
        echo "Please answer all questions before submitting.";
    }
}
?>
