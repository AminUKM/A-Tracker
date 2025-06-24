<?php
include 'db.php';
session_start();
if (!isset($_SESSION['matric'])) { header("Location: Login.php"); exit(); }

$message = '';
if (isset($_POST['register_subject'])) {
    // Check if subject already registered for this student
    $check = $conn->prepare("SELECT 1 FROM Table_Subjects WHERE matric_number = ? AND subject_code = ?");
    $check->execute([$_SESSION['matric'], $_POST['subject_code']]);
    if ($check->fetch()) {
        $message = "<div class='error-message'>You have already registered this subject.</div>";
    } else {
        $stmt = $conn->prepare("INSERT INTO Table_Subjects (matric_number, subject_code, subject_name, credit, is_exam, exam_date, exam_time) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $success = $stmt->execute([
            $_SESSION['matric'],
            $_POST['subject_code'],
            $_POST['subject_name'],
            $_POST['credit'],
            $_POST['is_exam'],
            $_POST['exam_date'],
            $_POST['exam_time']
        ]);
        if ($success) {
            $message = "<div class='success-message'>Subject registered successfully!</div>";
        } else {
            $message = "<div class='error-message'>Failed to register subject. Please try again.</div>";
        }
    }
}

// Fetch subjects for this student
$stmt = $conn->prepare("SELECT * FROM Table_Subjects WHERE matric_number = ?");
$stmt->execute([$_SESSION['matric']]);
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate total credits
$total_credits = 0;
foreach ($subjects as $sub) {
    $total_credits += (int)$sub['credit'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Register Subject</title>
    <meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="/static/images/APlusTracker.ico">
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700&display=swap" rel="stylesheet">
    <style>
        :root {
            --main-color: #ffe066;
            --accent-color: #2575fc;
            --danger-color: #e74c3c;
            --bg-gradient: linear-gradient(120deg, #fffde7 0%, #fff9e5 100%);
            --box-shadow: 0 8px 32px rgba(255,212,59,0.08), 0 2px 8px rgba(37,117,252,0.06);
            --border-radius: 22px;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Montserrat', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--bg-gradient);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 40px 10px;
        }
        .register-box {
            background: rgba(255,255,255,0.98);
            padding: 48px 38px 36px 38px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            width: 100%;
            max-width: 540px;
            text-align: center;
            margin-bottom: 40px;
            animation: fadeIn 1s ease;
            position: relative;
        }
        .register-box img {
            width: 110px;
            margin-bottom: 18px;
            border-radius: 14px;
            box-shadow: 0 2px 8px rgba(37,117,252,0.07);
        }
        .register-box h2 {
            margin: 0 0 22px;
            color: #263238;
            font-weight: 700;
            letter-spacing: 1px;
        }
        .register-box input[type="text"],
        .register-box input[type="number"],
        .register-box input[type="date"],
        .register-box input[type="time"],
        .register-box select {
            width: 100%;
            padding: 13px 14px;
            margin: 18px 0 12px 0;
            border: 1.5px solid #bdbdbd;
            border-radius: 10px;
            font-size: 16px;
            background: #f7fafc;
            transition: border-color 0.2s;
        }
        .register-box input[type="text"]:focus,
        .register-box input[type="number"]:focus,
        .register-box input[type="date"]:focus,
        .register-box input[type="time"]:focus,
        .register-box select:focus {
            border-color: var(--main-color);
            outline: none;
            background: #fffbe6;
        }
        .register-box button,
        .register-box input[type="submit"] {
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
        .register-box button:hover,
        .register-box input[type="submit"]:hover {
            background: linear-gradient(90deg, #ffd43b 60%, #ffe066 100%);
            color: #6a11cb;
            transform: translateY(-2px) scale(1.03);
        }
        .register-box a {
            display: block;
            margin-top: 18px;
            color: var(--accent-color);
            font-weight: bold;
            text-decoration: none;
            font-size: 15px;
            transition: color 0.2s;
        }
        .register-box a:hover {
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
            color: var(--danger-color);
            font-weight: 600;
            font-size: 1.08rem;
            text-align: center;
        }
        table {
            width: 100%;
            max-width: 900px;
            border-collapse: separate;
            border-spacing: 0;
            background: #fff;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: var(--box-shadow);
            margin-bottom: 40px;
            animation: fadeIn 1s;
        }
        th, td {
            border: none;
            padding: 18px 12px;
            text-align: center;
            font-size: 1.08rem;
        }
        th {
            background: #fffbe6;
            color: #2575fc;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        tr:nth-child(even) td {
            background: #fff9e5;
        }
        tr:hover td {
            background: #ffe066;
            transition: background 0.2s;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px);}
            to { opacity: 1; transform: translateY(0);}
        }
        @media (max-width: 700px) {
            .register-box {
                padding: 28px 8vw;
            }
            .register-box img {
                width: 80px;
            }
            table, th, td {
                font-size: 0.98rem;
            }
        }
    </style>
</head>
<body>
    <div class="register-box">
        <img src="/static/images/APlusTracker.png" alt="Logo" class="logo">
        <h2>Register Subject</h2>
        <?php echo $message; ?>
        <form method="POST" autocomplete="off">
            <input type="text" name="subject_code" placeholder="Subject Code" required autocomplete="off">
            <input type="text" name="subject_name" placeholder="Subject Name" required autocomplete="off">
            <input type="number" name="credit" placeholder="Credit" required autocomplete="off">
            <div style="text-align:left; margin:18px 0 12px 0;">
                <label style="font-weight:600; color:#263238; display:block; margin-bottom:8px;">Is Exam:</label>
                <select name="is_exam" id="is_exam" required onchange="toggleExamFields()">
                    <option value="1">Yes</option>
                    <option value="0">No</option>
                </select>
            </div>
            <div id="examFields">
                <input type="date" name="exam_date" id="exam_date" placeholder="Exam Date">
                <input type="time" name="exam_time" id="exam_time" placeholder="Exam Time">
            </div>
            <input type="submit" name="register_subject" value="Register">
        </form>
        <a href="dashboard.php">← Back to Dashboard</a>
    </div>

    <?php if (count($subjects) > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Subject Code</th>
                <th>Subject Name</th>
                <th>Credit</th>
                <th>Exam Date</th>
                <th>Exam Time</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($subjects as $sub): ?>
            <tr>
                <td><?php echo htmlspecialchars($sub['subject_code']); ?></td>
                <td><?php echo htmlspecialchars($sub['subject_name']); ?></td>
                <td><?php echo htmlspecialchars($sub['credit']); ?></td>
                <td>
                    <?php
                        echo ($sub['is_exam'] && $sub['exam_date']) ? htmlspecialchars($sub['exam_date']) : '-';
                    ?>
                </td>
                <td>
                    <?php
                        echo ($sub['is_exam'] && $sub['exam_time']) ? htmlspecialchars($sub['exam_time']) : '-';
                    ?>
                </td>
                <td>
                    <div style="display: flex; gap: 8px; justify-content: center;">
                        <form method="POST" action="edit_subject.php" style="display:inline;">
                            <input type="hidden" name="subject_code" value="<?php echo htmlspecialchars($sub['subject_code']); ?>">
                            <button type="submit" style="background:#2575fc; color:#fff; border:none; border-radius:6px; padding:6px 18px; cursor:pointer; font-weight:600;">
                                Edit
                            </button>
                        </form>
                        <form method="POST" action="drop_subject.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to drop this subject?');">
                            <input type="hidden" name="subject_code" value="<?php echo htmlspecialchars($sub['subject_code']); ?>">
                            <button type="submit" style="background:#e74c3c; color:#fff; border:none; border-radius:6px; padding:6px 18px; cursor:pointer; font-weight:600;">
                                Drop
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <tr>
                <td colspan="2" style="text-align:right; font-weight:bold; color:#2575fc;">Total Credits:</td>
                <td style="font-weight:bold; color:#2575fc;"><?php echo $total_credits; ?></td>
                <td colspan="3"></td>
            </tr>
        </tbody>
    </table>
    <?php endif; ?>
<script>
function toggleExamFields() {
    var isExam = document.getElementById('is_exam').value;
    var examFields = document.getElementById('examFields');
    if (isExam === "1") {
        examFields.style.display = "block";
        document.getElementById('exam_date').required = false;
        document.getElementById('exam_time').required = false;
    } else {
        examFields.style.display = "none";
        document.getElementById('exam_date').value = "";
        document.getElementById('exam_time').value = "";
    }
}
// Initialize on page load
window.onload = function() {
    toggleExamFields();
};
</script>
</body>
</html>
