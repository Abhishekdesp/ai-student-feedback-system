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
    $conn->query("CREATE TABLE IF NOT EXISTS `system_settings` (
        `setting_name` VARCHAR(100) PRIMARY KEY,
        `setting_value` TEXT NOT NULL
    )");
}

// Handle AI Settings Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_ai_settings'])) {
    $use_ext = isset($_POST['use_external_ai']) ? '1' : '0';
    $provider = isset($_POST['ai_provider']) ? $_POST['ai_provider'] : 'gemini';
    $api_key = isset($_POST['ai_api_key']) ? trim($_POST['ai_api_key']) : '';

    if ($conn && !$conn->connect_error) {
        $stmt1 = $conn->prepare("REPLACE INTO `system_settings` (`setting_name`, `setting_value`) VALUES ('use_external_ai', ?)");
        $stmt1->bind_param("s", $use_ext);
        $stmt1->execute();
        $stmt1->close();

        $stmt2 = $conn->prepare("REPLACE INTO `system_settings` (`setting_name`, `setting_value`) VALUES ('ai_provider', ?)");
        $stmt2->bind_param("s", $provider);
        $stmt2->execute();
        $stmt2->close();

        $stmt3 = $conn->prepare("REPLACE INTO `system_settings` (`setting_name`, `setting_value`) VALUES ('ai_api_key', ?)");
        $stmt3->bind_param("s", $api_key);
        $stmt3->execute();
        $stmt3->close();
    }

    $_SESSION['use_external_ai'] = ($use_ext === '1');
    $_SESSION['ai_provider'] = $provider;
    $_SESSION['ai_api_key'] = $api_key;

    AISentimentEngine::setExternalAiConfig(($use_ext === '1'), $provider, $api_key);

    $message = '<div class="alert alert-success alert-dismissible fade show" role="alert">
        ⚙️ <strong>AI Settings Saved Successfully!</strong> Your AI sentiment configuration has been updated.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>';
}

// Load Current Settings
$curr_use_ext = false;
$curr_provider = 'gemini';
$curr_api_key = '';

if ($conn && !$conn->connect_error) {
    $res = $conn->query("SELECT * FROM `system_settings`");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            if ($row['setting_name'] === 'use_external_ai') $curr_use_ext = ($row['setting_value'] === '1');
            if ($row['setting_name'] === 'ai_provider') $curr_provider = $row['setting_value'];
            if ($row['setting_name'] === 'ai_api_key') $curr_api_key = $row['setting_value'];
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
    <a class="navbar-brand d-flex align-items-center gap-2" href="Homepage.php">
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
        <li class="nav-item"><a class="nav-link active fw-bold text-primary" href="settings.php"><i class="bi bi-gear-fill me-1"></i>Settings</a></li>
        <li class="nav-item"><a class="nav-link text-warning" href="seed_demo_data.php"><i class="bi bi-database-fill-gear me-1"></i>Demo Data</a></li>
      </ul>
      <div class="d-flex align-items-center gap-3">
        <span class="text-white-50 small">Admin Account</span>
        <button class="btn btn-outline-light btn-sm px-3" onclick="window.location.href='logout.php'">Logout</button>
      </div>
    </div>
  </div>
</nav>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1"><i class="bi bi-sliders text-primary me-2"></i>System Settings</h4>
            <p class="text-muted small mb-0">Manage system parameters, AI integration, and administrative preferences</p>
        </div>
        <a class="btn btn-outline-secondary btn-sm" href="Homepage.php">&larr; Back to Dashboard</a>
    </div>

    <?php echo $message; ?>

    <div class="row g-4">
        <!-- LEFT SIDEBAR -->
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
                
                <!-- TAB 1: AI SENTIMENT ENGINE (DEDICATED OPTION) -->
                <div class="tab-pane fade show active" id="tab-ai" role="tabpanel">
                    <div class="settings-card">
                        <div class="border-bottom pb-3 mb-4">
                            <h5 class="fw-bold text-dark mb-1"><i class="bi bi-robot text-primary me-2"></i>AI Sentiment Analysis & Summarization</h5>
                            <p class="text-muted small mb-0">Configure Cloud AI model integrations (Gemini / OpenAI) or use the offline NLP Lexicon Engine.</p>
                        </div>

                        <form method="post" action="settings.php">
                            <input type="hidden" name="save_ai_settings" value="1">

                            <div class="form-check form-switch mb-4 p-3 bg-light rounded-3 border">
                                <input class="form-check-input ms-0 me-3" type="checkbox" role="switch" id="use_external_ai" name="use_external_ai" <?php echo $curr_use_ext ? 'checked' : ''; ?>>
                                <div>
                                    <label class="form-check-label fw-bold text-dark" for="use_external_ai">Enable External Cloud AI API Calls</label>
                                    <div class="form-text small mb-0">When disabled, the system automatically uses the zero-cost offline NLP Lexicon Engine.</div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="ai_provider" class="form-label text-dark fw-medium small">Select AI Provider</label>
                                <select class="form-select" name="ai_provider" id="ai_provider">
                                    <option value="gemini" <?php echo ($curr_provider === 'gemini') ? 'selected' : ''; ?>>Google Gemini (1.5 Flash API)</option>
                                    <option value="openai" <?php echo ($curr_provider === 'openai') ? 'selected' : ''; ?>>OpenAI (GPT-3.5 Turbo API)</option>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label for="ai_api_key" class="form-label text-dark fw-medium small">API Key</label>
                                <input type="password" class="form-control" name="ai_api_key" id="ai_api_key" value="<?php echo htmlspecialchars($curr_api_key); ?>" placeholder="Enter your Gemini or OpenAI API Key...">
                                <div class="form-text small">Stored securely in admin DB. If offline or empty key, falls back to offline engine automatically.</div>
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
                            <li class="list-group-item d-flex justify-content-between"><span>Database Host:</span> <strong>127.0.0.1 (MySQL)</strong></li>
                            <li class="list-group-item d-flex justify-content-between"><span>Application Module:</span> <strong>Student Feedback System v2.0</strong></li>
                        </ul>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
