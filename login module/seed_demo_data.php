<?php
session_start();
require_once "ai_sentiment_engine.php";

// Ensure user is logged in as admin/teacher to trigger seeder
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['auto'])) {
    $host = "127.0.0.1";
    $username = "root";
    $password = "";

    // Connect to MySQL server
    $conn = new mysqli($host, $username, $password);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // 1. Databases setup
    $conn->query("CREATE DATABASE IF NOT EXISTS `admin`");
    $conn->query("CREATE DATABASE IF NOT EXISTS `faculty`");
    $conn->query("CREATE DATABASE IF NOT EXISTS `student`");
    $conn->query("CREATE DATABASE IF NOT EXISTS `questions`");
    $conn->query("CREATE DATABASE IF NOT EXISTS `responses`");

    // 2. Admin database setup
    $conn->select_db("admin");
    $conn->query("CREATE TABLE IF NOT EXISTS `admin` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(255) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL
    )");
    $conn->query("INSERT INTO `admin` (`username`, `password`) VALUES ('admin', 'admin123') ON DUPLICATE KEY UPDATE `username`=`username`");

    // 3. Faculty database setup
    $conn->select_db("faculty");
    $conn->query("CREATE TABLE IF NOT EXISTS `faculty` (
        `id` INT PRIMARY KEY AUTO_INCREMENT,
        `name` VARCHAR(255) NOT NULL,
        `designation` VARCHAR(255) NOT NULL,
        `email` VARCHAR(255) NOT NULL,
        `scheme` VARCHAR(255) NOT NULL,
        `semester` VARCHAR(255) NOT NULL,
        `mobile` VARCHAR(20) NOT NULL,
        `year` VARCHAR(50) NOT NULL,
        `subject` VARCHAR(100) NOT NULL UNIQUE,
        `status` INT DEFAULT 1
    )");

    $sample_faculty = [
        [1, 'Dr. Rajesh Sharma', 'Professor & HOD', 'rajesh.sharma@college.edu', 'K-Scheme', '5', '9876543210', 'Third', 'AJP', 1],
        [2, 'Prof. Anita Roy', 'Associate Professor', 'anita.roy@college.edu', 'K-Scheme', '5', '9876543211', 'Third', 'WT', 1],
        [3, 'Dr. Vikram Mehta', 'Assistant Professor', 'vikram.mehta@college.edu', 'I-Scheme', '3', '9876543212', 'Second', 'DBMS', 1],
        [4, 'Prof. Neha Gupta', 'Assistant Professor', 'neha.gupta@college.edu', 'K-Scheme', '1', '9876543213', 'First', 'OOP', 1]
    ];

    foreach ($sample_faculty as $f) {
        $stmt = $conn->prepare("INSERT INTO `faculty` (`id`, `name`, `designation`, `email`, `scheme`, `semester`, `mobile`, `year`, `subject`, `status`) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) 
                                ON DUPLICATE KEY UPDATE `name`=?, `designation`=?, `email`=?, `status`=?");
        if ($stmt) {
            $stmt->bind_param("issssssssisssi", $f[0], $f[1], $f[2], $f[3], $f[4], $f[5], $f[6], $f[7], $f[8], $f[9], $f[1], $f[2], $f[3], $f[9]);
            $stmt->execute();
            $stmt->close();
        }
    }

    // 4. Questions database setup
    $conn->select_db("questions");
    $conn->query("CREATE TABLE IF NOT EXISTS `questions` (
        `id` INT PRIMARY KEY AUTO_INCREMENT,
        `questions` VARCHAR(255) NOT NULL
    )");

    $sample_questions = [
        "Punctuality and regularity in taking lectures and practicals?",
        "Clarity of explanation and domain expertise?",
        "Accessibility and willingness to assist students outside class hours?",
        "Fairness and transparency in evaluation and internal assessments?",
        "Use of interactive teaching aids, slides, and real-world examples?"
    ];

    $q_cnt = $conn->query("SELECT COUNT(*) AS cnt FROM `questions`")->fetch_assoc()['cnt'];
    if ($q_cnt == 0) {
        foreach ($sample_questions as $q) {
            $stmt = $conn->prepare("INSERT INTO `questions` (`questions`) VALUES (?)");
            $stmt->bind_param("s", $q);
            $stmt->execute();
            $stmt->close();
        }
    }

    // 5. Student database setup
    $conn->select_db("student");
    $conn->query("CREATE TABLE IF NOT EXISTS `student` (
        `id` INT PRIMARY KEY AUTO_INCREMENT,
        `sname` VARCHAR(255) NOT NULL,
        `year` VARCHAR(50) NOT NULL,
        `password` VARCHAR(255) NOT NULL,
        `imported` BOOLEAN DEFAULT FALSE,
        `AJP_submitted` INT DEFAULT 0,
        `WT_submitted` INT DEFAULT 0,
        `DBMS_submitted` INT DEFAULT 0,
        `OOP_submitted` INT DEFAULT 0
    )");

    $sample_students = [
        [1, 'Aarav', 'Third', '746Hb67V', 1],
        [2, 'Ananya', 'Second', '4qRvyxj9', 1],
        [3, 'Kabir', 'Third', 'cRkrlBZc', 1],
        [4, 'Diya', 'First', 'MseOVfS0', 1],
        [5, 'Rohan', 'Third', 'VbEa5Thi', 1]
    ];

    foreach ($sample_students as $s) {
        $stmt = $conn->prepare("INSERT INTO `student` (`id`, `sname`, `year`, `password`, `imported`) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `sname`=?");
        $stmt->bind_param("isssis", $s[0], $s[1], $s[2], $s[3], $s[4], $s[1]);
        $stmt->execute();
        $stmt->close();
    }

    // 6. Responses database setup & data seeding
    $conn->select_db("responses");
    $subjects = ['ajp', 'wt', 'dbms', 'oop'];
    foreach ($subjects as $sub) {
        $table_name = "{$sub}_responses";
        $conn->query("CREATE TABLE IF NOT EXISTS `$table_name` (
            `id` INT PRIMARY KEY AUTO_INCREMENT,
            `Questions` VARCHAR(255) DEFAULT '',
            `excellent` INT DEFAULT 0,
            `very_good` INT DEFAULT 0,
            `good` INT DEFAULT 0,
            `poor` INT DEFAULT 0,
            `bad` INT DEFAULT 0,
            `Counter` INT DEFAULT 0
        )");

        for ($i = 0; $i < count($sample_questions); $i++) {
            $row_id = $i + 1;
            $q_title = $conn->real_escape_string($sample_questions[$i]);
            $ex = rand(15, 28);
            $vg = rand(10, 22);
            $g = rand(4, 12);
            $p = rand(1, 4);
            $b = rand(0, 2);
            $c = ($row_id == 1) ? ($ex + $vg + $g + $p + $b) : 0;

            $conn->query("INSERT INTO `$table_name` (`id`, `Questions`, `excellent`, `very_good`, `good`, `poor`, `bad`, `Counter`) 
                          VALUES ($row_id, '$q_title', $ex, $vg, $g, $p, $b, $c) 
                          ON DUPLICATE KEY UPDATE `Questions`='$q_title', `excellent`=$ex, `very_good`=$vg, `good`=$g, `poor`=$p, `bad`=$b, `Counter`=$c");
        }
    }

    // 7. Seed Qualitative Comments & AI Sentiments
    $conn->query("CREATE TABLE IF NOT EXISTS `feedback_comments` (
        `id` INT PRIMARY KEY AUTO_INCREMENT,
        `subject` VARCHAR(50) NOT NULL,
        `comment` TEXT NOT NULL,
        `sentiment` VARCHAR(20) NOT NULL,
        `sentiment_score` FLOAT DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $sample_comments = [
        'AJP' => [
            "Explains complex Java multithreading and AWT concepts with great real-world examples!",
            "Extremely punctual and always available to clarify lab doubts.",
            "Please share more solved practice code samples for university lab exams."
        ],
        'WT' => [
            "Very engaging lectures on Web Development, HTML5, and JavaScript!",
            "Could explain CSS Flexbox and Grid layouts slightly slower.",
            "Interactive teaching style helps us understand web concepts clearly."
        ],
        'DBMS' => [
            "Excellent SQL query demonstrations and ER diagram explanations.",
            "Superb guidance during database practical lab sessions!",
            "Great thorough explanations of relational algebra and normalization."
        ],
        'OOP' => [
            "Clear explanation of C++ pointers and class inheritance.",
            "Please provide more practice assignment questions before midterm tests.",
            "Very supportive teacher who clarifies every doubt patiently."
        ]
    ];

    $conn->query("TRUNCATE TABLE `feedback_comments`");

    foreach ($sample_comments as $sub_name => $comment_list) {
        foreach ($comment_list as $c_text) {
            $analysis = AISentimentEngine::analyzeSentiment($c_text);
            $sent = $analysis['sentiment'];
            $score = $analysis['score'];
            $esc_cmt = $conn->real_escape_string($c_text);
            $conn->query("INSERT INTO `feedback_comments` (`subject`, `comment`, `sentiment`, `sentiment_score`) VALUES ('$sub_name', '$esc_cmt', '$sent', $score)");
        }
    }

    $conn->close();
    $message = '<div class="alert alert-success alert-dismissible fade show" role="alert">
        🎉 <strong>Demo Data Successfully Seeded!</strong> All databases, student records, and feedback charts have been populated.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo Data Seeder - Student Feedback System</title>
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
        .main-card {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 36px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            max-width: 580px;
            margin: 60px auto;
        }
    </style>
</head>
<body>

<div style="position: absolute; top: 15px; left: 15px;">
  <a class="btn btn-outline-secondary btn-sm" href="Homepage.php" title="Back">&larr; Back</a>
</div>

<div class="container">
    <div class="main-card text-center">
        <h4 class="fw-bold text-dark mb-2">Demo Data Seeder</h4>
        <p class="text-muted small mb-4">Click below to populate the system with realistic sample data for faculty, survey questions, students, and ratings.</p>

        <?php echo $message; ?>

        <form method="post" action="seed_demo_data.php">
            <button type="submit" name="seed_now" value="1" class="btn btn-primary px-4 py-2 fw-medium">
                Seed Sample Data
            </button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
