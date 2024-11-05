<?php
require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and sanitize form data
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);
    $role = $_POST["role"];

    // Check if username already exists
    $checkUserQuery = "SELECT * FROM users WHERE username = '$username'";
    $result = $conn->query($checkUserQuery);

    if ($result->num_rows > 0) {
        echo "<script>alert('Username already taken. Please choose a different username.'); window.location.href = 'index.php';</script>";
    } else {
        // Insert new user into the database
        $insertUserQuery = "INSERT INTO users (username, password, role) VALUES ('$username', '$password', '$role')";

        if ($conn->query($insertUserQuery) === TRUE) {
            echo "<script>
                    alert('Registration successful! You can now log in.');
                    window.location.href = 'index.php';
                  </script>";
            exit;
        } else {
            echo "<script>alert('Error: " . $conn->error . "'); window.location.href = 'index.php';</script>";
        }
    }

    // Close the connection
    $conn->close();
}
?>