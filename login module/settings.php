<?php
session_start();
require_once "ai_sentiment_engine.php";
require_once "_dbconfig.php";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

$message = "";

// Database connection for settings persistence
$conn = getGlobalDbConnection("admin");
if ($conn && !$conn->connect_error) {
    @$conn->query("CREATE TABLE IF NOT EXISTS `system_settings` (
        `setting_name` VARCHAR(100) PRIMARY KEY,
        `setting_value` TEXT NOT NULL
    )");
}

// Handle AI Settings Toggle Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_ai_settings'])) {
    $use_ext = isset($_POST['use_external_ai']) ? '1' : '0';

    if ($conn && !$conn->connect_error) {
        $stmt1 = $conn->prepare("REPLACE INTO `system_settings` (`setting_name`, `setting_value`) VALUES ('use_external_ai', ?)");
        $stmt1->bind_param("s", $use_ext);
        $stmt1->execute();
        $stmt1->close();
    }

    $_SESSION['use_external_ai'] = ($use_ext === '1');
    AISentimentEngine::setExternalAiConfig(($use_ext === '1'), 'gemini', getenv('GEMINI_API_KEY'));

    $status_label = ($use_ext === '1') ? 'Enabled (Google Gemini 1.5 Flash)' : 'Disabled (Offline Lexicon Engine Active)';

    $message = '<div class="alert alert-success alert-dismissible fade show" role="alert">
        ⚙️ <strong>AI Response Toggle Updated!</strong> Cloud AI is now <strong>' . $status_label . '</strong>.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>';
}

// Load Current Settings
$curr_use_ext = true;

