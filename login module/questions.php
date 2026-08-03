<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Question Renewal</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <style>
        .edit-form {
            display: none;
        }

        /* Center the container horizontally and position slightly at the top */
        body {
            display: flex;
            align-items: flex-start;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }

        /* Style the container */
        .container-box {
            background-color: #f8f9fa; /* Bootstrap default background color */
            padding: 20px;
            border-radius: 10px;
            margin-top: 30px; /* Adjust the top margin as needed */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body>

    <div style="position: absolute; top: 15px; left: 15px;">
      <a class="btn btn-dark btn-sm font-weight-bold" href="Homepage.php" title="Back" style="font-size: 1.1rem; padding: 2px 10px;">&larr;</a>
    </div>

    <div class="container mt-5 container-box">
        <h2 class="text-center">Question Renewal</h2>

        <?php
        // PHP code to handle question renewal
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "questions";

        $conn = new mysqli($servername, $username, $password, $dbname);

        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        // Create the questions table if not exists
        $createTableQuery = "CREATE TABLE IF NOT EXISTS questions (
            id INT PRIMARY KEY AUTO_INCREMENT,
            questions VARCHAR(255) NOT NULL
        )";

        if ($conn->query($createTableQuery) === FALSE) {
            echo "Error creating table: " . $conn->error;
        }

        // Function to fetch and display questions
        function showQuestions($conn)
        {
            $result = $conn->query("SELECT * FROM questions");

            if ($result->num_rows > 0) {
                echo '<table class="table mt-3">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Question</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>';

                while ($row = $result->fetch_assoc()) {
                    echo '<tr>
                        <td>' . $row["id"] . '</td>
                        <td>' . $row["questions"] . '</td>
                        <td>
                            <button class="btn btn-info btn-sm edit-button" name="editQuestion" data-questionid="' . $row["id"] . '">Edit</button>
                            <div class="edit-form" id="editForm_' . $row["id"] . '">
                                <form method="post" action="' . htmlspecialchars($_SERVER["PHP_SELF"]) . '">
                                    <input type="hidden" name="editedQuestionID" value="' . $row["id"] . '">
                                    <input type="text" class="form-control" name="editedQuestion" value="' . $row["questions"] . '">
                                    <button class="btn btn-warning btn-sm save-button" name="saveEditedQuestion">Save Changes</button>
                                </form>
                            </div>
                        </td>
                    </tr>';
                }

                echo '</tbody></table>';
            } else {
                echo '<div class="alert alert-info mt-3" role="alert">No questions found in the database.</div>';
            }
        }

        // Check if the "Show Questions" button has been submitted
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["showQuestions"])) {
            showQuestions($conn);
        } else {
            // If "Show Questions" button not submitted, show questions directly
            showQuestions($conn);
        }

        // Handle form submissions
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            if (isset($_POST["editQuestion"])) {
                // Edit Question button clicked
                $editQuestionID = $_POST["editQuestion"];

                // Display the edit form for the selected question
                echo '<div class="alert alert-info mt-3" role="alert">Editing question with ID: ' . $editQuestionID . '</div>';

                // Retrieve the question data for the selected question
                $result = $conn->query("SELECT * FROM questions WHERE id = $editQuestionID");

                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    echo '<div class="edit-form" id="editForm_' . $row["id"] . '">
                            <form method="post" action="' . htmlspecialchars($_SERVER["PHP_SELF"]) . '">
                                <input type="hidden" name="editedQuestionID" value="' . $row["id"] . '">
                                <input type="text" class="form-control" name="editedQuestion" value="' . $row["questions"] . '">
                                <button class="btn btn-warning btn-sm save-button" name="saveEditedQuestion">Save Changes</button>
                            </form>
                        </div>';
                } else {
                    echo '<div class="alert alert-danger mt-3" role="alert">Error fetching question data for editing.</div>';
                }
            }

            if (isset($_POST["saveEditedQuestion"])) {
                // Save Edited Question button clicked
                $editedQuestionID = $_POST["editedQuestionID"];
                $editedQuestion = $_POST["editedQuestion"];

                // Update the edited question in the database
                $sql = "UPDATE questions SET questions = '$editedQuestion' WHERE id = '$editedQuestionID'";

                if ($conn->query($sql) === TRUE) {
                    echo '<div class="alert alert-success mt-3" role="alert">Question updated successfully!</div>';
                } else {
                    echo '<div class="alert alert-danger mt-3" role="alert">Error updating question: ' . $conn->error . '</div>';
                }
            }
        }

        ?>

        
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.edit-button').forEach(function (button) {
                button.addEventListener('click', function () {
                    var questionId = this.getAttribute('data-questionid');
                    editQuestion(questionId);
                });
            });

            function editQuestion(id) {
                // Toggle the display of the edit form for the clicked question
                var editForm = document.getElementById('editForm_' + id);

                // Toggle the display using the "style.display" property
                if (editForm.style.display === 'none' || editForm.style.display === '') {
                    editForm.style.display = 'block';
                } else
                {
                    editForm.style.display = 'none';
                }
            }
        });

    </script>

    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>

</body>

</html>
