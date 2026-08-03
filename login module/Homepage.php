<?php
session_start();
require_once "ai_sentiment_engine.php";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

$host = "127.0.0.1";
$username = "root";
$password = "";

// Load settings from database if available
$conn_sett = @new mysqli($host, $username, $password, "admin");
if ($conn_sett && !$conn_sett->connect_error) {
    $res_s = $conn_sett->query("SELECT * FROM `system_settings`");
    if ($res_s) {
        $u_ext = false; $p_ext = 'gemini'; $k_ext = '';
        while ($r = $res_s->fetch_assoc()) {
            if ($r['setting_name'] === 'use_external_ai') $u_ext = ($r['setting_value'] === '1');
            if ($r['setting_name'] === 'ai_provider') $p_ext = $r['setting_value'];
            if ($r['setting_name'] === 'ai_api_key') $k_ext = $r['setting_value'];
        }
        AISentimentEngine::setExternalAiConfig($u_ext, $p_ext, $k_ext);
    }
    $conn_sett->close();
}

// Fetch Overview Metrics
$total_faculty = 0;
$total_students = 0;
$total_questions = 0;
$total_responses = 0;

$conn_faculty = @new mysqli($host, $username, $password, "faculty");
if ($conn_faculty && !$conn_faculty->connect_error) {
    $res = $conn_faculty->query("SELECT COUNT(*) AS cnt FROM faculty");
    if ($res) { $total_faculty = $res->fetch_assoc()['cnt']; }
    $conn_faculty->close();
}

$conn_student = @new mysqli($host, $username, $password, "student");
if ($conn_student && !$conn_student->connect_error) {
    $res = $conn_student->query("SELECT COUNT(*) AS cnt FROM student");
    if ($res) { $total_students = $res->fetch_assoc()['cnt']; }
    $conn_student->close();
}

$conn_q = @new mysqli($host, $username, $password, "questions");
if ($conn_q && !$conn_q->connect_error) {
    $res = $conn_q->query("SELECT COUNT(*) AS cnt FROM questions");
    if ($res) { $total_questions = $res->fetch_assoc()['cnt']; }
    $conn_q->close();
}

// Connect to responses DB to build subject reports
$conn_responses = @new mysqli($host, $username, $password, "responses");
$subject_data = [];

