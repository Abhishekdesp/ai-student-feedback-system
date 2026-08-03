<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

$host = "127.0.0.1";
$username = "root";
$password = "";

$conn_faculty = new mysqli($host, $username, $password, "faculty");
if ($conn_faculty->connect_error) {
    die("Connection failed: " . $conn_faculty->connect_error);
}

$message = "";
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'add';

// Handle Add Faculty
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['addFaculty'])) {
    if (empty($_POST["name"]) || empty($_POST["designation"]) || empty($_POST["email"]) || empty($_POST["scheme"]) || empty($_POST["semester"]) || empty($_POST["mobile"]) || empty($_POST["year"]) || empty($_POST["subject"])) {
        $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
            All fields are required!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';
    } else {
        $name = $conn_faculty->real_escape_string($_POST["name"]);
        $designation = $conn_faculty->real_escape_string($_POST["designation"]);
        $email = $conn_faculty->real_escape_string($_POST["email"]);
        $scheme = $conn_faculty->real_escape_string($_POST["scheme"]);
        $semester = $conn_faculty->real_escape_string($_POST["semester"]);
        $mobile = $conn_faculty->real_escape_string($_POST["mobile"]);
        $year = $conn_faculty->real_escape_string($_POST["year"]);
        $subject = strtoupper($conn_faculty->real_escape_string($_POST["subject"]));

        $check_q = "SELECT * FROM faculty WHERE subject = '$subject'";
        $check_res = $conn_faculty->query($check_q);

        if ($check_res && $check_res->num_rows > 0) {
            $message = '<div class="alert alert-warning alert-dismissible fade show" role="alert">
                Subject <strong>' . htmlspecialchars($subject) . '</strong> already exists!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
        } else {
            $max_q = "SELECT MAX(id) AS max_id FROM faculty";
            $max_res = $conn_faculty->query($max_q);
            $max_row = $max_res->fetch_assoc();
            $new_id = ($max_row['max_id'] !== null) ? $max_row['max_id'] + 1 : 1;

            $insert_sql = "INSERT INTO faculty (id, name, designation, email, scheme, semester, mobile, year, subject, status) 
                           VALUES ('$new_id', '$name', '$designation', '$email', '$scheme', '$semester', '$mobile', '$year', '$subject', 1)";

            if ($conn_faculty->query($insert_sql) === TRUE) {
                $conn_resp = new mysqli($host, $username, $password, "responses");
                if (!$conn_resp->connect_error) {
                    $lower_sub = strtolower($subject);
                    $tbl_name = "{$lower_sub}_responses";
                    $create_tbl = "CREATE TABLE IF NOT EXISTS `$tbl_name` (
                        id INT PRIMARY KEY AUTO_INCREMENT,
                        excellent INT DEFAULT 0,
                        very_good INT DEFAULT 0,
                        good INT DEFAULT 0,
                        poor INT DEFAULT 0,
                        bad INT DEFAULT 0,
                        Counter INT DEFAULT 0
                    )";
                    $conn_resp->query($create_tbl);

                    $cnt = $conn_resp->query("SELECT COUNT(*) AS c FROM `$tbl_name`")->fetch_assoc()['c'];
                    if ($cnt == 0) {
                        for ($qi = 1; $qi <= 5; $qi++) {
                            $conn_resp->query("INSERT INTO `$tbl_name` (id, excellent, very_good, good, poor, bad, Counter) VALUES ($qi, 0, 0, 0, 0, 0, 0)");
                        }
                    }

                    $conn_st = new mysqli($host, $username, $password, "student");
                    if (!$conn_st->connect_error) {
                        $col_name = "{$subject}_submitted";
                        $conn_st->query("ALTER TABLE student ADD COLUMN IF NOT EXISTS `$col_name` INT DEFAULT 0");
                        $conn_st->close();
                    }

                    $conn_resp->close();
                }

                $message = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                    Faculty <strong>' . htmlspecialchars($name) . '</strong> (' . htmlspecialchars($subject) . ') registered successfully!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>';
                $activeTab = 'list';
            } else {
                $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                    Error adding faculty: ' . $conn_faculty->error . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>';
            }
        }
    }
}

