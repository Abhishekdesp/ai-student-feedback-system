<?php
session_start();
require_once "ai_sentiment_engine.php";

// Check if the student is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$alertMessage = "";

// Check if the feedback form has been submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn_responses = new mysqli("127.0.0.1", "root", "", "responses");

    if ($conn_responses->connect_error) {
        die("Connection failed: " . $conn_responses->connect_error);
    }

    $subject = isset($_SESSION['selected_subject']) ? $_SESSION['selected_subject'] : '';

    if (!empty($subject)) {
        $username = $_SESSION['username'];
        $conn_student = new mysqli("127.0.0.1", "root", "", "student");
        $check_feedback_query = "SELECT {$subject}_submitted FROM student WHERE sname = '$username'";
        $check_feedback_result = $conn_student->query($check_feedback_query);

        if ($check_feedback_result && $check_feedback_result->num_rows > 0) {
            $row = $check_feedback_result->fetch_assoc();
            $feedback_submitted = $row["{$subject}_submitted"];

            if ($feedback_submitted == 1) {
                $alertMessage = '<div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
                    Feedback for <strong>' . htmlspecialchars($subject) . '</strong> has already been submitted.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>';
                $conn_responses->close();
                $conn_student->close();
            } else {
                $update_feedback_query = "UPDATE student SET {$subject}_submitted = 1 WHERE sname = '$username'";
                $update_feedback_result = $conn_student->query($update_feedback_query);

                if ($update_feedback_result && isset($_POST['response']) && is_array($_POST['response'])) {
                    foreach ($_POST['response'] as $question_id => $response) {
                        switch ($response) {
                            case 5: $column = 'excellent'; break;
                            case 4: $column = 'very_good'; break;
                            case 3: $column = 'good'; break;
                            case 2: $column = 'poor'; break;
                            case 1: $column = 'bad'; break;
                            default: continue 2;
                        }

                        $subject_responses_table = $subject . "_responses";
                        $update_query = "UPDATE $subject_responses_table SET $column = $column + 1 WHERE id = $question_id";
                        $conn_responses->query($update_query);
                    }

                    $subject_responses_table = "{$subject}_responses";
                    $update_counter_query = "UPDATE $subject_responses_table SET `Counter` = `Counter` + 1 WHERE id = 1";
                    $conn_responses->query($update_counter_query);

                    // Process Qualitative Student Comment & AI Sentiment
                    if (isset($_POST['user_comment']) && !empty(trim($_POST['user_comment']))) {
                        $user_cmt = trim($_POST['user_comment']);
                        $analysis = AISentimentEngine::analyzeSentiment($user_cmt);
                        $sent = $analysis['sentiment'];
                        $score = $analysis['score'];

                        $conn_responses->query("CREATE TABLE IF NOT EXISTS `feedback_comments` (
                            `id` INT PRIMARY KEY AUTO_INCREMENT,
                            `subject` VARCHAR(50) NOT NULL,
                            `comment` TEXT NOT NULL,
                            `sentiment` VARCHAR(20) NOT NULL,
                            `sentiment_score` FLOAT DEFAULT 0,
                            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                        )");

                        $esc_sub = $conn_responses->real_escape_string($subject);
                        $esc_cmt = $conn_responses->real_escape_string($user_cmt);
                        $conn_responses->query("INSERT INTO `feedback_comments` (`subject`, `comment`, `sentiment`, `sentiment_score`) VALUES ('$esc_sub', '$esc_cmt', '$sent', $score)");
                    }

                    $alertMessage = '<div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                        Thank you! Your feedback for <strong>' . htmlspecialchars($subject) . '</strong> has been submitted.
                    </div>';

                    echo '<script>
                        setTimeout(function() {
                            window.location.href = "dashboard.php";
                        }, 2000);
                     </script>';
                } else {
                    $alertMessage = '<div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                        Error submitting feedback. Please try again.
                    </div>';
                }
                $conn_responses->close();
                $conn_student->close();
            }
        }
    } else {
        $alertMessage = '<div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            No subject selected. Returning to dashboard...
        </div>';
    }
}

$studentName = $_SESSION['username'];
$selectedSubject = isset($_SESSION['selected_subject']) ? $_SESSION['selected_subject'] : 'Feedback';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Survey - <?php echo htmlspecialchars($selectedSubject); ?></title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            color: #1e293b;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            padding-bottom: 60px;
        }
        .survey-card {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 32px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            max-width: 800px;
            margin: 40px auto;
        }
        .question-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .options-group {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 12px;
        }
        .form-check-label {
            cursor: pointer;
            font-weight: 500;
        }
    </style>
</head>
<body>

<div style="position: absolute; top: 15px; left: 15px; z-index: 100;">
  <a class="btn btn-outline-secondary btn-sm" href="dashboard.php" title="Back">&larr; Back</a>
</div>

