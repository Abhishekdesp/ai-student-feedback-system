<?php
session_start();
require_once "_dbconfig.php";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

$conn = getGlobalDbConnection("student");

if (!$conn || $conn->connect_error) {
    die("Connection failed to student database.");
}

// Add a column 'imported' to the 'student' table if it doesn't exist
@$conn->query("ALTER TABLE student ADD COLUMN imported BOOLEAN DEFAULT FALSE");

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_FILES["file"]) && $_FILES["file"]["error"] == UPLOAD_ERR_OK) {
        $file_tmp = $_FILES["file"]["tmp_name"];
        $year = isset($_POST['year']) ? $conn->real_escape_string($_POST['year']) : 'First';

        if (($handle = fopen($file_tmp, "r")) !== FALSE) {
            // Read header row
            $header = fgetcsv($handle, 1000, ",");
            
            $imported_count = 0;
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (count($data) >= 2) {
                    $sname = trim($data[0]);
                    $pass = trim($data[1]);

                    if (!empty($sname) && !empty($pass)) {
                        $stmt = $conn->prepare("INSERT INTO student (sname, year, password, imported) VALUES (?, ?, ?, TRUE) ON DUPLICATE KEY UPDATE year=?, password=?, imported=TRUE");
                        $stmt->bind_param("sssss", $sname, $year, $pass, $year, $pass);
                        $stmt->execute();
                        $stmt->close();
                        $imported_count++;
                    }
                }
            }
            fclose($handle);

            $message = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                🎉 Successfully imported <strong>' . $imported_count . '</strong> students for ' . htmlspecialchars($year) . ' Year!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
        } else {
            $message = '<div class="alert alert-danger">Error reading CSV file.</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Student Roster - Student Feedback System</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
            padding: 36px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            max-width: 600px;
            margin: 50px auto;
        }
    </style>
</head>
<body>

<div style="position: absolute; top: 15px; left: 15px;">
  <a class="btn btn-outline-secondary btn-sm" href="sconnect.php" title="Back">&larr; Back to Student Import</a>
</div>

<div class="container container-box">
    <h4 class="fw-bold text-dark text-center mb-2">Import Student CSV Roster</h4>
    <p class="text-muted small text-center mb-4">Upload a CSV file containing Student Names and Passwords.</p>

    <?php echo $message; ?>

    <form method="post" action="import.php" enctype="multipart/form-data" class="row g-3">
        <div class="col-12">
            <label for="year" class="form-label fw-medium small">Academic Year</label>
            <select name="year" id="year" class="form-select" required>
                <option value="First">First Year</option>
                <option value="Second">Second Year</option>
                <option value="Third" selected>Third Year</option>
            </select>
        </div>

        <div class="col-12">
            <label for="file" class="form-label fw-medium small">CSV File</label>
            <input type="file" name="file" id="file" class="form-control" accept=".csv" required>
            <div class="form-text small">CSV Format: <code>StudentName, Password</code></div>
        </div>

        <div class="col-12 mt-4">
            <button type="submit" class="btn btn-primary w-100 py-2 fw-medium">Upload & Import Roster</button>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>