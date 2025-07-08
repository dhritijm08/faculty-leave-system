<?php
session_start();
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Prepare SQL - using email as username
    $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($user_id, $hashed_password, $role);
        $stmt->fetch();

        // Debug output (remove in production)
        // echo "Entered: $password<br>";
        // echo "Hash: $hashed_password<br>";

        if (password_verify($password, $hashed_password)) {
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $role;

            // Role-based redirection
            $redirect_path = match($role) {
                'faculty' => 'faculty_home.php',
                'hod' => 'hod_home.php',
                'dean' => 'dean_home.php',
                'principal' => 'principal_home.php',
                default => 'login.php',
            };

            echo json_encode(['status' => 'success', 'redirect' => $redirect_path]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid password']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
    }

    $stmt->close();
    $conn->close();
    exit();
}
?>