if ($conn_responses && !$conn_responses->connect_error) {
    $tables_res = $conn_responses->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'responses' AND table_name LIKE '%_responses'");
    
    if ($tables_res && $tables_res->num_rows > 0) {
        while ($t_row = $tables_res->fetch_assoc()) {
            $table_name = $t_row['table_name'];
            $subject_name = strtoupper(substr($table_name, 0, strpos($table_name, '_responses')));
            
            $faculty_name = "Unassigned";
            $faculty_status = 1;
            $conn_f = @new mysqli($host, $username, $password, "faculty");
            if ($conn_f && !$conn_f->connect_error) {
                $f_res = $conn_f->query("SELECT name, status FROM faculty WHERE subject = '$subject_name'");
                if ($f_res && $f_res->num_rows > 0) {
                    $f_data = $f_res->fetch_assoc();
                    $faculty_name = $f_data['name'];
                    $faculty_status = intval($f_data['status']);
                }
                $conn_f->close();
            }

            $agg_res = $conn_responses->query("SELECT 
                SUM(excellent) AS total_ex, 
                SUM(very_good) AS total_vg, 
                SUM(good) AS total_g, 
                SUM(poor) AS total_p, 
                SUM(bad) AS total_b,
                MAX(Counter) AS total_submissions
                FROM `$table_name`");

            if ($agg_res && $agg_row = $agg_res->fetch_assoc()) {
                $ex = intval($agg_row['total_ex']);
                $vg = intval($agg_row['total_vg']);
                $g = intval($agg_row['total_g']);
                $p = intval($agg_row['total_p']);
                $b = intval($agg_row['total_b']);
                $submissions = intval($agg_row['total_submissions']);
                $total_responses += $submissions;

                $total_votes = $ex + $vg + $g + $p + $b;
                $weighted_score = (5 * $ex) + (4 * $vg) + (3 * $g) + (2 * $p) + (1 * $b);
                $avg_rating = ($total_votes > 0) ? round($weighted_score / $total_votes, 2) : 0;

                // AI Sentiment Analysis for this subject
                $ai_summary = AISentimentEngine::getFacultyAiSummary($subject_name, $conn_responses);

                $subject_data[] = [
                    'table_name' => $table_name,
                    'subject' => $subject_name,
                    'faculty' => $faculty_name,
                    'status' => $faculty_status,
                    'excellent' => $ex,
                    'very_good' => $vg,
                    'good' => $g,
                    'poor' => $p,
                    'bad' => $b,
                    'submissions' => $submissions,
                    'avg_rating' => $avg_rating,
                    'ai' => $ai_summary
                ];
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard - Student Feedback System</title>
  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
      font-size: 1.15rem;
      color: #ffffff !important;
    }
    .nav-link {
      color: #cbd5e1 !important;
      font-weight: 500;
      font-size: 0.95rem;
    }
    .nav-link:hover, .nav-link.active {
      color: #ffffff !important;
    }
    .stat-card {
      background-color: #ffffff;
      border: 1px solid #e2e8f0;
      border-radius: 10px;
      padding: 20px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    .stat-icon {
      width: 44px;
      height: 44px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.25rem;
    }
    .analytics-card {
      background-color: #ffffff;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      padding: 24px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.05);
      height: 100%;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }
    .rating-badge {
      background-color: #fef3c7;
      color: #92400e;
      font-weight: 600;
      padding: 5px 12px;
      border-radius: 6px;
      font-size: 0.88rem;
      border: 1px solid #fde68a;
    }
    .chart-container {
      position: relative;
      height: 200px;
      width: 100%;
      margin: 15px 0;
    }
    .ai-box {
      background-color: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      padding: 14px;
      margin-top: 14px;
    }
  </style>
</head>
<body>

<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-dark sticky-top">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center gap-2" href="#">
      <i class="bi bi-mortarboard-fill text-primary"></i>
      Student Feedback System
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="adminNavbar">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3">
        <li class="nav-item"><a class="nav-link active" href="Homepage.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="questions.php">Questions</a></li>
        <li class="nav-item"><a class="nav-link" href="add_faculty.php">Faculty Directory</a></li>
        <li class="nav-item"><a class="nav-link" href="sconnect.php">Student Import</a></li>
        <li class="nav-item"><a class="nav-link" href="Sample.php">Status Control</a></li>
        <li class="nav-item"><a class="nav-link text-info" href="settings.php"><i class="bi bi-gear-fill me-1"></i>Settings</a></li>
        <li class="nav-item"><a class="nav-link text-warning" href="seed_demo_data.php"><i class="bi bi-database-fill-gear me-1"></i>Demo Data</a></li>
      </ul>
      <div class="d-flex align-items-center gap-3">
        <span class="text-white-50 small">Admin Account</span>
        <button class="btn btn-outline-light btn-sm px-3" onclick="logout()">Logout</button>
      </div>
    </div>
  </div>
</nav>

<div class="container mt-4">
  <!-- Stats Header Row -->
  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="stat-card d-flex align-items-center justify-content-between">
        <div>
          <p class="text-muted small mb-1">Total Faculty</p>
          <h3 class="fw-bold mb-0 text-dark"><?php echo $total_faculty; ?></h3>
        </div>
        <div class="stat-icon bg-primary bg-opacity-10 text-primary">
          <i class="bi bi-person-badge"></i>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="stat-card d-flex align-items-center justify-content-between">
        <div>
          <p class="text-muted small mb-1">Enrolled Students</p>
          <h3 class="fw-bold mb-0 text-dark"><?php echo $total_students; ?></h3>
        </div>
        <div class="stat-icon bg-success bg-opacity-10 text-success">
          <i class="bi bi-people"></i>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="stat-card d-flex align-items-center justify-content-between">
        <div>
          <p class="text-muted small mb-1">Survey Questions</p>
          <h3 class="fw-bold mb-0 text-dark"><?php echo $total_questions; ?></h3>
        </div>
        <div class="stat-icon bg-info bg-opacity-10 text-info">
          <i class="bi bi-card-checklist"></i>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="stat-card d-flex align-items-center justify-content-between">
        <div>
          <p class="text-muted small mb-1">Total Submissions</p>
          <h3 class="fw-bold mb-0 text-dark"><?php echo $total_responses; ?></h3>
        </div>
        <div class="stat-icon bg-warning bg-opacity-10 text-warning">
          <i class="bi bi-bar-chart"></i>
        </div>
      </div>
    </div>
  </div>

  <!-- Section Title -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold text-dark mb-0">Faculty Feedback Overview & AI Insights</h5>
    <span class="text-muted small">Real-time ratings breakdown & AI sentiment analysis</span>
  </div>

  <!-- Analytics Cards Grid -->
  <div class="row g-4">
    <?php
    if (!empty($subject_data)) {
        foreach ($subject_data as $index => $s) {
            $canvas_id = "chart_" . $index;
            $modal_id = "modal_cmt_" . $index;
            $ai = $s['ai'];
            ?>
            <div class="col-lg-6">
              <div class="analytics-card">
                <div>
                  <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                      <h5 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($s['subject']); ?> — <?php echo htmlspecialchars($s['faculty']); ?></h5>
                      <p class="text-muted small mb-0">Table: <code><?php echo htmlspecialchars($s['table_name']); ?></code> | Submissions: <strong><?php echo $s['submissions']; ?></strong></p>
                    </div>
                    <span class="rating-badge">
                      ★ <?php echo ($s['avg_rating'] > 0) ? $s['avg_rating'] . ' / 5.0' : 'No Data'; ?>
                    </span>
                  </div>

                  <!-- Chart Canvas -->
                  <div class="chart-container">
                    <canvas id="<?php echo $canvas_id; ?>"></canvas>
                  </div>

                  <!-- AI Sentiment Summary Container -->
                  <?php if ($ai && $ai['total_comments'] > 0): ?>
                    <div class="ai-box">
                      <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-bold text-dark small"><i class="bi bi-robot text-primary me-1"></i>AI Sentiment & Insights</span>
                        <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" data-bs-toggle="modal" data-bs-target="#<?php echo $modal_id; ?>">
                          View Comments (<?php echo $ai['total_comments']; ?>) &rarr;
                        </button>
                      </div>

                      <!-- Sentiment Progress Bar -->
                      <div class="progress mb-2" style="height: 8px;">
                        <div class="progress-bar bg-success" style="width: <?php echo $ai['pos_pct']; ?>%" title="Positive: <?php echo $ai['pos_pct']; ?>%"></div>
                        <div class="progress-bar bg-info" style="width: <?php echo $ai['neu_pct']; ?>%" title="Neutral: <?php echo $ai['neu_pct']; ?>%"></div>
                        <div class="progress-bar bg-warning" style="width: <?php echo $ai['con_pct']; ?>%" title="Constructive: <?php echo $ai['con_pct']; ?>%"></div>
                      </div>
                      <div class="d-flex justify-content-between small text-muted mb-2" style="font-size: 0.75rem;">
                        <span>🟢 Positive: <?php echo $ai['pos_pct']; ?>%</span>
                        <span>🔵 Neutral: <?php echo $ai['neu_pct']; ?>%</span>
                        <span>🟠 Constructive: <?php echo $ai['con_pct']; ?>%</span>
                      </div>

                      <!-- AI Executive Summary Bullets -->
                      <div class="small text-dark">
                        <strong>Key Strengths:</strong>
                        <ul class="mb-1 ps-3 text-muted">
                          <?php foreach ($ai['strengths'] as $st): ?>
                            <li><?php echo htmlspecialchars($st); ?></li>
                          <?php endforeach; ?>
                        </ul>

                        <strong>Areas for Growth:</strong>
                        <ul class="mb-0 ps-3 text-muted">
                          <?php foreach ($ai['growths'] as $gr): ?>
                            <li><?php echo htmlspecialchars($gr); ?></li>
                          <?php endforeach; ?>
                        </ul>
                      </div>
                    </div>
                  <?php else: ?>
                    <div class="ai-box text-center text-muted small py-2">
                      <i class="bi bi-chat-left-text me-1"></i>No student qualitative comments submitted yet.
                    </div>
                  <?php endif; ?>
                </div>

                <div class="d-flex justify-content-between align-items-center pt-3 border-top border-light mt-3">
                  <form class="importForm" action="file_import_handler.php" method="post" enctype="multipart/form-data">
                    <input type="file" name="file" class="file d-none">
                    <button type="button" class="btn btn-outline-primary btn-sm importButton">
                      <i class="bi bi-upload me-1"></i>Import File
                    </button>
                    <input type="hidden" name="table" value="<?php echo htmlspecialchars($s['table_name']); ?>">
                    <input type="hidden" name="faculty_name" value="<?php echo htmlspecialchars($s['faculty']); ?>">
                    <input type="hidden" name="subject_name" value="<?php echo htmlspecialchars($s['subject']); ?>">
                  </form>

                  <form class="clearForm" action="clear_database_handler.php" method="post">
                    <input type="hidden" name="table" value="<?php echo htmlspecialchars($s['table_name']); ?>">
                    <input type="hidden" name="subject_name" value="<?php echo htmlspecialchars($s['subject']); ?>">
                    <button type="button" class="btn btn-outline-danger btn-sm clearButton">
                      <i class="bi bi-trash me-1"></i>Clear Data
                    </button>
                  </form>
                </div>
              </div>
            </div>

            <!-- Modal for Anonymous Comments -->
            <?php if ($ai && !empty($ai['comments'])): ?>
              <div class="modal fade" id="<?php echo $modal_id; ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title fw-bold"><i class="bi bi-chat-left-quote me-2"></i>Anonymous Student Feedback — <?php echo htmlspecialchars($s['subject']); ?></h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      <div class="list-group">
                        <?php foreach ($ai['comments'] as $c): 
                            $badge = ($c['sentiment'] === 'Positive') ? 'bg-success' : (($c['sentiment'] === 'Constructive') ? 'bg-warning text-dark' : 'bg-info');
                          ?>
                          <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-start py-3">
                            <div class="ms-2 me-auto">
                              <p class="mb-1 text-dark">"<?php echo htmlspecialchars($c['comment']); ?>"</p>
                              <small class="text-muted"><?php echo htmlspecialchars($c['created_at']); ?></small>
                            </div>
                            <span class="badge <?php echo $badge; ?> rounded-pill ms-2"><?php echo htmlspecialchars($c['sentiment']); ?></span>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                    </div>
                  </div>
                </div>
              </div>
            <?php endif; ?>

            <script>
              document.addEventListener("DOMContentLoaded", function() {
                const ctx = document.getElementById('<?php echo $canvas_id; ?>').getContext('2d');
                new Chart(ctx, {
                  type: 'bar',
                  data: {
                    labels: ['Excellent', 'Very Good', 'Good', 'Poor', 'Bad'],
                    datasets: [{
                      label: 'Responses',
                      data: [<?php echo "{$s['excellent']}, {$s['very_good']}, {$s['good']}, {$s['poor']}, {$s['bad']}"; ?>],
                      backgroundColor: [
                        '#2563eb',
                        '#3b82f6',
                        '#60a5fa',
                        '#f59e0b',
                        '#ef4444'
                      ],
                      borderRadius: 4
                    }]
                  },
                  options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                      legend: { display: false }
                    },
                    scales: {
                      y: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f9' },
                        ticks: { color: '#64748b' }
                      },
                      x: {
                        grid: { display: false },
                        ticks: { color: '#64748b' }
                      }
                    }
                  }
                });
              });
            </script>
            <?php
        }
    } else {
        echo '<div class="col-12"><div class="alert alert-light border text-center">No feedback data available. Click <a href="seed_demo_data.php" class="fw-bold">Demo Data</a> to populate initial sample data.</div></div>';
    }
    ?>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  function logout() {
    window.location.href = 'logout.php';
  }

  $(document).ready(function() {
    $('.importButton').click(function() {
      var $form = $(this).closest('form');
      var subjectName = $form.find('input[name="subject_name"]').val();
      var facultyName = $form.find('input[name="faculty_name"]').val();
      $form.data('subjectName', subjectName);
      $form.data('facultyName', facultyName);
      $form.find('.file').click();
    });

    $('.file').change(function() {
      var $form = $(this).closest('form');
      var formData = new FormData($form[0]);
      formData.append('subject_name', $form.data('subjectName'));
      formData.append('faculty_name', $form.data('facultyName'));
      $.ajax({
          url: 'file_import_handler.php',
          type: 'POST',
          data: formData,
          contentType: false,
          processData: false,
          success: function(response) {
              alert(response);
              location.reload();
          },
          error: function(xhr, status, error) {
              alert("Error importing file: " + xhr.responseText);
          }
      });
    });

    $('.clearButton').click(function() {
      if (confirm("Are you sure you want to clear feedback data for this subject?")) {
        var $form = $(this).closest('form');
        var formData = new FormData($form[0]);
        $.ajax({
            url: 'clear_database_handler.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                alert(response);
                location.reload();
            },
            error: function(xhr, status, error) {
                alert("Error clearing data: " + xhr.responseText);
            }
        });
      }
    });
  });
</script>
</body>
</html>
