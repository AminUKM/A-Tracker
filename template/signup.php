<?php include 'db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Signup</title>
    <meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="../static/images/A+ Tracker.ico">
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700&display=swap" rel="stylesheet">
    <style>
        body {
            background: #FFF9E5; /* Light yellow */
            font-family: 'Montserrat', sans-serif;
            margin: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .signup-container {
            background: rgba(255,255,255,0.97);
            border-radius: 18px;
            box-shadow: 0 8px 32px 0 rgba(31,38,135,0.13);
            padding: 40px 32px 32px 32px;
            max-width: 370px;
            width: 100%;
            animation: fadeIn 1.2s cubic-bezier(.39,.575,.565,1) both;
        }
        @keyframes fadeIn {
            0% { opacity: 0; transform: translateY(-40px);}
            100% { opacity: 1; transform: translateY(0);}
        }
        .logo {
            display: block;
            margin: 0 auto 18px auto;
            width: 90px;
            height: 90px;
            border-radius: 20px; /* Rounded square */
            box-shadow: 0 4px 16px rgba(106,17,203,0.10);
            background: #fffbe6;
            object-fit: cover;
            border: 2.5px solid #ffe066;
            animation: logoPop 1s cubic-bezier(.39,.575,.565,1) 0.2s both;
        }
        @keyframes logoPop {
            0% { transform: scale(0.7); opacity: 0;}
            100% { transform: scale(1); opacity: 1;}
        }
        h2 {
            text-align: center;
            color: #2575fc;
            margin-bottom: 24px;
            font-weight: 700;
            letter-spacing: 1px;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        input[type="text"], input[type="email"], input[type="password"], input[type="number"] {
            padding: 12px;
            border: 1px solid #ffe066;
            border-radius: 8px;
            font-size: 1rem;
            transition: border 0.2s;
            background: #fffde7;
        }
        input[type="text"]:focus, input[type="email"]:focus, input[type="password"]:focus, input[type="number"]:focus {
            border: 1.5px solid #ffd43b;
            outline: none;
            background: #fffbe6;
        }
        input[type="submit"] {
            background: linear-gradient(90deg, #ffe066 0%, #ffd43b 100%);
            color: #2575fc;
            border: none;
            border-radius: 8px;
            padding: 12px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s, color 0.2s;
            box-shadow: 0 2px 8px rgba(255,212,59,0.08);
        }
        input[type="submit"]:hover {
            background: linear-gradient(90deg, #ffd43b 0%, #ffe066 100%);
            color: #6a11cb;
            transform: translateY(-2px) scale(1.03);
        }
        .success-message {
            color: #27ae60;
            text-align: center;
            margin-top: 14px;
            font-weight: 600;
            animation: fadeIn 0.7s;
        }
        .error-message {
            color: #e74c3c;
            text-align: center;
            margin-top: 14px;
            animation: shake 0.4s;
        }
        @keyframes shake {
            0% { transform: translateX(0);}
            20% { transform: translateX(-8px);}
            40% { transform: translateX(8px);}
            60% { transform: translateX(-8px);}
            80% { transform: translateX(8px);}
            100% { transform: translateX(0);}
        }
        .login-link {
            text-align: center;
            margin-top: 18px;
            font-size: 0.97rem;
        }
        .login-link a {
            color: #2575fc;
            font-weight: 600;
            text-decoration: underline;
            transition: color 0.2s;
        }
        .login-link a:hover {
            color: #6a11cb;
        }
    </style>
</head>
<body>
    <div class="signup-container">
        <img src="../static/images/A+ Tracker.png" alt="A+ Tracker Logo" class="logo">
        <h2>Sign Up</h2>
        <form method="POST" autocomplete="off">
            <input type="text" name="matric" placeholder="Matric Number" required autocomplete="off">
            <input type="text" name="name" placeholder="Name" required autocomplete="off">
            <input type="email" name="email" placeholder="Email" required autocomplete="off">
            <div style="position:relative; width:100%;">
                <input type="password" name="password" id="password" placeholder="Password" required style="width:100%; padding-right:38px; box-sizing:border-box;" autocomplete="new-password">
                <span id="togglePassword" style="position:absolute; top:50%; right:12px; transform:translateY(-50%); cursor:pointer;">
                    <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24">
                        <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z" stroke="#bdbdbd" stroke-width="2" fill="none"/>
                        <circle id="eyeBall" cx="12" cy="12" r="3" stroke="#bdbdbd" stroke-width="2" fill="none"/>
                    </svg>
                </span>
            </div>
            <input type="text" name="cgpa" placeholder="CGPA" required autocomplete="off">
            <input type="number" name="total_credits" placeholder="Total Credits (Previous Semester)" min="0" required autocomplete="off">
            <input type="submit" name="signup" value="Sign Up">
        </form>
        <div class="login-link">
            Already have an account?
            <a href="login.php">Login here</a>
        </div>
        <?php
if (isset($_POST['signup'])) {
    // Validate CGPA is numeric
    if (!is_numeric($_POST['cgpa'])) {
        echo '<div class="error-message">CGPA must be a number.</div>';
    } else {
        $stmt = $conn->prepare("INSERT INTO Table_Students (matric_number, name, email, password, cgpa, total_credits) VALUES (?, ?, ?, ?, ?, ?)");
        $hashed = password_hash($_POST['password'], PASSWORD_DEFAULT);
        try {
            if ($stmt->execute([$_POST['matric'], $_POST['name'], $_POST['email'], $hashed, $_POST['cgpa'], $_POST['total_credits']])) {
                echo "<script>
                    alert('Congratulation! Your account successfully registered.');
                    window.location.href = 'login.php';
                </script>";
            } else {
                echo "<div class='error-message'>Signup failed. Please try again.</div>";
            }
        } catch (PDOException $e) {
            echo "<div class='error-message'>Signup failed: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}
?>
    </div>
    <script>
        const password = document.getElementById('password');
        const toggle = document.getElementById('togglePassword');
        const eyeIcon = document.getElementById('eyeIcon');
        let show = false;
        toggle.addEventListener('click', function() {
            show = !show;
            password.type = show ? 'text' : 'password';
            eyeIcon.innerHTML = show
                ? `<path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z" stroke="#2575fc" stroke-width="2" fill="none"/>
                   <circle cx="12" cy="12" r="3" stroke="#2575fc" stroke-width="2" fill="none"/>
                   <line x1="5" y1="19" x2="19" y2="5" stroke="#2575fc" stroke-width="2"/>`
                : `<path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z" stroke="#bdbdbd" stroke-width="2" fill="none"/>
                   <circle cx="12" cy="12" r="3" stroke="#bdbdbd" stroke-width="2" fill="none"/>`;
        });
    </script>
</body>
</html>
