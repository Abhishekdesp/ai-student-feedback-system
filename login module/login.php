<?php
session_start();

// If already logged in, redirect to appropriate home page
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'student') {
        header("Location: dashboard.php");
        exit();
    } else {
        header("Location: Homepage.php");
        exit();
    }
}

$alertMessage = '';
$selectedRole = isset($_POST['role']) ? $_POST['role'] : 'student';

function getDbConnection($dbname) {
    $hosts = ["127.0.0.1", "localhost"];
    foreach ($hosts as $host) {
        try {
            $conn = @new mysqli($host, "root", "", $dbname);
            if ($conn && !$conn->connect_error) {
                return $conn;
            }
        } catch (Throwable $e) {
            // connection attempt failed, try next
        }
    }
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $role = isset($_POST['role']) ? trim($_POST['role']) : 'student';

    if (empty($username) || empty($password)) {
        $alertMessage = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Error!</strong> Please enter both username and password.
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>';
    } else {
        // Try Student login if student role chosen or auto
        if ($role === 'student' || $role === 'auto') {
            $conn_s = getDbConnection("student");
            if ($conn_s) {
                $u_s = $conn_s->real_escape_string($username);
                $p_s = $conn_s->real_escape_string($password);
                $sql_s = "SELECT * FROM student WHERE sname='$u_s' AND password='$p_s'";
                $res_s = $conn_s->query($sql_s);
                if ($res_s && $res_s->num_rows > 0) {
                    $row_s = $res_s->fetch_assoc();
                    $_SESSION['loggedin'] = true;
                    $_SESSION['role'] = 'student';
                    $_SESSION['username'] = $row_s['sname'];
                    $_SESSION['year'] = $row_s['year'];
                    $conn_s->close();
                    header("Location: dashboard.php");
                    exit();
                }
                $conn_s->close();
            }
        }

        // Try Teacher / Admin login if teacher role chosen or auto
        if ($role === 'teacher' || $role === 'auto') {
            $conn_a = getDbConnection("admin");
            if ($conn_a) {
                $u_a = $conn_a->real_escape_string($username);
                $p_a = $conn_a->real_escape_string($password);
                $sql_a = "SELECT * FROM admin WHERE username='$u_a' AND password='$p_a'";
                $res_a = $conn_a->query($sql_a);
                if ($res_a && $res_a->num_rows > 0) {
                    $row_a = $res_a->fetch_assoc();
                    $_SESSION['loggedin'] = true;
                    $_SESSION['role'] = 'teacher';
                    $_SESSION['username'] = $row_a['username'];
                    $conn_a->close();
                    header("Location: Homepage.php");
                    exit();
                }
                $conn_a->close();
            }
        }

        $alertMessage = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Invalid credentials!</strong> Please check your username and password.
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Feedback System - Login</title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <style>
    body {
      background-color: #f8f9fa;
      color: #333;
      min-height: 100vh;
    }
    .navbar {
      background-color: black !important;
    }
    .navbar-brand {
      color: #fff !important;
      font-weight: 600;
    }
    .login-box {
      max-width: 420px;
      margin: 60px auto;
      padding: 30px;
      border: 2px solid #000;
      border-radius: 10px;
      background-color: #ffffff;
      box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
    }
    .role-toggle-group {
      display: flex;
      margin-bottom: 25px;
      border: 1px solid #ced4da;
      border-radius: 6px;
      overflow: hidden;
    }
    .role-toggle-btn {
      flex: 1;
      border: none;
      padding: 10px;
      background-color: #f8f9fa;
      color: #495057;
      font-weight: 600;
      font-size: 0.95rem;
      cursor: pointer;
      transition: all 0.2s ease-in-out;
    }
    .role-toggle-btn.active {
      background-color: #007bff;
      color: #ffffff;
    }
    .form-group label {
      font-weight: 600;
      color: #212529;
    }
    .btn-submit {
      font-weight: 600;
      padding: 10px;
      font-size: 1rem;
    }
  </style>
</head>
<body>

<!-- Admin-Matching Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark">
  <a class="navbar-brand" href="#">Student Feedback System</a>
</nav>

<div class="container">
  <div class="login-box">
    <h2 class="text-center mb-4 font-weight-bold" id="login-title">Portal Login</h2>

    <?php echo $alertMessage; ?>

    <div class="role-toggle-group">
      <button type="button" class="role-toggle-btn <?php echo ($selectedRole === 'student') ? 'active' : ''; ?>" id="btn-student" onclick="setRole('student')">Student</button>
      <button type="button" class="role-toggle-btn <?php echo ($selectedRole === 'teacher') ? 'active' : ''; ?>" id="btn-teacher" onclick="setRole('teacher')">Teacher / Admin</button>
    </div>

    <form method="post" action="login.php">
      <input type="hidden" name="role" id="role-input" value="<?php echo htmlspecialchars($selectedRole); ?>">

      <div class="form-group">
        <label for="username" id="username-label">Username:</label>
        <input type="text" class="form-control" id="username" name="username" placeholder="Enter username" required autofocus>
      </div>

      <div class="form-group">
        <label for="password">Password:</label>
        <input type="password" class="form-control" id="password" name="password" placeholder="Enter password" required>
      </div>

      <button type="submit" class="btn btn-primary btn-block btn-submit" id="submit-btn">Submit</button>
    </form>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<script>
  function setRole(role) {
    document.getElementById('role-input').value = role;
    const btnStudent = document.getElementById('btn-student');
    const btnTeacher = document.getElementById('btn-teacher');
    const loginTitle = document.getElementById('login-title');
    const submitBtn = document.getElementById('submit-btn');
    const usernameLabel = document.getElementById('username-label');

    if (role === 'student') {
      btnStudent.classList.add('active');
      btnTeacher.classList.remove('active');
      loginTitle.innerText = 'Student Login';
      submitBtn.innerText = 'Sign In as Student';
      usernameLabel.innerText = 'Student Username:';
    } else {
      btnTeacher.classList.add('active');
      btnStudent.classList.remove('active');
      loginTitle.innerText = 'Admin / Teacher Login';
      submitBtn.innerText = 'Sign In as Teacher';
      usernameLabel.innerText = 'Teacher / Admin Username:';
    }
  }

  // Initialize role toggle UI
  document.addEventListener('DOMContentLoaded', function() {
    setRole('<?php echo htmlspecialchars($selectedRole); ?>');
  });
</script>

</body>
</html>