if ($conn && !$conn->connect_error) {
    $res = @$conn->query("SELECT * FROM `system_settings`");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            if ($row['setting_name'] === 'use_external_ai') $curr_use_ext = ($row['setting_value'] === '1');
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Settings - Student Feedback System</title>
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
    .settings-card {
      background-color: #ffffff;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      padding: 30px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    .sidebar-menu .list-group-item {
      border: 1px solid #e2e8f0;
      color: #475569;
      font-weight: 500;
      padding: 12px 18px;
      border-radius: 8px;
      margin-bottom: 8px;
      transition: all 0.2s ease;
      cursor: pointer;
    }
    .sidebar-menu .list-group-item.active {
      background-color: #2563eb;
      border-color: #2563eb;
      color: #ffffff;
    }
    .sidebar-menu .list-group-item:hover:not(.active) {
      background-color: #f1f5f9;
      color: #1e293b;
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
        <li class="nav-item"><a class="nav-link" href="Homepage.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="questions.php">Questions</a></li>
        <li class="nav-item"><a class="nav-link" href="add_faculty.php">Faculty Directory</a></li>
        <li class="nav-item"><a class="nav-link" href="sconnect.php">Student Import</a></li>
        <li class="nav-item"><a class="nav-link" href="Sample.php">Status Control</a></li>
        <li class="nav-item"><a class="nav-link text-info active" href="settings.php"><i class="bi bi-gear-fill me-1"></i>Settings</a></li>
        <li class="nav-item"><a class="nav-link text-warning" href="seed_demo_data.php"><i class="bi bi-database-fill-gear me-1"></i>Demo Data</a></li>
      </ul>
      <div class="d-flex align-items-center gap-3">
        <span class="text-white-50 small">Admin Account</span>
        <a class="btn btn-outline-light btn-sm px-3" href="logout.php">Logout</a>
      </div>
    </div>
  </div>
</nav>

<div class="container mt-4">
    <div class="row g-4">
        <!-- LEFT SIDEBAR MENU -->
        <div class="col-md-3">
            <div class="list-group sidebar-menu" id="settingsTab" role="tablist">
                <button class="list-group-item list-group-item-action active d-flex align-items-center gap-2" id="tab-ai-btn" data-bs-toggle="pill" data-bs-target="#tab-ai" type="button" role="tab">
                    <i class="bi bi-robot text-primary fs-5"></i> AI Sentiment Engine
                </button>
                <button class="list-group-item list-group-item-action d-flex align-items-center gap-2" id="tab-security-btn" data-bs-toggle="pill" data-bs-target="#tab-security" type="button" role="tab">
                    <i class="bi bi-shield-lock fs-5"></i> Security & Password
                </button>
                <button class="list-group-item list-group-item-action d-flex align-items-center gap-2" id="tab-system-btn" data-bs-toggle="pill" data-bs-target="#tab-system" type="button" role="tab">
                    <i class="bi bi-info-circle fs-5"></i> System Information
                </button>
            </div>
        </div>

        <!-- RIGHT MAIN CONTENT -->
        <div class="col-md-9">
            <div class="tab-content" id="settingsTabContent">
                
                <!-- TAB 1: AI SENTIMENT ENGINE (SINGLE CLEAN TOGGLE SWITCH) -->
                <div class="tab-pane fade show active" id="tab-ai" role="tabpanel">
                    <div class="settings-card">
                        <div class="border-bottom pb-3 mb-4">
                            <h5 class="fw-bold text-dark mb-1"><i class="bi bi-robot text-primary me-2"></i>AI Sentiment & Response Engine</h5>
                            <p class="text-muted small mb-0">Enable or disable Google Gemini 1.5 Flash Cloud AI response engine.</p>
                        </div>

                        <?php echo $message; ?>

                        <form method="post" action="settings.php">
                            <input type="hidden" name="save_ai_settings" value="1">

                            <div class="form-check form-switch mb-4 p-3 bg-light rounded-3 border">
                                <input class="form-check-input ms-0 me-3" style="width: 2.5em; height: 1.3em;" type="checkbox" role="switch" id="use_external_ai" name="use_external_ai" <?php echo $curr_use_ext ? 'checked' : ''; ?>>
                                <div>
                                    <label class="form-check-label fw-bold text-dark fs-6" for="use_external_ai">Enable Google Gemini 1.5 Flash AI Engine</label>
                                    <div class="form-text small mb-0 mt-1 text-muted">
                                        When <strong>enabled</strong>, feedback is processed using live Google Gemini AI.<br>
                                        When <strong>disabled</strong>, feedback is processed using the offline zero-cost Lexicon NLP Engine.
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary px-4 py-2 fw-medium shadow-sm">
                                <i class="bi bi-check-circle-fill me-1"></i> Save AI Settings
                            </button>
                        </form>
                    </div>
                </div>

                <!-- TAB 2: SECURITY & PASSWORD -->
                <div class="tab-pane fade" id="tab-security" role="tabpanel">
                    <div class="settings-card">
                        <div class="border-bottom pb-3 mb-4">
                            <h5 class="fw-bold text-dark mb-1"><i class="bi bi-shield-lock text-primary me-2"></i>Security & Credentials</h5>
                            <p class="text-muted small mb-0">Manage administrator login credentials and authentication parameters</p>
                        </div>
                        <div class="alert alert-light border">
                            <p class="mb-1 text-dark fw-medium">Default Administrator Credentials:</p>
                            <small class="text-muted">Username: <code>admin</code> | Password: <code>admin123</code></small>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: SYSTEM INFORMATION -->
                <div class="tab-pane fade" id="tab-system" role="tabpanel">
                    <div class="settings-card">
                        <div class="border-bottom pb-3 mb-4">
                            <h5 class="fw-bold text-dark mb-1"><i class="bi bi-info-circle text-primary me-2"></i>System Information</h5>
                            <p class="text-muted small mb-0">Environment specs and database configuration status</p>
                        </div>
                        <ul class="list-group list-group-flush small">
                            <li class="list-group-item d-flex justify-content-between"><span>PHP Version:</span> <strong><?php echo PHP_VERSION; ?></strong></li>
                            <li class="list-group-item d-flex justify-content-between"><span>AI Engine Provider:</span> <strong>Google Gemini 1.5 Flash</strong></li>
                            <li class="list-group-item d-flex justify-content-between"><span>Database Driver:</span> <strong>MySQL (TiDB Cloud SSL Enabled)</strong></li>
                        </ul>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
