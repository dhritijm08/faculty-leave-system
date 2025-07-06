<?php
session_start();
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $faculty_id = $_POST['faculty_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $role = $_POST['role'];
    $department = $_POST['department'];

    // Validate name (uppercase letters and spaces only)
    if (!preg_match('/^[A-Z\s]+$/', $name)) {
        die("❌ Name must contain only uppercase letters and spaces.");
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("❌ Invalid email format.");
    }

    // Validate phone number (basic validation)
    if (!preg_match('/^[0-9]{10,15}$/', $phone)) {
        die("❌ Phone number should be 10-15 digits.");
    }

    // Check if Faculty ID already exists
    $stmt = $conn->prepare("SELECT faculty_id FROM users WHERE faculty_id = ?");
    $stmt->bind_param("s", $faculty_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        die("❌ Faculty ID already exists. Please use a unique ID.");
    }
    $stmt->close();

    // Check if email already exists
    $stmt = $conn->prepare("SELECT email FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        die("❌ Email already exists. Please use a different email.");
    }
    $stmt->close();

    // Handle Photo Upload
    $upload_dir = "uploads/";
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    if ($_FILES['photo']['error'] == UPLOAD_ERR_OK) {
        $photo_name = basename($_FILES['photo']['name']);
        $photo_path = $upload_dir . $faculty_id . "_" . $photo_name;

        // Validate image type
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($_FILES['photo']['type'], $allowed_types)) {
            die("❌ Invalid image type. Only JPEG, PNG, and GIF are allowed.");
        }

        // Move uploaded file
        if (!move_uploaded_file($_FILES['photo']['tmp_name'], $photo_path)) {
            die("❌ Error uploading the photo.");
        }
    } else {
        $photo_path = "uploads/default.jpg";
    }

    // Insert into database
    $stmt = $conn->prepare("INSERT INTO users (faculty_id, name, email, phone, username, password, role, department, photo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssss", $faculty_id, $name, $email, $phone, $username, $password, $role, $department, $photo_path);

    if ($stmt->execute()) {
        echo "✅ Registration successful! Redirecting to login...";

        // Auto-login and redirect
        $_SESSION['faculty_id'] = $faculty_id;
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $role;
        $_SESSION['name'] = $name;

        // Redirect based on role
        switch ($role) {
            case 'faculty':
                header("Location: faculty_home.php");
                break;
            case 'hod':
                header("Location: hod_home.php");
                break;
            case 'dean':
                header("Location: dean_home.php");
                break;
            case 'principal':
                header("Location: principal_home.php");
                break;
            default:
                header("Location: login.php");
        }
        exit();
    } else {
        echo "❌ Error during registration: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>