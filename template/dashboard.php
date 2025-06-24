<?php
include 'db.php';
session_start();
if (!isset($_SESSION['matric'])) { header("Location: login.php"); exit(); }

// Fetch student name
$stmtName = $conn->prepare("SELECT name FROM Table_Students WHERE matric_number = ?");
$stmtName->execute([$_SESSION['matric']]);
$student_name = $stmtName->fetchColumn();

// Fetch registered subjects
$stmt = $conn->prepare("SELECT * FROM Table_Subjects WHERE matric_number = ?");
$stmt->execute([$_SESSION['matric']]);
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch CGPA (example: from Table_Students)
$stmtCGPA = $conn->prepare("SELECT cgpa FROM Table_Students WHERE matric_number = ?");
$stmtCGPA->execute([$_SESSION['matric']]);
$cgpa = $stmtCGPA->fetchColumn();

// Calculate total credits
$total_credits = 0;
foreach ($subjects as $sub) {
    $total_credits += (int)$sub['credit'];
}

// Fetch set targets for this student
$targets = [];
$stmt = $conn->prepare("SELECT * FROM table_targets WHERE matric_number = ?");
$stmt->execute([$_SESSION['matric']]);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $targets[$row['subject_code']] = $row;
}

// GPA & CGPA calculation (after your target table logic)
$total_credits_for_gpa = 0;
$total_weighted_points = 0;
foreach ($subjects as $sub) {
    $code = $sub['subject_code'];
    $credit = $sub['credit'];
    $value = isset($targets[$code]['value']) ? floatval($targets[$code]['value']) : 0;
    $total_credits_for_gpa += $credit;
    $total_weighted_points += $credit * $value;
}
$gpa = ($total_credits_for_gpa > 0) ? $total_weighted_points / $total_credits_for_gpa : 0;

// For CGPA, you may use $cgpa from database, or use $gpa if you want to show only current semester
$cgpa_display = isset($cgpa) ? floatval($cgpa) : $gpa;

// Example: Fetch previous CGPA and total credits (use current cgpa and total_credits from Table_Students)
$stmtPrev = $conn->prepare("SELECT cgpa, total_credits FROM Table_Students WHERE matric_number = ?");
$stmtPrev->execute([$_SESSION['matric']]);
$rowPrev = $stmtPrev->fetch(PDO::FETCH_ASSOC);
$prev_cgpa = $rowPrev['cgpa'] ?? 0;
$prev_total_credits = $rowPrev['total_credits'] ?? 0;

// Calculate current semester credits
$current_semester_credits = $total_credits;

// Calculate Target CGPA
$numerator = ($prev_cgpa * $prev_total_credits) + ($gpa * $current_semester_credits);
$denominator = $prev_total_credits + $current_semester_credits;
$target_cgpa = ($denominator > 0) ? $numerator / $denominator : 0;

// Save target_cgpa and gpa to Table_Students
$stmtSave = $conn->prepare("UPDATE Table_Students SET target_cgpa = ?, target_gpa = ? WHERE matric_number = ?");
$stmtSave->execute([$target_cgpa, $gpa, $_SESSION['matric']]);
$total_credits_combined = $prev_total_credits + $total_credits;

// Fetch actual_gpa and cgpa from Table_Students
$stmtGPA = $conn->prepare("SELECT actual_gpa, cgpa FROM Table_Students WHERE matric_number = ?");
$stmtGPA->execute([$_SESSION['matric']]);
$rowGPA = $stmtGPA->fetch(PDO::FETCH_ASSOC);
$actual_gpa = isset($rowGPA['actual_gpa']) ? floatval($rowGPA['actual_gpa']) : 0;
$cgpa = isset($rowGPA['cgpa']) ? floatval($rowGPA['cgpa']) : 0;

// GPA color logic (same as target)
$actualGpaColor = "#e74c3c";
if ($actual_gpa >= 3.68) {
    $actualGpaColor = "#43e97b";
} elseif ($actual_gpa >= 3.51) {
    $actualGpaColor = "#2575fc";
} elseif ($actual_gpa >= 3.00) {
    $actualGpaColor = "#ffe066";
}

