<?php
session_start();
include 'db_connect.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT password FROM admin WHERE id = 1");
    $stmt->execute();
    $stmt->bind_result($stored_password);
    $stmt->fetch();
    $stmt->close();

    if (password_verify($password, $stored_password)) {
        $_SESSION['admin'] = true;
        header("Location: admin.php");
        exit();
    } else {
        $error = "Incorrect password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Login</title>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <style>
    body {
      height: 100%;
      margin: 0;
      padding: 0;
      font-family: Arial, sans-serif;
    }

    .background {
      background-image: url('staffleaveback.png');
      background-size: cover;
      background-position: center;
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .login-box {
      background-color: #FFFFFF;
      padding: 25px 30px;
      border-radius: 3px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
      text-align: center;
      width: 320px;
      box-sizing: border-box;
    }

    .login-box img {
      width: 100px;
    }

    .login-box h2 {
      font-size: 18px;
      color: #0F1111;
      margin-bottom: 20px;
    }

    label {
      display: block;
      text-align: left;
      margin-top: 10px;
      font-size: 14px;
      font-weight: bold;
      color: #333;
    }

    input {
      padding: 10px;
      margin-top: 5px;
      border: 1px solid #ccc;
      border-radius: 3px;
      width: 100%;
      box-sizing: border-box;
      font-size: 14px;
    }

    button {
      width: 100%;
      padding: 12px 16px;
      margin-top: 15px;
      background: #FFD700;
      color: #111111;
      border: 1px solid #FFD700;
      border-radius: 3px;
      cursor: pointer;
      font-weight: bold;
      font-size: 14px;
      box-sizing: border-box;
      box-shadow: 0 1px 0 rgba(255,255,255,.4) inset;
    }

    button:hover {
      background:  #FFC107 ;
      box-shadow: 0 1px 3px rgba(0,0,0,.15);
    }

    .error {
      color: red;
      font-size: 14px;
      margin-top: 10px;
    }
    
    @media (max-width: 480px) {
      .login-box {
        width: 90%;
        margin: 20px;
        padding: 30px 20px;
      }
      
      .login-box h2 {
        font-size: 16px;
      }
    }
  </style>
</head>

<body>
  <div class="background">
    <div class="login-box">
      <img src="facultyleave.png" alt="Faculty Logo">
      <h2>ADMIN LOGIN</h2>
      <form method="POST" action="">
        <label for="password">Enter Admin Password:</label>
        <input type="password" name="password" id="password" required>
        <button type="submit">SIGN IN</button>
        <?php if (!empty($error)): ?>
          <div class="error"><?= htmlspecialchars($error); ?></div>
        <?php endif; ?>
      </form>
    </div>
  </div>
</body>

</html>