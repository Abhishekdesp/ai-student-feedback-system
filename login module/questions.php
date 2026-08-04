<?php
session_start();
require_once "_dbconfig.php";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

$conn = getGlobalDbConnection("questions");

if (!$conn || $conn->connect_error) {
    die("Connection failed to questions database.");
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Question Renewal - Student Feedback System</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            color: #1e293b;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            padding-bottom: 50px;
        }

        .container-box {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            margin-top: 40px;
        }
    </style>
</head>

<body>

    <div style="position: absolute; top: 15px; left: 15px;">
        <a class="btn btn-outline-secondary btn-sm" href="Homepage.php" title="Back">&larr; Back</a>
    </div>

    <div class="container mt-5 container-box">
        <h3 class="text-center fw-bold mb-4">Question Renewal & Management</h3>

        <?php
        // Create the questions table if not exists
        $createTableQuery = "CREATE TABLE IF NOT EXISTS questions (
            id INT PRIMARY KEY AUTO_INCREMENT,
            questions VARCHAR(255) NOT NULL
        )";

        if ($conn->query($createTableQuery) === FALSE) {
            echo '<div class="alert alert-danger">Error creating table: ' . htmlspecialchars($conn->error) . '</div>';
        }

        // Function to fetch and display questions
        function showQuestions($conn)
        {
            $result = $conn->query("SELECT * FROM questions ORDER BY id ASC");

            if ($result && $result->num_rows > 0) {
                echo '<table class="table table-hover align-middle mt-3">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Question Text</th>
                            <th style="width: 150px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>';

                while ($row = $result->fetch_assoc()) {
                    echo '<tr>
                        <td>#' . $row['id'] . '</td>
                        <td>' . htmlspecialchars($row['questions']) . '</td>
                        <td>
                            <form method="post" action="" style="display:inline;">
                                <input type="hidden" name="delete_id" value="' . $row['id'] . '">
                                <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm(\'Are you sure you want to delete this question?\')">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            </form>
                        </td>
                    </tr>';
                }

                echo '</tbody></table>';
            } else {
                echo '<div class="alert alert-light text-center border my-3">No questions found in database. Add a question below to get started.</div>';
            }
        }

        // Handle Add Question
        if (isset($_POST['new_question'])) {
            $newQuestion = trim($_POST['new_question']);
            if (!empty($newQuestion)) {
                $stmt = $conn->prepare("INSERT INTO questions (questions) VALUES (?)");
                $stmt->bind_param("s", $newQuestion);
                if ($stmt->execute()) {
                    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                        Question added successfully!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>';
                } else {
                    echo '<div class="alert alert-danger">Error adding question: ' . htmlspecialchars($conn->error) . '</div>';
                }
                $stmt->close();
            }
        }

        // Handle Delete Question
        if (isset($_POST['delete_id'])) {
            $deleteId = intval($_POST['delete_id']);
            $stmt = $conn->prepare("DELETE FROM questions WHERE id = ?");
            $stmt->bind_param("i", $deleteId);
            if ($stmt->execute()) {
                echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                    Question deleted successfully!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>';
            }
            $stmt->close();
        }

        // Display Questions
        showQuestions($conn);
        ?>

        <hr class="my-4">

        <!-- Form to Add a New Question -->
        <form method="post" action="" class="row g-3">
            <div class="col-md-9">
                <label for="new_question" class="form-label fw-bold">Add New Survey Question:</label>
                <input type="text" class="form-control" name="new_question" id="new_question" placeholder="e.g. Clarity of explanation and domain expertise?" required>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100 py-2 fw-medium">Add Question</button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
