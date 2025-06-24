<?php
// filepath: c:\xampp\htdocs\APlusTracker\template\input_result.php
session_start();
include 'db.php';

if (!isset($_SESSION['matric'])) {
    header("Location: login.php");
    exit();
}

$matric = $_SESSION['matric'];

// Fetch registered subjects for this student
$stmt = $conn->prepare("SELECT subject_code, subject_name, credit FROM Table_Subjects WHERE matric_number = ?");
$stmt->execute([$matric]);
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_results'])) {
    foreach ($subjects as $sub) {
        $code = $sub['subject_code'];
        $grade = $_POST['grade'][$code] ?? '';
        $value = gradeToValue($grade);

        // Save/update result in table_results (create if not exists)
        $stmtCheck = $conn->prepare("SELECT * FROM table_results WHERE matric_number = ? AND subject_code = ?");
        $stmtCheck->execute([$matric, $code]);
        if ($stmtCheck->fetch()) {
            // Update
            $stmtUpdate = $conn->prepare("UPDATE table_results SET grade = ?, value = ? WHERE matric_number = ? AND subject_code = ?");
            $stmtUpdate->execute([$grade, $value, $matric, $code]);
        } else {
            // Insert
            $stmtInsert = $conn->prepare("INSERT INTO table_results (matric_number, subject_code, grade, value) VALUES (?, ?, ?, ?)");
            $stmtInsert->execute([$matric, $code, $grade, $value]);
        }
    }
    $success = true;

    // Calculate actual GPA for current semester
    $stmtResults = $conn->prepare("SELECT value, credit FROM table_results r JOIN Table_Subjects s ON r.subject_code = s.subject_code WHERE r.matric_number = ? AND s.matric_number = ?");
    $stmtResults->execute([$matric, $matric]);
    $results = $stmtResults->fetchAll(PDO::FETCH_ASSOC);

    $total_points = 0;
    $total_credits = 0;
    foreach ($results as $row) {
        $total_points += floatval($row['value']) * intval($row['credit']);
        $total_credits += intval($row['credit']);
    }
    $actual_gpa = ($total_credits > 0) ? $total_points / $total_credits : 0;

    // Update actual_gpa in Table_Students
    $stmtUpdateGPA = $conn->prepare("UPDATE Table_Students SET actual_gpa = ? WHERE matric_number = ?");
    $stmtUpdateGPA->execute([round($actual_gpa, 2), $matric]);

    // Calculate new CGPA (using all credits and points so far)
    $stmtStudent = $conn->prepare("SELECT total_credits, cgpa FROM Table_Students WHERE matric_number = ?");
    $stmtStudent->execute([$matric]);
    $student = $stmtStudent->fetch(PDO::FETCH_ASSOC);

    $prev_total_credits = intval($student['total_credits']);
    $prev_cgpa = floatval($student['cgpa']);

    // Combine previous and current semester for CGPA
    $combined_points = ($prev_cgpa * $prev_total_credits) + ($actual_gpa * $total_credits);
    $combined_credits = $prev_total_credits + $total_credits;
    $new_cgpa = ($combined_credits > 0) ? $combined_points / $combined_credits : 0;

    // Update CGPA in Table_Students
    $stmtUpdateCGPA = $conn->prepare("UPDATE Table_Students SET cgpa = ? WHERE matric_number = ?");
    $stmtUpdateCGPA->execute([round($new_cgpa, 2), $matric]);
}

