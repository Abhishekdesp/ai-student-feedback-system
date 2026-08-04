<?php
session_start();
require_once "_dbconfig.php";

// If already logged in, redirect to appropriate home page
if (isset($_SESSION['loggedin']) || (isset($_SESSION['username']) && !empty($_SESSION['username']))) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'student') {
        header("Location: dashboard.php");
        exit();
    } else if (isset($_SESSION['role']) && $_SESSION['role'] === 'teacher') {
        header("Location: Homepage.php");
        exit();
    }
}

$alertMessage = '';
$selectedRole = isset($_POST['role']) ? $_POST['role'] : 'student';

function getDbConnection($dbname) {
    return getGlobalDbConnection($dbname);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $role = isset($_POST['role']) ? $_POST['role'] : 'student';
    $selectedRole = $role;

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
            if ($conn_s && !$conn_s->connect_error) {
                $u_s = $conn_s->real_escape_string($username);
                $p_s = $conn_s->real_escape_string($password);
                $sql_s = "SELECT * FROM student WHERE LOWER(sname)=LOWER('$u_s') AND password='$p_s'";
                $res_s = @$conn_s->query($sql_s);
                if ($res_s && $res_s->num_rows > 0) {
                    $row_s = $res_s->fetch_assoc();
                    $_SESSION['loggedin'] = true;
                    $_SESSION['role'] = 'student';
                    $_SESSION['username'] = $row_s['sname'];
                    $_SESSION['year'] = $row_s['year'];
                    header("Location: dashboard.php");
                    exit();
                }
            }
        }

        // Try Teacher / Admin login if teacher role chosen or auto
        if ($role === 'teacher' || $role === 'auto') {
            $conn_a = getDbConnection("admin");
            if ($conn_a && !$conn_a->connect_error) {
                $u_a = $conn_a->real_escape_string($username);
                $p_a = $conn_a->real_escape_string($password);
                $sql_a = "SELECT * FROM admin WHERE LOWER(username)=LOWER('$u_a') AND password='$p_a'";
                $res_a = @$conn_a->query($sql_a);
                if ($res_a && $res_a->num_rows > 0) {
                    $row_a = $res_a->fetch_assoc();
                    $_SESSION['loggedin'] = true;
                    $_SESSION['role'] = 'teacher';
                    $_SESSION['username'] = $row_a['username'];
                    header("Location: Homepage.php");
                    exit();
                }
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
  <!-- Bootstrap 4 -->
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <style>
    body {
      background-color: #f8f9fa;
      color: #333;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    }
    .login-card {
      background-color: #ffffff;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      padding: 36px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.05);
      width: 100%;
      max-width: 420px;
    }
    .role-btn-group .btn {
      font-weight: 600;
      padding: 10px;
    }
  </style>
</head>
<body>

<div class="login-card">
  <div class="text-center mb-4">
    <h3 class="font-weight-bold" id="formTitle">Student Login</h3>
  </div>

  <?php echo $alertMessage; ?>

  <form action="login.php" method="post" id="loginForm">
    <input type="hidden" name="role" id="roleInput" value="<?php echo htmlspecialchars($selectedRole); ?>">

    <div class="role-btn-group btn-group btn-group-toggle w-100 mb-4" data-toggle="buttons">
      <label class="btn btn-outline-primary <?php echo ($selectedRole === 'student') ? 'active' : ''; ?>" id="studentTab">
        <input type="radio" name="role_toggle" value="student" autocomplete="off" <?php echo ($selectedRole === 'student') ? 'checked' : ''; ?>> Student
      </label>
      <label class="btn btn-outline-primary <?php echo ($selectedRole === 'teacher') ? 'active' : ''; ?>" id="teacherTab">
        <input type="radio" name="role_toggle" value="teacher" autocomplete="off" <?php echo ($selectedRole === 'teacher') ? 'checked' : ''; ?>> Teacher / Admin
      </label>
    </div>

    <div class="form-group">
      <label for="username" id="usernameLabel" class="font-weight-bold">Student Name:</label>
      <input type="text" class="form-control" name="username" id="username" placeholder="e.g. Aarav" required>
    </div>

    <div class="form-group mb-4">
      <label for="password" class="font-weight-bold">Password:</label>
      <input type="password" class="form-control" name="password" id="password" placeholder="Enter password" required>
    </div>

    <button type="submit" class="btn btn-primary btn-block py-2 font-weight-bold" id="submitBtn">
      Sign In as Student
    </button>
  </form>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
  $(document).ready(function() {
    function updateRoleUI(role) {
      $('#roleInput').val(role);
      if (role === 'student') {
        $('#formTitle').text('Student Login');
        $('#usernameLabel').text('Student Name:');
        $('#username').attr('placeholder', 'e.g. Aarav');
        $('#submitBtn').text('Sign In as Student');
      } else {
        $('#formTitle').text('Admin / Teacher Login');
        $('#usernameLabel').text('Teacher / Admin Username:');
        $('#username').attr('placeholder', 'admin');
        $('#submitBtn').text('Sign In as Teacher');
      }
    }

    $('input[name="role_toggle"]').change(function() {
      updateRoleUI($(this).val());
    });

    updateRoleUI('<?php echo $selectedRole; ?>');
  });
</script>
</body>
</html>