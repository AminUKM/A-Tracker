<?php
include 'db.php';
session_start();
if (!isset($_SESSION['matric'])) { header("Location: Login.php"); exit(); }

// Fetch current student profile
$stmt = $conn->prepare("SELECT * FROM Table_Students WHERE matric_number = ?");
$stmt->execute([$_SESSION['matric']]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    header("Location: dashboard.php");
    exit();
}

$message = '';
if (isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $cgpa = trim($_POST['cgpa']);

    $stmt = $conn->prepare("UPDATE Table_Students SET name = ?, email = ?, cgpa = ? WHERE matric_number = ?");
    $success = $stmt->execute([$name, $email, $cgpa, $_SESSION['matric']]);
    if ($success) {
        $message = "<div class='success-message'>Profile updated successfully!</div>";
        // Refresh student data
        $stmt = $conn->prepare("SELECT * FROM Table_Students WHERE matric_number = ?");
        $stmt->execute([$_SESSION['matric']]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $message = "<div class='error-message'>Failed to update profile. Please try again.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Edit Profile</title>
    <meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="/static/images/APlusTracker.ico">
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700&display=swap" rel="stylesheet">
    <style>
        body {
            background: #FFF9E5;
            font-family: 'Montserrat', sans-serif;
            margin: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
        }
        .edit-profile-box {
            background: rgba(255,255,255,0.98);
            padding: 48px 38px 36px 38px;
            border-radius: 22px;
            box-shadow: 0 8px 32px rgba(255,212,59,0.08), 0 2px 8px rgba(37,117,252,0.06);
            width: 100%;
            max-width: 480px;
            text-align: center;
            margin: 48px auto 0 auto;
            animation: fadeIn 1s ease;
            position: relative;
        }
        .edit-profile-box h2 {
            margin: 0 0 22px;
            color: #263238;
            font-weight: 700;
            letter-spacing: 1px;
        }
        .edit-profile-box input[type="text"],
        .edit-profile-box input[type="email"],
        .edit-profile-box input[type="number"] {
            width: 100%;
            padding: 13px 14px;
            margin: 18px 0 12px 0;
            border: 1.5px solid #bdbdbd;
            border-radius: 10px;
            font-size: 16px;
            background: #f7fafc;
            transition: border-color 0.2s;
        }
        .edit-profile-box input[type="text"]:focus,
        .edit-profile-box input[type="email"]:focus,
        .edit-profile-box input[type="number"]:focus {
            border-color: #ffe066;
            outline: none;
            background: #fffbe6;
        }
        .edit-profile-box input[readonly] {
            background: #f3f3f3;
            color: #888;
        }
        .edit-profile-box button,
        .edit-profile-box input[type="submit"] {
            background: linear-gradient(90deg, #ffe066 60%, #ffd43b 100%);
            color: #2575fc;
            padding: 13px 0;
            border: none;
            border-radius: 10px;
            font-size: 1.08rem;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            box-shadow: 0 2px 8px rgba(255,212,59,0.10);
            transition: background 0.2s, transform 0.2s;
            margin-top: 10px;
        }
        .edit-profile-box button:hover,
        .edit-profile-box input[type="submit"]:hover {
            background: linear-gradient(90deg, #ffd43b 60%, #ffe066 100%);
            color: #6a11cb;
            transform: translateY(-2px) scale(1.03);
        }
        .edit-profile-box a {
            display: block;
            margin-top: 18px;
            color: #2575fc;
            font-weight: bold;
            text-decoration: none;
            font-size: 15px;
            transition: color 0.2s;
        }
        .edit-profile-box a:hover {
            color: #0d47a1;
            text-decoration: underline;
        }
        .success-message {
            margin-bottom: 18px;
            color: #27ae60;
            font-weight: 600;
            font-size: 1.08rem;
            text-align: center;
        }
        .error-message {
            margin-bottom: 18px;
            color: #e74c3c;
            font-weight: 600;
            font-size: 1.08rem;
            text-align: center;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px);}
            to { opacity: 1; transform: translateY(0);}
        }
    </style>
</head>
<body>
    <div class="edit-profile-box">
        <h2>Edit Profile</h2>
        <?php echo $message; ?>
        <form method="POST" autocomplete="off">
            <input type="text" name="matric_number" placeholder="Matric Number" value="<?php echo htmlspecialchars($student['matric_number'] ?? ''); ?>" readonly>
            <input type="text" name="name" placeholder="Full Name" required value="<?php echo htmlspecialchars($student['name'] ?? ''); ?>">
            <input type="email" name="email" placeholder="Email" required value="<?php echo htmlspecialchars($student['email'] ?? ''); ?>">
            <input type="number" step="0.01" min="0" max="4" name="cgpa" placeholder="CGPA" required value="<?php echo htmlspecialchars($student['cgpa'] ?? ''); ?>">
            <input type="text" name="total_credits" placeholder="Total Credits" value="<?php echo htmlspecialchars($student['total_credits'] ?? ''); ?>" readonly>
            <input type="submit" name="update_profile" value="Update Profile">
        </form>
        <a href="dashboard.php">← Back to Dashboard</a>
    </div>
</body>
</html>