<div class="container">
    <div class="survey-card">
        <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
            <div>
                <h4 class="fw-bold text-dark mb-1">Feedback Survey Form</h4>
                <p class="text-muted small mb-0">Subject: <strong><?php echo htmlspecialchars($selectedSubject); ?></strong></p>
            </div>
            <span class="badge bg-primary px-3 py-2">Subject: <?php echo htmlspecialchars($selectedSubject); ?></span>
        </div>

        <!-- Progress Bar -->
        <div class="mb-4">
            <div class="d-flex justify-content-between text-muted small mb-1">
                <span>Survey Progress</span>
                <span id="progressText">0%</span>
            </div>
            <div class="progress" style="height: 6px;">
                <div class="progress-bar bg-success" id="progressBar" role="progressbar" style="width: 0%;"></div>
            </div>
        </div>

        <?php echo $alertMessage; ?>

        <form id="surveyForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" onsubmit="return validateForm()">
            <?php
            $conn_q = new mysqli("127.0.0.1", "root", "", "questions");

            if (!$conn_q->connect_error) {
                $sql_q = "SELECT * FROM questions";
                $res_q = $conn_q->query($sql_q);

                if ($res_q && $res_q->num_rows > 0) {
                    $q_index = 1;
                    $total_q_cnt = $res_q->num_rows;
                    while ($row = $res_q->fetch_assoc()) {
                        $qid = $row["id"];
                        $qtext = $row["questions"];
                        ?>
                        <div class="question-card">
                            <div class="fw-semibold text-dark mb-3">
                                <?php echo $q_index . ". " . htmlspecialchars($qtext); ?>
                            </div>

                            <div class="options-group">
                                <div class="form-check me-3">
                                    <input class="form-check-input" type="radio" name="response[<?php echo $qid; ?>]" id="opt_5_<?php echo $qid; ?>" value="5" onchange="updateProgress()" required>
                                    <label class="form-check-label" for="opt_5_<?php echo $qid; ?>">Excellent</label>
                                </div>
                                <div class="form-check me-3">
                                    <input class="form-check-input" type="radio" name="response[<?php echo $qid; ?>]" id="opt_4_<?php echo $qid; ?>" value="4" onchange="updateProgress()">
                                    <label class="form-check-label" for="opt_4_<?php echo $qid; ?>">Very Good</label>
                                </div>
                                <div class="form-check me-3">
                                    <input class="form-check-input" type="radio" name="response[<?php echo $qid; ?>]" id="opt_3_<?php echo $qid; ?>" value="3" onchange="updateProgress()">
                                    <label class="form-check-label" for="opt_3_<?php echo $qid; ?>">Good</label>
                                </div>
                                <div class="form-check me-3">
                                    <input class="form-check-input" type="radio" name="response[<?php echo $qid; ?>]" id="opt_2_<?php echo $qid; ?>" value="2" onchange="updateProgress()">
                                    <label class="form-check-label" for="opt_2_<?php echo $qid; ?>">Poor</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="response[<?php echo $qid; ?>]" id="opt_1_<?php echo $qid; ?>" value="1" onchange="updateProgress()">
                                    <label class="form-check-label" for="opt_1_<?php echo $qid; ?>">Bad</label>
                                </div>
                            </div>
                        </div>
                        <?php
                        $q_index++;
                    }
                } else {
                    echo '<div class="alert alert-light border text-center">No questions currently active.</div>';
                }
                $conn_q->close();
            }
            ?>

            <!-- Qualitative Comments Field -->
            <div class="card border-0 bg-light p-3 mb-4 rounded-3">
                <label for="user_comment" class="form-label fw-bold text-dark mb-1">
                    <i class="bi bi-chat-left-quote-fill text-primary me-1"></i>Additional Feedback & Suggestions (Optional)
                </label>
                <textarea class="form-control" name="user_comment" id="user_comment" rows="3" placeholder="Share your experience, specific teaching strengths, or constructive suggestions for this lecturer..."></textarea>
                <div class="form-text small">Your comment will be processed anonymously for AI sentiment analysis.</div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary px-5 fw-medium">
                    Submit Feedback
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const totalQuestions = <?php echo isset($total_q_cnt) ? $total_q_cnt : 0; ?>;

    function updateProgress() {
        if (totalQuestions === 0) return;
        const cards = document.querySelectorAll('.question-card');
        let answered = 0;
        cards.forEach(card => {
            if (card.querySelector('input[type="radio"]:checked')) {
                answered++;
            }
        });
        const pct = Math.round((answered / totalQuestions) * 100);
        document.getElementById('progressBar').style.width = pct + '%';
        document.getElementById('progressText').innerText = pct + '% Completed';
    }

    function validateForm() {
        const cards = document.querySelectorAll('.question-card');
        for (let card of cards) {
            if (!card.querySelector('input[type="radio"]:checked')) {
                alert("Please answer all questions before submitting.");
                card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return false;
            }
        }
        return true;
    }
</script>
</body>
</html>
