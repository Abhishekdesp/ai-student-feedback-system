<?php
session_start();
require_once "_dbconfig.php";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

$conn = getGlobalDbConnection("faculty");

if (!$conn || $conn->connect_error) {
    die("Connection failed to faculty database.");
}

// Handle toggle button click and update database
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id']) && isset($_GET['state'])) {
    $id = intval($_GET['id']);
    $state = intval($_GET['state']);

    $sql = "UPDATE faculty SET status = $state WHERE id = $id";

    if ($conn->query($sql) === TRUE) {
        echo "Database updated successfully";
    } else {
        echo "Error updating database: " . $conn->error;
    }
    exit;
}

// Fetch faculty data from the database
$sql = "SELECT id, name, status FROM faculty ORDER BY id ASC";
$result = $conn->query($sql);

$facultyData = array();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $facultyData[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Status Control - Student Feedback System</title>
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
            padding: 30px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            margin-top: 40px;
        }
    </style>
</head>
<body>

<div style="position: absolute; top: 15px; left: 15px;">
  <a class="btn btn-outline-secondary btn-sm" href="Homepage.php" title="Back">&larr; Back</a>
</div>

<div class="container container-box">
    <h3 class="text-center fw-bold mb-4">Faculty Survey Status Control</h3>
    <p class="text-center text-muted small mb-4">Enable or disable student feedback surveys for individual faculty members in real-time.</p>

    <div class="table-responsive border rounded-2">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Faculty Name</th>
                    <th style="width: 150px;">Survey Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($facultyData)): ?>
                    <?php foreach ($facultyData as $faculty): ?>
                        <tr>
                            <td>#<?php echo $faculty['id']; ?></td>
                            <td class="fw-medium"><?php echo htmlspecialchars($faculty['name']); ?></td>
                            <td>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="toggle_<?php echo $faculty['id']; ?>" <?php echo $faculty['status'] ? 'checked' : ''; ?> onclick="updateDatabase(<?php echo $faculty['id']; ?>)">
                                    <label class="form-check-label ms-1" id="lbl_<?php echo $faculty['id']; ?>">
                                        <?php echo $faculty['status'] ? '<span class="badge bg-success">ON</span>' : '<span class="badge bg-secondary">OFF</span>'; ?>
                                    </label>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" class="text-center py-4 text-muted">No faculty records found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function updateDatabase(id) {
        var checkbox = document.getElementById('toggle_' + id);
        var label = document.getElementById('lbl_' + id);
        var state = checkbox.checked ? 1 : 0;

        label.innerHTML = state === 1 ? '<span class="badge bg-success">ON</span>' : '<span class="badge bg-secondary">OFF</span>';

        $.ajax({
            url: 'Sample.php',
            type: 'GET',
            data: { id: id, state: state },
            success: function(response) {
                console.log(response);
            },
            error: function(xhr, status, error) {
                console.error("Error updating status: " + error);
            }
        });
    }
</script>
</body>
</html>