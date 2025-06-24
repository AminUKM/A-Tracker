<?php
include 'db.php';
session_start();
if (!isset($_SESSION['matric'])) { header("Location: login.php"); exit(); }

// Get subject_code from POST or GET
if (isset($_POST['subject_code'])) {
    $subject_code = $_POST['subject_code'];
} elseif (isset($_GET['subject_code'])) {
    $subject_code = $_GET['subject_code'];
} else {
    header("Location: register_subject.php");
    exit();
}

// Fetch subject details
$stmt = $conn->prepare("SELECT * FROM Table_Subjects WHERE matric_number = ? AND subject_code = ?");
$stmt->execute([$_SESSION['matric'], $subject_code]);
$subject = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$subject) {
    header("Location: register_subject.php");
    exit();
}

$message = '';
if (isset($_POST['update_subject'])) {
    $stmt = $conn->prepare("UPDATE Table_Subjects SET subject_name = ?, credit = ?, is_exam = ?, exam_date = ?, exam_time = ? WHERE matric_number = ? AND subject_code = ?");
    $success = $stmt->execute([
        $_POST['subject_name'],
        $_POST['credit'],
        $_POST['is_exam'],
        $_POST['exam_date'],
        $_POST['exam_time'],
        $_SESSION['matric'],
        $subject_code
    ]);
    if ($success) {
        $message = "<div class='success-message'>Subject updated successfully!</div>";
        // Refresh subject data
        $stmt = $conn->prepare("SELECT * FROM Table_Subjects WHERE matric_number = ? AND subject_code = ?");
        $stmt->execute([$_SESSION['matric'], $subject_code]);
        $subject = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $message = "<div class='error-message'>Failed to update subject. Please try again.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Edit Subject</title>
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
        .edit-box {
            background: rgba(255,255,255,0.98);
            padding: 48px 38px 36px 38px;
            border-radius: 22px;
            box-shadow: 0 8px 32px rgba(255,212,59,0.08), 0 2px 8px rgba(37,117,252,0.06);
            width: 100%;
            max-width: 540px;
            text-align: center;
            margin: 48px auto 0 auto;
            animation: fadeIn 1s ease;
            position: relative;
        }
        .edit-box h2 {
            margin: 0 0 22px;
            color: #263238;
            font-weight: 700;
            letter-spacing: 1px;
        }
        .edit-box input[type="text"],
        .edit-box input[type="number"],
        .edit-box input[type="date"],
        .edit-box input[type="time"],
        .edit-box select {
            width: 100%;
            padding: 13px 14px;
            margin: 18px 0 12px 0;
            border: 1.5px solid #bdbdbd;
            border-radius: 10px;
            font-size: 16px;
            background: #f7fafc;
            transition: border-color 0.2s;
        }
        .edit-box input[type="text"]:focus,
        .edit-box input[type="number"]:focus,
        .edit-box input[type="date"]:focus,
        .edit-box input[type="time"]:focus,
        .edit-box select:focus {
            border-color: #ffe066;
            outline: none;
            background: #fffbe6;
        }
        .edit-box button,
        .edit-box input[type="submit"] {
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
        .edit-box button:hover,
        .edit-box input[type="submit"]:hover {
            background: linear-gradient(90deg, #ffd43b 60%, #ffe066 100%);
            color: #6a11cb;
            transform: translateY(-2px) scale(1.03);
        }
        .edit-box a {
            display: block;
            margin-top: 18px;
            color: #2575fc;
            font-weight: bold;
            text-decoration: none;
            font-size: 15px;
            transition: color 0.2s;
        }
        .edit-box a:hover {
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
    <div class="edit-box">
        <h2>Edit Subject</h2>
        <?php echo $message; ?>
        <form method="POST" autocomplete="off">
            <input type="hidden" name="subject_code" value="<?php echo htmlspecialchars($subject['subject_code']); ?>">
            <input type="text" name="subject_code_display" placeholder="Subject Code" value="<?php echo htmlspecialchars($subject['subject_code']); ?>" readonly style="background:#f3f3f3; color:#888;">
            <input type="text" name="subject_name" placeholder="Subject Name" value="<?php echo htmlspecialchars($subject['subject_name']); ?>" readonly style="background:#f3f3f3; color:#888;">
            <input type="number" name="credit" placeholder="Credit" required autocomplete="off" value="<?php echo htmlspecialchars($subject['credit']); ?>">
            <div style="text-align:left; margin:18px 0 12px 0;">
                <label style="font-weight:600; color:#263238; display:block; margin-bottom:8px;">Is Exam:</label>
                <select name="is_exam" id="is_exam" required onchange="toggleExamFields()">
                    <option value="1" <?php if ($subject['is_exam']) echo 'selected'; ?>>Yes</option>
                    <option value="0" <?php if (!$subject['is_exam']) echo 'selected'; ?>>No</option>
                </select>
            </div>
            <div id="examFields">
                <input type="date" name="exam_date" id="exam_date" placeholder="Exam Date" value="<?php echo htmlspecialchars($subject['exam_date']); ?>">
                <input type="time" name="exam_time" id="exam_time" placeholder="Exam Time" value="<?php echo htmlspecialchars($subject['exam_time']); ?>">
            </div>
            <input type="submit" name="update_subject" value="Update">
        </form>
        <a href="register_subject.php">← Back to Register Subject</a>
    </div>
    <script>
    function toggleExamFields() {
        var isExam = document.getElementById('is_exam').value;
        var examFields = document.getElementById('examFields');
        if (isExam === "1") {
            examFields.style.display = "block";
            document.getElementById('exam_date').disabled = false;
            document.getElementById('exam_time').disabled = false;
        } else {
            examFields.style.display = "none";
            document.getElementById('exam_date').value = "";
            document.getElementById('exam_time').value = "";
            document.getElementById('exam_date').disabled = true;
            document.getElementById('exam_time').disabled = true;
        }
    }
    // Initialize on page load
    window.onload = function() {
        toggleExamFields();
    };
    </script>
</body>
</html>
