<?php
session_start();

// Check if the student is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$alertMessage = "";

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['subjectSelect'])) {
        $selectedSubject = $_POST['subjectSelect'];
        $username = $_SESSION['username'];
        
        $conn_student = new mysqli("127.0.0.1", "root", "", "student");
        if (!$conn_student->connect_error) {
            $check_feedback_query = "SELECT {$selectedSubject}_submitted FROM student WHERE sname = '$username'";
            $check_feedback_result = $conn_student->query($check_feedback_query);

            if ($check_feedback_result && $check_feedback_result->num_rows > 0) {
                $row = $check_feedback_result->fetch_assoc();
                $feedback_submitted = $row["{$selectedSubject}_submitted"];
                
                if ($feedback_submitted == 1) {
                    $alertMessage = '<div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
                        Feedback for <strong>' . htmlspecialchars($selectedSubject) . '</strong> has already been submitted.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>';
                } else {
                    $_SESSION['selected_subject'] = $selectedSubject;
                    header("Location: shomepage.php");
                    exit();
                }
            } else {
                $alertMessage = '<div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                    Error checking feedback status. Please try again.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>';
            }
            $conn_student->close();
        }
    }
}

// Retrieve student session details
$studentName = $_SESSION['username'];
$studentYear = isset($_SESSION['year']) ? $_SESSION['year'] : 'First';

// Fetch available faculty and submission status
$conn_faculty = new mysqli("127.0.0.1", "root", "", "faculty");
$faculty_cards = [];

if (!$conn_faculty->connect_error) {
    $stmt = $conn_faculty->prepare("SELECT id, name, designation, subject, status FROM faculty WHERE year = ?");
    if ($stmt) {
        $stmt->bind_param("s", $studentYear);
        $stmt->execute();
        $res = $stmt->get_result();
        
        $conn_st = new mysqli("127.0.0.1", "root", "", "student");
        while ($row = $res->fetch_assoc()) {
            $sub = $row['subject'];
            $is_submitted = 0;
            if ($conn_st && !$conn_st->connect_error) {
                $c_res = $conn_st->query("SELECT {$sub}_submitted FROM student WHERE sname = '$studentName'");
                if ($c_res && $c_row = $c_res->fetch_assoc()) {
                    $is_submitted = intval($c_row["{$sub}_submitted"]);
                }
            }
            $row['is_submitted'] = $is_submitted;
            $faculty_cards[] = $row;
        }
        if ($conn_st) { $conn_st->close(); }
        $stmt->close();
    }
    $conn_faculty->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Student Feedback System</title>
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
        .navbar {
            background-color: #1e293b !important;
            padding: 12px 24px;
        }
        .navbar-brand {
            font-weight: 600;
            color: #ffffff !important;
        }
        .user-info {
            color: #cbd5e1;
            font-size: 0.9rem;
        }
        .subject-card {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 24px;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .subject-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .subject-tag {
            background-color: #eff6ff;
            color: #1d4ed8;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.85rem;
            border: 1px solid #dbeafe;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg sticky-top">
  <div class="container-fluid d-flex justify-content-between align-items-center">
    <a class="navbar-brand d-flex align-items-center gap-2" href="dashboard.php">
      <i class="bi bi-mortarboard-fill text-primary"></i>
      Student Feedback Portal
    </a>
    
    <div class="d-flex align-items-center gap-3">
      <span class="user-info">
        Student: <strong><?php echo htmlspecialchars($studentName); ?></strong> (<?php echo htmlspecialchars($studentYear); ?> Year)
      </span>
      <a href="logout.php" class="btn btn-outline-light btn-sm px-3">
        Logout
      </a>
    </div>
  </div>
</nav>

<div class="container mt-4">
    <div class="mb-4">
        <h4 class="fw-bold text-dark mb-1">Available Feedback Surveys</h4>
        <p class="text-muted small">Select a subject below to submit your faculty evaluation</p>
    </div>

    <?php echo $alertMessage; ?>

    <div class="row g-4">
        <?php
        if (!empty($faculty_cards)) {
            foreach ($faculty_cards as $f) {
                $status = intval($f['status']);
                $submitted = intval($f['is_submitted']);
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="subject-card">
                        <div>
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <span class="subject-tag"><?php echo htmlspecialchars($f['subject']); ?></span>
                                <?php if ($submitted == 1): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                        Submitted
                                    </span>
                                <?php elseif ($status != 1): ?>
                                    <span class="badge bg-secondary-subtle text-secondary px-2 py-1">Inactive</span>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-warning-emphasis px-2 py-1">Pending</span>
                                <?php endif; ?>
                            </div>

                            <h5 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($f['name']); ?></h5>
                            <p class="text-muted small mb-3"><?php echo htmlspecialchars($f['designation']); ?></p>
                        </div>

                        <form action="dashboard.php" method="post" class="mt-3">
                            <input type="hidden" name="subjectSelect" value="<?php echo htmlspecialchars($f['subject']); ?>">
                            <?php if ($submitted == 1): ?>
                                <button type="button" class="btn btn-light text-muted w-100 border btn-sm" disabled>
                                    Feedback Submitted
                                </button>
                            <?php elseif ($status != 1): ?>
                                <button type="button" class="btn btn-light text-muted w-100 border btn-sm" disabled>
                                    Feedback Closed
                                </button>
                            <?php else: ?>
                                <button type="submit" class="btn btn-primary w-100 btn-sm fw-medium">
                                    Start Feedback Survey &rarr;
                                </button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
                <?php
            }
        } else {
            echo '<div class="col-12"><div class="alert alert-light border text-center text-muted">No subjects currently available for your academic year.</div></div>';
        }
        ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
