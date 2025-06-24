<?php
include 'db.php';
session_start();
if (!isset($_SESSION['matric'])) { header("Location: login.php"); exit(); }

// Fetch all registered subjects for this student
$stmt = $conn->prepare("SELECT * FROM Table_Subjects WHERE matric_number = ?");
$stmt->execute([$_SESSION['matric']]);
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

$message = '';

// Helper functions
function get_grade($total) {
    if ($total < 30) return "E";
    if ($total < 35) return "D";
    if ($total < 40) return "D+";
    if ($total < 45) return "C-";
    if ($total < 50) return "C";
    if ($total < 55) return "C+";
    if ($total < 60) return "B-";
    if ($total < 65) return "B";
    if ($total < 75) return "B+";
    if ($total < 85) return "A-";
    return "A";
}
function get_value($grade) {
    switch ($grade) {
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

// Handle form submission
if (isset($_POST['save_targets'])) {
    $hasError = false;
    $errorMsg = '';
    foreach ($subjects as $sub) {
        $code = $sub['subject_code'];
        $carry = isset($_POST['carry_marks'][$code]) ? floatval($_POST['carry_marks'][$code]) : 0;
        $final = isset($_POST['final_target'][$code]) ? floatval($_POST['final_target'][$code]) : 0;

        // Restriction: Carry marks max 60, final target max 40
        if ($carry < 0 || $carry > 60) {
            $hasError = true;
            $errorMsg = "Carry marks for {$sub['subject_code']} must be between 0 and 60.";
            break;
        }
        if ($final < 0 || $final > 40) {
            $hasError = true;
            $errorMsg = "Final target for {$sub['subject_code']} must be between 0 and 40.";
            break;
        }
    }
    if ($hasError) {
        $message = "<div class='error-message'>{$errorMsg}</div>";
    } else {
        foreach ($subjects as $sub) {
            $code = $sub['subject_code'];
            $carry = isset($_POST['carry_marks'][$code]) ? floatval($_POST['carry_marks'][$code]) : 0;
            $final = isset($_POST['final_target'][$code]) ? floatval($_POST['final_target'][$code]) : 0;
            $total = $carry + $final;
            $grade = get_grade($total);
            $value = get_value($grade);

            // Check if target already exists
            $check = $conn->prepare("SELECT * FROM table_targets WHERE matric_number = ? AND subject_code = ?");
            $check->execute([$_SESSION['matric'], $code]);
            if ($check->fetch()) {
                // Update
                $stmt2 = $conn->prepare("UPDATE table_targets SET carry_marks=?, final_target=?, total_marks=?, grade=?, value=? WHERE matric_number=? AND subject_code=?");
                $stmt2->execute([$carry, $final, $total, $grade, $value, $_SESSION['matric'], $code]);
            } else {
                // Insert
                $stmt2 = $conn->prepare("INSERT INTO table_targets (matric_number, subject_code, carry_marks, final_target, total_marks, grade, value) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt2->execute([$_SESSION['matric'], $code, $carry, $final, $total, $grade, $value]);
            }
        }
        $message = "<div class='success-message'>Targets saved successfully!</div>";
    }
}

// Fetch existing targets for this student
$targets = [];
$stmt = $conn->prepare("SELECT * FROM table_targets WHERE matric_number = ?");
$stmt->execute([$_SESSION['matric']]);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $targets[$row['subject_code']] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Set Subject Target</title>
    <meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="/static/images/APlusTracker.ico">
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700&display=swap" rel="stylesheet">
    <style>
        body {
            background: #FFF9E5;
            font-family: 'Montserrat', sans-serif;
            margin: 0;
            min-height: 100vh;
        }
        .target-table-box {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 2px 8px rgba(255,212,59,0.07);
            padding: 32px 18px 24px 18px;
            max-width: 1100px;
            margin: 48px auto 0 auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
        }
        th, td {
            border: 1px solid #ffe066;
            padding: 10px 8px;
            text-align: center;
            font-size: 1rem;
        }
        th {
            background: #fffbe6;
            color: #2575fc;
            font-weight: 700;
        }
        input[type="number"] {
            width: 70px;
            padding: 4px 6px;
            border-radius: 6px;
            border: 1px solid #bdbdbd;
            background: #f7fafc;
            font-size: 1rem;
        }
        .success-message, .error-message {
            margin-bottom: 18px;
            text-align: center;
            font-weight: 600;
            font-size: 1.08rem;
        }
        .success-message { color: #27ae60; }
        .error-message { color: #e74c3c; }
        .back-link {
            display: inline-block;
            margin-top: 18px;
            color: #2575fc;
            font-weight: bold;
            text-decoration: none;
            font-size: 15px;
        }
        .back-link:hover {
            color: #0d47a1;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="target-table-box">
        <h2>Set Target for Each Subject</h2>
        <?php echo $message; ?>
        <form method="POST">
            <table>
                <thead>
                    <tr>
                        <th>Subject Code</th>
                        <th>Subject Name</th>
                        <th>Carry Marks<br>(/60)</th>
                        <th>Final Target<br>(/40)</th>
                        <th>Total Marks<br>(/100)</th>
                        <th>Grade</th>
                        <th>Value</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($subjects as $sub): 
                    $code = $sub['subject_code'];
                    $carry = $targets[$code]['carry_marks'] ?? '';
                    $final = $targets[$code]['final_target'] ?? '';
                    $total = ($carry !== '' && $final !== '') ? $carry + $final : '';
                    $grade = ($total !== '') ? get_grade($total) : '';
                    $value = ($grade !== '') ? get_value($grade) : '';
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($sub['subject_code']); ?></td>
                        <td><?php echo htmlspecialchars($sub['subject_name']); ?></td>
                        <td>
                            <input type="number" name="carry_marks[<?php echo $code; ?>]" min="0" max="60" step="0.01" value="<?php echo htmlspecialchars($carry); ?>">
                        </td>
                        <td>
                            <input type="number" name="final_target[<?php echo $code; ?>]" min="0" max="40" step="0.01" value="<?php echo htmlspecialchars($final); ?>">
                        </td>
                        <td>
                            <?php echo ($total !== '') ? number_format($total,2) : '-'; ?>
                        </td>
                        <td>
                            <?php echo ($grade !== '') ? $grade : '-'; ?>
                        </td>
                        <td>
                            <?php echo ($value !== '') ? number_format($value,2) : '-'; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <input type="submit" name="save_targets" value="Save Targets" style="margin-top:18px; background:#ffe066; color:#2575fc; border:none; border-radius:8px; padding:10px 28px; font-weight:600; font-size:1.08rem; cursor:pointer;">
        </form>
        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
    </div>
</body>
</html>