// Fetch all faculty
$faculty_rows = [];
$res_f = $conn_faculty->query("SELECT * FROM faculty ORDER BY id ASC");
if ($res_f) {
    while ($r = $res_f->fetch_assoc()) {
        $faculty_rows[] = $r;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Management - Student Feedback System</title>
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
            padding: 30px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            margin-top: 40px;
        }
        .nav-tabs .nav-link {
            color: #64748b;
            font-weight: 500;
        }
        .nav-tabs .nav-link.active {
            color: #2563eb;
            font-weight: 600;
        }
        .form-control, .form-select {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 8px 12px;
        }
        .form-control:focus, .form-select:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }
    </style>
</head>
<body>

<div style="position: absolute; top: 15px; left: 15px; z-index: 100;">
  <a class="btn btn-outline-secondary btn-sm" href="Homepage.php" title="Back">&larr; Back</a>
</div>

<div class="container">
    <div class="main-card">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4 border-bottom pb-3">
            <div>
                <h4 class="fw-bold text-dark mb-1">Faculty Management</h4>
                <p class="text-muted small mb-0">Register faculty members and manage subject assignments</p>
            </div>
            
            <ul class="nav nav-tabs border-0">
                <li class="nav-item">
                    <button class="nav-link <?php echo ($activeTab === 'add') ? 'active' : ''; ?>" id="tab-add-btn" data-bs-toggle="tab" data-bs-target="#tab-add">
                        Add Faculty
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link <?php echo ($activeTab === 'list') ? 'active' : ''; ?>" id="tab-list-btn" data-bs-toggle="tab" data-bs-target="#tab-list">
                        Faculty Directory (<?php echo count($faculty_rows); ?>)
                    </button>
                </li>
            </ul>
        </div>

        <?php echo $message; ?>

        <div class="tab-content">
            <!-- TAB 1: ADD FACULTY -->
            <div class="tab-pane fade <?php echo ($activeTab === 'add') ? 'show active' : ''; ?>" id="tab-add">
                <form method="post" action="add_faculty.php" id="addFacultyForm" class="row g-3">
                    <input type="hidden" name="addFaculty" value="1">
                    
                    <div class="col-md-6">
                        <label for="name" class="form-label text-dark fw-medium small">Faculty Name</label>
                        <input type="text" class="form-control" name="name" id="name" placeholder="e.g. Dr. Rajesh Sharma" required>
                    </div>

                    <div class="col-md-6">
                        <label for="designation" class="form-label text-dark fw-medium small">Designation</label>
                        <input type="text" class="form-control" name="designation" id="designation" placeholder="e.g. Professor & HOD" required>
                    </div>

                    <div class="col-md-6">
                        <label for="email" class="form-label text-dark fw-medium small">Email Address</label>
                        <input type="email" class="form-control" name="email" id="email" placeholder="faculty@college.edu" required>
                    </div>

                    <div class="col-md-3">
                        <label for="scheme" class="form-label text-dark fw-medium small">Scheme</label>
                        <input type="text" class="form-control" name="scheme" id="scheme" placeholder="e.g. K-Scheme" required>
                    </div>

                    <div class="col-md-3">
                        <label for="semester" class="form-label text-dark fw-medium small">Semester</label>
                        <select name="semester" id="semester" class="form-select" onchange="updateYearFromSem()" required>
                            <option value="1">Semester 1</option>
                            <option value="2">Semester 2</option>
                            <option value="3">Semester 3</option>
                            <option value="4">Semester 4</option>
                            <option value="5" selected>Semester 5</option>
                            <option value="6">Semester 6</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="mobile" class="form-label text-dark fw-medium small">Mobile Number</label>
                        <input type="text" class="form-control" name="mobile" id="mobile" placeholder="10-digit mobile" required>
                    </div>

                    <div class="col-md-4">
                        <label for="year" class="form-label text-dark fw-medium small">Academic Year</label>
                        <input type="text" class="form-control bg-light" name="year" id="year" value="Third" readonly required>
                    </div>

                    <div class="col-md-4">
                        <label for="subject" class="form-label text-dark fw-medium small">Subject Code</label>
                        <input type="text" class="form-control" name="subject" id="subject" placeholder="e.g. AJP, WT, DBMS" required>
                    </div>

                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary px-4 fw-medium">
                            Register Faculty
                        </button>
                    </div>
                </form>
            </div>

            <!-- TAB 2: FACULTY DIRECTORY & LIVE SEARCH -->
            <div class="tab-pane fade <?php echo ($activeTab === 'list') ? 'show active' : ''; ?>" id="tab-list">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <input type="text" id="facultySearch" class="form-control w-50" placeholder="Search by name, subject, or designation...">
                    <span class="text-muted small">Total: <?php echo count($faculty_rows); ?></span>
                </div>

                <div class="table-responsive border rounded-2">
                    <table class="table table-hover align-middle mb-0" id="facultyTable">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Designation</th>
                                <th>Subject</th>
                                <th>Year</th>
                                <th>Mobile</th>
                                <th>Email</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (!empty($faculty_rows)) {
                                foreach ($faculty_rows as $row) {
                                    $f_id = $row['id'];
                                    $st = intval($row['status']);
                                    $checked = ($st === 1) ? 'checked' : '';
                                    ?>
                                    <tr>
                                        <td>#<?php echo $row['id']; ?></td>
                                        <td class="fw-medium"><?php echo htmlspecialchars($row['name']); ?></td>
                                        <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($row['designation']); ?></span></td>
                                        <td><span class="badge bg-primary"><?php echo htmlspecialchars($row['subject']); ?></span></td>
                                        <td><?php echo htmlspecialchars($row['year']); ?> (Sem <?php echo htmlspecialchars($row['semester']); ?>)</td>
                                        <td><?php echo htmlspecialchars($row['mobile']); ?></td>
                                        <td><small class="text-muted"><?php echo htmlspecialchars($row['email']); ?></small></td>
                                        <td>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" role="switch" id="f_toggle_<?php echo $f_id; ?>" <?php echo $checked; ?> onclick="toggleFacultyStatus(<?php echo $f_id; ?>)">
                                                <label class="form-check-label ms-1" id="f_lbl_<?php echo $f_id; ?>">
                                                    <?php echo ($st === 1) ? '<span class="badge bg-success">ON</span>' : '<span class="badge bg-secondary">OFF</span>'; ?>
                                                </label>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                echo '<tr><td colspan="8" class="text-center py-4 text-muted">No faculty members found.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    function updateYearFromSem() {
        var sem = document.getElementById('semester').value;
        var yearInput = document.getElementById('year');
        if (sem == '1' || sem == '2') {
            yearInput.value = 'First';
        } else if (sem == '3' || sem == '4') {
            yearInput.value = 'Second';
        } else if (sem == '5' || sem == '6') {
            yearInput.value = 'Third';
        }
    }

    $(document).ready(function() {
        $("#facultySearch").on("keyup", function() {
            var value = $(this).val().toLowerCase();
            $("#facultyTable tbody tr").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
            });
        });
    });

    function toggleFacultyStatus(id) {
        var checkbox = document.getElementById('f_toggle_' + id);
        var label = document.getElementById('f_lbl_' + id);
        var isChecked = checkbox.checked ? 1 : 0;

        label.innerHTML = isChecked === 1 ? "<span class='badge bg-success'>ON</span>" : "<span class='badge bg-secondary'>OFF</span>";

        $.ajax({
            url: 'Sample.php',
            type: 'GET',
            data: { id: id, state: isChecked },
            success: function(res) {
                console.log("Status updated: " + res);
            }
        });
    }
</script>
</body>
</html>
