<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "student";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Add a column 'imported' to the 'student' table if it doesn't exist
$sqlAlterTable = "ALTER TABLE student ADD COLUMN IF NOT EXISTS imported BOOLEAN DEFAULT FALSE";
$conn->query($sqlAlterTable);

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_FILES['file']['name'])) {
        $inputFileName = $_FILES['file']['tmp_name'];

        $file = fopen($inputFileName, 'r');

        // Skip the header row if it exists
        fgetcsv($file);

        // Determine the last ID in the database
        $lastID = 0;
        $sqlLastID = "SELECT MAX(id) AS lastID FROM student";
        $resultLastID = $conn->query($sqlLastID);
        if ($resultLastID->num_rows > 0) {
            $row = $resultLastID->fetch_assoc();
            $lastID = $row["lastID"];
        }

        while (($row = fgetcsv($file, 1000, ',')) !== FALSE) {
            // Increment ID for each new record
            $lastID++;

            $sname = $row[0];
            $year = $row[1];
            $password = generateRandomPassword(); // Generate a random password

            // Insert data into MySQL database without checking for existing records
            $sql = "INSERT INTO student (id, sname, year, password, imported) VALUES ('$lastID', '$sname', '$year', '$password', TRUE)";
            if ($conn->query($sql) !== TRUE) {
                echo "Error: " . $sql . "<br>" . $conn->error;
            }
        }

        fclose($file);

        $message = "Data imported successfully";
    }
}

function generateRandomPassword($length = 8) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $password;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <title>Data Import</title>
</head>

<body>
    <div class="container mt-5">
        <?php if (!empty($message)) : ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <input type="file" name="file" class="form-control-file mb-3">
            <button type="submit" class="btn btn-primary">Import Data</button>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>


</body>

</html>
+






    