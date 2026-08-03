<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Show Questions</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
</head>

<body>

    <div class="container mt-5">
        <h2>Questions</h2>

        <?php
        // Start the session
        session_start();

        // Check if questions are stored in the session
        if (isset($_SESSION['questions']) && !empty($_SESSION['questions'])) {
            echo '<table class="table mt-3">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Question</th>
                        </tr>
                    </thead>
                    <tbody>';

            foreach ($_SESSION['questions'] as $index => $question) {
                echo '<tr>
                        <td>' . ($index + 1) . '</td>
                        <td>' . $question . '</td>
                    </tr>';
            }

            echo '</tbody></table>';
        } else {
            echo '<div class="alert alert-info mt-3" role="alert">No questions found in the session.</div>';
        }

        // Clear the questions from the session
        unset($_SESSION['questions']);
        ?>

        <a href="questions.php" class="btn btn-primary mt-3">Go Back</a>
    </div>

    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>

</body>

</html>
