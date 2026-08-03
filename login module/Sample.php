<?php
// Replace these values with your database credentials
$host = "localhost";
$username = "root";
$password = "";
$database = "faculty";

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle toggle button click and update database
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id']) && isset($_GET['state'])) {
    $id = $_GET['id'];
    $state = $_GET['state'];

    // Update the toggle state in the database
    $updateSql = "UPDATE faculty SET status = $state WHERE id = $id";
    if ($conn->query($updateSql) === TRUE) {
        echo 'Status updated successfully!';
    } else {
        echo 'Error updating status: ' . $conn->error;
    }

    // Close the database connection
    $conn->close();
    exit; // Stop further execution after handling the toggle request
}

// Retrieve column names dynamically
$result = $conn->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'faculty'");
$columns = array();
while ($row = $result->fetch_assoc()) {
    $columns[] = $row['COLUMN_NAME'];
}

// Retrieve data from the database
$sql = "SELECT * FROM faculty"; // Select all columns
$result = $conn->query($sql);

// Check if there are results
if ($result->num_rows > 0) {
    // Display back button at top left
    echo "<div style='position: absolute; top: 15px; left: 15px;'>";
    echo "<a class='btn btn-dark btn-sm font-weight-bold' href='Homepage.php' title='Back' style='font-size: 1.1rem; padding: 2px 10px;'>&larr;</a>";
    echo "</div>";

    // Display data in tabular form
    echo "<div class='container mt-5'>";
    echo "<table class='table'>";
    echo "<thead>";
    echo "<tr>";
    foreach ($columns as $column) {
        echo "<th>$column</th>";
    }
    echo "<th>Status</th>"; // Add an additional column for the toggle button
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";

    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        foreach ($columns as $column) {
            echo "<td>{$row[$column]}</td>";
        }
        $id = $row['id'];
        $toggleState = $row['Status'];
        echo "<td>";
        echo "<div class='form-check form-switch'>";
        echo "<input class='form-check-input' type='checkbox' role='switch' id='toggle_$id' ";
        echo $toggleState == 1 ? "checked" : "";
        echo " onclick='toggleButtonClick($id)'>";
        echo "</div>";
        echo "</td>";
        echo "</tr>";
    }

    echo "</tbody>";
    echo "</table>";
    echo "</div>";
} else {
    echo "No data found.";
}
?>
<!-- Rest of your HTML and script tags remain unchanged -->

<html>

<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p"
        crossorigin="anonymous"></script>
</head>

<body>
    <script>
        function toggleButtonClick(id) {
            // Send an AJAX request to update the toggle state in the database
            var xhr = new XMLHttpRequest();
            var isChecked = document.getElementById('toggle_' + id).checked ? 1 : 0;
            xhr.onreadystatechange = function () {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    // Handle the response if needed
                    console.log(xhr.responseText);
                }
            };

            xhr.open("GET", "Sample.php?id=" + id + "&state=" + isChecked, true);
            xhr.send();
        }
    </script>
</body>

</html>