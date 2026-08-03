<?php
// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Check if all radio buttons are checked
    $allChecked = true;
    foreach ($_POST['response'] as $response) {
        if (empty($response)) {
            $allChecked = false;
            break;
        }
    }

    if ($allChecked) {
        // Connect to the database
        $conn = new mysqli("localhost", "root", "", "responses");

        // Check the connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        // Process and insert responses into the database
        foreach ($_POST['response'] as $questionId => $response) {
            // Ensure that the values are safe to insert into the database (to prevent SQL injection)
            $questionId = $conn->real_escape_string($questionId);
            $response = $conn->real_escape_string($response);

            // Insert the response into the database
            $sql = "INSERT INTO responses (question_id, response) VALUES ('$questionId', '$response')";

            if ($conn->query($sql) !== TRUE) {
                echo "Error: " . $sql . "<br>" . $conn->error;
            }
        }

        // Close the database connection
        $conn->close();

        // Redirect back to the survey form or display a success message
        header("Location: shomepage.php");
        exit();
    } else {
        echo "<script>alert('Please answer all questions.');</script>";
        echo "<script>window.history.back();</script>"; // Go back to the previous page
        exit();
    }
} else {
    // If the form is not submitted, redirect to the survey form
    header("Location: shomepage.php");
    exit();
}
?>


