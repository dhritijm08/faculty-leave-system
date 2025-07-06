<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Faculty Registration</title>
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
      width: 350px;
      box-sizing: border-box;
      max-height: 90vh;
      overflow-y: auto;
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

    input,
    select {
      padding: 10px;
      margin-top: 5px;
      border: 1px solid #ccc;
      border-radius: 3px;
      width: 100%;
      box-sizing: border-box;
      font-size: 14px;
    }

    input[type="file"] {
      padding: 5px;
      font-size: 13px;
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
      background: #FFC107 ;
      box-shadow: 0 1px 3px rgba(0,0,0,.15);
    }

    .form-group {
      margin-bottom: 5px;
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
      <h2>FACULTY REGISTRATION</h2>
      <form id="registrationForm" action="process_register.php" method="POST" enctype="multipart/form-data">
        
        <div class="form-group">
          <label for="faculty_id">Faculty ID:</label>
          <input type="text" name="faculty_id" id="faculty_id" required>
        </div>

        <div class="form-group">
          <label for="name">Full Name:</label>
          <input type="text" name="name" id="name" style="text-transform: uppercase" pattern="[A-Z\s]+" title="Only uppercase letters allowed" required>
        </div>

        <div class="form-group">
          <label for="email">Email:</label>
          <input type="email" name="email" id="email" required>
        </div>

        <div class="form-group">
          <label for="phone">Phone Number:</label>
          <input type="tel" name="phone" id="phone" pattern="[0-9]{10,15}" required>
        </div>

        <div class="form-group">
          <label for="username">Username:</label>
          <input type="text" name="username" id="username" required>
        </div>

        <div class="form-group">
          <label for="password">Password:</label>
          <input type="password" name="password" id="password" required>
        </div>

        <div class="form-group">
          <label for="role">Role:</label>
          <select name="role" id="role" required>
            <option value="" disabled selected>Select Role</option>
            <option value="faculty">Faculty</option>
            <option value="hod">HOD</option>
            <option value="dean">Dean</option>
            <option value="principal">Principal</option>
          </select>
        </div>

        <div class="form-group">
          <label for="department">Department:</label>
          <select name="department" id="department" required>
            <option value="" disabled selected>Select Department</option>
            <option value="Biochemistry">Biochemistry</option>
            <option value="Biotechnology">Biotechnology</option>
            <option value="Botany">Botany</option>
            <option value="Business Administration">Business Administration</option>
            <option value="Chemistry">Chemistry</option>
            <option value="Commerce">Commerce</option>
            <option value="Communication Studies">Communication Studies</option>
            <option value="Computer Science">Computer Science</option>
            <option value="Economics">Economics</option>
            <option value="Education">Education (B.Ed. & M.Ed. programs)</option>
            <option value="Electronics">Electronics</option>
            <option value="English">English</option>
            <option value="Environmental Science">Environmental Science</option>
            <option value="Fashion & Apparel Design">Fashion & Apparel Design</option>
            <option value="Food Science & Nutrition">Food Science & Nutrition</option>
            <option value="French">French</option>
            <option value="German">German</option>
            <option value="Hindi">Hindi</option>
            <option value="History">History</option>
            <option value="Home Science">Home Science</option>
            <option value="Human Development">Human Development</option>
            <option value="Interior Design & Management">Interior Design & Management</option>
            <option value="Journalism">Journalism</option>
            <option value="Kannada">Kannada</option>
            <option value="Life Science">Life Science</option>
            <option value="Mathematics">Mathematics</option>
            <option value="Microbiology">Microbiology</option>
            <option value="Nano Science & Technology">Nano Science & Technology</option>
            <option value="Physical Education">Physical Education</option>
            <option value="Physics">Physics</option>
            <option value="Political Science">Political Science</option>
            <option value="Psychology">Psychology</option>
            <option value="Sanskrit">Sanskrit</option>
            <option value="Sociology">Sociology</option>
            <option value="Statistics & Analytics">Statistics & Analytics</option>
            <option value="Travel & Tourism">Travel & Tourism</option>
            <option value="Zoology">Zoology</option>
          </select>
        </div>

        <div class="form-group">
          <label for="photo">Upload Photo:</label>
          <input type="file" name="photo" id="photo" accept="image/*" required>
        </div>

        <button type="submit">REGISTER</button>
      </form>
    </div>
  </div>

  <script>
    $(document).ready(function() {
      // Convert name to uppercase as user types
      $('#name').on('input', function() {
        this.value = this.value.toUpperCase();
      });

      // Form validation
      $('#registrationForm').submit(function(e) {
        const phone = $('#phone').val();
        if (!/^\d{10,15}$/.test(phone)) {
          alert('Phone number must be 10-15 digits');
          e.preventDefault();
          return false;
        }

        const name = $('#name').val();
        if (!/^[A-Z\s]+$/.test(name)) {
          alert('Name must contain only uppercase letters');
          e.preventDefault();
          return false;
        }

        return true;
      });
    });
  </script>
</body>
</html>