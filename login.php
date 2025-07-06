<?php
session_start();
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Check user in database
    $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($user_id, $hashed_password, $role);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            // Store user info in session
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $role;

            // Determine redirect path based on role
            $redirect_path = '';
            switch ($role) {
                case 'faculty':
                    $redirect_path = 'faculty_home.php';
                    break;
                case 'hod':
                    $redirect_path = 'hod_home.php';
                    break;
                case 'dean':
                    $redirect_path = 'dean_home.php';
                    break;
                case 'principal':
                    $redirect_path = 'principal_home.php';
                    break;
                default:
                    $redirect_path = 'login.php';
            }

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