function gradeToValue($grade) {
    switch (strtoupper($grade)) {
        case "A": return 4.00;
        case "A-": return 3.67;
        case "B+": return 3.33;
        case "B": return 3.00;
        case "B-": return 2.67;
        case "C+": return 2.33;
        case "C": return 2.00;
        case "C-": return 1.67;
        case "D+": return 1.33;
        case "D": return 1.00;
        case "E": return 0.00;
        default: return 0.00;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Input Actual Result</title>
    <meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="../static/images/A+ Tracker.ico">
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700&display=swap" rel="stylesheet">
    <style>
        body {
            background: #FFF9E5;
            font-family: 'Montserrat', sans-serif;
            margin: 0;
            min-height: 100vh;
        }
        .container {
            max-width: 600px;
            margin: 40px auto 0 auto;
            padding: 0 20px;
        }
        .box {
            background: #fff;
            padding: 36px 40px 32px 40px;
            margin: 32px auto 0 auto;
            border-radius: 28px;
            box-shadow: 0 8px 32px rgba(255,212,59,0.08), 0 2px 8px rgba(37,117,252,0.06);
            max-width: 600px;
        }
        h2 {
            text-align: center;
            font-weight: 700;
            color: #2575fc;
            margin-bottom: 18px;
            letter-spacing: 1px;
        }
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 24px;
            background: #fffde7;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(255,212,59,0.04);
        }
        th, td {
            border: none;
            padding: 14px 10px;
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
        input[type="text"], input[type="number"] {
            padding: 8px;
            border: 1px solid #ffe066;
            border-radius: 8px;
            font-size: 1rem;
            background: #fffde7;
            width: 90px;
        }
        input[type="submit"] {
            background: linear-gradient(90deg, #ffe066 60%, #ffd43b 100%);
            color: #2575fc;
            border: none;
            border-radius: 10px;
            padding: 13px 32px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(255,212,59,0.10);
            transition: background 0.2s, transform 0.2s;
            margin-top: 24px;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }
        input[type="submit"]:hover {
            background: linear-gradient(90deg, #ffd43b 60%, #ffe066 100%);
            color: #6a11cb;
            transform: translateY(-2px) scale(1.03);
        }
        .success-message {
            color: #43e97b;
            text-align: center;
            font-size: 1.1rem;
            margin-bottom: 16px;
        }
        .error-message {
            color: #e74c3c;
            text-align: center;
            font-size: 1.1rem;
            margin-bottom: 16px;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 18px;
            color: #2575fc;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="box">
            <h2>Input Actual Result</h2>
            <?php if ($success): ?>
                <div class="success-message">Results saved successfully!</div>
            <?php elseif ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if (count($subjects) > 0): ?>
            <form method="POST">
                <table>
                    <thead>
                        <tr>
                            <th>Subject Code</th>
                            <th>Subject Name</th>
                            <th>Grade</th>
                            <th>Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subjects as $sub): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($sub['subject_code']); ?></td>
                            <td><?php echo htmlspecialchars($sub['subject_name']); ?></td>
                            <td>
                                <input type="text" name="grade[<?php echo htmlspecialchars($sub['subject_code']); ?>]" maxlength="2" placeholder="A" required
                                    oninput="setGradeValue(this)">
                            </td>
                            <td>
                                <input type="number" name="value[<?php echo htmlspecialchars($sub['subject_code']); ?>]" step="0.01" min="0" max="4" placeholder="4.00" required readonly>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <input type="submit" name="save_results" value="Save Results">
            </form>
            <?php else: ?>
                <div class="error-message">No registered subjects found.</div>
            <?php endif; ?>
            <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
        </div>
    </div>
    <script>
function setGradeValue(gradeInput) {
    const grade = gradeInput.value.trim().toUpperCase();
    let value = 0.00;
    switch (grade) {
        case "A": value = 4.00; break;
        case "A-": value = 3.67; break;
        case "B+": value = 3.33; break;
        case "B": value = 3.00; break;
        case "B-": value = 2.67; break;
        case "C+": value = 2.33; break;
        case "C": value = 2.00; break;
        case "C-": value = 1.67; break;
        case "D+": value = 1.33; break;
        case "D": value = 1.00; break;
        case "E": value = 0.00; break;
        default: value = 0.00;
    }
    // Find the value input in the same row
    const valueInput = gradeInput.parentElement.parentElement.querySelector('input[type="number"]');
    if (valueInput) valueInput.value = value.toFixed(2);
}
</script>
</body>
</html>