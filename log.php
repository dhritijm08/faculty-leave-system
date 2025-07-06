<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Faculty Leave Login</title>
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

    form {
      display: flex;
      flex-direction: column;
      text-align: left;
    }

    label {
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
      background: #FFC107;
      box-shadow: 0 1px 3px rgba(0,0,0,.15);
    }

    .error {
      color: red;
      font-size: 14px;
      margin-top: 10px;
    }

    .success {
      color: green;
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
      <h2>FACULTY LEAVE LOGIN</h2>
      <div id="message"></div>
      <form id="loginForm">
        <label for="username">Username:</label>
        <input type="text" name="username" id="username" required />

        <label for="password">Password:</label>
        <input type="password" name="password" id="password" required />

        <button type="submit">SIGN IN</button>
      </form>
    </div>
  </div>

  <script>
    $(document).ready(function () {
      $('#loginForm').submit(function (e) {
        e.preventDefault(); // Prevent form submission

        var formData = {
          username: $('#username').val().trim(),
          password: $('#password').val().trim()
        };

        // Validate inputs
        if (!formData.username || !formData.password) {
          $('#message').html('<p class="error">Username and password are required</p>');
          return;
        }

        // Show loading state
        $('button[type="submit"]').prop('disabled', true).text('Signing in...');

        $.ajax({
          url: 'login.php',
          type: 'POST',
          data: formData,
          dataType: 'json',
          success: function (response) {
            if (response.status === 'success') {
              $('#message').html('<p class="success">Login successful! Redirecting...</p>');
              // Redirect immediately
              window.location.href = response.redirect;
            } else {
              $('#message').html('<p class="error">' + response.message + '</p>');
            }
          },
          error: function (xhr) {
            let message = 'An error occurred. Please try again.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
              message = xhr.responseJSON.message;
            }
            $('#message').html('<p class="error">' + message + '</p>');
          },
          complete: function () {
            $('button[type="submit"]').prop('disabled', false).text('SIGN IN');
          }
        });
      });
    });
  </script>
</body>
</html>