// CGPA color logic (same as target)
$cgpaColor = "#e74c3c";
if ($cgpa >= 3.68) {
    $cgpaColor = "#43e97b";
} elseif ($cgpa >= 3.51) {
    $cgpaColor = "#2575fc";
} elseif ($cgpa >= 3.00) {
    $cgpaColor = "#ffe066";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Dashboard</title>
    <meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="/static/images/APlusTracker.ico">
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background: #FFF9E5;
            font-family: 'Montserrat', sans-serif;
            margin: 0;
            min-height: 100vh;
        }
        nav {
            background: linear-gradient(90deg, #ffe066 60%, #ffd43b 100%);
            color: #2575fc;
            padding: 18px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 0 0 24px 24px;
            box-shadow: 0 4px 24px rgba(255,212,59,0.10);
        }
        nav .logo-container {
            display: flex;
            align-items: center;
        }
        nav .logo-container img {
            height: 60px;
            margin-right: 14px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
        }
        nav strong {
            font-size: 2rem;
            letter-spacing: 2px;
        }
        nav a {
            color: #2575fc;
            margin-left: 28px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            transition: color 0.2s;
            padding: 8px 16px;
            border-radius: 8px;
        }
        nav a:hover {
            background: rgba(255,255,255,0.13);
            color: #6a11cb;
        }
        nav span {
            margin-right: 18px;
            font-size: 1.1rem;
            font-weight: 500;
        }
        .container {
            max-width: 900px;
            margin: 40px auto 0 auto;
            padding: 0 20px;
        }
        .box {
            background: rgba(255,255,255,0.98);
            padding: 36px 40px 32px 40px;
            margin: 32px auto 0 auto;
            border-radius: 28px;
            box-shadow: 0 8px 32px rgba(255,212,59,0.08), 0 2px 8px rgba(37,117,252,0.06);
            max-width: 900px;
        }
        h2, h3 {
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
        .print-btn-container {
            text-align: center;
            margin-top: 28px;
        }
        .print-btn-container button {
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
        }
        .print-btn-container button:hover {
            background: linear-gradient(90deg, #ffd43b 60%, #ffe066 100%);
            color: #6a11cb;
            transform: translateY(-2px) scale(1.03);
        }
        .register-button-container {
            display: flex;
            justify-content: center;
            gap: 18px;
            margin: 38px 0 0 0;
        }
        .register-button-container button {
            max-width: 260px;
            width: 100%;
            cursor: pointer;
            background: linear-gradient(90deg, #ffe066 60%, #ffd43b 100%);
            color: #2575fc;
            padding: 14px 0;
            border: none;
            border-radius: 12px;
            font-size: 1.08rem;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(255,212,59,0.10);
            transition: background 0.2s, transform 0.2s;
        }
        .register-button-container button:hover {
            background: linear-gradient(90deg, #ffd43b 60%, #ffe066 100%);
            color: #6a11cb;
            transform: translateY(-2px) scale(1.03);
        }
        canvas {
            display: block;
            margin: 38px auto 0 auto;
            max-width: 340px;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 2px 8px rgba(255,212,59,0.07);
            padding: 18px;
        }
        .charts-row {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            gap: 40px;
            margin-top: 38px;
            flex-wrap: wrap;
        }
        .chart-container {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 2px 8px rgba(255,212,59,0.07);
            padding: 18px;
            max-width: 360px;
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        @media (max-width: 700px) {
            .box, .container {
                padding: 16px 4vw;
            }
            nav {
                flex-direction: column;
                align-items: flex-start;
                padding: 18px 10px;
            }
            nav .logo-container img {
                height: 44px;
            }
            .register-button-container {
                flex-direction: column;
                gap: 12px;
            }
            .charts-row {
                flex-direction: column;
                align-items: center;
                gap: 24px;
            }
        }
    </style>
</head>
<body>
    <nav>
        <div class="logo-container">
            <img src="/static/images/APlusTracker.png" alt="Logo" class="logo">
            <strong>A+ Tracker</strong>
        </div>
        <div>
            <span>Welcome, <?php echo htmlspecialchars($student_name); ?>!</span>
            <a href="dashboard.php">Dashboard</a>
            <a href="edit_profile.php" style="background:#2575fc; color:#fff; margin-left:10px;">Edit Profile</a>
            <a href="#" onclick="logoutWithMessage(event)">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="box">
            <h2>Your Registered Subjects</h2>
            <?php if (count($subjects) > 0): ?>
            <table id="subjectTable">
                <thead>
                    <tr>
                        <th>Subject Code</th>
                        <th>Subject Name</th>
                        <th>Credit</th>
                        <th>Exam Date</th>
                        <th>Exam Time</th>
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
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="print-btn-container">
                <button onclick="printSubjectTable()">
                    <svg xmlns="http://www.w3.org/2000/svg" style="vertical-align:middle;margin-right:8px;" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M2 7a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v-2a1 1 0 0 1 1-1h8a1 1 0 0 1 1 1v2h1a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2H2zm11 5v2H3v-2h10zm-1-7V2a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v3h10z"/></svg>
                    Print Subject Table
                </button>
            </div>
            <?php else: ?>
                <p style="text-align:center; color:#555; font-size:1.1rem;">You have no subjects registered yet. <a href="register_subject.php">Register now</a></p>
            <?php endif; ?>

            <!-- Replace the chart section with this layout for side-by-side display -->
            <div class="charts-row">
                <div class="chart-container">
                    <canvas id="cgpaChart" width="340" height="340"></canvas>
                </div>
                <div class="chart-container">
                    <canvas id="creditChart" width="340" height="340"></canvas>
                </div>
                <div class="chart-container">
                    <canvas id="allCreditChart" width="340" height="340"></canvas>
                </div>
            </div>
            <?php if ($total_credits_combined >= 100): ?>
                <div style="text-align:center; color:#43e97b; font-size:1.2rem; font-weight:600; margin-top:12px;">
                    Congratulations! You are close to graduate.
                </div>
            <?php endif; ?>
        </div>

        <div class="box">
            <h2>Your Target Marks</h2>
            <?php if (count($targets) > 0): ?>
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
                        $target = $targets[$code] ?? null;
                        $carry = $target['carry_marks'] ?? 0;
                        $final = $target['final_target'] ?? 0;
                        $total = $target['total_marks'] ?? ($carry + $final);
                        $grade = $target['grade'] ?? '-';
                        $value = isset($target['value']) ? number_format($target['value'],2) : '-';

                        // Carry marks color
                        if ($carry <= 20) {
                            $carryColor = "#e74c3c"; // red
                        } elseif ($carry <= 30) {
                            $carryColor = "#ffa500"; // orange
                        } elseif ($carry <= 40) {
                            $carryColor = "#2575fc"; // blue
                        } else {
                            $carryColor = "#43e97b"; // green
                        }

                        // Final target color
                        if ($final <= 20) {
                            $finalColor = "#e74c3c"; // red
                        } elseif ($final <= 30) {
                            $finalColor = "#2575fc"; // blue
                        } else {
                            $finalColor = "#43e97b"; // green
                        }

                        // Total marks color
                        if ($total <= 30) {
                            $totalColor = "#e74c3c"; // red
                        } elseif ($total <= 60) {
                            $totalColor = "#ffa500"; // orange
                        } elseif ($total <= 90) {
                            $totalColor = "#2575fc"; // blue
                        } else {
                            $totalColor = "#43e97b"; // green
                        }
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($sub['subject_code']); ?></td>
                        <td><?php echo htmlspecialchars($sub['subject_name']); ?></td>
                        <td>
                            <div style="width:100px; background:#eee; border-radius:8px; overflow:hidden; display:inline-block;">
                                <div style="
                                    width:<?php echo ($carry > 0) ? min(100, ($carry/60)*100).'%' : '8px'; ?>;
                                    background:<?php echo $carryColor; ?>;
                                    height:18px;">
                                </div>
                            </div>
                            <span style="margin-left:8px;"><?php echo number_format($carry,2); ?> / 60</span>
                        </td>
                        <td>
                            <div style="width:100px; background:#eee; border-radius:8px; overflow:hidden; display:inline-block;">
                                <div style="
                                    width:<?php echo ($final > 0) ? min(100, ($final/40)*100).'%' : '8px'; ?>;
                                    background:<?php echo $finalColor; ?>;
                                    height:18px;">
                                </div>
                            </div>
                            <span style="margin-left:8px;"><?php echo number_format($final,2); ?> / 40</span>
                        </td>
                        <td>
                            <div style="width:100px; background:#eee; border-radius:8px; overflow:hidden; display:inline-block;">
                                <div style="
                                    width:<?php echo ($total > 0) ? min(100, ($total/100)*100).'%' : '8px'; ?>;
                                    background:<?php echo $totalColor; ?>;
                                    height:18px;">
                                </div>
                            </div>
                            <span style="margin-left:8px;"><?php echo number_format($total,2); ?> / 100</span>
                        </td>
                        <td><?php echo htmlspecialchars($grade); ?></td>
                        <td><?php echo $value; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <p style="text-align:center; color:#555; font-size:1.1rem;">No target marks set yet. <a href="set_target.php">Set your targets now</a></p>
            <?php endif; ?>

            <?php
            // GPA (Based on Target) color logic
            $gpaColor = "#e74c3c"; // default red
            if ($gpa >= 3.68) {
                $gpaColor = "#43e97b"; // green
            } elseif ($gpa >= 3.51) {
                $gpaColor = "#2575fc"; // blue
            } elseif ($gpa >= 3.00) {
                $gpaColor = "#ffe066"; // yellow
            }

            // Target CGPA (Projected) color logic
            $targetCgpaColor = "#e74c3c"; // default red
            if ($target_cgpa >= 3.68) {
                $targetCgpaColor = "#43e97b"; // green
            } elseif ($target_cgpa >= 3.51) {
                $targetCgpaColor = "#2575fc"; // blue
            } elseif ($target_cgpa >= 3.00) {
                $targetCgpaColor = "#ffe066"; // yellow
            }
            ?>
            <!-- GPA (Based on Target) Progress Bar -->
            <div style="margin:32px 0 0 0; text-align:center;">
                <div style="font-size:1.15rem; font-weight:600; color:#2575fc; margin-bottom:10px;">
                    GPA (Based on Target): <?php echo number_format($gpa, 2); ?> / 4.00
                </div>
                <div style="width:320px; max-width:90vw; margin:0 auto 18px auto; background:#eee; border-radius:12px; overflow:hidden; height:28px; box-shadow:0 2px 8px rgba(37,117,252,0.07);">
                    <div style="
                        width:<?php echo min(100, ($gpa/4)*100); ?>%;
                        background: <?php echo $gpaColor; ?>;
                        height:28px;
                        border-radius:12px;
                        transition:width 0.5s;">
                    </div>
                </div>
                <?php if ($gpa >= 3.66): ?>
                    <div style="color:#43e97b; font-size:1.1rem; font-weight:600; margin-top:10px;">
                        Congratulations! Dean List!
                    </div>
                <?php else: ?>
                    <div style="color:#e74c3c; font-size:1.1rem; font-weight:600; margin-top:10px;">
                        Try Again Next Semester
                    </div>
                <?php endif; ?>
            </div>

            <!-- Target CGPA (Projected) Progress Bar -->
            <div style="margin:32px 0 0 0; text-align:center;">
                <div style="font-size:1.15rem; font-weight:600; color:#2575fc; margin-bottom:10px;">
                    Target CGPA (Projected): <?php echo number_format($target_cgpa, 2); ?> / 4.00
                </div>
                <div style="width:320px; max-width:90vw; margin:0 auto 18px auto; background:#eee; border-radius:12px; overflow:hidden; height:28px; box-shadow:0 2px 8px rgba(37,117,252,0.07);">
                    <div style="
                        width:<?php echo min(100, ($target_cgpa/4)*100); ?>%;
                        background: <?php echo $targetCgpaColor; ?>;
                        height:28px;
                        border-radius:12px;
                        transition:width 0.5s;">
                    </div>
                </div>
            </div>
        </div>

        <div class="box">
            <h2>Your Actual Results</h2>
            <?php
            // Fetch actual results for this student
            $stmtResults = $conn->prepare(
                "SELECT s.subject_code, s.subject_name, s.credit, r.grade, r.value
                 FROM Table_Subjects s
                 LEFT JOIN table_results r ON s.subject_code = r.subject_code AND r.matric_number = s.matric_number
                 WHERE s.matric_number = ?"
            );
            $stmtResults->execute([$_SESSION['matric']]);
            $actualResults = $stmtResults->fetchAll(PDO::FETCH_ASSOC);
            ?>
            <?php if (count($actualResults) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Subject Code</th>
                        <th>Subject Name</th>
                        <th>Credit</th>
                        <th>Grade</th>
                        <th>Value</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($actualResults as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['subject_code']); ?></td>
                        <td><?php echo htmlspecialchars($row['subject_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['credit']); ?></td>
                        <td><?php echo htmlspecialchars($row['grade'] ?? '-'); ?></td>
                        <td><?php echo isset($row['value']) ? number_format($row['value'], 2) : '-'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <p style="text-align:center; color:#555; font-size:1.1rem;">No actual results entered yet.</p>
            <?php endif; ?>
        </div>

        <!-- GPA (Actual) Progress Bar -->
        <div style="margin:32px 0 0 0; text-align:center;">
            <div style="font-size:1.15rem; font-weight:600; color:#2575fc; margin-bottom:10px;">
                GPA (Actual): <?php echo number_format($actual_gpa, 2); ?> / 4.00
            </div>
            <div style="width:320px; max-width:90vw; margin:0 auto 18px auto; background:#eee; border-radius:12px; overflow:hidden; height:28px; box-shadow:0 2px 8px rgba(37,117,252,0.07);">
                <div class="progress-animate"
                    data-value="<?php echo min(100, ($actual_gpa/4)*100); ?>"
                    data-color="<?php echo $actualGpaColor; ?>"
                    style="width:0%; background:<?php echo $actualGpaColor; ?>; height:28px; border-radius:12px;">
                </div>
            </div>
        </div>

        <!-- CGPA Progress Bar -->
        <div style="margin:32px 0 0 0; text-align:center;">
            <div style="font-size:1.15rem; font-weight:600; color:#2575fc; margin-bottom:10px;">
                CGPA: <?php echo number_format($cgpa, 2); ?> / 4.00
            </div>
            <div style="width:320px; max-width:90vw; margin:0 auto 18px auto; background:#eee; border-radius:12px; overflow:hidden; height:28px; box-shadow:0 2px 8px rgba(37,117,252,0.07);">
                <div style="
                    width:<?php echo min(100, ($cgpa/4)*100); ?>%;
                    background: <?php echo $cgpaColor; ?>;
                    height:28px;
                    border-radius:12px;
                    transition:width 0.5s;">
                </div>
            </div>
        </div>

        <div class="register-button-container">
            <button type="button" onclick="window.location.href='register_subject.php'">
                Subject Registration
            </button>
            <button type="button" onclick="window.location.href='set_target.php'">
                Set Target
            </button>
            <button type="button" onclick="window.location.href='input_result.php'">
                Input Actual Result
            </button>
        </div>

        <!-- Add this button below your register-button-container or wherever appropriate -->
        <div class="print-btn-container" style="margin-top:32px;">
            <button style="background:#e74c3c; color:#fff;" onclick="showDeleteProfileModal()">Delete Profile</button>
        </div>

        <!-- Modal for delete confirmation -->
        <div id="deleteProfileModal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.35); z-index:9999; align-items:center; justify-content:center;">
            <div style="background:#fff; border-radius:16px; padding:32px 28px; max-width:350px; margin:auto; box-shadow:0 4px 24px rgba(0,0,0,0.13); text-align:center;">
                <h3 style="color:#e74c3c;">Delete Profile</h3>
                <p style="margin-bottom:18px;">Type <b>DELETE</b> to confirm profile deletion.<br>This action cannot be undone.</p>
                <input type="text" id="deleteConfirmInput" style="width:90%;padding:8px 10px;border-radius:8px;border:1px solid #ccc;margin-bottom:16px;">
                <br>
                <button onclick="confirmDeleteProfile()" style="background:#e74c3c; color:#fff; padding:8px 22px; border:none; border-radius:8px; font-weight:600;">Delete</button>
                <button onclick="closeDeleteProfileModal()" style="margin-left:10px; background:#eee; color:#333; padding:8px 22px; border:none; border-radius:8px;">Cancel</button>
                <div id="deleteProfileError" style="color:#e74c3c; margin-top:10px; font-size:1rem;"></div>
            </div>
        </div>
    </div>

    <script>
        const cgpa = <?php echo json_encode((float)$cgpa); ?>;
        const remaining = Math.max(0, 4.00 - cgpa);

        // Set CGPA donut color based on value
        let cgpaColor = "#e74c3c"; // default red
        if (cgpa >= 3.67) {
            cgpaColor = "#43e97b"; // green
        } else if (cgpa >= 3.51) {
            cgpaColor = "#2575fc"; // blue
        } else if (cgpa >= 3.00) {
            cgpaColor = "#ffe066"; // yellow
        }

        const ctx = document.getElementById("cgpaChart").getContext("2d");

        new Chart(ctx, {
            type: "doughnut",
            data: {
                labels: ["Your CGPA", "Remaining"],
                datasets: [{
                    data: [cgpa, remaining],
                    backgroundColor: [
                        cgpaColor, // Dynamic color for CGPA
                        "#e0e0e0"  // Light gray for remaining
                    ],
                    borderWidth: 0,
                    hoverOffset: 16,
                    borderRadius: 18,
                    cutout: "75%"
                }]
            },
            options: {
                responsive: true,
                animation: {
                    animateRotate: true,
                    animateScale: true,
                    duration: 1800,
                    easing: 'easeOutBounce'
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: {
                            color: "#2575fc",
                            font: {
                                size: 16,
                                weight: "bold",
                                family: "'Montserrat', 'Segoe UI', sans-serif"
                            },
                            padding: 24,
                            boxWidth: 24
                        }
                    },
                    title: {
                        display: true,
                        text: `CGPA Summary: ${cgpa.toFixed(2)} / 4.00`,
                        color: "#2575fc",
                        font: {
                            size: 22,
                            weight: "bold",
                            family: "'Montserrat', 'Segoe UI', sans-serif"
                        },
                        padding: {
                            top: 18,
                            bottom: 18
                        }
                    },
                    tooltip: {
                        enabled: true,
                        backgroundColor: "#263238",
                        titleColor: "#fff",
                        bodyColor: "#fff",
                        borderColor: cgpaColor,
                        borderWidth: 2,
                        padding: 14,
                        caretSize: 8,
                        cornerRadius: 10,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                if (context.label === "Your CGPA") {
                                    return `Your CGPA: ${cgpa.toFixed(2)}`;
                                } else {
                                    return `Remaining: ${(4.00 - cgpa).toFixed(2)}`;
                                }
                            }
                        }
                    }
                }
            },
            plugins: [{
                id: 'centerText',
                afterDraw: chart => {
                    const {ctx, chartArea: {width, height}} = chart;
                    ctx.save();
                    ctx.font = "bold 2.6rem 'Montserrat', 'Segoe UI', sans-serif";
                    ctx.fillStyle = cgpaColor;
                    ctx.textAlign = "center";
                    ctx.textBaseline = "middle";
                    ctx.fillText(cgpa.toFixed(2), width / 2, height / 2 + 5);
                    ctx.restore();
                }
            }]
        });

        // Total Credits Donut Chart (Current Semester)
        const totalCredits = <?php echo (int)$total_credits; ?>;
        const maxCredits = 20;
        const creditsRemaining = Math.max(0, maxCredits - totalCredits);

        const ctxCredit = document.getElementById("creditChart").getContext("2d");

        new Chart(ctxCredit, {
            type: "doughnut",
            data: {
                labels: ["Current Semester Credits", "Remaining"],
                datasets: [{
                    data: [totalCredits, creditsRemaining],
                    backgroundColor: [
                        "#2575fc", // Blue for credits
                        "#e0e0e0"  // Light gray for remaining
                    ],
                    borderWidth: 0,
                    hoverOffset: 16,
                    borderRadius: 18,
                    cutout: "75%"
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: true, position: 'bottom' },
                    title: {
                        display: true,
                        text: `Current Semester Credits: ${totalCredits} / ${maxCredits}`,
                        color: "#2575fc",
                        font: { size: 22, weight: "bold", family: "'Montserrat', 'Segoe UI', sans-serif" }
                    }
                }
            },
            plugins: [{
                id: 'centerTextCredit',
                afterDraw: chart => {
                    const {ctx, chartArea: {width, height}} = chart;
                    ctx.save();
                    ctx.font = "bold 2.2rem 'Montserrat', 'Segoe UI', sans-serif";
                    ctx.fillStyle = "#2575fc";
                    ctx.textAlign = "center";
                    ctx.textBaseline = "middle";
                    ctx.fillText(totalCredits + " / " + maxCredits, width / 2, height / 2 + 5);
                    ctx.restore();
                }
            }]
        });

        // All Credits Donut Chart (Previous + Current)
        const totalCreditsCombined = <?php echo (int)$total_credits_combined; ?>;
        const gradCredits = 120; // Graduation requirement
        const creditsAllRemaining = Math.max(0, gradCredits - totalCreditsCombined);

        const ctxAllCredit = document.getElementById("allCreditChart").getContext("2d");

        new Chart(ctxAllCredit, {
            type: "doughnut",
            data: {
                labels: ["Total Credits (All Sem)", "Remaining"],
                datasets: [{
                    data: [totalCreditsCombined, creditsAllRemaining],
                    backgroundColor: [
                        "#43e97b", // Green for all credits
                        "#e0e0e0"  // Light gray for remaining
                    ],
                    borderWidth: 0,
                    hoverOffset: 16,
                    borderRadius: 18,
                    cutout: "75%"
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: true, position: 'bottom' },
                    title: {
                        display: true,
                        text: `Total Credits (All Sem): ${totalCreditsCombined} / ${gradCredits}`,
                        color: "#2575fc",
                        font: { size: 22, weight: "bold", family: "'Montserrat', 'Segoe UI', sans-serif" }
                    }
                }
            },
            plugins: [{
                id: 'centerTextAllCredit',
                afterDraw: chart => {
                    const {ctx, chartArea: {width, height}} = chart;
                    ctx.save();
                    ctx.font = "bold 2.2rem 'Montserrat', 'Segoe UI', sans-serif";
                    ctx.fillStyle = "#43e97b";
                    ctx.textAlign = "center";
                    ctx.textBaseline = "middle";
                    ctx.fillText(totalCreditsCombined + " / " + gradCredits, width / 2, height / 2 + 5);
                    ctx.restore();
                }
            }]
        });

        function printSubjectTable() {
            const table = document.getElementById('subjectTable').outerHTML;
            const win = window.open('', '', 'width=800,height=600');
            win.document.write(`
                <html>
                <head>
                    <title>Print Subject Table</title>
                    <style>
                        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                        th, td { border: 1px solid #ccc; padding: 12px; text-align: center; border-radius: 10px; }
                        th { background-color: #f2f2f2; }
                        body { font-family: 'Segoe UI', sans-serif; padding: 40px; }
                    </style>
                </head>
                <body>
                    <h2>Your Registered Subjects</h2>
                    ${table}
                </body>
                </html>
            `);
            win.document.close();
            win.print();
        }

        function logoutWithMessage(e) {
            e.preventDefault();
            alert('See You Again!');
            window.location.href = 'logout.php';
        }

        function showDeleteProfileModal() {
            document.getElementById('deleteProfileModal').style.display = 'flex';
            document.getElementById('deleteConfirmInput').value = '';
            document.getElementById('deleteProfileError').innerText = '';
        }
        function closeDeleteProfileModal() {
            document.getElementById('deleteProfileModal').style.display = 'none';
        }
        function confirmDeleteProfile() {
            const input = document.getElementById('deleteConfirmInput').value.trim();
            if (input !== 'DELETE') {
                document.getElementById('deleteProfileError').innerText = 'You must type DELETE to confirm.';
                return;
            }
            // Redirect to delete_profile.php (you must create this file)
            window.location.href = 'delete_profile.php';
        }

        document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll('.progress-animate').forEach(function(bar) {
        const value = bar.getAttribute('data-value');
        const color = bar.getAttribute('data-color');
        bar.style.background = color;
        setTimeout(() => {
            bar.style.transition = "width 1.2s cubic-bezier(.4,2,.6,1)";
            bar.style.width = value + "%";
        }, 200);
    });
});
    </script>
</body>
</html>
